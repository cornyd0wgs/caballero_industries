<?php

    require_once __DIR__ . '/../stuff.php';
    

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

      <a href="#home" class="logo" aria-label="<?php echo $site_name; ?> — home">
        <img src="images/logo2.png" alt="<?php echo $site_name; ?> logo" class="logo-img">
      </a>

      <nav class="main-nav" id="mainNav">
        <ul class="nav-list">
          <?php foreach ($nav_items as $index => $item) : ?>
            <li>
              <a href="#<?php echo $item['target']; ?>"
                 class="nav-link<?php echo $index === 0 ? ' active' : ''; ?>"
                 data-nav="<?php echo $item['target']; ?>">
                <?php echo $item['label']; ?>
              </a>
            </li>
          <?php endforeach; ?>
           <?php if (is_logged_in()) : ?>
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
