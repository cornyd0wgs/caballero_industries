<?php if (!empty($success)): ?>
    <p class="form-message form-message-success">
        <?php echo safe_output($success); ?>
    </p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <ul class="form-message form-message-error">
        <?php foreach ($errors as $error): ?>
            <li><?php echo safe_output($error); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" class="admin-form profile-form">
    <?php echo csrf_field(); ?>

    <div class="form-row">
        <label for="full_name">FULL NAME</label>
        <input
            id="full_name"
            type="text"
            name="full_name"
            value="<?php echo safe_output($user['full_name']); ?>"
            required
        >
    </div>

    <div class="form-row">
        <label for="email">EMAIL</label>
        <input
            id="email"
            type="email"
            name="email"
            value="<?php echo safe_output($user['email']); ?>"
            required
        >
    </div>

    <div class="form-row">
        <label for="age">AGE</label>
        <input
            id="age"
            type="number"
            name="age"
            min="1"
            max="100"
            value="<?php echo (int) $user['age']; ?>"
            required
        >
    </div>

    <div class="profile-password-block">
        <p class="tech-label">// OPTIONAL_PASSWORD_CHANGE</p>

        <div class="form-row">
            <label for="current_password">CURRENT PASSWORD</label>
            <input
                id="current_password"
                type="password"
                name="current_password"
                autocomplete="current-password"
            >
        </div>

        <div class="form-row-split">
            <div class="form-row">
                <label for="new_password">NEW PASSWORD</label>
                <input
                    id="new_password"
                    type="password"
                    name="new_password"
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <div class="form-row">
                <label for="confirm_password">CONFIRM NEW PASSWORD</label>
                <input
                    id="confirm_password"
                    type="password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        SAVE PROFILE <span class="arrow">→</span>
    </button>
</form>
