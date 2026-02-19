<?php
require_once 'jwt.helper.php';

function findUserByEmail($email) {
    global $shopLink;

    $stmt = $shopLink->prepare("SELECT * FROM users WHERE userEmail = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function generateRefreshToken($userId) {
    global $shopLink;

    $token = bin2hex(random_bytes(64));
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30)); // 30 days

    $stmt = $shopLink->prepare("
        INSERT INTO user_refresh_tokens (userId, userRefreshToken, expiresAt)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $userId, $token, $expiresAt);
    $stmt->execute();

    return $token;
}

function checkUserExists($email, $phone, $userName) {
    global $shopLink;

    $stmt = $shopLink->prepare("SELECT userId FROM users WHERE userEmail = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows) {
        return ['exists' => true, 'reason' => 'email'];
    }

    if ($phone) {
        $stmt = $shopLink->prepare("SELECT userId FROM users WHERE userPhone = ? LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows) {
            return ['exists' => true, 'reason' => 'phone'];
        }
    }

    $stmt = $shopLink->prepare("SELECT userId FROM users WHERE userName = ? LIMIT 1");
    $stmt->bind_param("s", $userName);
    $stmt->execute();
    if ($stmt->get_result()->num_rows) {
        return ['exists' => true, 'reason' => 'username'];
    }

    return ['exists' => false];
}

function createUser($data) {
    global $shopLink;

    $name     = $data['name'];
    $email    = $data['email'];
    $phone    = $data['phone'] ?: substr("TEMP_{$email}", 0, 20);
    $password = $data['password']; // ⚠️ hash later
    $source   = $data['sourceReferral'];

    $stmt = $shopLink->prepare("
        INSERT INTO users 
        (name, userEmail, userPhone, userPassword, sourceReferral)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssss",
        $name,
        $email,
        $phone,
        $password,
        $source
    );

    $stmt->execute();

    return true;
}

function generateResetToken() {
    return bin2hex(random_bytes(32));
}

function saveResetToken($email, $token) {
    global $shopLink;

    $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $stmt = $shopLink->prepare("
        UPDATE users 
        SET resetToken = ?, resetTokenExpiry = ?
        WHERE userEmail = ?
    ");

    $stmt->bind_param("sss", $token, $expiry, $email);
    return $stmt->execute();
}

function deleteRefreshToken($refreshToken) {
    global $shopLink;

    $stmt = $shopLink->prepare(query: "
        DELETE FROM user_refresh_tokens 
        WHERE userRefreshToken = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $refreshToken);
    $stmt->execute();

    return true;
}