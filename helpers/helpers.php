<?php

function safe_output($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function format_price($amount) {
    return '&#8369;' . number_format((float) $amount, 2);
}

// Turn a database path such as "images/jacket.png" into a URL that
// works from the homepage, admin pages, cart pages, and product pages.
function asset_url($path) {
    $path = trim((string) $path);

    if ($path === '') {
        return BASE_URL . 'images/box.png';
    }

    if (preg_match('#^(https?:)?//#', $path) || str_starts_with($path, '/')) {
        return $path;
    }

    return BASE_URL . ltrim($path, '/');
}

function get_cart_count($conn, $user_id) {
    if (!$user_id) {
        return 0;
    }

    $stmt = $conn->prepare(
        'SELECT SUM(quantity) AS total
         FROM cart_items
         WHERE user_id = ?'
    );

    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    return ($row && $row['total']) ? (int) $row['total'] : 0;
}

function get_daily_popular_product_ids($conn, $limit = 2) {
    $limit = max(1, (int) $limit);

    $sql = "SELECT oi.product_id, SUM(oi.quantity) AS units_sold
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.status = 'completed'
              AND DATE(o.created_at) = CURDATE()
            GROUP BY oi.product_id
            ORDER BY units_sold DESC, oi.product_id ASC
            LIMIT {$limit}";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    return array_map('intval', array_column($stmt->fetchAll(), 'product_id'));
}

function nav_href($item, $current_page) {
    if ($item['type'] === 'page') {
        return BASE_URL . ltrim($item['href'], '/');
    }

    if ($current_page === 'home') {
        return '#' . $item['target'];
    }

    return BASE_URL . 'index.php#' . $item['target'];
}

// Simple CSRF protection for forms that change data.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' .
        safe_output(csrf_token()) . '">';
}

function verify_csrf() {
    $submitted = $_POST['csrf_token'] ?? '';
    $saved = $_SESSION['csrf_token'] ?? '';

    return $saved !== '' && hash_equals($saved, $submitted);
}

?>
