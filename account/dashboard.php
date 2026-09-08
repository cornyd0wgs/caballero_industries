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

$stmt = $conn->prepare('SELECT full_name, email, age, created_at FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $conn->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmt->execute([$user_id]);
$order_count = (int) $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_count = (int) $stmt->fetchColumn();

$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM orders
     WHERE user_id = ? AND status = 'completed'"
);
$stmt->execute([$user_id]);
$total_spent = (float) $stmt->fetchColumn();

$cart_count = get_cart_count($conn, $user_id);

$stmt = $conn->prepare(
    'SELECT id, total_amount, status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 5'
);
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="account-shell">
    <div class="container">
        <section class="account-masthead">
            <div class="account-masthead-copy">
                <p class="account-kicker">CUSTOMER ACCOUNT // PERSONAL HUB</p>
                <h1>MY ACCOUNT</h1>
                <p>Track your orders, manage your profile, and return to the collection when you are ready.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>index.php#gallery" class="btn btn-primary">CONTINUE SHOPPING →</a>
        </section>

        <?php require __DIR__ . '/../includes/account-nav.php'; ?>

        <section class="account-profile-banner">
            <div class="account-profile-identity">
                <div class="account-profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <span class="account-member-since">CUSTOMER SINCE <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    <h2><?php echo safe_output($user['full_name']); ?></h2>
                    <p><?php echo safe_output($user['email']); ?></p>
                </div>
            </div>
            <a href="profile.php" class="account-outline-button">EDIT PROFILE</a>
        </section>

        <div class="account-stat-grid">
            <article class="account-stat-card">
                <span>ALL ORDERS</span>
                <strong><?php echo $order_count; ?></strong>
                <small>Your complete order history</small>
            </article>
            <article class="account-stat-card">
                <span>COMPLETED</span>
                <strong><?php echo $completed_count; ?></strong>
                <small>Successfully completed orders</small>
            </article>
            <article class="account-stat-card">
                <span>TOTAL SPENT</span>
                <strong><?php echo format_price($total_spent); ?></strong>
                <small>Completed purchases only</small>
            </article>
            <article class="account-stat-card account-stat-accent">
                <span>CART ITEMS</span>
                <strong><?php echo $cart_count; ?></strong>
                <small>Waiting in your cart</small>
            </article>
        </div>

        <div class="account-content-grid">
            <section class="account-card account-orders-card">
                <div class="account-card-heading">
                    <div>
                        <span>RECENT ACTIVITY</span>
                        <h2>YOUR ORDERS</h2>
                    </div>
                    <a href="orders.php">VIEW ALL →</a>
                </div>

                <?php if (!$recent_orders): ?>
                    <div class="account-empty-state">
                        <strong>NO ORDERS YET</strong>
                        <p>Your recent purchases will appear here.</p>
                        <a href="<?php echo BASE_URL; ?>index.php#gallery">EXPLORE COLLECTION →</a>
                    </div>
                <?php else: ?>
                    <div class="account-order-list">
                        <?php foreach ($recent_orders as $order): ?>
                            <article class="account-order-row">
                                <div>
                                    <span>ORDER</span>
                                    <strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                </div>
                                <div>
                                    <span>DATE</span>
                                    <strong><?php echo date('M d, Y', strtotime($order['created_at'])); ?></strong>
                                </div>
                                <div>
                                    <span>TOTAL</span>
                                    <strong><?php echo format_price($order['total_amount']); ?></strong>
                                </div>
                                <span class="status-badge status-<?php echo safe_output($order['status']); ?>">
                                    <?php echo strtoupper(safe_output($order['status'])); ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="account-card account-profile-summary">
                <div class="account-card-heading">
                    <div>
                        <span>ACCOUNT DETAILS</span>
                        <h2>PROFILE</h2>
                    </div>
                    <a href="profile.php">EDIT →</a>
                </div>
                <dl class="account-detail-list">
                    <div><dt>FULL NAME</dt><dd><?php echo safe_output($user['full_name']); ?></dd></div>
                    <div><dt>EMAIL</dt><dd><?php echo safe_output($user['email']); ?></dd></div>
                    <div><dt>AGE</dt><dd><?php echo (int) $user['age']; ?></dd></div>
                    <div><dt>ACCOUNT STATUS</dt><dd class="account-active">ACTIVE</dd></div>
                </dl>
            </aside>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
