<?php

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/auth/validation.php';
require_once __DIR__ . '/helpers/helpers.php';
require_once __DIR__ . '/helpers/stuff.php';

$current_page = 'product';
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1) Load the product requested in the URL: product.php?id=3
$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="product-page">
        <div class="container">
            <p class="tech-label">// ASSET_NOT_FOUND</p>
            <h1 class="section-heading">PRODUCT NOT FOUND</h1>
            <a href="<?php echo BASE_URL; ?>index.php#gallery" class="text-link">
                Back to collection →
            </a>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$review_errors = array();
$review_success = $_SESSION['review_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['review_success'], $_SESSION['flash_error']);

// 2) Logged-in customers can leave a review on this same page.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'register/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    $rating = $_POST['rating'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    if (!verify_csrf()) {
        $review_errors[] = 'Your form session expired. Please try again.';
    }

    if (!validate_number_range($rating, 1, 5)) {
        $review_errors[] = 'Rating must be from 1 to 5.';
    }

    if ($comment === '' || strlen($comment) > 1000) {
        $review_errors[] = 'Review must be between 1 and 1000 characters.';
    }

    if (empty($review_errors)) {
        $stmt = $conn->prepare(
            'INSERT INTO reviews (product_id, user_id, rating, comment)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            $product_id,
            current_user_id(),
            (int) $rating,
            $comment
        ]);

        $_SESSION['review_success'] = 'Review posted. Thank you!';
        header('Location: ' . BASE_URL . 'product.php?id=' . $product_id . '#reviews');
        exit;
    }
}

// 3) Load reviews and the reviewer's name in one query.
$stmt = $conn->prepare(
    'SELECT reviews.rating,
            reviews.comment,
            reviews.created_at,
            users.full_name
     FROM reviews
     JOIN users ON reviews.user_id = users.id
     WHERE reviews.product_id = ?
     ORDER BY reviews.created_at DESC'
);
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

$average_rating = 0;
if (!empty($reviews)) {
    $rating_total = array_sum(array_column($reviews, 'rating'));
    $average_rating = $rating_total / count($reviews);
}

$display_price = $product['discount_price'] ?: $product['price'];
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


require __DIR__ . '/includes/header.php';
?>

<section class="product-page">
    <div class="container product-detail-grid">

        <div class="product-detail-image-wrap">
            <div class="product-detail-image">
                <div class="product-tags product-tags-detail">
                    <?php if ($is_popular_today) : ?>
                        <span class="slant-tag slant-tag-popular"><span>POPULAR</span></span>
                    <?php endif; ?>
                    <?php if ($product['discount_price']) :
                        $discount_percent = (int) round((($product['price'] - $product['discount_price']) / $product['price']) * 100);
                    ?>
                        <span class="slant-tag slant-tag-sale"><span>-<?php echo $discount_percent; ?>%</span></span>
                    <?php endif; ?>
                </div>

                <img
                    src="<?php echo safe_output(asset_url($product['image'])); ?>"
                    alt="<?php echo safe_output($product['name']); ?>"
                    data-fallback="product"
                >
            </div>
        </div>

        <div class="product-detail-content">
            <p class="tech-label">// <?php echo safe_output($product['product_code']); ?></p>
            <h1 class="section-heading product-detail-title"><?php echo safe_output($product['name']); ?></h1>

            <?php if ($product['discount_price']) : ?>
                <div class="product-detail-price-row">
                    <span class="product-detail-price"><?php echo format_price($product['discount_price']); ?></span>
                    <span class="price-old"><?php echo format_price($product['price']); ?></span>
                </div>
            <?php else : ?>
                <p class="product-detail-price"><?php echo format_price($product['price']); ?></p>
            <?php endif; ?>

            <a href="#reviews" class="product-rating-summary">
                <span class="review-stars" aria-hidden="true">
                    <?php if (!empty($reviews)) : ?>
                        <?php echo str_repeat('★', (int) round($average_rating)); ?><?php echo str_repeat('☆', 5 - (int) round($average_rating)); ?>
                    <?php else : ?>
                        ☆☆☆☆☆
                    <?php endif; ?>
                </span>
                <span>
                    <?php if (!empty($reviews)) : ?>
                        <?php echo number_format($average_rating, 1); ?> · <?php echo count($reviews); ?> review<?php echo count($reviews) === 1 ? '' : 's'; ?>
                    <?php else : ?>
                        No reviews yet — be the first
                    <?php endif; ?>
                </span>
            </a>

            <p class="body-text product-description">
                <?php echo nl2br(safe_output($product['description'])); ?>
            </p>

            <div class="product-stock-row">
                <span class="product-meta-label">AVAILABILITY</span>
                <?php if ((int) $product['quantity'] === 0) : ?>
                    <span class="product-stock-status is-out">OUT OF STOCK</span>
                <?php elseif ((int) $product['quantity'] <= 5) : ?>
                    <span class="product-stock-status is-low">LOW STOCK · <?php echo (int) $product['quantity']; ?> LEFT</span>
                <?php else : ?>
                    <span class="product-stock-status is-ready">IN STOCK · <?php echo (int) $product['quantity']; ?> AVAILABLE</span>
                <?php endif; ?>
            </div>

            <?php if ($flash_error) : ?>
                <p class="form-message form-message-error-single">
                    <?php echo safe_output($flash_error); ?>
                </p>
            <?php endif; ?>

            <div class="product-purchase-panel">
                <?php if ((int) $product['quantity'] > 0) : ?>
                    <?php if (is_logged_in()) : ?>
                        <form class="add-to-cart-form" method="post" action="<?php echo CART_URL; ?>cart-action.php">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo (int) $product_id; ?>">

                            <div class="quantity-control-group">
                                <label for="quantity">QUANTITY</label>
                                <div class="quantity-stepper">
                                    <button type="button" class="quantity-step" data-qty-action="minus" aria-label="Decrease quantity">−</button>
                                    <input
                                        type="number"
                                        id="quantity"
                                        name="quantity"
                                        min="1"
                                        max="<?php echo (int) $product['quantity']; ?>"
                                        value="1"
                                        required
                                    >
                                    <button type="button" class="quantity-step" data-qty-action="plus" aria-label="Increase quantity">+</button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary product-add-button">
                                ADD TO CART <span class="arrow">→</span>
                            </button>
                        </form>
                    <?php else : ?>
                        <a
                            class="btn btn-primary product-add-button"
                            href="<?php echo REGISTER_URL; ?>login.php?redirect=<?php echo urlencode(BASE_URL . 'product.php?id=' . $product_id); ?>"
                        >
                            LOG IN TO BUY <span class="arrow">→</span>
                        </a>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="form-message form-message-error-single">This product is currently sold out.</p>
                <?php endif; ?>
            </div>

            <div class="product-reviews-panel" id="reviews">
                <div class="product-reviews-heading">
                    <div>
                        <p class="tech-label">// CUSTOMER_FEEDBACK</p>
                        <h2>REVIEWS</h2>
                    </div>
                    <div class="review-score">
                        <strong><?php echo !empty($reviews) ? number_format($average_rating, 1) : '—'; ?></strong>
                        <span><?php echo count($reviews); ?> REVIEW<?php echo count($reviews) === 1 ? '' : 'S'; ?></span>
                    </div>
                </div>

                <?php if ($review_success) : ?>
                    <p class="form-message form-message-success"><?php echo safe_output($review_success); ?></p>
                <?php endif; ?>

                <?php if (!empty($review_errors)) : ?>
                    <ul class="form-message form-message-error">
                        <?php foreach ($review_errors as $error) : ?>
                            <li><?php echo safe_output($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="review-list">
                    <?php if (empty($reviews)) : ?>
                        <div class="review-empty">
                            <strong>NO REVIEWS YET</strong>
                            <span>Share your experience with this product.</span>
                        </div>
                    <?php else : ?>
                        <?php foreach ($reviews as $review) : ?>
                            <article class="review-item">
                                <div class="review-header">
                                    <div>
                                        <span class="review-author"><?php echo safe_output($review['full_name']); ?></span>
                                        <span class="review-stars">
                                            <?php echo str_repeat('★', (int) $review['rating']); ?><?php echo str_repeat('☆', 5 - (int) $review['rating']); ?>
                                        </span>
                                    </div>
                                    <time><?php echo date('M j, Y', strtotime($review['created_at'])); ?></time>
                                </div>
                                <p class="review-comment"><?php echo nl2br(safe_output($review['comment'])); ?></p>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (is_logged_in()) : ?>
                    <details class="review-composer" <?php echo !empty($review_errors) ? 'open' : ''; ?>>
                        <summary>WRITE A REVIEW <span>+</span></summary>
                        <form class="review-form" method="post" action="product.php?id=<?php echo (int) $product_id; ?>#reviews">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="review">

                            <div class="form-row">
                                <label for="rating">RATING</label>
                                <select id="rating" name="rating" required>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Average</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Very poor</option>
                                </select>
                            </div>

                            <div class="form-row">
                                <label for="comment">YOUR REVIEW</label>
                                <textarea id="comment" name="comment" rows="4" maxlength="1000" placeholder="How did the product perform?" required><?php echo safe_output($_POST['comment'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">POST REVIEW</button>
                        </form>
                    </details>
                <?php else : ?>
                    <a class="review-login-link" href="<?php echo REGISTER_URL; ?>login.php?redirect=<?php echo urlencode(BASE_URL . 'product.php?id=' . $product_id . '#reviews'); ?>">
                        LOG IN TO WRITE A REVIEW →
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
