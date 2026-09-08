<?php

require_once __DIR__ . '/../auth/auth.php';

$_SESSION = array();
session_destroy();

header('location: ' . '../index.php ');
?>