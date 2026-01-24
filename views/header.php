<?php
$is_logged_in = isset($_COOKIE['user_session']) && !empty($_COOKIE['user_session']);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php generateMetaTags($pageData); ?>

    <!-- Set the base URL dynamically depending on local or live environment -->
    <base href="<?php $is_local = isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']); echo $is_local ? '/Salty-Lameon-Shop/' : '/'; ?>">

    <!--=============== FLATICON ===============-->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-straight/css/uicons-regular-straight.css" />

    <!--=============== SWIPER CSS ===============-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    />

    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="assets/css/header.css" />

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PMC7KDVW');</script>
    <!-- End Google Tag Manager -->

  </head>
  <body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PMC7KDVW"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <!--=============== HEADER ===============-->
    <!-- for wavePath used in home.css to provide wave-y effect to the banner's bottom -->
    <svg width="0" height="0" style="position:absolute;">
      <defs>
        <clipPath id="wavePath" clipPathUnits="objectBoundingBox">
          <path d="M0,0 H1 V0.8 C0.85,0.95 0.65,0.75 0.5,0.85 C0.35,0.95 0.15,0.75 0,0.85 V0 Z" />
        </clipPath>
      </defs>
    </svg>
    <header class="header">
      <!-- <div class="header__top">
        <div class="header__container container">
          <div class="header__contact">
            <span>(+91) - 70025 - 4678</span>

            <span> Our location</span>
          </div>

          <p class="header__alert-news">
            Super Value Deals - Save more with coupons
          </p>

          <?php if (!$is_logged_in): ?>
            <a href="login-register" class="header__top-action">
              Login / Sign up
            </a>
          <?php endif; ?>
        </div>
      </div> -->

      <nav class="nav container">
        <div class="nav__menu" id="nav-menu">
          <div class="nav__menu-top">
            <a href="index.php" class="nav__menu-logo">
              <img src="assets/img/logo.svg" alt="">
            </a>

            <div class="nav__close" id="nav-close">
              <i class="fi fi-rs-cross-small"></i>
            </div>
          </div>
          
          <ul class="nav__list">
            <li class="nav__item">
              <a href="home" class="nav__link <?= $current_page === 'home' ? 'active-link' : '' ?>">Home</a>
            </li>

            <li class="nav__item">
              <a href="shop"class="nav__link <?= $current_page === 'shop' ? 'active-link' : '' ?>">Shop</a>
            </li>

            <?php if ($is_logged_in): ?>
              <li class="nav__item">
                <a href="accounts" class="nav__link<?= $current_page === 'accounts' ? 'active-link' : '' ?>">My Account</a>
              </li>
            <?php endif; ?>

            <li class="nav__item">
              <a href="https://biblophile.freshdesk.com/" class="nav__link">Contact Us</a>
            </li>

            <li class="nav__item">
              <a href="" class="nav__link"></a>
            </li>
          </ul>

          <div class="header__search">
            <input 
              type="text"
              id="searchQuery"
              placeholder="Search for items..."
              class="form__input"
            />
            <div id="searchSuggestions" style="display:none;"></div>

            <button class="search__btn">
              <img src="assets/img/search.png" alt="" />
            </button>
          </div>
        </div>

        <div class="header__user-actions">
          <?php if (!$is_logged_in): ?>
            <a href="cart" class="header__action-btn">
              <img src="assets/img/icon-cart.svg" alt="">
                <span id="guest-cart-count" class="count"></span>
            </a>
          <?php endif; ?>
          <?php if ($is_logged_in): ?>
            <a href="cart" class="header__action-btn">
              <img src="assets/img/icon-cart.svg" alt="">
              <?php $cartNumber = displayCartNumber(); ?>
              <?php if (!empty($cartNumber)): ?>
                  <span class="count"><?php echo $cartNumber; ?></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>
          <?php if (!$is_logged_in): ?>
            <a href="login-register" class="nav__link <?= $current_page === 'login-register' ? 'active-link' : '' ?>">Login / Sign up</a>
          <?php endif; ?>
          
          <div class="header__action-btn nav__toggle" id="nav-toggle">
            <img src="assets/img/menu-burger.svg" alt="">
          </div>
        </div>
      </nav>
    </header>

    <!-- Banner Section -->
    <section class="banner">
      <div class="banner__image-container">
        <img src="https://ik.imagekit.io/umjnzfgqh/shop/common_assets/banners/banner.jpeg" alt="Banner Image" class="banner__image banner__image--small">
        <img src="https://ik.imagekit.io/umjnzfgqh/shop/common_assets/banners/banner-large.png" alt="Banner Image" class="banner__image banner__image--large">
      </div>
    </section>

    <!-- Make sure this is added before any script that uses CartSystem -->
    <script>
        var USER_LOGGED_IN = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    </script>
    <script src="./assets/js/cart-system.js"></script>

    <script>
      let selectedProductId = null;

      //--suggestions requests for searces--//
      document.getElementById("searchQuery").addEventListener("input", function() {
          var query = this.value.trim();

          if (query.length > 0) {
            fetchSearches(query);
          } else {
              document.getElementById("searchSuggestions").style.display = 'none';
          }
      });

      document.getElementById("searchQuery").addEventListener("keydown", function(event) {
          var suggestionsDiv = document.getElementById("searchSuggestions");
          var activeItem = suggestionsDiv.querySelector('.suggestion-item.active');
          
          if (event.key === 'ArrowDown') {
              if (activeItem) {
                  var nextItem = activeItem.nextElementSibling;
                  if (nextItem) {
                      activeItem.classList.remove('active');
                      nextItem.classList.add('active');
                  }
              } else {
                  var firstItem = suggestionsDiv.querySelector('.suggestion-item');
                  if (firstItem) firstItem.classList.add('active');
              }
          } else if (event.key === 'ArrowUp') {
              if (activeItem) {
                  var prevItem = activeItem.previousElementSibling;
                  if (prevItem) {
                      activeItem.classList.remove('active');
                      prevItem.classList.add('active');
                  }
              }
          } else if (event.key === 'Enter' && activeItem) {
              activeItem.click();
          }
      });

      function fetchSearches(query) {
        fetch(`actions.php?action=fetchSearchResults&query=${encodeURIComponent(query)}`)
          .then(response => response.json())
          .then(data => {
              var suggestionsDiv = document.getElementById("searchSuggestions");

              if (data && data.length > 0) {
                  suggestionsDiv.style.display = 'block';
                  suggestionsDiv.innerHTML = '';

                  data.forEach(function(searchResults) {
                      var suggestionItem = document.createElement("p");
                      suggestionItem.textContent = searchResults.ProductName;
                      suggestionItem.classList.add('suggestion-item');

                      // Store the Product ID in the suggestion item as a data attribute
                      suggestionItem.dataset.productId = searchResults.ProductId;
                      
                      suggestionItem.addEventListener("click", function() {
                          document.getElementById("searchQuery").value = searchResults.ProductName;
                          selectedProductId = searchResults.ProductId;
                          suggestionsDiv.style.display = 'none';
                      });

                      suggestionsDiv.appendChild(suggestionItem);
                  });
              } else {
                  suggestionsDiv.style.display = 'none';
              }
          })
          .catch(error => {
              console.error("Error fetching results:", error);
          });
      }

      // Handle search button click
      document.querySelector('.search__btn').addEventListener('click', function() {
          if (selectedProductId) {
              // Redirect to the product page with the ProductId and ProductName
              var productName = document.getElementById("searchQuery").value.trim().replace(/\s+/g, '-').toLowerCase(); // Ensure the ProductName is URL friendly
              window.location.href = `details/${selectedProductId}/${productName}`;
          } else {
              alert('Please select a product from the suggestions.');
          }
      });
    </script>