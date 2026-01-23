document.addEventListener("DOMContentLoaded", () => {
    const lightbox = document.getElementById("lightbox");
    const lightboxImg = document.getElementById("lightbox-img");
    const closeBtn = document.querySelector(".lightbox__close");
    const portfolioItems = document.querySelectorAll(".portfolio__item img");

    // Open Lightbox
    portfolioItems.forEach(image => {
        image.addEventListener("click", () => {
            lightbox.classList.add("active");
            lightboxImg.src = image.src; // Copy the image source to the lightbox
            document.body.style.overflow = "hidden"; // Prevent background scrolling
        });
    });

    // Close Lightbox (Clicking 'X' or the background)
    const closeLightbox = () => {
        lightbox.classList.remove("active");
        document.body.style.overflow = "auto"; // Re-enable scrolling
    };

    closeBtn.addEventListener("click", closeLightbox);
    
    lightbox.addEventListener("click", (e) => {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });
});