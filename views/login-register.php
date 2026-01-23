    <link rel="stylesheet" href="assets/css/login-register.css" />
    <!--=============== MAIN ===============-->
    <main class="main">
      <!--=============== BREADCRUMB ===============-->
      <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
          <li><a href="/" class="breadcrumb__link">Home</a></li>
          <li><span class="breadcrumb__link">></span></li>
          <li><span class="breadcrumb__link">Login / Register</span></li>
        </ul>
      </section>

      <!--=============== LOGIN-REGISTER ===============-->
      <section class="login-register section--lg">
        <div class="login-register__container container grid">
          <div class="login">
            <h3 class="section__title">Login</h3>
            <div id="loginMessage" class="form__message"></div>
            <form id="loginForm" method="POST" class="form grid">
              <input
                id="loginEmail"
                type="email"
                name="email"
                placeholder="Your Email" 
                class="form__input"
                required
              />

              <input 
                type="password"
                name="password"
                placeholder="Your Password" 
                class="form__input"
                required
              />

              <a href="#" id="forgotPasswordLink" class="forgot-password-link">Forgot your password?</a>

              <div class="form__btn">
                <button type="submit" class="btn">Login</button>
              </div>
            </form>
          </div>

          <div class="register">
            <h3 class="section__title">Create an account</h3>
            <div id="signupMessage" class="form__message"></div>
            <form id="signupForm" method="POST" class="form grid">
              <input 
                type="text"
                name="name"
                placeholder="Full Name" 
                class="form__input"
                required
              />

              <input 
                type="text"
                name="userName"
                placeholder="Username" 
                class="form__input"
                required
              />

              <input 
                type="email"
                name="email"
                placeholder="Your Email" 
                class="form__input"
                required
              />

              <input 
                type="tel"
                name="phone"
                placeholder="Your Phone number" 
                class="form__input"
                pattern="\d{10}"
                required
              />

              <input 
                type="password"
                name="password"
                placeholder="Your Password" 
                class="form__input"
                required
              />

              <input 
                type="password"
                name="confirm_password"
                placeholder="Confirm Password" 
                class="form__input"
                required
              />

              <h2>How did you find us?</h2>
              <select name="referral" class="form__input" required>
                  <option value={null}>Select an option</option>
                  <option value="Social Media">Social Media</option>
                  <option value="Word of Mouth">Friends/Word of Mouth</option>
                  <option value="Online Ads">Online Ads</option>
                  <option value="App Store">App Store</option>
                  <option value="Forums or Online Communities">Influencer/Online Communities</option>
                  <option value="Print Media">Print Media</option>
                  <option value="Other">Other</option>
              </select>

              <div class="form__btn">
                <button type="submit" class="btn">Register</button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>

    <script>
      /*=============== Login Submission ===============*/
document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const email = this.email.value;
    const password = this.password.value;

    //Get the return URL from the query parameter or use the referrer
    const urlParams = new URLSearchParams(window.location.search);
    const returnUrl = urlParams.get('returnUrl') || document.referrer || '/';

    const data = { email, pass: password };

    const endpoint = '<?php echo $_ENV["BIBLOPHILE_API_URL"]; ?>' + 'auth/login';
  
    fetch(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
      headers: {
        'Content-Type': 'application/json',
      }
    })
    .then(response => response.json())
    .then(data => {
      const messageContainer = document.getElementById('loginMessage');
      messageContainer.style.marginBottom = '5px';
      if (data.message === 1) {
        const accessToken = data.accessToken;
        const refreshToken = data.refreshToken;
        const cookieValue = btoa(JSON.stringify({ accessToken, refreshToken }));

        fetch('actions.php?action=storeSessionCookie', {
          method: 'POST',
          body: JSON.stringify({
            cookieValue
          }),
          headers: {
            'Content-Type': 'application/json',
          }
        })
        .then(response => response.json())
        .then(cookieData => {
          if (cookieData.success) {
            console.log('Access token successfully stored on server:', data.accessToken);

            // --- Merge Guest Cart Logic Here ---
            const guestCart = localStorage.getItem('guestCart');
            if (guestCart) {
            fetch('actions.php?action=mergeGuestCart', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json'
            },
            body: guestCart
            })
            .then(mergeResponse => mergeResponse.json())
            .then(mergeData => {
              console.log('Guest cart merge response:', mergeData);
              localStorage.removeItem('guestCart');
              updateCartCount('./actions.php?action=getCartCount');
              window.location.href = returnUrl;
            })
            .catch(error => {
              console.error('Error merging guest cart:', error);
              window.location.href = returnUrl;
              });
            }
            // --- End Merge Guest Cart Logic ---
            // Optionally, redirect or show a success message
          } else {
          console.log('Error storing session cookie on server:', cookieData.message);
          }
        })
        .catch(error => {
          console.error('Error setting session cookie on server:', error);
        });
      } else {
        console.log('Login failed:', data.message);
        //show error message
        messageContainer.textContent = 'Login failed: ' + data.message;
        messageContainer.style.color = 'red'; 
      }
    })
    .catch(error => {
      console.error('Error:', error);
    });
  });

  /*=============== Signup Submission ===============*/
document.getElementById('signupForm').addEventListener('submit', function(event) {
    event.preventDefault();
  
    const name = this.name.value;
    const userName = this.userName.value;
    const email = this.email.value;
    const phone = this.phone.value;
    const password = this.password.value;
    const signupPassCnf = this.confirm_password.value;
    const referral = this.referral.value;

    const data = { name, userName, email, phone, password, signupPassCnf, source: referral };

    const endpoint = '<?php echo $_ENV["BIBLOPHILE_API_URL"]; ?>' + 'auth/signup';
  
    fetch(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
      const messageContainer = document.getElementById('signupMessage');
      messageContainer.style.marginBottom = '5px';
      if (data.message === 1) {
        console.log('signup success:');
        //show a success message
        messageContainer.textContent = 'Signup successful! Welcome aboard.';
        messageContainer.style.color = 'green'; 
      } else {
        console.log('Signup failed:', data.message);
        //show error message
        messageContainer.textContent = 'Signup failed: ' + data.message;
        messageContainer.style.color = 'red'; 
      }
    })
    .catch(error => {
      console.error('Error:', error);
      //show error message
      messageContainer.textContent = 'An error occurred: ' + error.message;
      messageContainer.style.color = 'red';
    });
  });

  /*=============== Forgot Password Handler ===============*/
  document.getElementById('forgotPasswordLink').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent default link action
  
    const email = document.getElementById('loginEmail').value; // Get the email from the login form

    if (email) {
      fetch('<?php echo $_ENV["BIBLOPHILE_API_URL"]; ?>' + 'auth/forgot-password', {
        method: 'POST',
        body: JSON.stringify({ email: email }),
        headers: {
          'Content-Type': 'application/json',
        },
      })
      .then(response => response.json())
      .then(data => {
        const messageContainer = document.getElementById('loginMessage');
        if (data.message === "Reset link has been sent to your email") {
          messageContainer.textContent = "A password reset link has been sent to your email.";
          messageContainer.style.color = 'green';
        } else {
          messageContainer.textContent = data.message;
          messageContainer.style.color = 'red';
        }
      })
      .catch(error => {
        const messageContainer = document.getElementById('loginMessage');
        messageContainer.textContent = "An error occurred. Please try again.";
        messageContainer.style.color = 'red';
        console.error('Error:', error);
      });
    } else {
      const messageContainer = document.getElementById('loginMessage');
      messageContainer.textContent = "Please enter a valid email address.";
      messageContainer.style.color = 'red';
    }
  });
    </script>