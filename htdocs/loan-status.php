<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_onboarding();

$uid = $_SESSION['user_id'];
$loanId = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM loans WHERE id = ? AND user_id = ?');
$stmt->execute([$loanId, $uid]);
$loan = $stmt->fetch();

if (!$loan) redirect('transactions.php');

// Auto-evaluation: if submitted > 5 minutes ago and not yet evaluated
$loan['status'] = run_auto_evaluation($loanId, $uid);
if ($loan['status'] !== 'pending') {
  $loan['auto_evaluated'] = 1;
  $loan['evaluated_at'] = date('Y-m-d H:i:s');
}

// Determine if this loan is awaiting final assessment (auto-evaluated but still pending)
$awaitingFinal = ($loan['auto_evaluated'] && $loan['status'] === 'pending');

$isEwallet = $loan['disbursal_method'] === 'ewallet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Loan Status'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/dashboard.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Loan Status</span>
    <div style="width:40px"></div>
  </div>

  <div class="page">
    <?php if ($loan['status'] === 'pending' && !$awaitingFinal): ?>
    <div class="status-hero">
      <div class="status-icon pending">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="status-title">Loan Pending</div>
      <div class="status-msg">
        <?php if ($isEwallet): ?>
          Disbursal can take up to 24 hours. Please stand by while one of our agents reviews your application.
        <?php else: ?>
          Card transactions may take 24–48 hours.
        <?php endif; ?>
      </div>
    </div>
    <?php elseif ($loan['status'] === 'pending' && $awaitingFinal): ?>
    <div class="status-hero">
      <div class="status-icon pending">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
      </div>
      <div class="status-title">One Final Assessment</div>
      <div class="status-msg">
        Your application has been pre-reviewed. A final assessment is being conducted by our team. Please wait for an admin to complete the review. This may take up to 24 hours.
      </div>
    </div>
    <?php elseif ($loan['status'] === 'approved'): ?>
    <div class="status-hero">
      <div class="status-icon approved">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div class="status-title">Loan Approved</div>
      <div class="status-msg">Processing Disbursal — This may take up to 24 hours.</div>
    </div>
    <?php else: ?>
    <div class="status-hero">
      <div class="status-icon rejected">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      </div>
      <div class="status-title">Loan Rejected</div>
      <div class="status-msg">Unfortunately, your application was not approved. Please try again later.</div>
    </div>
    <?php endif; ?>

    <!-- Loan Details -->
    <div class="card mt-6">
      <h3 style="font-size:.95rem; font-weight:700; color:var(--neutral-900); margin-bottom:var(--space-3);">Loan Details</h3>
      <div class="detail-row"><span class="lbl">Loan ID</span><span class="val">#<?= $loan['id'] ?></span></div>
      <div class="detail-row"><span class="lbl">Amount</span><span class="val"><?= format_peso($loan['amount']) ?></span></div>
      <div class="detail-row"><span class="lbl">Term</span><span class="val"><?= $loan['term_months'] ?> month(s)</span></div>
      <div class="detail-row"><span class="lbl">Interest Rate</span><span class="val"><?= ($loan['interest_rate'] * 100) ?>%</span></div>
      <div class="detail-row"><span class="lbl">Total Repayable</span><span class="val"><?= format_peso($loan['total_repayable']) ?></span></div>
      <div class="detail-row"><span class="lbl">Monthly Payment</span><span class="val"><?= format_peso($loan['monthly_payment']) ?></span></div>
      <div class="detail-row"><span class="lbl">Disbursal Method</span><span class="val"><?= ucfirst($loan['disbursal_method']) ?></span></div>
      <div class="detail-row"><span class="lbl">Submitted</span><span class="val"><?= format_datetime($loan['submitted_at']) ?></span></div>
      <?php if ($loan['evaluated_at']): ?>
      <div class="detail-row"><span class="lbl">Evaluated</span><span class="val"><?= format_datetime($loan['evaluated_at']) ?></span></div>
      <?php endif; ?>
    </div>

    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-secondary mt-6">Back to Dashboard</a>
  </div>
</div>
</body>
</html>
