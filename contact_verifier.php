<?php
session_start();

// Only allow POST requests here — visiting this file directly does nothing
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

// Collect + trim the submitted fields
$name    = isset($_POST['name'])    ? trim($_POST['name'])    : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])   : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

$errors = array();

if ($name === '') {
    $errors[] = 'Please enter your name.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if ($message === '') {
    $errors[] = 'Please enter a message.';
}

if (!empty($errors)) {
    // Send the errors + previously typed values back to the form
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_old']    = array(
        'name'    => $name,
        'email'   => $email,
        'message' => $message,
    );
    header('Location: contact.php');
    exit;
}

// All fields are valid — log the message to a local text file.
// htmlspecialchars() here just keeps the log file safe to view in a browser later.
$logLine = sprintf(
    "[%s] %s <%s>: %s%s",
    date('Y-m-d H:i:s'),
    htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
    PHP_EOL
);

file_put_contents(__DIR__ . '/contact-log.txt', $logLine, FILE_APPEND | LOCK_EX);

// Clear any leftover error/old-input data and set a success flag
unset($_SESSION['contact_errors'], $_SESSION['contact_old']);
$_SESSION['contact_success'] = true;

header('Location: contact.php');
exit;
