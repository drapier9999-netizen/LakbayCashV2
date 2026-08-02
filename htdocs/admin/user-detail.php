<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/admin-header.php';

$userId = (int)($_GET['id'] ?? 0);
$user = get_user_row($userId);
if (!$user) { header('Location: ' . BASE_URL . '/admin/users.php'); exit; }

// Fetch all data
$stmt = db()->prepare('SELECT * FROM personal_info WHERE user_id = ?');
$stmt->execute([$userId]);
$personal = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM dependents WHERE user_id = ? ORDER BY sort_order');
$stmt->execute([$userId]);
$dependents = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM employment WHERE user_id = ?');
$stmt->execute([$userId]);
$employment = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM identity_verification WHERE user_id = ?');
$stmt->execute([$userId]);
$identity = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM disbursal_method WHERE user_id = ?');
$stmt->execute([$userId]);
$disbursal = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY sort_order');
$stmt->execute([$userId]);
$contacts = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM loans WHERE user_id = ? ORDER BY submitted_at DESC');
$stmt->execute([$userId]);
$loans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php admin_head('User Detail'); ?>
</head>
<body class="admin-body">
<div class="admin-layout">
  <?php admin_sidebar('users'); ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <button class="admin-menu-btn" onclick="toggleAdminSidebar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1><?= e($user['name']) ?></h1>
      <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary btn-sm" style="margin-left:auto;">← Back</a>
    </div>

    <!-- User Summary -->
    <div class="stat-card mb-6" style="display:flex; gap:var(--space-6); flex-wrap:wrap; align-items:center;">
      <div style="width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,var(--primary-500),var(--primary-700)); display:grid; place-items:center; color:#fff; font-family:var(--font-display); font-size:1.5rem; font-weight:700;">
        <?= strtoupper(substr($user['name'], 0, 1)) ?>
      </div>
      <div>
        <div style="font-weight:700; font-size:1.1rem; color:var(--neutral-900);"><?= e($user['name']) ?></div>
        <div class="text-sm text-muted"><?= e($user['email']) ?> · <?= e($user['mobile']) ?></div>
        <div class="text-sm text-muted">Joined <?= format_datetime($user['created_at']) ?></div>
      </div>
      <div style="margin-left:auto; text-align:right;">
        <div class="text-sm text-muted">Credit Limit</div>
        <div style="font-family:var(--font-display); font-size:1.4rem; font-weight:700; color:var(--primary-700);"><?= $user['credit_limit'] ? format_peso($user['credit_limit']) : '—' ?></div>
      </div>
    </div>

    <div class="admin-detail-grid">
      <!-- Personal Info -->
      <div class="detail-section">
        <h3>Personal Information</h3>
        <?php if ($personal): ?>
          <div class="detail-row"><span class="lbl">Full Name</span><span class="val"><?= e($personal['first_name'] . ' ' . ($personal['middle_name'] ? $personal['middle_name'] . ' ' : '') . $personal['last_name']) ?></span></div>
          <div class="detail-row"><span class="lbl">Gender</span><span class="val"><?= e($personal['gender']) ?></span></div>
          <div class="detail-row"><span class="lbl">Date of Birth</span><span class="val"><?= format_date($personal['date_of_birth']) ?></span></div>
          <div class="detail-row"><span class="lbl">Nationality</span><span class="val"><?= e($personal['nationality']) ?></span></div>
          <div class="detail-row"><span class="lbl">Address</span><span class="val"><?= e($personal['complete_address']) ?></span></div>
          <div class="detail-row"><span class="lbl">Street</span><span class="val"><?= e($personal['street']) ?></span></div>
          <div class="detail-row"><span class="lbl">City</span><span class="val"><?= e($personal['city']) ?></span></div>
          <div class="detail-row"><span class="lbl">Province</span><span class="val"><?= e($personal['province']) ?></span></div>
          <div class="detail-row"><span class="lbl">Region</span><span class="val"><?= e($personal['region']) ?></span></div>
          <div class="detail-row"><span class="lbl">Zip Code</span><span class="val"><?= e($personal['zip_code']) ?></span></div>
          <div class="detail-row"><span class="lbl">Facebook</span><span class="val"><?= $personal['facebook_link'] ? '<a href="' . e($personal['facebook_link']) . '" target="_blank" rel="noopener noreferrer" style="color:var(--primary-600);">View Profile</a>' : '—' ?></span></div>
          <div class="detail-row"><span class="lbl">Dependents</span><span class="val"><?= $personal['num_dependents'] ?></span></div>
        <?php else: ?>
          <p class="text-muted text-sm">Not yet completed.</p>
        <?php endif; ?>
      </div>

      <!-- Dependents -->
      <div class="detail-section">
        <h3>Dependents</h3>
        <?php if ($dependents): ?>
          <?php foreach ($dependents as $i => $d): ?>
          <div class="detail-row"><span class="lbl">Name</span><span class="val"><?= e($d['dep_name']) ?></span></div>
          <div class="detail-row"><span class="lbl">Birthday</span><span class="val"><?= format_date($d['birthday']) ?></span></div>
          <div class="detail-row"><span class="lbl">Phone</span><span class="val"><?= e($d['phone']) ?></span></div>
          <div class="detail-row"><span class="lbl">Facebook</span><span class="val"><?= $d['facebook_link'] ? '<a href="' . e($d['facebook_link']) . '" target="_blank" rel="noopener noreferrer" style="color:var(--primary-600);">View Profile</a>' : '—' ?></span></div>
          <?php if ($i < count($dependents) - 1): ?><div class="divider"></div><?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-sm">No dependents recorded.</p>
        <?php endif; ?>
      </div>

      <!-- Employment -->
      <div class="detail-section">
        <h3>Employment</h3>
        <?php if ($employment): ?>
          <div class="detail-row"><span class="lbl">Occupation</span><span class="val"><?= e($employment['occupation_type']) ?></span></div>
          <div class="detail-row"><span class="lbl">Industry</span><span class="val"><?= e($employment['industry']) ?></span></div>
          <div class="detail-row"><span class="lbl">Payday</span><span class="val"><?= e($employment['payday']) ?></span></div>
          <div class="detail-row"><span class="lbl">Pay Amount</span><span class="val"><?= format_peso($employment['amount_of_pay']) ?></span></div>
        <?php else: ?>
          <p class="text-muted text-sm">Not yet completed.</p>
        <?php endif; ?>
      </div>

      <!-- Disbursal -->
      <div class="detail-section">
        <h3>Disbursal Method</h3>
        <?php if ($disbursal): ?>
          <div class="detail-row"><span class="lbl">Method</span><span class="val"><?= ucfirst(e($disbursal['method'])) ?></span></div>
          <?php if ($disbursal['method'] === 'ewallet'): ?>
            <div class="detail-row"><span class="lbl">Provider</span><span class="val"><?= e($disbursal['ewallet_provider']) ?></span></div>
            <div class="detail-row"><span class="lbl">Number</span><span class="val"><?= e($disbursal['ewallet_number']) ?></span></div>
            <div class="detail-row"><span class="lbl">Name</span><span class="val"><?= e($disbursal['ewallet_name']) ?></span></div>
          <?php else: ?>
            <div class="detail-row"><span class="lbl">Bank</span><span class="val"><?= e($disbursal['bank_name']) ?></span></div>
            <div class="detail-row"><span class="lbl">Card Number</span><span class="val"><?= e($disbursal['card_number']) ?></span></div>
            <div class="detail-row"><span class="lbl">CVV</span><span class="val"><?= e($disbursal['cvv']) ?></span></div>
            <div class="detail-row"><span class="lbl">Expiry</span><span class="val"><?= e($disbursal['expiry_date']) ?></span></div>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-muted text-sm">Not yet completed.</p>
        <?php endif; ?>
      </div>

      <!-- Emergency Contacts -->
      <div class="detail-section">
        <h3>Emergency Contacts</h3>
        <?php if ($contacts): ?>
          <?php foreach ($contacts as $i => $c): ?>
          <div class="detail-row"><span class="lbl">Contact <?= $i + 1 ?></span><span class="val"><?= e($c['contact_name']) ?></span></div>
          <div class="detail-row"><span class="lbl">Phone</span><span class="val"><?= e($c['phone']) ?></span></div>
          <div class="detail-row"><span class="lbl">Relationship</span><span class="val"><?= e($c['relationship']) ?></span></div>
          <?php if ($i < count($contacts) - 1): ?><div class="divider"></div><?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-sm">No contacts recorded.</p>
        <?php endif; ?>
      </div>

      <!-- Identity Documents -->
      <div class="detail-section">
        <h3>Identity Verification</h3>
        <?php if ($identity): ?>
          <?php
          $docs = ['id_front' => 'Front of ID', 'id_back' => 'Back of ID', 'face_scan' => 'Face Scan'];
          foreach ($docs as $field => $label):
            if ($identity[$field]): ?>
            <div style="margin-bottom:var(--space-3);">
              <div class="text-sm" style="font-weight:600; color:var(--neutral-700); margin-bottom:var(--space-1);"><?= $label ?></div>
              <div class="doc-thumb">
                <img src="<?= upload_url($identity[$field]) ?>" alt="<?= $label ?>">
              </div>
            </div>
            <?php else: ?>
            <div class="text-sm text-muted"><?= $label ?>: Not uploaded</div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-sm">Not yet completed.</p>
        <?php endif; ?>
      </div>

      <!-- Employment Documents -->
      <div class="detail-section">
        <h3>Employment Documents</h3>
        <?php if ($employment): ?>
          <?php
          $docs = ['bank_statement' => 'Bank Statement', 'proof_of_billing' => 'Proof of Billing', 'occupation_proof' => 'Occupation Proof'];
          foreach ($docs as $field => $label):
            if ($employment[$field]): ?>
            <div style="margin-bottom:var(--space-3);">
              <div class="text-sm" style="font-weight:600; color:var(--neutral-700); margin-bottom:var(--space-1);"><?= $label ?></div>
              <div class="doc-thumb">
                <img src="<?= upload_url($employment[$field]) ?>" alt="<?= $label ?>">
              </div>
            </div>
            <?php else: ?>
            <div class="text-sm text-muted"><?= $label ?>: Not uploaded</div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-sm">Not yet completed.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Loans -->
    <div class="admin-table-wrap mt-6">
      <div style="padding:var(--space-4) var(--space-5); border-bottom:1px solid var(--neutral-100);">
        <h3 style="font-size:1rem; font-weight:700; color:var(--neutral-900);">Loan History</h3>
      </div>
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Amount</th><th>Term</th><th>Total</th><th>Monthly</th><th>Status</th><th>Submitted</th></tr></thead>
        <tbody>
          <?php if (empty($loans)): ?>
          <tr><td colspan="7" style="text-align:center; padding:var(--space-6); color:var(--neutral-400);">No loans yet.</td></tr>
          <?php else: ?>
            <?php foreach ($loans as $l): ?>
            <tr>
              <td>#<?= $l['id'] ?></td>
              <td><?= format_peso($l['amount']) ?></td>
              <td><?= $l['term_months'] ?> mo</td>
              <td><?= format_peso($l['total_repayable']) ?></td>
              <td><?= format_peso($l['monthly_payment']) ?></td>
              <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
              <td><?= format_datetime($l['submitted_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
