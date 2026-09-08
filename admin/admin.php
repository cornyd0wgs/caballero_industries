<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';
$current_page = 'admin';
$admin_tab = 'overview';
require_admin();

$revenue = (float) $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='completed'")->fetchColumn();
$order_count = (int) $conn->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$customer_count = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$low_stock_count = (int) $conn->query('SELECT COUNT(*) FROM products WHERE quantity <= 5')->fetchColumn();

$status_rows = $conn->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status')->fetchAll();
$status_counts = ['pending'=>0,'processing'=>0,'completed'=>0,'cancelled'=>0];
foreach ($status_rows as $row) { $status_counts[$row['status']] = (int) $row['total']; }

$stmt = $conn->prepare("SELECT o.id,u.full_name,o.total_amount,o.status,o.created_at FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8");
$stmt->execute(); $recent_orders = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT oi.product_name, SUM(oi.quantity) units_sold, SUM(oi.quantity*oi.unit_price) revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status='completed' GROUP BY oi.product_id,oi.product_name ORDER BY units_sold DESC LIMIT 5");
$stmt->execute(); $top_products = $stmt->fetchAll();

$stmt = $conn->prepare('SELECT product_code,name,quantity FROM products ORDER BY quantity ASC, name ASC LIMIT 6');
$stmt->execute(); $inventory = $stmt->fetchAll();

$days=[]; $revenue_by_day=[];
for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$days[]=$d;$revenue_by_day[$d]=0;}
$stmt=$conn->prepare("SELECT DATE(created_at) sale_date, SUM(total_amount) revenue FROM orders WHERE status='completed' AND created_at >= CURDATE()-INTERVAL 6 DAY GROUP BY DATE(created_at)");
$stmt->execute(); foreach($stmt->fetchAll() as $r){$revenue_by_day[$r['sale_date']] = (float)$r['revenue'];}
$chart_max=max(1,max($revenue_by_day));

require __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-shell"><div class="container">
  <div class="dashboard-heading"><div><p class="tech-label">// ADMIN_COMMAND</p><h1 class="section-heading">OPERATIONS CENTER</h1><p class="dashboard-subtext">Live store activity from orders, customers and inventory.</p></div><span class="dashboard-date"><?php echo date('d M Y'); ?> // SYSTEM_ONLINE</span></div>
  <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

  <div class="metric-grid">
    <article class="metric-card"><span>TOTAL REVENUE</span><strong><?php echo format_price($revenue); ?></strong><small>Completed orders</small></article>
    <article class="metric-card"><span>TOTAL ORDERS</span><strong><?php echo $order_count; ?></strong><small><?php echo $status_counts['pending']; ?> pending</small></article>
    <article class="metric-card"><span>CUSTOMERS</span><strong><?php echo $customer_count; ?></strong><small>Registered accounts</small></article>
    <article class="metric-card<?php echo $low_stock_count ? ' metric-alert' : ''; ?>"><span>LOW STOCK</span><strong><?php echo $low_stock_count; ?></strong><small>5 units or fewer</small></article>
  </div>

  <div class="dashboard-grid dashboard-grid-main">
    <section class="dashboard-panel"><div class="panel-heading"><div><span>REVENUE_ACTIVITY // 01</span><h2>LAST 7 DAYS</h2></div></div>
      <div class="revenue-chart">
        <?php foreach($revenue_by_day as $date=>$amount): ?><div class="chart-column"><div class="chart-value"><?php echo $amount>0 ? format_price($amount) : '—'; ?></div><div class="chart-track"><span style="height:<?php echo max(3,($amount/$chart_max)*100); ?>%"></span></div><small><?php echo strtoupper(date('D',strtotime($date))); ?></small></div><?php endforeach; ?>
      </div>
    </section>
    <section class="dashboard-panel"><div class="panel-heading"><div><span>ORDER_STATUS // 02</span><h2>FULFILLMENT</h2></div></div>
      <div class="status-stack"><?php foreach($status_counts as $status=>$count): ?><div><span class="status-badge status-<?php echo $status; ?>"><?php echo strtoupper($status); ?></span><strong><?php echo $count; ?></strong></div><?php endforeach; ?></div>
      <a class="text-link compact-link" href="<?php echo ADMIN_URL; ?>orders.php">MANAGE ORDERS →</a>
    </section>
  </div>

  <section class="dashboard-panel"><div class="panel-heading"><div><span>TRANSACTION_LOG // 03</span><h2>RECENT TRANSACTIONS</h2></div><a class="text-link compact-link" href="<?php echo ADMIN_URL; ?>orders.php">VIEW ALL →</a></div>
    <div class="dashboard-table-wrap"><table class="dashboard-table"><thead><tr><th>ORDER</th><th>CUSTOMER</th><th>AMOUNT</th><th>STATUS</th><th>DATE</th></tr></thead><tbody>
    <?php if(!$recent_orders): ?><tr><td colspan="5">No orders yet.</td></tr><?php else: foreach($recent_orders as $o): ?><tr><td>#<?php echo str_pad($o['id'],4,'0',STR_PAD_LEFT); ?></td><td><?php echo safe_output($o['full_name']); ?></td><td><?php echo format_price($o['total_amount']); ?></td><td><span class="status-badge status-<?php echo safe_output($o['status']); ?>"><?php echo strtoupper(safe_output($o['status'])); ?></span></td><td><?php echo date('M d, Y',strtotime($o['created_at'])); ?></td></tr><?php endforeach; endif; ?>
    </tbody></table></div>
  </section>

  <div class="dashboard-grid">
    <section class="dashboard-panel"><div class="panel-heading"><div><span>TOP_MOVERS // 04</span><h2>BEST SELLERS</h2></div></div><div class="rank-list">
      <?php if(!$top_products): ?><p class="dashboard-empty">No completed sales yet.</p><?php else: foreach($top_products as $i=>$item): ?><div class="rank-row"><span><?php echo str_pad($i+1,2,'0',STR_PAD_LEFT); ?></span><div><strong><?php echo safe_output($item['product_name']); ?></strong><small><?php echo (int)$item['units_sold']; ?> units sold</small></div><b><?php echo format_price($item['revenue']); ?></b></div><?php endforeach; endif; ?>
    </div></section>
    <section class="dashboard-panel"><div class="panel-heading"><div><span>INVENTORY_STATUS // 05</span><h2>STOCK WATCH</h2></div><a class="text-link compact-link" href="<?php echo ADMIN_URL; ?>inventory.php">OPEN →</a></div><div class="inventory-list">
      <?php foreach($inventory as $item): $q=(int)$item['quantity']; $state=$q===0?'OUT OF STOCK':($q<=5?'LOW STOCK':'NOMINAL'); ?><div><span><small><?php echo safe_output($item['product_code']); ?></small><?php echo safe_output($item['name']); ?></span><strong><?php echo $q; ?></strong><em class="inventory-state inventory-<?php echo $q===0?'zero':($q<=5?'low':'ok'); ?>"><?php echo $state; ?></em></div><?php endforeach; ?>
    </div></section>
  </div>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
