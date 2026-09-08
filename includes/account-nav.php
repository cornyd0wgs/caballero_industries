<?php
$account_tab = $account_tab ?? 'overview';
$account_tabs = [
    'overview' => ['OVERVIEW', 'dashboard.php'],
    'orders' => ['MY ORDERS', 'orders.php'],
    'profile' => ['PROFILE & SECURITY', 'profile.php'],
];
?>
<nav class="account-tabs" aria-label="Account sections">
    <?php foreach ($account_tabs as $key => $tab): ?>
        <a
            href="<?php echo ACCOUNT_URL . $tab[1]; ?>"
            class="account-tab<?php echo $account_tab === $key ? ' active' : ''; ?>"
        >
            <?php echo safe_output($tab[0]); ?>
        </a>
    <?php endforeach; ?>
</nav>
