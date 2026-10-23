<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $product_id = isset($_POST['product_id'])
        ? (int) $_POST['product_id']
        : 0;

    $productController = new ProductController($conn);
    if ($productController->delete($product_id)) {
        $_SESSION['flash_success'] = 'Product deleted.';
    }
}

header('Location: products.php');
exit;
