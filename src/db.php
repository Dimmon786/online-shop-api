<?php

//     $server = "localhost";
//     $user = "php-user";
//     $pass = "suppersecretPassword123";
//     $database = "homepage";

// $dsn = "mysql:host=$server;dbname=$database;charset=utf8";

// $pdo = new PDO($dsn, $user, $pass);

// $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$connection = mysqli_connect("localhost", "php-user", "suppersecretPassword123", "online-shop");

    if (!$connection) {
        die("DB-Verbindung fehlgeschlagen: " . mysqli_connect_error());
    }
?>