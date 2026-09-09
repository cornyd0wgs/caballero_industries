<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';
$admin_tab = 'products';

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
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll();
$daily_popular_ids = get_daily_popular_product_ids($conn, DAILY_POPULAR_LIMIT);

$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
            <div class="dashboard-heading">
                <div>
                    <p class="tech-label">// PRODUCT & STOCK CONTROL</p>
                    <h1 class="section-heading">PRODUCTS</h1>
                    <p class="dashboard-subtext">
                        Manage product information, pricing, discounts, stock levels, and storefront visibility.
                    </p>
                </div>

                <a href="add-products.php" class="btn btn-primary">
                    ADD PRODUCT <span class="arrow">→</span>
                </a>
            </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <?php if ($flash_success): ?>
                <p class="form-message form-message-success">
                    <?php echo safe_output($flash_success); ?>
                </p>
            <?php endif; ?>

            <div class="dashboard-table-wrap admin-products-table-wrap">
                <table class="dashboard-table admin-products-table">
                    <thead>
                        <tr>
                            <th>CODE</th>
                            <th>NAME</th>
                            <th>PRICE</th>
                            <th>DISCOUNT</th>
                            <th>STOCK</th>
                            <th>STATUS</th>
                            <th>SOLD</th>
                            <th>FLAGS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $stock = (int) $product['quantity'];
                            $units_sold = (int) $product['units_sold'];
                            $is_popular = in_array((int) $product['id'], $daily_popular_ids, true);

                            if ($stock === 0) {
                                $stock_class = 'stock-zero';
                                $stock_status = 'OUT OF STOCK';
                            } elseif ($stock <= 5) {
                                $stock_class = 'stock-low';
                                $stock_status = 'LOW STOCK';
                            } else {
                                $stock_class = 'stock-ok';
                                $stock_status = 'IN STOCK';
                            }
                            ?>

                            <tr>
                                <td><?php echo safe_output($product['product_code']); ?></td>
                                <td><?php echo safe_output($product['name']); ?></td>
                                <td><?php echo format_price($product['price']); ?></td>

                                <td>
                                    <?php echo $product['discount_price']
                                        ? format_price($product['discount_price'])
                                        : '—'; ?>
                                </td>

                                <td>
                                    <div class="product-stock-control">
                                        <span class="stock-pill <?php echo $stock_class; ?>">
                                            <?php echo $stock; ?>
                                        </span>
                                        <a href="restock-product.php?id=<?php echo (int) $product['id']; ?>">+ RESTOCK</a>
                                    </div>
                                </td>

                                <td>
                                    <span class="admin-stock-badge <?php echo $stock_class; ?>">
                                        <?php echo $stock_status; ?>
                                    </span>
                                </td>

                                <td><?php echo $units_sold; ?></td>

                                <td>
                                    <?php if ($product['is_featured']): ?>
                                        <span class="flag-pill">FEATURED</span>
                                    <?php endif; ?>

                                    <?php if ($is_popular): ?>
                                        <span class="flag-pill flag-popular">POPULAR TODAY</span>
                                    <?php endif; ?>

                                    <?php if ($product['discount_price']): ?>
                                        <span class="flag-pill">SALE</span>
                                    <?php endif; ?>
                                </td>

                                <td class="admin-actions">
                                    <div class="admin-action-group">
                                        <a
                                            href="edit-product.php?id=<?php echo (int) $product['id']; ?>"
                                            class="admin-action-link admin-action-edit"
                                        >
                                            EDIT
                                        </a>

                                        <form
                                            method="post"
                                            action="delete-product.php"
                                            class="admin-delete-form"
                                            onsubmit="return confirm('Delete this product?');"
                                        >
                                            <?php echo csrf_field(); ?>

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?php echo (int) $product['id']; ?>"
                                            >

                                            <button class="admin-action-link admin-action-delete">
                                                DELETE
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
