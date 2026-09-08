<?php
$account_tab = $account_tab ?? 'overview';
$account_tabs = [
    'overview' => ['OVERVIEW', 'dashboard.php'],
    'orders' => ['ORDERS', 'orders.php'],
    'profile' => ['PROFILE', 'profile.php'],
];
?>
<nav class="dashboard-tabs dashboard-tabs-customer" aria-label="Account sections">
  <?php foreach ($account_tabs as $key => $tab) : ?>
    <a href="<?php echo ACCOUNT_URL . $tab[1]; ?>" class="dashboard-tab<?php echo $account_tab === $key ? ' active' : ''; ?>">
      <?php echo $tab[0]; ?>
    </a>
  <?php endforeach; ?>
</nav>
