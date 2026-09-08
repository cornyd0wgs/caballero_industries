<?php
require_once __DIR__ . '/../auth/auth.php'; require_once __DIR__ . '/../auth/validation.php'; require_once __DIR__ . '/../database/db.php'; require_once __DIR__ . '/../helpers/helpers.php'; require_once __DIR__ . '/../helpers/stuff.php';
require_login();
require_admin();
$stmt=$conn->prepare('SELECT * FROM users WHERE id=?');$stmt->execute([current_user_id()]);$user=$stmt->fetch();$errors=[];$success=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf())$errors[]='Your form session expired.';
 $name=trim($_POST['full_name']??'');$email=trim($_POST['email']??'');$age=trim($_POST['age']??'');$current=$_POST['current_password']??'';$new=$_POST['new_password']??'';$confirm=$_POST['confirm_password']??'';
 if(!validate_name($name))$errors[]='Please enter a valid name.'; if(!validate_email($email))$errors[]='Please enter a valid email.'; if(!validate_age($age))$errors[]='Age must be between 1 and 100.';
 $q=$conn->prepare('SELECT id FROM users WHERE email=? AND id<>?');$q->execute([$email,current_user_id()]);if($q->fetch())$errors[]='That email is already in use.';
 $changePassword=$current!==''||$new!==''||$confirm!==''; if($changePassword){if(!password_verify($current,$user['password_hash']))$errors[]='Current password is incorrect.';if(!validate_password($new))$errors[]='New password must be at least 8 characters.';if($new!==$confirm)$errors[]='New passwords do not match.';}
 if(!$errors){if($changePassword){$hash=password_hash($new,PASSWORD_DEFAULT);$q=$conn->prepare('UPDATE users SET full_name=?,email=?,age=?,password_hash=? WHERE id=?');$q->execute([$name,$email,(int)$age,$hash,current_user_id()]);}else{$q=$conn->prepare('UPDATE users SET full_name=?,email=?,age=? WHERE id=?');$q->execute([$name,$email,(int)$age,current_user_id()]);}$_SESSION['user_name']=$name;$success='Profile updated.';$stmt->execute([current_user_id()]);$user=$stmt->fetch();}
}
?><?php $current_page='admin';$admin_tab='profile';require __DIR__.'/../includes/header.php';?><section class="dashboard-shell"><div class="container"><div class="dashboard-heading"><div><p class="tech-label">// ADMIN_IDENTITY</p><h1 class="section-heading">PROFILE</h1></div></div><?php require __DIR__.'/../includes/admin-nav.php';?><?php require __DIR__.'/../includes/profile-form.php';?></div></section><?php require __DIR__.'/../includes/footer.php';?>