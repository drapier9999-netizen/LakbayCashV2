<?php
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$form = ['mobile' => '', 'name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mobile = trim($_POST['mobile'] ?? '');
  $name   = trim($_POST['name'] ?? '');
  $email  = trim($_POST['email'] ?? '');

  $form = compact('mobile', 'name', 'email');
  if ($mobile === '') $errors['mobile'] = 'Mobile number is required.';
  elseif (!preg_match('/^(\+?63|0)?9\d{9}$/', preg_replace('/[\s\-]/', '', $mobile))) $errors['mobile'] = 'Enter a valid Philippine mobile number.';
  if ($name === '') $errors['name'] = 'Full name is required.';
  if ($email === '') $errors['email'] = 'Email address is required.';
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';

  if (!$errors) {
    // Check if user exists
    $stmt = db()->prepare('SELECT id FROM users WHERE mobile = ? OR email = ?');
    $stmt->execute([$mobile, $email]);
    $existing = $stmt->fetch();

    $otp = generate_otp();

    if ($existing) {
      // Update existing user's OTP and details
      $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, otp_code = ? WHERE id = ?');
      $stmt->execute([$name, $email, $otp, $existing['id']]);
      $_SESSION['pending_user_id'] = $existing['id'];
    } else {
      $stmt = db()->prepare('INSERT INTO users (mobile, name, email, otp_code) VALUES (?, ?, ?, ?)');
      $stmt->execute([$mobile, $name, $email, $otp]);
      $_SESSION['pending_user_id'] = (int)db()->lastInsertId();
    }

    $_SESSION['pending_otp'] = $otp;
    $_SESSION['pending_mobile'] = $mobile;
    redirect('verify.php');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#059669">
  <title>Sign Up · <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <div class="logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4"/><path d="M3 17l9 4 9-4"/></svg>
      </div>
      <h2>Welcome to <?= APP_NAME ?></h2>
      <p>Enter your details to get started.</p>
    </div>

    <form method="POST" novalidate>
      <div class="field">
        <label class="field-label">Mobile Number</label>
        <input type="tel" name="mobile" class="field-input" placeholder="09XX XXX XXXX" value="<?= e($form['mobile']) ?>" required>
        <?php if (!empty($errors['mobile'])): ?><div class="field-error"><?= e($errors['mobile']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label class="field-label">Full Name</label>
        <input type="text" name="name" class="field-input" placeholder="Juan Dela Cruz" value="<?= e($form['name']) ?>" required>
        <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label class="field-label">Email Address</label>
        <input type="email" name="email" class="field-input" placeholder="juan@example.com" value="<?= e($form['email']) ?>" required>
        <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary mt-2">Continue</button>
    </form>

    <div class="text-center mt-6">
      <a href="<?= BASE_URL ?>/index.php" class="text-sm text-muted">← Back to home</a>
    </div>
  </div>
</div>
</body>
</html>
