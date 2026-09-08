<?php

require_once __DIR__ . '/../helpers/stuff.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function current_user_name() {
    return $_SESSION['user_name'] ?? null;
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        $current_path = $_SERVER['REQUEST_URI'] ?? BASE_URL;
        $login_url = BASE_URL . 'register/login.php?redirect=' . urlencode($current_path);
        header('Location: ' . $login_url);
        exit;
    }
}

function require_admin() {
    require_login();

    if (!is_admin()) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

?>
