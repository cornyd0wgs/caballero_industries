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

// Suggest the next code, such as CI 011. The admin can still edit it.
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM products');
$stmt->execute();
$next_number = (int) $stmt->fetch()['total'] + 1;
$suggested_code = 'CI ' . str_pad($next_number, 3, '0', STR_PAD_LEFT);

$errors = [];
$old = [
    'product_code' => $suggested_code,
    'name' => '',
    'description' => '',
    'price' => '',
    'discount_price' => '',
    'quantity' => '',
];
$is_featured = 0;

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
        'quantity' => trim($_POST['quantity'] ?? ''),
    ];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    $errors = array_merge($errors, validate_product_values($old));

    if (empty($errors) && product_code_exists($conn, $old['product_code'])) {
        $errors[] = 'That product code is already in use.';
    }

    $image_result = save_product_image($_FILES['image'] ?? []);
    if ($image_result['error']) {
        $errors[] = $image_result['error'];
    }

    if (empty($errors)) {
        $price = (float) $old['price'];
        $discount_price = $old['discount_price'] !== ''
            ? (float) $old['discount_price']
            : null;
        $quantity = (int) $old['quantity'];

        $stmt = $conn->prepare(
            'INSERT INTO products
                (product_code, name, description, price, discount_price, quantity, image, is_featured)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $old['product_code'],
            $old['name'],
            $old['description'],
            $price,
            $discount_price,
            $quantity,
            $image_result['path'],
            $is_featured,
        ]);

        $_SESSION['flash_success'] = 'Product "' . $old['name'] . '" added successfully.';
        header('Location: products.php');
        exit;
    }
}

$form_action = 'add-products.php';
$submit_label = 'ADD PRODUCT';
$is_editing = false;

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-nav.php';
?>

<main class="admin-workspace">
    <section class="admin-section">
        <div class="container admin-form-container">
            <p class="tech-label">// NEW_ASSET_ENTRY</p>
            <h1 class="section-heading">ADD PRODUCT</h1>
            <p class="dashboard-subtext">Create the product record, stock level, pricing, discount, and storefront image.</p>

            <?php require __DIR__ . '/../includes/product-form.php'; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
