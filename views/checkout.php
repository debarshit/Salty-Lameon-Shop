    <?php
      if ($is_logged_in) {
        $addresses = fetchUserAddresses();
        $userDetails = fetchUserDetails();
        $name = $userDetails['name'];
        $phone = $userDetails['phone'];
        $accessToken = getAccessTokenFromSession();
        list($subtotal, $cartItems) = displayCheckoutTable(false);
        $shippingCharge = 0;
        $selectedPincode = isset($addresses[0]['PostalCode']) ? $addresses[0]['PostalCode'] : '';
        if ($subtotal >= 150) {
          $shippingCharge = 0;
        } else {
          $shippingCharge = calculateShippingChargesByPincode($selectedPincode);
        }
      } else {
        $subtotal = 0;
        $shippingCharge = 0;
      }
    ?>
    <link rel="stylesheet" href="assets/css/checkout.css" />
    <!--=============== MAIN ===============-->
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a href="/" class="breadcrumb__link">Home</a></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Shop</span></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Checkout</span></li>
        </ul>
      </section>

      <!--=============== SUCCESS ANIMATION ===============-->
      <div class="success-animation" id="successAnimation" style="display: none;">
        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
        </svg>
      </div>

      <!--=============== CHECKOUT ===============-->
      <section class="checkout section--lg">
        <div class="checkout__container container grid">
          <div class="checkout__group checkout__address">
            <button type="button" class="checkout__collapse-toggle" aria-expanded="true">
              <h3 class="section__title">Billing Details</h3>
              <span class="collapse__icon">−</span>
            </button>

            <div class="checkout__collapse-content">
            <?php 
              if (isset($addresses['error'])) {
                echo '<p>' . htmlspecialchars($addresses['error']) . '</p>';
                return;
              }
          
              if (empty($addresses)) {
                  echo '<p>You have no saved addresses. Please add an address to proceed.</p> <a href="accounts#address" class="btn btn--md">Add Address</a>';
              } else {
                echo '<h3 class="section__title">Select Billing Address</h3>';
              }
              echo '<div class="addresses__list">';
          
              foreach ($addresses as $index => $address) {
                  $checked = ($index == 0) ? 'checked' : '';
                  echo '<div class="address__option">';
                  echo '<input type="radio" id="address_' . $address['AddressId'] . '" name="selected_address" value="' . $address['AddressId'] . '" class="address__radio" data-pincode="' . htmlspecialchars($address['PostalCode']) . '" ' . $checked . '>';
                  echo '<label for="address_' . $address['AddressId'] . '" class="address__label">';
                  echo '<p class="address__name">' . htmlspecialchars($address['ReceiverName']) . '</p>';
                  echo '<p class="address__line">' . htmlspecialchars($address['AddressLine1']) . ' ' . htmlspecialchars($address['AddressLine2']) . '</p>';
                  echo '<p class="address__city">' . htmlspecialchars($address['City']) . ', ' . htmlspecialchars($address['State']) . ' ' . htmlspecialchars($address['PostalCode']) . '</p>';
                  echo '<p class="address__country">' . htmlspecialchars($address['Country']) . '</p>';
                  echo '</label>';
                  echo '</div>';
              }
          
              echo '</div>';
            ?>
            </div>
          </div>

          <div class="checkout__group">
            <h3 class="section__title">Cart Totals</h3>

            <table class="order__table">
              <tr>
                <th colspan="2">Products</th>
                <th>Total</th>
              </tr>

              <?php
                if ($is_logged_in) {
                  list($checkoutTable, ) = displayCheckoutTable(true);
                  echo $checkoutTable;
                } else {
                  // We'll use JavaScript to get localStorage and potentially reload
                  echo '<tbody id="guest-checkout-items">';
                  echo '<tr><td colspan="6">Loading guest cart...</td></tr>';
                  echo '</tbody>';
                }
              ?>

              <tr>
                <td colspan="2"><span class="order__subtitle">SubTotal</span></td>
                <td><span class="table__price subtotal__price">₹<?php echo number_format($subtotal, 2); ?></span></td>
              </tr>

              <tr>
                <td colspan="2"><span class="order__subtitle">Shipping</span></td>
                <td>
                  <span class="table__price shipping__price">
                    <?php 
                    if ($subtotal < 150) {
                      echo '₹' . number_format($shippingCharge, 2);
                    } else {
                      echo 'Free Shipping';
                    }
                    ?>
                  </span>
                </td>
              </tr>

              <tr class="colored__total">
                <td colspan="2"><span class="order__subtitle">Total</span></td>
                <td><span class="order__grand-total">₹<?php echo number_format($subtotal + $shippingCharge, 2); ?></span></td>
              </tr>
            </table>

            <div class="payment__methods">
              <h3 class="checkout__title payment__title">Payment</h3>

              <div class="payment__option flex">
                <input type="radio" name="payment_method" value="online" class="payment__input" checked>
                <label for="" class="payment__label">Pay online</label>
              </div>

              <!-- <div class="payment__option flex">
                <input type="radio" name="payment_method" value="offline" class="payment__input">
                <label for="" class="payment__label">Pay on delivery</label>
              </div> -->
            </div>

            <form id="orderForm">
              <input type="hidden" name="action" value="placeOrder">
              <input type="hidden" name="shippingCharge" value="<?php echo $shippingCharge; ?>">
              <input type="hidden" name="totalAmount" value="<?php echo $subtotal + $shippingCharge; ?>">
  
              <!-- Hidden fields for Cart Items -->
              <?php
              if (!empty($cartItems)) {
                  foreach ($cartItems as $index => $item) {
                      echo '<input type="hidden" name="cartItems[' . $index . '][productId]" value="' . $item['productId'] . '">';
                      echo '<input type="hidden" name="cartItems[' . $index . '][customization]" value="' . $item['customization'] . '">';
                      echo '<input type="hidden" name="cartItems[' . $index . '][quantity]" value="' . $item['quantity'] . '">';
                      echo '<input type="hidden" name="cartItems[' . $index . '][price]" value="' . $item['price'] . '">';
                  }
              }
              ?>
  
              <!-- Hidden fields for Address ID and Payment Method -->
              <input type="hidden" name="addressId" id="addressId">
              <input type="hidden" name="paymentMethod" id="paymentMethod">

              <button type="submit" class="btn btn--md" id="placeOrderButton">Place Order</button>
            </form>
          </div>
        </div>
      </section>
    </main>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize UI elements
    const guestCheckoutItemsContainer = document.getElementById('guest-checkout-items');
    const cartSubtotalElement = document.querySelector('.subtotal__price');
    const shippingPriceElement = document.querySelector('.shipping__price');
    const grandTotalElement = document.querySelector('.order__grand-total');
    const placeOrderButton = document.querySelector('#placeOrderButton');
    const orderForm = document.querySelector('#orderForm');
    const successAnimation = document.getElementById('successAnimation');
    const addressRadios = document.querySelectorAll('input[name="selected_address"]');
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    
    // States and flags
    let isRequestInProgress = false;
    let selectedAddressId = null;
    let selectedPincode = '';
    let paymentMethod = 'online'; // Default
    const isLoggedIn = CartSystem.isLoggedIn;
    
    // Initialize the checkout page
    initCheckoutPage();
    
    /**
    * Initialize the checkout page based on user login status
    */
    function initCheckoutPage() {
      if (isLoggedIn) {
        setupLoggedInCheckout();
        setupBillingCollapse();
      } else {
        setupGuestCheckout();
      }
      setupCommonHandlers();
    }
    
    /**
    * Setup guest checkout flow
    */
    function setupGuestCheckout() {
      // Get the billing details container
      const billingGroup = document.querySelector('.checkout__group.checkout__address');
      if (!billingGroup) return;
      
      // Create guest checkout form
      const guestFormHTML = `
        <div class="guest-checkout-form">
          <div class="form__group">
            <label for="guest_name" class="form__label">Full Name *</label>
            <input type="text" id="guest_name" class="form__input" required>
          </div>

          <div class="form__group">
            <label for="guest_email" class="form__label">Email *</label>
            <input type="email" id="guest_email" class="form__input" required>
          </div>

          <div class="form__group">
            <label for="guest_phone" class="form__label">Phone *</label>
            <input type="tel" id="guest_phone" class="form__input" required>
          </div>

          <h4 class="section__subtitle">Shipping Address</h4>

          <div class="form__group">
            <label for="guest_address_line1" class="form__label">Address Line 1 *</label>
            <input type="text" id="guest_address_line1" class="form__input" required>
          </div>

          <div class="form__group">
            <label for="guest_address_line2" class="form__label">Address Line 2</label>
            <input type="text" id="guest_address_line2" class="form__input">
          </div>

          <div class="form__group grid-2">
            <div>
              <label for="guest_city" class="form__label">City *</label>
              <input type="text" id="guest_city" class="form__input" required>
            </div>

            <div>
              <label for="guest_state" class="form__label">State *</label>
              <input type="text" id="guest_state" class="form__input" required>
            </div>
          </div>

          <div class="form__group grid-2">
            <div>
              <label for="guest_pincode" class="form__label">Postal Code *</label>
              <input type="text" id="guest_pincode" class="form__input" required>
            </div>

            <div>
              <label for="guest_country" class="form__label">Country *</label>
              <input type="text" id="guest_country" class="form__input" value="India" required>
            </div>
          </div>
        </div>
      `;

      // Inject guest form into collapse content
      const collapseContent = billingGroup.querySelector('.checkout__collapse-content');
      collapseContent.innerHTML = guestFormHTML;

      // Enable billing collapse (mobile)
      setupBillingCollapse();

      // Pincode → shipping calculation
      const guestPincodeInput = document.getElementById('guest_pincode');
      guestPincodeInput.addEventListener('blur', function () {
        const pincode = this.value.trim();
        if (isValidPincode(pincode)) {
          updateCartTotalsWithPincode(pincode);
        }
      });

      // Load guest cart
      loadCartData();
    }
    
    /**
    * Setup logged-in user checkout flow
    */
    function setupLoggedInCheckout() {
      // Get selected address initially
      const selectedAddressElement = document.querySelector('input[name="selected_address"]:checked');
      if (selectedAddressElement) {
        selectedAddressId = selectedAddressElement.value;
        selectedPincode = selectedAddressElement.getAttribute('data-pincode');
      }
      
      // Setup address selection
      addressRadios.forEach(function(addressRadio) {
        addressRadio.addEventListener('change', function(event) {
          selectedAddressId = event.target.value;
          selectedPincode = event.target.getAttribute('data-pincode');
          
          if (isValidPincode(selectedPincode)) {
            updateCartTotalsWithPincode(selectedPincode);
          } else {
            showMessage('Please provide a valid pincode.', 'error');
          }
        });
      });
    }
    
    /**
    * Setup handlers common to both user types
    */
    function setupCommonHandlers() {
      // Setup payment method selection
      paymentMethodRadios.forEach(function(paymentRadio) {
        paymentRadio.addEventListener('change', function(event) {
          paymentMethod = event.target.value;
        });
      });
      
      // Initially get selected payment method
      const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
      if (selectedPaymentMethod) {
        paymentMethod = selectedPaymentMethod.value;
      }
      
      // Setup place order button
      placeOrderButton.addEventListener('click', function(event) {
        event.preventDefault();
        
        if (isRequestInProgress) return;
        
        placeOrder();
      });
    }
    
    /**
    * Load cart data (common for both logged in and guest)
    */
    function loadCartData() {
      if (!isLoggedIn) {
        const guestCart = JSON.parse(localStorage.getItem('guestCart') || '[]');
        
        if (guestCart.length === 0) {
          // Redirect to cart page if cart is empty
          window.location.href = 'cart';
          return;
        }
        
        // Fetch cart details from server
        fetch('actions.php?action=displayGuestCheckout', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'guestCart=' + encodeURIComponent(JSON.stringify(guestCart))
        })
        .then(response => response.text())
        .then(html => {
          guestCheckoutItemsContainer.innerHTML = html;
          
          // After loading the HTML, update cart totals using CartSystem
          updateCartTotals();
        })
        .catch(error => {
          console.error("Error during guest cart loading:", error);
          showMessage('There was an error loading your cart. Please try again.', 'error');
        });
      }
    }
    
    /**
    * Update cart totals using CartSystem
    */
    function updateCartTotals() {
      CartSystem.calculateTotals(selectedPincode)
        .then(data => {
          if (data.success) {
            updateCartTotalsDisplay(data.subtotal, data.shipping, data.total);
            
            // Update hidden form fields
            document.querySelector('input[name="shippingCharge"]').value = data.shipping;
            document.querySelector('input[name="totalAmount"]').value = data.total;
            
            // Add cart items to form if guest checkout
            if (!isLoggedIn && data.cartItems) {
              // Clear any existing cart item inputs
              const existingInputs = orderForm.querySelectorAll('input[name^="cartItems"]');
              existingInputs.forEach(input => input.remove());
              
              // Add new cart item inputs
              data.cartItems.forEach((item, index) => {
                Object.keys(item).forEach(key => {
                  const input = document.createElement('input');
                  input.type = 'hidden';
                  input.name = `cartItems[${index}][${key}]`;
                  input.value = item[key];
                  orderForm.appendChild(input);
                });
              });
            }
          } else {
            console.error("Error calculating totals:", data.message);
          }
        })
        .catch(error => {
          console.error("Error refreshing cart totals:", error);
        });
    }
    
    /**
    * Update cart totals with a specific pincode
    */
    function updateCartTotalsWithPincode(pincode) {
      if (isRequestInProgress) return;
      isRequestInProgress = true;
      
      CartSystem.calculateTotals(pincode)
        .then(data => {
          if (data.success) {
            updateCartTotalsDisplay(data.subtotal, data.shipping, data.total);
            
            // Update hidden form fields
            document.querySelector('input[name="shippingCharge"]').value = data.shipping;
            document.querySelector('input[name="totalAmount"]').value = data.total;
          } else {
            showMessage(data.message || 'An error occurred while calculating shipping.', 'error');
          }
        })
        .catch(error => {
          console.error("Error occurred while recalculating shipping charge:", error);
          showMessage('An error occurred while recalculating the shipping charge.', 'error');
        })
        .finally(() => {
          isRequestInProgress = false;
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
      if (cartSubtotalElement) {
        cartSubtotalElement.textContent = '₹' + parseFloat(subtotal).toFixed(2);
      }
      
      if (shippingPriceElement) {
        if (parseFloat(subtotal) >= 150) {
          shippingPriceElement.textContent = 'Free Shipping';
        } else {
          shippingPriceElement.textContent = '₹' + parseFloat(shipping).toFixed(2);
        }
      }
      
      if (grandTotalElement) {
        grandTotalElement.textContent = '₹' + parseFloat(total).toFixed(2);
      }
    }
    
    /**
    * Place order handler - unified for both guest and logged-in users
    */
    function placeOrder() {
      if (isLoggedIn) {
        if (!selectedAddressId) {
          showMessage("Please select an address.", 'error');
          return;
        }
        
        // Set form values
        document.querySelector('#addressId').value = selectedAddressId;
        document.querySelector('#paymentMethod').value = paymentMethod;
        
        processPayment();
      } else {
        // Validate guest form
        if (!validateGuestForm()) return;
        
        // Create form data with guest details
        const formData = createGuestFormData();
        
        // Process payment with guest form data
        processPayment(formData);
      }
    }
    
    /**
    * Validate guest checkout form
    * @returns {boolean} True if valid, false otherwise
    */
    function validateGuestForm() {
      const guestName = document.getElementById('guest_name').value.trim();
      const guestEmail = document.getElementById('guest_email').value.trim();
      const guestPhone = document.getElementById('guest_phone').value.trim();
      const guestAddressLine1 = document.getElementById('guest_address_line1').value.trim();
      const guestCity = document.getElementById('guest_city').value.trim();
      const guestState = document.getElementById('guest_state').value.trim();
      const guestPincode = document.getElementById('guest_pincode').value.trim();
      const guestCountry = document.getElementById('guest_country').value.trim();
      
      if (!guestName || !guestEmail || !guestPhone || !guestAddressLine1 || 
          !guestCity || !guestState || !guestPincode || !guestCountry) {
        showMessage("Please fill in all required fields.", 'error');
        return false;
      }
      
      if (!isValidEmail(guestEmail)) {
        showMessage("Please enter a valid email address.", 'error');
        return false;
      }
      
      if (!isValidPincode(guestPincode)) {
        showMessage("Please enter a valid 6-digit pincode.", 'error');
        return false;
      }
      
      return true;
    }
    
    /**
    * Create form data for guest checkout
    * @returns {FormData} Form data with guest details
    */
    function createGuestFormData() {
      const formData = new FormData(orderForm);
      formData.append('isGuest', 'true');
      formData.append('guestName', document.getElementById('guest_name').value.trim());
      formData.append('guestEmail', document.getElementById('guest_email').value.trim());
      formData.append('guestPhone', document.getElementById('guest_phone').value.trim());
      formData.append('guestAddressLine1', document.getElementById('guest_address_line1').value.trim());
      formData.append('guestAddressLine2', document.getElementById('guest_address_line2').value.trim());
      formData.append('guestCity', document.getElementById('guest_city').value.trim());
      formData.append('guestState', document.getElementById('guest_state').value.trim());
      formData.append('guestPincode', document.getElementById('guest_pincode').value.trim());
      formData.append('guestCountry', document.getElementById('guest_country').value.trim());
      formData.append('paymentMethod', paymentMethod);

      // Explicitly add cart items for guest checkout
      const guestCart = JSON.parse(localStorage.getItem('guestCart') || '[]');
      guestCart.forEach((item, index) => {
          formData.append(`cartItems[${index}][productId]`, item.productId);
          formData.append(`cartItems[${index}][customization]`, item.customization || '');
          formData.append(`cartItems[${index}][price]`, item.price);
          formData.append(`cartItems[${index}][quantity]`, item.quantity);
      });
      
      return formData;
    }
    
    /**
    * Process payment based on selected method
    * 
    * @param {FormData} customFormData - Optional form data for guest checkout
    */
    function processPayment(customFormData = null) {
      isRequestInProgress = true;
      
      // If paying online
      if (paymentMethod === 'online') {
        // Get customer details based on user type
        let customerName, customerPhone, formToUse;
        
        if (isLoggedIn) {
          // Get from hidden fields or data attributes for logged-in users
          customerName = document.querySelector('[data-customer-name]')?.getAttribute('data-customer-name') || '';
          customerPhone = document.querySelector('[data-customer-phone]')?.getAttribute('data-customer-phone') || '';
          formToUse = new FormData(orderForm);
        } else {
          // Get from guest form
          customerName = document.getElementById('guest_name').value.trim();
          customerPhone = document.getElementById('guest_phone').value.trim();
          formToUse = customFormData;
        }
        
        const planPrice = document.querySelector('input[name="totalAmount"]').value;
        
        // Request to initiate online payment
        fetch('<?php echo $_ENV['BIBLOPHILE_API_URL']; ?>payments/request', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            customerName,
            customerPhone,
            amount: planPrice
          })
        })
        .then(response => response.json())
        .then(data => {
          isRequestInProgress = false;
          
          if (data.link_url) {
            // Open payment window
            window.open(data.link_url, '_blank');
            
            // Start polling payment status
            pollPaymentStatus(
              data.link_id, 
              customerPhone, 
              planPrice, 
              isLoggedIn ? selectedAddressId : null,
              paymentMethod,
              formToUse
            );
          } else {
            showMessage("Payment link creation failed. Please try again.", 'error');
          }
        })
        .catch(error => {
          console.error("Error occurred during payment request:", error);
          isRequestInProgress = false;
          showMessage("Payment processing failed. Please try again.", 'error');
        });
      } else {
        // Cash on delivery - submit directly
        submitOrder(customFormData);
      }
    }
    
    /**
    * Poll payment status until completed
    */
    function pollPaymentStatus(linkId, customerPhone, amount, addressId, paymentMethod, formData) {
      const pollInterval = setInterval(function() {
        const accessToken = '<?php echo $accessToken ?? null; ?>';
        const headers = {
          'Content-Type': 'application/json'
        };
        if (accessToken) {
          headers['Authorization'] = 'Bearer ' + accessToken;
        }

        fetch('<?php echo $_ENV['BIBLOPHILE_API_URL']; ?>payments/success?linkId=' + linkId, {
          method: 'POST',
          headers,
          body: JSON.stringify({
            customerPhone,
            amount: amount
          })
        })
        .then(response => response.json())
        .then(statusResponse => {
          console.log("Payment poll response:", statusResponse);
          if (statusResponse.status === "success. You can close this window.") {
            clearInterval(pollInterval);
            submitOrder(formData);
          }
        })
        .catch(error => {
          console.error("Error occurred while checking payment status:", error);
          // Don't clear interval, keep trying
        });
      }, 5000);
    }
    
    /**
    * Submit the order to the server
    * 
    * @param {FormData} customFormData - Optional form data for guest checkout
    */
    function submitOrder(customFormData = null) {
      const formToSubmit = customFormData || new FormData(orderForm);
      
      fetch('actions.php?action=placeOrder', {
        method: 'POST',
        body: formToSubmit
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Track purchase for analytics
          if (typeof fbq === 'function') {
            fbq('track', 'Purchase', {
              currency: "INR", 
              value: formToSubmit.get('totalAmount')
            });
          }
          
          // Clear guest cart if applicable
          if (!isLoggedIn) {
            localStorage.removeItem('guestCart');
          }
          
          // Show success animation
          displaySuccessAnimation(function() {
            // window.location.href = 'order-confirmation?id=' + data.orderId;
            window.location.reload();
          });
        } else {
          showMessage(data.message || 'An error occurred while placing the order.', 'error');
        }
        isRequestInProgress = false;
      })
      .catch(error => {
        console.error("Error occurred during order placement:", error);
        showMessage('An error occurred while placing the order. Please try again.', 'error');
        isRequestInProgress = false;
      });
    }
    
    /**
    * Display success animation and execute callback
    */
    function displaySuccessAnimation(callback) {
      successAnimation.style.display = 'block';
      
      setTimeout(function() {
        if (callback && typeof callback === 'function') {
          callback();
        }
      }, 3000);
    }
    
    /**
    * Show message to user
    * @param {string} message - Message content
    * @param {string} type - Message type (error, success)
    */
    function showMessage(message, type = 'info') {
      if (type === 'error') {
        alert(message);
      } else {
        console.log(message);
      }
    }
    
    /**
    * Validate if pincode is valid
    */
    function isValidPincode(pincode) {
      const pincodeRegex = /^[0-9]{6}$/;
      return pincodeRegex.test(pincode);
    }
    
    /**
    * Validate if email is valid
    */
    function isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }
  });

  // ===== Mobile collapsible billing section =====
function setupBillingCollapse() {
  const addressSection = document.querySelector('.checkout__address');
  const toggleBtn = document.querySelector('.checkout__collapse-toggle');
  const icon = document.querySelector('.collapse__icon');

  if (!addressSection || !toggleBtn) return;

  const isMobile = window.innerWidth <= 768;

  // Remove previous handler if any
  toggleBtn.onclick = null;

  if (isMobile) {
    addressSection.classList.remove('active');
    toggleBtn.setAttribute('aria-expanded', 'false');
    icon.textContent = '+';
  } else {
    addressSection.classList.add('active');
    toggleBtn.setAttribute('aria-expanded', 'true');
    icon.textContent = '−';
  }

  toggleBtn.onclick = () => {
    const expanded = addressSection.classList.toggle('active');
    toggleBtn.setAttribute('aria-expanded', expanded);
    icon.textContent = expanded ? '−' : '+';
  };
}
</script>