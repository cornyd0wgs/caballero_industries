<?php

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$current_page = 'register';

// Already logged in? No need to be here.
if (is_logged_in()) {
    header('Location:' . '../index.php');
    exit;
}

$errors = array();
$old = array(
    'full_name' => '',
    'email' => '',
    'age' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    $full_name       = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $age             = trim($_POST['age'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Keep whatever was typed so the form doesn't clear on error.
    // Passwords are intentionally NOT kept.
    $old = array(
        'full_name' => $full_name,
        'email' => $email,
        'age' => $age
    );

    if (empty($errors)) {
        $authController = new AuthController($conn);
        $result = $authController->register($full_name, $email, $age, $password, $confirm_password);

        if ($result['success']) {
            header('Location: ../index.php');
            exit;
        } else {
            $errors = array_merge($errors, $result['errors']);
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">
  <div class="container auth-container">
    <p class="tech-label">// NEW_OPERATOR_REGISTRATION</p>
    <h1 class="section-heading">CREATE AN ACCOUNT</h1>

    <?php if (!empty($errors)) : ?>
      <ul class="form-message form-message-error">
        <?php foreach ($errors as $error) : ?>
          <li><?php echo safe_output($error); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form class="auth-form" method="post" action="register.php">
      <?php echo csrf_field(); ?>
      <div class="form-row">
        <label for="full_name">FULL NAME</label>
        <input type="text" id="full_name" name="full_name" required
               value="<?php echo safe_output($old['full_name']); ?>">
      </div>

      <div class="form-row">
        <label for="email">EMAIL</label>
        <input type="email" id="email" name="email" required
               value="<?php echo safe_output($old['email']); ?>">
      </div>

      <div class="form-row">
        <label for="age">AGE</label>
        <input type="number" id="age" name="age" min="1" max="100" required
               value="<?php echo safe_output($old['age']); ?>">
      </div>

      <div class="form-row">
        <label for="password">PASSWORD</label>
        <input type="password" id="password" name="password" minlength="8" required>
      </div>

      <div class="form-row">
        <label for="confirm_password">CONFIRM PASSWORD</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
      </div>

      <button type="submit" class="btn btn-primary">
        CREATE ACCOUNT <span class="arrow">→</span>
      </button>
    </form>

    <p class="auth-switch">
      Already have an account? <a href="login.php">Log in</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>