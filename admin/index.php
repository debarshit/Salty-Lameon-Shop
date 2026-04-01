<?php
include("functions.php");
include("../functions.php");

// ── Edit mode: load existing product data ──────────────────────────────────
$editMode   = isset($_GET['edit']) && !empty($_GET['edit']);
$editId     = $editMode ? (int)$_GET['edit'] : 0;
$prefill    = [];   // JS-safe prefill payload

if ($editMode && $editId > 0) {
    $product = fetchProductDetails($editId);
    if ($product) {
        $prefill['productName']        = $product['ProductName']        ?? '';
        $prefill['description']        = $product['ProductDescription'] ?? '';
        $prefill['oldPrice']           = $product['OldPrice']           ?? '';
        $prefill['newPrice']           = $product['NewPrice']           ?? '';
        $prefill['stockQuantity']      = $product['StockQuantity']      ?? '';
        $prefill['sku']                = $product['SKU']                ?? '';
        $prefill['categoryName']       = $product['CategoryName']       ?? '';
        $prefill['tags']               = $product['Tags']               ?? '';
        $prefill['promotionalLabels']  = $product['PromotionalLabel']   ?? '';
        $prefill['discountLabels']     = $product['DiscountLabel']      ?? '';
        $prefill['specialCategories']  = $product['SpecialCategories']  ?? '';

        $customizations = fetchCustomizationOptions($editId);
        $prefill['customizations'] = $customizations ? $customizations : [];

        $additionalInfo = fetchProductAdditionalInfo($editId);
        $prefill['additionalInfo'] = $additionalInfo ? $additionalInfo : [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <title><?= $editMode ? 'Edit Product' : 'Add Product' ?> — Admin</title>
    <style>
        /* ── Layout ── */
        .container          { display: flex; gap: 0; min-height: 100vh; }
        .form-container     { width: 480px; flex-shrink: 0; overflow-y: auto; padding: 2rem; background: #fff; border-right: 1px solid #e5e7eb; }
        .preview-container  { flex: 1; overflow-y: auto; background: #f8f8f8; padding: 2rem; position: relative; }

        .preview-container h3.preview-label {
            font-size: 0.75rem; font-weight: 600; letter-spacing: .1em;
            text-transform: uppercase; color: #9ca3af; margin-bottom: 1.25rem;
        }

        #previewWrapper {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 20px rgba(0,0,0,.06);
            overflow: hidden;
            min-height: 300px;
            transition: opacity .2s;
        }
        #previewWrapper.loading { opacity: .4; pointer-events: none; }

        /* ── spinner ── */
        .preview-spinner {
            display: none;
            position: absolute; top: 3.5rem; right: 1.5rem;
            width: 20px; height: 20px;
            border: 2px solid #e5e7eb;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── form chrome ── */
        .form h2  { margin-bottom: .25rem; }
        .form > p { color: #6b7280; margin-bottom: 1.5rem; font-size: .9rem; }

        /* image preview strip */
        #imagePreviews { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .5rem; }
        #imagePreviews img { width: 64px; height: 64px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb; }
        #imageError   { color: #ef4444; font-size: .8rem; margin-top: .3rem; display: none; }
        #uploadStatus { color: #6366f1; font-size: .8rem; margin-top: .3rem; display: none; }

        /* suggestions */
        .suggestion-box { border: 1px solid #e5e7eb; border-radius: 6px; background: #fff; margin-top: -1px; z-index: 10; }
        .suggestion-box div { padding: .45rem .75rem; cursor: pointer; font-size: .875rem; }
        .suggestion-box div:hover { background: #f3f4f6; }

        /* edit-mode badge */
        .edit-badge {
            display: inline-flex; align-items: center; gap: .4rem;
            background: #fef3c7; color: #92400e;
            border: 1px solid #fde68a; border-radius: 20px;
            font-size: .75rem; font-weight: 600; padding: .25rem .75rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- ══════════════════ FORM PANEL ══════════════════ -->
    <div class="form-container">
        <div class="form">
            <?php if ($editMode): ?>
                <span class="edit-badge">✏️ Editing Product #<?= $editId ?></span>
            <?php endif; ?>

            <h2><?= $editMode ? 'Edit Product' : 'Product Entry Form' ?></h2>
            <p>Please fill in the details for the <?= $editMode ? 'existing' : 'new' ?> product.</p>

            <!-- radio-driven step indicator (unchanged) -->
            <input id="one"   type="radio" name="stage" checked="checked" />
            <input id="two"   type="radio" name="stage" />
            <input id="three" type="radio" name="stage" />
            <input id="four"  type="radio" name="stage" />
            <input id="five"  type="radio" name="stage" />
            <input id="six"   type="radio" name="stage" />

            <div class="stages">
                <label for="one">1</label>
                <label for="two">2</label>
                <label for="three">3</label>
                <label for="four">4</label>
                <label for="five">5</label>
                <label for="six">6</label>
            </div>

            <span class="progress"><span></span></span>
            <div class="loader" id="loader" style="display:none"></div>

            <div class="panels">

                <!-- Panel 1 – Product Info -->
                <div data-panel="one">
                    <h4>Product Information</h4>
                    <input type="text"      placeholder="Product Name"  id="productName"  required />
                    <textarea              placeholder="Description"    id="description"  required></textarea>
                </div>

                <!-- Panel 2 – Pricing -->
                <div data-panel="two">
                    <h4>Pricing</h4>
                    <input type="number" placeholder="Old Price"       id="oldPrice" />
                    <input type="number" placeholder="New Price"       id="newPrice"       required />
                    <input type="number" placeholder="Stock Quantity"  id="stockQuantity"  required />
                </div>

                <!-- Panel 3 – Media -->
                <div data-panel="three">
                    <h4>Media</h4>
                    <?php if ($editMode && !empty($prefill['productImage'])): ?>
                        <p style="font-size:.8rem;color:#6b7280;margin-bottom:.5rem;">
                            Current images will be kept unless you upload new ones.
                        </p>
                    <?php endif; ?>
                    <input type="file" id="imageUpload" accept="image/*" multiple />
                    <div id="imagePreviews"></div>
                    <div id="imageError">Only square images are allowed. Maximum 5 images.</div>
                    <div id="uploadStatus">Uploading images…</div>
                </div>

                <!-- Panel 4 – Categorization -->
                <div data-panel="four">
                    <h4>Categorization</h4>
                    <input type="text" placeholder="Category Name" id="categoryName" required />
                    <div id="categorySuggestions" class="suggestion-box" style="display:none;"></div>
                    <input type="text" placeholder="SKU" id="sku" required />
                </div>

                <!-- Panel 5 – Tags & Labels -->
                <div data-panel="five">
                    <h4>Tags &amp; Labels</h4>
                    <input type="text" placeholder="Tags (comma-separated)"               id="tags" />
                    <div id="tagSuggestions"   class="suggestion-box" style="display:none;"></div>
                    <input type="text" placeholder="Promotional Labels (comma-separated)" id="promotionalLabels" />
                    <div id="promoSuggestions" class="suggestion-box" style="display:none;"></div>
                    <input type="text" placeholder="Discount Labels (comma-separated)"    id="discountLabels" />
                    <div id="discSuggestions"  class="suggestion-box" style="display:none;"></div>
                </div>

                <!-- Panel 6 – Additional Info & Customizations -->
                <div data-panel="six">
                    <h4>Additional Info &amp; Customizations</h4>

                    <h5>Additional Info</h5>
                    <table id="additionalInfosTable">
                        <thead><tr><th>Key</th><th>Value</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><input type="text" placeholder="Key"   class="key" /></td>
                                <td><input type="text" placeholder="Value" class="value" /></td>
                                <td><button class="removeAdditionalInfoRow">Remove</button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="3"><button id="addAdditionalInfoRow">Add Row</button></td></tr>
                        </tfoot>
                    </table>

                    <h5>Customizations</h5>
                    <table id="customizationTable">
                        <thead><tr><th>Option</th><th>Price</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><input type="text" placeholder="Option" class="option" /></td>
                                <td><input type="text" placeholder="Price"  class="values" /></td>
                                <td><button class="removeCustomizationRow">Remove</button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="3"><button id="addCustomizationRow">Add Row</button></td></tr>
                        </tfoot>
                    </table>

                    <input type="text" placeholder="Special Categories (comma-separated)" id="specialCategories" />
                    <div id="specialCategorySuggestions" class="suggestion-box" style="display:none;"></div>
                </div>

            </div><!-- /panels -->

            <button id="submitBtn"><?= $editMode ? 'Save Changes' : 'Next' ?></button>

        </div><!-- /form -->
    </div><!-- /form-container -->

    <!-- ══════════════════ PREVIEW PANEL ══════════════════ -->
    <div class="preview-container">
		<div class="preview-header">
			<h3 class="preview-label">Live Preview</h3>

			<div class="preview-controls">
				<button id="desktopView" class="active" title="Desktop View">💻</button>
				<button id="mobileView" title="Mobile View">📱</button>
			</div>
		</div>

		<div class="preview-spinner" id="previewSpinner"></div>

		<iframe
			id="previewFrame"
			src="product-preview.php"
			class="preview-frame"
		></iframe>
	</div>

</div><!-- /container -->

<!--=============== MAIN JS (step wizard etc.) ===============-->
<script src="assets/js/main.js"></script>

<script>
(function () {
// ─────────────────────────────────────────────────────────────
//  PREFILL DATA (PHP → JS)
// ─────────────────────────────────────────────────────────────
const EDIT_MODE    = <?= $editMode ? 'true' : 'false' ?>;
const EDIT_ID      = <?= $editId ?>;
const PREFILL_DATA = <?= json_encode($prefill, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

// ─────────────────────────────────────────────────────────────
//  PREFILL FORM FIELDS
// ─────────────────────────────────────────────────────────────
function prefillForm() {
    if (!EDIT_MODE || !PREFILL_DATA) return;

    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el && val !== undefined && val !== null) el.value = val;
    };

    set('productName',       PREFILL_DATA.productName);
    set('description',       PREFILL_DATA.description);
    set('oldPrice',          PREFILL_DATA.oldPrice);
    set('newPrice',          PREFILL_DATA.newPrice);
    set('stockQuantity',     PREFILL_DATA.stockQuantity);
    set('sku',               PREFILL_DATA.sku);
    set('categoryName',      PREFILL_DATA.categoryName);
    set('tags',              PREFILL_DATA.tags);
    set('promotionalLabels', PREFILL_DATA.promotionalLabels);
    set('discountLabels',    PREFILL_DATA.discountLabels);
    set('specialCategories', PREFILL_DATA.specialCategories);

    // Additional Info rows
    if (PREFILL_DATA.additionalInfo && Object.keys(PREFILL_DATA.additionalInfo).length) {
        const tbody = document.querySelector('#additionalInfosTable tbody');
        tbody.innerHTML = '';
        for (const [k, v] of Object.entries(PREFILL_DATA.additionalInfo)) {
            tbody.appendChild(makeInfoRow(k, v));
        }
    }

    // Customization rows
    if (PREFILL_DATA.customizations && Object.keys(PREFILL_DATA.customizations).length) {
        const tbody = document.querySelector('#customizationTable tbody');
        tbody.innerHTML = '';
        for (const [option, price] of Object.entries(PREFILL_DATA.customizations)) {
            tbody.appendChild(makeCustomRow(option, price));
        }
    }
}

function makeInfoRow(key = '', value = '') {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" placeholder="Key"   class="key"   value="${escHtml(key)}" /></td>
        <td><input type="text" placeholder="Value" class="value" value="${escHtml(value)}" /></td>
        <td><button class="removeAdditionalInfoRow">Remove</button></td>`;
    tr.querySelector('.removeAdditionalInfoRow')
        .addEventListener('click', () => {
            tr.remove();
            schedulePreview();
        });
    tr.querySelectorAll('input').forEach(i => i.addEventListener('input', schedulePreview));
    return tr;
}

function makeCustomRow(option = '', price = '') {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" placeholder="Option" class="option" value="${escHtml(option)}" /></td>
        <td><input type="text" placeholder="Price"  class="values" value="${escHtml(price)}" /></td>
        <td><button class="removeCustomizationRow">Remove</button></td>`;
    tr.querySelector('.removeCustomizationRow')
    .addEventListener('click', () => {
            tr.remove();
            schedulePreview();
        });
    tr.querySelectorAll('input').forEach(i => i.addEventListener('input', schedulePreview));
    return tr;
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ─────────────────────────────────────────────────────────────
//  COLLECT FORM STATE
// ─────────────────────────────────────────────────────────────
function collectFormData() {
    const additionalInfo = {};
    document.querySelectorAll('#additionalInfosTable tbody tr').forEach(tr => {
        const k = tr.querySelector('.key')?.value.trim();
        const v = tr.querySelector('.value')?.value.trim();
        if (k) additionalInfo[k] = v ?? '';
    });

    const customizations = {};
    document.querySelectorAll('#customizationTable tbody tr').forEach(tr => {
        const opt = tr.querySelector('.option')?.value.trim();
        const prc = tr.querySelector('.values')?.value.trim();
        if (opt) customizations[opt] = prc ?? '';
    });

    // Collect base64 images selected in this session
    const imagePreviews = [...document.querySelectorAll('#imagePreviews img')].map(img => img.src);

    return {
        productName:       document.getElementById('productName')?.value       ?? '',
        description:       document.getElementById('description')?.value       ?? '',
        oldPrice:          document.getElementById('oldPrice')?.value           ?? '',
        newPrice:          document.getElementById('newPrice')?.value           ?? '',
        stockQuantity:     document.getElementById('stockQuantity')?.value      ?? '',
        sku:               document.getElementById('sku')?.value                ?? '',
        categoryName:      document.getElementById('categoryName')?.value       ?? '',
        tags:              document.getElementById('tags')?.value               ?? '',
        promotionalLabels: document.getElementById('promotionalLabels')?.value  ?? '',
        discountLabels:    document.getElementById('discountLabels')?.value     ?? '',
        specialCategories: document.getElementById('specialCategories')?.value  ?? '',
        additionalInfo,
        customizations,
        imagePreviews,   // base64 data URLs from this session
        editId: EDIT_ID,
    };
}

// ─────────────────────────────────────────────────────────────
//  LIVE PREVIEW via iframe + postMessage (true style isolation)
// ─────────────────────────────────────────────────────────────
const desktopBtn = document.getElementById('desktopView');
const mobileBtn = document.getElementById('mobileView');
const iframe = document.getElementById('previewFrame');

desktopBtn.addEventListener('click', () => {
    iframe.style.width = '100%';

    desktopBtn.classList.add('active');
    mobileBtn.classList.remove('active');
});

mobileBtn.addEventListener('click', () => {
    iframe.style.width = '50%';

    mobileBtn.classList.add('active');
    desktopBtn.classList.remove('active');
});

const previewFrame   = document.getElementById('previewFrame');
const previewSpinner = document.getElementById('previewSpinner');
let previewTimer     = null;
let iframeReady      = false;

// Wait for the iframe to signal it's ready to receive data
window.addEventListener('message', (e) => {
    if (e.data === 'preview-ready') {
        iframeReady = true;
        doPreview();  // send initial data as soon as iframe loads
    }
});

function schedulePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(doPreview, 350);
}

function doPreview() {
    if (!iframeReady || !previewFrame.contentWindow) return;
    previewSpinner.style.display = 'block';
    previewFrame.contentWindow.postMessage({
        type: 'preview-update',
        data: collectFormData(),
    }, '*');
}

// Hide spinner once iframe confirms it rendered
window.addEventListener('message', (e) => {
    if (e.data === 'preview-rendered') {
        previewSpinner.style.display = 'none';
    }
});

// ─────────────────────────────────────────────────────────────
//  WATCH INPUTS
// ─────────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    prefillForm();

    // All simple inputs & textareas
    document.querySelectorAll('input:not([type=file]):not([type=radio]), textarea')
        .forEach(el => el.addEventListener('input', schedulePreview));

    // Table "Add Row" buttons — delegate
    document.getElementById('addAdditionalInfoRow')?.addEventListener('click', () => {
        const tbody = document.querySelector('#additionalInfosTable tbody');
        const tr = makeInfoRow();
        tbody.appendChild(tr);
        schedulePreview();
    });

    document.getElementById('addCustomizationRow')?.addEventListener('click', () => {
        const tbody = document.querySelector('#customizationTable tbody');
        const tr = makeCustomRow();
        tbody.appendChild(tr);
        schedulePreview();
    });

    // Existing "Remove" buttons
    document.querySelectorAll('.removeAdditionalInfoRow').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('tr').remove();
            schedulePreview();
        });
    });
    document.querySelectorAll('.removeCustomizationRow').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('tr').remove();
            schedulePreview();
        });
    });

    // Attach input listeners to initial table rows
    document.querySelectorAll('#additionalInfosTable input, #customizationTable input')
        .forEach(i => i.addEventListener('input', schedulePreview));

    // Initial preview
    schedulePreview();
});

// ─────────────────────────────────────────────────────────────
//  IMAGE UPLOAD HANDLING
// ─────────────────────────────────────────────────────────────
const imageUpload   = document.getElementById('imageUpload');
const imagePreviews = document.getElementById('imagePreviews');
const imageError    = document.getElementById('imageError');
const uploadStatus  = document.getElementById('uploadStatus');

imageUpload.addEventListener('change', () => {
    const files = [...imageUpload.files];
    imageError.style.display    = 'none';
    imagePreviews.innerHTML     = '';

    if (files.length > 5) {
        imageError.style.display = 'block';
        imageUpload.value        = '';
        return;
    }

    let loadedCount = 0;

    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                loadedCount++;
                // if (Math.abs(img.width - img.height) > 10) {
                //     imageError.style.display = 'block';
                //     return;
                // }
                const thumb     = document.createElement('img');
                thumb.src       = e.target.result;
                thumb.title     = file.name;
                imagePreviews.appendChild(thumb);

                if (loadedCount === files.length) {
                    schedulePreview();   // update preview with first image
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
});

// ─────────────────────────────────────────────────────────────
//  SUBMIT / SAVE
// ─────────────────────────────────────────────────────────────
document.getElementById('submitBtn')?.addEventListener('click', async () => {
    // If the step wizard is not on last step, let main.js handle "Next"
    // We only intercept submit on the final step.
    const checkedStage = document.querySelector('input[name="stage"]:checked');
    if (checkedStage && checkedStage.id !== 'six') return;   // let wizard advance

    const data          = collectFormData();
    data.action         = EDIT_MODE ? 'updateProduct' : 'insertProduct';
    data.productId      = EDIT_ID;

    // Attach raw files as base64 for upload
    const files  = [...(imageUpload.files || [])];
    const b64s   = await Promise.all(files.map(fileToBase64));
    data.newImages = b64s;

    uploadStatus.style.display = 'block';
    document.getElementById('submitBtn').disabled = true;

    try {
        const res  = await fetch('actions.php?action=' + data.action, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data),
        });
        const json = await res.json();

        if (json.success || json.message == '1') {
            alert(EDIT_MODE ? 'Product updated!' : 'Product added!');
            if (!EDIT_MODE && json.productId) {
                window.location.href = `?page=details&product_id=${json.productId}`;
            }
        } else {
            alert('Error: ' + (json.message || 'Unknown error'));
        }
    } catch (err) {
        console.error(err);
        alert('Network error, please try again.');
    } finally {
        uploadStatus.style.display = 'none';
        document.getElementById('submitBtn').disabled = false;
    }
});

function fileToBase64(file) {
    return new Promise((res, rej) => {
        const r = new FileReader();
        r.onload  = e => res(e.target.result.split(',')[1]);
        r.onerror = () => rej(new Error('Read failed'));
        r.readAsDataURL(file);
    });
}

})(); // end IIFE
</script>
</body>
</html>