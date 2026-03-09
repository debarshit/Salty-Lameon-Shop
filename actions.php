<?php

    include("functions.php");
    require_once 'includes/auth/auth.service.php';
    require_once 'includes/auth/auth.helper.php';
    require_once 'includes/mail.helper.php';
    
    require 'vendor/autoload.php';

  use Firebase\JWT\JWT;
  use Firebase\JWT\Key;

    if ($_GET['action'] == 'storeSessionCookie') {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (isset($data['cookieValue'])) {
            $decodedValue = base64_decode($data['cookieValue']);
            
            $tokens = json_decode($decodedValue, true);

            if (isset($tokens['accessToken']) && isset($tokens['refreshToken'])) {
                $accessToken = $tokens['accessToken'];
                $refreshToken = $tokens['refreshToken'];
    
                // Create a cookie with HttpOnly, Secure, SameSite, and expiration of 7 days
                $cookieValue = json_encode([
                    'accessToken' => $accessToken,
                    'refreshToken' => $refreshToken,
                ]);
    
                // Set the cookie with proper security options
                setcookie('user_session', $data['cookieValue'], [
                    'expires' => time() + (86400 * 7),  // 7 days
                    'path' => '/',
                    'domain' => $_ENV['APP_ENV'] === "production" ? ".thesaltylameon.com" : null,
                    // 'secure' => ($_SERVER['HTTPS'] ?? false) === 'on',  // Only send cookie over HTTPS
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
    
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid token data']);
            }
        }  else {
            echo json_encode(['success' => false, 'message' => 'No cookie data provided']);
        }
    }

    if ($_GET['action'] === 'deleteSessionCookie') {
    header('Content-Type: application/json');

    if (!isset($_COOKIE['user_session'])) {
        echo json_encode(['success' => true, 'message' => 'Already logged out.']);
        exit;
    }

    $session = json_decode(base64_decode($_COOKIE['user_session']), true);

    if (!empty($session['refreshToken'])) {
        deleteRefreshToken($session['refreshToken']);
    }

    // Delete cookie
    setcookie('user_session', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => $_ENV['APP_ENV'] === 'production' ? '.thesaltylameon.com' : null,
        // 'secure' => ($_SERVER['HTTPS'] ?? false) === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully.'
    ]);
    exit;
}
    //not sure about its usage whether it is required
    if ($_GET['action'] == 'refreshToken') {
        if (!isset($_COOKIE['user_session'])) {
            http_response_code(401);
            echo json_encode(['message' => 'No session']);
            exit;
        }

        $cookie = json_decode(base64_decode($_COOKIE['user_session']), true);

        $refreshToken = $cookie['refreshToken'];

        global $shopLink;

        $stmt = $shopLink->prepare("
            SELECT userId, expiresAt
            FROM user_refresh_tokens
            WHERE userRefreshToken = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $refreshToken);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid refresh token']);
            exit;
        }

        if (strtotime($result['expiresAt']) < time()) {
            deleteRefreshToken($refreshToken);
            http_response_code(401);
            echo json_encode(['message' => 'Refresh token expired']);
            exit;
        }

        // fetch user
        $stmt = $shopLink->prepare("SELECT * FROM users WHERE UserId = ?");
        $stmt->bind_param("i", $result['userId']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $payload = buildTokenPayload($user);
        $newAccessToken = generateAccessToken($payload);

        $cookieValue = base64_encode(json_encode([
            'accessToken' => $newAccessToken,
            'refreshToken' => $refreshToken
        ]));

        setcookie('user_session', $cookieValue, [
            'expires' => time() + (86400 * 7),
            'path' => '/',
            'domain' => $_ENV['APP_ENV'] === "production" ? ".thesaltylameon.com" : null,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        echo json_encode([
            'success' => true,
            'accessToken' => $newAccessToken
        ]);
    }

    if ($_GET['action'] == 'fetchProductIds') {
        $offset = $_GET['offset'] ?? 0;
        $limit = $_GET['limit'] ?? 8;
        $categoryId = $_GET['category_id'] ?? null;
    
        $productIds = fetchProductIds($limit, $offset, $categoryId);
    
        foreach ($productIds as $productId) {
            $product = fetchProductDetails($productId);
            if ($product) {
                echo generateProductHTML($product, $productId);
            }
        }
    }

    if ($_GET['action'] == "addToCart") {      
        $error = "";
        $response = array();
    
        $accessToken = getAccessTokenFromSession();
        if (isset($accessToken)) {
            $userId = getUserIdFromAccessToken($accessToken);
    
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Unable to decode userId. ']);
                exit;
            }
    
            $productId = $_POST['productId'];
            $customization = $_POST['customization'] ?? null;
            $quantity = $_POST['quantity'];
    
            // Check if the item already exists in the cart
            $queryCheck = "SELECT Quantity FROM `carts` WHERE `UserId` = ? AND `ProductId` = ? AND `Customization` = ?";
            
            if ($stmtCheck = mysqli_prepare($shopLink, $queryCheck)) {
                mysqli_stmt_bind_param($stmtCheck, 'iis', $userId, $productId, $customization);
                mysqli_stmt_execute($stmtCheck);
                mysqli_stmt_bind_result($stmtCheck, $existingQuantity);
                mysqli_stmt_fetch($stmtCheck);
                mysqli_stmt_close($stmtCheck);
            }
    
            if (isset($existingQuantity)) {
                // Update the quantity if the item exists
                $newQuantity = $existingQuantity + $quantity;
                $queryUpdate = "UPDATE `carts` SET `Quantity` = ? WHERE `UserId` = ? AND `ProductId` = ? AND `Customization` = ?";
    
                if ($stmtUpdate = mysqli_prepare($shopLink, $queryUpdate)) {
                    mysqli_stmt_bind_param($stmtUpdate, 'iisi', $newQuantity, $userId, $productId, $customization);
                    if (mysqli_stmt_execute($stmtUpdate)) {
                        $response = ['message' => '1'];
                    } else {
                        $error = mysqli_error($shopLink);
                    }
                    mysqli_stmt_close($stmtUpdate);
                }
            } else {
                // Insert new item if it does not exist
                $queryInsert = "INSERT INTO `carts` (`UserId`, `ProductId`, `Customization`, `Quantity`) VALUES (?, ?, ?, ?)";
    
                if ($stmtInsert = mysqli_prepare($shopLink, $queryInsert)) {
                    mysqli_stmt_bind_param($stmtInsert, 'iisi', $userId, $productId, $customization, $quantity);
                    if (mysqli_stmt_execute($stmtInsert)) {
                        $response = ['message' => '1'];
                    } else {
                        $error = mysqli_error($shopLink);
                    }
                    mysqli_stmt_close($stmtInsert);
                }
            }
    
            if ($error != "") {
                $response['message'] = $error;
            }
    
            echo json_encode($response);
            exit();
        } else {
            $response = ['message' => "Login to add product to cart"];
            echo json_encode($response);
            exit();
        }
    }

    if ($_GET['action'] == 'fetchUserAddresses') {
        $addresses = fetchUserAddresses();
        
        if (!empty($addresses)) {
            foreach ($addresses as $address) {
                echo '<div class="address__container">
                        <address class="address__name">
                            ' . htmlspecialchars($address['ReceiverName']) . '
                        </address>
                        <address class="address__one">
                            ' . htmlspecialchars($address['AddressLine1']) .'
                        </address>
                        <address class="address__two">
                            ' . htmlspecialchars($address['AddressLine2']) . '
                        </address>
                        <address class="address__pin">
                            ' . htmlspecialchars($address['PostalCode']) . '
                        </address>
                        <p class="address__city">
                            ' . htmlspecialchars($address['City']) .'
                        </p>
                        <p class="address__state">
                            ' . htmlspecialchars($address['State']) .'
                        </p>
                        <p class="address__country">
                            ' . htmlspecialchars($address['Country']) .'
                        </p>
                        <a href="accounts#editModal" class="edit" data-id="' . $address['AddressId'] . 
                          '" data-receiver="' . htmlspecialchars($address['ReceiverName']) . 
                          '" data-line1="' . htmlspecialchars($address['AddressLine1']) . 
                          '" data-line2="' . htmlspecialchars($address['AddressLine2']) . 
                          '" data-city="' . htmlspecialchars($address['City']) . 
                          '" data-state="' . htmlspecialchars($address['State']) . 
                          '" data-postal="' . htmlspecialchars($address['PostalCode']) . 
                          '" data-country="' . htmlspecialchars($address['Country']) . '">
                            Edit
                        </a>
                        <a href="#" class="delete" data-id="' . $address['AddressId'] . '">Delete</a>
                      </div>';
            }
        } else {
            echo '<p>No addresses found.</p>';
        }
        exit;
    } 
    
    if ($_GET['action'] == 'updateUserAddress') {
        $accessToken = getAccessTokenFromSession();
        if (!$accessToken) {
            echo json_encode(['success' => false, 'message' => 'No access token found.']);
            exit;
        }

        $userId = getUserIdFromAccessToken($accessToken);
    
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Unable to decode userId. ']);
            exit;
        }
    
        $addressId = $_POST['addressId'] ?? null;
        $receiver = $_POST['receiver'];
        $phone = $_POST['phone'];
        $addressLine1 = $_POST['addressLine1'];
        $addressLine2 = $_POST['addressLine2'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $postalCode = $_POST['postalCode'];
        $country = $_POST['country'];

         // Validate phone number (must be exactly 10 digits)
        if (!preg_match('/^\d{10}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Phone number must be exactly 10 digits.']);
            exit;
        }
    
        if ($addressId) {
            // Update address
            $stmt = $shopLink->prepare("UPDATE useraddresses SET ReceiverName = ?, Phone = ?, AddressLine1 = ?, AddressLine2 = ?, City = ?, State = ?, PostalCode = ?, Country = ? WHERE AddressId = ?");
            $stmt->bind_param("ssssssssi", $receiver, $phone, $addressLine1, $addressLine2, $city, $state, $postalCode, $country, $addressId);
        } else {
            // Add new address
            $stmt = $shopLink->prepare("INSERT INTO useraddresses (UserId, ReceiverName, Phone, AddressLine1, AddressLine2, City, State, PostalCode, Country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $userId, $receiver, $phone, $addressLine1, $addressLine2, $city, $state, $postalCode, $country);
        }
    
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Address saved successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving address.']);
        }
        exit;
    }

    if ($_GET['action'] == 'deleteUserAddress') {
        // Get access token from session
        $accessToken = getAccessTokenFromSession();
        if (!$accessToken) {
            echo json_encode(['success' => false, 'message' => 'No access token found.']);
            exit;
        }
        
        // Decode user ID from access token
        $userId = getUserIdFromAccessToken($accessToken);
    
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Unable to decode userId. ']);
            exit;
        }
    
        // Get address ID from the POST data
        $data = json_decode(file_get_contents('php://input'), true);
        $addressId = isset($data['addressId']) ? intval($data['addressId']) : 0;
        
        if ($addressId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid address ID']);
            exit;
        }
    
        // Verify this address belongs to the current user before deleting
        $checkStmt = $shopLink->prepare("SELECT COUNT(*) FROM useraddresses WHERE AddressId = ? AND UserId = ?");
        $checkStmt->bind_param("ii", $addressId, $userId);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();
        
        if ($count == 0) {
            echo json_encode(['success' => false, 'message' => 'Address not found or not authorized']);
            exit;
        }
        
        // Delete the address
        $deleteStmt = $shopLink->prepare("DELETE FROM useraddresses WHERE AddressId = ? AND UserId = ?");
        $deleteStmt->bind_param("ii", $addressId, $userId);
        
        if ($deleteStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete address: ' . $shopLink->error]);
        }
        $deleteStmt->close();
        exit;
    }

    if ($_GET['action'] == 'updateProfile') {
        $requestData = json_decode(file_get_contents('php://input'), true);

        if (isset($requestData['property']) && isset($requestData['value'])) {
            $property = $requestData['property'];
            $value = $requestData['value'];

            $updateResult = updateUserData($property, $value);

            echo json_encode($updateResult);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required data'
            ]);
        }
    }

    if ($_GET['action'] == 'removeFromCart') {
        $productId = (int)$_POST['productId'];

        $accessToken = getAccessTokenFromSession();
    
        // Decode the user ID using the access token
        $userId = getUserIdFromAccessToken($accessToken);
    
        if (!$userId) {
           echo json_encode(['success' => false]);
        } else {
            // Remove the product from the cart
            $query = "DELETE FROM carts WHERE UserId = ? AND ProductId = ?";
            $stmt = $shopLink->prepare($query);
            $stmt->bind_param('ii', $userId, $productId);
    
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
    
            $stmt->close();
        }
    }

    if ($_GET['action'] == 'updateQuantity') {
        $productId = (int)$_POST['productId'];
        $quantity = (int)$_POST['quantity'];

        $accessToken = getAccessTokenFromSession();
        
        // Decode the user ID using the access token
        $userId = getUserIdFromAccessToken($accessToken);
    
        if (!$userId) {
            echo json_encode(['success' => false]);
            exit;
        } else {
            $productId = (int)$_POST['productId'];
            $quantity = (int)$_POST['quantity'];

            // Update the cart with the new quantity
            $query = "UPDATE carts SET Quantity = ? WHERE UserId = ? AND ProductId = ?";
            $stmt = $shopLink->prepare($query);
            $stmt->bind_param('iii', $quantity, $userId, $productId);

            if ($stmt->execute()) {
                // Fetch the new subtotal and the total cart value
                $subtotalQuery = "SELECT p.NewPrice, c.Quantity FROM carts c JOIN products p ON c.ProductId = p.ProductId WHERE c.UserId = ? AND c.ProductId = ?";
                $subtotalStmt = $shopLink->prepare($subtotalQuery);
                $subtotalStmt->bind_param('ii', $userId, $productId);
                $subtotalStmt->execute();
                $subtotalResult = $subtotalStmt->get_result();
                $product = $subtotalResult->fetch_assoc();

                $newSubtotal = $product['NewPrice'] * $quantity;

                // Get the total cart price
                $cartTotalQuery = "SELECT SUM(p.NewPrice * c.Quantity) AS cartTotal FROM carts c JOIN products p ON c.ProductId = p.ProductId WHERE c.UserId = ?";
                $cartTotalStmt = $shopLink->prepare($cartTotalQuery);
                $cartTotalStmt->bind_param('i', $userId);
                $cartTotalStmt->execute();
                $cartTotalResult = $cartTotalStmt->get_result();
                $cartTotal = $cartTotalResult->fetch_assoc()['cartTotal'];

                echo json_encode([
                    'success' => true,
                    'newSubtotal' => $newSubtotal,
                    'newCartTotal' => $cartTotal
                ]);
            } else {
                echo json_encode(['success' => false]);
            }

            $stmt->close();
            $subtotalStmt->close();
            $cartTotalStmt->close();
        }
    }

    if ($_GET['action'] == 'calculateShipping') {
        if (isset($_POST['pincode']) && !empty($_POST['pincode'])) {
            // Sanitize and validate pincode input
            $pincode = filter_var($_POST['pincode'], FILTER_SANITIZE_NUMBER_INT);
    
            // Calculate shipping charges based on the pincode
            $shippingPrice = calculateShippingChargesByPincode($pincode);
    
            if ($shippingPrice !== null) {
                // If the shipping price is valid, return it
                echo json_encode([
                    'success' => true,
                    'shipping' => $shippingPrice
                ]);
            } else {
                // If shipping couldn't be calculated (e.g., invalid pincode)
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid pincode or no shipping available for this pincode.'
                ]);
            }
        } else {
            // Pincode is missing or empty
            echo json_encode([
                'success' => false,
                'message' => 'Please provide a valid pincode.'
            ]);
        }
    }

    if ($_GET['action'] == 'placeOrder') {
        $accessToken = getAccessTokenFromSession();
        $userId = null;
        $isGuest = isset($_POST['isGuest']) && $_POST['isGuest'] === 'true';
    
        if ($accessToken) {
            $userId = getUserIdFromAccessToken($accessToken);
    
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Unable to decode userId. ']);
                exit;
            }
    
        }
    
        $paymentStatus = ($_POST['paymentMethod'] == 'online') ? 1 : 0; // 1 for online, 0 for offline
        $shippingCharge = $_POST['shippingCharge'];
        $cartTotal = $_POST['totalAmount'];
        $addressId = $isGuest ? null : $_POST['addressId']; // Set addressId to null for guests
    
        // Get affiliateId from cookie
        $affiliateId = null;
        if (isset($_COOKIE['affiliate_info'])) {
            $affiliateId = $_COOKIE['affiliate_info'];
            $decodedAffiliateId = base64_decode($affiliateId);
            $decodedArray = json_decode($decodedAffiliateId, true);
            $affiliateId = $decodedArray['affiliateId'];
        }

        mysqli_begin_transaction($shopLink);
    
        try {
            // Insert into the orders table
            $query = "INSERT INTO `orders`(`UserId`, `AddressId`, `PaymentStatus`, `TotalAmount`, `ShippingAmount`, `AffiliateId`) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($shopLink, $query);
            mysqli_stmt_bind_param($stmt, 'iiisis', $userId, $addressId, $paymentStatus, $cartTotal, $shippingCharge, $affiliateId);
        
            if (mysqli_stmt_execute($stmt)) {
                $orderId = mysqli_insert_id($shopLink);

                // If guest user, store address in guestaddresses table
                if ($isGuest) {
                    $guestName = $_POST['guestName'];
                    $guestEmail = $_POST['guestEmail'];
                    $guestPhone = $_POST['guestPhone'];
                    $guestAddressLine1 = $_POST['guestAddressLine1'];
                    $guestAddressLine2 = $_POST['guestAddressLine2'] ?: null;
                    $guestCity = $_POST['guestCity'];
                    $guestState = $_POST['guestState'];
                    $guestPincode = $_POST['guestPincode'];
                    $guestCountry = $_POST['guestCountry'];
                    
                    $guestAddressQuery = "INSERT INTO `guestaddresses`
                        (`Email`, `ReceiverName`, `AddressLine1`, `AddressLine2`, `City`, `State`, `PostalCode`, `Country`, `Phone`, `OrderId`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $guestAddressStmt = mysqli_prepare($shopLink, $guestAddressQuery);
                    mysqli_stmt_bind_param($guestAddressStmt, 'sssssssssi',
                        $guestEmail, $guestName, $guestAddressLine1, $guestAddressLine2,
                        $guestCity, $guestState, $guestPincode, $guestCountry, $guestPhone, $orderId);
                    
                    if (!mysqli_stmt_execute($guestAddressStmt)) {
                        throw new Exception("Failed to save guest address information");
                    }
                }
        
                // Insert into the order_items table
                if (isset($_POST['cartItems']) && is_array($_POST['cartItems'])) {
                    foreach ($_POST['cartItems'] as $item) {
                        $productId = $item['productId'];
                        $customization = $item['customization'];
                        $price = $item['price'];
                        $quantity = $item['quantity'];
        
                        $orderItemQuery = "INSERT INTO `orderitems`(`OrderId`, `ProductId`, `Customization`, `Price`, `Quantity`) VALUES (?, ?, ?, ?, ?)";
                        $orderItemStmt = mysqli_prepare($shopLink, $orderItemQuery);
                        mysqli_stmt_bind_param($orderItemStmt, 'iisdi', $orderId, $productId, $customization, $price, $quantity);
                        if (!mysqli_stmt_execute($orderItemStmt)) {
                            throw new Exception("Failed to add order items");
                        }
        
                        // Delete cart items only if userId is not null (i.e., a logged-in user)
                        if ($userId !== null) {
                            $cartItemDeleteQuery = "DELETE FROM `carts` WHERE `UserId` = ? AND `ProductId` = ?";
                            $deleteStmt = mysqli_prepare($shopLink, $cartItemDeleteQuery);
                            mysqli_stmt_bind_param($deleteStmt, 'ii', $userId, $productId);
                            mysqli_stmt_execute($deleteStmt);
                        }
                    }
                }
                mysqli_commit($shopLink);
                echo json_encode(['success' => true, 'orderId' => $orderId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: Unable to create order.']);
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($shopLink);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    if ($_GET['action'] == "submitReview")
    {      
      $error = "";
      $response = array();

      $accessToken = getAccessTokenFromSession();
      if (isset($accessToken)) {
        $userId = getUserIdFromAccessToken($accessToken);
    
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Unable to decode userId. ']);
            exit;
        }
        $productId = $_POST['productId'];
        $rating = $_POST['rating'];
        $review = $_POST['review'] ?? null;

        $query = "INSERT INTO `productreviews`(`ProductId`, `UserId`, `Rating`, `Review`) VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($shopLink, $query);
        mysqli_stmt_bind_param($stmt, 'iids', $productId, $userId, $rating, $review);
      
        if (mysqli_stmt_execute($stmt)) {
          // Insertion successful
          $response = ['message' => '1'];   //replace 1 with successful
          echo json_encode($response);
          exit();
        } else{
            // $error = "There was a problem - please try again later";
            $error = mysqli_error($shopLink);
        }
        if ($error != "")
        {
            
            $response['message'] = $error;
            echo json_encode($response);
            exit();
            
        }  
    } else {
        $error = "Error: No access token found.";
      } 
    }

    if($_GET['action'] == "fetchSearchResults") {
        $query = $_GET['query'];

        if ($query !== '') {

            $stmt = mysqli_prepare($shopLink, "SELECT ProductId, ProductName FROM products WHERE LOWER(ProductName) LIKE LOWER(?) LIMIT 5");
            $likeQuery = "%" . $query . "%";
            mysqli_stmt_bind_param($stmt, 's', $likeQuery);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $products = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }

            mysqli_stmt_close($stmt);

            echo json_encode($products);
        } else {
            echo json_encode([]);
        }
    }

    if ($_GET['action'] == 'getCartCount') {
        $cartCount = displayCartNumber();
        echo json_encode(['success' => true, 'cartCount' => $cartCount]);
        exit;
    }

    //For when a guest user with items in cart login
    if ($_GET['action'] == "mergeGuestCart") {
        $response = array();
        $accessToken = getAccessTokenFromSession();
    
        if (isset($accessToken)) {
            $userId = getUserIdFromAccessToken($accessToken);
    
            if ($userId) {
                $guestCartData = json_decode(file_get_contents('php://input'), true); // Get guest cart data sent from JavaScript
    
                if (is_array($guestCartData)) {
                    foreach ($guestCartData as $item) {
                        $productId = $item['productId'];
                        $customization = $item['customization'] ?? null;
                        $quantity = $item['quantity'];
    
                        // Check if the item exists in the user's cart in the database
                        $queryCheck = "SELECT Quantity FROM `carts` WHERE `UserId` = ? AND `ProductId` = ? AND `Customization` = ?";
                        if ($stmtCheck = mysqli_prepare($shopLink, $queryCheck)) {
                            mysqli_stmt_bind_param($stmtCheck, 'iis', $userId, $productId, $customization);
                            mysqli_stmt_execute($stmtCheck);
                            mysqli_stmt_bind_result($stmtCheck, $existingQuantity);
                            mysqli_stmt_fetch($stmtCheck);
                            mysqli_stmt_close($stmtCheck);
    
                            if (isset($existingQuantity)) {
                                // Update quantity
                                $newQuantity = $existingQuantity + $quantity;
                                $queryUpdate = "UPDATE `carts` SET `Quantity` = ? WHERE `UserId` = ? AND `ProductId` = ? AND `Customization` = ?";
                                if ($stmtUpdate = mysqli_prepare($shopLink, $queryUpdate)) {
                                    mysqli_stmt_bind_param($stmtUpdate, 'iisi', $newQuantity, $userId, $productId, $customization);
                                    mysqli_stmt_execute($stmtUpdate);
                                    mysqli_stmt_close($stmtUpdate);
                                }
                            } else {
                                // Insert new item
                                $queryInsert = "INSERT INTO `carts` (`UserId`, `ProductId`, `Customization`, `Quantity`) VALUES (?, ?, ?, ?)";
                                if ($stmtInsert = mysqli_prepare($shopLink, $queryInsert)) {
                                    mysqli_stmt_bind_param($stmtInsert, 'iisi', $userId, $productId, $customization, $quantity);
                                    mysqli_stmt_execute($stmtInsert);
                                    mysqli_stmt_close($stmtInsert);
                                }
                            }
                        }
                    }
                    $response['message'] = 'Guest cart merged successfully.';
                } else {
                    $response['message'] = 'No guest cart data received.';
                }
            } else {
                $response['message'] = 'Error: Unable to decode userId for merging.';
            }
        } else {
            $response['message'] = 'User not logged in for merging.';
        }
    
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    if ($_GET['action'] == "displayGuestCart") {
        if (isset($_POST['guestCart'])) {
            $guestCartJson = $_POST['guestCart'];
            // Call the displayCartList function with the guest cart data
            displayCartList(true, $guestCartJson);
        } else {
            echo '<tr><td colspan="6">Error: Guest cart data not provided.</td></tr>';
        }
    }

    if ($_GET['action'] == "calculateGuestCartTotals") {
        $response = ['success' => false, 'message' => 'Guest cart data not provided.'];

            if (isset($_POST['guestCart'])) {
                $guestCartJson = $_POST['guestCart'];
                $guestCart = json_decode($guestCartJson, true);

                if ($guestCart && is_array($guestCart)) {
                    $cartSubtotal = 0;
                    $productIds = [];
                    foreach ($guestCart as $item) {
                        $productIds[] = $item['productId'];
                    }

                    if (!empty($productIds)) {
                         // Fetch product details (especially prices and customization options) from DB
                         $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                         $query = "SELECT ProductId, NewPrice FROM products WHERE ProductId IN ($placeholders)";
                         $stmt = $shopLink->prepare($query);
                         $types = str_repeat('i', count($productIds));
                         $stmt->bind_param($types, ...$productIds);
                         $stmt->execute();
                         $result = $stmt->get_result();
                         $productPrices = [];
                         while($row = $result->fetch_assoc()) {
                             $productPrices[$row['ProductId']] = $row['NewPrice'];
                         }
                         $stmt->close();

                          // Fetch customization options if necessary
                         $customizationPrices = [];
                         $customizationQuery = "SELECT ProductId, CustomizationOption FROM product_customizations WHERE ProductId IN ($placeholders)";
                         $customizationStmt = $shopLink->prepare($customizationQuery);
                         $customizationStmt->bind_param($types, ...$productIds);
                         $customizationStmt->execute();
                         $customizationResult = $customizationStmt->get_result();
                         while($row = $customizationResult->fetch_assoc()) {
                              $customizationPrices[$row['ProductId']] = json_decode($row['CustomizationOption'], true);
                         }
                         $customizationStmt->close();


                        // Calculate subtotal based on fetched prices
                        foreach ($guestCart as $item) {
                            $productId = $item['productId'];
                            $quantity = $item['quantity'];
                             $customization = isset($item['customization']) && $item['customization'] !== 'null' ? $item['customization'] : null;

                            if (isset($productPrices[$productId])) {
                                 $price = $productPrices[$productId];
                                  // Apply customization price if available
                                 if ($customization && isset($customizationPrices[$productId]) && isset($customizationPrices[$productId][$customization])) {
                                     $price = (float) $customizationPrices[$productId][$customization];
                                 }
                                $cartSubtotal += $price * $quantity;
                            }
                        }
                    }


                    // Calculate shipping charge for guest user
                    $shippingCharge = 0;
                    $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : (isset($_GET['pincode']) ? trim($_GET['pincode']) : null);

                    if ($pincode) {
                        $shippingCharge = calculateShippingChargesByPincode($pincode);
                    } else {
                         // Default shipping for guests without pincode? Or prompt for pincode?
                         // For now, let's assume 0 until pincode is provided.
                         $shippingCharge = 50;
                    }

                    $response = [
                        'success' => true,
                        'subtotal' => $cartSubtotal,
                        'shipping' => $shippingCharge,
                        'total' => $cartSubtotal + $shippingCharge
                    ];

                } else {
                    $response['message'] = 'Invalid guest cart data.';
                     // Return zero totals for empty or invalid cart
                     $response = [
                        'success' => true,
                        'subtotal' => 0,
                        'shipping' => 0,
                        'total' => 0
                    ];
                }
            }

            header('Content-Type: application/json');
            echo json_encode($response);
    }

    if ($_GET['action'] == "getCartTotals") {
        $response = ['success' => false, 'message' => 'User not logged in or error fetching cart.'];
        $accessToken = getAccessTokenFromSession(); // Assuming this function exists

        if (isset($accessToken)) {
            $userId = getUserIdFromAccessToken($accessToken);

            if ($userId) {
                // Calculate subtotal for logged-in user
                $cartSubtotal = 0;
                 $query = "
                    SELECT c.Quantity, c.Customization, p.ProductId, p.NewPrice
                    FROM carts c
                    JOIN products p ON c.ProductId = p.ProductId
                    WHERE c.UserId = ?
                ";
                $stmt = $shopLink->prepare($query);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                     $productPrice = $row['NewPrice'];
                     $quantity = $row['Quantity'];
                     $customization = $row['Customization'];

                     // Check for customization price
                    if (!empty($customization) && $customization !== 'null') {
                        $customizationQuery = "
                            SELECT CustomizationOption
                            FROM product_customizations
                            WHERE ProductId = ?
                        ";
                        $customizationStmt = $shopLink->prepare($customizationQuery);
                        $customizationStmt->bind_param('i', $row['ProductId']);
                        $customizationStmt->execute();
                        $customizationResult = $customizationStmt->get_result();

                        if ($customizationResult->num_rows > 0) {
                            $customizationData = $customizationResult->fetch_assoc();
                            $customizationOptions = json_decode($customizationData['CustomizationOption'], true);

                            if (isset($customizationOptions[$customization])) {
                                $productPrice = (float) $customizationOptions[$customization];
                            }
                        }
                        $customizationStmt->close();
                    }
                    $cartSubtotal += $productPrice * $quantity;
                }
                $stmt->close();


                // Calculate shipping charge
                $shippingCharge = 50;
                $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : (isset($_GET['pincode']) ? trim($_GET['pincode']) : null);

                if ($pincode) {
                    $shippingCharge = calculateShippingChargesByPincode($pincode); // Assuming this function exists
                } else {
                    // Try to get pincode from user's default address if available
                    $addresses = fetchUserAddresses(); // Assuming this function exists and takes userId
                    if (!empty($addresses)) {
                        $shippingCharge = calculateShippingChargesByPincode($addresses[0]['PostalCode']);
                    }
                }

                $response = [
                    'success' => true,
                    'subtotal' => $cartSubtotal,
                    'shipping' => $shippingCharge,
                    'total' => $cartSubtotal + $shippingCharge
                ];

            } else {
                 $response['message'] = $decodedUserData['message'] ?? 'Unable to decode user ID.';
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    if ($_GET['action'] == "displayGuestCheckout") {
        if (isset($_POST['guestCart'])) {
            $guestCart = $_POST['guestCart'];
            // Call the displayCartList function with the guest cart data
            displayCheckoutTable(true, $guestCart);
        } else {
            echo '<tr><td colspan="6">Error: Guest cart data not provided.</td></tr>';
        }
    }

    if ($_GET['action'] === 'login') {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents("php://input"), true);

        $email = trim($data['email'] ?? '');
        $pass  = $data['pass'] ?? '';

        if (!$email || !$pass) {
            echo json_encode(['message' => 'Email and password are required']);
            exit;
        }

        if (!validateEmail($email)) {
            echo json_encode(['message' => 'Please enter a valid email address']);
            exit;
        }

        $user = findUserByEmail($email);
        if (!$user) {
            echo json_encode(['message' => 'Invalid email or password']);
            exit;
        }

        // ⚠️ Replace with password_verify once hashed
        if ($pass !== $user['UserPassword']) {
            echo json_encode(['message' => 'Incorrect password']);
            exit;
        }

        $payload = buildTokenPayload($user);
        $accessToken  = generateAccessToken($payload);
        $refreshToken = generateRefreshToken($user['UserId']);

        echo json_encode([
            'message' => 1,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'userId' => $user['UserId'],
            'fullName' => $user['Name'],
            'phone' => $user['UserPhone'],
            'email' => $user['UserEmail'],
            'role' => $user['Role'],
        ]);
        exit;
    }

    if ($_GET['action'] === 'signup') {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents("php://input"), true);

        $name       = trim($data['name'] ?? '');
        $email      = trim($data['email'] ?? '');
        $phone      = trim($data['phone'] ?? '');
        $password   = $data['password'] ?? '';
        $confirmPwd = $data['signupPassCnf'] ?? '';
        $source     = $data['source'] ?? null;

        if (!$name || !$email || !$password || !$confirmPwd) {
            echo json_encode(['message' => 'All fields are required']);
            exit;
        }

        if (!validateEmail($email)) {
            echo json_encode(['message' => 'Please enter a valid email address']);
            exit;
        }

        if ($password !== $confirmPwd) {
            echo json_encode(['message' => 'Passwords do not match']);
            exit;
        }

        $exists = checkUserExists($email, $phone);
        if ($exists['exists']) {
            echo json_encode(['message' => "That {$exists['reason']} is already taken"]);
            exit;
        }

        createUser([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password, // hash later
            'sourceReferral' => $source
        ]);

        echo json_encode(['message' => 1]);
        exit;
    }

    if ($_GET['action'] === 'forgotPassword') {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents("php://input"), true);
        $email = trim($data['email'] ?? '');

        if (!$email || !validateEmail($email)) {
            echo json_encode(['message' => 'A valid email is required.']);
            exit;
        }

        $user = findUserByEmail($email);
        if (!$user) {
            echo json_encode(['message' => 'No user found with that email address.']);
            exit;
        }

        $token = generateResetToken();
        $saved = saveResetToken($email, $token);

        if (!$saved) {
            echo json_encode(['message' => 'There was an issue! Please try again.']);
            exit;
        }

        try {
            sendPasswordResetEmail($email, $token);
        } catch (Exception $e) {
            echo json_encode(['message' => 'Failed to send password reset email.']);
            exit;
        }

        echo json_encode(['message' => 'Reset link has been sent to your email']);
        exit;
    }

    if ($_GET['action'] === "paymentRequest") {

    header('Content-Type: application/json');

    $request_body = file_get_contents('php://input');
    $input = json_decode($request_body, true);

    // Generate unique link_id
    $link_id = 'link_' . time() . '_' . uniqid();

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname($_SERVER['PHP_SELF']);

    $returnUrl = $protocol . $host . $basePath .
        "/actions.php?action=paymentSuccessful&linkId=" . $link_id;

    $payload = [
        "customer_details" => [
            "customer_phone" => $input['customerPhone'],
            "customer_name" => $input['customerName']
        ],
        "link_notify" => [
            "send_sms" => true
        ],
        "link_meta" => [
            "return_url" => $returnUrl,
            "upi_intent" => true,
            "payment_methods" => "dc,nb,upi"
        ],
        "link_id" => $link_id,
        "link_amount" => $input['amount'],
        "link_currency" => "INR",
        "link_purpose" => "SaltyLameon Studios Payment"
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $_ENV['CASHFREE_API_URL'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . $_ENV['CASHFREE_CLIENT_ID'],
            'x-client-secret: ' . $_ENV['CASHFREE_CLIENT_SECRET'],
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode([
            "error" => curl_error($ch)
        ]);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);

    echo json_encode($responseData);
    exit;
}

    if ($_GET['action'] == "paymentSuccessful")       
    {
        $request_body = file_get_contents('php://input'); // For axios input
        $input = json_decode($request_body, true);

        $headers = getallheaders();
        global $secretKey;
        $custId = 0;

        // Check if the Authorization header is set
        if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
            try {
                $tokenResult = getUserIdFromToken($headers, $secretKey);
                $custId = $input['customerId'] ?? $tokenResult['userId'] ?? 0;
            } catch (Exception $e) {
                $userId = null;
            }
        }

        $custPhone = $input['customerPhone'];
        $amount = $input['amount'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $_ENV['CASHFREE_API_URL'] .'/'. $_GET['linkId']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: '. $_ENV['CASHFREE_CLIENT_ID'],
            'x-client-secret: '. $_ENV['CASHFREE_CLIENT_SECRET'],
        ]);

        $response = curl_exec($ch);
        $responseData = json_decode($response, true);

        // Check if payment is successful
        if ($responseData['link_status'] == "PAID") {
            //database storage of payment copy from biblo
            // Insert data into Payments table
            // if ($custPhone) {
            //     $checkQuery = "SELECT COUNT(*) FROM Payments WHERE LinkId = ?";
            //     $stmtCheck = mysqli_prepare($shopLink, $checkQuery);
            //     mysqli_stmt_bind_param($stmtCheck, 's', $_GET['linkId']);
            //     mysqli_stmt_execute($stmtCheck);
            //     mysqli_stmt_bind_result($stmtCheck, $count);
            //     mysqli_stmt_fetch($stmtCheck);
            //     mysqli_stmt_close($stmtCheck);
            
            //     if ($count == 0) {
            //         $insertQuery = "INSERT INTO Payments (LinkId, UserId, UserPhone, Amount) VALUES (?, ?, ?, ?)";
            //         $stmt = mysqli_prepare($shopLink, $insertQuery);
            //         mysqli_stmt_bind_param($stmt, 'sisd', $_GET['linkId'], $custId, $custPhone, $amount);
            //         mysqli_stmt_execute($stmt);
            //         mysqli_stmt_close($stmt);
            //     }
            // }

            // Update the payment status in database yet to do
            //if orderPayment update status in orders table
            //if subscriptionPayment update status in subscriptions table

            // Payment successful, send the status to frontend
            echo json_encode(["status" => "success. You can close this window."]);
        } else {
            // Payment not successful
            echo json_encode(["status" => "failed", "error" => "Payment not successful"]);
        }

        curl_close($ch);
    }

    // Function to extract and validate `user` from JWT
    function getUserIdFromToken($headers, $secretKey) {
        if (!isset($headers['Authorization'])) {
            return ['error' => 'Authorization header missing'];
        }

        $jwt = str_replace('Bearer ', '', $headers['Authorization']);

        try {
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
            return ['userId' => $decoded->userId];
        } catch (Exception $e) {
            return ['error' => 'Invalid token'];
        }
    }
?>