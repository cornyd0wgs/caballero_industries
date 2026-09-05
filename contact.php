<?php


require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/auth/validation.php';
require_once 'helpers.php';
require_once 'stuff.php';

$current_page = 'contact';

$contact_errors  = $_SESSION['contact_errors'] ?? array();
$contact_old     = $_SESSION['contact_old'] ?? array('name' => '', 'email' => '', 'message' => '');
$contact_success = $_SESSION['contact_success'] ?? false;
unset($_SESSION['contact_errors'], $_SESSION['contact_old'], $_SESSION['contact_success']);

require 'includes/header.php';
?>

    <section class="final-cta" id="contact">
      <div class="container final-cta-content">
        <p class="section-index">TERMINAL_COMMAND // 07</p>
        <p class="tech-label">// INITIALIZE_DEPLOYMENT</p>
        <h2 class="final-cta-heading">GET IN TOUCH.</h2>
        <p class="body-text" style="text-align:center;">Questions about an order, sizing, or anything else — send us a message and we'll get back to you.</p>

        <div class="contact-form-wrap">

          <?php if ($contact_success) : ?>
            <p class="form-message form-message-success">Message sent. We&rsquo;ll be in touch shortly.</p>
          <?php endif; ?>

          <?php if (!empty($contact_errors)) : ?>
            <ul class="form-message form-message-error">
              <?php foreach ($contact_errors as $error) : ?>
                <li><?php echo safe_output($error); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <form class="contact-form" method="post">
            <div class="form-row">
              <label for="name">NAME</label>
              <input type="text" id="name" name="name" required
                     value="<?php echo safe_output($contact_old['name']); ?>">
            </div>
            <div class="form-row">
              <label for="email">EMAIL</label>
              <input type="email" id="email" name="email" required
                     value="<?php echo safe_output($contact_old['email']); ?>">
            </div>
            <div class="form-row">
              <label for="message">MESSAGE</label>
              <textarea id="message" name="message" rows="4" required><?php echo safe_output($contact_old['message']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">SEND MESSAGE <span class="arrow">→</span></button>
          </form>
        </div>
      </div>
    </section>

<?php require 'includes/footer.php'; ?>
