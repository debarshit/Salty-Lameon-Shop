<?php
use Firebase\JWT\JWT;

require_once 'vendor/autoload.php';

function buildTokenPayload($user) {
    return [
        'iss' => 'https://thesaltylameon.com',
        'aud' => 'https://thesaltylameon.com',
        'userId' => $user['UserId'],
        'name' => $user['Name'],
        'role' => $user['Role'],
        'iat' => time(),
        'exp' => time() + (60 * 15) // 15 min
    ];
}

function generateAccessToken($payload) {
    // $secret = $_ENV['JWT_SECRET'];
    $secret = 'my_super_secure_secret_key_2026_very_long_random';
    
    return JWT::encode($payload, $secret, 'HS256');
}