<?php
require_once 'includes/auth/auth.helper.php';

$message = '';
$success = false;

$token = $_GET['token'] ?? '';

if (!$token) {
    die('Invalid or missing reset token.');
}

// STEP 1: Validate token
$stmt = $shopLink->prepare("
    SELECT userId, resetTokenExpiry 
    FROM users 
    WHERE resetToken = ?
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die('Invalid or expired reset token.');
}

if (strtotime($user['resetTokenExpiry']) < time()) {
    die('This reset link has expired.');
}

// STEP 2: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$password || !$confirm) {
        $message = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $shopLink->prepare("
            UPDATE users 
            SET userPassword = ?, resetToken = NULL, resetTokenExpiry = NULL
            WHERE userId = ?
        ");
        $stmt->bind_param("si", $hashed, $user['userId']);
        $stmt->execute();

        $success = true;
        $message = 'Your password has been reset successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f6f6f6;
    }
    .reset-box {
      max-width: 400px;
      margin: 80px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
    }
    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #222;
      color: #fff;
      border: none;
      cursor: pointer;
    }
    .message {
      margin-bottom: 10px;
      color: red;
    }
    .success {
      color: green;
    }
  </style>
</head>
<body>

<div class="reset-box">
  <h2>Reset Password</h2>

  <?php if ($message): ?>
    <div class="message <?= $success ? 'success' : '' ?>">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <?php if (!$success): ?>
    <form method="POST">
      <input type="password" name="password" placeholder="New password" required>
      <input type="password" name="confirm_password" placeholder="Confirm password" required>
      <button type="submit">Reset Password</button>
    </form>
  <?php else: ?>
    <p>
      You can now <a href="/login-register.php">login</a> with your new password.
    </p>
  <?php endif; ?>
</div>

</body>
</html>