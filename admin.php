<?php


require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';
require_once 'helpers.php';
require_once 'stuff.php';

$current_page = 'admin';

require_admin();

$products_result = mysqli_query($conn, 'SELECT * FROM products ORDER BY created_at DESC');
$products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);

$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

require 'includes/header.php';
?>

    <section class="admin-section">
      <div class="container">

        <div class="section-intro-split">
          <div>
            <p class="tech-label">// INVENTORY_CONTROL</p>
            <h1 class="section-heading">MANAGE PRODUCTS</h1>
          </div>
          <a href="admin-add-product.php" class="btn btn-primary">ADD PRODUCT <span class="arrow">→</span></a>
        </div>

        <?php if ($flash_success) : ?>
          <p class="form-message form-message-success"><?php echo safe_output($flash_success); ?></p>
        <?php endif; ?>

        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>CODE</th>
                <th>NAME</th>
                <th>PRICE</th>
                <th>DISCOUNT</th>
                <th>STOCK</th>
                <th>FLAGS</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product) : ?>
                <?php
                  $stock = (int) $product['quantity'];
                  $stock_class = 'stock-ok';
                  if ($stock === 0) {
                      $stock_class = 'stock-zero';
                  } elseif ($stock < 5) {
                      $stock_class = 'stock-low';
                  }
                ?>
                <tr>
                  <td><?php echo safe_output($product['product_code']); ?></td>
                  <td><?php echo safe_output($product['name']); ?></td>
                  <td><?php echo format_price($product['price']); ?></td>
                  <td><?php echo $product['discount_price'] ? format_price($product['discount_price']) : '—'; ?></td>
                  <td><span class="stock-pill <?php echo $stock_class; ?>"><?php echo $stock; ?></span></td>
                  <td>
                    <?php if ($product['is_featured']) : ?><span class="flag-pill">FEATURED</span><?php endif; ?>
                    <?php if ($product['is_popular']) : ?><span class="flag-pill">POPULAR</span><?php endif; ?>
                  </td>
                  <td class="admin-actions">
                    <a href="admin-edit-product.php?id=<?php echo (int) $product['id']; ?>" class="text-link">EDIT</a>
                    <form method="post" action="admin-delete-product.php" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                      <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                      <button type="submit" class="btn-small btn-small-remove">DELETE</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </section>

<?php require 'includes/footer.php'; ?>
