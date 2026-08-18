<?php

    include("functions.php");
    include("../functions.php");
    
    // if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === "insertProductDetails") {
    //     // Get the input data
    //     $productName = isset($_POST['productName']) ? $_POST['productName'] : null;
    //     $sku = isset($_POST['sku']) ? $_POST['sku'] : null;
    //     $description = isset($_POST['description']) ? $_POST['description'] : null;
    //     $oldPrice = isset($_POST['oldPrice']) ? $_POST['oldPrice'] : null;
    //     $newPrice = isset($_POST['newPrice']) ? $_POST['newPrice'] : null;
    //     $stockQuantity = isset($_POST['stockQuantity']) ? $_POST['stockQuantity'] : null;
    //     $categoryName = isset($_POST['categoryName']) ? $_POST['categoryName'] : null;
    //     // Prepare the JSON fields as strings
    //     $tags = $tags = isset($_POST['tags']) ? json_decode($_POST['tags'], true) : [];
    //     $promotionalLabels = isset($_POST['promotionalLabels']) ? json_decode($_POST['promotionalLabels'], true) : [];
    //     $discountLabels = isset($_POST['discountLabels']) ? json_decode($_POST['discountLabels'], true) : [];
    //     $additionalInfos = isset($_POST['additionalInfos']) ? json_decode($_POST['additionalInfos'], true) : [];
    //     $customizations = isset($_POST['customizations']) ? json_decode($_POST['customizations'], true) : [];
    //     $specialCategories = isset($_POST['specialCategories']) ? json_decode($_POST['specialCategories'], true) : [];

    //     // require('../../public_html/imagekit-sdk/vendor/autoload.php');
    
    //     // $imageKit = new ImageKit\ImageKit(
    //     //     "public_ATmw8NT1gHxm6Mzt6d8oicFMJ/k=",
    //     //     "private_M8laZw59kBD1UuCzwb2WsMYI8Zo=",
    //     //     "https://ik.imagekit.io/umjnzfgqh/"
    //     // );

    //     // Start transaction
    //     mysqli_begin_transaction($link);
    //     try {
    //         // Step 1: Check if category exists
    //         error_log("Checking for category: $categoryName");
    //         $stmt = mysqli_prepare($link, "SELECT CategoryId FROM categories WHERE LOWER(CategoryName) = LOWER(TRIM(?)) LIMIT 1");
    //         mysqli_stmt_bind_param($stmt, 's', $categoryName);
    //         mysqli_stmt_execute($stmt);
    //         mysqli_stmt_bind_result($stmt, $categoryId);
    //         mysqli_stmt_fetch($stmt);
    //         mysqli_stmt_close($stmt);
    
    //         // If category doesn't exist, insert it
    //         if (!$categoryId) {
    //             error_log("Category not found, inserting new category");
    //             $stmt = mysqli_prepare($link, "INSERT INTO categories (CategoryName, CreatedAt) VALUES (?, NOW())");
    //             mysqli_stmt_bind_param($stmt, 's', $categoryName);
    //             mysqli_stmt_execute($stmt);
    //             $categoryId = mysqli_insert_id($link);
    //             mysqli_stmt_close($stmt);
    //         }
    
    //         // Step 2: Insert product
    //         error_log("Inserting product details for: " . $_POST['productName']);
    //         $stmt = mysqli_prepare($link, "INSERT INTO products (ProductName, SKU, ProductDescription, OldPrice, NewPrice, StockQuantity, CategoryId, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    //         mysqli_stmt_bind_param($stmt, 'sssddii', 
    //             $productName, 
    //             $sku, 
    //             $description, 
    //             $oldPrice, 
    //             $newPrice, 
    //             $stockQuantity, 
    //             $categoryId
    //         );
    //         mysqli_stmt_execute($stmt);
    //         $productId = mysqli_insert_id($link);
    //         mysqli_stmt_close($stmt);

    //         // Step 2.1: Handle and insert multiple images
    //         error_log("Processing images for productId: $productId");
    //         $folderPath = "shop/products/categoryId-$categoryId/productId-$productId/";

    //         if (isset($_FILES['productImages']) && !empty($_FILES['productImages']['name'][0])) {
    //             foreach ($_FILES['productImages']['tmp_name'] as $index => $tmpName) {
    //                 $imageName = $_FILES['productImages']['name'][$index];
    //                 error_log("Uploading image: $imageName");

    //                 // Upload to ImageKit
    //                 $uploadFile = $imageKit->uploadFiles([
    //                     "file" => fopen($tmpName, 'r'), 
    //                     "fileName" => $imageName,
    //                     "folder" => $folderPath,
    //                     "useUniqueFileName" => true
    //                 ]);
    //                 error_log("Image upload response: " . print_r($uploadFile, true));
    //             }
    //             // if (!empty($newImages)) {
    //             // foreach ($newImages as $index => $base64) {
    //             //     $uploadResponse = uploadImageToImageKit($base64, $folderPath, "image_{$index}.jpg");
    //             //     if (empty($uploadResponse['fileId'])) {
    //             //         throw new Exception("Image upload failed for image_{$index}");
    //             //     }
    //             //     error_log("Image upload successful: " . $uploadResponse['url']);
    //             // }
    //             // }

    //             // Store folder path in the ProductImage column
    //             $stmt = mysqli_prepare($link, "UPDATE products SET ProductImage = ? WHERE ProductId = ?");
    //             mysqli_stmt_bind_param($stmt, 'si', $folderPath, $productId);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_close($stmt);
    //         } else {
    //             throw new Exception("No images provided");
    //         }
    
    //         // Step 3: Insert tags
    //         foreach ($tags as $tagName) {
    //             $tagId = null; 
            
    //             // Check if the tag already exists
    //             $stmt = mysqli_prepare($link, "SELECT TagId FROM tags WHERE TagName = ? LIMIT 1");
    //             mysqli_stmt_bind_param($stmt, 's', $tagName);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_bind_result($stmt, $tagId);
    //             mysqli_stmt_fetch($stmt);
    //             mysqli_stmt_close($stmt);

    //             if ($tagId === null) {
    //                 $stmt = mysqli_prepare($link, "INSERT INTO tags (TagName) VALUES (?)");
    //                 mysqli_stmt_bind_param($stmt, 's', $tagName);
    //                 mysqli_stmt_execute($stmt);
    //                 $tagId = mysqli_insert_id($link);
    //                 mysqli_stmt_close($stmt);
    //             }
            
    //             // Insert into producttags
    //             $stmt = mysqli_prepare($link, "INSERT INTO producttags (ProductId, TagId) VALUES (?, ?)");
    //             mysqli_stmt_bind_param($stmt, 'ii', $productId, $tagId);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_close($stmt);
    //         }
    
    //         // Step 4: Insert promotional labels
    //         foreach ($promotionalLabels as $promoLabelName) {
    //             $stmt = mysqli_prepare($link, "SELECT Id FROM promotionallabels WHERE Name = ? LIMIT 1");
    //             mysqli_stmt_bind_param($stmt, 's', $promoLabelName);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_bind_result($stmt, $promoLabelId);
    //             mysqli_stmt_fetch($stmt);
    //             mysqli_stmt_close($stmt);
    
    //             // If promotional label doesn't exist, insert it
    //             if (!$promoLabelId) {
    //                 $stmt = mysqli_prepare($link, "INSERT INTO promotionallabels (Name) VALUES (?)");
    //                 mysqli_stmt_bind_param($stmt, 's', $promoLabelName);
    //                 mysqli_stmt_execute($stmt);
    //                 $promoLabelId = mysqli_insert_id($link);
    //                 mysqli_stmt_close($stmt);
    //             }
    
    //             // Insert into products_promotionallabels
    //             $stmt = mysqli_prepare($link, "INSERT INTO products_promotionallabels (ProductId, PromotionalLabelId) VALUES (?, ?)");
    //             mysqli_stmt_bind_param($stmt, 'ii', $productId, $promoLabelId);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_close($stmt);
    //         }
    
    //         // Step 5: Insert discount labels
    //         foreach ($discountLabels as $discountLabelName) {
    //             $stmt = mysqli_prepare($link, "SELECT Id FROM discountlabels WHERE Name = ? LIMIT 1");
    //             mysqli_stmt_bind_param($stmt, 's', $discountLabelName);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_bind_result($stmt, $discountLabelId);
    //             mysqli_stmt_fetch($stmt);
    //             mysqli_stmt_close($stmt);
    
    //             // If discount label doesn't exist, insert it
    //             if (!$discountLabelId) {
    //                 $stmt = mysqli_prepare($link, "INSERT INTO discountlabels (Name) VALUES (?)");
    //                 mysqli_stmt_bind_param($stmt, 's', $discountLabelName);
    //                 mysqli_stmt_execute($stmt);
    //                 $discountLabelId = mysqli_insert_id($link);
    //                 mysqli_stmt_close($stmt);
    //             }
    
    //             // Insert into products_discountlabels
    //             $stmt = mysqli_prepare($link, "INSERT INTO products_discountlabels (ProductId, DiscountLabelId) VALUES (?, ?)");
    //             mysqli_stmt_bind_param($stmt, 'ii', $productId, $discountLabelId);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_close($stmt);
    //         }
    
    //         // Step 6: Insert additional info
    //         if (!empty($additionalInfos)) {
    //             $additionalInfoArray = array();
            
    //             foreach ($additionalInfos as $info) {
    //                 $infoKey = $info['key'];
    //                 $infoValue = $info['value'];
    //                 $additionalInfoArray[$infoKey] = $infoValue;
    //             }
            
    //             $additionalInfoJson = json_encode($additionalInfoArray);
            
    //             $stmt = mysqli_prepare($link, "INSERT INTO product_additionalinfo (ProductId, AdditionalInfo) VALUES (?, ?)");
    //             mysqli_stmt_bind_param($stmt, 'is', $productId, $additionalInfoJson);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_close($stmt);
    //         }

    //         // Step 7: Insert customizations
    //         if (!empty($customizations)) {
    //             $customizationArray = array();
            
    //             foreach ($customizations as $customization) {
    //                 $customizationOption = $customization['option'];
    //                 $customizationValues = $customization['values'];
    //                 $customizationArray[$customizationOption] = $customizationValues;
    //             }
            
    //             $customizationJson = json_encode($customizationArray);
            
    //             $stmt = mysqli_prepare($link, "INSERT INTO product_customizations (ProductId, CustomizationOption) VALUES (?, ?)");
    //             mysqli_stmt_bind_param($stmt, 'is', $productId, $customizationJson);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_close($stmt);
    //         }
    
    //         // Step 8: Insert special categories
    //         foreach ($specialCategories as $specialCategoryName) {
    //             $stmt = mysqli_prepare($link, "SELECT Id FROM specialcategories WHERE Name = ? LIMIT 1");
    //             mysqli_stmt_bind_param($stmt, 's', $specialCategoryName);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_bind_result($stmt, $specialCategoryId);
    //             mysqli_stmt_fetch($stmt);
    //             mysqli_stmt_close($stmt);
    
    //             // If special category doesn't exist, insert it
    //             if (!$specialCategoryId) {
    //                 $stmt = mysqli_prepare($link, "INSERT INTO specialcategories (Name) VALUES (?)");
    //                 mysqli_stmt_bind_param($stmt, 's', $specialCategoryName);
    //                 mysqli_stmt_execute($stmt);
    //                 $specialCategoryId = mysqli_insert_id($link);
    //                 mysqli_stmt_close($stmt);
    //             }
    
    //             // Insert into product_specialcategories
    //             $stmt = mysqli_prepare($link, "INSERT INTO product_specialcategories (ProductId, SpecialCategoryId) VALUES (?, ?)");
    //             mysqli_stmt_bind_param($stmt, 'ii', $productId, $specialCategoryId);
    //             mysqli_stmt_execute($stmt);
    //             mysqli_stmt_close($stmt);
    //         }
    
    //         // Step 9: Commit the transaction
    //         mysqli_commit($link);
    //         echo json_encode(['success' => true, 'message' => 'Product inserted successfully!']);
    //     } catch (Exception $e) {
    //         // Rollback on error
    //         mysqli_rollback($link);
    //         error_log("Error: " . $e->getMessage());
    //         echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    //     }
    // }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === "insertProductDetails") {
    // ── Read JSON body ────────────────────────────────────────
    $rawInput = file_get_contents('php://input');
    error_log("Raw input length: " . strlen($rawInput));

    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }

    error_log("Decoded input: " . print_r($input, true));

    // ── Extract fields ────────────────────────────────────────
    $productName       = trim($input['productName']   ?? '') ?: null;
    $sku               = trim($input['sku']           ?? '') ?: null;
    $description       = trim($input['description']   ?? '') ?: null;
    $oldPrice          = $input['oldPrice']  !== '' ? $input['oldPrice']  : null;
    $newPrice          = $input['newPrice']  !== '' ? $input['newPrice']  : null;
    $stockQuantity = isset($input['stockQuantity']) && $input['stockQuantity'] !== '' ? $input['stockQuantity'] : null;
    $categoryName      = trim($input['categoryName']  ?? '') ?: null;
    $newImages         = $input['newImages']  ?? [];

    // Comma-separated strings → arrays
    $tags              = array_values(array_filter(array_map('trim', explode(',', $input['tags']              ?? ''))));
    $promotionalLabels = array_values(array_filter(array_map('trim', explode(',', $input['promotionalLabels'] ?? ''))));
    $discountLabels    = array_values(array_filter(array_map('trim', explode(',', $input['discountLabels']    ?? ''))));
    $specialCategories = array_values(array_filter(array_map('trim', explode(',', $input['specialCategories'] ?? ''))));

    // Objects {key: value}
    $additionalInfos   = $input['additionalInfo']  ?? [];
    $customizations    = $input['customizations']  ?? [];

    error_log("productName: " . var_export($productName, true));
    error_log("categoryName: " . var_export($categoryName, true));
    error_log("newPrice: " . var_export($newPrice, true));
    error_log("sku: " . var_export($sku, true));
    error_log("tags: " . var_export($tags, true));
    error_log("newImages count: " . count($newImages));

    // ── Validate required fields ──────────────────────────────
    if (!$productName || !$sku || !$description || !$newPrice || !$categoryName) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // ── Transaction ───────────────────────────────────────────
    mysqli_begin_transaction($link);
    try {
        // Step 1: Category
        error_log("Looking up category: $categoryName");
        $stmt = mysqli_prepare($link, "SELECT CategoryId FROM categories WHERE LOWER(CategoryName) = LOWER(TRIM(?)) LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $categoryName);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $categoryId);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        error_log("Category lookup result: " . var_export($categoryId, true));

        if (!$categoryId) {
            error_log("Inserting new category: $categoryName");
            $stmt = mysqli_prepare($link, "INSERT INTO categories (CategoryName, CreatedAt) VALUES (?, NOW())");
            mysqli_stmt_bind_param($stmt, 's', $categoryName);
            mysqli_stmt_execute($stmt);
            $categoryId = mysqli_insert_id($link);
            mysqli_stmt_close($stmt);
            error_log("New categoryId: $categoryId");
        }

        // Step 2: Product
        error_log("Inserting product: $productName");
        $stmt = mysqli_prepare($link, "INSERT INTO products (ProductName, SKU, ProductDescription, OldPrice, NewPrice, StockQuantity, CategoryId, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, 'sssddii',
            $productName,
            $sku,
            $description,
            $oldPrice,
            $newPrice,
            $stockQuantity,
            $categoryId
        );
        mysqli_stmt_execute($stmt);
        $productId = mysqli_insert_id($link);
        mysqli_stmt_close($stmt);
        error_log("New productId: $productId");

        // Step 2.1: Images
        $folderPath = "shop/products/categoryId-$categoryId/productId-$productId/";
        error_log("Image folder: $folderPath, image count: " . count($newImages));

        if (!empty($newImages)) {
            foreach ($newImages as $index => $base64) {
                $uploadResponse = uploadImageToImageKit($base64, $folderPath, "image_{$index}.jpg");
                if (empty($uploadResponse['fileId'])) {
                    throw new Exception("Image upload failed for image_{$index}");
                }
                error_log("Uploaded image_{$index}: " . $uploadResponse['url']);
            }
            $stmt = mysqli_prepare($link, "UPDATE products SET ProductImage = ? WHERE ProductId = ?");
            mysqli_stmt_bind_param($stmt, 'si', $folderPath, $productId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("No images provided");
        }

        // Step 3: Tags
        foreach ($tags as $tagName) {
            $tagId = null;
            $stmt = mysqli_prepare($link, "SELECT TagId FROM tags WHERE TagName = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $tagName);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $tagId);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($tagId === null) {
                $stmt = mysqli_prepare($link, "INSERT INTO tags (TagName) VALUES (?)");
                mysqli_stmt_bind_param($stmt, 's', $tagName);
                mysqli_stmt_execute($stmt);
                $tagId = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($link, "INSERT INTO producttags (ProductId, TagId) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'ii', $productId, $tagId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Step 4: Promotional labels
        foreach ($promotionalLabels as $promoLabelName) {
            $promoLabelId = null;
            $stmt = mysqli_prepare($link, "SELECT Id FROM promotionallabels WHERE Name = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $promoLabelName);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $promoLabelId);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (!$promoLabelId) {
                $stmt = mysqli_prepare($link, "INSERT INTO promotionallabels (Name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, 's', $promoLabelName);
                mysqli_stmt_execute($stmt);
                $promoLabelId = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($link, "INSERT INTO products_promotionallabels (ProductId, PromotionalLabelId) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'ii', $productId, $promoLabelId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Step 5: Discount labels
        foreach ($discountLabels as $discountLabelName) {
            $discountLabelId = null;
            $stmt = mysqli_prepare($link, "SELECT Id FROM discountlabels WHERE Name = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $discountLabelName);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $discountLabelId);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (!$discountLabelId) {
                $stmt = mysqli_prepare($link, "INSERT INTO discountlabels (Name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, 's', $discountLabelName);
                mysqli_stmt_execute($stmt);
                $discountLabelId = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($link, "INSERT INTO products_discountlabels (ProductId, DiscountLabelId) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'ii', $productId, $discountLabelId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Step 6: Additional info
        if (!empty($additionalInfos)) {
            $additionalInfoJson = json_encode($additionalInfos);
            $stmt = mysqli_prepare($link, "INSERT INTO product_additionalinfo (ProductId, AdditionalInfo) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $productId, $additionalInfoJson);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Step 7: Customizations
        if (!empty($customizations)) {
            $customizationJson = json_encode($customizations);
            $stmt = mysqli_prepare($link, "INSERT INTO product_customizations (ProductId, CustomizationOption) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $productId, $customizationJson);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Step 8: Special categories
        foreach ($specialCategories as $specialCategoryName) {
            $specialCategoryId = null;
            $stmt = mysqli_prepare($link, "SELECT Id FROM specialcategories WHERE Name = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $specialCategoryName);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $specialCategoryId);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (!$specialCategoryId) {
                $stmt = mysqli_prepare($link, "INSERT INTO specialcategories (Name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, 's', $specialCategoryName);
                mysqli_stmt_execute($stmt);
                $specialCategoryId = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($link, "INSERT INTO product_specialcategories (ProductId, SpecialCategoryId) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'ii', $productId, $specialCategoryId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Step 9: Commit
        mysqli_commit($link);
        echo json_encode(['success' => true, 'message' => 'Product inserted successfully!', 'productId' => $productId]);

    } catch (Exception $e) {
        mysqli_rollback($link);
        error_log("insertProductDetails error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
    if ($_GET['action'] === "updateProductDetails") {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid payload']);
            exit;
        }

        $productId         = (int)($data['productId'] ?? 0);
        $productName       = $data['productName']       ?? '';
        $sku               = $data['sku']               ?? '';
        $description       = $data['description']       ?? '';
        $oldPrice          = $data['oldPrice']           !== '' ? (float)$data['oldPrice'] : null;
        $newPrice          = (float)($data['newPrice']   ?? 0);
        $stockQuantity = isset($data['stockQuantity']) && $data['stockQuantity'] !== '' ? (int)$data['stockQuantity'] : null;
        $categoryName      = $data['categoryName']       ?? '';
        $tags              = array_filter(array_map('trim', explode(',', $data['tags'] ?? '')));
        $promotionalLabels = array_filter(array_map('trim', explode(',', $data['promotionalLabels'] ?? '')));
        $discountLabels    = array_filter(array_map('trim', explode(',', $data['discountLabels'] ?? '')));
        $specialCategories = array_filter(array_map('trim', explode(',', $data['specialCategories'] ?? '')));
        $additionalInfo    = $data['additionalInfo']    ?? [];   // already key=>value map
        $customizations    = $data['customizations']    ?? [];   // already key=>value map
        $newImages         = $data['newImages']         ?? [];   // base64 strings
        $imageKitFolder    = $data['imageKitFolder']    ?? '';

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }

        mysqli_begin_transaction($link);
        try {
            // ── Step 1: Resolve category ──────────────────────────────────────────
            $stmt = mysqli_prepare($link, "SELECT CategoryId FROM categories WHERE LOWER(CategoryName) = LOWER(TRIM(?)) LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $categoryName);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $categoryId);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (!$categoryId) {
                $stmt = mysqli_prepare($link, "INSERT INTO categories (CategoryName, CreatedAt) VALUES (?, NOW())");
                mysqli_stmt_bind_param($stmt, 's', $categoryName);
                mysqli_stmt_execute($stmt);
                $categoryId = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
            }

            // ── Step 2: Update core product row ───────────────────────────────────
            $stmt = mysqli_prepare($link,
                "UPDATE products
                SET ProductName = ?, SKU = ?, ProductDescription = ?,
                    OldPrice = ?, NewPrice = ?, StockQuantity = ?, CategoryId = ?, UpdatedAt = NOW()
                WHERE ProductId = ?");
            mysqli_stmt_bind_param($stmt, 'sssddiii',
                $productName, $sku, $description,
                $oldPrice, $newPrice, $stockQuantity, $categoryId,
                $productId
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // ── Step 3: Images — only replace if new ones were uploaded ──────────
            if (!empty($newImages)) {
                $folderPath = $imageKitFolder ?: "shop/products/categoryId-{$categoryId}/productId-{$productId}/";

                // Bulk-delete all existing files in the folder first
                if ($imageKitFolder) {
                    deleteImageKitFolder($imageKitFolder);
                }

                // Upload replacements into the same folder
                foreach ($newImages as $index => $base64) {
                    $uploadResponse = uploadImageToImageKit($base64, $folderPath, "image_{$index}.jpg");
                    if (empty($uploadResponse['fileId'])) {
                        throw new Exception("Image upload failed for image_{$index}");
                    }
                }

                // Update the folder reference (in case it was newly derived)
                $stmt = mysqli_prepare($link, "UPDATE products SET ProductImage = ? WHERE ProductId = ?");
                mysqli_stmt_bind_param($stmt, 'si', $folderPath, $productId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            // ── Step 4: Tags — delete all, re-insert ──────────────────────────────
            $stmt = mysqli_prepare($link, "DELETE FROM producttags WHERE ProductId = ?");
            mysqli_stmt_bind_param($stmt, 'i', $productId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            foreach ($tags as $tagName) {
                $tagId = null;
                $stmt = mysqli_prepare($link, "SELECT TagId FROM tags WHERE TagName = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $tagName);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $tagId);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if (!$tagId) {
                    $stmt = mysqli_prepare($link, "INSERT INTO tags (TagName) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, 's', $tagName);
                    mysqli_stmt_execute($stmt);
                    $tagId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare($link, "INSERT INTO producttags (ProductId, TagId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $tagId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            // ── Step 5: Promotional labels — delete all, re-insert ───────────────
            $stmt = mysqli_prepare($link, "DELETE FROM products_promotionallabels WHERE ProductId = ?");
            mysqli_stmt_bind_param($stmt, 'i', $productId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            foreach ($promotionalLabels as $labelName) {
                $labelId = null;
                $stmt = mysqli_prepare($link, "SELECT Id FROM promotionallabels WHERE Name = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $labelName);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $labelId);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if (!$labelId) {
                    $stmt = mysqli_prepare($link, "INSERT INTO promotionallabels (Name) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, 's', $labelName);
                    mysqli_stmt_execute($stmt);
                    $labelId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare($link, "INSERT INTO products_promotionallabels (ProductId, PromotionalLabelId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $labelId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            // ── Step 6: Discount labels — delete all, re-insert ──────────────────
            $stmt = mysqli_prepare($link, "DELETE FROM products_discountlabels WHERE ProductId = ?");
            mysqli_stmt_bind_param($stmt, 'i', $productId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            foreach ($discountLabels as $labelName) {
                $labelId = null;
                $stmt = mysqli_prepare($link, "SELECT Id FROM discountlabels WHERE Name = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $labelName);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $labelId);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if (!$labelId) {
                    $stmt = mysqli_prepare($link, "INSERT INTO discountlabels (Name) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, 's', $labelName);
                    mysqli_stmt_execute($stmt);
                    $labelId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare($link, "INSERT INTO products_discountlabels (ProductId, DiscountLabelId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $labelId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            // ── Step 7: Additional info — upsert ──────────────────────────────────
            if (array_key_exists('additionalInfo', $data)) {
    if (!empty($additionalInfo)) {
    $infoJson = json_encode($additionalInfo);

    // Check if additional info already exists for this product
    $stmt = mysqli_prepare($link, "SELECT COUNT(*) FROM product_additionalinfo WHERE ProductId = ?");
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($count > 0) {
        // Update existing additional info
        $stmt = mysqli_prepare($link, "UPDATE product_additionalinfo SET AdditionalInfo = ? WHERE ProductId = ?");
        mysqli_stmt_bind_param($stmt, 'si', $infoJson, $productId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Insert new additional info
        $stmt = mysqli_prepare($link, "INSERT INTO product_additionalinfo (ProductId, AdditionalInfo) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'is', $productId, $infoJson);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
}

            // ── Step 8: Customizations — upsert ───────────────────────────────────
            if (!empty($customizations)) {
    $customizationJson = json_encode($customizations);

    // Check if customization info already exists for this product
    $stmt = mysqli_prepare($link, "SELECT COUNT(*) FROM product_customizations WHERE ProductId = ?");
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($count > 0) {
        // Update existing customization
        $stmt = mysqli_prepare($link, "UPDATE product_customizations SET CustomizationOption = ? WHERE ProductId = ?");
        mysqli_stmt_bind_param($stmt, 'si', $customizationJson, $productId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Insert new customization
        $stmt = mysqli_prepare($link, "INSERT INTO product_customizations (ProductId, CustomizationOption) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'is', $productId, $customizationJson);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

            // ── Step 9: Special categories — delete all, re-insert ───────────────
            $stmt = mysqli_prepare($link, "DELETE FROM product_specialcategories WHERE ProductId = ?");
            mysqli_stmt_bind_param($stmt, 'i', $productId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            foreach ($specialCategories as $catName) {
                $catId = null;
                $stmt = mysqli_prepare($link, "SELECT Id FROM specialcategories WHERE Name = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $catName);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $catId);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if (!$catId) {
                    $stmt = mysqli_prepare($link, "INSERT INTO specialcategories (Name) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, 's', $catName);
                    mysqli_stmt_execute($stmt);
                    $catId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare($link, "INSERT INTO product_specialcategories (ProductId, SpecialCategoryId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $catId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($link);
            echo json_encode(['success' => true, 'message' => 'Product updated successfully!', 'productId' => $productId]);

        } catch (Exception $e) {
            mysqli_rollback($link);
            error_log("updateProductDetails error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if($_GET['action'] === "fetchCategories") {
        $query = $_GET['query'];

        if ($query !== '') {

            $stmt = mysqli_prepare($link, "SELECT CategoryName FROM categories WHERE LOWER(CategoryName) LIKE LOWER(?) LIMIT 5");
            $likeQuery = "%" . $query . "%";
            mysqli_stmt_bind_param($stmt, 's', $likeQuery);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $categories = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row;
            }

            mysqli_stmt_close($stmt);

            echo json_encode($categories);
        } else {
            echo json_encode([]);
        }
    }

    if($_GET['action'] === "fetchTags") {
        $query = $_GET['query'];
    
        if ($query !== '') {
            $stmt = mysqli_prepare($link, "SELECT TagName FROM tags WHERE LOWER(TagName) LIKE LOWER(?) LIMIT 5");
            $likeQuery = "%" . $query . "%";
            mysqli_stmt_bind_param($stmt, 's', $likeQuery);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
    
            $tags = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $tags[] = $row;
            }
    
            mysqli_stmt_close($stmt);
    
            echo json_encode($tags);
        } else {
            echo json_encode([]);
        }
    }

    if($_GET['action'] === "fetchPromoLabels") {
        $query = $_GET['query'];
    
        if ($query !== '') {
            $stmt = mysqli_prepare($link, "SELECT Name FROM promotionallabels WHERE LOWER(Name) LIKE LOWER(?) LIMIT 5");
            $likeQuery = "%" . $query . "%";
            mysqli_stmt_bind_param($stmt, 's', $likeQuery);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
    
            $labels = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row;
            }
    
            mysqli_stmt_close($stmt);
    
            echo json_encode($labels);
        } else {
            echo json_encode([]);
        }
    }

    if($_GET['action'] === "fetchDiscLabel") {
        $query = $_GET['query'];
    
        if ($query !== '') {
            $stmt = mysqli_prepare($link, "SELECT Name FROM discountlabels WHERE LOWER(Name) LIKE LOWER(?) LIMIT 5");
            $likeQuery = "%" . $query . "%";
            mysqli_stmt_bind_param($stmt, 's', $likeQuery);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
    
            $labels = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row;
            }
    
            mysqli_stmt_close($stmt);
    
            echo json_encode($labels);
        } else {
            echo json_encode([]);
        }
    }

    if ($_GET['action'] === "fetchSpecialCategories") {
        $query = $_GET['query'];
    
        if ($query !== '') {
            $stmt = mysqli_prepare($link, "SELECT Name FROM specialcategories WHERE LOWER(Name) LIKE LOWER(?) LIMIT 5");
            $likeQuery = "%" . $query . "%";
            mysqli_stmt_bind_param($stmt, 's', $likeQuery);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
    
            $categories = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = $row;
            }
    
            mysqli_stmt_close($stmt);
    
            echo json_encode($categories);
        } else {
            echo json_encode([]);
        }
    }

    if ($_GET['action'] === "getProductImages") {
         $folder = $_GET['folder'] ?? '';
        if (!$folder) { echo json_encode(['urls' => []]); exit; }
        $urls = fetchImagesFromImageKit($folder);
        echo json_encode(['urls' => $urls]);
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === "saveAdminPushSubscription") {
        header('Content-Type: application/json');
        
        $accessToken = getAccessTokenFromSession();
        $isAdmin = false;
        $userId = 0;
        if ($accessToken) {
            $role = getUserRoleFromAccessToken($accessToken);
            $isAdmin = ($role === 'admin');
            $userId = getUserIdFromAccessToken($accessToken);
        }

        if (!$isAdmin || !$userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $endpoint = $input['endpoint'] ?? null;
        $p256dh = $input['keys']['p256dh'] ?? null;
        $auth = $input['keys']['auth'] ?? null;

        if (!$endpoint || !$p256dh || !$auth) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid subscription data']);
            exit;
        }

        $stmt = mysqli_prepare($link, "INSERT INTO admin_push_subscriptions (UserId, endpoint, p256dh, auth) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE UserId = ?, p256dh = ?, auth = ?");
        mysqli_stmt_bind_param($stmt, 'isssiss', $userId, $endpoint, $p256dh, $auth, $userId, $p256dh, $auth);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
        }
        mysqli_stmt_close($stmt);
        exit;
    }
    
    mysqli_close($link);