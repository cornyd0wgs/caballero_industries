<?php

session_start();

// Only allow POST requests here.
// Visiting this file directly redirects back to the contact page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

// Collect and trim the submitted fields.
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

$errors = array();

// Validate name.
if ($name === '') {
    $errors[] = 'Please enter your name.';
}

// Validate email.
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Validate message.
if ($message === '') {
    $errors[] = 'Please enter a message.';
}

// If validation fails, return the user to the contact form.
if (!empty($errors)) {
    $_SESSION['contact_errors'] = $errors;

    $_SESSION['contact_old'] = array(
        'name' => $name,
        'email' => $email,
        'message' => $message
    );

    header('Location: contact.php');
    exit;
}

// All fields are valid.
// Save the contact message to the local log file.
$logLine = sprintf(
    "[%s] %s <%s>: %s%s",
    date('Y-m-d H:i:s'),
    htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
    PHP_EOL
);

file_put_contents(
    __DIR__ . '/contact-log.txt',
    $logLine,
    FILE_APPEND | LOCK_EX
);

// Clear previous form data and show the success message.
unset(
    $_SESSION['contact_errors'],
    $_SESSION['contact_old']
);

$_SESSION['contact_success'] = true;

header('Location: contact.php');
exit;
