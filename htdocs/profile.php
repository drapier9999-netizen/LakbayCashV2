<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_onboarding();

$uid = $_SESSION['user_id'];
$user = current_user();
$prog = get_onboarding_progress($uid);

// Fetch all onboarding data
$stmt = db()->prepare('SELECT * FROM personal_info WHERE user_id = ?');
$stmt->execute([$uid]);
$personal = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM employment WHERE user_id = ?');
$stmt->execute([$uid]);
$employment = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM disbursal_method WHERE user_id = ?');
$stmt->execute([$uid]);
$disbursal = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY sort_order');
$stmt->execute([$uid]);
$contacts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('My Profile'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <div style="width:40px"></div>
    <span class="topbar-title">My Profile</span>
    <a href="<?= BASE_URL ?>/logout.php" class="topbar-back" style="background:var(--error); color:#fff;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
    </a>
  </div>

  <div class="page">
    <!-- Profile Header -->
    <div class="card text-center mb-6" style="background:linear-gradient(135deg,var(--primary-50),var(--neutral-0)); border:none;">
      <div style="width:72px; height:72px; margin:0 auto var(--space-3); border-radius:50%; background:linear-gradient(135deg,var(--primary-500),var(--primary-700)); display:grid; place-items:center; color:#fff; font-family:var(--font-display); font-size:1.5rem; font-weight:700;">
        <?= strtoupper(substr($user['name'], 0, 1)) ?>
      </div>
      <h2 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:var(--neutral-900);"><?= e($user['name']) ?></h2>
      <p class="text-sm text-muted"><?= e($user['email']) ?></p>
      <p class="text-sm text-muted"><?= e($user['mobile']) ?></p>
    </div>

    <!-- Credit Limit -->
    <div class="credit-card mb-6">
      <div class="credit-label">Credit Limit</div>
      <div class="credit-value"><?= format_peso($user['credit_limit']) ?></div>
      <div class="credit-sub">Profile completion: <?= $prog['pct'] ?>%</div>
    </div>

    <!-- Personal Info -->
    <?php if ($personal): ?>
    <div class="card mb-4">
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-3);">Personal Information</h3>
      <div class="detail-row"><span class="lbl">Full Name</span><span class="val"><?= e($personal['first_name'] . ' ' . ($personal['middle_name'] ? $personal['middle_name'] . ' ' : '') . $personal['last_name']) ?></span></div>
      <div class="detail-row"><span class="lbl">Gender</span><span class="val"><?= e($personal['gender']) ?></span></div>
      <div class="detail-row"><span class="lbl">Date of Birth</span><span class="val"><?= format_date($personal['date_of_birth']) ?></span></div>
      <div class="detail-row"><span class="lbl">Nationality</span><span class="val"><?= e($personal['nationality']) ?></span></div>
      <div class="detail-row"><span class="lbl">Address</span><span class="val"><?= e($personal['street'] . ', ' . $personal['city'] . ', ' . $personal['province'] . ' ' . $personal['zip_code']) ?></span></div>
      <div class="detail-row"><span class="lbl">Region</span><span class="val"><?= e($personal['region']) ?></span></div>
      <div class="detail-row"><span class="lbl">Facebook</span><span class="val"><?= e($personal['facebook_name'] ?: '—') ?></span></div>
      <div class="detail-row"><span class="lbl">Dependents</span><span class="val"><?= $personal['num_dependents'] ?></span></div>
    </div>
    <?php endif; ?>

    <!-- Employment -->
    <?php if ($employment): ?>
    <div class="card mb-4">
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-3);">Employment</h3>
      <div class="detail-row"><span class="lbl">Occupation</span><span class="val"><?= e($employment['occupation_type']) ?></span></div>
      <div class="detail-row"><span class="lbl">Industry</span><span class="val"><?= e($employment['industry']) ?></span></div>
      <div class="detail-row"><span class="lbl">Payday</span><span class="val"><?= e($employment['payday']) ?></span></div>
      <div class="detail-row"><span class="lbl">Pay Amount</span><span class="val"><?= format_peso($employment['amount_of_pay']) ?></span></div>
    </div>
    <?php endif; ?>

    <!-- Disbursal -->
    <?php if ($disbursal): ?>
    <div class="card mb-4">
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-3);">Disbursal Method</h3>
      <div class="detail-row"><span class="lbl">Method</span><span class="val"><?= ucfirst(e($disbursal['method'])) ?></span></div>
      <?php if ($disbursal['method'] === 'ewallet'): ?>
        <div class="detail-row"><span class="lbl">Provider</span><span class="val"><?= e($disbursal['ewallet_provider']) ?></span></div>
        <div class="detail-row"><span class="lbl">Number</span><span class="val"><?= e($disbursal['ewallet_number']) ?></span></div>
        <div class="detail-row"><span class="lbl">Name</span><span class="val"><?= e($disbursal['ewallet_name']) ?></span></div>
      <?php else: ?>
        <div class="detail-row"><span class="lbl">Bank</span><span class="val"><?= e($disbursal['bank_name']) ?></span></div>
        <div class="detail-row"><span class="lbl">Card</span><span class="val">••••<?= substr($disbursal['card_number'] ?? '', -4) ?></span></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Emergency Contacts -->
    <?php if ($contacts): ?>
    <div class="card mb-4">
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-3);">Emergency Contacts</h3>
      <?php foreach ($contacts as $i => $c): ?>
      <div class="detail-row"><span class="lbl">Contact <?= $i + 1 ?></span><span class="val"><?= e($c['contact_name'] . ' (' . $c['relationship'] . ')') ?></span></div>
      <div class="detail-row"><span class="lbl">Phone</span><span class="val"><?= e($c['phone']) ?></span></div>
      <?php if ($i < count($contacts) - 1): ?><div class="divider"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php bottom_nav('profile'); ?>
</div>
</body>
</html>
