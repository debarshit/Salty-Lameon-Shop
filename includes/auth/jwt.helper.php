<?php
use Firebase\JWT\JWT;

require_once 'vendor/autoload.php';

function buildTokenPayload($user) {
    return [
        'iss' => 'https://thesaltylameon.com',
        'aud' => 'https://thesaltylameon.com',
        'userId' => $user['userId'],
        'name' => $user['userName'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + (60 * 15) // 15 min
    ];
}

function generateAccessToken($payload) {
    // $secret = $_ENV['JWT_SECRET'];
    $secret = 'rashmi';
    return JWT::encode($payload, $secret, 'HS256');
}