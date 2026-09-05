<?php
/**
 * register.php
 * -------------------------------------------------------------
 * Sign-up page. Handles its own form submission (no separate
 * handler file) — if the request is a POST, it validates and
 * either shows errors or creates the account and logs them in.
 * -------------------------------------------------------------
 */

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/auth/auth.php';
require_once  __DIR__ . '/auth/validation.php';
require_once 'helpers.php';
require_once 'stuff.php';
$current_page = 'register';

// Already logged in? No need to be here.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = array();
$old = array('full_name' => '', 'email' => '', 'age' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name        = trim($_POST['full_name'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $age               = trim($_POST['age'] ?? '');
    $password          = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Keep whatever was typed so the form doesn't clear on error
    // (passwords are intentionally NOT kept, for safety)
    $old = array('full_name' => $full_name, 'email' => $email, 'age' => $age);

    // ---- Run every field through auth/validation.php ----
    if (!validate_name($full_name)) {
        $errors[] = 'Please enter your real name using letters only (no numbers or symbols).';
    }

    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!validate_age($age)) {
        $errors[] = 'Age must be a whole number between 1 and 100.';
    }

    if (!validate_password($password)) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Only hit the database if the basic checks above already passed —
    // no point checking "is this email taken" if the email isn't valid.
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ?');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'An account with that email already exists. Try logging in instead.';
        }
        mysqli_stmt_close($stmt);
    }

    // ---- All checks passed: create the account ----
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $age_int = (int) $age;

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO users (full_name, email, password_hash, age, role) VALUES (?, ?, ?, ?, "customer")'
        );
        mysqli_stmt_bind_param($stmt, 'sssi', $full_name, $email, $password_hash, $age_int);
        mysqli_stmt_execute($stmt);
        $new_user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Log the new user in immediately, same as most sign-up flows
        $_SESSION['user_id']   = $new_user_id;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_role'] = 'customer';

        header('Location: index.php');
        exit;
    }
}

require 'includes/header.php';
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

          <button type="submit" class="btn btn-primary">CREATE ACCOUNT <span class="arrow">→</span></button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
      </div>
    </section>

<?php require 'includes/footer.php'; ?>
