<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'account';
$account_tab = 'overview';

require_login();

if (is_admin()) {
    header('Location: ' . ADMIN_URL . 'admin.php');
    exit;
}

$user_id = current_user_id();

$stmt = $conn->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmt->execute([$user_id]);
$order_count = (int) $stmt->fetchColumn();

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM orders
     WHERE user_id = ? AND status = 'completed'"
);
$stmt->execute([$user_id]);
$total_spent = (float) $stmt->fetchColumn();

$cart_count = get_cart_count($conn, $user_id);

$stmt = $conn->prepare(
    'SELECT *
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 5'
);
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
        <div class="dashboard-heading">
            <div>
                <p class="tech-label">// OPERATOR_ACCOUNT</p>
                <h1 class="section-heading">
                    WELCOME, <?php echo strtoupper(safe_output(current_user_name())); ?>
                </h1>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/account-nav.php'; ?>

        <div class="metric-grid metric-grid-three">
            <article class="metric-card">
                <span>YOUR ORDERS</span>
                <strong><?php echo $order_count; ?></strong>
                <small>All transactions</small>
            </article>

            <article class="metric-card">
                <span>TOTAL SPENT</span>
                <strong><?php echo format_price($total_spent); ?></strong>
                <small>Completed orders</small>
            </article>

            <article class="metric-card">
                <span>CART ITEMS</span>
                <strong><?php echo $cart_count; ?></strong>
                <small>Ready for checkout</small>
            </article>
        </div>

        <section class="dashboard-panel">
            <div class="panel-heading">
                <div>
                    <span>RECENT_ORDERS // 01</span>
                    <h2>YOUR ACTIVITY</h2>
                </div>

                <a class="text-link compact-link" href="orders.php">
                    VIEW ALL →
                </a>
            </div>

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
                        <?php if (!$recent_orders): ?>
                            <tr>
                                <td colspan="4">You have not placed an order yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
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
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
