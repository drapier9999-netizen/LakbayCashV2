<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Logout
$_SESSION = array();
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
