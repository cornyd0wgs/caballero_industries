<?php

function safe_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function format_price($amount) {
    return '&#8369;' . number_format((float) $amount, 2);
}

function nav_href($item, $current_page) {
    if ($item['type'] === 'page') {
        return $item['href'];
    }

    // type === 'anchor'
    return ($current_page === 'home') ? '#' . $item['target'] : 'index.php#' . $item['target'];
}
?>