<?php

class CartController {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Get items in user's cart with details and computed subtotals.
     */
    public function getCart(int $userId): array {
        $stmt = $this->conn->prepare(
            'SELECT cart_items.id AS cart_item_id,
                    cart_items.quantity AS cart_quantity,
                    products.id AS product_id,
                    products.name,
                    products.image,
                    products.price,
                    products.discount_price,
                    products.quantity AS stock
             FROM cart_items
             JOIN products ON cart_items.product_id = products.id
             WHERE cart_items.user_id = ?
             ORDER BY cart_items.added_at DESC'
        );

        $stmt->execute([$userId]);
        $cart_rows = $stmt->fetchAll();

        $grand_total = 0;
        foreach ($cart_rows as &$row) {
            $unit_price = $row['discount_price'] ? $row['discount_price'] : $row['price'];
            $row['unit_price'] = $unit_price;
            $row['subtotal'] = $unit_price * $row['cart_quantity'];
            $grand_total += $row['subtotal'];
        }
        unset($row);

        $item_count = array_sum(array_column($cart_rows, 'cart_quantity'));

        return [
            'items' => $cart_rows,
            'grand_total' => $grand_total,
            'item_count' => $item_count
        ];
    }

    /**
     * Add item to cart.
     */
    public function add(int $userId, int $productId, $requestedQty): array {
        $stmt = $this->conn->prepare('SELECT quantity FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        $stock = $product ? (int) $product['quantity'] : 0;
        $maxAllowed = min($stock, MAX_CART_QUANTITY);

        if (!$product || $stock < 1 || !validate_number_range($requestedQty, 1, max($maxAllowed, 1))) {
            return [
                'success' => false,
                'message' => 'You may order up to ' . MAX_CART_QUANTITY . ' units of one product, subject to available stock.',
                'redirect' => BASE_URL . 'product.php?id=' . $productId
            ];
        }

        $requestedQty = (int) $requestedQty;

        $stmt = $this->conn->prepare(
            'SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?'
        );
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();
        $existingQty = $existing ? (int) $existing['quantity'] : 0;

        $newQty = $existingQty + $requestedQty;
        if ($newQty > $maxAllowed) {
            return [
                'success' => false,
                'message' => 'You already have ' . $existingQty . ' in your cart. The maximum is ' . $maxAllowed . ' for this product.',
                'redirect' => BASE_URL . 'product.php?id=' . $productId
            ];
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO cart_items (user_id, product_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = ?'
        );
        $stmt->execute([$userId, $productId, $newQty, $newQty]);

        return [
            'success' => true,
            'message' => 'Added to cart.',
            'redirect' => CART_URL . 'cart.php'
        ];
    }

    /**
     * Update quantity of cart item.
     */
    public function update(int $userId, int $cartItemId, $requestedQty): array {
        $stmt = $this->conn->prepare(
            'SELECT cart_items.id, products.quantity AS stock
             FROM cart_items
             JOIN products ON cart_items.product_id = products.id
             WHERE cart_items.id = ? AND cart_items.user_id = ?'
        );
        $stmt->execute([$cartItemId, $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'success' => false,
                'redirect' => CART_URL . 'cart.php'
            ];
        }

        $stock = (int) $row['stock'];
        $maxAllowed = min($stock, MAX_CART_QUANTITY);

        if (!validate_number_range($requestedQty, 1, max($maxAllowed, 1))) {
            return [
                'success' => false,
                'message' => 'Quantity must be between 1 and ' . max($maxAllowed, 1) . ' for this product.',
                'redirect' => CART_URL . 'cart.php'
            ];
        }

        $requestedQty = (int) $requestedQty;
        $stmt = $this->conn->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
        $stmt->execute([$requestedQty, $cartItemId]);

        return [
            'success' => true,
            'redirect' => CART_URL . 'cart.php'
        ];
    }

    /**
     * Remove item from cart.
     */
    public function remove(int $userId, int $cartItemId): array {
        $stmt = $this->conn->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $stmt->execute([$cartItemId, $userId]);

        return [
            'success' => true,
            'redirect' => CART_URL . 'cart.php'
        ];
    }

    /**
     * Process checkout for the user.
     */
    public function checkout(int $userId, array $data): array {
        $recipient_name   = trim($data['recipient_name'] ?? '');
        $contact_number   = trim($data['contact_number'] ?? '');
        $delivery_address = trim($data['delivery_address'] ?? '');
        $city             = trim($data['city'] ?? '');
        $province         = trim($data['province'] ?? '');
        $postal_code      = trim($data['postal_code'] ?? '');
        $payment_method   = strtolower(trim($data['payment_method'] ?? ''));
        $gcash_reference  = trim($data['gcash_reference'] ?? '');

        $address_is_valid =
            $recipient_name !== '' && strlen($recipient_name) <= 100 &&
            $contact_number !== '' && strlen($contact_number) <= 30 &&
            $delivery_address !== '' && strlen($delivery_address) <= 255 &&
            $city !== '' && strlen($city) <= 100 &&
            $province !== '' && strlen($province) <= 100 &&
            preg_match('/^[0-9]{4,10}$/', $postal_code);

        if (!$address_is_valid) {
            return [
                'success' => false,
                'message' => 'Please complete the delivery details. Postal code must contain 4 to 10 digits.',
                'redirect' => CART_URL . 'cart.php'
            ];
        }

        if (!in_array($payment_method, ['cash', 'gcash'], true)) {
            return [
                'success' => false,
                'message' => 'Please choose Cash or GCash as your payment method.',
                'redirect' => CART_URL . 'cart.php'
            ];
        }

        if ($payment_method === 'gcash') {
            if (!preg_match('/^[0-9]{6,30}$/', $gcash_reference)) {
                return [
                    'success' => false,
                    'message' => 'GCash reference number must contain 6 to 30 digits.',
                    'redirect' => CART_URL . 'cart.php'
                ];
            }
        } else {
            $gcash_reference = null;
        }

        $stmt = $this->conn->prepare(
            'SELECT cart_items.quantity AS cart_quantity,
                    products.id AS product_id,
                    products.name,
                    products.price,
                    products.discount_price,
                    products.quantity AS stock
             FROM cart_items
             JOIN products ON cart_items.product_id = products.id
             WHERE cart_items.user_id = ?'
        );
        $stmt->execute([$userId]);
        $cart_rows = $stmt->fetchAll();

        if (empty($cart_rows)) {
            return [
                'success' => false,
                'message' => 'Your cart is empty.',
                'redirect' => CART_URL . 'cart.php'
            ];
        }

        foreach ($cart_rows as $row) {
            $allowed_quantity = min((int) $row['stock'], MAX_CART_QUANTITY);
            if ((int) $row['cart_quantity'] < 1 || (int) $row['cart_quantity'] > $allowed_quantity) {
                return [
                    'success' => false,
                    'message' => 'One or more cart quantities exceed the purchase limit or available stock. Please update your cart.',
                    'redirect' => CART_URL . 'cart.php'
                ];
            }
        }

        $this->conn->beginTransaction();

        try {
            $grand_total = 0;
            foreach ($cart_rows as $row) {
                $unit_price = $row['discount_price'] ? $row['discount_price'] : $row['price'];
                $grand_total += $unit_price * $row['cart_quantity'];
            }

            $stmt = $this->conn->prepare(
                'INSERT INTO orders
                    (user_id, recipient_name, contact_number, delivery_address, city, province, postal_code, payment_method, gcash_reference, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
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

            $order_id = $this->conn->lastInsertId();

            foreach ($cart_rows as $row) {
                $unit_price = $row['discount_price'] ? $row['discount_price'] : $row['price'];

                $stmt = $this->conn->prepare(
                    'UPDATE products
                     SET quantity = quantity - ?
                     WHERE id = ? AND quantity >= ?'
                );
                $stmt->execute([
                    $row['cart_quantity'],
                    $row['product_id'],
                    $row['cart_quantity']
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception(
                        'Sorry, "' . $row['name'] . '" no longer has enough stock. Please update your cart.'
                    );
                }

                $stmt = $this->conn->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price)
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

            $stmt = $this->conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $stmt->execute([$userId]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Order #' . $order_id . ' placed successfully and is pending processing.',
                'order_id' => $order_id,
                'redirect' => CART_URL . 'cart.php'
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'redirect' => CART_URL . 'cart.php'
            ];
        }
    }
}
