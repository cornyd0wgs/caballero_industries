<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../controllers/ContactController.php';

// Only allow POST requests here.
// Visiting this file directly redirects back to the contact page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

if (!verify_csrf()) {
    $_SESSION['contact_errors'] = array('Your form session expired. Please try again.');
    header('Location: contact.php');
    exit;
}

// Collect and trim the submitted fields.
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

$contactController = new ContactController();
$result = $contactController->submit($name, $email, $message);

if (!$result['success']) {
    $_SESSION['contact_errors'] = $result['errors'];
    $_SESSION['contact_old'] = $result['old'];
} else {
    $_SESSION['contact_success'] = $result['message'];
}

header('Location: contact.php');
exit;
