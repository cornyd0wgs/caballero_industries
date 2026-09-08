<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';
$admin_tab = 'overview';
require_admin();

$revenue = (float) $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'"
)->fetchColumn();

$order_count = (int) $conn->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$customer_count = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$low_stock_count = (int) $conn->query('SELECT COUNT(*) FROM products WHERE quantity <= 5')->fetchColumn();
$out_of_stock_count = (int) $conn->query('SELECT COUNT(*) FROM products WHERE quantity = 0')->fetchColumn();
$completed_orders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
$pending_orders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$processing_orders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
$cancelled_orders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();
$today_revenue = (float) $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM orders
     WHERE status = 'completed' AND DATE(created_at) = CURDATE()"
)->fetchColumn();
$today_orders = (int) $conn->query(
    'SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()'
)->fetchColumn();

$average_order_value = $completed_orders > 0
    ? $revenue / $completed_orders
    : 0;

$stmt = $conn->prepare(
    "SELECT
        o.id,
        u.full_name,
        o.total_amount,
        o.status,
        o.created_at
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
$stmt->execute();
$recent_orders = $stmt->fetchAll();

$stmt = $conn->prepare(
    "SELECT
        oi.product_id,
        oi.product_name,
        p.image,
        SUM(oi.quantity) AS units_sold,
        SUM(oi.quantity * oi.unit_price) AS revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE o.status = 'completed'
     GROUP BY oi.product_id, oi.product_name, p.image
     ORDER BY units_sold DESC, revenue DESC
     LIMIT 5"
);
$stmt->execute();
$top_products = $stmt->fetchAll();

$stmt = $conn->prepare(
    'SELECT product_code, name, quantity, image
     FROM products
     ORDER BY quantity ASC, name ASC
     LIMIT 4'
);
$stmt->execute();
$stock_watch = $stmt->fetchAll();

$stmt = $conn->prepare(
    "SELECT
        r.rating,
        r.comment,
        r.created_at,
        u.full_name,
        p.name AS product_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     JOIN products p ON p.id = r.product_id
     ORDER BY r.created_at DESC
     LIMIT 3"
);
$stmt->execute();
$recent_reviews = $stmt->fetchAll();

$revenue_by_day = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $revenue_by_day[$date] = 0;
}

$stmt = $conn->prepare(
    "SELECT DATE(created_at) AS sale_date, SUM(total_amount) AS revenue
     FROM orders
     WHERE status = 'completed'
       AND created_at >= CURDATE() - INTERVAL 6 DAY
     GROUP BY DATE(created_at)"
);
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $revenue_by_day[$row['sale_date']] = (float) $row['revenue'];
}

$chart_width = 700;
$chart_height = 220;
$chart_padding_x = 24;
$chart_padding_y = 24;
$chart_max = max(1, max($revenue_by_day));
$chart_points = [];
$chart_circles = [];
$chart_values = array_values($revenue_by_day);
$chart_dates = array_keys($revenue_by_day);
$point_count = count($chart_values);

foreach ($chart_values as $index => $amount) {
    $x = $chart_padding_x;
    if ($point_count > 1) {
        $x += ($index / ($point_count - 1)) * ($chart_width - ($chart_padding_x * 2));
    }

    $usable_height = $chart_height - ($chart_padding_y * 2);
    $y = $chart_height - $chart_padding_y - (($amount / $chart_max) * $usable_height);

    $chart_points[] = round($x, 1) . ',' . round($y, 1);
    $chart_circles[] = ['x' => $x, 'y' => $y, 'amount' => $amount];
}

$status_counts = [
    'completed' => $completed_orders,
    'processing' => $processing_orders,
    'pending' => $pending_orders,
    'cancelled' => $cancelled_orders,
];

$status_total = max(1, array_sum($status_counts));
$completed_end = ($completed_orders / $status_total) * 360;
$processing_end = $completed_end + (($processing_orders / $status_total) * 360);
$pending_end = $processing_end + (($pending_orders / $status_total) * 360);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-nav.php';
?>

<section class="admin-workspace">
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">// STORE COMMAND CENTER</p>
            <h1>Hello, <?php echo safe_output($_SESSION['user_name'] ?? 'Admin'); ?>.</h1>
            <p>Here is what is happening with Caballero Industries today.</p>
        </div>

        <div class="admin-date-block">
            <strong><?php echo date('F j, Y'); ?></strong>
            <span><?php echo strtoupper(date('l, g:i A')); ?></span>
        </div>
    </div>

    <div class="admin-metric-grid">
        <article class="admin-metric-card">
            <div class="admin-metric-icon">01</div>
            <div>
                <span>TOTAL ORDERS</span>
                <strong><?php echo $order_count; ?></strong>
                <small><?php echo $today_orders; ?> created today</small>
            </div>
        </article>

        <article class="admin-metric-card">
            <div class="admin-metric-icon">02</div>
            <div>
                <span>TOTAL REVENUE</span>
                <strong><?php echo format_price($revenue); ?></strong>
                <small><?php echo format_price($today_revenue); ?> today</small>
            </div>
        </article>

        <article class="admin-metric-card">
            <div class="admin-metric-icon">03</div>
            <div>
                <span>REGISTERED CUSTOMERS</span>
                <strong><?php echo $customer_count; ?></strong>
                <small>Customer accounts</small>
            </div>
        </article>

        <article class="admin-metric-card<?php echo $low_stock_count > 0 ? ' alert' : ''; ?>">
            <div class="admin-metric-icon">04</div>
            <div>
                <span>LOW STOCK ITEMS</span>
                <strong><?php echo $low_stock_count; ?></strong>
                <small><?php echo $out_of_stock_count; ?> out of stock</small>
            </div>
        </article>
    </div>

    <div class="admin-primary-grid">
        <section class="admin-card admin-revenue-card">
            <div class="admin-card-heading">
                <div>
                    <span>REVENUE_ACTIVITY // 01</span>
                    <h2>REVENUE OVERVIEW</h2>
                </div>
                <div class="admin-period-chip">LAST 7 DAYS</div>
            </div>

            <div class="admin-revenue-summary">
                <div>
                    <span>TOTAL INCOME</span>
                    <strong><?php echo format_price($revenue); ?></strong>
                </div>
                <div>
                    <span>AVERAGE ORDER</span>
                    <strong><?php echo format_price($average_order_value); ?></strong>
                </div>
            </div>

            <div class="admin-line-chart">
                <svg viewBox="0 0 <?php echo $chart_width; ?> <?php echo $chart_height; ?>" role="img" aria-label="Revenue for the last seven days">
                    <defs>
                        <linearGradient id="revenueArea" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#ff6a00" stop-opacity="0.32" />
                            <stop offset="100%" stop-color="#ff6a00" stop-opacity="0" />
                        </linearGradient>
                    </defs>

                    <line x1="24" y1="50" x2="676" y2="50" class="chart-grid-line" />
                    <line x1="24" y1="110" x2="676" y2="110" class="chart-grid-line" />
                    <line x1="24" y1="170" x2="676" y2="170" class="chart-grid-line" />

                    <?php if ($chart_points): ?>
                        <polygon
                            points="<?php echo implode(' ', $chart_points); ?> 676,196 24,196"
                            class="chart-area"
                        />
                        <polyline points="<?php echo implode(' ', $chart_points); ?>" class="chart-line" />

                        <?php foreach ($chart_circles as $point): ?>
                            <circle cx="<?php echo round($point['x'], 1); ?>" cy="<?php echo round($point['y'], 1); ?>" r="4" class="chart-point" />
                        <?php endforeach; ?>
                    <?php endif; ?>
                </svg>

                <div class="admin-chart-labels">
                    <?php foreach ($chart_dates as $date): ?>
                        <span><?php echo date('M j', strtotime($date)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="admin-card admin-status-card">
            <div class="admin-card-heading">
                <div>
                    <span>FULFILLMENT // 02</span>
                    <h2>ORDER STATUS</h2>
                </div>
            </div>

            <div class="admin-status-content">
                <div
                    class="admin-status-donut"
                    style="--completed-end: <?php echo round($completed_end, 2); ?>deg; --processing-end: <?php echo round($processing_end, 2); ?>deg; --pending-end: <?php echo round($pending_end, 2); ?>deg;"
                >
                    <div>
                        <strong><?php echo $order_count; ?></strong>
                        <span>Total Orders</span>
                    </div>
                </div>

                <div class="admin-status-legend">
                    <?php foreach ($status_counts as $status => $count): ?>
                        <?php $percent = ($count / $status_total) * 100; ?>
                        <div>
                            <span class="legend-dot legend-<?php echo $status; ?>"></span>
                            <b><?php echo ucfirst($status); ?></b>
                            <strong><?php echo $count; ?></strong>
                            <small><?php echo number_format($percent, 1); ?>%</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="admin-secondary-grid">
        <section class="admin-card admin-orders-card">
            <div class="admin-card-heading">
                <div>
                    <span>TRANSACTION_LOG // 03</span>
                    <h2>RECENT ORDERS</h2>
                </div>
                <a href="<?php echo ADMIN_URL; ?>orders.php">VIEW ALL →</a>
            </div>

            <div class="admin-table-scroll">
                <table class="admin-dashboard-table">
                    <thead>
                        <tr>
                            <th>ORDER</th>
                            <th>CUSTOMER</th>
                            <th>DATE</th>
                            <th>TOTAL</th>
                            <th>STATUS</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recent_orders): ?>
                            <tr><td colspan="6">No orders yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo safe_output($order['full_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo safe_output($order['status']); ?>">
                                            <?php echo ucfirst(safe_output($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><a class="admin-row-link" href="<?php echo ADMIN_URL; ?>orders.php">VIEW</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card admin-top-products-card">
            <div class="admin-card-heading">
                <div>
                    <span>TOP_MOVERS // 04</span>
                    <h2>TOP SELLING PRODUCTS</h2>
                </div>
                <a href="<?php echo ADMIN_URL; ?>products.php">VIEW ALL →</a>
            </div>

            <div class="admin-product-rank-list">
                <?php if (!$top_products): ?>
                    <p class="admin-empty">No completed sales yet.</p>
                <?php else: ?>
                    <?php foreach ($top_products as $index => $product): ?>
                        <div class="admin-product-rank-row">
                            <span class="rank-number"><?php echo $index + 1; ?></span>
                            <img src="<?php echo asset_url($product['image']); ?>" alt="">
                            <div>
                                <strong><?php echo safe_output($product['product_name']); ?></strong>
                                <small><?php echo format_price($product['revenue']); ?> revenue</small>
                            </div>
                            <b><?php echo (int) $product['units_sold']; ?> sold</b>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="admin-bottom-grid">
        <section class="admin-card">
            <div class="admin-card-heading">
                <div>
                    <span>STOCK_WATCH // 05</span>
                    <h2>INVENTORY STATUS</h2>
                </div>
                <a href="<?php echo ADMIN_URL; ?>products.php">VIEW PRODUCTS →</a>
            </div>

            <div class="admin-inventory-list">
                <?php foreach ($stock_watch as $item): ?>
                    <?php
                    $quantity = (int) $item['quantity'];
                    $stock_label = 'IN STOCK';
                    $stock_class = 'stock-ok';

                    if ($quantity === 0) {
                        $stock_label = 'OUT OF STOCK';
                        $stock_class = 'stock-zero';
                    } elseif ($quantity <= 5) {
                        $stock_label = 'LOW STOCK';
                        $stock_class = 'stock-low';
                    }
                    ?>
                    <div class="admin-inventory-row">
                        <img src="<?php echo asset_url($item['image']); ?>" alt="">
                        <div>
                            <strong><?php echo safe_output($item['name']); ?></strong>
                            <small><?php echo safe_output($item['product_code']); ?></small>
                        </div>
                        <b><?php echo $quantity; ?></b>
                        <span class="admin-stock-badge <?php echo $stock_class; ?>"><?php echo $stock_label; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-heading">
                <div>
                    <span>FEEDBACK_FEED // 06</span>
                    <h2>RECENT REVIEWS</h2>
                </div>
                <a href="<?php echo ADMIN_URL; ?>reviews.php">VIEW ALL →</a>
            </div>

            <div class="admin-review-feed">
                <?php if (!$recent_reviews): ?>
                    <p class="admin-empty">No customer reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_reviews as $review): ?>
                        <article>
                            <div class="admin-review-avatar">
                                <?php echo strtoupper(substr($review['full_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="admin-review-meta">
                                    <strong><?php echo safe_output($review['full_name']); ?></strong>
                                    <span><?php echo str_repeat('★', (int) $review['rating']); ?></span>
                                </div>
                                <small><?php echo safe_output($review['product_name']); ?> · <?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                                <p><?php echo safe_output($review['comment']); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-heading">
                <div>
                    <span>COMMAND_SHORTCUTS // 07</span>
                    <h2>QUICK ACTIONS</h2>
                </div>
            </div>

            <div class="admin-quick-actions">
                <a href="<?php echo ADMIN_URL; ?>add-products.php">
                    <span>+</span>
                    <b>ADD PRODUCT</b>
                </a>
                <a href="<?php echo ADMIN_URL; ?>orders.php">
                    <span>↗</span>
                    <b>VIEW ORDERS</b>
                </a>
                <a href="<?php echo ADMIN_URL; ?>products.php">
                    <span>□</span>
                    <b>MANAGE PRODUCTS</b>
                </a>
                <a href="<?php echo ADMIN_URL; ?>customers.php">
                    <span>◎</span>
                    <b>VIEW CUSTOMERS</b>
                </a>
            </div>
        </section>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
