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
    'SELECT id, recipient_name, contact_number, delivery_address, city, province, postal_code, total_amount, status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
$stmt->execute([current_user_id()]);
$orders = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="account-shell">
    <div class="container">
        <section class="account-page-heading">
            <div>
                <p class="account-kicker">ORDER HISTORY // CUSTOMER RECORD</p>
                <h1>MY ORDERS</h1>
                <p>Follow the status and value of every order placed with Caballero Industries.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>index.php#gallery" class="account-outline-button">SHOP COLLECTION</a>
        </section>

        <?php require __DIR__ . '/../includes/account-nav.php'; ?>

        <section class="account-card account-orders-full">
            <?php if (!$orders): ?>
                <div class="account-empty-state account-empty-large">
                    <strong>YOUR ORDER HISTORY IS EMPTY</strong>
                    <p>Once you complete checkout, your orders will be recorded here.</p>
                    <a href="<?php echo BASE_URL; ?>index.php#gallery">EXPLORE COLLECTION →</a>
                </div>
            <?php else: ?>
                <div class="account-order-list account-order-list-full">
                    <?php foreach ($orders as $order): ?>
                        <article class="account-order-row account-order-row-full">
                            <div>
                                <span>ORDER</span>
                                <strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                            </div>
                            <div>
                                <span>PLACED</span>
                                <strong><?php echo date('M d, Y', strtotime($order['created_at'])); ?></strong>
                                <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div class="account-order-delivery">
                                <span>DELIVER TO</span>
                                <?php if (!empty($order['delivery_address'])): ?>
                                    <strong><?php echo safe_output($order['recipient_name']); ?></strong>
                                    <small>
                                        <?php echo safe_output($order['delivery_address']); ?>,
                                        <?php echo safe_output($order['city']); ?>,
                                        <?php echo safe_output($order['province']); ?>
                                        <?php echo safe_output($order['postal_code']); ?>
                                    </small>
                                <?php else: ?>
                                    <strong>Address not recorded</strong>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span>AMOUNT</span>
                                <strong><?php echo format_price($order['total_amount']); ?></strong>
                            </div>
                            <div>
                                <span>STATUS</span>
                                <span class="status-badge status-<?php echo safe_output($order['status']); ?>">
                                    <?php echo strtoupper(safe_output($order['status'])); ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
