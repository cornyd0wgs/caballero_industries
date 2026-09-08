<?php
require_once __DIR__.'/../auth/auth.php'; require_once __DIR__.'/../database/db.php'; require_once __DIR__.'/../helpers/helpers.php'; require_once __DIR__.'/../helpers/stuff.php';
$current_page='admin'; $admin_tab='orders'; require_admin(); $error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf()){$error='Your form session expired.';} else {
  $id=(int)($_POST['order_id']??0); $new=$_POST['status']??''; $allowed=['pending','processing','completed','cancelled'];
  $stmt=$conn->prepare('SELECT status FROM orders WHERE id=?');$stmt->execute([$id]);$order=$stmt->fetch();
  $trans=['pending'=>['processing','completed','cancelled'],'processing'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]];
  if(!$order||!in_array($new,$allowed,true)||!in_array($new,$trans[$order['status']]??[],true)){$error='That status change is not allowed.';} else {
   $conn->beginTransaction(); try{
    if($new==='cancelled'){$stmt=$conn->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=?');$stmt->execute([$id]);foreach($stmt->fetchAll() as $it){$u=$conn->prepare('UPDATE products SET quantity=quantity+? WHERE id=?');$u->execute([(int)$it['quantity'],(int)$it['product_id']]);}}
    $stmt=$conn->prepare('UPDATE orders SET status=? WHERE id=?');$stmt->execute([$new,$id]);$conn->commit();$_SESSION['flash_success']='Order #'.$id.' updated.';header('Location: orders.php');exit;
   }catch(Throwable $e){if($conn->inTransaction())$conn->rollBack();$error='Could not update the order.';}
  }
 }
}
$filter=$_GET['status']??'all'; $valid=['all','pending','processing','completed','cancelled']; if(!in_array($filter,$valid,true))$filter='all';
$sql="SELECT o.*,u.full_name,u.email FROM orders o JOIN users u ON u.id=o.user_id"; $params=[]; if($filter!=='all'){$sql.=' WHERE o.status=?';$params[]=$filter;} $sql.=' ORDER BY o.created_at DESC';$stmt=$conn->prepare($sql);$stmt->execute($params);$orders=$stmt->fetchAll();$flash=$_SESSION['flash_success']??null;unset($_SESSION['flash_success']); require __DIR__.'/../includes/header.php'; ?>
<section class="dashboard-shell"><div class="container"><div class="dashboard-heading"><div><p class="tech-label">// ORDER_CONTROL</p><h1 class="section-heading">ORDERS</h1><p class="dashboard-subtext">Checkout creates a pending order. Admin moves it through fulfillment.</p></div></div><?php require __DIR__.'/../includes/admin-nav.php'; ?>
<?php if($flash): ?><p class="form-message form-message-success"><?php echo safe_output($flash); ?></p><?php endif; ?><?php if($error): ?><p class="form-message form-message-error-single"><?php echo safe_output($error); ?></p><?php endif; ?>
<div class="filter-tabs"><?php foreach($valid as $f): ?><a class="filter-tab<?php echo $filter===$f?' active':''; ?>" href="?status=<?php echo $f; ?>"><?php echo strtoupper($f); ?></a><?php endforeach; ?></div>
<div class="dashboard-table-wrap"><table class="dashboard-table order-table"><thead><tr><th>ORDER</th><th>CUSTOMER</th><th>AMOUNT</th><th>STATUS</th><th>DATE</th><th>UPDATE</th></tr></thead><tbody><?php if(!$orders): ?><tr><td colspan="6">No orders found.</td></tr><?php else: foreach($orders as $o): ?><tr><td>#<?php echo str_pad($o['id'],4,'0',STR_PAD_LEFT); ?></td><td><?php echo safe_output($o['full_name']); ?><small class="table-subtext"><?php echo safe_output($o['email']); ?></small></td><td><?php echo format_price($o['total_amount']); ?></td><td><span class="status-badge status-<?php echo safe_output($o['status']); ?>"><?php echo strtoupper(safe_output($o['status'])); ?></span></td><td><?php echo date('M d, Y H:i',strtotime($o['created_at'])); ?></td><td><?php $choices=['pending'=>['processing','completed','cancelled'],'processing'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]][$o['status']]??[]; if($choices): ?><form method="post" class="status-form"><?php echo csrf_field(); ?><input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>"><select name="status"><?php foreach($choices as $c): ?><option value="<?php echo $c; ?>"><?php echo strtoupper($c); ?></option><?php endforeach; ?></select><button class="admin-action-link admin-action-edit">APPLY</button></form><?php else: ?><span class="muted-text">LOCKED</span><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
</div></section><?php require __DIR__.'/../includes/footer.php'; ?>
