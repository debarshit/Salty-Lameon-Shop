// This script provides a unified API for cart operations regardless of user login status

// Initialize the cart system
const CartSystem = {
    // Get login state from global variable set in PHP
    isLoggedIn: typeof USER_LOGGED_IN !== 'undefined' ? USER_LOGGED_IN : false,
    
    /**
     * Add an item to the cart
     * 
     * @param {number} productId - Product ID
     * @param {number} quantity - Quantity to add
     * * @param {number} price - product price at the time of adding to cart
     * @param {string} customization - Optional customization
     * @returns {Promise} - Promise resolving to success/failure
     */
    addItem: function(productId, quantity, price, customization = null) {
        if (this.isLoggedIn) {
            // Logged-in user - call server
            return fetch('actions.php?action=addToCart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'productId=' + encodeURIComponent(productId) + 
                      '&quantity=' + encodeURIComponent(quantity) + 
                      (customization ? '&customization=' + encodeURIComponent(customization) : '')
            })
            .then(response => response.json());
        } else {
            // Guest user - use localStorage
            return new Promise((resolve) => {
                let guestCart = JSON.parse(localStorage.getItem('guestCart') || '[]');
                
                // Check if product already exists in cart
                const existingItemIndex = guestCart.findIndex(item => 
                    item.productId === productId && 
                    (item.customization || 'null') === (customization || 'null')
                );
                
                if (existingItemIndex > -1) {
                    // Update quantity if item exists
                    guestCart[existingItemIndex].quantity += parseInt(quantity);
                } else {
                    // Add new item
                    guestCart.push({
                        productId: productId,
                        quantity: parseInt(quantity),
                        price: price,
                        customization: customization || 'null'
                    });
                }
                
                localStorage.setItem('guestCart', JSON.stringify(guestCart));
                resolve({ success: true });
            });
        }
    },
    
    /**
     * Update quantity of a cart item
     * 
     * @param {number} productId - Product ID
     * @param {number} quantity - New quantity
     * @param {string} customization - Optional customization
     * @returns {Promise} - Promise resolving to success/failure
     */
    updateQuantity: function(productId, quantity, customization = null) {
        if (this.isLoggedIn) {
            // Logged-in user - call server
            return fetch('actions.php?action=updateQuantity', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'productId=' + encodeURIComponent(productId) + 
                      '&quantity=' + encodeURIComponent(quantity) + 
                      (customization ? '&customization=' + encodeURIComponent(customization) : '')
            })
            .then(response => response.json());
        } else {
            // Guest user - use localStorage
            return new Promise((resolve) => {
                let guestCart = JSON.parse(localStorage.getItem('guestCart') || '[]');
                
                const itemIndex = guestCart.findIndex(item => 
                    item.productId === productId && 
                    (item.customization || 'null') === (customization || 'null')
                );
                
                if (itemIndex > -1) {
                    if (parseInt(quantity) <= 0) {
                        // Remove item if quantity is zero or negative
                        guestCart.splice(itemIndex, 1);
                    } else {
                        // Update quantity
                        guestCart[itemIndex].quantity = parseInt(quantity);
                    }
                    
                    localStorage.setItem('guestCart', JSON.stringify(guestCart));
                    resolve({ success: true });
                } else {
                    resolve({ success: false, message: 'Item not found in cart' });
                }
            });
        }
    },
    
    /**
     * Remove an item from the cart
     * 
     * @param {number} productId - Product ID
     * @param {string} customization - Optional customization
     * @returns {Promise} - Promise resolving to success/failure
     */
    removeItem: function(productId, customization = null) {
        if (this.isLoggedIn) {
            // Logged-in user - call server
            return fetch('actions.php?action=removeFromCart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'productId=' + encodeURIComponent(productId) + 
                      (customization ? '&customization=' + encodeURIComponent(customization) : '')
            })
            .then(response => response.json());
        } else {
            // Guest user - use localStorage
            return new Promise((resolve) => {
                let guestCart = JSON.parse(localStorage.getItem('guestCart') || '[]');
                
                const updatedCart = guestCart.filter(item => 
                    !(item.productId === productId && 
                      (item.customization || 'null') === (customization || 'null'))
                );
                
                localStorage.setItem('guestCart', JSON.stringify(updatedCart));
                resolve({ success: true });
            });
        }
    },
    
    /**
     * Calculate cart totals
     * 
     * @param {string} pincode - Optional pincode for shipping calculation
     * @returns {Promise} - Promise resolving to cart totals
     */
    calculateTotals: function(pincode = null) {
        if (this.isLoggedIn) {
            // Logged-in user - call server
            return fetch('actions.php?action=getCartTotals' + (pincode ? '&pincode=' + encodeURIComponent(pincode) : ''))
            .then(response => response.json());
        } else {
            // Guest user - calculate on server using localStorage data
            return new Promise((resolve, reject) => {
                const guestCart = localStorage.getItem('guestCart');
                
                if (!guestCart) {
                    resolve({
                        success: true,
                        subtotal: 0,
                        shipping: 0,
                        total: 0
                    });
                    return;
                }
                
                fetch('actions.php?action=calculateGuestCartTotals', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'guestCart=' + encodeURIComponent(guestCart) + 
                          (pincode ? '&pincode=' + encodeURIComponent(pincode) : '')
                })
                .then(response => response.json())
                .then(resolve)
                .catch(reject);
            });
        }
    },
    
    /**
     * Get the number of items in cart
     * 
     * @returns {number} - Number of items
     */
    getItemCount: function() {
        if (this.isLoggedIn) {
            // This would need to be implemented server-side and exposed via an API
            // For now, return the cart-count data attribute from a header element
            const cartCountElement = document.querySelector('[data-cart-count]');
            return cartCountElement ? parseInt(cartCountElement.getAttribute('data-cart-count')) : 0;
        } else {
            const guestCart = JSON.parse(localStorage.getItem('guestCart') || '[]');
            return guestCart.reduce((total, item) => total + parseInt(item.quantity), 0);
        }
    }
};

// Add a function to update cart count in the header
function updateCartCount() {
    const count = CartSystem.getItemCount();
    const cartCountElements = document.querySelectorAll('.cart-count');
    
    cartCountElements.forEach(element => {
        element.textContent = count;
        
        // Toggle visibility based on count
        if (count > 0) {
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    });
}

// Make updateCartCount available globally
window.updateCartCountInHeader = updateCartCount;