<?php

	$connection = mysqli_connect("localhost", "php-user", "suppersecretPassword123", "online-shop");

    if (!$connection) {
        die("DB-Verbindung fehlgeschlagen: " . mysqli_connect_error());
    }
?>