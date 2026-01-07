<link rel="stylesheet" href="assets/css/footer.css" />
<!--=============== NEWSLETTER ===============-->
    <section class="newsletter section home__newsletter">
      <div class="newsletter__container container grid">
        <h3 class="newsletter__title flex">
          <img 
            src="assets/img/icon-email.svg" 
            alt="" 
            class="newsletter__icon"
          />
          Sign up to Newsletter
        </h3>

        <p class="newsletter__description">
          ...and receive exclusive discount offers.
        </p>

        <form class="newsletter__form" id="newsletter-form">
          <button 
            type="submit" 
            class="newsletter__btn"
          >
            Subscribe
          </button>
        </form>
      </div>
    </section>
    
    <!--=============== FOOTER ===============-->
    <footer class="footer container">
      <div class="footer__bottom">
        <p class="copyright">&copy; 2025 Biblophile. All rights reserved</p>
        <div class="footer__social-links flex">
          <a href="https://www.facebook.com/profile.php?id=61559661155321">
            <img 
              src="assets/img/icon-facebook.svg" 
              alt=""
              class="footer__social-icon"
              />
          </a>

          <a href="https://x.com/__biblophile__">
            <img 
              src="assets/img/icon-twitter.svg" 
              alt=""
              class="footer__social-icon"
              />
          </a>

          <a href="https://www.instagram.com/rashmii.ramesh/">
            <img 
              src="assets/img/icon-instagram.svg" 
              alt=""
              class="footer__social-icon"
              />
          </a>

          <a href="https://in.pinterest.com/rashmiramesha/">
            <img 
              src="assets/img/icon-pinterest.svg" 
              alt=""
              class="footer__social-icon"
              />
          </a>

        </div>
        <span class="designer">Shop by Biblophile</span>
      </div>
    </footer>

    <!--=============== SWIPER JS ===============-->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!--=============== Lazy load image JS ===============-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js" async></script>

    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>

    <!--=============== NEWSLETTER SCRIPT ===============-->
    <script>
      document.getElementById("newsletter-form").addEventListener("submit", function (e) {
        e.preventDefault();

        setTimeout(() => {
          window.open("https://biblophileupdates.substack.com/", "_blank");
        }, 500);
      });
    </script>
  </body>
</html>