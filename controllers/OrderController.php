<?php

class OrderController {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Get orders for a specific user.
     */
    public function getUserOrders(int $userId): array {
        $stmt = $this->conn->prepare(
            'SELECT id, recipient_name, contact_number, delivery_address, city, province, postal_code, payment_method, gcash_reference, total_amount, status, created_at
             FROM orders
             WHERE user_id = ?
             ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $stmtItems = $this->conn->prepare(
                'SELECT oi.*, p.image
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id = ?'
            );
            $stmtItems->execute([$order['id']]);
            $order['items'] = $stmtItems->fetchAll();
        }
        unset($order);

        return $orders;
    }

    /**
     * Get all orders with user info (for Admin).
     */
    public function getAllOrders(?string $statusFilter = null, ?string $search = null): array {
        $sql = 'SELECT o.*, u.full_name AS customer_name, u.email AS customer_email
                FROM orders o
                JOIN users u ON u.id = o.user_id
                WHERE 1=1';
        $params = [];

        if ($statusFilter) {
            $sql .= ' AND o.status = ?';
            $params[] = $statusFilter;
        }

        if ($search) {
            $sql .= ' AND (o.id = ? OR u.full_name LIKE ? OR u.email LIKE ? OR o.recipient_name LIKE ?)';
            $searchId = is_numeric($search) ? (int)$search : 0;
            $wildcard = '%' . $search . '%';
            $params[] = $searchId;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        $sql .= ' ORDER BY o.created_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $stmtItems = $this->conn->prepare(
                'SELECT oi.*, p.image
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id = ?'
            );
            $stmtItems->execute([$order['id']]);
            $order['items'] = $stmtItems->fetchAll();
        }
        unset($order);

        return $orders;
    }

    /**
     * Update order status (Admin action with stock rollback on cancellation).
     */
    public function updateStatus(int $orderId, string $newStatus): array {
        $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        $allowedTransitions = [
            'pending' => ['processing', 'completed', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        $stmt = $this->conn->prepare('SELECT status FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        $currentStatus = $order['status'] ?? null;
        $canChange = $order
            && in_array($newStatus, $allowedStatuses, true)
            && in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true);

        if (!$canChange) {
            return [
                'success' => false,
                'message' => 'That status change is not allowed.'
            ];
        }

        $this->conn->beginTransaction();
        try {
            if ($newStatus === 'cancelled') {
                $stmt = $this->conn->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
                $stmt->execute([$orderId]);
                $items = $stmt->fetchAll();

                foreach ($items as $item) {
                    $stmtStock = $this->conn->prepare(
                        'UPDATE products SET quantity = quantity + ? WHERE id = ?'
                    );
                    $stmtStock->execute([$item['quantity'], $item['product_id']]);
                }
            }

            $stmt = $this->conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $orderId]);

            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Order status updated to ' . $newStatus . '.'
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage()
            ];
        }
    }
}
