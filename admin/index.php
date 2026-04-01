<?php
	include("functions.php");
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="assets/css/styles.css" />

    <title>Ecommerce Website</title>
  </head>
  <body>
	<div class="container">
		<div class="form-container">
  			<div class="form">
	<h2>Product Entry Form</h2>
	<p>Please fill in the details for the new product.</p>

	<input id="one" type="radio" name="stage" checked="checked" />
	<input id="two" type="radio" name="stage" />
	<input id="three" type="radio" name="stage" />
	<input id="four" type="radio" name="stage" />
	<input id="five" type="radio" name="stage" />
	<input id="six" type="radio" name="stage" />

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
		<div data-panel="one">
			<h4>Product Information</h4>
			<input type="text" placeholder="Product Name" id="productName" required />
			<textarea 
				placeholder="Description"
				id="description"
				required
			></textarea>
		</div>
		<div data-panel="two">
			<h4>Pricing</h4>
			<input type="number" placeholder="Old Price" id="oldPrice" />
			<input type="number" placeholder="New Price" id="newPrice" required />
			<input type="number" placeholder="Stock Quantity" id="stockQuantity" required />
		</div>
		<div data-panel="three">
			<h4>Media</h4>
			<input type="file" id="imageUpload" accept="image/*" multiple max="5" />
			<div id="imagePreviews"></div>
			<div id="imageError" style="color: red; display: none;">Only square images are allowed. Maximum 5 images.</div>
			<div id="uploadStatus" style="display: none; color: blue;">Uploading images...</div>
		</div>
		<div data-panel="four">
			<h4>Categorization</h4>
			<input type="text" placeholder="Category Name" id="categoryName" required />
			<div id="categorySuggestions" style="display:none;"></div>
			<input type="text" placeholder="SKU" id="sku" required />
		</div>
		<div data-panel="five">
			<h4>Tags & Labels</h4>
			<input type="text" placeholder="Tags (comma-separated)" id="tags" />
			<div id="tagSuggestions" style="display:none;"></div>
			<input type="text" placeholder="Promotional Labels (comma-separated)" id="promotionalLabels" />
			<div id="promoSuggestions" style="display:none;"></div>
			<input type="text" placeholder="Discount Labels (comma-separated)" id="discountLabels" />
			<div id="discSuggestions" style="display:none;"></div>
		</div>
		<div data-panel="six">
			<h4>Additional Info & Customizations</h4>
			<h5>Additional Info</h5>
			<table id="additionalInfosTable">
				<thead>
					<tr>
						<th>Key</th>
						<th>Value</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input type="text" placeholder="Key" class="key" /></td>
						<td><input type="text" placeholder="Value" class="value" /></td>
						<td><button class="removeAdditionalInfoRow">Remove</button></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3">
							<button id="addAdditionalInfoRow">Add Row</button>
						</td>
					</tr>
				</tfoot>
    		</table>
			<h5>Customizations</h5>
			<table id="customizationTable">
				<thead>
					<tr>
						<th>Option</th>
						<th>Value</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input type="text" placeholder="Option" class="option" /></td>
						<td><input type="text" placeholder="Price" class="values" /></td>
						<td><button class="removeCustomizationRow">Remove</button></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3">
							<button id="addCustomizationRow">Add Row</button>
						</td>
					</tr>
				</tfoot>
    		</table>
    	
			<input type="text" placeholder="Special Categories (comma-separated)" id="specialCategories" />
			<div id="specialCategorySuggestions" style="display:none;"></div>
		</div>
	</div>

	<button id="submitBtn">Next</button>
			</div>
		</div>
		<div class="preview-container">
			<iframe id="previewFrame"></iframe>
		</div>
	</div>

    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>

	<script>
		window.addEventListener("DOMContentLoaded", () => {
  
  function updatePreview() {
    const params = new URLSearchParams({
      preview: 1,
      name: document.getElementById("productName").value,
      desc: document.getElementById("description").value,
      newPrice: document.getElementById("newPrice").value,
      oldPrice: document.getElementById("oldPrice").value,
      tags: document.getElementById("tags").value,
      stock: document.getElementById("stockQuantity").value,
      category: document.getElementById("categoryName").value,
    });

      const url = `../index.php?page=details&preview=1&product_id=0&product_name=preview&${params.toString()}`;
	  document.getElementById("previewFrame").src = url;
  }

  let timeout;

  function updatePreviewDebounced() {
    clearTimeout(timeout);
    timeout = setTimeout(updatePreview, 300);
  }

  const inputs = document.querySelectorAll("input, textarea");

  inputs.forEach((el) => {
    el.addEventListener("input", updatePreviewDebounced);
  });

  // initial load
  updatePreview();
});

imageUpload.addEventListener("change", () => {
  const file = imageUpload.files[0];

  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      const params = new URLSearchParams({
        preview: 1,
        image: e.target.result
      });

      const url = `../index.php?page=details&preview=1&product_id=0&product_name=preview&${params.toString()}`;
	  document.getElementById("previewFrame").src = url;
    };
    reader.readAsDataURL(file);
  }
});

	</script>
  </body>
</html>