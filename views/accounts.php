    <?php
      $addresses = fetchUserAddresses();
      $userDetails = fetchUserDetails();
    ?>
    <link rel="stylesheet" href="assets/css/accounts.css" />
    <!--=============== MAIN ===============-->
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a href="/" class="breadcrumb__link">Home</a></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Account</span></li>
        </ul>
      </section>

      <!--=============== ACCOUNTS ===============-->
      <section class="accounts section--lg">
        <div class="accounts__container container grid">
          <div class="account__tabs">
            <p class="account__tab active-tab" data-target="#dashboard">
              <i class="fi fi-rs-settings-sliders"></i> Dashboard
            </p>

            <p class="account__tab" data-target="#orders">
              <i class="fi fi-rs-shopping-bag"></i> Orders
            </p>

            <p class="account__tab" data-target="#update-profile">
              <i class="fi fi-rs-user"></i> Update Profile
            </p>

            <p class="account__tab" data-target="#address">
              <i class="fi fi-rs-marker"></i> My Addresses
            </p>

            <p class="account__tab" data-target="#change-password">
              <i class="fi fi-rs-user"></i> Change Password
            </p>

            <p class="account__tab" id="logoutButton">
              <i class="fi fi-rs-exit"></i> Logout
            </p>
          </div>

          <div class="tabs__content">
            <div class="tab__content active-tab" content id="dashboard">
              <h3 class="tab__header">Hello <?php echo $userDetails['name']; ?></h3>

              <div class="tab__body">
                <p class="tab__description">
                  From your account dashboard, you can easily check & 
                  view your recent orders, manage your shipping and billing Address 
                  and edit your password and account details.
                </p>
              </div>
            </div>

            <div class="tab__content" content id="orders">
              <h3 class="tab__header">Your Orders</h3>

              <div class="tab__body">
                <table class="placed__order-table">
                  <tr>
                    <th>Orders</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Actions</th>
                  </tr>

                  <?php
                    $orders = displayUserOrders();
                    $counter = 1;

                    foreach ($orders as $order) {
                        $orderId = $order['OrderId'];
                        $orderDate = date('F j, Y', strtotime($order['OrderDate']));
                        $status = $order['OrderStatus'];
                        $totalAmount = '₹' . number_format($order['TotalAmount'], 2);
                        ?>

                        <tr>
                            <td>#<?php echo $counter; ?></td>
                            <td><?php echo $orderDate; ?></td>
                            <td><?php echo $status; ?></td>
                            <td><?php echo $totalAmount; ?></td>
                            <td><a href="order-details?orderId=<?php echo $orderId; ?>" class="view__order">View</a></td>
                        </tr>

                        <?php
                         $counter++;
                    }

                    if (empty($orders)) {
                        echo '<tr><td colspan="5">No orders placed yet.</td></tr>';
                    }
                  ?>
                </table>
              </div>
            </div>

            <div class="tab__content" content id="update-profile">
              <h3 class="tab__header">Update Profile</h3>

              <div class="tab__body">
                <form id="updateProfileForm" action="" class="form grid">
                  <input 
                    type="text" 
                    placeholder="Username" 
                    class="form__input"
                    name="UserName"
                  />

                  <div class="form__btn">
                    <button type="submit" class="btn btn--md">Save</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="tab__content" content id="address">
              <h3 class="tab__header">Shipping Addresses</h3>

              <div class="tab__body">
              <button id="addAddressBtn" class="btn">Add New Address</button>

                <?php
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
                ?>
              </div>
            </div>

            <!-- Modal for adding/editing address -->
            <div id="addressModal" class="modal">
              <div class="modal-content">
                <span class="close">&times;</span>
                <h4 id="modalTitle">Add New Address</h4>
                
                <form id="addressForm" class="form grid">
                  <input type="hidden" id="addressId" name="addressId">
                  
                  <label for="receiver">Receiver Name:</label>
                  <input type="text" id="receiver" name="receiver" required>
                  
                  <label for="addressLine1">Address Line 1:</label>
                  <input type="text" id="addressLine1" name="addressLine1" required>
                  
                  <label for="addressLine2">Address Line 2:</label>
                  <input type="text" id="addressLine2" name="addressLine2">
                  
                  <label for="city">City:</label>
                  <input type="text" id="city" name="city" required>
                  
                  <label for="state">State:</label>
                  <input type="text" id="state" name="state" required>
                  
                  <label for="postalCode">Postal Code:</label>
                  <input type="text" id="postalCode" name="postalCode" required>
                  
                  <label for="country">Country:</label>
                  <input type="text" id="country" name="country" required>
                  
                  <button type="submit" class="btn" id="saveAddressBtn">Save Address</button>
                </form>
              </div>
            </div>

            <div class="tab__content" content id="change-password">
              <h3 class="tab__header">Change Password</h3>

              <div class="tab__body">
                <form id="changePasswordForm" action="" class="form grid">

                  <input 
                    type="password" 
                    placeholder="New Password" 
                    class="form__input"
                    name="UserPassword"
                  />

                  <input 
                    type="password" 
                    placeholder="Confirm Password" 
                    class="form__input"
                    name="ConfirmPassword"
                  />

                  <div class="form__btn">
                    <button type="submit" class="btn btn--md">Save</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <script>
      document.getElementById('logoutButton').addEventListener('click', function() {
        fetch('actions.php?action=deleteSessionCookie', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {

                alert(data.message || 'Logged out successfully.');
                window.location.href = '/';
            } else {
                alert(data.message || 'Logout failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
      });

      document.addEventListener('DOMContentLoaded', function() {
        attachEditButtonListeners();
        attachDeleteButtonListeners();
        // Check if the URL contains the 'address' hash
        if (window.location.hash === '#address') {
            // Activate the 'My Addresses' tab
            document.querySelectorAll('.account__tab').forEach(tab => {
                tab.classList.remove('active-tab');
            });
            document.querySelector('.account__tab[data-target="#address"]').classList.add('active-tab');

            // Show the 'Address' tab content
            document.querySelectorAll('.tab__content').forEach(content => {
                content.classList.remove('active-tab');
            });
            document.querySelector('#address').classList.add('active-tab');

            // Automatically open the address modal
            document.getElementById('addAddressBtn').click();
        }
      });

      document.getElementById('addAddressBtn').addEventListener('click', function() {
      document.getElementById('addressModal').style.display = 'block';
      document.getElementById('modalTitle').innerText = 'Add New Address';
      document.getElementById('addressForm').reset();
      document.getElementById('addressId').value = '';
      });

      // Close modal when clicking the close button
      document.querySelector('.close').addEventListener('click', function() {
          document.getElementById('addressModal').style.display = 'none';
      });

      //update username
      document.getElementById('updateProfileForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        const UserName = formData.get('UserName');
        
        const data = {
          property: 'UserName',
          value: UserName
        };

        fetch('actions.php?action=updateProfile', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
          alert(result.message);
        })
        .catch(error => {
          console.error('Error:', error);
        });
      });

      //update password
      document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        const UserPassword = formData.get('UserPassword');
        const ConfirmPassword = formData.get('ConfirmPassword');

        if (UserPassword !== ConfirmPassword) {
          alert('New password and confirm password do not match!');
          return;
        }

        const data = {
          property: 'UserPassword',
          value: UserPassword 
        };

        fetch('actions.php?action=updateProfile', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
          alert(result.message);
        })
        .catch(error => {
          console.error('Error:', error);
        });
      });

      document.getElementById('addressForm').addEventListener('submit', function(event) {
        event.preventDefault();

        // Disable the save button
        const saveButton = document.getElementById('saveAddressBtn');
        const originalButtonText = saveButton.textContent;
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
        
        const formData = new FormData(this);
        
        fetch('actions.php?action=updateUserAddress', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.text();
        })
        .then(data => {
          if (data.includes("successfully")) {
            document.getElementById('addressModal').style.display = 'none';
            
            refreshAddressList();
            
            if (window.location.hash === '#address') {
              setTimeout(function() {
                window.location.href = 'checkout';
              }, 1000);
            }
          } else {
            alert('Error saving address. Please try again.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred. Please try again.');
        })
        .finally(() => {
          saveButton.disabled = false;
          saveButton.textContent = originalButtonText;
        });
      });

      // Add this function to refresh the address list
      function refreshAddressList() {

        const addressContainer = document.querySelector('#address .tab__body');
        addressContainer.classList.add('position-relative');
        
        // Create and add spinner overlay
        const spinnerOverlay = document.createElement('div');
        spinnerOverlay.className = 'spinner-overlay';
        spinnerOverlay.innerHTML = '<div class="spinner"></div>';
        addressContainer.appendChild(spinnerOverlay);
        spinnerOverlay.style.display = 'flex';
        spinnerOverlay.style.zIndex = '1000';
  
        fetch('actions.php?action=fetchUserAddresses', {
          method: 'GET'
        })
        .then(response => response.text())
        .then(html => {
          spinnerOverlay.remove();
          // Replace the current address list with the updated one
          const addressContainer = document.querySelector('#address .tab__body');
          const addButton = document.getElementById('addAddressBtn');
          
          // Keep the "Add New Address" button and update the rest
          const updatedHtml = '<button id="addAddressBtn" class="btn">Add New Address</button>' + html;
          addressContainer.innerHTML = updatedHtml;
          
          // Reattach event listeners to the new buttons
          attachEditButtonListeners();
          attachDeleteButtonListeners();
          
          // Reattach event listener to the add button
          document.getElementById('addAddressBtn').addEventListener('click', function() {
            document.getElementById('addressModal').style.display = 'block';
            document.getElementById('modalTitle').innerText = 'Add New Address';
            document.getElementById('addressForm').reset();
            document.getElementById('addressId').value = '';
          });
        })
        .catch(error => {
          console.error('Error refreshing addresses:', error);
        });
      }

      // Function to attach event listeners to edit buttons to open edit modal
      function attachEditButtonListeners() {
        document.querySelectorAll('.edit').forEach(function(button) {
          button.addEventListener('click', function() {
            const addressData = this.dataset;
            
            document.getElementById('addressModal').style.display = 'block';
            document.getElementById('modalTitle').innerText = 'Edit Address';
            
            // Populate form fields with existing data
            document.getElementById('addressId').value = addressData.id;
            document.getElementById('receiver').value = addressData.receiver;
            document.getElementById('addressLine1').value = addressData.line1;
            document.getElementById('addressLine2').value = addressData.line2;
            document.getElementById('city').value = addressData.city;
            document.getElementById('state').value = addressData.state;
            document.getElementById('postalCode').value = addressData.postal;
            document.getElementById('country').value = addressData.country;
          });
        });
      }

      function attachDeleteButtonListeners() {
      document.querySelectorAll('.delete').forEach(function(button) {
        button.addEventListener('click', function(event) {
          event.preventDefault();
          const addressId = this.dataset.id;
          
          if (confirm("Are you sure you want to delete this address?")) {

            const addressContainer = document.querySelector('#address .tab__body');
            addressContainer.classList.add('position-relative');
            
            const spinnerOverlay = document.createElement('div');
            spinnerOverlay.className = 'spinner-overlay';
            spinnerOverlay.innerHTML = '<div class="spinner"></div>';
            addressContainer.appendChild(spinnerOverlay);
            spinnerOverlay.style.display = 'flex';
            spinnerOverlay.style.zIndex = '1000';
            
            fetch('actions.php?action=deleteUserAddress', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({ addressId: addressId })
            })
            .then(response => response.json())
            .then(result => {
              if (result.success) {
                spinnerOverlay.remove();
                refreshAddressList();
              } else {
                alert(result.message || 'Failed to delete address. Please try again.');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert('An error occurred. Please try again.');
              spinnerOverlay.remove();
            });
          }
        });
      });
    }

      // Add click-outside functionality to close the modal
      window.addEventListener('click', function(event) {
        const modal = document.getElementById('addressModal');
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      });

    </script>
