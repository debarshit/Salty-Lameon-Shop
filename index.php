<?php

    include("functions.php");

    // Check if the aff_Id is passed in the URL
    if (isset($_GET['aff_id'])) {
      $affiliateId = $_GET['aff_id'];

      $encodedAffiliateInfo = base64_encode(json_encode([
          'affiliateId' => $affiliateId
      ]));

      setcookie('affiliate_info', $encodedAffiliateInfo, [
          'expires' => time() + (86400 * 30),
          'path' => '/',
          'domain' => '.biblophile.com',
          // 'secure' => ($_SERVER['HTTPS'] ?? false) === 'on',  // Uncomment if using HTTPS
          'httponly' => true,
          'samesite' => 'Lax',
      ]);
    }

    $page = isset($_GET['page']) ? $_GET['page'] : 'home';

    $pageData = []; // Default meta data

    // Set custom meta data for each page
    switch ($page) {
        case 'login-register':
            $pageData = [
                'meta_title' => 'Login / Register - The Salty Lameon',
                'meta_description' => 'Log in or register to start shopping art works like bookmarks, stickers, and more at The Salty Lameon.',
                'meta_keywords' => 'login, register, bookmarks, stickers, art works, The Salty Lameon',
                'meta_url' => 'https://shop.biblophile.com/login-register',
            ];
            break;
        case 'accounts':
            $pageData = [
                'meta_title' => 'My Account - The Salty Lameon',
                'meta_description' => 'Manage your account and order history at The Salty Lameon.',
                'meta_keywords' => 'account, user, orders, bookmarks, stickers, The Salty Lameon',
                'meta_url' => 'https://shop.biblophile.com/accounts',
            ];
            break;
        case 'cart':
            $pageData = [
                'meta_title' => 'Your Cart - The Salty Lameon',
                'meta_description' => 'Review your cart including bookmarks, stickers, and other fun items at The Salty Lameon.',
                'meta_keywords' => 'cart, shopping, bookmarks, stickers, art works, The Salty Lameon',
                'meta_url' => 'https://shop.biblophile.com/cart',
            ];
            break;
        case 'checkout':
            $pageData = [
                'meta_title' => 'Checkout - The Salty Lameon',
                'meta_description' => 'Complete your purchase of products like bookmarks, stickers, and more at The Salty Lameon..',
                'meta_keywords' => 'checkout, payment, bookmarks, stickers, art works, The Salty Lameon',
                'meta_url' => 'https://shop.biblophile.com/checkout',
            ];
            break;
        case 'shop':
            $pageData = [
                'meta_title' => 'Shop Art Products - The Salty Lameon',
                'meta_description' => 'Browse our unique collection of products like bookmarks, stickers, and more',
                'meta_keywords' => 'shop, bookmarks, stickers, The Salty Lameon',
                'meta_url' => 'https://shop.biblophile.com/shop',
            ];
            break;
        case 'details':
          $productId = $_GET['product_id'];
          $productDetails = fetchProductDetails($productId);

          if ($productDetails) {

            $productName = $productDetails['ProductName'];
            $productDescription = $productDetails['ProductDescription'];
            $productImage = fetchImagesFromImageKit($productDetails['ProductImage']);
            $productTags = $productDetails['Tags'];
              $pageData = [
                  'meta_title' => $productName . ' - The Salty Lameon',
                  'meta_description' => $productDescription,
                  'meta_keywords' => 'shop, products, ecommerce',
                  'meta_image' => $productImage[0],
              ];
          }
          else {
            $pageData = [
              'meta_title' => 'The Salty Lameon',
              'meta_description' => 'Welcome to The Salty Lameon your shop for art products, and more!.',
              'meta_keywords' => 'bookish merchandise, bookmarks, stickers, notepads, snail mail',
              'meta_url' => 'https://shop.biblophile.com',
          ];
          }
            break;
        // Add more cases as needed for other pages
        default:
            $pageData = [
                'meta_title' => 'The Salty Lameon',
                'meta_description' => 'Welcome to The Salty Lameon your shop for art products, and more!',
                'meta_keywords' => 'bookish merchandise, bookmarks, stickers, notepads, snail mail',
                'meta_url' => 'https://shop.biblophile.com',
            ];
            break;
    }
    
    include("views/header.php");

    if ($page == 'login-register') {

      include("views/login-register.php");

    }else if ($page == 'order-details') {
            
      include("views/order-details.php");
        
    }else if ($page == 'accounts') {
            
      include("views/accounts.php");
        
    }
    else if ($page == 'cart') {
        
      include("views/cart.php");
        
    }else if ($page == 'checkout') {
        
      include("views/checkout.php");
        
    }else if ($page == 'compare') {
        
      include("views/compare.php");
        
    }else if ($page == 'details') {
        
      include("views/details.php");
        
    }else if ($page == 'shop') {
        
      include("views/shop.php");
        
    }else if ($page == 'wishlist') {
        
      include("views/wishlist.php");
        
    }else if ($page == 'reset-password') {
        
      include("views/reset-password.php");
        
    }else {
        
      include("views/home.php");
        
    }
                
    include("views/footer.php");

?>