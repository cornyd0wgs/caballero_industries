<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'caballero_industries');
define('DB_PORT', '3307');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST .
        ";port=" . DB_PORT .
        ";dbname=" . DB_NAME .
        ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );

    // Make PDO throw exceptions when something goes wrong.
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Return database rows as associative arrays.
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die(
        'Database connection failed: ' . $e->getMessage() . '<br><br>' .
        'Checklist:<br>' .
        '1) Is MySQL running in the XAMPP control panel?<br>' .
        '2) Have you imported database/schema.sql in phpMyAdmin?<br>' .
        '3) Is MySQL using port 3307?<br>' .
        '4) Do DB_USER / DB_PASS match your MySQL login?'
    );
}