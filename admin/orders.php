<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';
require_once __DIR__ . '/../controllers/OrderController.php';

$current_page = 'admin';
$admin_tab = 'orders';
$error = null;

require_admin();

$orderController = new OrderController($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your form session expired.';
    } else {
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';

        $result = $orderController->updateStatus($order_id, $new_status);
        if ($result['success']) {
            $_SESSION['flash_success'] = 'Order #' . $order_id . ' updated.';
            header('Location: orders.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$filter = $_GET['status'] ?? 'all';
$valid_filters = ['all', 'pending', 'processing', 'completed', 'cancelled'];

if (!in_array($filter, $valid_filters, true)) {
    $filter = 'all';
}

$orders = $orderController->getAllOrders($filter === 'all' ? null : $filter);

$flash = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-shell">
    <div class="container">
        <div class="dashboard-heading">
            <div>
                <p class="tech-label">// ORDER_CONTROL</p>
                <h1 class="section-heading">ORDERS</h1>
                <p class="dashboard-subtext">
                    Checkout creates a pending order. Admin moves it through fulfillment.
                </p>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <?php if ($flash): ?>
            <p class="form-message form-message-success">
                <?php echo safe_output($flash); ?>
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="form-message form-message-error-single">
                <?php echo safe_output($error); ?>
            </p>
        <?php endif; ?>

        <div class="filter-tabs">
            <?php foreach ($valid_filters as $status_filter): ?>
                <a
                    class="filter-tab<?php echo $filter === $status_filter ? ' active' : ''; ?>"
                    href="?status=<?php echo $status_filter; ?>"
                >
                    <?php echo strtoupper($status_filter); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-table-wrap">
            <table class="dashboard-table order-table">
                <thead>
                    <tr>
                        <th>ORDER</th>
                        <th>CUSTOMER</th>
                        <th>DELIVERY</th>
                        <th>AMOUNT</th>
                        <th>STATUS / PAYMENT</th>
                        <th>DATE</th>
                        <th>UPDATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                        <tr>
                            <td colspan="7">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $status_choices = [
                                'pending' => ['processing', 'completed', 'cancelled'],
                                'processing' => ['completed', 'cancelled'],
                                'completed' => [],
                                'cancelled' => [],
                            ][$order['status']] ?? [];
                            ?>

                            <tr>
                                <td>
                                    #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                                </td>

                                <td>
                                    <?php echo safe_output($order['full_name']); ?>
                                    <small class="table-subtext">
                                        <?php echo safe_output($order['email']); ?>
                                    </small>
                                </td>

                                <td class="order-delivery-cell">
                                    <?php if (!empty($order['delivery_address'])): ?>
                                        <strong><?php echo safe_output($order['recipient_name']); ?></strong>
                                        <small class="table-subtext">
                                            <?php echo safe_output($order['contact_number']); ?>
                                        </small>
                                        <small class="table-subtext">
                                            <?php echo safe_output($order['delivery_address']); ?>,
                                            <?php echo safe_output($order['city']); ?>,
                                            <?php echo safe_output($order['province']); ?>
                                            <?php echo safe_output($order['postal_code']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="muted-text">Address not recorded</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo format_price($order['total_amount']); ?></td>

                                <td class="order-status-payment-cell">
                                    <span class="status-badge status-<?php echo safe_output($order['status']); ?>">
                                        <?php echo strtoupper(safe_output($order['status'])); ?>
                                    </span>
                                    <strong class="order-payment-method">
                                        <?php echo strtoupper(safe_output($order['payment_method'] ?? 'cash')); ?>
                                    </strong>
                                    <?php if (($order['payment_method'] ?? 'cash') === 'gcash' && !empty($order['gcash_reference'])): ?>
                                        <small class="table-subtext">REF <?php echo safe_output($order['gcash_reference']); ?></small>
                                    <?php else: ?>
                                        <small class="table-subtext">PAY ON DELIVERY</small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                </td>

                                <td>
                                    <?php if ($status_choices): ?>
                                        <form method="post" class="status-form">
                                            <?php echo csrf_field(); ?>

                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?php echo (int) $order['id']; ?>"
                                            >

                                            <select name="status">
                                                <?php foreach ($status_choices as $choice): ?>
                                                    <option value="<?php echo $choice; ?>">
                                                        <?php echo strtoupper($choice); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button class="admin-action-link admin-action-edit">
                                                APPLY
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted-text">LOCKED</span>
                                    <?php endif; ?>
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
