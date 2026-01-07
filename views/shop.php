<link rel="stylesheet" href="assets/css/shop.css" />
    <!--=============== MAIN ===============-->
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a href="/" class="breadcrumb__link">Home</a></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Shop</span></li>
        </ul>
      </section>

      <!--=============== PROMOTIONAL BANNER SPACE ===============-->

      <!--=============== CATEGORIES ===============-->
      <section class="categories container section">
        <h3 class="section__title"><span>Popular</span> Categories</h3>

        <div class="categories__container">
          <!-- <button class="category-btn prev-btn">
            <i class="fi fi-rs-angle-left"></i>
          </button> -->

          <div class="category-scroll">
            <?php displayCategories(); ?>
          </div>

          <!-- <button class="category-btn next-btn">
            <i class="fi fi-rs-angle-right"></i>
          </button> -->
        </div>
      </section>

      <!--=============== PRODUCTS ===============-->
      <section class="products section--lg container">
        <?php
              $categoryName = isset($_GET['category_name']) ? $_GET['category_name'] : null;
              if ($categoryName) {
                echo '<h3 class="section__title"><span>' . $categoryName . '</span></h3>';
              } else {
                echo '<h3 class="section__title"><span>All Products</span></h3>';
              }
        ?>

        <div class="products__container grid" id="products-container">
          <?php
            $categoryIdFromUrl = null;
            if (isset($_GET['category_id'])) {
                $categoryIdFromUrl = (int) $_GET['category_id'];
            }
            $productIds = fetchProductIds(4, 0, $categoryIdFromUrl);

            foreach ($productIds as $productId) {
              $product = fetchProductDetails($productId);
              if ($product) {
                  $images = fetchImagesFromImageKit($product['ProductImage']);
                  $imagesToDisplay = array_slice($images, 0, 2);
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
                              <div class="product__badge light-pink"><?= htmlspecialchars('Sold out'); ?></div>
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

                          <button href="#" class="action__btn cart__btn" aria-label="Add To Cart"id="addToCartBtn_<?= $productId ?>" data-product-id="<?= $productId ?>" data-product-name="<?= htmlspecialchars($product['ProductName']); ?>" data-product-price="<?= $product['NewPrice'] ?>"  data-category="<?= htmlspecialchars($product['CategoryName']); ?>">
                            <i class="fi fi-rs-shopping-bag-add"></i>
                          </button>
                      </div>
                  </div>
                  <?php
              }
            }
            ?>
            <!-- Products will be dynamically loaded here -->
        </div>
        <div id="loading-indicator" style="text-align: center; display: none;">
            <p>Loading more products...</p>
        </div>
    </section>

      <!--=============== DEALS ===============-->
      <!-- <section class="deals section">
        <div class="deals__container container grid">
          <div class="deals__item">
            <div class="deals__group">
              <h3 class="deals__brand">Deal of the Day</h3>
              <span class="deals__category">Limited quantities.</span>
            </div>

            <h4 class="deals__title">
              Summer Collection New Morden Design
            </h4>

            <div class="deals__price flex">
              <span class="new__price">₹139.00</span>
              <span class="old__price">₹160.99</span>
            </div>

            <div class="deals__group">
              <p class="deals__countdown-text">Hurry up! Offer End In:</p>

              <div class="countdown">
                <div class="countdown__amount">
                  <p class="countdown__period">02</p>
                  <span class="unit">Days</span>
                </div>
                <div class="countdown__amount">
                  <p class="countdown__period">22</p>
                  <span class="unit">Hours</span>
                </div>
                <div class="countdown__amount">
                  <p class="countdown__period">57</p>
                  <span class="unit">Mins</span>
                </div>
                <div class="countdown__amount">
                  <p class="countdown__period">24</p>
                  <span class="unit">Secs</span>
                </div>
              </div>

            </div>

            <div class="deals__btn">
              <a href="details.php" class="btn btn--md">Shop Now</a>
            </div>
          </div>

          <div class="deals__item">
            <div class="deals__group">
              <h3 class="deals__brand">Women Clothing</h3>
              <span class="deals__category">Shirt and Bag.</span>
            </div>

            <h4 class="deals__title">
              Try something new on vacation
            </h4>

            <div class="deals__price flex">
              <span class="new__price">₹178.00</span>
              <span class="old__price">₹160.99</span>
            </div>

            <div class="deals__group">
              <p class="deals__countdown-text">Hurry up! Offer End In:</p>

              <div class="countdown">
                <div class="countdown__amount">
                  <p class="countdown__period">02</p>
                  <span class="unit">Days</span>
                </div>
                <div class="countdown__amount">
                  <p class="countdown__period">22</p>
                  <span class="unit">Hours</span>
                </div>
                <div class="countdown__amount">
                  <p class="countdown__period">57</p>
                  <span class="unit">Mins</span>
                </div>
                <div class="countdown__amount">
                  <p class="countdown__period">24</p>
                  <span class="unit">Secs</span>
                </div>
              </div>

            </div>

            <div class="deals__btn">
              <a href="details.php" class="btn btn--md">Shop Now</a>
            </div>
          </div>
        </div>
      </section> -->

    </main>

    <script>
      let offset = 4;
      const limit = 8;
      let isLoading = false;
      const categoryId = <?= isset($_GET['category_id']) ? intval($_GET['category_id']) : 'null'; ?>;

      function loadProducts() {
          if (isLoading) return;
          isLoading = true;
          document.getElementById('loading-indicator').style.display = 'block';

          let url = `./actions.php?action=fetchProductIds&offset=${offset}&limit=${limit}`;
          if (categoryId !== null) {
              url += `&category_id=${categoryId}`;
          }

          fetch(url)
              .then(response => response.text())
              .then(data => {
                  if (data.trim() !== '') {
                      document.querySelector('#products-container').insertAdjacentHTML('beforeend', data);
                      offset += limit;
                      isLoading = false;
                  } else {
                      // No more products to load
                      isLoading = false;
                      window.removeEventListener('scroll', handleScroll);
                  }
                  document.getElementById('loading-indicator').style.display = 'none';
              })
              .catch(error => {
                  console.error('Error fetching products:', error);
                  isLoading = false;
                  document.getElementById('loading-indicator').style.display = 'none';
              });
      }

      // Event delegation for dynamically loaded "add to cart" buttons
      document.querySelector('#products-container').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('cart__btn')) {
          e.preventDefault();

          // Get product details from data attributes
          var button = e.target;
          var productId = button.getAttribute('data-product-id');
          var productName = button.getAttribute('data-product-name');
          var price = button.getAttribute('data-product-price');
          var category = button.getAttribute('data-category');

          // Get the position of the clicked button
          const rect = e.target.getBoundingClientRect();
          const clickX = rect.left + rect.width / 2;
          const clickY = rect.top + rect.height / 2;

          // Create the animation element
          const animationElement = document.createElement('div');
          animationElement.classList.add('add-to-cart-animation');
          animationElement.style.top = `${clickY}px`;
          animationElement.style.left = `${clickX}px`;
          document.body.appendChild(animationElement);

          // Remove the animation element after the animation ends
          animationElement.addEventListener('animationend', function() {
              animationElement.remove();
          });

          var quantity = 1;
          var customization = null;

          // Use the unified CartSystem to add the item
          CartSystem.addItem(productId=productId, quantity=quantity, price=price, customization=customization)
          .then(data => {
              // Handle the response based on the CartSystem's outcome
              // For guest users, success: true is returned immediately
              // For logged-in users, data.message == '1' indicates success
              if (data.success || data.message == '1') {
                  // alert('Product added to cart!');
                  // Update the cart count in the header using the global function
                  updateCartCountInHeader();
              } else {
                  // Handle server-side errors for logged-in users
                  alert('Error: ' + (data.message || 'Failed to add product to cart.'));
              }
          })
          .catch(error => {
              console.error('Error adding to cart:', error);
              alert('There was an issue adding the item to the cart.');
          }); 
        }
      });

      function handleScroll() {
          const scrollPosition = document.documentElement.scrollTop || document.body.scrollTop;
          const windowHeight = window.innerHeight;
          const documentHeight = document.documentElement.scrollHeight;

          if (scrollPosition + windowHeight >= documentHeight - 100) {
              loadProducts();
          }
      }

      window.addEventListener('scroll', handleScroll);

      // Initial load
      loadProducts();
    </script>
