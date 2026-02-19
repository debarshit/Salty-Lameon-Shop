<?php

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: *");

    // Load environment variables
    require_once __DIR__ . '/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $shopServername = $_ENV['DATABASE_SERVER'];
    $shopUsername = $_ENV['DATABASE_USERNAME'];
    $shopPassword = $_ENV['DATABASE_PASSWORD'];
    $shopDatabase = $_ENV['SHOP_DATABASE'];
    
    // Create connection
    $shopLink = mysqli_connect($shopServername, $shopUsername, $shopPassword, $shopDatabase);
        
    if (mysqli_connect_errno()) {
        
        print_r(mysqli_connect_error());
        exit();
        
    }

    if (!mysqli_set_charset($shopLink, "utf8mb4")) {
        echo "Error loading character set utf8mb4: " . mysqli_error($shopLink);
    }

    function generateMetaTags($pageData) {
        $metaTitle = isset($pageData['meta_title']) ? $pageData['meta_title'] : 'Pages & Palette | Biblophile Merchandise Store';
        $metaDescription = isset($pageData['meta_description']) ? $pageData['meta_description'] : 'Welcome to Pages & Palette – your shop for bookish merchandise like bookmarks, stickers, and more!';
        $metaKeywords = isset($pageData['meta_keywords']) ? $pageData['meta_keywords'] : 'bookish merchandise, bookmarks, stickers, books, Pages & Palette';
        $metaImage = isset($pageData['meta_image']) ? $pageData['meta_image'] : 'assets/img/logo.svg';
        $metaUrl = isset($pageData['meta_url']) ? $pageData['meta_url'] : 'https://shop.biblophile.com';
    
        // Open Graph meta tags
        $ogTitle = $metaTitle;
        $ogDescription = $metaDescription;
        $ogImage = $metaImage;
        $ogUrl = $metaUrl;
    
        // Twitter meta tags
        $twitterTitle = $metaTitle;
        $twitterDescription = $metaDescription;
        $twitterImage = $metaImage;
        $twitterUrl = $metaUrl;
    
        // Output the meta tags
        echo '
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="' . htmlspecialchars($metaDescription) . '" />
        <meta name="keywords" content="' . htmlspecialchars($metaKeywords) . '" />
        <meta name="author" content="Pages & Palette" />
        <meta name="robots" content="index, follow" />
    
        <title>' . htmlspecialchars($metaTitle) . '</title>
    
        <!-- Open Graph meta tags -->
        <meta property="og:title" content="' . htmlspecialchars($ogTitle) . '" />
        <meta property="og:description" content="' . htmlspecialchars($ogDescription) . '" />
        <meta property="og:image" content="' . $ogImage . '" />
        <meta property="og:url" content="' . $ogUrl . '" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="Pages & Palette" />
    
        <!-- Twitter Card meta tags -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="' . htmlspecialchars($twitterTitle) . '" />
        <meta name="twitter:description" content="' . htmlspecialchars($twitterDescription) . '" />
        <meta name="twitter:image" content="' . $twitterImage . '" />
        <meta name="twitter:url" content="' . $twitterUrl . '" />
    
        <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
        ';
    }
    
    function timeSince($since) {       //for showing itna time ago
        $chunks = array(
            array(60 * 60 * 24 * 365 , 'year'),
            array(60 * 60 * 24 * 30 , 'month'),
            array(60 * 60 * 24 * 7, 'week'),
            array(60 * 60 * 24 , 'day'),
            array(60 * 60 , 'hour'),
            array(60 , 'min'),
            array(1 , 'sec')
        );
    
        for ($i = 0, $j = count($chunks); $i < $j; $i++) {
            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];
            if (($count = floor($since / $seconds)) != 0) {
                break;
            }
        }
    
        $print = ($count == 1) ? '1 '.$name : "$count {$name}s";
        return $print;
    }

    // Function to fetch categories from the database
    function displayCategories() {
        global $shopLink;

        $query = "SELECT CategoryImage, CategoryName, CategoryId FROM categories WHERE IsAvailable = 1 LIMIT 8";
        $result = mysqli_query($shopLink, $query);
        
        if (!$result) {
            echo "Error: " . mysqli_error($shopLink);
            return;
        }

        $categories_html = '';

        while ($row = mysqli_fetch_assoc($result)) {
            $image = htmlspecialchars($row['CategoryImage']);
            $name = htmlspecialchars($row['CategoryName']);
            $categoryId = htmlspecialchars($row['CategoryId']);
            $categoryUrl = "shop/{$categoryId}/" . urlencode($name);
            $categories_html .= '
                <a href="' . $categoryUrl . '" class="category__item swiper-slide">
                    <img src="' . $image . '" alt="" class="category__img">
                    <h3 class="category__title">' . $name . '</h3>
                </a>';
        }

        echo $categories_html;
    }

    //function to fetch images from imagekit folder
    function fetchImagesFromImageKit($folderPath) {
        $apiUrl = 'https://api.imagekit.io/v1/files';
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?path=' . urlencode($folderPath));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $privateKey = 'private_M8laZw59kBD1UuCzwb2WsMYI8Zo=';
        $auth = base64_encode($privateKey . ':');
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Accept: application/json'
        ]);
    
        $response = curl_exec($ch);
    
        if ($response === false) {
            echo 'Curl error: ' . curl_error($ch);
            curl_close($ch);
            return [];
        }
    
        curl_close($ch);
    
        $responseData = json_decode($response, true);
    
        if (is_array($responseData) && !empty($responseData)) {
            return array_map(fn($image) => $image['url'], $responseData);
        }
    
        return [];
    }

    //function to display images in details screen
    function displayDetailImages($folderPath) {
        $imageUrls = fetchImagesFromImageKit($folderPath);
    
        if (empty($imageUrls)) {
            echo 'No images available.';
            return;
        }
    
        $firstImage = array_shift($imageUrls);
    
        echo '<img 
                  src="' . htmlspecialchars($firstImage) . '&tr=w-400' . '" 
                  alt="" 
                  class="details__img"
              />';

        if (count($imageUrls) > 0) {
    
            echo '<div class="details__small-images grid">';
    
            array_unshift($imageUrls, $firstImage);
    
            foreach ($imageUrls as $url) {
                echo '<img 
                        src="' . htmlspecialchars($url) . '" 
                        alt="" 
                        class="details__small-img"
                    />';
            }
    
            echo '</div>';
        }
    }

// Function to fetch product details from the database
function fetchProductDetails($productId) {
    global $shopLink;

    $stmt = $shopLink->prepare("
        SELECT p.ProductId, p.ProductName, p.ProductDescription, p.NewPrice, p.OldPrice, p.SKU, p.StockQuantity, p.ProductImage, 
               c.CategoryName, c.CategoryId,
               GROUP_CONCAT(t.TagName SEPARATOR ', ') AS Tags,
               (SELECT AVG(Rating) FROM productreviews WHERE ProductId = p.ProductId) AS AvgRating,
               (SELECT pl.Name FROM products_promotionallabels ppl
                JOIN promotionallabels pl ON ppl.PromotionalLabelId = pl.Id
                WHERE ppl.ProductId = p.ProductId LIMIT 1) AS PromotionalLabel,
               (SELECT dl.Name FROM products_discountlabels pdl
                JOIN discountlabels dl ON pdl.DiscountLabelId = dl.Id
                WHERE pdl.ProductId = p.ProductId LIMIT 1) AS DiscountLabel
        FROM products p
        JOIN categories c ON p.CategoryID = c.CategoryId
        LEFT JOIN producttags pt ON p.ProductId = pt.ProductId
        LEFT JOIN tags t ON pt.TagId = t.TagId
        WHERE p.ProductId = ?
        GROUP BY p.ProductId
    ");

    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $product = $result->fetch_assoc();

    // Format average rating into star count
    $product['AvgRating'] = round($product['AvgRating']); // Round to nearest integer

    return $product;
}

// Function to fetch additional product info
function fetchProductAdditionalInfo($productId) {
    global $shopLink;

    $stmt = $shopLink->prepare("SELECT AdditionalInfo FROM product_additionalinfo WHERE ProductId = ?");
    
    $stmt->bind_param("i", $productId);
    
    $stmt->execute();
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $row = $result->fetch_assoc();

    $additionalInfo = json_decode($row['AdditionalInfo'], true);

    return $additionalInfo;
}

// Function to fetch product reviews from the database
function displayReviews($productId) {
    global $shopLink;

    $query = "SELECT Rating, Review, CreatedAt FROM productreviews WHERE ProductId = $productId LIMIT 3";
    $result = mysqli_query($shopLink, $query);
    
    if (!$result) {
        echo "Error: " . mysqli_error($shopLink);
        return;
    }

    $reviews_html = '';

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rating = htmlspecialchars($row['Rating']);
            $review = htmlspecialchars($row['Review']);
            $createdAt = htmlspecialchars($row['CreatedAt']);
            $reviews_html .= '
                <div class="review__single">
                    <div>
                    <img 
                        src="https://thumbs.dreamstime.com/b/default-avatar-profile-icon-vector-social-media-user-image-182145777.jpg"
                        alt="" 
                        class="review__img">
                        <h4 class="review__title">User</h4>
                    </div>

                    <div class="review__data">
                    <div class="review__rating">
                        <i class="fi fi-rs-star">'. $rating .'</i>
                    </div>

                    <p class="review__description">
                        '. $review .'
                    </p>

                    <span class="review__date">'. $createdAt .'</span>
                    </div>
                </div>';
            }
    } else {
        $reviews_html = '<p>No reviews yet for this product.</p>';
    }
    

    echo $reviews_html;
}

// Function to fetch product customization info
function fetchCustomizationOptions($productId) {
    global $shopLink;
    
    $stmt = $shopLink->prepare("SELECT CustomizationOption FROM product_customizations WHERE ProductId = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $customization = $result->fetch_assoc();
    
    return json_decode($customization['CustomizationOption'], true);
}

// Function to fetch all products
//implement infinite scrolling
//add advance filters later to sort from tags, special categories and stuffs
function fetchProductIds($limit = 8, $offset = 0, $categoryId = null) {
    global $shopLink;

    $query = "SELECT ProductId FROM products WHERE (StockQuantity > 0 OR StockQuantity IS NULL) AND IsAvailable = 1";
    $params = [];
    $types = '';

    if ($categoryId) {
        $query .= " AND CategoryId = ?";
        $params[] = $categoryId;
        $types .= 'i';
    }

    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $shopLink->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $productIds = [];
    while ($row = $result->fetch_assoc()) {
        $productIds[] = $row['ProductId'];
    }

    return $productIds;
}

function generateProductHTML($product, $productId) {
    $images = fetchImagesFromImageKit($product['ProductImage']);
    $imagesToDisplay = array_slice($images, 0, 2);

    ob_start(); // Start output buffering
    ?>
    <div class="product__item">
        <div class="product__banner">
            <a href="details/<?= $productId ?>/<?= str_replace('+', '%20', urlencode(str_replace(' ', '-', $product['ProductName']))) ?>" class="product__images">
                <?php foreach ($imagesToDisplay as $index => $image): ?>
                    <img
                        srcset="
                            <?= htmlspecialchars($image) . '&tr=h-250,w-250'; ?> 500w,
                            <?= htmlspecialchars($image) . '&tr=h-500,w-500'; ?> 1000w"
                        sizes="(max-width: 768px) 250px, 500px"
                        alt=""
                        class="lazyload product__img <?= $index === 0 ? 'default' : 'hover'; ?>">
                <?php endforeach; ?>
            </a>
            <?php if (!empty($product['PromotionalLabel'])): ?>
                <div class="product__badge light-pink"><?= htmlspecialchars($product['PromotionalLabel']); ?></div>
            <?php endif; ?>
            <?php if ($product['StockQuantity'] == '0'): ?>
                <div class="product__badge light-pink">Sold out</div>
            <?php endif; ?>
            <?php if (!empty($product['DiscountLabel'])): ?>
                <div class="product__badge light-green"><?= htmlspecialchars($product['DiscountLabel']); ?></div>
            <?php endif; ?>
        </div>
        <div class="product__content">
            <span class="product__category"><?= htmlspecialchars($product['CategoryName']); ?></span>
            <a href="?page=details&product_id=<?= $productId ?>">
                <h3 class="product__title"><?= htmlspecialchars($product['ProductName']); ?></h3>
            </a>
            <div class="product__rating">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <i class="fi fi-rs-star<?= $i < $product['AvgRating'] ? '' : '-o'; ?>"></i>
                <?php endfor; ?>
            </div>
            <div class="product__price flex">
                <span class="new__price">₹<?= number_format($product['NewPrice'], 2); ?></span>
                <?php if ($product['OldPrice'] !== null): ?>
                    <span class="old__price">₹<?= number_format($product['OldPrice'], 2); ?></span>
                <?php endif; ?>
            </div>
            <button class="action__btn cart__btn" aria-label="Add To Cart" id="addToCartBtn_<?= $productId ?>" data-product-id="<?= $productId ?>" data-product-name="<?= htmlspecialchars($product['ProductName']); ?>" data-category="<?= htmlspecialchars($product['CategoryName']); ?>">
                <i class="fi fi-rs-shopping-bag-add"></i>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean(); // Return the buffered content
}

// Function to fetch products under a special category
function fetchProductIdsBySpecialCategory($specialCategoryId) {
    global $shopLink;
    $stmt = $shopLink->prepare("SELECT ProductId FROM product_specialcategories WHERE SpecialCategoryId = ? LIMIT 8");
    $stmt->bind_param("i", $specialCategoryId);
    $stmt->execute();
    $result = $stmt->get_result();

    $productIds = [];
    while ($row = $result->fetch_assoc()) {
        $productIds[] = $row['ProductId'];
    }

    return $productIds;
}

// Function to fetch user addresses from the database
function fetchUserAddresses() {
    global $shopLink;
    $accessToken = getAccessTokenFromSession();

    // Step 1: Retrieve the cookie value
    if (isset($accessToken)) {

    // Step 2: Decode the accessToken using the external API
    $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
    
    $response = file_get_contents($url);
    $decodedResponse = json_decode($response, true);

    if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
        // Step 3: Extract userId from the decoded response
        $userId = $decodedResponse['userId'];

        $stmt = $shopLink->prepare("
            SELECT AddressId, ReceiverName, AddressLine1, AddressLine2, City, State, PostalCode, Country
            FROM useraddresses
            WHERE UserId = ?
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return [];
        }
    } else {
        return ['error' => 'Unable to decode userId: ' . $decodedResponse['message']];
    }
    } else {
        return ['error' => 'User session cookie is not set'];
    }
}

// Function to calculate shipping charges based on the PIN code
function calculateShippingChargesByPincode($pincode) {
    if (!is_numeric($pincode) || strlen($pincode) !== 6) {
        return null;
    }

    if ($pincode >= 560001 && $pincode <= 560999) {
        return 50;
    }

    $nearbyStates = [
        'Andhra Pradesh' => [500001, 599999],
        'Tamil Nadu' => [600001, 600999],
        'Telangana' => [500001, 599999],
        'Kerala' => [680001, 699999]
    ];

    foreach ($nearbyStates as $state => $range) {
        $start = $range[0];
        $end = $range[1];
        
        if ($pincode >= $start && $pincode <= $end) {
            return 75;
        }
    }

    return 100;
}

// Function to fetch access token from the session cookie
function getAccessTokenFromSession() {
    if (isset($_COOKIE['user_session'])) {
        $cookieData = base64_decode($_COOKIE['user_session']);
        
        $sessionData = json_decode($cookieData, true);

        if (isset($sessionData['accessToken'])) {
            return $sessionData['accessToken'];
        } else {
            return null;
        }
    } else {
        return null;
    }
}

// Function to fetch user details from the database
function fetchUserDetails() {
    $url = $_ENV['BIBLOPHILE_API_URL'].'users/me';

    $accessToken = getAccessTokenFromSession();

    $ch = curl_init($url);

    // Set the cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    curl_close($ch);

    echo $response;

    if ($httpCode == 200) {
        $responseData = json_decode($response, true);

        if (isset($responseData['data'])) {
             return [
                'name' => $responseData['data']['name'] ?? null,
                'userName' => $responseData['data']['userName'] ?? null,
                'email' => $responseData['data']['email'] ?? null,
                'phone' => $responseData['data']['phone'] ?? null,
                'address' => $responseData['data']['address'] ?? null,
                'sourceReferral' => $responseData['data']['sourceReferral'] ?? null,
            ];
        } else {
            return [
                'error' => 'Error fetching user details: ' . $responseData['message']
            ];
        }
    } else {
        return [
            'error' => 'Failed to connect to the API. HTTP status code: ' . $httpCode
        ];
    }
}

// Function to fetch user details from the database
function updateUserData($property, $value) {
    $url = $_ENV['BIBLOPHILE_API_URL'].'users/update';

    $accessToken = getAccessTokenFromSession();

    $postData = json_encode([
        'property' => $property,
        'value' => $value
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $responseData = json_decode($response, true);

        if (isset($responseData['message']) && $responseData['message'] == "Updated") {
            return [
                'success' => true,
                'message' => 'Profile updated successfully!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error: ' . $responseData['message']
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Failed to connect to the API. HTTP status code: ' . $httpCode
        ];
    }
}

function displayCartNumber() {
    global $shopLink;

    $accessToken = getAccessTokenFromSession();

    if (isset($accessToken)) {

        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];

            $stmt = $shopLink->prepare("
                SELECT COUNT(*) AS totalItems 
                FROM carts 
                WHERE UserId = ? 
            ");

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $cartCount = $row['totalItems'];

                // If there are more than 3 items, display "3+"
                if ($cartCount > 3) {
                    return "3+";
                } elseif ($cartCount > 0) {
                    return $cartCount;
                } else {
                    return ""; // Return empty string if there are no items in the cart
                }
            } else {
                return ""; // Return empty string if no items in the cart
            }
        } else {
            return 'Error: ' . $decodedResponse['message'];
        }
    } else {
        return 'Error: User session cookie is not set';
    }
}

function displayCartList($displayTable = true, $guestCartJson = null) {
    global $shopLink;

    $accessToken = getAccessTokenFromSession();
    $totalPrice = 0;

    if (isset($accessToken)) {
        // User is logged in - existing logic here
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];

            $query = "
                SELECT c.Quantity, c.Customization, p.ProductId, p.ProductName, p.NewPrice, p.ProductImage 
                FROM carts c 
                JOIN products p ON c.ProductId = p.ProductId 
                WHERE c.UserId = ?
            ";

            $stmt = $shopLink->prepare($query);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $productId = $row['ProductId'];
                    $productName = $row['ProductName'];
                    $productPrice = $row['NewPrice'];
                    $productImage = $row['ProductImage'];
                    $quantity = $row['Quantity'];
                    $customization = $row['Customization'];
                    $subtotal = $productPrice * $quantity;

                    // Check for customization price if any customization is selected
                    if (!empty($customization)) {
                        // Fetch customization price from the product_customizations table
                        $customizationQuery = "
                            SELECT CustomizationOption
                            FROM product_customizations
                            WHERE ProductId = ?
                        ";

                        $customizationStmt = $shopLink->prepare($customizationQuery);
                        $customizationStmt->bind_param('i', $productId);
                        $customizationStmt->execute();
                        $customizationResult = $customizationStmt->get_result();

                        if ($customizationResult->num_rows > 0) {
                            // Customization option found, fetch the options
                            $customizationData = $customizationResult->fetch_assoc();
                            $customizationOptions = json_decode($customizationData['CustomizationOption'], true);
                        
                            // Check if the selected customization exists in the options
                            if (isset($customizationOptions[$customization])) {
                                // Override price with customization price
                                $customizationPrice = (float) $customizationOptions[$customization];
                                $productPrice = $customizationPrice;
                            }
                        }
                        $customizationStmt->close();
                    }

                    $subtotal = $productPrice * $quantity;
                    $totalPrice += $subtotal;

                    // If displaying the table, render the HTML
                    if ($displayTable) {
                        $images = fetchImagesFromImageKit($productImage);
                        $imagesToDisplay = array_slice($images, 0, 2);

                        echo '<tr class="table__row">';
                        echo '<td><img src="' . htmlspecialchars($imagesToDisplay[0]) . '&tr=h-250,w-250' . '" alt="' . htmlspecialchars($productName) . '" class="table__img" /></td>';
                        echo '<td>';
                        echo '<a href="details/' .$productId. '/' .str_replace('+', '%20', urlencode(str_replace(' ', '-', $productName))). '"><h3 class="table__title">' . htmlspecialchars($productName) . '</h3></a>';
                        if ($customization !== 'null') {
                            echo '<p class="table__description">Customization: ' . $customization . '</p>';
                        }
                        echo '</td>';
                        echo '<td><span class="table__price">₹' . number_format($productPrice, 2) . '</span></td>';
                        echo '<td><input type="number" value="' . $quantity . '" class="quantity" data-product-id="' . $productId . '" /></td>';
                        echo '<td><span class="table__subtotal">₹' . number_format($subtotal, 2) . '</span></td>';
                        echo '<td><i class="fi fi-rs-trash table__trash" data-product-id="' . $productId . '"></i></td>';
                        echo '</tr>';
                    }
                }
            } else {
                if ($displayTable) {
                    echo '<tr><td colspan="6">Your cart is empty.</td></tr>';
                }
            }

            $stmt->close();
        } else {
            if ($displayTable) {
                echo '<tr><td colspan="6">Error: Unable to retrieve user information. Please try again later.</td></tr>';
            }
        }
    } else {
        // User is not logged in - handle guest cart
        if ($guestCartJson) {
            $guestCart = json_decode($guestCartJson, true);
            if ($guestCart && is_array($guestCart)) {
                foreach ($guestCart as $item) {
                    $productId = $item['productId'];
                    $quantity = $item['quantity'];
                    $customization = isset($item['customization']) && $item['customization'] !== 'null' ? $item['customization'] : null;

                    // Fetch product details from the database
                    $query = "SELECT ProductId, ProductName, NewPrice, ProductImage FROM products WHERE ProductId = ?";
                    $stmt = $shopLink->prepare($query);
                    $stmt->bind_param('i', $productId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $productName = $row['ProductName'];
                        $productPrice = $row['NewPrice'];
                        $productImage = $row['ProductImage'];
                        $subtotal = $productPrice * $quantity;

                        // Check for customization price
                        if (!empty($customization)) {
                            $customizationQuery = "
                                SELECT CustomizationOption
                                FROM product_customizations
                                WHERE ProductId = ?
                            ";
                            $customizationStmt = $shopLink->prepare($customizationQuery);
                            $customizationStmt->bind_param('i', $productId);
                            $customizationStmt->execute();
                            $customizationResult = $customizationStmt->get_result();

                            if ($customizationResult->num_rows > 0) {
                                $customizationData = $customizationResult->fetch_assoc();
                                $customizationOptions = json_decode($customizationData['CustomizationOption'], true);

                                if (isset($customizationOptions[$customization])) {
                                    $customizationPrice = (float) $customizationOptions[$customization];
                                    $productPrice = $customizationPrice;
                                }
                            }
                            $customizationStmt->close();
                        }

                        $subtotal = $productPrice * $quantity;
                        $totalPrice += $subtotal;

                        if ($displayTable) {
                            $images = fetchImagesFromImageKit($productImage);
                            $imagesToDisplay = array_slice($images, 0, 2);

                            echo '<tr class="table__row guest-cart-item" data-product-id="' . $productId . '" data-customization="' . htmlspecialchars($customization) . '">';
                            echo '<td><img src="' . htmlspecialchars($imagesToDisplay[0]) . '&tr=h-250,w-250' . '" alt="' . htmlspecialchars($productName) . '" class="table__img" /></td>';
                            echo '<td>';
                            echo '<a href="details/' .$productId. '/' .str_replace('+', '%20', urlencode(str_replace(' ', '-', $productName))). '"><h3 class="table__title">' . htmlspecialchars($productName) . '</h3></a>';
                            if ($customization) {
                                echo '<p class="table__description">Customization: ' . htmlspecialchars(urldecode($customization)) . '</p>';
                            }
                            echo '</td>';
                            echo '<td><span class="table__price">₹' . number_format($productPrice, 2) . '</span></td>';
                            echo '<td><input type="number" value="' . $quantity . '" class="quantity guest-quantity" data-product-id="' . $productId . '" data-customization="' . htmlspecialchars($customization) . '" /></td>';
                            echo '<td><span class="table__subtotal">₹' . number_format($subtotal, 2) . '</span></td>';
                            echo '<td><i class="fi fi-rs-trash table__trash guest-remove-item" data-product-id="' . $productId . '" data-customization="' . htmlspecialchars($customization) . '"></i></td>';
                            echo '</tr>';
                        }
                    }
                }
            } else {
                if ($displayTable) {
                    echo '<tr><td colspan="6">Your guest cart is empty.</td></tr>';
                }
            }
        } else {
            if ($displayTable) {
                echo '<tr><td colspan="6">Your cart is empty.</td></tr>';
            }
        }
    }

    return $totalPrice;
}

function displayCheckoutTable($displayTable = true, $guestCartJson = null) {
    global $shopLink;

    $accessToken = getAccessTokenFromSession();
    $totalPrice = 0;
    $cartItems = [];
    $customizationOptions = [];

    if (isset($accessToken)) {
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];

            $query = "
                SELECT c.Quantity, c.Customization, p.ProductId, p.ProductName, p.NewPrice, p.ProductImage 
                FROM carts c 
                JOIN products p ON c.ProductId = p.ProductId 
                WHERE c.UserId = ?
            ";

            $stmt = $shopLink->prepare($query);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $productId = $row['ProductId'];
                    $productName = $row['ProductName'];
                    $productPrice = $row['NewPrice'];
                    $productImage = $row['ProductImage'];
                    $quantity = $row['Quantity'];
                    $customization = $row['Customization'];
                    $subtotal = $productPrice * $quantity;

                    if (!empty($customization)) {
                        // Fetch customization price from the product_customizations table
                        $customizationQuery = "
                            SELECT CustomizationOption
                            FROM product_customizations
                            WHERE ProductId = ?
                        ";

                        $customizationStmt = $shopLink->prepare($customizationQuery);
                        $customizationStmt->bind_param('i', $productId);
                        $customizationStmt->execute();
                        $customizationResult = $customizationStmt->get_result();

                        if ($customizationResult->num_rows > 0) {
                            // Customization option found, fetch the options
                            $customizationData = $customizationResult->fetch_assoc();
                            $customizationOptions = json_decode($customizationData['CustomizationOption'], true);
                        
                            // Check if the selected customization exists in the options
                            if (isset($customizationOptions[$customization])) {
                                // Override price with customization price
                                $customizationPrice = (float) $customizationOptions[$customization];
                                $productPrice = $customizationPrice;
                            }
                        }
                        $customizationStmt->close();
                    }

                    // Add to cart items array
                    $cartItems[] = [
                        'productId' => $productId,
                        'quantity' => $quantity,
                        'price' => $productPrice,
                        'subtotal' => $subtotal,
                        'productName' => $productName,
                        'productImage' => $productImage,
                        'customization' => $customization,
                    ];

                    $subtotal = $productPrice * $quantity;
                    $totalPrice += $subtotal;

                    if ($displayTable) {
                        $images = fetchImagesFromImageKit($productImage);
                        $imagesToDisplay = array_slice($images, 0, 2);

                        echo '<tr>';
                        echo '<td><img src="' . htmlspecialchars($imagesToDisplay[0]) . '&tr=h-100,w-100" alt="' . htmlspecialchars($productName) . '" class="order__img" /></td>';
                        echo '<td>';
                        echo '<a href="details/' .$productId. '/' .str_replace('+', '%20', urlencode(str_replace(' ', '-', $productName))). '"><p class="table__title">' . htmlspecialchars($productName) . '</p></a>';
                        echo '<p class="table__quantity">x ' . $quantity . '</p>';
                        if ($customization !== 'null') {
                            echo '<p class="table__description">Customization: ' . $customization . '</p>';
                        }
                        echo '</td>';
                        echo '<td><span class="table__price">₹' . number_format($subtotal, 2) . '</span></td>';
                        echo '</tr>';
                    }
                }
            } else {
                if ($displayTable) {
                    echo '<tr><td colspan="3">Your cart is empty.</td></tr>';
                }
            }

            $stmt->close();
        } else {
            if ($displayTable) {
                echo '<tr><td colspan="3">Error: Unable to retrieve user information. Please try again later.</td></tr>';
            }
        }
        return [$totalPrice, $cartItems];
    } else {
        // User is not logged in - handle guest cart
        if ($guestCartJson) {
            $guestCart = json_decode($guestCartJson, true);
            if ($guestCart && is_array($guestCart)) {
                foreach ($guestCart as $item) {
                    $productId = (int) ($item['productId'] ?? 0);
                    $quantity = (int) ($item['quantity'] ?? 1);
                    $customization = $item['customization'] ?? null;
                    $customization = ($customization === 'null' || $customization === '') ? null : $customization;
    
                    if (!$productId) continue;
    
                    // Fetch product details from the database
                    $query = "SELECT ProductId, ProductName, NewPrice, ProductImage FROM products WHERE ProductId = ?";
                    $stmt = $shopLink->prepare($query);
                    $stmt->bind_param('i', $productId);
                    $stmt->execute();
                    $result = $stmt->get_result();
    
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $productName = $row['ProductName'];
                        $productPrice = (float) $row['NewPrice'];
                        $productImage = $row['ProductImage'];
    
                        // Check for customization price
                        if (!empty($customization)) {
                            $customizationPrice = getCustomizationPrice($shopLink, $productId, $customization);
                            if ($customizationPrice !== null) {
                                $productPrice = $customizationPrice;
                            }
                        }
    
                        $subtotal = $productPrice * $quantity;
                        $totalPrice += $subtotal;
    
                        $cartItems[] = [
                            'productId' => $productId,
                            'quantity' => $quantity,
                            'price' => $productPrice,
                            'subtotal' => $subtotal,
                            'productName' => $productName,
                            'productImage' => $productImage,
                            'customization' => $customization,
                        ];
    
                        if ($displayTable) {
                            $images = fetchImagesFromImageKit($productImage);
                            $imageSrc = !empty($images[0]) ? $images[0] . '&tr=h-100,w-100' : 'fallback.jpg';
    
                            echo '<tr>';
                            echo '<td><img src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($productName) . '" class="order__img" /></td>';
                            echo '<td>';
                            echo '<a href="details/' . $productId . '/' . str_replace('+', '%20', urlencode(str_replace(' ', '-', $productName))) . '"><p class="table__title">' . htmlspecialchars($productName) . '</p></a>';
                            echo '<p class="table__quantity">x ' . $quantity . '</p>';
                            if ($customization) {
                                echo '<p class="table__description">Customization: ' . htmlspecialchars($customization) . '</p>';
                            }
                            echo '</td>';
                            echo '<td><span class="table__price">₹' . number_format($subtotal, 2) . '</span></td>';
                            echo '</tr>';
                        }
                    }
    
                    $stmt->close();
                }
            } else {
                if ($displayTable) {
                    echo '<tr><td colspan="3">Your guest cart is empty.</td></tr>';
                }
            }
        } else {
            if ($displayTable) {
                echo '<tr><td colspan="3">Your cart is empty.</td></tr>';
            }
        }
    
        return [$totalPrice, $cartItems];
    }
}

function getCustomizationPrice($db, $productId, $customizationKey) {
    $query = "SELECT CustomizationOption FROM product_customizations WHERE ProductId = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $options = json_decode($row['CustomizationOption'], true);
        if (isset($options[$customizationKey])) {
            return (float) $options[$customizationKey];
        }
    }

    $stmt->close();
    return null;
}

function displayUserOrders() {
    global $shopLink;

    $accessToken = getAccessTokenFromSession();
    $userId = null;
    if (isset($accessToken)) {
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);
        if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];
        }
    }

    if ($userId) {
        $query = "SELECT o.OrderId, o.CreatedAt, o.OrderStatus, o.TotalAmount
                  FROM orders o
                  WHERE o.UserId = ?
                  ORDER BY o.CreatedAt DESC";

        $stmt = mysqli_prepare($shopLink, $query);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $orderId, $orderDate, $orderStatus, $totalAmount);

        $orders = [];
        while (mysqli_stmt_fetch($stmt)) {
            $orders[] = [
                'OrderId' => $orderId,
                'OrderDate' => $orderDate,
                'OrderStatus' => $orderStatus,
                'TotalAmount' => $totalAmount
            ];
        }

        // Close the statement
        mysqli_stmt_close($stmt);

        return $orders;
    } else {
        // Handle error: User not authenticated or token invalid
        return [];
    }
}

function fetchOrderDetails($orderId) {
    global $shopLink;

    // Initialize variables for storing total and items
    $totalAmount = 0;
    $orderItems = [];
    $orderStatus = '';
    $shippingAmount = 0;

    // Fetch order summary from the orders table
    $queryOrder = "
        SELECT OrderStatus, TotalAmount, ShippingAmount
        FROM orders
        WHERE OrderId = ?
    ";
    $stmtOrder = $shopLink->prepare($queryOrder);
    $stmtOrder->bind_param('i', $orderId);
    $stmtOrder->execute();
    $resultOrder = $stmtOrder->get_result();

    if ($resultOrder->num_rows > 0) {
        $rowOrder = $resultOrder->fetch_assoc();
        $orderStatus = $rowOrder['OrderStatus'];
        $totalAmount = $rowOrder['TotalAmount'];
        $shippingAmount = $rowOrder['ShippingAmount'];
    }

    // Fetch order items from the orderitems table
    $queryItems = "
        SELECT oi.Quantity, oi.Customization, oi.Price, p.ProductId, p.ProductName, p.ProductImage
        FROM orderitems oi
        JOIN products p ON oi.ProductId = p.ProductId
        WHERE oi.OrderId = ?
    ";
    $stmtItems = $shopLink->prepare($queryItems);
    $stmtItems->bind_param('i', $orderId);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();

    $subtotal = 0;

    if ($resultItems->num_rows > 0) {
        while ($row = $resultItems->fetch_assoc()) {
            $productId = $row['ProductId'];
            $productName = $row['ProductName'];
            $productImage = $row['ProductImage'];
            $quantity = $row['Quantity'];
            $customization = $row['Customization'];
            $price = $row['Price'];
            $itemSubtotal = $price * $quantity;
            $subtotal += $itemSubtotal;

            // Add to order items array
            $orderItems[] = [
                'productId' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $itemSubtotal,
                'productName' => $productName,
                'productImage' => $productImage,
                'customization' => $customization,
            ];
        }
    }

    // Display the order details
    echo '<table class="order__table">
            <tr>
                <th colspan="2">Products</th>
                <th>Total</th>
            </tr>';

    // Loop through the items and display them
    foreach ($orderItems as $item) {
        $images = fetchImagesFromImageKit($item['productImage']);
        $imagesToDisplay = array_slice($images, 0, 2);  // Display first image
        echo '<tr>';
        echo '<td><img src="' . htmlspecialchars($imagesToDisplay[0]) . '&tr=h-100,w-100" alt="' . htmlspecialchars($item['productName']) . '" class="order__img" /></td>';
        echo '<td>';
        echo '<a href="details/' .$productId. '/' .str_replace('+', '%20', urlencode(str_replace(' ', '-', $productName))). '"><h3 class="table__title">' . htmlspecialchars($item['productName']) . '</h3></a>';
        echo '<p class="table__quantity">x ' . $item['quantity'] . '</p>';
        if ($item['customization'] !== 'null') {
            echo '<p class="table__description">Customization: ' . $item['customization'] . '</p>';
        }
        echo '</td>';
        echo '<td><span class="table__price">₹' . number_format($item['subtotal'], 2) . '</span></td>';
        echo '</tr>';
    }

    // Calculate the final subtotal
    echo '<tr><td><span class="order__subtitle">SubTotal</span></td>';
    echo '<td colspan="2"><span class="table__price">₹' . number_format($subtotal, 2) . '</span></td></tr>';

    // Handle shipping charge based on subtotal
    echo '<tr><td><span class="order__subtitle">Shipping</span></td>';
    echo '<td colspan="2"><span class="table__price shipping__price">';
    if ($subtotal < 150) {
        echo '₹' . number_format($shippingAmount, 2);
    } else {
        echo 'Free Shipping';
    }
    echo '</span></td></tr>';

    // Display the total amount
    echo '<tr><td><span class="order__subtitle">Total</span></td>';
    echo '<td colspan="2"><span class="order__grand-total">₹' . number_format($totalAmount) . '</span></td></tr>';

    echo '</table>';

    return;
}
