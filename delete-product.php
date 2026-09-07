<?php

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

    $stmt = mysqli_prepare($conn, 'DELETE FROM products WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['flash_success'] = 'Product deleted.';
}

header('Location: admin-products.php');
exit;
