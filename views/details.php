<?php
  $productId = $_GET['product_id'];
  $productDetails = fetchProductDetails($productId);

  if ($productDetails) {
    function escape($value) {
        return htmlspecialchars($value);
    }

    $productName = escape($productDetails['ProductName']);
    $productDescription = escape($productDetails['ProductDescription']);
    $newPrice = (float) $productDetails['NewPrice'];
    $oldPrice = (float) $productDetails['OldPrice'];
    $sku = escape($productDetails['SKU']);
    $productTags = escape($productDetails['Tags']);
    $availability = escape($productDetails['StockQuantity']);
    $productImage = escape($productDetails['ProductImage']);
    $categoryName = escape($productDetails['CategoryName']);
    $categoryId = escape($productDetails['CategoryId']);
    $discount = $oldPrice > 0 ? round((($oldPrice - $newPrice) / $oldPrice) * 100) : 0;

?>
<link rel="stylesheet" href="assets/css/details.css" />
<!--=============== MAIN ===============-->
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
  <ul class="breadcrumb__list flex container">
    <li><a href="" class="breadcrumb__link">Home</a></li>
    <li><span class="breadcrumb__link"> &gt; </span></li>
    <li><a href="shop" class="breadcrumb__link">Shop</a></li>
    <li><span class="breadcrumb__link"> &gt; </span></li>
    <li><a href="shop/<?php echo $categoryId.'/'.$categoryName; ?>" class="breadcrumb__link"><?php echo $categoryName; ?></a></li>
    <li><span class="breadcrumb__link"> &gt; </span></li>
    <li><span class="breadcrumb__link"><?php echo $productName; ?></span></li>
  </ul>
</section>

      <!--=============== DETAILS ===============-->
      <section class="details section--lg">
        <div class="details__container container grid">
          <div class="details__group">
          <?php displayDetailImages($productImage); ?>
          </div>

          <div class="details__group">
            <h3 class="details__title"><?php echo $productName; ?></h3>
            <p class="details__category">Category: <span><?php echo $categoryName; ?></span></p>


            <div class="details__price flex">
              <span class="new__price">₹<?php echo $newPrice; ?></span>
              <?php if ($oldPrice > 0): ?>
                <span class="old__price">₹<?php echo $oldPrice; ?></span>
                <span class="save__price"><?php echo $discount; ?>% Off</span>
              <?php endif; ?>
            </div>

            <p class="short__description"><?php echo $productDescription; ?></p>
            <?php if ($categoryName == "Bookmarks"): ?>
              <p class="short__description">
              <a href="https://biblophile.freshdesk.com/support/solutions/articles/1060000121728-how-to-use-smart-bookmarks-">
                How to use Smart Bookmarks?
              </a>
              </p>
            <?php endif; ?>

            <div class="customization__container">
              <!-- <ul class="product__list">
                <li class="list__item flex">
                  <i class="fi-rs-crown"></i> 1 year Biblophile Brand Warranty
                </li>

                <li class="list__item flex">
                  <i class="fi-rs-refresh"></i> 30 days return policy
                </li>

                <li class="list__item flex">
                  <i class="fi-rs-credit-card"></i> COD available
                </li>
              </ul> -->

              <?php
                $productCustomizationOptions = fetchCustomizationOptions($productId);

                   if ($productCustomizationOptions) {
                  echo '<div class="details__customization flex">';
                  echo '<ul class="customization-value__list">';
                  
                  $isFirst = true;
                  foreach ($productCustomizationOptions as $customization => $price) {
                    $activeClass = $isFirst ? 'customization-value-active' : '';
                    echo '<li>';
                    echo '<a href="details/' . $productId . '/' . str_replace('+', '%20', urlencode(str_replace(' ', '-', $productName))) . '/#'.$customization.'" class="customization-value__link ' . $activeClass . '" data-price="' . $price . '">' . $customization . '</a>';
                    echo '</li>';
                    $isFirst = false;
                  }
          
                  echo '</ul>';
                  echo '</div>';
              }
              ?>
            </div>

            <?php if ($availability !== '0'): ?>
              <div class="details__action">
                <div class="quantity-controls">
                  <button class="quantity-btn" id="decrementBtn">-</button>
                  <input type="number" class="quantity" id="quantity" value="1" min="1" />
                  <button class="quantity-btn" id="incrementBtn">+</button>
                </div>
                <button class="btn btn--sm" id="addToCartBtn">Add to Cart</button>
              </div>
            <?php endif; ?>

            <ul class="details__meta">
              <li class="meta__list flex"><span>SKU:</span> <?php echo $sku; ?></li>
              <li class="meta__list flex">
                <span>Tags:</span> <?php echo $productTags; ?>
              </li>
              <?php if ($availability !== ''): ?>
                <li class="meta__list flex">
                    <span>Availability:</span> <?php echo htmlspecialchars($availability); ?> items in stock
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </section>

      <!--=============== DETAILS TAB ===============-->
      <section class="details__tab container">
        <div class="detail__tabs">
          <span class="detail__tab active-tab" data-target="#info">
            Additional Info
          </span>
          <!-- <span class="detail__tab" data-target="#reviews">Reviews(3)</span> -->
        </div>

        <div class="details__tabs-content">
          <div class="details__tab-content active-tab" content id="info">
            <?php 
              $productAdditionalInfo = fetchProductAdditionalInfo($productId);

              if ($productAdditionalInfo) {
                  echo '<table class="info__table">';
                  
                  foreach ($productAdditionalInfo as $key => $value) {
                      echo '<tr>';
                      echo '<th>' . htmlspecialchars(ucfirst($key)) . '</th>';
                      echo '<td>' . htmlspecialchars($value) . '</td>';
                      echo '</tr>';
                  }
              
                  echo '</table>';
              } else {
                  echo 'No additional information available.';
              }
            ?>
          </div>

          <div class="details__tab-content" content id="reviews">
            <div class="reviews__container grid">
              <?php displayReviews($productId); ?>
            </div>

            <div class="review__form">
              <h4 class="review__form-title">Add a review</h4>
              
              <div class="rate__product">
                <i class="fi fi-rs-star"></i>
                <select id="rating" name="rating" class="form__input">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>

                <select id="decimal" name="decimal" class="form__input">
                    <option value="0.00">.00</option>
                    <option value="0.25">.25</option>
                    <option value="0.50">.50</option>
                    <option value="0.75">.75</option>
                </select>
              </div>

              <form action="" class="form grid">
                <textarea 
                  class="form__input textarea"
                  id="review-entry"
                  placeholder="Write Review">
                </textarea>

                <div class="form__btn">
                  <button class="btn" id="submitRatingBtn">Submit Review</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>

      <!--=============== RELATED PRODUCTS ===============-->
      <section class="related__products section" id="products-container">
        <h3 class="section__title"><span>Related</span> Products</h3>
        <div class="related__products__container swiper">
        <div class="swiper-wrapper">
          <?php
            $productIds = fetchProductIds(5, 0, $productDetails['CategoryId']);

            foreach ($productIds as $productId) {
              $product = fetchProductDetails($productId);
              if ($product) {
                $images = fetchImagesFromImageKit($product['ProductImage']);
                $imagesToDisplay = array_slice($images, 0, 2);
                ?>
                <div class="product__item swiper-slide">
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

                    <!-- gotta write the logic to add to cart here  -->
                    <!-- <a href="#" class="action__btn cart__btn" aria-label="Add To Cart">
                      <i class="fi fi-rs-shopping-bag-add"></i>
                    </a> -->
                  </div>
                </div>
                <?php
              }
            }
          ?>
          </div>

<div class="swiper-button-next">
  <i class="fi fi-rs-angle-right"></i>
</div>
<div class="swiper-button-prev">
  <i class="fi fi-rs-angle-left"></i>
</div>
        </div>
      </section>
    </main>

<?php
  } else {
      echo "<p>Product not found.</p>";
  }
?>

<!--=============== SWIPER JS ===============-->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>

  /*=============== IMAGE GALLERY ===============*/
  function imgGallery() {
      const mainImg = document.querySelector('.details__img'),
      smallImg = document.querySelectorAll('.details__small-img');

      smallImg.forEach((img) => {
          img.addEventListener('click', function() {
            let newSrc = this.src;

            // Check if the src contains &tr=, and if so, remove everything after &tr=
            if (newSrc.includes('&tr=')) {
                newSrc = newSrc.replace(/&tr=[^&]*/, '');
            }

            newSrc += '&tr=w-400';

            mainImg.src = newSrc;
          });
      });
  }

  imgGallery();

  document.addEventListener("DOMContentLoaded", () => {
  const customizationLinks = document.querySelectorAll(".customization-value__link");
  const newPriceElement = document.querySelector(".new__price");
  const oldPriceElement = document.querySelector(".old__price");
  const discountElement = document.querySelector(".save__price");

  const formatPrice = (price) => `₹${price.toFixed(2)}`;

  const updateCustomization = (hash) => {
    customizationLinks.forEach((link) => {
      const customization = link.getAttribute("href").split("#")[1];
      if (customization === hash) {
        link.classList.add("customization-value-active");
        const updatedPrice = parseFloat(link.getAttribute("data-price"));
        if (!isNaN(updatedPrice)) {
          newPriceElement.textContent = formatPrice(updatedPrice);
          // Hide old price and discount if a customization is applied
          if (oldPriceElement) oldPriceElement.style.display = "none";
          if (discountElement) discountElement.style.display = "none";
        }
      } else {
        link.classList.remove("customization-value-active");
      }
    });
  };

  const hash = window.location.hash.substring(1);
  if (hash) {
    updateCustomization(hash);
  }

  customizationLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      const customization = link.getAttribute("href").split("#")[1];
      updateCustomization(customization);
      const currentUrl = window.location.href.split("#")[0];
      history.replaceState(null, "", `${currentUrl}#${customization}`);
    });
  });
});

  /*=============== SWIPER RELATED PRODUCTS ===============*/
  var swiperProducts = new Swiper(".related__products__container", {
      spaceBetween: 24,
      loop: true,
      
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },

      breakpoints: {
          300: {
              slidesPerView: 2.5,
              spaceBetween: 24,
          },
          448: {
              slidesPerView: 3,
              spaceBetween: 24,
          },
          768: {
              slidesPerView: 3,
              spaceBetween: 24,
          },
          992: {
              slidesPerView: 3,
              spaceBetween: 44,
          },
          1400: {
              slidesPerView: 5,
              spaceBetween: 24,
          },
      }
  });

  /*=============== Add to Cart Submission ===============*/
  document.addEventListener('DOMContentLoaded', function () {
  const decrementBtn = document.getElementById('decrementBtn');
  const incrementBtn = document.getElementById('incrementBtn');
  const quantityInput = document.getElementById('quantity');

  // Decrease quantity by 1, but not below 1
  decrementBtn.addEventListener('click', function () {
    let currentQuantity = parseInt(quantityInput.value, 10);
    if (currentQuantity > 1) {
      quantityInput.value = currentQuantity - 1;
    }
  });

  // Increase quantity by 1
  incrementBtn.addEventListener('click', function () {
    let currentQuantity = parseInt(quantityInput.value, 10);
    quantityInput.value = currentQuantity + 1;
  });
});

  document.getElementById('addToCartBtn').addEventListener('click', function(e) {
    e.preventDefault();

    var quantity = document.getElementById('quantity').value;
    var price = <?php echo $productDetails['NewPrice']; ?>;
    var productId = <?php echo $productDetails['ProductId']; ?>;
    const hash = window.location.hash.substring(1);
    var customization = null;
    if (hash) {
      customization = decodeURIComponent(hash);
    }

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

    // Use the unified CartSystem to add the item
    CartSystem.addItem(productId=productId, quantity=quantity, price=price, customization=customization)
        .then(data => {
            // Handle the response based on the CartSystem's outcome
            // For guest users, success: true is returned immediately
            // For logged-in users, data.message == '1' indicates success
            if (data.success || data.message == '1') {
                //  alert('Product added to cart!');
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
});

  /*=============== Submiting Rating ===============*/
  const ratingSelect = document.getElementById('rating');
  const decimalSelect = document.getElementById('decimal');

  ratingSelect.addEventListener('change', function() {
      // If rating is 5, only allow .00 in the decimal select
      if (this.value == '5') {
          decimalSelect.innerHTML = '<option value="0.00">.00</option>';
      } else {
          decimalSelect.innerHTML = `
              <option value="0.00">.00</option>
              <option value="0.25">.25</option>
              <option value="0.50">.50</option>
              <option value="0.75">.75</option>
          `;
      }
  });

   document.getElementById('submitRatingBtn').addEventListener('click', function(e) {
    e.preventDefault();

    var ratingWhole = document.getElementById('rating').value;
    var ratingDecimal = document.getElementById('decimal').value;
    var review = document.getElementById('review-entry').value;
    var productId = <?php echo $productId; ?>;

    var finalRating = parseFloat(ratingWhole) + parseFloat(ratingDecimal);

    var formData = new FormData();
    formData.append('productId', productId);
    formData.append('rating', finalRating);
    formData.append('review', review);

    fetch('actions.php?action=submitReview', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.message == '1') {
        alert('Review submitted');
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error adding the rating:', error);
      // alert('There was an issue adding the item to the cart.');
      alert('Please login to add rating.');
    });
  });

</script>
