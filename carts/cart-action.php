<?php

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/auth/validation.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    if (
        !$product ||
        $stock < 1 ||
        !validate_number_range($requested_qty, 1, $stock)
    ) {
        $_SESSION['flash_error'] =
            'Please choose a valid quantity for that item.';

        header('Location: product.php?id=' . $product_id);
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

    // Never exceed available stock.
    $new_qty = min(
        $existing_qty + $requested_qty,
        $stock
    );

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

    if (
        !validate_number_range(
            $requested_qty,
            1,
            max($stock, 1)
        )
    ) {
        $_SESSION['flash_error'] =
            'Please enter a valid quantity.';

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
                (user_id, total_amount)
             VALUES (?, ?)'
        );

        $stmt->execute([
            $user_id,
            $grand_total
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
            ' placed successfully. Thank you!';

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