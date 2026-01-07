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
                'meta_title' => 'Login / Register - Pages & Palette',
                'meta_description' => 'Log in or register to start shopping bookish merchandise like bookmarks, stickers, and more at Pages & Palette.',
                'meta_keywords' => 'login, register, bookmarks, stickers, bookish merchandise, Pages & Palette',
                'meta_url' => 'https://shop.biblophile.com/login-register',
            ];
            break;
        case 'accounts':
            $pageData = [
                'meta_title' => 'My Account - Pages & Palette',
                'meta_description' => 'Manage your account and order history at Pages & Palette.',
                'meta_keywords' => 'account, user, orders, bookmarks, stickers, Pages & Palette',
                'meta_url' => 'https://shop.biblophile.com/accounts',
            ];
            break;
        case 'cart':
            $pageData = [
                'meta_title' => 'Your Cart - Pages & Palette',
                'meta_description' => 'Review your cart including bookmarks, stickers, and other fun items at Pages & Palette.',
                'meta_keywords' => 'cart, shopping, bookmarks, stickers, bookish merch, Pages & Palette',
                'meta_url' => 'https://shop.biblophile.com/cart',
            ];
            break;
        case 'checkout':
            $pageData = [
                'meta_title' => 'Checkout - Pages & Palette',
                'meta_description' => 'Complete your purchase of bookish merchandise like bookmarks, stickers, and more at Pages & Palette..',
                'meta_keywords' => 'checkout, payment, bookmarks, stickers, bookish merch, Pages & Palette',
                'meta_url' => 'https://shop.biblophile.com/checkout',
            ];
            break;
        case 'shop':
            $pageData = [
                'meta_title' => 'Shop Bookish Merchandise - Pages & Palette',
                'meta_description' => 'Browse our unique collection of bookish merch like bookmarks, stickers, and more at Pages & Palette.',
                'meta_keywords' => 'shop, bookish merchandise, bookmarks, stickers, Pages & Palette',
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
                  'meta_title' => $productName . ' - Pages & Palette',
                  'meta_description' => $productDescription,
                  'meta_keywords' => 'shop, products, ecommerce',
                  'meta_image' => $productImage[0],
              ];
          }
          else {
            $pageData = [
              'meta_title' => 'Pages & Palette | Biblophile Merchandise Store',
              'meta_description' => 'Explore a wide range of bookish merch like bookmarks, stickers, and more at Pages & Palette.',
              'meta_keywords' => 'bookish merchandise, bookmarks, stickers, Pages & Palette',
              'meta_url' => 'https://shop.biblophile.com',
          ];
          }
            break;
        // Add more cases as needed for other pages
        default:
            $pageData = [
                'meta_title' => 'Pages & Palette | Biblophile Merchandise Store',
                'meta_description' => 'Welcome to Pages & Palette – your shop for bookish merchandise like bookmarks, stickers, and more!',
                'meta_keywords' => 'bookish merchandise, bookmarks, stickers, Pages & Palette',
                'meta_url' => 'https://shop.biblophile.com',
            ];
            break;
    }
    
    include("views/header.php");

    if ($page == 'login-register') {

      include("views/login-register.php");

    }else if ($page == 'portfolio') {
            
      include("views/portfolio.php");
        
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
        
    }else {
        
      include("views/home.php");
        
    }
                
    include("views/footer.php");

?>