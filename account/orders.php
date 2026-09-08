<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'account';
$account_tab = 'orders';

require_login();

if (is_admin()) {
    header('Location: ' . ADMIN_URL . 'orders.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT *
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
$stmt->execute([current_user_id()]);
$orders = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
        <div class="dashboard-heading">
            <div>
                <p class="tech-label">// ORDER_HISTORY</p>
                <h1 class="section-heading">YOUR ORDERS</h1>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/account-nav.php'; ?>

        <div class="dashboard-table-wrap">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>ORDER</th>
                        <th>AMOUNT</th>
                        <th>STATUS</th>
                        <th>DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                        <tr>
                            <td colspan="4">No orders yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                                </td>

                                <td><?php echo format_price($order['total_amount']); ?></td>

                                <td>
                                    <span class="status-badge status-<?php echo safe_output($order['status']); ?>">
                                        <?php echo strtoupper(safe_output($order['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
