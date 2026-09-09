<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';
$admin_tab = 'products';

require_admin();

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare('SELECT id, product_code, name, quantity FROM products WHERE id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$errors = [];
$add_quantity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    $add_quantity = trim($_POST['add_quantity'] ?? '');

    if (!validate_number_range($add_quantity, 1, 10000)) {
        $errors[] = 'Restock quantity must be a whole number from 1 to 10,000.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            'UPDATE products SET quantity = quantity + ? WHERE id = ?'
        );
        $stmt->execute([(int) $add_quantity, $product_id]);

        $_SESSION['flash_success'] =
            'Added ' . (int) $add_quantity . ' units to ' . $product['name'] . '.';

        header('Location: products.php');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
        <div class="dashboard-heading">
            <div>
                <p class="tech-label">// STOCK_INTAKE</p>
                <h1 class="section-heading">RESTOCK PRODUCT</h1>
                <p class="dashboard-subtext">Add inventory without overwriting the product's current stock.</p>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div class="admin-form-container restock-panel">
            <?php if ($errors): ?>
                <ul class="form-message form-message-error">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo safe_output($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="restock-product-summary">
                <span><?php echo safe_output($product['product_code']); ?></span>
                <h2><?php echo safe_output($product['name']); ?></h2>
                <p>CURRENT STOCK <strong><?php echo (int) $product['quantity']; ?> UNITS</strong></p>
            </div>

            <form method="post" class="admin-form restock-form">
                <?php echo csrf_field(); ?>

                <div class="form-row">
                    <label for="add_quantity">QUANTITY TO ADD</label>
                    <input
                        id="add_quantity"
                        type="number"
                        name="add_quantity"
                        min="1"
                        max="10000"
                        step="1"
                        value="<?php echo safe_output($add_quantity); ?>"
                        required
                    >
                    <small class="form-help">Example: current stock 13 + restock 10 = new stock 23.</small>
                </div>

                <div class="restock-actions">
                    <button type="submit" class="btn btn-primary">ADD STOCK →</button>
                    <a href="products.php" class="btn btn-outline">CANCEL</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
