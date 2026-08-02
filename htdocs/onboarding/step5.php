<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid = $_SESSION['user_id'];
$prog = get_onboarding_progress($uid);

if (!$prog['steps'][1]) redirect('onboarding/step1.php');
if (!$prog['steps'][2]) redirect('onboarding/step2.php');
if (!$prog['steps'][3]) redirect('onboarding/step3.php');
if (!$prog['steps'][4]) redirect('onboarding/step4.php');

$errors = [];
$contacts = [
  ['name' => '', 'phone' => '', 'relationship' => ''],
  ['name' => '', 'phone' => '', 'relationship' => ''],
  ['name' => '', 'phone' => '', 'relationship' => ''],
];

$stmt = db()->prepare('SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY sort_order');
$stmt->execute([$uid]);
$existing = $stmt->fetchAll();
foreach ($existing as $i => $row) {
  if ($i < 3) $contacts[$i] = ['name' => $row['contact_name'], 'phone' => $row['phone'], 'relationship' => $row['relationship']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $names = $_POST['contact_name'] ?? [];
  $phones = $_POST['phone'] ?? [];
  $rels = $_POST['relationship'] ?? [];

  for ($i = 0; $i < 3; $i++) {
    $contacts[$i]['name'] = trim($names[$i] ?? '');
    $contacts[$i]['phone'] = trim($phones[$i] ?? '');
    $contacts[$i]['relationship'] = trim($rels[$i] ?? '');
    if ($contacts[$i]['name'] === '') $errors['name_' . $i] = 'Name is required.';
    if ($contacts[$i]['phone'] === '') $errors['phone_' . $i] = 'Phone is required.';
    if ($contacts[$i]['relationship'] === '') $errors['rel_' . $i] = 'Relationship is required.';
  }

  if (!$errors) {
    db()->prepare('DELETE FROM emergency_contacts WHERE user_id = ?')->execute([$uid]);
    for ($i = 0; $i < 3; $i++) {
      $stmt = db()->prepare('INSERT INTO emergency_contacts (user_id, contact_name, phone, relationship, sort_order) VALUES (?,?,?,?,?)');
      $stmt->execute([$uid, $contacts[$i]['name'], $contacts[$i]['phone'], $contacts[$i]['relationship'], $i + 1]);
    }

    // Generate credit limit & mark onboarding done
    $creditLimit = generate_credit_limit();
    db()->prepare('UPDATE users SET onboarding_step = 5, onboarding_done = 1, credit_limit = ? WHERE id = ?')->execute([$creditLimit, $uid]);
    redirect('dashboard.php?welcome=1');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Onboarding · Emergency Contacts'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/onboarding/step4.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Step 5 of 5</span>
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
      <div class="step-dot done"></div>
      <div class="step-dot <?= $prog['steps'][5] ? 'done' : 'active' ?>"></div>
    </div>

    <div class="page-head">
      <h1>Emergency Contacts</h1>
      <p>Add exactly three emergency contacts. We'll only contact them if needed.</p>
    </div>

    <form method="POST" novalidate>
      <?php for ($i = 0; $i < 3; $i++): ?>
      <div class="card mb-4" style="border-color:var(--primary-200);">
        <h4 style="font-size:.9rem; font-weight:700; color:var(--primary-700); margin-bottom:var(--space-3);">Contact <?= $i + 1 ?></h4>
        <div class="field">
          <label class="field-label">Name <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="contact_name[]" class="field-input" value="<?= e($contacts[$i]['name']) ?>" required>
          <?php if (!empty($errors['name_' . $i])): ?><div class="field-error"><?= e($errors['name_' . $i]) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Phone Number <span class="pct">+1% Profile Completion</span></label>
          <input type="tel" name="phone[]" class="field-input" value="<?= e($contacts[$i]['phone']) ?>" placeholder="09XX XXX XXXX" required>
          <?php if (!empty($errors['phone_' . $i])): ?><div class="field-error"><?= e($errors['phone_' . $i]) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Relationship <span class="pct">+1% Profile Completion</span></label>
          <select name="relationship[]" class="field-select" required>
            <option value="">Select...</option>
            <?php
            $relOptions = ['Parent','Spouse','Sibling','Child','Relative','Friend','Colleague','Guardian','Other'];
            foreach ($relOptions as $opt): ?>
              <option value="<?= $opt ?>" <?= $contacts[$i]['relationship']===$opt?'selected':'' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['rel_' . $i])): ?><div class="field-error"><?= e($errors['rel_' . $i]) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endfor; ?>

      <button type="submit" class="btn btn-primary mt-4">Complete Onboarding</button>
    </form>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
