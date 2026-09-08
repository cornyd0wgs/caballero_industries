<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/validation.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/stuff.php';

require_login();

if (is_admin()) {
    header('Location: ' . ADMIN_URL . 'profile.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([current_user_id()]);
$user = $stmt->fetch();

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!validate_name($name)) {
        $errors[] = 'Please enter a valid name.';
    }

    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email.';
    }

    if (!validate_age($age)) {
        $errors[] = 'Age must be between 1 and 100.';
    }

    $emailCheck = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $emailCheck->execute([$email, current_user_id()]);

    if ($emailCheck->fetch()) {
        $errors[] = 'That email is already in use.';
    }

    $changePassword =
        $currentPassword !== '' ||
        $newPassword !== '' ||
        $confirmPassword !== '';

    if ($changePassword) {
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (!validate_password($newPassword)) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (empty($errors)) {
        if ($changePassword) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $update = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, age = ?, password_hash = ?
                 WHERE id = ?'
            );
            $update->execute([
                $name,
                $email,
                (int) $age,
                $passwordHash,
                current_user_id()
            ]);
        } else {
            $update = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, age = ?
                 WHERE id = ?'
            );
            $update->execute([
                $name,
                $email,
                (int) $age,
                current_user_id()
            ]);
        }

        $_SESSION['user_name'] = $name;
        $success = 'Profile updated.';

        $stmt->execute([current_user_id()]);
        $user = $stmt->fetch();
    }
}

$current_page = 'account';
$account_tab = 'profile';
require __DIR__ . '/../includes/header.php';
?>

<section class="account-shell">
    <div class="container">
        <section class="account-page-heading">
            <div>
                <p class="account-kicker">IDENTITY & SECURITY // CUSTOMER PROFILE</p>
                <h1>PROFILE & SECURITY</h1>
                <p>Keep your account details current and change your password when needed.</p>
            </div>
            <a href="dashboard.php" class="account-outline-button">BACK TO OVERVIEW</a>
        </section>

        <?php require __DIR__ . '/../includes/account-nav.php'; ?>

        <div class="account-profile-layout">
            <aside class="account-card account-profile-sidecard">
                <div class="account-profile-avatar account-profile-avatar-large">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <span class="account-member-since">CUSTOMER PROFILE</span>
                <h2><?php echo safe_output($user['full_name']); ?></h2>
                <p><?php echo safe_output($user['email']); ?></p>
                <div class="account-security-note">
                    <strong>ACCOUNT STATUS</strong>
                    <span>ACTIVE / SECURED</span>
                </div>
            </aside>

            <section class="account-card account-profile-form-card">
                <div class="account-card-heading">
                    <div>
                        <span>PERSONAL DETAILS</span>
                        <h2>EDIT PROFILE</h2>
                    </div>
                </div>
                <?php require __DIR__ . '/../includes/profile-form.php'; ?>
            </section>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
