<?php
// ============================================================
// LakbayCash — Configuration
// ============================================================

define('APP_NAME',    'LakbayCash');
define('APP_TAGLINE', 'Kasama Mo Sa Bawat Hakbang');
define('APP_VERSION', '1.0.0');

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME')     ?: 'lakbaycash');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASS',     getenv('DB_PASS')     ?: '');
define('DB_CHARSET',  'utf8mb4');

// ── Paths ─────────────────────────────────────────────────────
// Calculate BASE_URL from the document root so it's correct regardless
// of which subdirectory the current script lives in.
$docRoot  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/'));
$appRoot  = str_replace('\\', '/', __DIR__);
$baseRel  = trim(str_replace($docRoot, '', $appRoot), '/');
$scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $scheme . '://' . $host . ($baseRel !== '' ? '/' . $baseRel : ''));

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// ── Loan Settings ─────────────────────────────────────────────
define('LOAN_INTEREST_RATE', 0.04);    // 4% flat
define('LOAN_MIN_LIMIT',     500);
define('LOAN_MAX_LIMIT',     6000);
define('LOAN_MAX_MONTHS',    9);

// ── Admin ─────────────────────────────────────────────────────
define('ADMIN_PATH', '/admin');

// ── Session lifetime (seconds) ────────────────────────────────
define('SESSION_LIFETIME', 7200);

// ── Error reporting (set to 0 in production) ─────────────────
error_reporting(E_ALL);
ini_set('display_errors', '1');
