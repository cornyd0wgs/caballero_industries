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


require __DIR__ . '/includes/header.php';
?>

<section class="product-page">
    <div class="container product-detail-grid">

        <div class="product-detail-image">
            <div class="product-tags product-tags-detail">
                <?php if ($units_sold >= POPULAR_THRESHOLD) : ?><span class="slant-tag slant-tag-popular"><span>POPULAR</span></span><?php endif; ?>
                <?php if ($product['discount_price']) : $discount_percent=(int)round((($product['price']-$product['discount_price'])/$product['price'])*100); ?><span class="slant-tag slant-tag-sale"><span>-<?php echo $discount_percent; ?>%</span></span><?php endif; ?>
            </div>
            <img
                src="<?php echo safe_output(asset_url($product['image'])); ?>"
                alt="<?php echo safe_output($product['name']); ?>"
                data-fallback="product"
            >
        </div>

        <div>
            <p class="tech-label">// <?php echo safe_output($product['product_code']); ?></p>
            <h1 class="section-heading"><?php echo safe_output($product['name']); ?></h1>

            <?php if ($product['discount_price']) : ?>
                <p class="product-detail-price">
                    <span class="price-old"><?php echo format_price($product['price']); ?></span>
                    <?php echo format_price($product['discount_price']); ?>
                </p>
            <?php else : ?>
                <p class="product-detail-price"><?php echo format_price($product['price']); ?></p>
            <?php endif; ?>

            <p class="product-rating">
                <?php if (!empty($reviews)) : ?>
                    <?php echo number_format($average_rating, 1); ?>/5 from <?php echo count($reviews); ?> review(s)
                <?php else : ?>
                    No reviews yet
                <?php endif; ?>
            </p>

            <p class="body-text">
                <?php echo nl2br(safe_output($product['description'])); ?>
            </p>

            <p class="body-text">
                Stock: <strong><?php echo (int) $product['quantity']; ?></strong>
            </p>

            <?php if ($flash_error) : ?>
                <p class="form-message form-message-error-single">
                    <?php echo safe_output($flash_error); ?>
                </p>
            <?php endif; ?>

            <?php if ((int) $product['quantity'] > 0) : ?>
                <?php if (is_logged_in()) : ?>
                    <form class="add-to-cart-form" method="post" action="<?php echo CART_URL; ?>cart-action.php">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo (int) $product_id; ?>">

                        <div>
                            <label for="quantity">QUANTITY</label>
                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                min="1"
                                max="<?php echo (int) $product['quantity']; ?>"
                                value="1"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary">
                            ADD TO CART <span class="arrow">→</span>
                        </button>
                    </form>
                <?php else : ?>
                    <a
                        class="btn btn-primary"
                        href="<?php echo REGISTER_URL; ?>login.php?redirect=<?php echo urlencode(BASE_URL . 'product.php?id=' . $product_id); ?>"
                    >
                        LOG IN TO BUY <span class="arrow">→</span>
                    </a>
                <?php endif; ?>
            <?php else : ?>
                <p class="form-message form-message-error-single">This product is currently sold out.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<section class="reviews-section" id="reviews">
    <div class="container">
        <p class="tech-label">// CUSTOMER_FEEDBACK</p>
        <h2 class="section-heading">REVIEWS</h2>

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
                <p class="body-text">Be the first to review this product.</p>
            <?php else : ?>
                <?php foreach ($reviews as $review) : ?>
                    <article class="review-item">
                        <div class="review-header">
                            <span class="review-stars">
                                <?php echo str_repeat('★', (int) $review['rating']); ?><?php echo str_repeat('☆', 5 - (int) $review['rating']); ?>
                            </span>
                            <span class="review-author"><?php echo safe_output($review['full_name']); ?></span>
                        </div>
                        <p class="review-comment"><?php echo nl2br(safe_output($review['comment'])); ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (is_logged_in()) : ?>
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
                    <label for="comment">REVIEW</label>
                    <textarea id="comment" name="comment" rows="4" maxlength="1000" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">POST REVIEW</button>
            </form>
        <?php else : ?>
            <p class="body-text">
                <a href="<?php echo REGISTER_URL; ?>login.php?redirect=<?php echo urlencode(BASE_URL . 'product.php?id=' . $product_id . '#reviews'); ?>">
                    Log in to leave a review.
                </a>
            </p>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
