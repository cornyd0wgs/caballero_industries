<?php

function safe_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function format_price($amount) {
    return '&#8369;' . number_format((float) $amount, 2);
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

function nav_href($item, $current_page) {
    if ($item['type'] === 'page') {
        // Ensure page links start from root directory
        return BASE_URL . $item['href'];
    }

    // type === 'anchor'
    if ($current_page === 'home') {
        return '#' . $item['target'];
    }

    // On subpages, return absolute path back to index.php with anchor
    return BASE_URL . 'index.php#' . $item['target'];
}

?>