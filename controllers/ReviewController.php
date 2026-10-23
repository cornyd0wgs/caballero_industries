<?php

class ReviewController {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Get reviews for a specific product.
     */
    public function getProductReviews(int $productId): array {
        $stmt = $this->conn->prepare(
            'SELECT reviews.*, users.full_name
             FROM reviews
             JOIN users ON reviews.user_id = users.id
             WHERE reviews.product_id = ?
             ORDER BY reviews.created_at DESC'
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all reviews with user and product names (Admin).
     */
    public function getAllAdmin(): array {
        $sql = '
            SELECT
                r.*,
                u.full_name,
                p.name AS product_name
            FROM reviews r
            JOIN users u ON u.id = r.user_id
            JOIN products p ON p.id = r.product_id
            ORDER BY r.created_at DESC
        ';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Add a review for a product.
     */
    public function addReview(int $userId, int $productId, $rating, string $comment): array {
        $comment = trim($comment);
        $errors = [];

        if (!validate_number_range($rating, 1, 5)) {
            $errors[] = 'Please select a star rating between 1 and 5.';
        }

        if ($comment === '') {
            $errors[] = 'Please write a comment for your review.';
        } elseif (strlen($comment) > 1000) {
            $errors[] = 'Comments cannot be longer than 1000 characters.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO reviews (product_id, user_id, rating, comment)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $productId,
            $userId,
            (int)$rating,
            $comment
        ]);

        return [
            'success' => true,
            'message' => 'Thank you for your transmission. Your review is live.'
        ];
    }

    /**
     * Delete review (Admin).
     */
    public function deleteReview(int $reviewId): bool {
        if ($reviewId <= 0) {
            return false;
        }
        $stmt = $this->conn->prepare('DELETE FROM reviews WHERE id = ?');
        return $stmt->execute([$reviewId]);
    }
}
