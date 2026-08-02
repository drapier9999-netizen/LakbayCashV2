<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/admin-header.php';

$msg = '';
$error = '';

// Handle QR/Agreement image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'upload_qr') {
    $path = save_upload('qr_image', 'qr');
    if ($path) {
      set_setting('qr_agreement_image', $path);
      $msg = 'Loan agreement image updated successfully.';
    } else {
      $error = 'Failed to upload image. Please try again.';
    }
  }

  if ($action === 'update_settings') {
    $minLimit = (int)($_POST['loan_min_limit'] ?? LOAN_MIN_LIMIT);
    $maxLimit = (int)($_POST['loan_max_limit'] ?? LOAN_MAX_LIMIT);
    $rate = (float)($_POST['interest_rate'] ?? LOAN_INTEREST_RATE);
    $autoDelay = (int)($_POST['auto_approve_delay'] ?? 300);

    set_setting('loan_min_limit', $minLimit);
    set_setting('loan_max_limit', $maxLimit);
    set_setting('interest_rate', $rate);
    set_setting('auto_approve_delay', $autoDelay);
    $msg = 'Loan settings updated successfully.';
  }

  if ($action === 'change_password') {
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    if (strlen($newPass) < 8) {
      $error = 'Password must be at least 8 characters.';
    } elseif ($newPass !== $confirmPass) {
      $error = 'Passwords do not match.';
    } else {
      $hash = password_hash($newPass, PASSWORD_DEFAULT);
      $stmt = db()->prepare('UPDATE admins SET password = ? WHERE id = ?');
      $stmt->execute([$hash, $_SESSION['admin_id']]);
      $msg = 'Admin password updated successfully.';
    }
  }
}

$qrImage = get_setting('qr_agreement_image');
$minLimit = get_setting('loan_min_limit', LOAN_MIN_LIMIT);
$maxLimit = get_setting('loan_max_limit', LOAN_MAX_LIMIT);
$rate = get_setting('interest_rate', LOAN_INTEREST_RATE);
$autoDelay = get_setting('auto_approve_delay', 300);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php admin_head('Settings'); ?>
</head>
<body class="admin-body">
<div class="admin-layout">
  <?php admin_sidebar('settings'); ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <button class="admin-menu-btn" onclick="toggleAdminSidebar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1>Settings</h1>
    </div>

    <?php if ($msg): ?>
    <div style="background:var(--success); color:#fff; padding:var(--space-3) var(--space-4); border-radius:var(--radius-md); margin-bottom:var(--space-4); font-size:.88rem;"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:var(--error); color:#fff; padding:var(--space-3) var(--space-4); border-radius:var(--radius-md); margin-bottom:var(--space-4); font-size:.88rem;"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="admin-detail-grid">
      <!-- QR Agreement Image Manager -->
      <div class="detail-section">
        <h3>QR / Loan Agreement Image</h3>
        <p class="text-sm text-muted mb-4">Upload the image that appears as the QR code / loan agreement in the disbursal step. Users will see this image.</p>

        <?php if ($qrImage): ?>
        <div class="doc-thumb mb-4">
          <img src="<?= upload_url($qrImage) ?>" alt="Current Agreement Image" style="max-height:200px; width:auto; margin:0 auto;">
        </div>
        <?php else: ?>
        <div style="background:var(--neutral-100); border-radius:var(--radius-md); padding:var(--space-6); text-align:center; margin-bottom:var(--space-4); color:var(--neutral-400);">No image set.</div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_qr">
          <div class="upload-box" id="qrUploadBox">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <div class="upload-label">Tap to upload new image</div>
            <div class="upload-sub">JPG, PNG, WebP</div>
            <input type="file" name="qr_image" accept=".jpg,.jpeg,.png,.webp" hidden>
            <div class="upload-preview"></div>
          </div>
          <button type="submit" class="btn btn-primary mt-4">Upload Agreement Image</button>
        </form>
      </div>

      <!-- Loan Settings -->
      <div class="detail-section">
        <h3>Loan Settings</h3>
        <form method="POST">
          <input type="hidden" name="action" value="update_settings">
          <div class="field">
            <label class="field-label">Minimum Loan Limit (₱)</label>
            <input type="number" name="loan_min_limit" class="field-input" value="<?= e($minLimit) ?>">
          </div>
          <div class="field">
            <label class="field-label">Maximum Loan Limit (₱)</label>
            <input type="number" name="loan_max_limit" class="field-input" value="<?= e($maxLimit) ?>">
          </div>
          <div class="field">
            <label class="field-label">Interest Rate (decimal, e.g. 0.04 for 4%)</label>
            <input type="number" name="interest_rate" class="field-input" value="<?= e($rate) ?>" step="0.01">
          </div>
          <div class="field">
            <label class="field-label">Auto-Evaluation Delay (seconds)</label>
            <input type="number" name="auto_approve_delay" class="field-input" value="<?= e($autoDelay) ?>">
            <div class="field-hint">Default: 300 (5 minutes)</div>
          </div>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
      </div>

      <!-- Change Admin Password -->
      <div class="detail-section">
        <h3>Change Admin Password</h3>
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="field">
            <label class="field-label">New Password</label>
            <input type="password" name="new_password" class="field-input" required>
          </div>
          <div class="field">
            <label class="field-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="field-input" required>
          </div>
          <button type="submit" class="btn btn-danger">Update Password</button>
        </form>
      </div>
    </div>
  </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
