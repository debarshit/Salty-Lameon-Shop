<?php

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: *");

    // $servername = "127.0.0.1";
    // $username = "root";
    // $password = "";
    // $database = "biblophile_shop";

    $servername = "localhost:5522";
    $username = "thesalty_rashmi";
    $password = "Abhiabhi@13";
    $database = "thesalty_Ecomm";
    
    // Create connection
    $link = mysqli_connect($servername, $username, $password, $database);
        
    if (mysqli_connect_errno()) {
        
        print_r(mysqli_connect_error());
        exit();
        
    }