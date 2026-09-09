<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'cart';

require_login(); // must be logged in to have a cart

$user_id = current_user_id();

// Use the account name as the default recipient name at checkout.
$stmt = $conn->prepare('SELECT full_name FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$checkout_user = $stmt->fetch();
$recipient_name = $checkout_user['full_name'] ?? '';

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

$item_count = array_sum(array_column($cart_rows, 'cart_quantity'));

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
      <a href="<?php echo BASE_URL; ?>index.php#gallery" class="text-link">
        Browse the collection →
      </a>

    <?php else : ?>

      <div class="cart-layout">
        <div class="cart-list">

          <?php foreach ($cart_rows as $row) : ?>

            <div class="cart-row">

              <img
                src="<?php echo safe_output(asset_url($row['image'])); ?>"
                alt="<?php echo safe_output($row['name']); ?>"
                class="cart-thumb"
                data-fallback="product"
              >

              <div class="cart-row-info">
                <span class="product-meta-label">PRODUCT</span>
                <a
                  href="<?php echo BASE_URL; ?>product.php?id=<?php echo (int) $row['product_id']; ?>"
                  class="cart-row-name"
                >
                  <?php echo safe_output($row['name']); ?>
                </a>
                <p class="cart-row-price">
                  <?php echo format_price($row['unit_price']); ?> each
                </p>
              </div>

              <div class="cart-row-controls">
                <form class="cart-row-qty-form" method="post" action="cart-action.php">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="cart_item_id" value="<?php echo (int) $row['cart_item_id']; ?>">

                  <label>QUANTITY</label>
                  <div class="quantity-stepper">
                    <button type="button" class="quantity-step" data-qty-action="minus" aria-label="Decrease quantity">−</button>
                    <input
                      type="number"
                      name="quantity"
                      value="<?php echo (int) $row['cart_quantity']; ?>"
                      min="1"
                      max="<?php echo min((int) $row['stock'], MAX_CART_QUANTITY); ?>"
                    >
                    <button type="button" class="quantity-step" data-qty-action="plus" aria-label="Increase quantity">+</button>
                  </div>

                  <button type="submit" class="btn btn-outline cart-update-button">UPDATE CART</button>
                </form>

                <div class="cart-row-total-block">
                  <span>SUBTOTAL</span>
                  <strong><?php echo format_price($row['subtotal']); ?></strong>
                </div>

                <form class="cart-remove-form" method="post" action="cart-action.php">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="cart_item_id" value="<?php echo (int) $row['cart_item_id']; ?>">
                  <button type="submit" class="cart-remove-button">REMOVE ITEM</button>
                </form>
              </div>

            </div>

          <?php endforeach; ?>

        </div>

        <aside class="cart-summary">
          <p class="tech-label">// ORDER_SUMMARY</p>
          <h2>SUMMARY</h2>

          <div class="cart-summary-line">
            <span>Items</span>
            <strong><?php echo (int) $item_count; ?></strong>
          </div>

          <div class="cart-summary-line cart-summary-total">
            <span>TOTAL</span>
            <strong><?php echo format_price($grand_total); ?></strong>
          </div>

          <form method="post" action="cart-action.php" class="checkout-delivery-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="checkout">

            <div class="checkout-delivery-heading">
              <span>DELIVERY DETAILS</span>
              <small>Used for this order only</small>
            </div>

            <div class="checkout-address-grid checkout-address-grid-primary">
              <div class="checkout-field">
                <label for="recipient_name">RECIPIENT NAME</label>
                <input
                  id="recipient_name"
                  type="text"
                  name="recipient_name"
                  maxlength="100"
                  value="<?php echo safe_output($recipient_name); ?>"
                  required
                >
              </div>

              <div class="checkout-field">
                <label for="contact_number">CONTACT NUMBER</label>
                <input
                  id="contact_number"
                  type="tel"
                  name="contact_number"
                  maxlength="30"
                  placeholder="e.g. 0917 123 4567"
                  required
                >
              </div>
            </div>

            <div class="checkout-field">
              <label for="delivery_address">STREET / BARANGAY</label>
              <textarea
                id="delivery_address"
                name="delivery_address"
                rows="3"
                maxlength="255"
                placeholder="House no., street, barangay"
                required
              ></textarea>
            </div>

            <div class="checkout-address-grid">
              <div class="checkout-field">
                <label for="city">CITY / MUNICIPALITY</label>
                <input id="city" type="text" name="city" maxlength="100" required>
              </div>

              <div class="checkout-field">
                <label for="province">PROVINCE</label>
                <input id="province" type="text" name="province" maxlength="100" required>
              </div>
            </div>

            <div class="checkout-field checkout-postal-field">
              <label for="postal_code">POSTAL CODE</label>
              <input
                id="postal_code"
                type="text"
                name="postal_code"
                maxlength="20"
                inputmode="numeric"
                placeholder="e.g. 6200"
                required
              >
            </div>

            <div class="checkout-payment-section">
              <div class="checkout-delivery-heading">
                <span>PAYMENT METHOD</span>
                <small>Choose how you will pay</small>
              </div>

              <div class="payment-method-options">
                <label class="payment-method-card">
                  <input type="radio" name="payment_method" value="cash" checked>
                  <span>
                    <strong>CASH</strong>
                    <small>Pay when the order is delivered.</small>
                  </span>
                </label>

                <label class="payment-method-card">
                  <input type="radio" name="payment_method" value="gcash">
                  <span>
                    <strong>GCASH</strong>
                    <small>Enter the GCash reference number below.</small>
                  </span>
                </label>
              </div>

              <div class="checkout-field gcash-reference-field" data-gcash-reference hidden>
                <label for="gcash_reference">GCASH REFERENCE NUMBER</label>
                <input
                  id="gcash_reference"
                  type="text"
                  name="gcash_reference"
                  maxlength="30"
                  inputmode="numeric"
                  placeholder="e.g. 1234567890123"
                >
                <small class="form-help">Use the reference number shown on your GCash receipt.</small>
              </div>
            </div>

            <button type="submit" class="btn btn-primary cart-checkout-button">
              PLACE ORDER <span class="arrow">→</span>
            </button>
          </form>

          <a href="<?php echo BASE_URL; ?>index.php#gallery" class="cart-continue-link">CONTINUE SHOPPING →</a>
        </aside>
      </div>

    <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>