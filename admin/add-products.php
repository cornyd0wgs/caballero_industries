<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';

require_admin();

// Suggest the next product code, e.g. "CI 005" — the admin can
// still change it before submitting.
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM products');
$stmt->execute();

$count_row = $stmt->fetch();
$next_number = (int) $count_row['total'] + 1;

$suggested_code = 'CI ' . str_pad(
    $next_number,
    3,
    '0',
    STR_PAD_LEFT
);

$errors = array();

$old = array(
    'product_code' => $suggested_code,
    'name' => '',
    'description' => '',
    'price' => '',
    'discount_price' => '',
    'quantity' => '',
);

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
    $is_popular  = isset($_POST['is_popular']) ? 1 : 0;

    // ---- Validate text fields ----

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
        (
            !is_numeric($old['discount_price']) ||
            (float) $old['discount_price'] <= 0
        )
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

    // Product code must be unique
    if (empty($errors)) {

        $stmt = $conn->prepare(
            'SELECT id
             FROM products
             WHERE product_code = ?'
        );

        $stmt->execute([
            $old['product_code']
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'That product code is already in use.';
        }
    }

    // ---- Handle the image upload (optional) ----

    $image_path = 'images/box.png';

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

            $errors[] =
                'There was a problem uploading the image. Please try again.';

        } else {

            $allowed_extensions = array(
                'jpg',
                'jpeg',
                'png',
                'webp'
            );

            $original_name = $_FILES['image']['name'];

            $extension = strtolower(
                pathinfo(
                    $original_name,
                    PATHINFO_EXTENSION
                )
            );

            $allowed_mime_types = array(
                'image/jpeg',
                'image/png',
                'image/webp'
            );

            $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file(
                $_FILES['image']['tmp_name']
            );

            if (
                !in_array($extension, $allowed_extensions, true) ||
                !in_array($mime_type, $allowed_mime_types, true)
            ) {
                $errors[] = 'Image must be a real JPG, PNG, or WEBP file.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image must be smaller than 5MB.';
            } else {
                $upload_dir = __DIR__ . '/../images/products/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $safe_filename = uniqid('product_') . '.' . $extension;
                $destination = $upload_dir . $safe_filename;
                $database_image_path = 'images/products/' . $safe_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_path = $database_image_path;
                } else {
                    $errors[] = 'Could not save the uploaded image.';
                }
            }
        }
    }

    // ---- All good: insert the product ----

    if (empty($errors)) {

        $price = (float) $old['price'];

        $discount_price =
            $old['discount_price'] !== ''
                ? (float) $old['discount_price']
                : null;

        $quantity = (int) $old['quantity'];

        $stmt = $conn->prepare(
            'INSERT INTO products
            (
                product_code,
                name,
                description,
                price,
                discount_price,
                quantity,
                image,
                is_featured,
                is_popular
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            $is_popular
        ]);

        $_SESSION['flash_success'] =
            'Product "' . $old['name'] . '" added successfully.';

        header('Location: admin.php');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
  <div class="container admin-form-container">

    <p class="tech-label">// NEW_ASSET_ENTRY</p>
    <h1 class="section-heading">ADD PRODUCT</h1>

    <?php if (!empty($errors)) : ?>

      <ul class="form-message form-message-error">

        <?php foreach ($errors as $error) : ?>

          <li>
            <?php echo safe_output($error); ?>
          </li>

        <?php endforeach; ?>

      </ul>

    <?php endif; ?>

    <form
      class="admin-form"
      method="post"
      action="add-products.php"
      enctype="multipart/form-data"
    >
      <?php echo csrf_field(); ?>

      <div class="form-row">

        <label for="product_code">
          PRODUCT CODE
        </label>

        <input
          type="text"
          id="product_code"
          name="product_code"
          required
          value="<?php echo safe_output($old['product_code']); ?>"
        >

      </div>

      <div class="form-row">

        <label for="name">
          NAME
        </label>

        <input
          type="text"
          id="name"
          name="name"
          required
          value="<?php echo safe_output($old['name']); ?>"
        >

      </div>

      <div class="form-row">

        <label for="description">
          DESCRIPTION
        </label>

        <textarea
          id="description"
          name="description"
          rows="4"
        ><?php echo safe_output($old['description']); ?></textarea>

      </div>

      <div class="form-row-split">

        <div class="form-row">

          <label for="price">
            PRICE (&#8369;)
          </label>

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

        <label for="quantity">
          QUANTITY IN STOCK
        </label>

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
          PRODUCT IMAGE (optional — JPG/PNG/WEBP, up to 5MB)
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
            checked
          >
          Show in Featured Collection
        </label>

        <label class="checkbox-label">
          <input
            type="checkbox"
            name="is_popular"
          >
          Mark as Popular (shows in homepage carousel)
        </label>

      </div>

      <button
        type="submit"
        class="btn btn-primary"
      >
        ADD PRODUCT <span class="arrow">→</span>
      </button>

    </form>

  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>