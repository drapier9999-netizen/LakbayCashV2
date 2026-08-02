<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_onboarding();

$uid = $_SESSION['user_id'];
$user = current_user();
$hasActiveLoan = user_has_active_loan($uid);

// Get latest loan
$stmt = db()->prepare('SELECT * FROM loans WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1');
$stmt->execute([$uid]);
$latestLoan = $stmt->fetch();

$welcome = isset($_GET['welcome']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Dashboard'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <div class="topbar-brand">
      <span class="logo-dot">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4"/><path d="M3 17l9 4 9-4"/></svg>
      </span>
      <?= APP_NAME ?>
    </div>
    <a href="<?= BASE_URL ?>/profile.php" class="topbar-back" style="background:var(--primary-50);">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/></svg>
    </a>
  </div>

  <div class="page">
    <?php if ($welcome): ?>
    <div class="card mb-6" style="background:linear-gradient(135deg,var(--primary-50),var(--accent-50)); border:none;">
      <div style="display:flex; align-items:center; gap:var(--space-3);">
        <div style="width:44px; height:44px; border-radius:50%; background:var(--primary-500); display:grid; place-items:center; color:#fff;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <div style="font-weight:700; color:var(--neutral-900);">Welcome to <?= APP_NAME ?>!</div>
          <div class="text-sm text-muted">Your profile is complete. You can now apply for a loan.</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Greeting -->
    <div class="page-head">
      <h1>Kamusta, <?= e($user['name']) ?>!</h1>
      <p>Here's your loan overview.</p>
    </div>

    <!-- Credit Limit Card -->
    <div class="credit-card mb-6">
      <div class="credit-label">Your Credit Limit</div>
      <div class="credit-value"><?= format_peso($user['credit_limit']) ?></div>
      <div class="credit-sub"><?= $hasActiveLoan ? 'Credit Limit Exhausted — active loan in progress' : 'Available for a new loan' ?></div>
    </div>

    <!-- Active Loan Status -->
    <?php if ($latestLoan): ?>
    <?php
      // Run auto-evaluation so the dashboard reflects the latest status
      $latestLoan['status'] = run_auto_evaluation($latestLoan['id'], $uid);
      $awaitingFinal = ((int)$latestLoan['auto_evaluated'] === 1 && $latestLoan['status'] === 'pending');
    ?>
    <div class="card mb-6">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-3);">
        <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900);">Latest Loan</h3>
        <?php
        $badgeClass = 'badge-' . $latestLoan['status'];
        $badgeText = $awaitingFinal ? 'Final Assessment' : ucfirst($latestLoan['status']);
        ?>
        <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
      </div>
      <div class="detail-row"><span class="lbl">Amount</span><span class="val"><?= format_peso($latestLoan['amount']) ?></span></div>
      <div class="detail-row"><span class="lbl">Term</span><span class="val"><?= $latestLoan['term_months'] ?> month(s)</span></div>
      <div class="detail-row"><span class="lbl">Total Repayable</span><span class="val"><?= format_peso($latestLoan['total_repayable']) ?></span></div>
      <div class="detail-row"><span class="lbl">Monthly Payment</span><span class="val"><?= format_peso($latestLoan['monthly_payment']) ?></span></div>
      <div class="detail-row"><span class="lbl">Submitted</span><span class="val"><?= time_ago($latestLoan['submitted_at']) ?></span></div>
      <a href="<?= BASE_URL ?>/transactions.php" class="btn btn-secondary mt-4" style="font-size:.88rem;">View Details</a>
    </div>
    <?php endif; ?>

    <!-- Apply CTA -->
    <?php if (!$hasActiveLoan): ?>
    <a href="<?= BASE_URL ?>/apply.php" class="btn btn-primary" style="font-size:1.05rem;">Apply for a Loan</a>
    <?php else: ?>
    <div class="card text-center" style="background:var(--neutral-100); border-style:dashed; border-color:var(--neutral-300);">
      <div style="width:48px; height:48px; margin:0 auto var(--space-3); border-radius:50%; background:var(--warning); display:grid; place-items:center; color:#fff;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <h3 style="font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-1);">Credit Limit Exhausted</h3>
      <p class="text-sm text-muted">You have an active loan. Apply again once it's fully settled.</p>
    </div>
    <?php endif; ?>
  </div>

  <?php bottom_nav('home'); ?>
</div>
</body>
</html>
