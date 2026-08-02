<?php
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['pending_user_id']) || !isset($_SESSION['pending_otp'])) {
  redirect('login.php');
}

$otp_display = $_SESSION['pending_otp'];
$pending_uid = $_SESSION['pending_user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submitted_otp = trim($_POST['otp'] ?? '');
  if ($submitted_otp === '' || $submitted_otp !== ($_SESSION['pending_otp'] ?? '')) {
    $errors['otp'] = 'Invalid OTP code. Please try again.';
  } else {
  $_SESSION['user_id'] = $pending_uid;
  unset($_SESSION['pending_user_id'], $_SESSION['pending_otp'], $_SESSION['pending_mobile']);

  // Clear OTP code in DB
  $stmt = db()->prepare('UPDATE users SET otp_code = NULL WHERE id = ?');
  $stmt->execute([$pending_uid]);

  // Check onboarding progress
  $prog = get_onboarding_progress($pending_uid);
  if ($prog['pct'] < 100) {
    for ($i = 1; $i <= 5; $i++) {
      if (!$prog['steps'][$i]) { redirect('onboarding/step' . $i . '.php'); }
    }
  }
  redirect('dashboard.php');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#059669">
  <title>Verify OTP · <?= APP_NAME ?></title>
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
      <div class="logo" style="background:linear-gradient(135deg,var(--accent-400),var(--accent-600)); box-shadow:0 8px 28px rgba(245,158,11,.3);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
      </div>
      <h2>Verify Your Number</h2>
      <p>We sent a 6-digit code to your mobile number. Enter it below to continue.</p>
    </div>

    <!-- OTP Display -->
    <div style="background:var(--accent-50); border:1px solid var(--accent-200); border-radius:var(--radius-md); padding:var(--space-4); margin-bottom:var(--space-4);">
      <div class="text-sm" style="font-weight:600; color:var(--accent-700); margin-bottom:var(--space-2);">Your OTP Code</div>
      <div class="otp-display" onclick="copyText('<?= e($otp_display) ?>', this)"><?= e($otp_display) ?></div>
      <div class="text-sm text-muted text-center">Tap the code to copy it.</div>
    </div>

    <?php if (!empty($errors['otp'])): ?>
    <div class="field-error" style="text-align:center; margin-bottom:var(--space-4);"><?= e($errors['otp']) ?></div>
    <?php endif; ?>
    <form method="POST" id="otpForm">
      <div class="otp-inputs" id="otpInputs">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autofocus>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
      </div>
      <input type="hidden" name="otp" id="otpHidden">
      <button type="submit" class="btn btn-primary">Verify & Continue</button>
    </form>

    <div class="text-center mt-6">
      <a href="<?= BASE_URL ?>/login.php" class="text-sm text-muted">← Back</a>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('otpForm').addEventListener('submit', function(e){
  var inputs = document.querySelectorAll('#otpInputs input');
  var code = '';
  inputs.forEach(function(i){ code += i.value || '0'; });
  document.getElementById('otpHidden').value = code;
});
</script>
</body>
</html>
