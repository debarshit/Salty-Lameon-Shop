<?php
$artworks = fetchImagesFromImageKit('/artworks');
?>
<link rel="stylesheet" href="assets/css/home.css" />
    <!--=============== MAIN ===============-->
    <main class="main">
        <!--=============== HOME ===============-->
        <section class="featured container">
            <div class="featured__grid">
            <div class="featured__item featured__item--large">
                <img src="https://picsum.photos/800" alt="Featured">
                <div class="contact-infos">
                <div class="contact-infos__email__section">
                    <i class="ri-mail-line"></i>
                    <a href="mailto:rashmi@thesaltylameon.com" class="contact-infos__email">rashmi@thesaltylameon.com</a>
                </div>

                <div class="contact-infos__socials__section">
                    <a href="https://www.instagram.com/maybe.rush_me/" target="_blank" class="contact-infos__socials__link">
                        <img 
                            src="assets/img/icon-instagram.svg" 
                            alt=""
                            class="contact-infos__socials__icon"
                            />
                    </a>
                    <a href="https://www.youtube.com/channel/UC7fnmbvh6tMTeXUqN0AglZg" target="_blank" class="contact-infos__socials__link">
                        <img 
                            src="assets/img/icon-youtube.svg" 
                            alt=""
                            class="contact-infos__socials__icon"
                            />
                    </a>
                    <a href="https://pin.it/7JO9YvYb6" target="_blank" class="contact-infos__socials__link">
                        <img 
                            src="assets/img/icon-pinterest.svg" 
                            alt=""
                            class="contact-infos__socials__icon"
                            />
                    </a>
                </div>

                <!-- <div class="footer__note">
                    <p>For all book lovers, check out <span class="highlight">bibliophile ;)</span></p>
                </div> -->
                </div>
            </div>
            <div class="featured__item featured__item--small">
                <img src="assets/img/logo.svg" alt="Featured">
            </div>
            </div>
        </section>

        <!-- Portfolio Images Section -->
        <section class="portfolio container section">
            <h2 class="section__title">My Artworks</h2>  
            <div class="portfolio__masonry">
                 <?php if (!empty($artworks)): ?>
                    <?php foreach ($artworks as $index => $imageUrl): ?>
                        <div class="portfolio__item">
                            <img src="<?php echo $imageUrl; ?>" alt="Artwork <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No artworks found.</p>
                <?php endif; ?>
            </div>
            <div id="lightbox" class="lightbox">
            <span class="lightbox__close">&times;</span>
            <img class="lightbox__content" id="lightbox-img">
            </div>
        </section>
    </main>
    <script src="assets/js/lightbox.js"></script>