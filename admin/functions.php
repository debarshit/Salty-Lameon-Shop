<?php

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: *");
    require_once(__DIR__ . '/../vendor/autoload.php');

    // $servername = "127.0.0.1";
    // $username = "root";
    // $password = "";
    // $database = "biblophile_shop";

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    $servername = $_ENV['DATABASE_SERVER'];
    $username = $_ENV['DATABASE_USERNAME'];
    $password = $_ENV['DATABASE_PASSWORD'];
    $database = $_ENV['SHOP_DATABASE'];
    
    // Create connection
    $link = mysqli_connect($servername, $username, $password, $database);
        
    if (mysqli_connect_errno()) {
        
        print_r(mysqli_connect_error());
        exit();
        
    }

    //helper functions
    function uploadImageToImageKit($base64, $folderPath, $fileName) {
        $privateKey = 'private_UXMsh7UiS3NWKADYAwJ/Tebobxw=';
        $auth       = base64_encode($privateKey . ':');

        $ch = curl_init('https://api.imagekit.io/v1/files/upload');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $auth],
            CURLOPT_POSTFIELDS     => [
                'file'              => 'data:image/jpeg;base64,' . $base64,
                'fileName'          => $fileName,
                'folder'            => $folderPath,
                'useUniqueFileName' => 'true',
            ],
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response; // contains fileId, url, name etc.
    }

    function deleteImageKitFolder($folderPath) {
        $privateKey = 'private_UXMsh7UiS3NWKADYAwJ/Tebobxw=';
        $auth       = base64_encode($privateKey . ':');

        // List all files in the folder
        $ch = curl_init('https://api.imagekit.io/v1/files?path=' . urlencode($folderPath));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $auth, 'Accept: application/json'],
        ]);
        $files = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!is_array($files)) return;

        // Collect all fileIds
        $fileIds = array_column($files, 'fileId');
        if (empty($fileIds)) return;

        // Bulk delete
        $ch = curl_init('https://api.imagekit.io/v1/files/batch/deleteByFileIds');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['fileIds' => $fileIds]),
        ]);
        curl_exec($ch);
        curl_close($ch);
    }