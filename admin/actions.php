<?php

    include("functions.php");
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === "insertProductDetails") {
        // Get the input data
        $productName = isset($_POST['productName']) ? $_POST['productName'] : null;
        $sku = isset($_POST['sku']) ? $_POST['sku'] : null;
        $description = isset($_POST['description']) ? $_POST['description'] : null;
        $oldPrice = isset($_POST['oldPrice']) ? $_POST['oldPrice'] : null;
        $newPrice = isset($_POST['newPrice']) ? $_POST['newPrice'] : null;
        $stockQuantity = isset($_POST['stockQuantity']) ? $_POST['stockQuantity'] : null;
        $categoryName = isset($_POST['categoryName']) ? $_POST['categoryName'] : null;
        // Prepare the JSON fields as strings
        $tags = $tags = isset($_POST['tags']) ? json_decode($_POST['tags'], true) : [];
        $promotionalLabels = isset($_POST['promotionalLabels']) ? json_decode($_POST['promotionalLabels'], true) : [];
        $discountLabels = isset($_POST['discountLabels']) ? json_decode($_POST['discountLabels'], true) : [];
        $additionalInfos = isset($_POST['additionalInfos']) ? json_decode($_POST['additionalInfos'], true) : [];
        $customizations = isset($_POST['customizations']) ? json_decode($_POST['customizations'], true) : [];
        $specialCategories = isset($_POST['specialCategories']) ? json_decode($_POST['specialCategories'], true) : [];

        require('../../public_html/imagekit-sdk/vendor/autoload.php');
    
        $imageKit = new ImageKit\ImageKit(
            "public_ATmw8NT1gHxm6Mzt6d8oicFMJ/k=",
            "private_M8laZw59kBD1UuCzwb2WsMYI8Zo=",
            "https://ik.imagekit.io/umjnzfgqh/"
        );

        // Start transaction
        mysqli_begin_transaction($link);
        try {
            // Step 1: Check if category exists
            error_log("Checking for category: $categoryName");
            $stmt = mysqli_prepare($link, "SELECT CategoryId FROM categories WHERE LOWER(CategoryName) = LOWER(TRIM(?)) LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $categoryName);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $categoryId);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
    
            // If category doesn't exist, insert it
            if (!$categoryId) {
                error_log("Category not found, inserting new category");
                $stmt = mysqli_prepare($link, "INSERT INTO categories (CategoryName, CreatedAt) VALUES (?, NOW())");
                mysqli_stmt_bind_param($stmt, 's', $categoryName);
                mysqli_stmt_execute($stmt);
                $categoryId = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
            }
    
            // Step 2: Insert product
            error_log("Inserting product details for: " . $data['productName']);
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

            // Step 2.1: Handle and insert multiple images
            error_log("Processing images for productId: $productId");
            $folderPath = "shop/products/categoryId-$categoryId/productId-$productId/";

            if (isset($_FILES['productImages']) && !empty($_FILES['productImages']['name'][0])) {
                foreach ($_FILES['productImages']['tmp_name'] as $index => $tmpName) {
                    $imageName = $_FILES['productImages']['name'][$index];
                    error_log("Uploading image: $imageName");

                    // Upload to ImageKit
                    $uploadFile = $imageKit->uploadFiles([
                        "file" => fopen($tmpName, 'r'), 
                        "fileName" => $imageName,
                        "folder" => $folderPath,
                        "useUniqueFileName" => true
                    ]);
                    error_log("Image upload response: " . print_r($uploadFile, true));
                }

                if ($uploadFile->responseMetadata['statusCode'] === 200) {
                    error_log("Image upload successful, updating database"); 
                    // Store folder path in the ProductImage column
                    $stmt = mysqli_prepare($link, "UPDATE products SET ProductImage = ? WHERE ProductId = ?");
                    mysqli_stmt_bind_param($stmt, 'si', $folderPath, $productId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                            
                } else {
                    error_log("Image upload failed: " . $uploadFile->message); 
                    echo json_encode(['success' => false, 'message' => "Image upload failed: " . $uploadFile->message]);
                    return;
                }
            } else {
                error_log("No images uploaded for product");
                $response['message'] = "There was a problem - please try again later";
                echo json_encode(['success' => false, 'message' => "There was a problem - please try again later"]);
                return;
            }
    
            // Step 3: Insert tags
            foreach ($tags as $tagName) {
                $tagId = null; 
            
                // Check if the tag already exists
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
            
                // Insert into producttags
                $stmt = mysqli_prepare($link, "INSERT INTO producttags (ProductId, TagId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $tagId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
    
            // Step 4: Insert promotional labels
            foreach ($promotionalLabels as $promoLabelName) {
                $stmt = mysqli_prepare($link, "SELECT Id FROM promotionallabels WHERE Name = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $promoLabelName);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $promoLabelId);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
    
                // If promotional label doesn't exist, insert it
                if (!$promoLabelId) {
                    $stmt = mysqli_prepare($link, "INSERT INTO promotionallabels (Name) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, 's', $promoLabelName);
                    mysqli_stmt_execute($stmt);
                    $promoLabelId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);
                }
    
                // Insert into products_promotionallabels
                $stmt = mysqli_prepare($link, "INSERT INTO products_promotionallabels (ProductId, PromotionalLabelId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $promoLabelId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
    
            // Step 5: Insert discount labels
            foreach ($discountLabels as $discountLabelName) {
                $stmt = mysqli_prepare($link, "SELECT Id FROM discountlabels WHERE Name = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $discountLabelName);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $discountLabelId);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
    
                // If discount label doesn't exist, insert it
                if (!$discountLabelId) {
                    $stmt = mysqli_prepare($link, "INSERT INTO discountlabels (Name) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, 's', $discountLabelName);
                    mysqli_stmt_execute($stmt);
                    $discountLabelId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);
                }
    
                // Insert into products_discountlabels
                $stmt = mysqli_prepare($link, "INSERT INTO products_discountlabels (ProductId, DiscountLabelId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $discountLabelId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
    
            // Step 6: Insert additional info
            if (!empty($additionalInfos)) {
                $additionalInfoArray = array();
            
                foreach ($additionalInfos as $info) {
                    $infoKey = $info['key'];
                    $infoValue = $info['value'];
                    $additionalInfoArray[$infoKey] = $infoValue;
                }
            
                $additionalInfoJson = json_encode($additionalInfoArray);
            
                $stmt = mysqli_prepare($link, "INSERT INTO product_additionalinfo (ProductId, AdditionalInfo) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'is', $productId, $additionalInfoJson);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            // Step 7: Insert customizations
            if (!empty($customizations)) {
                $customizationArray = array();
            
                foreach ($customizations as $customization) {
                    $customizationOption = $customization['option'];
                    $customizationValues = $customization['values'];
                    $customizationArray[$customizationOption] = $customizationValues;
                }
            
                $customizationJson = json_encode($customizationArray);
            
                $stmt = mysqli_prepare($link, "INSERT INTO product_customizations (ProductId, CustomizationOption) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'is', $productId, $customizationJson);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
    
            // Step 8: Insert special categories
            foreach ($specialCategories as $specialCategoryName) {
                $stmt = mysqli_prepare($link, "SELECT Id FROM specialcategories WHERE Name = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, 's', $specialCategoryName);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $specialCategoryId);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
    
                // If special category doesn't exist, insert it
                if (!$specialCategoryId) {
                    $stmt = mysqli_prepare($link, "INSERT INTO specialcategories (Name) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, 's', $specialCategoryName);
                    mysqli_stmt_execute($stmt);
                    $specialCategoryId = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);
                }
    
                // Insert into product_specialcategories
                $stmt = mysqli_prepare($link, "INSERT INTO product_specialcategories (ProductId, SpecialCategoryId) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $productId, $specialCategoryId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
    
            // Step 9: Commit the transaction
            mysqli_commit($link);
            echo json_encode(['success' => true, 'message' => 'Product inserted successfully!']);
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($link);
            error_log("Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
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
    
    mysqli_close($link);