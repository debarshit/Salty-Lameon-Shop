<?php
session_start();

// 🔒 Restrict access
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     die("Access denied");
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['category_name']);
    $categoryImage = trim($_POST['category_image']); // optional

    if (empty($categoryName)) {
        $error = "Category name is required";
    } else {
        $stmt = mysqli_prepare($shopLink, 
            "INSERT INTO categories (CategoryName, CategoryImage, IsAvailable) VALUES (?, ?, 1)"
        );

        mysqli_stmt_bind_param($stmt, "ss", $categoryName, $categoryImage);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php"); // redirect after success
            exit;
        } else {
            $error = "Error: " . mysqli_error($shopLink);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Category</title>
</head>
<body>

<h2>Add New Category</h2>

<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST">
    <label>Category Name:</label><br>
    <input type="text" name="category_name" required><br><br>

    <label>Category Image URL (optional):</label><br>
    <input type="text" name="category_image"><br><br>

    <button type="submit">Add Category</button>
</form>

</body>
</html>