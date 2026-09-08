<?php

require_once __DIR__ . '/../auth/auth.php';

$_SESSION = array();
session_destroy();

header('Location: ' . BASE_URL);
exit;
