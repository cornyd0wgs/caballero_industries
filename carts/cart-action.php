<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

if (!verify_csrf()) {
    $_SESSION['flash_error'] = 'Your form session expired. Please try again.';
    header('Location: cart.php');
    exit;
}

$user_id = current_user_id();
$action  = $_POST['action'] ?? '';
$cartController = new CartController($conn);

// ADD TO CART
if ($action === 'add') {
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $requested_qty = $_POST['quantity'] ?? '';

    $result = $cartController->add($user_id, $product_id, $requested_qty);
    if (!empty($result['message'])) {
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
    }
    header('Location: ' . ($result['redirect'] ?? 'cart.php'));
    exit;
}

// UPDATE QUANTITY
if ($action === 'update') {
    $cart_item_id = isset($_POST['cart_item_id']) ? (int) $_POST['cart_item_id'] : 0;
    $requested_qty = $_POST['quantity'] ?? '';

    $result = $cartController->update($user_id, $cart_item_id, $requested_qty);
    if (!empty($result['message'])) {
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
    }
    header('Location: ' . ($result['redirect'] ?? 'cart.php'));
    exit;
}

// REMOVE ITEM
if ($action === 'remove') {
    $cart_item_id = isset($_POST['cart_item_id']) ? (int) $_POST['cart_item_id'] : 0;

    $result = $cartController->remove($user_id, $cart_item_id);
    header('Location: ' . ($result['redirect'] ?? 'cart.php'));
    exit;
}

// CHECKOUT
if ($action === 'checkout') {
    $result = $cartController->checkout($user_id, $_POST);
    if (!empty($result['message'])) {
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
    }
    header('Location: ' . ($result['redirect'] ?? 'cart.php'));
    exit;
}

// UNKNOWN ACTION
header('Location: cart.php');
exit;
