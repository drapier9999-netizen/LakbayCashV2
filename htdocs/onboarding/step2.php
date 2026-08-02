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
if ($firstIncomplete && $firstIncomplete !== 2) { redirect('onboarding/step' . $firstIncomplete . '.php'); }

$errors = [];
$form = ['occupation_type' => '', 'industry' => '', 'payday' => '', 'amount_of_pay' => ''];
$files = ['bank_statement' => '', 'proof_of_billing' => '', 'occupation_proof' => ''];

$stmt = db()->prepare('SELECT * FROM employment WHERE user_id = ?');
$stmt->execute([$uid]);
if ($row = $stmt->fetch()) { $form = array_merge($form, $row); $files = array_intersect_key($row, $files); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($form as $k => $v) if (isset($_POST[$k])) $form[$k] = trim($_POST[$k]);

  $req = ['occupation_type','industry','payday','amount_of_pay'];
  foreach ($req as $r) {
    if ($form[$r] === '') $errors[$r] = 'This field is required.';
  }

  // Handle uploads
  $uploadFields = ['bank_statement','proof_of_billing','occupation_proof'];
  foreach ($uploadFields as $f) {
    $path = save_upload($f);
    if ($path) { $files[$f] = $path; }
    if (!$files[$f]) $errors[$f] = 'This document is required.';
  }

  if (!$errors) {
    $stmt = db()->prepare('INSERT INTO employment
      (user_id, occupation_type, industry, payday, amount_of_pay, bank_statement, proof_of_billing, occupation_proof)
      VALUES (?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
      occupation_type=VALUES(occupation_type), industry=VALUES(industry), payday=VALUES(payday), amount_of_pay=VALUES(amount_of_pay), bank_statement=VALUES(bank_statement), proof_of_billing=VALUES(proof_of_billing), occupation_proof=VALUES(occupation_proof)');
    $stmt->execute([$uid, $form['occupation_type'], $form['industry'], $form['payday'], $form['amount_of_pay'], $files['bank_statement'], $files['proof_of_billing'], $files['occupation_proof']]);
    db()->prepare('UPDATE users SET onboarding_step = GREATEST(onboarding_step, 2) WHERE id = ?')->execute([$uid]);
    redirect('onboarding/step3.php');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Onboarding · Employment'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/onboarding/step1.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Step 2 of 5</span>
    <div style="width:40px"></div>
  </div>

  <div class="page">
    <div class="progress-wrap">
      <div class="progress-head"><span>Onboarding Progress</span><span><?= $prog['pct'] ?>%</span></div>
      <div class="progress-track"><div class="progress-fill" style="width:<?= $prog['pct'] ?>%"></div></div>
    </div>
    <div class="step-dots">
      <div class="step-dot done"></div>
      <div class="step-dot <?= $prog['steps'][2] ? 'done' : 'active' ?>"></div>
      <div class="step-dot <?= $prog['steps'][3] ? 'done' : '' ?>"></div>
      <div class="step-dot <?= $prog['steps'][4] ? 'done' : '' ?>"></div>
      <div class="step-dot <?= $prog['steps'][5] ? 'done' : '' ?>"></div>
    </div>

    <div class="page-head">
      <h1>Employment Details</h1>
      <p>Upload your documents and tell us about your work.</p>
    </div>

    <form method="POST" enctype="multipart/form-data" novalidate>
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-4);">Required Documents</h3>

      <div class="field">
        <label class="field-label">Bank Statement <span class="pct">+1% Profile Completion</span></label>
        <div class="upload-box" <?= $files['bank_statement'] ? 'class="has-file"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <div class="upload-label"><?= $files['bank_statement'] ? 'Uploaded ✓' : 'Tap to upload' ?></div>
          <div class="upload-sub">PDF, JPG, PNG (max 10MB)</div>
          <input type="file" name="bank_statement" accept=".jpg,.jpeg,.png,.pdf,.heic" hidden>
          <div class="upload-preview"><?= $files['bank_statement'] ? '<img src="'.upload_url($files['bank_statement']).'" alt="Bank Statement">' : '' ?></div>
        </div>
        <?php if (!empty($errors['bank_statement'])): ?><div class="field-error"><?= e($errors['bank_statement']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label class="field-label">Proof of Billing <span class="pct">+1% Profile Completion</span></label>
        <div class="upload-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <div class="upload-label"><?= $files['proof_of_billing'] ? 'Uploaded ✓' : 'Tap to upload' ?></div>
          <div class="upload-sub">PDF, JPG, PNG (max 10MB)</div>
          <input type="file" name="proof_of_billing" accept=".jpg,.jpeg,.png,.pdf,.heic" hidden>
          <div class="upload-preview"><?= $files['proof_of_billing'] ? '<img src="'.upload_url($files['proof_of_billing']).'" alt="Proof of Billing">' : '' ?></div>
        </div>
        <?php if (!empty($errors['proof_of_billing'])): ?><div class="field-error"><?= e($errors['proof_of_billing']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label class="field-label">Occupation Proof <span class="pct">+1% Profile Completion</span></label>
        <div class="upload-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <div class="upload-label"><?= $files['occupation_proof'] ? 'Uploaded ✓' : 'Tap to upload' ?></div>
          <div class="upload-sub">PDF, JPG, PNG (max 10MB)</div>
          <input type="file" name="occupation_proof" accept=".jpg,.jpeg,.png,.pdf,.heic" hidden>
          <div class="upload-preview"><?= $files['occupation_proof'] ? '<img src="'.upload_url($files['occupation_proof']).'" alt="Occupation Proof">' : '' ?></div>
        </div>
        <?php if (!empty($errors['occupation_proof'])): ?><div class="field-error"><?= e($errors['occupation_proof']) ?></div><?php endif; ?>
      </div>

      <div class="divider"></div>
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-4);">Work Information</h3>

      <div class="field-grid">
        <div class="field">
          <label class="field-label">Occupation Type <span class="pct">+1% Profile Completion</span></label>
          <select name="occupation_type" class="field-select" required>
            <option value="">Select...</option>
            <option value="Employed" <?= $form['occupation_type']==='Employed'?'selected':'' ?>>Employed</option>
            <option value="Self-Employed" <?= $form['occupation_type']==='Self-Employed'?'selected':'' ?>>Self-Employed</option>
            <option value="Business Owner" <?= $form['occupation_type']==='Business Owner'?'selected':'' ?>>Business Owner</option>
            <option value="Freelancer" <?= $form['occupation_type']==='Freelancer'?'selected':'' ?>>Freelancer</option>
            <option value="OFW" <?= $form['occupation_type']==='OFW'?'selected':'' ?>>OFW</option>
            <option value="Government Employee" <?= $form['occupation_type']==='Government Employee'?'selected':'' ?>>Government Employee</option>
          </select>
          <?php if (!empty($errors['occupation_type'])): ?><div class="field-error"><?= e($errors['occupation_type']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Industry <span class="pct">+1% Profile Completion</span></label>
          <select name="industry" class="field-select" required>
            <option value="">Select...</option>
            <option value="Agriculture" <?= $form['industry']==='Agriculture'?'selected':'' ?>>Agriculture</option>
            <option value="BPO/Call Center" <?= $form['industry']==='BPO/Call Center'?'selected':'' ?>>BPO/Call Center</option>
            <option value="Construction" <?= $form['industry']==='Construction'?'selected':'' ?>>Construction</option>
            <option value="Education" <?= $form['industry']==='Education'?'selected':'' ?>>Education</option>
            <option value="Finance/Banking" <?= $form['industry']==='Finance/Banking'?'selected':'' ?>>Finance/Banking</option>
            <option value="Healthcare" <?= $form['industry']==='Healthcare'?'selected':'' ?>>Healthcare</option>
            <option value="Hospitality" <?= $form['industry']==='Hospitality'?'selected':'' ?>>Hospitality</option>
            <option value="IT/Technology" <?= $form['industry']==='IT/Technology'?'selected':'' ?>>IT/Technology</option>
            <option value="Manufacturing" <?= $form['industry']==='Manufacturing'?'selected':'' ?>>Manufacturing</option>
            <option value="Retail" <?= $form['industry']==='Retail'?'selected':'' ?>>Retail</option>
            <option value="Transportation" <?= $form['industry']==='Transportation'?'selected':'' ?>>Transportation</option>
            <option value="Other" <?= $form['industry']==='Other'?'selected':'' ?>>Other</option>
          </select>
          <?php if (!empty($errors['industry'])): ?><div class="field-error"><?= e($errors['industry']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="field-grid">
        <div class="field">
          <label class="field-label">Payday <span class="pct">+1% Profile Completion</span></label>
          <select name="payday" class="field-select" required>
            <option value="">Select...</option>
            <option value="Weekly" <?= $form['payday']==='Weekly'?'selected':'' ?>>Weekly</option>
            <option value="Bi-weekly" <?= $form['payday']==='Bi-weekly'?'selected':'' ?>>Bi-weekly</option>
            <option value="15th & 30th" <?= $form['payday']==='15th & 30th'?'selected':'' ?>>15th & 30th</option>
            <option value="Monthly" <?= $form['payday']==='Monthly'?'selected':'' ?>>Monthly</option>
          </select>
          <?php if (!empty($errors['payday'])): ?><div class="field-error"><?= e($errors['payday']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Amount of Pay <span class="pct">+1% Profile Completion</span></label>
          <input type="number" name="amount_of_pay" class="field-input" value="<?= e($form['amount_of_pay']) ?>" placeholder="0.00" step="0.01" required>
          <?php if (!empty($errors['amount_of_pay'])): ?><div class="field-error"><?= e($errors['amount_of_pay']) ?></div><?php endif; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary mt-4">Save & Continue</button>
    </form>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
