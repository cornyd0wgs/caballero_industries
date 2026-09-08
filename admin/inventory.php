<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';
$admin_tab = 'inventory';

require_admin();

$sql = "
    SELECT
        p.*,
        COALESCE(
            SUM(
                CASE
                    WHEN o.status = 'completed' THEN oi.quantity
                    ELSE 0
                END
            ),
            0
        ) AS units_sold
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id
    GROUP BY p.id
    ORDER BY p.quantity ASC, p.name
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll();
$daily_popular_ids = get_daily_popular_product_ids($conn, DAILY_POPULAR_LIMIT);

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
        <div class="dashboard-heading">
            <div>
                <p class="tech-label">// INVENTORY_CONTROL</p>
                <h1 class="section-heading">INVENTORY</h1>
                <p class="dashboard-subtext">
                    Stock levels and automatic sales popularity.
                </p>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div class="inventory-cards">
            <?php foreach ($products as $product): ?>
                <?php
                $stock = (int) $product['quantity'];
                $units_sold = (int) $product['units_sold'];

                if ($stock === 0) {
                    $stock_status = 'OUT OF STOCK';
                } elseif ($stock <= 5) {
                    $stock_status = 'LOW STOCK';
                } else {
                    $stock_status = 'NOMINAL';
                }

                $popularity = in_array((int) $product['id'], $daily_popular_ids, true)
                    ? 'POPULAR TODAY'
                    : 'STANDARD';
                ?>

                <article class="inventory-card">
                    <div>
                        <small><?php echo safe_output($product['product_code']); ?></small>
                        <h3><?php echo safe_output($product['name']); ?></h3>
                    </div>

                    <strong>
                        <?php echo $stock; ?>
                        <small> IN STOCK</small>
                    </strong>

                    <div class="inventory-card-meta">
                        <span><?php echo $units_sold; ?> SOLD</span>
                        <span><?php echo $popularity; ?></span>
                        <span><?php echo $stock_status; ?></span>
                    </div>

                    <a
                        class="text-link compact-link"
                        href="edit-product.php?id=<?php echo (int) $product['id']; ?>"
                    >
                        EDIT PRODUCT →
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
