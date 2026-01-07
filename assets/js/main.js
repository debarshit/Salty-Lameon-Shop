/*=============== SHOW MENU ===============*/
const navMenu = document.getElementById('nav-menu'),
navToggle = document.getElementById('nav-toggle'),
navClose = document.getElementById('nav-close');

/*===== Menu Show =====*/
/* Validate if constant exists */
if(navToggle) {
    navToggle.addEventListener('click', () => {
        navMenu.classList.add('show-menu');
    });
}
 
/*===== Hide Show =====*/
/* Validate if constant exists */
if(navClose) {
    navClose.addEventListener('click', () => {
        navMenu.classList.remove('show-menu');
    });
}

/*=============== SWIPER CATEGORIES ===============*/
  const scrollContainer = document.querySelector('.category-scroll');
  const nextBtn = document.querySelector('.next-btn');
  const prevBtn = document.querySelector('.prev-btn');

  nextBtn.addEventListener('click', () => {
    scrollContainer.scrollBy({ left: 300, behavior: 'smooth' });
  });

  prevBtn.addEventListener('click', () => {
    scrollContainer.scrollBy({ left: -300, behavior: 'smooth' });
  });

/*=============== DETAILS TABS ===============*/
const tabs = document.querySelectorAll('[data-target]'),
tabContents = document.querySelectorAll('[content]');

tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        const target = document.querySelector(tab.dataset.target);
        tabContents.forEach((tabContent) => {
            tabContent.classList.remove('active-tab')
        });

       target.classList.add('active-tab');

       tabs.forEach((tab) => {
        tab.classList.remove('active-tab')
       });

       tab.classList.add('active-tab');
    });
});

// Function to update the cart number dynamically in the header
function updateCartCount(route=null) {
    let actionRoute = 'actions.php?action=getCartCount';
    if (route !== null) {
        actionRoute = route;
    }
    fetch(actionRoute)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const cartNumberElement = document.querySelector('.header__user-actions .count');
          if (cartNumberElement) {
            cartNumberElement.textContent = data.cartCount;
          }
        } else {
          console.error("Error fetching updated cart count.");
        }
      })
      .catch(error => {
        console.error("Error fetching updated cart count:", error);
      });
}

// Function to update the guest cart count display
function updateGuestCartCount() {
    const guestCart = localStorage.getItem('guestCart');
    const cartCount = guestCart ? JSON.parse(guestCart).length : 0;
    // Update the element that displays the cart count (e.g., a span with an ID)
    const guestCartCountElement = document.getElementById('guest-cart-count');
    if (guestCartCountElement) {
        guestCartCountElement.textContent = cartCount;
    }
}

// Call updateGuestCartCount on page load to show initial count
document.addEventListener('DOMContentLoaded', updateGuestCartCount);

  