<?php

    include("functions.php");

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
                    'domain' => $_ENV['APP_ENV'] === "production" ? ".biblophile.com" : null,
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

    if ($_GET['action'] == 'deleteSessionCookie') {
        // Step 1: Extract and parse the refreshToken from the cookie
        if (isset($_COOKIE['user_session'])) {
            $userSession = json_decode(base64_decode($_COOKIE['user_session']), true);
            if (isset($userSession['refreshToken'])) {
                $refreshToken = $userSession['refreshToken'];
                
                // Step 2: Make the API request to log out
                $url = $_ENV['BIBLOPHILE_API_URL'].'actions.php?action=logout';
                $data = json_encode(['refreshToken' => $refreshToken]);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                $response = curl_exec($ch);
                curl_close($ch);

                // Step 3: Handle the response
                $responseData = json_decode($response, true);

                if (isset($responseData['message']) && $responseData['message'] === 'Logged out successfully.') {
                    // Step 4: If logout is successful, delete the cookie
                    setcookie('user_session', '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'domain' => $_ENV['APP_ENV'] === "production" ? ".biblophile.com" : null,
                        // 'secure' => ($_SERVER['HTTPS'] ?? false) === 'on',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);

                    // Optionally, you can redirect the user after successful logout
                    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Logout failed.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No refresh token found in the session cookie.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No user session found.']);
        }
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
            $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
            $response = file_get_contents($url);
            $decodedResponse = json_decode($response, true);
    
            if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
                $userId = $decodedResponse['userId'];
            } else {
                $response = ['message' => 'Error: Unable to decode userId.'];
                echo json_encode($response);
                exit();
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
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);
    
        if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to decode userId. ' . ($decodedResponse['message'] ?? 'Unknown error.')]);
            exit;
        }
    
        $addressId = $_POST['addressId'] ?? null;
        $receiver = $_POST['receiver'];
        $addressLine1 = $_POST['addressLine1'];
        $addressLine2 = $_POST['addressLine2'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $postalCode = $_POST['postalCode'];
        $country = $_POST['country'];
    
        if ($addressId) {
            // Update address
            $stmt = $shopLink->prepare("UPDATE useraddresses SET ReceiverName = ?, AddressLine1 = ?, AddressLine2 = ?, City = ?, State = ?, PostalCode = ?, Country = ? WHERE AddressId = ?");
            $stmt->bind_param("sssssssi", $receiver, $addressLine1, $addressLine2, $city, $state, $postalCode, $country, $addressId);
        } else {
            // Add new address
            $stmt = $shopLink->prepare("INSERT INTO useraddresses (UserId, ReceiverName, AddressLine1, AddressLine2, City, State, PostalCode, Country) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $userId, $receiver, $addressLine1, $addressLine2, $city, $state, $postalCode, $country);
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
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);
    
        if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to decode userId. ' . ($decodedResponse['message'] ?? 'Unknown error.')]);
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
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);
    
        if ($decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];
    
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
        } else {
            echo json_encode(['success' => false]);
        }
    }

    if ($_GET['action'] == 'updateQuantity') {
        $productId = (int)$_POST['productId'];
        $quantity = (int)$_POST['quantity'];

        $accessToken = getAccessTokenFromSession();
        
        // Decode the user ID using the access token
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);

        if ($decodedResponse['success'] === true) {
            $productId = (int)$_POST['productId'];
            $quantity = (int)$_POST['quantity'];
    
            // Decode the user ID using the access token
            $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
            $response = file_get_contents($url);
            $decodedResponse = json_decode($response, true);

            if ($decodedResponse['success'] === true) {
                $userId = $decodedResponse['userId'];

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
            } else {
                echo json_encode(['success' => false]);
            }
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
            $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
            $response = file_get_contents($url);
            $decodedResponse = json_decode($response, true);
    
            if ($decodedResponse['success'] === true) {
                $userId = $decodedResponse['userId'];
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: Unable to decode userId.']);
                return;
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
        $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
        $response = file_get_contents($url);
        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            $userId = $decodedResponse['userId'];
        } else {
            $response = ['message' => 'Error: Unable to decode userId.'];
            echo json_encode($response);
            exit();
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
            $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
            $userResponse = file_get_contents($url);
            $decodedUserResponse = json_decode($userResponse, true);
    
            if (isset($decodedUserResponse['success']) && $decodedUserResponse['success'] === true) {
                $userId = $decodedUserResponse['userId'];
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

                     if ($cartSubtotal >= 150) {
                        $shippingCharge = 0;
                    } else if ($pincode) {
                        $shippingCharge = calculateShippingChargesByPincode($pincode); // Assuming this function exists
                    } else {
                         // Default shipping for guests without pincode? Or prompt for pincode?
                         // For now, let's assume 0 until pincode is provided.
                         $shippingCharge = 0;
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
            $url = 'https://shop.biblophile.com/decodeUserId.php?action=decodeUserId&Authorization=' . $accessToken;
            $userDataResponse = file_get_contents($url);
            $decodedUserData = json_decode($userDataResponse, true);

            if (isset($decodedUserData['success']) && $decodedUserData['success'] === true) {
                $userId = $decodedUserData['userId'];

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
                $shippingCharge = 0;
                $pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : (isset($_GET['pincode']) ? trim($_GET['pincode']) : null);

                if ($cartSubtotal >= 150) {
                    $shippingCharge = 0;
                } else {
                     if ($pincode) {
                         $shippingCharge = calculateShippingChargesByPincode($pincode); // Assuming this function exists
                     } else {
                         // Try to get pincode from user's default address if available
                         $addresses = fetchUserAddresses($userId); // Assuming this function exists and takes userId
                         if (!empty($addresses)) {
                             $shippingCharge = calculateShippingChargesByPincode($addresses[0]['PostalCode']);
                         }
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
?>