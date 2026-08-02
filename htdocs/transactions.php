<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_onboarding();

$uid = $_SESSION['user_id'];
$stmt = db()->prepare('SELECT * FROM loans WHERE user_id = ? ORDER BY submitted_at DESC');
$stmt->execute([$uid]);
$loans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Transaction History'); ?>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/dashboard.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Transactions</span>
    <div style="width:40px"></div>
  </div>

  <div class="page">
    <div class="page-head">
      <h1>Transaction History</h1>
      <p>All your loan applications in one place.</p>
    </div>

    <?php if (empty($loans)): ?>
    <div class="card text-center" style="padding:var(--space-10);">
      <div style="width:56px; height:56px; margin:0 auto var(--space-4); border-radius:50%; background:var(--neutral-100); display:grid; place-items:center; color:var(--neutral-400);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M3 3v18h18M7 12l4-4 4 4 5-5"/></svg>
      </div>
      <h3 style="font-weight:700; color:var(--neutral-900);">No Transactions Yet</h3>
      <p class="text-sm text-muted mt-2">Apply for your first loan to see it here.</p>
      <a href="<?= BASE_URL ?>/apply.php" class="btn btn-primary mt-4" style="max-width:200px; margin:var(--space-4) auto 0;">Apply Now</a>
    </div>
    <?php else: ?>
      <?php foreach ($loans as $loan): ?>
      <?php
        // Run auto-evaluation for pending loans so the list is up to date
        if ($loan['status'] === 'pending' && !(int)$loan['auto_evaluated']) {
          $loan['status'] = run_auto_evaluation($loan['id'], $uid);
        }
        $awaitingFinal = ((int)$loan['auto_evaluated'] === 1 && $loan['status'] === 'pending');
        $badgeText = $awaitingFinal ? 'Final Assessment' : ucfirst($loan['status']);
      ?>
      <div class="card mb-4">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-3);">
          <div>
            <div style="font-weight:700; color:var(--neutral-900);">Loan #<?= $loan['id'] ?></div>
            <div class="text-sm text-muted"><?= format_datetime($loan['submitted_at']) ?></div>
          </div>
          <span class="badge badge-<?= $loan['status'] ?>"><?= $badgeText ?></span>
        </div>
        <div class="detail-row"><span class="lbl">Amount</span><span class="val"><?= format_peso($loan['amount']) ?></span></div>
        <div class="detail-row"><span class="lbl">Term</span><span class="val"><?= $loan['term_months'] ?> month(s)</span></div>
        <div class="detail-row"><span class="lbl">Total Repayable</span><span class="val"><?= format_peso($loan['total_repayable']) ?></span></div>
        <div class="detail-row"><span class="lbl">Monthly</span><span class="val"><?= format_peso($loan['monthly_payment']) ?></span></div>
        <a href="<?= BASE_URL ?>/loan-status.php?id=<?= $loan['id'] ?>" class="btn btn-secondary mt-3" style="font-size:.85rem;">View Details</a>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php bottom_nav('history'); ?>
</div>
</body>
</html>
