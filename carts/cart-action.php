<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

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


// -----------------------------------------------------------------
// ADD TO CART
// -----------------------------------------------------------------
if ($action === 'add') {

    $product_id = isset($_POST['product_id'])
        ? (int) $_POST['product_id']
        : 0;

    $requested_qty = $_POST['quantity'] ?? '';

    // Get current stock.
    $stmt = $conn->prepare(
        'SELECT quantity
         FROM products
         WHERE id = ?'
    );

    $stmt->execute([$product_id]);

    $product = $stmt->fetch();

    $stock = $product ? (int) $product['quantity'] : 0;

    $max_allowed = min($stock, MAX_CART_QUANTITY);

    if (
        !$product ||
        $stock < 1 ||
        !validate_number_range($requested_qty, 1, max($max_allowed, 1))
    ) {
        $_SESSION['flash_error'] =
            'You may order up to ' . MAX_CART_QUANTITY . ' units of one product, subject to available stock.';

        header('Location: ' . BASE_URL . 'product.php?id=' . $product_id);
        exit;
    }

    $requested_qty = (int) $requested_qty;

    // Check existing cart quantity.
    $stmt = $conn->prepare(
        'SELECT quantity
         FROM cart_items
         WHERE user_id = ? AND product_id = ?'
    );

    $stmt->execute([
        $user_id,
        $product_id
    ]);

    $existing = $stmt->fetch();

    $existing_qty = $existing
        ? (int) $existing['quantity']
        : 0;

    $new_qty = $existing_qty + $requested_qty;

    if ($new_qty > $max_allowed) {
        $_SESSION['flash_error'] =
            'You already have ' . $existing_qty . ' in your cart. The maximum is ' .
            $max_allowed . ' for this product.';

        header('Location: ' . BASE_URL . 'product.php?id=' . $product_id);
        exit;
    }

    // Insert new cart item or update existing one.
    $stmt = $conn->prepare(
        'INSERT INTO cart_items
            (user_id, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            quantity = ?'
    );

    $stmt->execute([
        $user_id,
        $product_id,
        $new_qty,
        $new_qty
    ]);

    $_SESSION['flash_success'] = 'Added to cart.';

    header('Location: cart.php');
    exit;
}


// -----------------------------------------------------------------
// UPDATE QUANTITY
// -----------------------------------------------------------------
if ($action === 'update') {

    $cart_item_id = isset($_POST['cart_item_id'])
        ? (int) $_POST['cart_item_id']
        : 0;

    $requested_qty = $_POST['quantity'] ?? '';

    // Make sure this cart item belongs to this user.
    $stmt = $conn->prepare(
        'SELECT cart_items.id,
                products.quantity AS stock
         FROM cart_items
         JOIN products
           ON cart_items.product_id = products.id
         WHERE cart_items.id = ?
           AND cart_items.user_id = ?'
    );

    $stmt->execute([
        $cart_item_id,
        $user_id
    ]);

    $row = $stmt->fetch();

    if (!$row) {
        header('Location: cart.php');
        exit;
    }

    $stock = (int) $row['stock'];

    $max_allowed = min($stock, MAX_CART_QUANTITY);

    if (
        !validate_number_range(
            $requested_qty,
            1,
            max($max_allowed, 1)
        )
    ) {
        $_SESSION['flash_error'] =
            'Quantity must be between 1 and ' . max($max_allowed, 1) . ' for this product.';

        header('Location: cart.php');
        exit;
    }

    $requested_qty = (int) $requested_qty;

    $stmt = $conn->prepare(
        'UPDATE cart_items
         SET quantity = ?
         WHERE id = ?'
    );

    $stmt->execute([
        $requested_qty,
        $cart_item_id
    ]);

    header('Location: cart.php');
    exit;
}


// -----------------------------------------------------------------
// REMOVE ITEM
// -----------------------------------------------------------------
if ($action === 'remove') {

    $cart_item_id = isset($_POST['cart_item_id'])
        ? (int) $_POST['cart_item_id']
        : 0;

    $stmt = $conn->prepare(
        'DELETE FROM cart_items
         WHERE id = ?
           AND user_id = ?'
    );

    $stmt->execute([
        $cart_item_id,
        $user_id
    ]);

    header('Location: cart.php');
    exit;
}


// -----------------------------------------------------------------
// CHECKOUT
// -----------------------------------------------------------------
if ($action === 'checkout') {

    $recipient_name   = trim($_POST['recipient_name'] ?? '');
    $contact_number   = trim($_POST['contact_number'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $city             = trim($_POST['city'] ?? '');
    $province         = trim($_POST['province'] ?? '');
    $postal_code      = trim($_POST['postal_code'] ?? '');
    $payment_method  = strtolower(trim($_POST['payment_method'] ?? ''));
    $gcash_reference = trim($_POST['gcash_reference'] ?? '');

    $address_is_valid =
        $recipient_name !== '' && strlen($recipient_name) <= 100 &&
        $contact_number !== '' && strlen($contact_number) <= 30 &&
        $delivery_address !== '' && strlen($delivery_address) <= 255 &&
        $city !== '' && strlen($city) <= 100 &&
        $province !== '' && strlen($province) <= 100 &&
        preg_match('/^[0-9]{4,10}$/', $postal_code);

    if (!$address_is_valid) {
        $_SESSION['flash_error'] =
            'Please complete the delivery details. Postal code must contain 4 to 10 digits.';

        header('Location: cart.php');
        exit;
    }

    if (!in_array($payment_method, ['cash', 'gcash'], true)) {
        $_SESSION['flash_error'] = 'Please choose Cash or GCash as your payment method.';
        header('Location: cart.php');
        exit;
    }

    if ($payment_method === 'gcash') {
        if (!preg_match('/^[0-9]{6,30}$/', $gcash_reference)) {
            $_SESSION['flash_error'] =
                'GCash reference number must contain 6 to 30 digits.';
            header('Location: cart.php');
            exit;
        }
    } else {
        $gcash_reference = null;
    }

    // Get everything currently in the user's cart.
    $stmt = $conn->prepare(
        'SELECT
            cart_items.quantity AS cart_quantity,
            products.id AS product_id,
            products.name,
            products.price,
            products.discount_price,
            products.quantity AS stock
         FROM cart_items
         JOIN products
           ON cart_items.product_id = products.id
         WHERE cart_items.user_id = ?'
    );

    $stmt->execute([$user_id]);

    $cart_rows = $stmt->fetchAll();

    if (empty($cart_rows)) {

        $_SESSION['flash_error'] =
            'Your cart is empty.';

        header('Location: cart.php');
        exit;
    }

    foreach ($cart_rows as $row) {
        $allowed_quantity = min((int) $row['stock'], MAX_CART_QUANTITY);

        if ((int) $row['cart_quantity'] < 1 || (int) $row['cart_quantity'] > $allowed_quantity) {
            $_SESSION['flash_error'] =
                'One or more cart quantities exceed the purchase limit or available stock. Please update your cart.';
            header('Location: cart.php');
            exit;
        }
    }


    // Start database transaction.
    $conn->beginTransaction();

    try {

        $grand_total = 0;

        foreach ($cart_rows as $row) {

            $unit_price = $row['discount_price']
                ? $row['discount_price']
                : $row['price'];

            $grand_total +=
                $unit_price * $row['cart_quantity'];
        }


        // ---------------------------------------------------------
        // 1) CREATE THE ORDER
        // ---------------------------------------------------------

        $stmt = $conn->prepare(
            'INSERT INTO orders
                (
                    user_id,
                    recipient_name,
                    contact_number,
                    delivery_address,
                    city,
                    province,
                    postal_code,
                    payment_method,
                    gcash_reference,
                    total_amount,
                    status
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $user_id,
            $recipient_name,
            $contact_number,
            $delivery_address,
            $city,
            $province,
            $postal_code,
            $payment_method,
            $gcash_reference,
            $grand_total,
            'pending'
        ]);

        // PDO equivalent of mysqli_insert_id().
        $order_id = $conn->lastInsertId();


        foreach ($cart_rows as $row) {

            $unit_price = $row['discount_price']
                ? $row['discount_price']
                : $row['price'];


            // -----------------------------------------------------
            // 2) DEDUCT STOCK
            // -----------------------------------------------------

            $stmt = $conn->prepare(
                'UPDATE products
                 SET quantity = quantity - ?
                 WHERE id = ?
                   AND quantity >= ?'
            );

            $stmt->execute([
                $row['cart_quantity'],
                $row['product_id'],
                $row['cart_quantity']
            ]);

            $affected = $stmt->rowCount();


            // If nothing was updated, there wasn't enough stock.
            if ($affected === 0) {

                throw new Exception(
                    sprintf_stock_error($row['name'])
                );
            }


            // -----------------------------------------------------
            // 3) CREATE ORDER ITEM
            // -----------------------------------------------------

            $stmt = $conn->prepare(
                'INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        product_name,
                        quantity,
                        unit_price
                    )
                 VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $order_id,
                $row['product_id'],
                $row['name'],
                $row['cart_quantity'],
                $unit_price
            ]);
        }


        // ---------------------------------------------------------
        // 4) EMPTY THE CART
        // ---------------------------------------------------------

        $stmt = $conn->prepare(
            'DELETE FROM cart_items
             WHERE user_id = ?'
        );

        $stmt->execute([$user_id]);


        // Everything succeeded.
        $conn->commit();

        $_SESSION['flash_success'] =
            'Order #' . $order_id .
            ' placed successfully and is pending processing.';

        header('Location: cart.php');
        exit;


    } catch (Exception $e) {

        // Something failed.
        // Undo EVERYTHING that happened during checkout.
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $_SESSION['flash_error'] =
            $e->getMessage();

        header('Location: cart.php');
        exit;
    }
}


// -----------------------------------------------------------------
// UNKNOWN ACTION
// -----------------------------------------------------------------

header('Location: cart.php');
exit;


/**
 * Small helper used when stock is insufficient.
 */
function sprintf_stock_error($product_name) {

    return 'Sorry, "' .
        $product_name .
        '" no longer has enough stock. Please update your cart.';
}