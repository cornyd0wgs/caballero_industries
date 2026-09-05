<?php

    require_once __DIR__ . '/../stuff.php';
    require_once __DIR__ . '/../auth/auth.php';
    require_once __DIR__ . '/../helpers.php';
    require_once __DIR__ . '/../database/db.php';


    
if (!isset($current_page)) {
    $current_page = '';
}

$cart_count = is_logged_in() ? get_cart_count($conn, current_user_id()) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $site_name; ?> — Built For Purpose. Driven By Innovation.</title>
  <meta name="description" content="Caballero Industries — high-performance tactical apparel and equipment engineered for durability, function, and unrestricted movement.">
  <link rel="icon" type="image/png" href="images/favicon.png">

  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- =========================================================
       STATUS BAR — thin technical strip above the main nav
  ========================================================== -->
  <div class="status-bar">
    <div class="container status-bar-inner">
      <span class="status-left"><?php echo $status_left; ?></span>
      <span class="status-right"><?php echo $status_right; ?></span>
    </div>
  </div>

  <!-- =========================================================
       HEADER / NAVIGATION
  ========================================================== -->
   <header class="site-header" id="siteHeader">
    <div class="container header-inner">

      <a href="<?php echo $current_page === 'home' ? '#home' : 'index.php'; ?>" class="logo" aria-label="<?php echo $site_name; ?> — home">
        <img src="assets/logo.png" alt="<?php echo $site_name; ?> logo" class="logo-img">
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
              <a href="<?php echo $href; ?>" class="nav-link<?php echo $is_active ? ' active' : ''; ?>">
                <?php echo $item['label']; ?>
              </a>
            </li>
          <?php endforeach; ?>

          <!-- Utility links: cart + account. Sit inside the same
               collapsible menu so they work on mobile too. -->
          <li class="nav-divider" aria-hidden="true"></li>

          <li>
            <a href="cart.php" class="nav-link<?php echo $current_page === 'cart' ? ' active' : ''; ?>">
              CART<?php if ($cart_count > 0) : ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?>
            </a>
          </li>

          <?php if (is_logged_in()) : ?>
            <?php if (is_admin()) : ?>
              <li><a href="admin.php" class="nav-link">ADMIN</a></li>
            <?php endif; ?>
            <li><a href="logout.php" class="nav-link">LOGOUT</a></li>
          <?php else : ?>
            <li><a href="login.php" class="nav-link<?php echo $current_page === 'login' ? ' active' : ''; ?>">LOGIN</a></li>
            <li><a href="register.php" class="nav-link<?php echo $current_page === 'register' ? ' active' : ''; ?>">SIGN UP</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <!-- Hamburger menu button (mobile only) -->
      <button class="hamburger" id="hamburgerBtn" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mainNav">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>

    </div>
  </header>

  <main>
