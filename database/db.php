<?php
/**
 * includes/db.php
 * -------------------------------------------------------------
 * Opens ONE database connection and stores it in $conn.
 * Every page that needs the database does:
 *
 *     require_once 'includes/db.php';
 *
 * ...and then uses $conn with mysqli functions, e.g.:
 *
 *     $result = mysqli_query($conn, "SELECT * FROM products");
 *
 * -------------------------------------------------------------
 * XAMPP DEFAULTS:
 * A fresh XAMPP install uses username "root" with an EMPTY password
 * and MySQL running on "localhost". If you changed your MySQL root
 * password, update DB_PASS below to match.
 * -------------------------------------------------------------
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          
define('DB_NAME', 'caballero_industries');
define('PORT','3307');

// By default, modern PHP throws an exception if the connection fails.
// We turn that off here so we can show our own friendly error message
// below instead of a scary raw PHP error page.
mysqli_report(MYSQLI_REPORT_OFF);

// Open the connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, PORT);

// If the connection fails, stop the page here with a clear message
// instead of letting confusing PHP warnings pile up further down.
if (!$conn) {
    die(
        'Database connection failed: ' . mysqli_connect_error() . '<br>' .
        'Checklist: <br>' .
        '1) Is MySQL running in the XAMPP control panel? <br>' .
        '2) Have you imported database/schema.sql in phpMyAdmin yet? <br>' .
        '3) Do DB_USER / DB_PASS in includes/db.php match your MySQL login?'
    );
}

// Make sure text with special characters (accents, emoji, etc.) is
// stored and read correctly.
mysqli_set_charset($conn, 'utf8mb4');
