<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';
require_once __DIR__ . '/../helpers/product-admin.php';

$current_page = 'admin';
$admin_tab = 'products';

require_admin();

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$errors = [];
$old = [
    'product_code' => $product['product_code'],
    'name' => $product['name'],
    'description' => $product['description'],
    'price' => $product['price'],
    'discount_price' => $product['discount_price'],
    'quantity' => $product['quantity'],
];
$is_featured = (int) $product['is_featured'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    $old = [
        'product_code' => trim($_POST['product_code'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'discount_price' => trim($_POST['discount_price'] ?? ''),
        'quantity' => $product['quantity'],
    ];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    $errors = array_merge($errors, validate_product_values($old, false));

    if (
        empty($errors) &&
        product_code_exists($conn, $old['product_code'], $product_id)
    ) {
        $errors[] = 'That product code is already used by another product.';
    }

    $image_result = save_product_image(
        $_FILES['image'] ?? [],
        $product['image']
    );

    if ($image_result['error']) {
        $errors[] = $image_result['error'];
    }

    if (empty($errors)) {
        $price = (float) $old['price'];
        $discount_price = $old['discount_price'] !== ''
            ? (float) $old['discount_price']
            : null;
        $stmt = $conn->prepare(
            'UPDATE products
             SET product_code = ?,
                 name = ?,
                 description = ?,
                 price = ?,
                 discount_price = ?,
                 image = ?,
                 is_featured = ?
             WHERE id = ?'
        );

        $stmt->execute([
            $old['product_code'],
            $old['name'],
            $old['description'],
            $price,
            $discount_price,
            $image_result['path'],
            $is_featured,
            $product_id,
        ]);

        $_SESSION['flash_success'] = 'Product "' . $old['name'] . '" updated.';
        header('Location: products.php');
        exit;
    }
}

$sales_stmt = $conn->prepare(
    "SELECT COALESCE(SUM(oi.quantity), 0)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.product_id = ? AND o.status = 'completed'"
);
$sales_stmt->execute([$product_id]);
$units_sold = (int) $sales_stmt->fetchColumn();

$daily_popular_ids = get_daily_popular_product_ids($conn, DAILY_POPULAR_LIMIT);
$is_popular_today = in_array($product_id, $daily_popular_ids, true);

$form_action = 'edit-product.php?id=' . $product_id;
$submit_label = 'SAVE CHANGES';
$is_editing = true;

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-nav.php';
?>

<main class="admin-workspace">
    <section class="admin-section">
        <div class="container admin-form-container">
            <p class="tech-label">// ASSET_RECORD_UPDATE</p>
            <h1 class="section-heading">EDIT PRODUCT</h1>
            <p class="dashboard-subtext">Update product details, storefront pricing, image, and featured status. Stock is managed separately.</p>

            <?php require __DIR__ . '/../includes/product-form.php'; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
