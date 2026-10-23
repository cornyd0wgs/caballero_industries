<?php

class ProductController {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Get single product by ID.
     */
    public function getById(int $productId): ?array {
        $stmt = $this->conn->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    /**
     * Get all products (Admin view with sales count).
     */
    public function getAllAdmin(): array {
        $sql = "
            SELECT
                p.*,
                COALESCE(
                    SUM(
                        CASE
                            WHEN o.status = 'completed' THEN oi.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS units_sold
            FROM products p
            LEFT JOIN order_items oi ON oi.product_id = p.id
            LEFT JOIN orders o ON o.id = oi.order_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Delete product by ID.
     */
    public function delete(int $productId): bool {
        if ($productId <= 0) {
            return false;
        }
        $stmt = $this->conn->prepare('DELETE FROM products WHERE id = ?');
        return $stmt->execute([$productId]);
    }

    /**
     * Restock product quantity.
     */
    public function restock(int $productId, int $addQuantity): array {
        if ($productId <= 0 || $addQuantity < 1) {
            return [
                'success' => false,
                'message' => 'Please enter a valid restock quantity (at least 1).'
            ];
        }

        $stmt = $this->conn->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
        $stmt->execute([$addQuantity, $productId]);

        return [
            'success' => true,
            'message' => 'Product stock updated successfully.'
        ];
    }
}
