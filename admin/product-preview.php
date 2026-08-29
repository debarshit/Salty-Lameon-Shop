<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="../assets/css/details.css" />
    <style>
        /* ── Preview-only overrides ───────────────────────────── */
        body                  { background: #fff; margin-bottom: 50px; }
        .breadcrumb           { display: none; }
        .related__products    { display: none; }
        .review__form         { display: none; }
        .details__action      { pointer-events: none; opacity: .55; }

        /* Empty state */
        #empty-state {
            display: flex; align-items: center; justify-content: center;
            min-height: 60vh; color: #9ca3af; font-family: sans-serif;
            font-size: .9rem; flex-direction: column; gap: .75rem;
        }
        #empty-state span { font-size: 2rem; }

        /* Image placeholder */
        .preview-img-placeholder {
            width: 100%; aspect-ratio: 1/1; max-width: 340px;
            background: #f3f4f6; border: 2px dashed #d1d5db;
            border-radius: 12px; display: flex; align-items: center;
            justify-content: center; color: #9ca3af;
            font-size: .875rem; gap: .5rem;
        }

        .details__small-img {
            width: 60px; height: 60px; object-fit: cover;
            border-radius: 6px; cursor: pointer;
            border: 2px solid transparent; transition: border-color .15s;
        }
        .details__small-img:hover  { border-color: #6366f1; }
        .details__small-img.active { border-color: #6366f1; }
    </style>
</head>
<body>

<div id="empty-state">
    <span>🖊️</span>
    Start filling the form to see a live preview
</div>

<div id="preview-root" style="display:none;">
<main class="main">

    <section class="details section--lg">
        <div class="details__container container grid">

            <!-- Image column -->
            <div class="details__group" id="img-col">
                <div class="preview-img-placeholder">📷 No image yet</div>
            </div>

            <!-- Info column -->
            <div class="details__group">
                <h3 class="details__title"    id="p-name">—</h3>
                <p  class="details__category">Category: <span id="p-category">—</span></p>

                <div class="details__price flex">
                    <span class="new__price"  id="p-new-price">₹0.00</span>
                    <span class="old__price"  id="p-old-price" style="display:none;"></span>
                    <span class="save__price" id="p-discount"  style="display:none;"></span>
                </div>

                <p class="short__description" id="p-desc">—</p>

                <!-- Customizations (rebuilt on each update) -->
                <div class="customization__container" id="p-customizations"></div>

                <!-- Cart UI (visual only, non-functional) -->
                <div class="details__action" id="p-cart-action">
                    <div class="quantity-controls">
                        <button class="quantity-btn" disabled>-</button>
                        <input  type="number" class="quantity" value="1" min="1" disabled />
                        <button class="quantity-btn" disabled>+</button>
                    </div>
                    <button class="btn btn--sm" disabled>Add to Cart</button>
                </div>

                <ul class="details__meta">
                    <li class="meta__list flex"><span>SKU:</span> <span id="p-sku">—</span></li>
                    <li class="meta__list flex" id="p-tags-row" style="display:none;">
                        <span>Tags:</span> <span id="p-tags"></span>
                    </li>
                    <li class="meta__list flex" id="p-stock-row">
                        <span>Availability:</span> <span id="p-stock"></span> items in stock
                    </li>
                </ul>
            </div>

        </div>
    </section>

    <!-- Additional Info tab -->
    <section class="details__tab container">
        <div class="detail__tabs">
            <span class="detail__tab active-tab">Additional Info</span>
        </div>
        <div class="details__tabs-content">
            <div class="details__tab-content active-tab" id="p-additional-info">
                <p style="color:#9ca3af;font-size:.875rem;">No additional information yet.</p>
            </div>
        </div>
    </section>

</main>
</div><!-- /#preview-root -->

<script>
// ── Tell the parent we're ready ───────────────────────────────
window.parent.postMessage('preview-ready', '*');

// ── Listen for data updates from the form ─────────────────────
window.addEventListener('message', (e) => {
    if (!e.data || e.data.type !== 'preview-update') return;
    render(e.data.data);
    window.parent.postMessage('preview-rendered', '*');
});

// ── Main render — updates DOM nodes in-place, no reload ───────
function render(d) {
    document.getElementById('empty-state').style.display  = 'none';
    document.getElementById('preview-root').style.display = '';

    setText('p-name',     d.productName  || '—');
    setText('p-category', d.categoryName || '—');
    setText('p-desc',     d.description  || '—');
    setText('p-sku',      d.sku          || '—');

    // Price
    const newPrice = parseFloat(d.newPrice) || 0;
    const oldPrice = parseFloat(d.oldPrice) || 0;
    setText('p-new-price', fmt(newPrice));

    const oldEl      = document.getElementById('p-old-price');
    const discountEl = document.getElementById('p-discount');
    if (oldPrice > 0) {
        setText('p-old-price', fmt(oldPrice));
        oldEl.style.display      = '';
        const pct = Math.round(((oldPrice - newPrice) / oldPrice) * 100);
        setText('p-discount', pct + '% Off');
        discountEl.style.display = '';
    } else {
        oldEl.style.display      = 'none';
        discountEl.style.display = 'none';
    }

    // Stock / availability
    const stock = String(d.stockQuantity ?? '');
    setText('p-stock', stock);
    document.getElementById('p-cart-action').style.display = stock === '0' ? 'none' : '';
    document.getElementById('p-stock-row').style.display   = stock !== ''  ? ''     : 'none';

    // Tags
    const tagsRow = document.getElementById('p-tags-row');
    if (d.tags) {
        setText('p-tags', d.tags);
        tagsRow.style.display = '';
    } else {
        tagsRow.style.display = 'none';
    }

    // Images, customizations, additional info
    renderImages(d.imagePreviews   || []);
    renderCustomizations(d.customizations || {}, newPrice);
    renderAdditionalInfo(d.additionalInfo  || {});
}

function renderImages(images) {
    const col = document.getElementById('img-col');
    if (!images.length) {
        col.innerHTML = '<div class="preview-img-placeholder">📷 No image yet</div>';
        return;
    }
    let html = `<img id="mainPreviewImg" src="${esc(images[0])}"
                     class="details__img"
                     style="width:100%;max-width:340px;border-radius:8px;object-fit:cover;" />`;
    if (images.length > 1) {
        html += '<div style="display:flex;gap:.5rem;margin-top:.5rem;flex-wrap:wrap;">';
        images.forEach((src, i) => {
            html += `<img src="${esc(src)}"
                         class="details__small-img${i === 0 ? ' active' : ''}"
                         data-src="${esc(src)}" />`;
        });
        html += '</div>';
    }
    col.innerHTML = html;

    col.querySelectorAll('.details__small-img').forEach(thumb => {
        thumb.addEventListener('click', () => {
            const main = document.getElementById('mainPreviewImg');
            if (main) main.src = thumb.dataset.src;
            col.querySelectorAll('.details__small-img').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        });
    });
}

function renderCustomizations(customizations, basePrice) {
    const container = document.getElementById('p-customizations');
    const entries   = Object.entries(customizations).filter(([k]) => k.trim());
    if (!entries.length) { container.innerHTML = ''; return; }

    let html = '<div class="details__customization flex"><ul class="customization-value__list">';
    entries.forEach(([option, price], i) => {
        html += `<li><a href="#"
                    class="customization-value__link${i === 0 ? ' customization-value-active' : ''}"
                    data-price="${esc(String(price))}">${esc(option)}</a></li>`;
    });
    html += '</ul></div>';
    container.innerHTML = html;

    container.querySelectorAll('.customization-value__link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            container.querySelectorAll('.customization-value__link')
                .forEach(l => l.classList.remove('customization-value-active'));
            link.classList.add('customization-value-active');
            const p = parseFloat(link.dataset.price);
            if (!isNaN(p)) setText('p-new-price', fmt(p));
        });
    });
}

function renderAdditionalInfo(info) {
    const el      = document.getElementById('p-additional-info');
    const entries = Object.entries(info).filter(([k]) => k.trim());
    if (!entries.length) {
        el.innerHTML = '<p style="color:#9ca3af;font-size:.875rem;">No additional information yet.</p>';
        return;
    }
    let html = '<table class="info__table">';
    entries.forEach(([k, v]) => {
        html += `<tr><th>${esc(cap(k))}</th><td>${esc(String(v))}</td></tr>`;
    });
    el.innerHTML = html + '</table>';
}

// ── Helpers ───────────────────────────────────────────────────
function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}
function fmt(n) { return '₹' + n.toFixed(2); }
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
function esc(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/</g, '&lt;').replace(/>/g,  '&gt;');
}
</script>
</body>
</html>