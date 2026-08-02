<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) redirect('admin/index.php');

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    $errors['form'] = 'Enter username and password.';
  } else {
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
      $_SESSION['admin_id']   = $admin['id'];
      $_SESSION['admin_name'] = $admin['full_name'];
      redirect('admin/index.php');
    } else {
      $errors['form'] = 'Invalid credentials.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login · <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
</head>
<body class="admin-body">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <div class="logo" style="background:linear-gradient(135deg,var(--neutral-800),var(--neutral-900)); box-shadow:0 8px 28px rgba(15,23,42,.3);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <h2>Admin Panel</h2>
      <p>Restricted access. Authorized personnel only.</p>
    </div>

    <?php if (!empty($errors['form'])): ?>
    <div style="background:var(--error); color:#fff; padding:var(--space-3) var(--space-4); border-radius:var(--radius-md); margin-bottom:var(--space-4); font-size:.88rem;"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="field">
        <label class="field-label">Username</label>
        <input type="text" name="username" class="field-input" value="<?= e($username) ?>" required autofocus>
      </div>
      <div class="field">
        <label class="field-label">Password</label>
        <input type="password" name="password" class="field-input" required>
      </div>
      <button type="submit" class="btn btn-primary">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>
