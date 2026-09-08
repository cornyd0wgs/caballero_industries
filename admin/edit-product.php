<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';

require_admin();

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare(
    'SELECT *
     FROM products
     WHERE id = ?'
);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$errors = array();

// Pre-fill the form with the product's current values.
$old = array(
    'product_code'   => $product['product_code'],
    'name'           => $product['name'],
    'description'    => $product['description'],
    'price'          => $product['price'],
    'discount_price' => $product['discount_price'],
    'quantity'       => $product['quantity'],
);

$is_featured = (int) $product['is_featured'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    $old['product_code']  = trim($_POST['product_code'] ?? '');
    $old['name']          = trim($_POST['name'] ?? '');
    $old['description']   = trim($_POST['description'] ?? '');
    $old['price']         = trim($_POST['price'] ?? '');
    $old['discount_price'] = trim($_POST['discount_price'] ?? '');
    $old['quantity']      = trim($_POST['quantity'] ?? '');

    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if ($old['product_code'] === '') {
        $errors[] = 'Product code is required.';
    }

    if ($old['name'] === '') {
        $errors[] = 'Product name is required.';
    }

    if (!is_numeric($old['price']) || (float) $old['price'] <= 0) {
        $errors[] = 'Price must be a number greater than 0.';
    }

    if (
        $old['discount_price'] !== '' &&
        (!is_numeric($old['discount_price']) || (float) $old['discount_price'] <= 0)
    ) {
        $errors[] = 'Discount price must be a number greater than 0 (or left blank).';
    }

    if (
        $old['discount_price'] !== '' &&
        is_numeric($old['price']) &&
        (float) $old['discount_price'] >= (float) $old['price']
    ) {
        $errors[] = 'Discount price must be lower than the regular price.';
    }

    if (!validate_number_range($old['quantity'], 0, 100000)) {
        $errors[] = 'Quantity must be a whole number of 0 or more.';
    }

    // Product code must stay unique, but ignore this product's own row.
    if (empty($errors)) {
        $stmt = $conn->prepare(
            'SELECT id
             FROM products
             WHERE product_code = ?
             AND id != ?'
        );

        $stmt->execute([
            $old['product_code'],
            $product_id
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'That product code is already used by another product.';
        }
    }

    // Image is optional here.
    // Only replace the current image if a new file was selected.
    $image_path = $product['image'];

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'There was a problem uploading the image. Please try again.';
        } else {
            $allowed_extensions = array(
                'jpg',
                'jpeg',
                'png',
                'webp'
            );

            $extension = strtolower(
                pathinfo(
                    $_FILES['image']['name'],
                    PATHINFO_EXTENSION
                )
            );

            $allowed_mime_types = array('image/jpeg', 'image/png', 'image/webp');
            $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);

            if (
                !in_array($extension, $allowed_extensions, true) ||
                !in_array($mime_type, $allowed_mime_types, true)
            ) {
                $errors[] = 'Image must be a real JPG, PNG, or WEBP file.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image must be smaller than 5MB.';
            } else {
                $safe_filename = uniqid('product_') . '.' . $extension;

                $upload_dir = __DIR__ . '/../images/products/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $destination = $upload_dir . $safe_filename;

                $database_image_path =
                    'images/products/' .
                    $safe_filename;

                if (
                    move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $destination
                    )
                ) {
                    $image_path = $database_image_path;
                } else {
                    $errors[] = 'Could not save the uploaded image.';
                }
            }
        }
    }

    if (empty($errors)) {
        $price = (float) $old['price'];

        $discount_price =
            $old['discount_price'] !== ''
                ? (float) $old['discount_price']
                : null;

        $quantity = (int) $old['quantity'];

        $stmt = $conn->prepare(
            'UPDATE products
             SET product_code = ?,
                 name = ?,
                 description = ?,
                 price = ?,
                 discount_price = ?,
                 quantity = ?,
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
            $quantity,
            $image_path,
            $is_featured,
            $product_id
        ]);

        $_SESSION['flash_success'] =
            'Product "' . $old['name'] . '" updated.';

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

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <div class="container admin-form-container">

        <p class="tech-label">// ASSET_RECORD_UPDATE</p>
        <h1 class="section-heading">EDIT PRODUCT</h1>

        <?php if (!empty($errors)) : ?>
            <ul class="form-message form-message-error">
                <?php foreach ($errors as $error) : ?>
                    <li><?php echo safe_output($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form
            class="admin-form"
            method="post"
            action="edit-product.php?id=<?php echo (int) $product_id; ?>"
            enctype="multipart/form-data"
        >
            <?php echo csrf_field(); ?>

            <div class="form-row">
                <label>CURRENT IMAGE</label>

                <img
                    src="<?php echo safe_output(asset_url($product['image'])); ?>"
                    alt=""
                    class="admin-current-image"
                >
            </div>

            <div class="form-row">
                <label for="product_code">PRODUCT CODE</label>

                <input
                    type="text"
                    id="product_code"
                    name="product_code"
                    required
                    value="<?php echo safe_output($old['product_code']); ?>"
                >
            </div>

            <div class="form-row">
                <label for="name">NAME</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    value="<?php echo safe_output($old['name']); ?>"
                >
            </div>

            <div class="form-row">
                <label for="description">DESCRIPTION</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                ><?php echo safe_output($old['description']); ?></textarea>
            </div>

            <div class="form-row-split">

                <div class="form-row">
                    <label for="price">PRICE (&#8369;)</label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        step="0.01"
                        min="0.01"
                        required
                        value="<?php echo safe_output($old['price']); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="discount_price">
                        DISCOUNT PRICE (&#8369;, optional)
                    </label>

                    <input
                        type="number"
                        id="discount_price"
                        name="discount_price"
                        step="0.01"
                        min="0.01"
                        value="<?php echo safe_output($old['discount_price']); ?>"
                    >
                </div>

            </div>

            <div class="form-row">
                <label for="quantity">QUANTITY IN STOCK</label>

                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    min="0"
                    step="1"
                    required
                    value="<?php echo safe_output($old['quantity']); ?>"
                >
            </div>

            <div class="form-row">
                <label for="image">
                    REPLACE IMAGE (optional — JPG/PNG/WEBP, up to 5MB)
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                >
            </div>

            <div class="form-row-checkboxes">

                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="is_featured"
                        <?php echo $is_featured ? 'checked' : ''; ?>
                    >
                    Show in Featured Collection
                </label>

            </div>

            <div class="auto-popularity">
                <span>AUTOMATIC POPULARITY</span>
                <strong><?php echo $units_sold; ?> SOLD</strong>
                <em><?php echo $units_sold >= POPULAR_THRESHOLD ? 'POPULAR' : 'STANDARD'; ?></em>
            </div>

            <button type="submit" class="btn btn-primary">
                SAVE CHANGES <span class="arrow">→</span>
            </button>

        </form>

    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
