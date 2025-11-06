<?php

    $server = "localhost";
    $user = "php-user";
    $pass = "suppersecretPassword123";
    $database = "homepage";

$dsn = "mysql:host=$server;dbname=$database;charset=utf8";

$pdo = new PDO($dsn, $user, $pass);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>