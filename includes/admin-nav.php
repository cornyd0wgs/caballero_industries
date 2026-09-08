<?php
$admin_tab = $admin_tab ?? 'overview';
$admin_tabs = [
    'overview' => ['OVERVIEW', 'admin.php'],
    'orders' => ['ORDERS', 'orders.php'],
    'products' => ['PRODUCTS', 'products.php'],
    'inventory' => ['INVENTORY', 'inventory.php'],
    'customers' => ['CUSTOMERS', 'customers.php'],
    'reviews' => ['REVIEWS', 'reviews.php'],
    'profile' => ['PROFILE', 'profile.php'],
];
?>
<nav class="dashboard-tabs" aria-label="Admin sections">
  <?php foreach ($admin_tabs as $key => $tab) : ?>
    <a href="<?php echo ADMIN_URL . $tab[1]; ?>" class="dashboard-tab<?php echo $admin_tab === $key ? ' active' : ''; ?>">
      <?php echo $tab[0]; ?>
    </a>
  <?php endforeach; ?>
</nav>
