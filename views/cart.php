    <?php
      if ($is_logged_in) {
        $addresses = fetchUserAddresses();
        $cartSubtotal = displayCartList(false);
        
        $shippingCharge = 0;
        if ($cartSubtotal >= 150) {
        $shippingCharge = 0;
        } else if (!empty($addresses)) {
        $shippingCharge = calculateShippingChargesByPincode($addresses[0]['PostalCode']);
        } else {
        $shippingCharge = 0;
        }
      } else {
        $cartSubtotal = 0;
        $shippingCharge = 0;
      }
    ?>
    <link rel="stylesheet" href="assets/css/cart.css" />
    <!--=============== MAIN ===============-->
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a href="/" class="breadcrumb__link">Home</a></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Shop</span></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Cart</span></li>
        </ul>
      </section>

      <!--=============== CART ===============-->
      <section class="cart section--lg container">
        <div class="table__container">
          <table class="table">
            <tr class="table__header">
              <th>Image</th>
              <th>Name</th>
              <th>Price</th>
              <th>Quantity</th>
              <th>Subtotal</th>
              <th>Remove</th>
            </tr>

            <?php
              if ($is_logged_in) {
                displayCartList(true); // Display cart for logged-in users
              } else {
                // We'll use JavaScript to get localStorage and potentially reload
                echo '<tbody id="guest-cart-items">';
                echo '<tr><td colspan="6">Loading guest cart...</td></tr>';
                echo '</tbody>';
              }
            ?>
          </table>
        </div>

        <div class="cart__actions">
          <?php if (!$is_logged_in): ?>
            <a href="login-register?returnUrl=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn flex btn--md">
              <i class="fi fi-rs-exit"></i> Login to track your order
            </a>
          <?php endif; ?>
          <a href="shop" class="btn flex btn--md">
            <i class="fi-rs-shopping-bag"></i> Continue Shopping
          </a>
        </div>

        <div class="divider">
          <i class="fi fi-rs-fingerprint"></i>
        </div>

        <div class="cart__group grid">
          <div>
            <div class="cart__shipping">
              <h3 class="section__title">Calculate Shipping</h3>
              
              <form action="" class="form grid">
                <!-- <input type="text" placeholder="State/Country" class="form__input"> -->

                <div class="form__group grid">
                  <!-- <input type="text" placeholder="City" class="form__input"> -->

                  <input type="text" id="pincode" placeholder="Pincode" class="form__input" value="<?php echo $addresses[0]['PostalCode']; ?>">
                </div>

                <div class="form__btn">
                  <button type="button" id="calculateShipping" class="btn flex btn--sm">
                    <i class="fi-rs-shuffle"></i> Calculate
                  </button>
                </div>
              </form>
            </div>

            <!-- <div class="cart__coupon">
              <h3 class="section__title">Apply Coupon</h3>

              <form action="" class="coupon__form form grid">
                <div class="form__group grid">
                  <input 
                    type="text" 
                    class="form__input" 
                    placeholder="Enter your coupon code"
                  />

                  <div class="form__btn">
                    <button class="btn flex btn--sm">
                      <i class="fi-rs-label"></i> Apply
                    </button>
                  </div>
                </div>
              </form>
            </div> -->
          </div>

          <div class="cart__total">
            <h3 class="section__title">Cart Totals</h3>
            
            <table class="cart__total-table">
              <tr>
                <td><span class="cart__total-title">Cart Subtotal</span></td>
                <td><span class="cart__total-price">₹<?php echo number_format($cartSubtotal, 2); ?></span></td>
              </tr>

              <tr>
                <td><span class="cart__total-title">Shipping</span></td>
                <td><span class="cart__total-price">₹<?php echo number_format($shippingCharge, 2); ?></span></td>
              </tr>

              <tr>
                <td><span class="cart__total-title">Total</span></td>
                <td><span class="cart__total-price">₹<?php echo number_format($cartSubtotal + $shippingCharge, 2); ?></span></td>
              </tr>
            </table>

            <a href="checkout" class="btn flex btn--md">
              <i class="fi fi-rs-box-alt"></i> Proceed to checkout
            </a>
          </div>
        </div>
      </section>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Initialize cart elements
        const cartSubtotalElement = document.querySelector('.cart__total-table tr:nth-child(1) td .cart__total-price');
        const shippingChargeElement = document.querySelector('.cart__total-table tr:nth-child(2) td .cart__total-price');
        const totalElement = document.querySelector('.cart__total-table tr:nth-child(3) td .cart__total-price');
        const pincodeInput = document.getElementById('pincode');
        const calculateShippingBtn = document.getElementById('calculateShipping');
        
        // Set up event handlers for cart items
        setupCartItemHandlers();
        
        // Setup calculate shipping button
        calculateShippingBtn.addEventListener('click', function() {
          const pincode = pincodeInput.value.trim();
          
          if (!pincode) {
            alert("Please enter a valid pincode.");
            return;
          }
          
          // Use CartSystem to recalculate totals with new pincode
          CartSystem.calculateTotals(pincode)
            .then(data => {
              if (data.success) {
                updateCartTotalsDisplay(data.subtotal, data.shipping, data.total);
              } else {
                alert(data.message || "Failed to calculate shipping.");
              }
            })
            .catch(error => {
              console.error("Error calculating shipping:", error);
              alert("An error occurred while calculating shipping.");
            });
        });
        
        // For guest users, load cart content dynamically
        if (!CartSystem.isLoggedIn) {
          loadGuestCartItems();
        }
        
        /**
         * Set up event handlers for cart items
         */
        function setupCartItemHandlers() {
          // Handle quantity changes
          document.querySelectorAll('.quantity').forEach(input => {
            input.addEventListener('change', function() {
              const productId = this.getAttribute('data-product-id');
              const customization = this.getAttribute('data-customization') || null;
              const newQuantity = parseInt(this.value);
              const row = this.closest('tr');
              
              if (newQuantity <= 0) {
                // Remove item if quantity is zero or less
                handleRemoveItem(productId, customization, row);
                return;
              }
              
              // Optimistically update the UI
              const price = parseFloat(row.querySelector('.table__price').textContent.replace('₹', ''));
              const oldSubtotal = parseFloat(row.querySelector('.table__subtotal').textContent.replace('₹', ''));
              const newSubtotal = price * newQuantity;
              
              row.querySelector('.table__subtotal').textContent = '₹' + newSubtotal.toFixed(2);
              
              // Use CartSystem to update quantity
              CartSystem.updateQuantity(productId, newQuantity, customization)
                .then(data => {
                  if (data.success) {
                    // Refresh cart totals
                    refreshCartTotals();
                    updateCartCount();
                  } else {
                    // Revert the UI on failure
                    row.querySelector('.table__subtotal').textContent = '₹' + oldSubtotal.toFixed(2);
                    this.value = oldSubtotal / price;
                    alert(data.message || "Failed to update quantity.");
                  }
                })
                .catch(error => {
                  console.error("Error updating quantity:", error);
                  // Revert the UI on error
                  row.querySelector('.table__subtotal').textContent = '₹' + oldSubtotal.toFixed(2);
                  this.value = oldSubtotal / price;
                  alert("An error occurred while updating quantity.");
                });
            });
          });
          
          // Handle remove item clicks
          document.querySelectorAll('.table__trash').forEach(item => {
            item.addEventListener('click', function() {
              const productId = this.getAttribute('data-product-id');
              const customization = this.getAttribute('data-customization') || null;
              const row = this.closest('tr');
              
              handleRemoveItem(productId, customization, row);
            });
          });
        }
        
        /**
         * Handle removing an item from the cart
         * 
         * @param {string} productId - The product ID
         * @param {string|null} customization - Optional customization
         * @param {HTMLElement} row - The table row element
         */
        function handleRemoveItem(productId, customization, row) {
          // Get the current subtotal before removing
          const subtotal = parseFloat(row.querySelector('.table__subtotal').textContent.replace('₹', ''));
          
          // Optimistically remove the row
          row.style.transition = 'opacity 0.3s ease';
          row.style.opacity = '0.5';
          
          // Use CartSystem to remove the item
          CartSystem.removeItem(productId, customization)
            .then(data => {
              if (data.success) {
                // Animate removal
                setTimeout(() => {
                  row.remove();
                  
                  // Check if cart is empty
                  const remainingItems = document.querySelectorAll('table tr:not(:first-child)').length;
                  if (remainingItems === 0) {
                    const tableBody = document.querySelector('table tbody') || document.querySelector('table');
                    tableBody.innerHTML += '<tr><td colspan="6">Your cart is empty.</td></tr>';
                  }
                  
                  // Refresh cart totals and update header count
                  refreshCartTotals();
                  updateCartCount();
                }, 300);
              } else {
                // Revert the UI on failure
                row.style.opacity = '1';
                alert(data.message || "Failed to remove item.");
              }
            })
            .catch(error => {
              console.error("Error removing item:", error);
              row.style.opacity = '1';
              alert("An error occurred while removing item.");
            });
        }
        
        /**
         * Load guest cart items from localStorage via AJAX
         */
        function loadGuestCartItems() {
          const guestCartItemsContainer = document.getElementById('guest-cart-items');
          const guestCart = localStorage.getItem('guestCart');
          
          if (!guestCart || JSON.parse(guestCart).length === 0) {
            guestCartItemsContainer.innerHTML = '<tr><td colspan="6">Your cart is empty.</td></tr>';
            updateCartTotalsDisplay(0, 0, 0);
            return;
          }
          
          // Fetch cart items HTML from server
          fetch('actions.php?action=displayGuestCart', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'guestCart=' + encodeURIComponent(guestCart)
          })
          .then(response => response.text())
          .then(html => {
            guestCartItemsContainer.innerHTML = html;
            setupCartItemHandlers(); // Setup handlers for new cart items
            refreshCartTotals(); // Calculate and display totals
          })
          .catch(error => {
            console.error("Error loading guest cart:", error);
            guestCartItemsContainer.innerHTML = '<tr><td colspan="6">Failed to load cart items.</td></tr>';
            updateCartTotalsDisplay(0, 0, 0);
          });
        }
        
        /**
         * Refresh cart totals using CartSystem
         */
        function refreshCartTotals() {
          const pincode = pincodeInput.value.trim();
          
          CartSystem.calculateTotals(pincode)
            .then(data => {
              if (data.success) {
                updateCartTotalsDisplay(data.subtotal, data.shipping, data.total);
              } else {
                console.error("Error calculating totals:", data.message);
              }
            })
            .catch(error => {
              console.error("Error refreshing cart totals:", error);
            });
        }
        
        /**
         * Update cart totals display
         * 
         * @param {number} subtotal - Cart subtotal
         * @param {number} shipping - Shipping charge
         * @param {number} total - Cart total
         */
        function updateCartTotalsDisplay(subtotal, shipping, total) {
          cartSubtotalElement.textContent = '₹' + parseFloat(subtotal).toFixed(2);
          shippingChargeElement.textContent = '₹' + parseFloat(shipping).toFixed(2);
          totalElement.textContent = '₹' + parseFloat(total).toFixed(2);
        }
      });
    </script>