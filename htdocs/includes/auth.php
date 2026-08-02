<?php
// ============================================================
// Session & Auth Bootstrap
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function is_logged_in() {
  return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
  return isset($_SESSION['admin_id']);
}

function require_admin() {
  if (!is_admin_logged_in()) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
  }
}

function current_user() {
  if (!is_logged_in()) return null;
  return get_user_row($_SESSION['user_id']);
}
