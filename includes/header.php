<?php

require_once __DIR__ . '/../helpers/stuff.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../database/db.php';

if (!isset($current_page)) {
    $current_page = '';
}

$cart_count = is_logged_in()
    ? get_cart_count($conn, current_user_id())
    : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $site_name; ?> — Built For Purpose. Driven By Innovation.
    </title>

    <meta
        name="description"
        content="Caballero Industries — high-performance tactical apparel and equipment engineered for durability, function, and unrestricted movement."
    >

    

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
</head>

<body class="page-<?php echo safe_output($current_page ?: 'default'); ?>">

    <?php if ($current_page === 'admin') : ?>

        <header class="admin-topbar">
            <div class="admin-topbar-title">
                <span class="admin-topbar-mark">}</span>
                <span>// ADMIN PANEL</span>
            </div>

            <div class="admin-topbar-actions">
                <a href="<?php echo BASE_URL; ?>" class="admin-topbar-link">VIEW STORE</a>
                <a href="<?php echo ADMIN_URL; ?>profile.php" class="admin-user-chip">
                    <span class="admin-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?></span>
                    <span><?php echo safe_output($_SESSION['user_name'] ?? 'Admin'); ?></span>
                </a>
                <a href="<?php echo REGISTER_URL; ?>logout.php" class="admin-topbar-link">LOGOUT</a>
            </div>
        </header>

    <?php elseif ($current_page === 'account') : ?>

        <header class="account-topbar">
            <div class="container account-topbar-inner">
                <a href="<?php echo BASE_URL; ?>" class="account-brand" aria-label="Back to Caballero Industries">
                    <img src="<?php echo BASE_URL; ?>assets/header-logo.png" alt="Caballero Industries">
                </a>

                <div class="account-topbar-actions">
                    <a href="<?php echo BASE_URL; ?>index.php#gallery" class="account-topbar-link">SHOP</a>
                    <a href="<?php echo CART_URL; ?>cart.php" class="account-topbar-link">
                        CART<?php echo $cart_count > 0 ? ' (' . $cart_count . ')' : ''; ?>
                    </a>
                    <span class="account-user-chip">
                        <span class="account-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
                        <span><?php echo safe_output($_SESSION['user_name'] ?? 'Customer'); ?></span>
                    </span>
                    <a href="<?php echo REGISTER_URL; ?>logout.php" class="account-topbar-link account-logout">LOGOUT</a>
                </div>
            </div>
        </header>

    <?php else : ?>

    <!-- =========================================================
         HEADER / NAVIGATION
    ========================================================== -->

    <header class="site-header" id="siteHeader">

        <div class="container header-inner">

            <a
                href="<?php echo $current_page === 'home' ? '#home' : BASE_URL; ?>"
                class="logo"
                aria-label="<?php echo $site_name; ?> — home"
            >
                <img
                    src="<?php echo BASE_URL; ?>assets/header-logo.png"
                    alt="<?php echo $site_name; ?> logo"
                    class="logo-img"
                >
            </a>

            <nav class="main-nav" id="mainNav">
                <ul class="nav-list">
                    <?php foreach ($nav_items as $item) : ?>
                        <?php
                        $href = nav_href($item, $current_page);
                        $is_active = ($item['type'] === 'anchor')
                            ? ($current_page === 'home' && $item['target'] === 'home')
                            : ($current_page === 'contact');
                        ?>
                        <li>
                            <a
                                href="<?php echo $href; ?>"
                                class="nav-link<?php echo $is_active ? ' active' : ''; ?>"
                                data-nav-key="<?php echo $item['type'] === 'anchor' ? safe_output($item['target']) : 'contact'; ?>"
                                <?php if ($item['type'] === 'anchor') : ?>
                                    data-section="<?php echo safe_output($item['target']); ?>"
                                <?php endif; ?>
                            >
                                <?php echo $item['label']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>

                    <li class="nav-divider" aria-hidden="true"></li>

                    <li>
                        <a
                            href="<?php echo CART_URL; ?>cart.php"
                            class="nav-link<?php echo $current_page === 'cart' ? ' active' : ''; ?>"
                            data-nav-key="cart"
                        >
                            CART
                            <?php if ($cart_count > 0) : ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if (is_logged_in()) : ?>
                        <?php if (is_admin()) : ?>
                            <li><a href="<?php echo ADMIN_URL; ?>admin.php" class="nav-link<?php echo $current_page === 'admin' ? ' active' : ''; ?>" data-nav-key="admin">ADMIN</a></li>
                        <?php else : ?>
                            <li><a href="<?php echo ACCOUNT_URL; ?>dashboard.php" class="nav-link<?php echo $current_page === 'account' ? ' active' : ''; ?>" data-nav-key="account">ACCOUNT</a></li>
                        <?php endif; ?>

                        <li>
                            <a href="<?php echo REGISTER_URL; ?>logout.php" class="nav-link" data-nav-key="logout">LOGOUT</a>
                        </li>
                    <?php else : ?>
                        <li>
                            <a
                                href="<?php echo REGISTER_URL; ?>login.php"
                                class="nav-link<?php echo $current_page === 'login' ? ' active' : ''; ?>"
                                data-nav-key="login"
                            >
                                LOGIN
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <button
                class="hamburger"
                id="hamburgerBtn"
                aria-label="Toggle navigation menu"
                aria-expanded="false"
                aria-controls="mainNav"
            >
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </header>

    <div class="status-bar">
        <div class="container status-bar-inner">
            <span class="status-left"><?php echo $status_left; ?></span>
            <span class="status-right"><?php echo $status_right; ?></span>
        </div>
    </div>

    <?php endif; ?>

    <main>

