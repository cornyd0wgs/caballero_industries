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

    $stmt = mysqli_prepare($conn, 'SELECT SUM(quantity) AS total FROM cart_items WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row['total'] ? (int) $row['total'] : 0;
}

function nav_href($item, $current_page) {
    if ($item['type'] === 'page') {
        return $item['href'];
    }

    // type === 'anchor'
    return ($current_page === 'home') ? '#' . $item['target'] : 'index.php#' . $item['target'];
}
?>