<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';
$admin_tab = 'reviews';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $review_id = (int) ($_POST['review_id'] ?? 0);

    $stmt = $conn->prepare('DELETE FROM reviews WHERE id = ?');
    $stmt->execute([$review_id]);

    header('Location: reviews.php');
    exit;
}

$sql = '
    SELECT
        r.*,
        u.full_name,
        p.name AS product_name
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    JOIN products p ON p.id = r.product_id
    ORDER BY r.created_at DESC
';

$stmt = $conn->prepare($sql);
$stmt->execute();
$reviews = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
        <div class="dashboard-heading">
            <div>
                <p class="tech-label">// FEEDBACK_CONTROL</p>
                <h1 class="section-heading">REVIEWS</h1>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div class="review-admin-list">
            <?php if (!$reviews): ?>
                <p class="dashboard-empty">No reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <article>
                        <div class="review-admin-head">
                            <div>
                                <span>
                                    <?php
                                    echo str_repeat('★', (int) $review['rating']);
                                    echo str_repeat('☆', 5 - (int) $review['rating']);
                                    ?>
                                </span>

                                <strong>
                                    <?php echo safe_output($review['product_name']); ?>
                                </strong>

                                <small>
                                    <?php echo safe_output($review['full_name']); ?>
                                    //
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </small>
                            </div>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this review?');"
                            >
                                <?php echo csrf_field(); ?>

                                <input
                                    type="hidden"
                                    name="review_id"
                                    value="<?php echo (int) $review['id']; ?>"
                                >

                                <button class="admin-action-link admin-action-delete">
                                    DELETE
                                </button>
                            </form>
                        </div>

                        <p><?php echo nl2br(safe_output($review['comment'])); ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
