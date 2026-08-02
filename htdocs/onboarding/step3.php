<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid = $_SESSION['user_id'];
$prog = get_onboarding_progress($uid);

// Redirect to first incomplete step (unless it's this one)
$firstIncomplete = 0;
for ($i = 1; $i <= 5; $i++) {
  if (!$prog['steps'][$i]) { $firstIncomplete = $i; break; }
}
if ($firstIncomplete && $firstIncomplete !== 3) { redirect('onboarding/step' . $firstIncomplete . '.php'); }

$errors = [];
$files = ['id_front' => '', 'id_back' => '', 'face_scan' => ''];

$stmt = db()->prepare('SELECT * FROM identity_verification WHERE user_id = ?');
$stmt->execute([$uid]);
if ($row = $stmt->fetch()) { $files = array_intersect_key($row, $files); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uploadFields = ['id_front', 'id_back', 'face_scan'];
  foreach ($uploadFields as $f) {
    $path = save_upload($f);
    if ($path) $files[$f] = $path;
    if (!$files[$f]) $errors[$f] = 'This document is required.';
  }

  if (!$errors) {
    $stmt = db()->prepare('INSERT INTO identity_verification (user_id, id_front, id_back, face_scan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id_front=VALUES(id_front), id_back=VALUES(id_back), face_scan=VALUES(face_scan)');
    $stmt->execute([$uid, $files['id_front'], $files['id_back'], $files['face_scan']]);
    db()->prepare('UPDATE users SET onboarding_step = GREATEST(onboarding_step, 3) WHERE id = ?')->execute([$uid]);
    redirect('onboarding/step4.php');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Onboarding · Identity Verification'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/onboarding/step2.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Step 3 of 5</span>
    <div style="width:40px"></div>
  </div>

  <div class="page">
    <div class="progress-wrap">
      <div class="progress-head"><span>Onboarding Progress</span><span><?= $prog['pct'] ?>%</span></div>
      <div class="progress-track"><div class="progress-fill" style="width:<?= $prog['pct'] ?>%"></div></div>
    </div>
    <div class="step-dots">
      <div class="step-dot done"></div>
      <div class="step-dot done"></div>
      <div class="step-dot <?= $prog['steps'][3] ? 'done' : 'active' ?>"></div>
      <div class="step-dot <?= $prog['steps'][4] ? 'done' : '' ?>"></div>
      <div class="step-dot <?= $prog['steps'][5] ? 'done' : '' ?>"></div>
    </div>

    <div class="page-head">
      <h1>Identity Verification</h1>
      <p>Upload a clear photo of your valid ID and a selfie.</p>
    </div>

    <form method="POST" enctype="multipart/form-data" novalidate>
      <div class="field">
        <label class="field-label">Front of ID <span class="pct">+1% Profile Completion</span></label>
        <div class="upload-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/></svg>
          <div class="upload-label"><?= $files['id_front'] ? 'Uploaded ✓' : 'Tap to upload' ?></div>
          <div class="upload-sub">JPG, PNG, HEIC</div>
          <input type="file" name="id_front" accept=".jpg,.jpeg,.png,.heic" hidden>
          <div class="upload-preview"><?= $files['id_front'] ? '<img src="'.upload_url($files['id_front']).'" alt="ID Front">' : '' ?></div>
        </div>
        <?php if (!empty($errors['id_front'])): ?><div class="field-error"><?= e($errors['id_front']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label class="field-label">Back of ID <span class="pct">+1% Profile Completion</span></label>
        <div class="upload-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/></svg>
          <div class="upload-label"><?= $files['id_back'] ? 'Uploaded ✓' : 'Tap to upload' ?></div>
          <div class="upload-sub">JPG, PNG, HEIC</div>
          <input type="file" name="id_back" accept=".jpg,.jpeg,.png,.heic" hidden>
          <div class="upload-preview"><?= $files['id_back'] ? '<img src="'.upload_url($files['id_back']).'" alt="ID Back">' : '' ?></div>
        </div>
        <?php if (!empty($errors['id_back'])): ?><div class="field-error"><?= e($errors['id_back']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label class="field-label">Face Scan (Selfie) <span class="pct">+1% Profile Completion</span></label>
        <div class="upload-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
          <div class="upload-label"><?= $files['face_scan'] ? 'Uploaded ✓' : 'Tap to upload' ?></div>
          <div class="upload-sub">Look directly at the camera</div>
          <input type="file" name="face_scan" accept=".jpg,.jpeg,.png,.heic" hidden>
          <div class="upload-preview"><?= $files['face_scan'] ? '<img src="'.upload_url($files['face_scan']).'" alt="Face Scan">' : '' ?></div>
        </div>
        <?php if (!empty($errors['face_scan'])): ?><div class="field-error"><?= e($errors['face_scan']) ?></div><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary mt-4">Save & Continue</button>
    </form>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
