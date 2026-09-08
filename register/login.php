<?php

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/auth/validation.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/stuff.php';

$current_page = 'login';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Where to send the user after a successful login.
$redirect_to = $_GET['redirect'] ?? 'index.php';

// Basic safety check: only allow redirecting to a page on this same site.
if (strpos($redirect_to, '://') !== false) {
    $redirect_to = 'index.php';
}

$errors = array();
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $old_email = $email;

    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    // ---- Check login credentials using PDO ----
    if (empty($errors)) {

        $stmt = $conn->prepare(
            'SELECT id, full_name, password_hash, role
             FROM users
             WHERE email = ?'
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        // Deliberately vague error message — don't reveal whether
        // the email or password was wrong.
        if (!$user || !password_verify($password, $user['password_hash'])) {

            $errors[] = 'Incorrect email or password.';

        } else {

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: ' . $redirect_to);
            exit;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
  <div class="container auth-container">
    <p class="tech-label">// SECURE_LOGIN</p>
    <h1 class="section-heading">LOG IN</h1>

    <?php if (!empty($errors)) : ?>
      <ul class="form-message form-message-error">
        <?php foreach ($errors as $error) : ?>
          <li><?php echo safe_output($error); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form class="auth-form" method="post" action="login.php?redirect=<?php echo urlencode($redirect_to); ?>">

      <div class="form-row">
        <label for="email">EMAIL</label>
        <input
          type="email"
          id="email"
          name="email"
          required
          value="<?php echo safe_output($old_email); ?>"
        >
      </div>

      <div class="form-row">
        <label for="password">PASSWORD</label>
        <input
          type="password"
          id="password"
          name="password"
          required
        >
      </div>

      <button type="submit" class="btn btn-primary">
        LOG IN <span class="arrow">→</span>
      </button>

    </form>

    <p class="auth-switch">
      New here? <a href="register.php">Create an account</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>