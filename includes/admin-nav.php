<?php
$admin_tab = $admin_tab ?? 'overview';

$admin_tabs = [
    'overview' => ['DASHBOARD', 'admin.php', 'dashboard'],
    'orders' => ['ORDERS', 'orders.php', 'orders'],
    'products' => ['PRODUCTS', 'products.php', 'products'],
    'customers' => ['CUSTOMERS', 'customers.php', 'customers'],
    'reviews' => ['REVIEWS', 'reviews.php', 'reviews'],
    'profile' => ['PROFILE', 'profile.php', 'profile'],
];

function admin_nav_icon(string $icon): string
{
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12l2 4v14H4V7l2-4Z"/><path d="M4 8h16M9 12h6M9 16h6"/></svg>',
        'products' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4-8 4-8-4 8-4Z"/><path d="m4 7 8 4 8-4M4 7v10l8 4 8-4V7M12 11v10"/></svg>',
        'customers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-4 2.5-6 6-6s6 2 6 6M16 6c2 0 4 1.5 4 4s-2 4-4 4"/></svg>',
        'reviews' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c.7-4.5 3.5-7 8-7s7.3 2.5 8 7"/></svg>',
    ];

    return $icons[$icon] ?? '';
}
?>

<aside class="admin-sidebar" aria-label="Admin navigation">
    <div class="admin-sidebar-brand">
        <a href="<?php echo ADMIN_URL; ?>admin.php" aria-label="Caballero Industries admin dashboard">
            <img src="<?php echo BASE_URL; ?>assets/header-logo.png" alt="Caballero Industries">
        </a>
        <span>ADMIN SYSTEM // V1</span>
    </div>

    <div class="admin-sidebar-label">COMMAND</div>

    <nav class="admin-sidebar-nav">
        <?php foreach ($admin_tabs as $key => $tab): ?>
            <a
                href="<?php echo ADMIN_URL . $tab[1]; ?>"
                class="admin-sidebar-link<?php echo $admin_tab === $key ? ' active' : ''; ?>"
            >
                <span class="admin-sidebar-icon"><?php echo admin_nav_icon($tab[2]); ?></span>
                <span><?php echo $tab[0]; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-footer">
        <p>TACTICAL GEAR<br>FOR REAL WORLDS</p>
        <span>VER. 25.4.19</span>
    </div>
</aside>
