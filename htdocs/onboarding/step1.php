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
if ($firstIncomplete && $firstIncomplete !== 1) { redirect('onboarding/step' . $firstIncomplete . '.php'); }

$errors = [];
$form = [
  'first_name' => '', 'middle_name' => '', 'last_name' => '',
  'gender' => '', 'nationality' => 'Filipino', 'date_of_birth' => '',
  'complete_address' => '', 'street' => '', 'city' => '', 'province' => '',
  'region' => '', 'zip_code' => '', 'facebook_link' => '', 'num_dependents' => 1,
];

// Load existing
$stmt = db()->prepare('SELECT * FROM personal_info WHERE user_id = ?');
$stmt->execute([$uid]);
if ($row = $stmt->fetch()) { $form = array_merge($form, $row); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($form as $k => $v) {
    if (isset($_POST[$k])) $form[$k] = trim($_POST[$k]);
  }
  $form['num_dependents'] = max(1, min(5, (int)($_POST['num_dependents'] ?? 1)));

  $req = ['first_name','last_name','gender','date_of_birth','complete_address','street','city','province','region','zip_code'];
  foreach ($req as $r) {
    if ($form[$r] === '') $errors[$r] = 'This field is required.';
  }

  if ($form['facebook_link'] === '') {
    $errors['facebook_link'] = 'Facebook profile link is required.';
  } elseif (!is_valid_facebook_url($form['facebook_link'])) {
    $errors['facebook_link'] = 'Enter a valid Facebook profile link (e.g. https://facebook.com/username).';
  }

  // Dependents — use strict string checks instead of empty() which
  // falsely rejects '0' and doesn't catch whitespace-only values.
  $raw = $_POST['dep_name'] ?? [];
  $dep_names = is_array($raw) ? array_map('trim', $raw) : [];
  $raw = $_POST['dep_birthday'] ?? [];
  $dep_bd = is_array($raw) ? array_map('trim', $raw) : [];
  $raw = $_POST['dep_phone'] ?? [];
  $dep_ph = is_array($raw) ? array_map('trim', $raw) : [];
  $raw = $_POST['dep_facebook_link'] ?? [];
  $dep_fb = is_array($raw) ? array_map('trim', $raw) : [];
  for ($i = 0; $i < $form['num_dependents']; $i++) {
    $n = $dep_names[$i] ?? '';
    $b = $dep_bd[$i] ?? '';
    $p = $dep_ph[$i] ?? '';
    $f = $dep_fb[$i] ?? '';
    if ($n === '' || $b === '' || $p === '' || $f === '') {
      $errors['dependents'] = 'Please complete all fields for Dependent ' . ($i + 1) . '.';
    } elseif (!is_valid_facebook_url($f)) {
      $errors['dependents'] = 'Dependent ' . ($i + 1) . ': Enter a valid Facebook profile link (e.g. https://facebook.com/username).';
    }
  }

  if (!$errors) {
    // Upsert personal_info
    $stmt = db()->prepare('INSERT INTO personal_info
      (user_id, first_name, middle_name, last_name, gender, nationality, date_of_birth, complete_address, street, city, province, region, zip_code, facebook_link, num_dependents)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
      first_name=VALUES(first_name), middle_name=VALUES(middle_name), last_name=VALUES(last_name), gender=VALUES(gender), nationality=VALUES(nationality), date_of_birth=VALUES(date_of_birth), complete_address=VALUES(complete_address), street=VALUES(street), city=VALUES(city), province=VALUES(province), region=VALUES(region), zip_code=VALUES(zip_code), facebook_link=VALUES(facebook_link), num_dependents=VALUES(num_dependents)');
    $stmt->execute([
      $uid, $form['first_name'], $form['middle_name'], $form['last_name'],
      $form['gender'], $form['nationality'], $form['date_of_birth'],
      $form['complete_address'], $form['street'], $form['city'], $form['province'],
      $form['region'], $form['zip_code'], $form['facebook_link'], $form['num_dependents']
    ]);

    // Delete & re-insert dependents
    $stmt = db()->prepare('DELETE FROM dependents WHERE user_id = ?');
    $stmt->execute([$uid]);
    for ($i = 0; $i < $form['num_dependents']; $i++) {
      $stmt = db()->prepare('INSERT INTO dependents (user_id, dep_name, birthday, phone, facebook_link, sort_order) VALUES (?,?,?,?,?,?)');
      $stmt->execute([$uid, $dep_names[$i], $dep_bd[$i], $dep_ph[$i], $dep_fb[$i], $i + 1]);
    }

    // Update onboarding step
    db()->prepare('UPDATE users SET onboarding_step = GREATEST(onboarding_step, 1) WHERE id = ?')->execute([$uid]);
    redirect('onboarding/step2.php');
  }
}

// Prepare dependent values for pre-fill on form re-display
$dep_values = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  for ($i = 0; $i < $form['num_dependents']; $i++) {
    $dep_values[] = [
      'name'          => $dep_names[$i] ?? '',
      'birthday'      => $dep_bd[$i] ?? '',
      'phone'         => $dep_ph[$i] ?? '',
      'facebook_link' => $dep_fb[$i] ?? '',
    ];
  }
} else {
  $stmt = db()->prepare('SELECT dep_name, birthday, phone, facebook_link FROM dependents WHERE user_id = ? ORDER BY sort_order');
  $stmt->execute([$uid]);
  foreach ($stmt->fetchAll() as $row) {
    $dep_values[] = [
      'name'          => $row['dep_name'],
      'birthday'      => $row['birthday'],
      'phone'         => $row['phone'],
      'facebook_link' => $row['facebook_link'],
    ];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Onboarding · Personal Info'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/onboarding/step1.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Step 1 of 5</span>
    <div style="width:40px"></div>
  </div>

  <div class="page">
    <!-- Progress -->
    <div class="progress-wrap">
      <div class="progress-head"><span>Onboarding Progress</span><span><?= $prog['pct'] ?>%</span></div>
      <div class="progress-track"><div class="progress-fill" style="width:<?= $prog['pct'] ?>%"></div></div>
    </div>
    <div class="step-dots">
      <div class="step-dot <?= $prog['steps'][1] ? 'done' : 'active' ?>"></div>
      <div class="step-dot <?= $prog['steps'][2] ? 'done' : '' ?>"></div>
      <div class="step-dot <?= $prog['steps'][3] ? 'done' : '' ?>"></div>
      <div class="step-dot <?= $prog['steps'][4] ? 'done' : '' ?>"></div>
      <div class="step-dot <?= $prog['steps'][5] ? 'done' : '' ?>"></div>
    </div>

    <div class="page-head">
      <h1>Personal Information</h1>
      <p>Tell us about yourself. Each field adds to your profile completion.</p>
    </div>

    <form method="POST" novalidate>
      <div class="field-grid">
        <div class="field">
          <label class="field-label">First Name <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="first_name" class="field-input" value="<?= e($form['first_name']) ?>" required>
          <?php if (!empty($errors['first_name'])): ?><div class="field-error"><?= e($errors['first_name']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Middle Name <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="middle_name" class="field-input" value="<?= e($form['middle_name'] ?? '') ?>">
        </div>
      </div>

      <div class="field">
        <label class="field-label">Last Name <span class="pct">+1% Profile Completion</span></label>
        <input type="text" name="last_name" class="field-input" value="<?= e($form['last_name']) ?>" required>
        <?php if (!empty($errors['last_name'])): ?><div class="field-error"><?= e($errors['last_name']) ?></div><?php endif; ?>
      </div>

      <div class="field-grid">
        <div class="field">
          <label class="field-label">Gender <span class="pct">+1% Profile Completion</span></label>
          <select name="gender" class="field-select" required>
            <option value="">Select...</option>
            <option value="Male" <?= $form['gender']==='Male'?'selected':'' ?>>Male</option>
            <option value="Female" <?= $form['gender']==='Female'?'selected':'' ?>>Female</option>
            <option value="Prefer not to say" <?= $form['gender']==='Prefer not to say'?'selected':'' ?>>Prefer not to say</option>
          </select>
          <?php if (!empty($errors['gender'])): ?><div class="field-error"><?= e($errors['gender']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Nationality <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="nationality" class="field-input" value="<?= e($form['nationality']) ?>" required>
        </div>
      </div>

      <div class="field">
        <label class="field-label">Date of Birth <span class="pct">+1% Profile Completion</span></label>
        <input type="date" name="date_of_birth" class="field-input" value="<?= e($form['date_of_birth']) ?>" required>
        <?php if (!empty($errors['date_of_birth'])): ?><div class="field-error"><?= e($errors['date_of_birth']) ?></div><?php endif; ?>
      </div>

      <div class="divider"></div>
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-4);">Address</h3>

      <div class="field">
        <label class="field-label">Complete Address <span class="pct">+1% Profile Completion</span></label>
        <textarea name="complete_address" class="field-textarea" rows="2" required><?= e($form['complete_address']) ?></textarea>
        <?php if (!empty($errors['complete_address'])): ?><div class="field-error"><?= e($errors['complete_address']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label class="field-label">Street <span class="pct">+1% Profile Completion</span></label>
        <input type="text" name="street" class="field-input" value="<?= e($form['street']) ?>" required>
        <?php if (!empty($errors['street'])): ?><div class="field-error"><?= e($errors['street']) ?></div><?php endif; ?>
      </div>

      <div class="field-grid">
        <div class="field">
          <label class="field-label">City <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="city" class="field-input" value="<?= e($form['city']) ?>" required>
          <?php if (!empty($errors['city'])): ?><div class="field-error"><?= e($errors['city']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Province <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="province" class="field-input" value="<?= e($form['province']) ?>" required>
          <?php if (!empty($errors['province'])): ?><div class="field-error"><?= e($errors['province']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="field-grid">
        <div class="field">
          <label class="field-label">Region <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="region" class="field-input" value="<?= e($form['region']) ?>" required>
          <?php if (!empty($errors['region'])): ?><div class="field-error"><?= e($errors['region']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">Zip Code <span class="pct">+1% Profile Completion</span></label>
          <input type="text" name="zip_code" class="field-input" value="<?= e($form['zip_code']) ?>" required>
          <?php if (!empty($errors['zip_code'])): ?><div class="field-error"><?= e($errors['zip_code']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label class="field-label">Facebook Profile Link <span class="pct">+1% Profile Completion</span></label>
        <input type="url" name="facebook_link" class="field-input" value="<?= e($form['facebook_link'] ?? '') ?>" placeholder="https://facebook.com/yourprofile" pattern="https?://(www\.|m\.)?(facebook\.com|fb\.com)/.+" required>
        <?php if (!empty($errors['facebook_link'])): ?><div class="field-error"><?= e($errors['facebook_link']) ?></div><?php endif; ?>
      </div>

      <div class="divider"></div>
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-4);">Dependents</h3>

      <div class="field">
        <label class="field-label">Number of Dependents <span class="pct">+1% Profile Completion</span></label>
        <select name="num_dependents" id="numDependents" class="field-select">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>" <?= (int)$form['num_dependents']===$i?'selected':'' ?>><?= $i ?></option>
          <?php endfor; ?>
        </select>
        <div class="field-hint">Select 1–5. Additional fields will appear below.</div>
      </div>

      <div id="dependentsContainer"></div>
      <?php if (!empty($errors['dependents'])): ?><div class="field-error"><?= e($errors['dependents']) ?></div><?php endif; ?>

      <button type="submit" class="btn btn-primary mt-4">Save & Continue</button>
    </form>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
var existingDeps = <?= json_encode($dep_values) ?>;
var sel = document.getElementById('numDependents');
var container = document.getElementById('dependentsContainer');
function updateDeps() { renderDependents(parseInt(sel.value), container, existingDeps); }
sel.addEventListener('change', updateDeps);
updateDeps();
</script>
</body>
</html>
