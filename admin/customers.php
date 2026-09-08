<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

$current_page = 'admin';
$admin_tab = 'customers';

require_admin();

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.age,
        u.created_at,
        COUNT(o.id) AS order_count,
        COALESCE(
            SUM(
                CASE
                    WHEN o.status = 'completed' THEN o.total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$customers = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
        <div class="dashboard-heading">
            <div>
                <p class="tech-label">// CUSTOMER_DIRECTORY</p>
                <h1 class="section-heading">CUSTOMERS</h1>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div class="dashboard-table-wrap">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>AGE</th>
                        <th>ORDERS</th>
                        <th>TOTAL SPENT</th>
                        <th>JOINED</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$customers): ?>
                        <tr>
                            <td colspan="6">No customers yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo safe_output($customer['full_name']); ?></td>
                                <td><?php echo safe_output($customer['email']); ?></td>
                                <td><?php echo (int) $customer['age']; ?></td>
                                <td><?php echo (int) $customer['order_count']; ?></td>
                                <td><?php echo format_price($customer['total_spent']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
