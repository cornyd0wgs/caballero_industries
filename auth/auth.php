<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        $current_path = $_SERVER['REQUEST_URI'];
        header('Location: login.php?redirect=' . urlencode($current_path));
        exit;
    }
}

function require_admin() {
    require_login();

    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

?>