<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';

$current_page = 'cart';

require_login(); // must be logged in to have a cart

$user_id = current_user_id();

// Join cart_items with products so we get the name/price/image/stock
// in one query instead of looping and querying per-item.
$stmt = $conn->prepare(
    'SELECT cart_items.id AS cart_item_id,
            cart_items.quantity AS cart_quantity,
            products.id AS product_id,
            products.name,
            products.image,
            products.price,
            products.discount_price,
            products.quantity AS stock
     FROM cart_items
     JOIN products ON cart_items.product_id = products.id
     WHERE cart_items.user_id = ?
     ORDER BY cart_items.added_at DESC'
);

$stmt->execute([$user_id]);
$cart_rows = $stmt->fetchAll();

// Work out each row's subtotal and the cart's grand total using
// the discount price when one is set.
$grand_total = 0;

foreach ($cart_rows as &$row) {
    $unit_price = $row['discount_price']
        ? $row['discount_price']
        : $row['price'];

    $row['unit_price'] = $unit_price;
    $row['subtotal'] = $unit_price * $row['cart_quantity'];
    $grand_total += $row['subtotal'];
}

unset($row); // break the reference now that the loop is done

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;

unset(
    $_SESSION['flash_success'],
    $_SESSION['flash_error']
);

require __DIR__ . '/../includes/header.php';
?>

<section class="cart-section">
  <div class="container">
    <p class="tech-label">// SUPPLY_MANIFEST</p>
    <h1 class="section-heading">YOUR CART</h1>

    <?php if ($flash_success) : ?>
      <p class="form-message form-message-success">
        <?php echo safe_output($flash_success); ?>
      </p>
    <?php endif; ?>

    <?php if ($flash_error) : ?>
      <p class="form-message form-message-error-single">
        <?php echo safe_output($flash_error); ?>
      </p>
    <?php endif; ?>

    <?php if (empty($cart_rows)) : ?>

      <p class="body-text">Your cart is empty.</p>
      <a href="index.php#gallery" class="text-link">
        Browse the collection →
      </a>

    <?php else : ?>

      <div class="cart-list">

        <?php foreach ($cart_rows as $row) : ?>

          <div class="cart-row">

            <img
              src="<?php echo safe_output($row['image']); ?>"
              alt="<?php echo safe_output($row['name']); ?>"
              class="cart-thumb"
              data-fallback="product"
            >

            <div class="cart-row-info">

              <a
                href="product.php?id=<?php echo (int) $row['product_id']; ?>"
                class="cart-row-name"
              >
                <?php echo safe_output($row['name']); ?>
              </a>

              <p class="cart-row-price">
                <?php echo format_price($row['unit_price']); ?> each
              </p>

            </div>

            <!-- Update quantity -->
            <form
              class="cart-row-qty-form"
              method="post"
              action="cart-actions.php"
            >
              <input
                type="hidden"
                name="action"
                value="update"
              >

              <input
                type="hidden"
                name="cart_item_id"
                value="<?php echo (int) $row['cart_item_id']; ?>"
              >

              <input
                type="number"
                name="quantity"
                value="<?php echo (int) $row['cart_quantity']; ?>"
                min="1"
                max="<?php echo (int) $row['stock']; ?>"
              >

              <button
                type="submit"
                class="btn-small"
              >
                UPDATE
              </button>
            </form>

            <p class="cart-row-subtotal">
              <?php echo format_price($row['subtotal']); ?>
            </p>

            <!-- Remove item -->
            <form
              method="post"
              action="cart-actions.php"
            >
              <input
                type="hidden"
                name="action"
                value="remove"
              >

              <input
                type="hidden"
                name="cart_item_id"
                value="<?php echo (int) $row['cart_item_id']; ?>"
              >

              <button
                type="submit"
                class="btn-small btn-small-remove"
              >
                REMOVE
              </button>
            </form>

          </div>

        <?php endforeach; ?>

      </div>

      <div class="cart-total-row">
        <span>TOTAL</span>

        <span class="cart-total-amount">
          <?php echo format_price($grand_total); ?>
        </span>
      </div>

      <form method="post" action="cart-actions.php">
        <input
          type="hidden"
          name="action"
          value="checkout"
        >

        <button
          type="submit"
          class="btn btn-primary"
        >
          CHECKOUT <span class="arrow">→</span>
        </button>
      </form>

    <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>