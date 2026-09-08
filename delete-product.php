<?php

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id'])
        ? (int) $_POST['product_id']
        : 0;

    if ($product_id > 0) {
        $stmt = $conn->prepare(
            'DELETE FROM products WHERE id = ?'
        );

        $stmt->execute([$product_id]);
    }

    $_SESSION['flash_success'] = 'Product deleted.';
}

header('Location: admin.php');
exit;

