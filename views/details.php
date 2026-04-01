<?php
/**
 * details.php  –  Product detail page / live-preview fragment
 *
 * PREVIEW MODE (called by the form via fetch POST):
 *   POST ../index.php?page=details&preview=1&product_id=0&product_name=preview
 *   Body: JSON with keys  productName, description, newPrice, oldPrice, tags,
 *         stockQuantity, categoryName, sku, additionalInfo{}, customizations{},
 *         imagePreviews[]
 *
 * NORMAL MODE:
 *   GET  ../index.php?page=details&product_id=<id>
 */

$isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';

if ($isPreview) {
    // ── Read POST body (JSON) sent by the form ─────────────────────────────
    $raw  = file_get_contents('php://input');
    $post = $raw ? (json_decode($raw, true) ?? []) : [];

    // Merge with any GET fallbacks (backwards-compat with old iframe approach)
    $get  = $_GET;

    $productDetails = [
        'ProductId'          => 0,
        'ProductName'        => $post['productName']        ?? $get['name']     ?? 'Product Name',
        'ProductDescription' => $post['description']        ?? $get['desc']     ?? 'Description',
        'NewPrice'           => $post['newPrice']           ?? $get['newPrice'] ?? 0,
        'OldPrice'           => $post['oldPrice']           ?? $get['oldPrice'] ?? 0,
        'SKU'                => $post['sku']                ?? 'PREVIEW-SKU',
        'Tags'               => $post['tags']               ?? $get['tags']     ?? '',
        'StockQuantity'      => $post['stockQuantity']      ?? $get['stock']    ?? 10,
        'ProductImage'       => '',   // handled separately below
        'CategoryName'       => $post['categoryName']       ?? $get['category'] ?? 'Category',
        'CategoryId'         => 0,
        'PromotionalLabel'   => $post['promotionalLabels']  ?? '',
        'DiscountLabel'      => $post['discountLabels']      ?? '',
    ];

    // Preview-mode helpers (don't hit the DB)
    $previewAdditionalInfo  = $post['additionalInfo']  ?? [];
    $previewCustomizations  = $post['customizations']  ?? [];
    $previewImages          = $post['imagePreviews']   ?? [];   // base64 data-URLs

} else {
    $productId      = (int)$_GET['product_id'];
    $productDetails = fetchProductDetails($productId);
}

if ($productDetails) {

    function escape($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    $productId          = $isPreview ? 0 : ($productDetails['ProductId'] ?? $productId);
    $productName        = escape($productDetails['ProductName']);
    $productDescription = escape($productDetails['ProductDescription']);
    $newPrice           = (float)$productDetails['NewPrice'];
    $oldPrice           = (float)$productDetails['OldPrice'];
    $sku                = escape($productDetails['SKU']);
    $productTags        = escape($productDetails['Tags']);
    $availability       = escape($productDetails['StockQuantity']);
    $productImage       = escape($productDetails['ProductImage'] ?? '');
    $categoryName       = escape($productDetails['CategoryName']);
    $categoryId         = escape($productDetails['CategoryId']);
    $discount           = $oldPrice > 0 ? round((($oldPrice - $newPrice) / $oldPrice) * 100) : 0;
?>
<link rel="stylesheet" href="assets/css/details.css" />

<?php if ($isPreview): ?>
<style>
    /* ── Scoped overrides for in-page preview ─────────────────── */
    .main                 { padding: 1rem 0; }
    .breadcrumb           { display: none; }   /* not useful in preview */
    .related__products    { display: none; }   /* hide related in preview */
    .details__tab         { margin-top: 1.5rem; }
    .review__form         { display: none; }
    .details__action      { pointer-events: none; opacity: .6; }  /* disable cart in preview */
    .add-to-cart-animation{ display: none !important; }
</style>
<?php endif; ?>

<main class="main">

    <!--=============== BREADCRUMB ===============-->
    <?php if (!$isPreview): ?>
    <section class="breadcrumb">
        <ul class="breadcrumb__list flex container">
            <li><a href=""     class="breadcrumb__link">Home</a></li>
            <li><span class="breadcrumb__link"> &gt; </span></li>
            <li><a href="shop" class="breadcrumb__link">Shop</a></li>
            <li><span class="breadcrumb__link"> &gt; </span></li>
            <li><a href="shop/<?= $categoryId . '/' . $categoryName ?>" class="breadcrumb__link"><?= $categoryName ?></a></li>
            <li><span class="breadcrumb__link"> &gt; </span></li>
            <li><span class="breadcrumb__link"><?= $productName ?></span></li>
        </ul>
    </section>
    <?php endif; ?>

    <!--=============== DETAILS ===============-->
    <section class="details section--lg">
        <div class="details__container container grid">

            <!-- Image gallery -->
            <div class="details__group">
                <?php if ($isPreview && !empty($previewImages)): ?>
                    <!-- Show images uploaded in this session as base64 -->
                    <div class="details__images">
                        <img
                            src="<?= htmlspecialchars($previewImages[0]) ?>"
                            alt="<?= $productName ?>"
                            class="details__img"
                        />
                        <?php if (count($previewImages) > 1): ?>
                        <div class="details__small-images">
                            <?php foreach ($previewImages as $img): ?>
                                <img
                                    src="<?= htmlspecialchars($img) ?>"
                                    alt=""
                                    class="details__small-img"
                                />
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($isPreview): ?>
                    <!-- Placeholder when no image uploaded yet -->
                    <div style="
                        width:100%; aspect-ratio:1/1; max-width:340px;
                        background:#f3f4f6; border-radius:12px;
                        display:flex; align-items:center; justify-content:center;
                        color:#9ca3af; font-size:.875rem; border: 2px dashed #e5e7eb;
                    ">
                        📷 No image yet
                    </div>
                <?php else: ?>
                    <?php displayDetailImages($productImage); ?>
                <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="details__group">
                <h3 class="details__title"><?= $productName ?></h3>
                <p class="details__category">Category: <span><?= $categoryName ?></span></p>

                <div class="details__price flex">
                    <span class="new__price">₹<?= number_format($newPrice, 2) ?></span>
                    <?php if ($oldPrice > 0): ?>
                        <span class="old__price">₹<?= number_format($oldPrice, 2) ?></span>
                        <span class="save__price"><?= $discount ?>% Off</span>
                    <?php endif; ?>
                </div>

                <p class="short__description"><?= $productDescription ?></p>

                <?php if (!$isPreview && $categoryName === 'Bookmarks'): ?>
                    <p class="short__description">
                        <a href="https://biblophile.freshdesk.com/support/solutions/articles/1060000121728-how-to-use-smart-bookmarks-">
                            How to use Smart Bookmarks?
                        </a>
                    </p>
                <?php endif; ?>

                <!-- Customization options -->
                <div class="customization__container">
                    <?php
                    // In preview mode use JS-passed data; in real mode fetch from DB
                    $customizationOptions = $isPreview
                        ? $previewCustomizations
                        : fetchCustomizationOptions($productId);

                    if ($customizationOptions && count($customizationOptions) > 0):
                    ?>
                        <div class="details__customization flex">
                            <ul class="customization-value__list">
                                <?php
                                $isFirst = true;
                                foreach ($customizationOptions as $option => $price):
                                    $activeClass = $isFirst ? 'customization-value-active' : '';
                                    $href        = $isPreview
                                        ? '#' . htmlspecialchars($option)
                                        : 'details/' . $productId . '/' . str_replace('+', '%20', urlencode(str_replace(' ', '-', $productDetails['ProductName']))) . '/#' . $option;
                                ?>
                                    <li>
                                        <a href="<?= $href ?>"
                                           class="customization-value__link <?= $activeClass ?>"
                                           data-price="<?= htmlspecialchars($price) ?>">
                                            <?= htmlspecialchars($option) ?>
                                        </a>
                                    </li>
                                <?php $isFirst = false; endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
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
                    <li class="meta__list flex"><span>SKU:</span> <?= $sku ?></li>
                    <li class="meta__list flex"><span>Tags:</span> <?= $productTags ?></li>
                    <?php if ($availability !== ''): ?>
                        <li class="meta__list flex">
                            <span>Availability:</span> <?= htmlspecialchars($availability) ?> items in stock
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>
    </section>

    <!--=============== DETAILS TAB ===============-->
    <section class="details__tab container">
        <div class="detail__tabs">
            <span class="detail__tab active-tab" data-target="#info">Additional Info</span>
            <?php if (!$isPreview): ?>
                <!-- <span class="detail__tab" data-target="#reviews">Reviews(3)</span> -->
            <?php endif; ?>
        </div>

        <div class="details__tabs-content">
            <div class="details__tab-content active-tab" id="info">
                <?php
                $additionalInfo = $isPreview
                    ? $previewAdditionalInfo
                    : fetchProductAdditionalInfo($productId);

                if ($additionalInfo && count($additionalInfo) > 0):
                ?>
                    <table class="info__table">
                        <?php foreach ($additionalInfo as $key => $value): ?>
                            <tr>
                                <th><?= htmlspecialchars(ucfirst($key)) ?></th>
                                <td><?= htmlspecialchars($value) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p style="color:#9ca3af;font-size:.875rem;">No additional information yet.</p>
                <?php endif; ?>
            </div>

            <?php if (!$isPreview): ?>
            <div class="details__tab-content" id="reviews">
                <div class="reviews__container grid">
                    <?php displayReviews($productId); ?>
                </div>

                <div class="review__form">
                    <h4 class="review__form-title">Add a review</h4>
                    <div class="rate__product">
                        <i class="fi fi-rs-star"></i>
                        <select id="rating" name="rating" class="form__input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <select id="decimal" name="decimal" class="form__input">
                            <option value="0.00">.00</option>
                            <option value="0.25">.25</option>
                            <option value="0.50">.50</option>
                            <option value="0.75">.75</option>
                        </select>
                    </div>
                    <form action="" class="form grid">
                        <textarea class="form__input textarea" id="review-entry" placeholder="Write Review"></textarea>
                        <div class="form__btn">
                            <button class="btn" id="submitRatingBtn">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!$isPreview): ?>
    <!--=============== RELATED PRODUCTS ===============-->
    <section class="related__products section" id="products-container">
        <h3 class="section__title"><span>Related</span> Products</h3>
        <div class="related__products__container swiper">
            <div class="swiper-wrapper">
                <?php
                $productIds = fetchProductIds(5, 0, $productDetails['CategoryId']);
                foreach ($productIds as $relId) {
                    $product = fetchProductDetails($relId);
                    if (!$product) continue;
                    $images         = fetchImagesFromImageKit($product['ProductImage']);
                    $imagesToDisplay = array_slice($images, 0, 2);
                ?>
                    <div class="product__item swiper-slide">
                        <div class="product__banner">
                            <a href="details/<?= $relId ?>/<?= str_replace('+', '%20', urlencode(str_replace(' ', '-', $product['ProductName']))) ?>" class="product__images">
                                <?php foreach ($imagesToDisplay as $idx => $image): ?>
                                    <img
                                        srcset="<?= htmlspecialchars($image) ?>&tr=h-250,w-250 500w,
                                                <?= htmlspecialchars($image) ?>&tr=h-500,w-500 1000w"
                                        sizes="(max-width:768px) 250px, 500px"
                                        alt=""
                                        class="lazyload product__img <?= $idx === 0 ? 'default' : 'hover' ?>"
                                    />
                                <?php endforeach; ?>
                            </a>

                            <?php if (!empty($product['PromotionalLabel'])): ?>
                                <div class="product__badge light-pink"><?= htmlspecialchars($product['PromotionalLabel']) ?></div>
                            <?php endif; ?>
                            <?php if ($product['StockQuantity'] == '0'): ?>
                                <div class="product__badge light-pink">Sold out</div>
                            <?php endif; ?>
                            <?php if (!empty($product['DiscountLabel'])): ?>
                                <div class="product__badge light-green"><?= htmlspecialchars($product['DiscountLabel']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="product__content">
                            <span class="product__category"><?= htmlspecialchars($product['CategoryName']) ?></span>
                            <a href="?page=details&product_id=<?= $relId ?>">
                                <h3 class="product__title"><?= htmlspecialchars($product['ProductName']) ?></h3>
                            </a>
                            <div class="product__rating">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fi fi-rs-star<?= $i < $product['AvgRating'] ? '' : '-o' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="product__price flex">
                                <span class="new__price">₹<?= number_format($product['NewPrice'], 2) ?></span>
                                <?php if ($product['OldPrice'] !== null): ?>
                                    <span class="old__price">₹<?= number_format($product['OldPrice'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                    <!-- gotta write the logic to add to cart here  -->
                    <!-- <a href="#" class="action__btn cart__btn" aria-label="Add To Cart">
                      <i class="fi fi-rs-shopping-bag-add"></i>
                    </a> -->
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="swiper-button-next"><i class="fi fi-rs-angle-right"></i></div>
            <div class="swiper-button-prev"><i class="fi fi-rs-angle-left"></i></div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php if (!$isPreview): ?>
<!-- ── Full-page JS (only loaded on the real product page) ── -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>

/*=============== IMAGE GALLERY ===============*/
function imgGallery() {
    const mainImg  = document.querySelector('.details__img');
    const smallImg = document.querySelectorAll('.details__small-img');
    smallImg.forEach(img => {
        img.addEventListener('click', function () {
            let newSrc = this.src;
            if (newSrc.includes('&tr=')) newSrc = newSrc.replace(/&tr=[^&]*/, '');
            newSrc += '&tr=w-400';
            mainImg.src = newSrc;
        });
    });
}
imgGallery();

/*=============== CUSTOMIZATION ===============*/
document.addEventListener('DOMContentLoaded', () => {
    const customizationLinks = document.querySelectorAll('.customization-value__link');
    const newPriceEl         = document.querySelector('.new__price');
    const oldPriceEl         = document.querySelector('.old__price');
    const discountEl         = document.querySelector('.save__price');

    const formatPrice = p => `₹${p.toFixed(2)}`;

    const updateCustomization = hash => {
        customizationLinks.forEach(link => {
            const c = link.getAttribute('href').split('#')[1];
            if (c === hash) {
                link.classList.add('customization-value-active');
                const updatedPrice = parseFloat(link.getAttribute('data-price'));
                if (!isNaN(updatedPrice)) {
                    newPriceEl.textContent = formatPrice(updatedPrice);
                    if (oldPriceEl) oldPriceEl.style.display = 'none';
                    if (discountEl) discountEl.style.display = 'none';
                }
            } else {
                link.classList.remove('customization-value-active');
            }
        });
    };

    const hash = window.location.hash.substring(1);
    if (hash) updateCustomization(hash);

    customizationLinks.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const c = link.getAttribute('href').split('#')[1];
            updateCustomization(c);
            history.replaceState(null, '', `${window.location.href.split('#')[0]}#${c}`);
        });
    });
});

/*=============== QUANTITY CONTROLS ===============*/
document.addEventListener('DOMContentLoaded', () => {
    const dec = document.getElementById('decrementBtn');
    const inc = document.getElementById('incrementBtn');
    const qty = document.getElementById('quantity');
    dec?.addEventListener('click', () => { if (parseInt(qty.value) > 1) qty.value--; });
    inc?.addEventListener('click', () => { qty.value++; });
});

/*=============== ADD TO CART ===============*/
document.getElementById('addToCartBtn')?.addEventListener('click', function (e) {
    e.preventDefault();
    const quantity      = document.getElementById('quantity').value;
    const price         = <?= json_encode((float)$productDetails['NewPrice']) ?>;
    const productId     = <?= json_encode((int)($productDetails['ProductId'] ?? 0)) ?>;
    const hash          = window.location.hash.substring(1);
    const customization = hash ? decodeURIComponent(hash) : null;

    const rect = e.target.getBoundingClientRect();
    const anim = document.createElement('div');
    anim.classList.add('add-to-cart-animation');
    anim.style.top  = `${rect.top  + rect.height / 2}px`;
    anim.style.left = `${rect.left + rect.width  / 2}px`;
    document.body.appendChild(anim);
    anim.addEventListener('animationend', () => anim.remove());

    CartSystem.addItem(productId, quantity, price, customization)
        .then(data => {
            if (data.success || data.message == '1') {
                updateCartCountInHeader();
            } else {
                alert('Error: ' + (data.message || 'Failed to add product to cart.'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('There was an issue adding the item to the cart.');
        });
});

/*=============== SWIPER ===============*/
new Swiper('.related__products__container', {
    spaceBetween: 24,
    loop: true,
    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    breakpoints: {
        300:  { slidesPerView: 2.5, spaceBetween: 24 },
        448:  { slidesPerView: 3,   spaceBetween: 24 },
        768:  { slidesPerView: 3,   spaceBetween: 24 },
        992:  { slidesPerView: 3,   spaceBetween: 44 },
        1400: { slidesPerView: 5,   spaceBetween: 24 },
    }
});

/*=============== RATING ===============*/
const ratingSelect  = document.getElementById('rating');
const decimalSelect = document.getElementById('decimal');

ratingSelect?.addEventListener('change', function () {
    decimalSelect.innerHTML = this.value == '5'
        ? '<option value="0.00">.00</option>'
        : `<option value="0.00">.00</option>
           <option value="0.25">.25</option>
           <option value="0.50">.50</option>
           <option value="0.75">.75</option>`;
});

document.getElementById('submitRatingBtn')?.addEventListener('click', e => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('productId', <?= json_encode((int)($productDetails['ProductId'] ?? 0)) ?>);
    formData.append('rating',    parseFloat(ratingSelect.value) + parseFloat(decimalSelect.value));
    formData.append('review',    document.getElementById('review-entry').value);

    fetch('actions.php?action=submitReview', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.message == '1') alert('Review submitted');
            else alert('Error: ' + data.message);
        })
        .catch(() => alert('Please login to add a rating.'));
});
</script>

<?php else: ?>
<!-- ── Lightweight preview JS (no cart, no swiper) ── -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    /* Gallery for base64 preview images */
    const mainImg  = document.querySelector('.details__img');
    const smallImg = document.querySelectorAll('.details__small-img');
    smallImg?.forEach(img => {
        img.addEventListener('click', function () { if (mainImg) mainImg.src = this.src; });
    });

    /* Customization price switching (works the same in preview) */
    const links      = document.querySelectorAll('.customization-value__link');
    const newPriceEl = document.querySelector('.new__price');
    links.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            links.forEach(l => l.classList.remove('customization-value-active'));
            link.classList.add('customization-value-active');
            const p = parseFloat(link.getAttribute('data-price'));
            if (!isNaN(p) && newPriceEl) newPriceEl.textContent = `₹${p.toFixed(2)}`;
        });
    });
});
</script>
<?php endif; ?>

<?php
} else {
    echo '<p style="padding:2rem;color:#6b7280;">Product not found.</p>';
}
?>