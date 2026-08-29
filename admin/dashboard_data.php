<?php
/**
 * dashboard_data.php
 * Sales Dashboard Data API — thesalty_Ecomm_Dev
 * 
 * Expects DB credentials via environment variables or edit the $config block below.
 * Usage: dashboard_data.php?section=<name>&range=<7|30|90|365>
 */

header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: same-origin');

require_once(__DIR__ . '/../vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ── DB CONFIG ──────────────────────────────────────────────────────────────────
$config = [
    'host' => $_ENV['DATABASE_SERVER'] ?: 'localhost:5522',
    'user' => $_ENV['DATABASE_USERNAME'] ?: 'thesalty_rashmi',
    'pass' => $_ENV['DATABASE_PASSWORD'] ?: 'Abhiabhi@13',
    'name' => $_ENV['SHOP_DATABASE'] ?: 'thesalty_Ecomm_Dev',
];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

$section = $_GET['section'] ?? 'overview';
$range   = (int)($_GET['range'] ?? 30);
$range   = in_array($range, [7, 30, 90, 365]) ? $range : 30;

// ── POST: update order ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $section === 'update_order') {
    $body = json_decode(file_get_contents('php://input'), true);
    $orderId       = isset($body['order_id'])       ? (int)$body['order_id']       : 0;
    $orderStatus   = $body['order_status']   ?? null;
    $paymentStatus = isset($body['payment_status']) ? (int)$body['payment_status'] : null;

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing order_id']);
        exit;
    }

    $allowed_statuses = ['Pending', 'Completed', 'Canceled'];

    $sets   = [];
    $params = [];

    if ($orderStatus !== null) {
        if (!in_array($orderStatus, $allowed_statuses, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid order_status value']);
            exit;
        }
        $sets[]   = 'OrderStatus = ?';
        $params[] = $orderStatus;
    }

    if ($paymentStatus !== null) {
        if (!in_array($paymentStatus, [0, 1], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payment_status value (must be 0 or 1)']);
            exit;
        }
        $sets[]   = 'PaymentStatus = ?';
        $params[] = $paymentStatus;
    }

    if (empty($sets)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nothing to update']);
        exit;
    }

    $params[] = $orderId;

    try {
        $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $sets) . " WHERE OrderId = ?");
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        // Return the fresh row so the UI can update in place
        $updated = q1($pdo,
            "SELECT o.OrderId,
                    COALESCE(u.Name, ga.ReceiverName, 'Guest') AS customer,
                    o.TotalAmount,
                    o.OrderStatus   AS status,
                    o.PaymentStatus AS paid,
                    o.CreatedAt
             FROM orders o
             LEFT JOIN users u            ON u.UserId   = o.UserId
             LEFT JOIN guestaddresses ga  ON ga.OrderId = o.OrderId
             WHERE o.OrderId = ?", [$orderId]);

        echo json_encode(['success' => true, 'order' => $updated]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

function q(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function q1(PDO $pdo, string $sql, array $params = []): array {
    $rows = q($pdo, $sql, $params);
    return $rows[0] ?? [];
}

// ── SECTIONS ──────────────────────────────────────────────────────────────────

switch ($section) {

    // KPI summary cards
    case 'overview':
        $since = date('Y-m-d', strtotime("-{$range} days"));

        $revenue = q1($pdo,
            "SELECT COALESCE(SUM(TotalAmount),0) AS total, COUNT(*) AS orders
             FROM orders
             WHERE OrderStatus != 'Canceled' AND PaymentStatus = 1
               AND CreatedAt >= ?", [$since]);

        $prev_since = date('Y-m-d', strtotime("-" . ($range * 2) . " days"));
        $prev = q1($pdo,
            "SELECT COALESCE(SUM(TotalAmount),0) AS total
             FROM orders
             WHERE OrderStatus != 'Canceled' AND PaymentStatus = 1
               AND CreatedAt >= ? AND CreatedAt < ?", [$prev_since, $since]);

        $users = q1($pdo,
            "SELECT COUNT(*) AS new_users FROM users WHERE UserJoiningDate >= ?", [$since]);

        $aov = ($revenue['orders'] > 0)
            ? round($revenue['total'] / $revenue['orders'], 2) : 0;

        $prev_revenue = (float)$prev['total'];
        $curr_revenue = (float)$revenue['total'];
        $growth = $prev_revenue > 0
            ? round((($curr_revenue - $prev_revenue) / $prev_revenue) * 100, 1) : null;

        $pending = q1($pdo,
            "SELECT COUNT(*) AS cnt FROM orders WHERE OrderStatus = 'Pending'");

        echo json_encode([
            'revenue'      => $curr_revenue,
            'orders'       => (int)$revenue['orders'],
            'aov'          => $aov,
            'new_users'    => (int)$users['new_users'],
            'revenue_growth' => $growth,
            'pending_orders' => (int)$pending['cnt'],
        ]);
        break;

    // Revenue over time (line chart)
    case 'revenue_chart':
        $since = date('Y-m-d', strtotime("-{$range} days"));
        $fmt   = $range <= 30 ? '%Y-%m-%d' : '%Y-%u'; // daily or weekly
        $rows  = q($pdo,
            "SELECT DATE_FORMAT(CreatedAt, ?) AS period,
                    COALESCE(SUM(TotalAmount),0) AS revenue,
                    COUNT(*) AS orders
             FROM orders
             WHERE OrderStatus != 'Canceled' AND PaymentStatus = 1
               AND CreatedAt >= ?
             GROUP BY period
             ORDER BY period ASC", [$fmt, $since]);
        echo json_encode($rows);
        break;

    // Order status breakdown (donut)
    case 'order_status':
        $rows = q($pdo,
            "SELECT OrderStatus AS status, COUNT(*) AS cnt FROM orders GROUP BY OrderStatus");
        echo json_encode($rows);
        break;

    // Top products by revenue
    case 'top_products':
        $since = date('Y-m-d', strtotime("-{$range} days"));
        $rows  = q($pdo,
            "SELECT p.ProductName AS name,
                    SUM(oi.Quantity) AS units_sold,
                    SUM(oi.Price * oi.Quantity) AS revenue
             FROM orderitems oi
             JOIN products p ON p.ProductId = oi.ProductId
             JOIN orders o ON o.OrderId = oi.OrderId
             WHERE o.OrderStatus != 'Canceled' AND o.PaymentStatus = 1
               AND o.CreatedAt >= ?
             GROUP BY p.ProductId
             ORDER BY revenue DESC
             LIMIT 10", [$since]);
        echo json_encode($rows);
        break;

    // Sales by category
    case 'category_sales':
        $since = date('Y-m-d', strtotime("-{$range} days"));
        $rows  = q($pdo,
            "SELECT c.CategoryName AS category,
                    SUM(oi.Price * oi.Quantity) AS revenue
             FROM orderitems oi
             JOIN products p ON p.ProductId = oi.ProductId
             JOIN categories c ON c.CategoryId = p.CategoryId
             JOIN orders o ON o.OrderId = oi.OrderId
             WHERE o.OrderStatus != 'Canceled' AND o.PaymentStatus = 1
               AND o.CreatedAt >= ?
             GROUP BY c.CategoryId
             ORDER BY revenue DESC", [$since]);
        echo json_encode($rows);
        break;

    // Customer acquisition source
    case 'acquisition':
        $rows = q($pdo,
            "SELECT COALESCE(SourceReferral,'Unknown') AS source, COUNT(*) AS cnt
             FROM users
             GROUP BY SourceReferral
             ORDER BY cnt DESC");
        echo json_encode($rows);
        break;

    // Recent orders table
    case 'recent_orders':
        $limit  = max(1, min(200, (int)($_GET['limit'] ?? 50)));
        $search = trim($_GET['search'] ?? '');
        $status_filter = $_GET['status_filter'] ?? '';

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(o.OrderId = ? OR u.Name LIKE ? OR ga.ReceiverName LIKE ?)';
            $params[] = is_numeric($search) ? (int)$search : 0;
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (in_array($status_filter, ['Pending','Completed','Canceled'], true)) {
            $where[]  = 'o.OrderStatus = ?';
            $params[] = $status_filter;
        }

        $rows = q($pdo,
            "SELECT o.OrderId,
                    COALESCE(u.Name, ga.ReceiverName, 'Guest') AS customer,
                    COALESCE(u.UserEmail, ga.Email, '')         AS email,
                    COALESCE(u.UserPhone, ga.Phone, '')         AS phone,
                    o.TotalAmount,
                    o.ShippingAmount,
                    o.OrderStatus   AS status,
                    o.PaymentStatus AS paid,
                    o.CreatedAt,
                    o.UpdatedAt
             FROM orders o
             LEFT JOIN users u           ON u.UserId   = o.UserId
             LEFT JOIN guestaddresses ga ON ga.OrderId = o.OrderId
             WHERE " . implode(' AND ', $where) . "
             ORDER BY o.CreatedAt DESC
             LIMIT $limit", $params);
        echo json_encode($rows);
        break;

    // Low stock alerts
    case 'low_stock':
        $rows = q($pdo,
            "SELECT ProductName AS name, SKU AS sku, StockQuantity AS stock
             FROM products
             WHERE IsAvailable = 1 AND StockQuantity IS NOT NULL AND StockQuantity <= 10
             ORDER BY StockQuantity ASC
             LIMIT 10");
        echo json_encode($rows);
        break;

    // Coupon usage
    case 'coupons':
        $rows = q($pdo,
            "SELECT code, type, uses, max_uses, discount_value,
                    expiration_date, stackable
             FROM coupons
             ORDER BY uses DESC
             LIMIT 10");
        echo json_encode($rows);
        break;

    // Order items for a specific order
    case 'order_items':
        $orderId = (int)($_GET['order_id'] ?? 0);
        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing order_id']);
            break;
        }
        $rows = q($pdo,
            "SELECT oi.OrderItemId,
                    p.ProductName   AS name,
                    p.SKU           AS sku,
                    p.ProductImage  AS image,
                    oi.Customization,
                    oi.Price        AS unit_price,
                    oi.Quantity     AS qty,
                    (oi.Price * oi.Quantity) AS line_total
             FROM orderitems oi
             JOIN products p ON p.ProductId = oi.ProductId
             WHERE oi.OrderId = ?
             ORDER BY oi.OrderItemId ASC", [$orderId]);
        echo json_encode($rows);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown section']);
}