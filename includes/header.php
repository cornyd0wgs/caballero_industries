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

<body>

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
                    src="<?php echo BASE_URL; ?>assets/logo2.png"
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


                    <!-- Utility links: cart + account.
                         Sit inside the same collapsible menu
                         so they work on mobile too. -->

                    <li
                        class="nav-divider"
                        aria-hidden="true"
                    ></li>


                    <li>
                        <a
                            href="<?php echo CART_URL; ?>cart.php"
                            class="nav-link<?php echo $current_page === 'cart' ? ' active' : ''; ?>"
                            data-nav-key="cart"
                        >
                            CART

                            <?php if ($cart_count > 0) : ?>
                                <span class="cart-badge">
                                    <?php echo $cart_count; ?>
                                </span>
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
                            <a
                                href="<?php echo REGISTER_URL; ?>logout.php"
                                class="nav-link"
                                data-nav-key="logout"
                            >
                                LOGOUT
                            </a>
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


            <!-- Hamburger menu button (mobile only) -->

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


    <!-- =========================================================
         STATUS BAR — thin technical strip above the main nav
    ========================================================== -->

    <div class="status-bar">
        <div class="container status-bar-inner">

            <span class="status-left">
                <?php echo $status_left; ?>
            </span>

            <span class="status-right">
                <?php echo $status_right; ?>
            </span>

        </div>
    </div>


    <main>

