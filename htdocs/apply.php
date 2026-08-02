<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_onboarding();

$uid = $_SESSION['user_id'];
$user = current_user();

// Prevent if user already has any loan (pending or approved)
if (user_has_active_loan($uid)) {
  redirect('dashboard.php');
}

// Extra safety: block submission if any loan exists for this user
$stmt = db()->prepare("SELECT COUNT(*) as cnt FROM loans WHERE user_id = ? AND status IN ('pending','approved')");
$stmt->execute([$uid]);
if ((int)$stmt->fetch()['cnt'] > 0) {
  redirect('dashboard.php');
}

$creditLimit = (float)$user['credit_limit'];
$errors = [];
$amount = $creditLimit;
$months = 1;
$disbursalMethod = 'ewallet';

// Get saved disbursal method
$stmt = db()->prepare('SELECT method FROM disbursal_method WHERE user_id = ?');
$stmt->execute([$uid]);
$disbRow = $stmt->fetch();
if ($disbRow) $disbursalMethod = $disbRow['method'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $amount = (float)($_POST['amount'] ?? 0);
  $months = max(1, min(LOAN_MAX_MONTHS, (int)($_POST['months'] ?? 1)));
  $disbursalMethod = $_POST['disbursal_method'] ?? 'ewallet';

  if ($amount < LOAN_MIN_LIMIT) $errors['amount'] = 'Minimum loan amount is ₱' . LOAN_MIN_LIMIT . '.';
  if ($amount > $creditLimit) $errors['amount'] = 'Amount exceeds your credit limit.';

  // Final server-side guard: no more than one active loan per user
  if (user_has_active_loan($uid)) {
    $errors['amount'] = 'You already have an active loan. You can apply again once it is fully settled.';
  }

  if (!$errors) {
    $calc = calculate_loan($amount, $months);
    $stmt = db()->prepare('INSERT INTO loans (user_id, amount, term_months, interest_rate, total_repayable, monthly_payment, disbursal_method, status) VALUES (?,?,?,?,?,?,?, "pending")');
    $stmt->execute([$uid, $amount, $months, LOAN_INTEREST_RATE, $calc['total'], $calc['monthly'], $disbursalMethod]);

    // Schedule auto-evaluation via cron-style check (evaluated on next page load after 5 min)
    redirect('loan-status.php?id=' . db()->lastInsertId());
  }
}

$calc = calculate_loan($amount, $months);
$sliderPct = ($creditLimit > LOAN_MIN_LIMIT) ? (($amount - LOAN_MIN_LIMIT) / ($creditLimit - LOAN_MIN_LIMIT)) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php app_head('Apply for a Loan'); ?>
<style>
  .term-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:var(--space-2); margin-bottom:var(--space-4); }
  .term-btn { padding:var(--space-3) var(--space-2); border:1.5px solid var(--neutral-200); border-radius:var(--radius-md); text-align:center; font-weight:600; font-size:.85rem; color:var(--neutral-600); cursor:pointer; transition: all var(--t-fast) var(--ease); background:var(--neutral-0); }
  .term-btn.active { border-color:var(--primary-500); background:var(--primary-50); color:var(--primary-700); }
  .summary-box { background:var(--primary-50); border:1px solid var(--primary-200); border-radius:var(--radius-md); padding:var(--space-4); margin:var(--space-4) 0; }
  .summary-row { display:flex; justify-content:space-between; padding:var(--space-2) 0; font-size:.9rem; }
  .summary-row.total { font-weight:700; font-size:1rem; border-top:1px solid var(--primary-200); margin-top:var(--space-2); padding-top:var(--space-3); }
</style>
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <a href="<?= BASE_URL ?>/dashboard.php" class="topbar-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>
    <span class="topbar-title">Loan Application</span>
    <div style="width:40px"></div>
  </div>

  <div class="page">
    <div class="page-head">
      <h1>Apply for a Loan</h1>
      <p>Choose your amount and repayment term.</p>
    </div>

    <!-- Credit Limit Display -->
    <div class="credit-card mb-6">
      <div class="credit-label">Available Credit Limit</div>
      <div class="credit-value"><?= format_peso($creditLimit) ?></div>
      <div class="credit-sub">Interest rate: <?= (LOAN_INTEREST_RATE * 100) ?>% flat per month</div>
    </div>

    <form method="POST" id="loanForm" novalidate>
      <!-- Amount Slider -->
      <div class="field">
        <label class="field-label">Loan Amount</label>
        <div style="display:flex; justify-content:space-between; margin-bottom:var(--space-3);">
          <span class="text-sm text-muted">₱<?= LOAN_MIN_LIMIT ?></span>
          <span style="font-family:var(--font-display); font-size:1.5rem; font-weight:700; color:var(--primary-700);" id="amountDisplay"><?= format_peso($amount) ?></span>
          <span class="text-sm text-muted"><?= format_peso($creditLimit) ?></span>
        </div>
        <input type="range" name="amount" class="range-slider" id="amountSlider" min="<?= LOAN_MIN_LIMIT ?>" max="<?= (int)$creditLimit ?>" step="100" value="<?= (int)$amount ?>" style="--pct:<?= $sliderPct ?>%">
        <?php if (!empty($errors['amount'])): ?><div class="field-error"><?= e($errors['amount']) ?></div><?php endif; ?>
      </div>

      <!-- Term Selection -->
      <div class="field">
        <label class="field-label">Repayment Duration</label>
        <div class="term-grid">
          <?php for ($m = 1; $m <= LOAN_MAX_MONTHS; $m++): ?>
          <button type="button" class="term-btn <?= $months === $m ? 'active' : '' ?>" data-months="<?= $m ?>" onclick="selectTerm(<?= $m ?>)"><?= $m ?> Mo</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="months" id="monthsHidden" value="<?= $months ?>">
      </div>

      <!-- Disbursal Method -->
      <div class="field">
        <label class="field-label">Disbursal Method</label>
        <div class="method-tab" style="display:flex; gap:var(--space-2); background:var(--neutral-100); border-radius:var(--radius-md); padding:4px; margin-bottom:var(--space-3);">
          <button type="button" id="dmEwallet" class="<?= $disbursalMethod==='ewallet'?'active':'' ?>" style="flex:1; padding:var(--space-3); border-radius:var(--radius-sm); font-weight:600; font-size:.88rem; color:var(--neutral-500); background:<?= $disbursalMethod==='ewallet'?'var(--neutral-0)':'transparent' ?>; box-shadow:<?= $disbursalMethod==='ewallet'?'var(--shadow-sm)':'none' ?>;" onclick="selectDisbursal('ewallet')">E-Wallet</button>
          <button type="button" id="dmBank" class="<?= $disbursalMethod==='bank'?'active':'' ?>" style="flex:1; padding:var(--space-3); border-radius:var(--radius-sm); font-weight:600; font-size:.88rem; color:var(--neutral-500); background:<?= $disbursalMethod==='bank'?'var(--neutral-0)':'transparent' ?>; box-shadow:<?= $disbursalMethod==='bank'?'var(--shadow-sm)':'none' ?>;" onclick="selectDisbursal('bank')">Bank Card</button>
        </div>
        <input type="hidden" name="disbursal_method" id="dmHidden" value="<?= e($disbursalMethod) ?>">
        <div class="field-hint">Your disbursal details from onboarding will be used.</div>
      </div>

      <!-- Summary -->
      <div class="summary-box">
        <div class="summary-row"><span>Loan Amount</span><span id="sumAmount"><?= format_peso($amount) ?></span></div>
        <div class="summary-row"><span>Term</span><span id="sumTerm"><?= $months ?> month(s)</span></div>
        <div class="summary-row"><span>Interest (<?= (LOAN_INTEREST_RATE * 100) ?>%/mo)</span><span id="sumInterest"><?= format_peso($calc['interest']) ?></span></div>
        <div class="summary-row total"><span>Total Repayable</span><span id="sumTotal"><?= format_peso($calc['total']) ?></span></div>
        <div class="summary-row"><span>Monthly Payment</span><span id="sumMonthly"><?= format_peso($calc['monthly']) ?></span></div>
      </div>

      <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
  </div>

  <?php bottom_nav('loan'); ?>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
var slider = document.getElementById('amountSlider');
var monthsHidden = document.getElementById('monthsHidden');
var dmHidden = document.getElementById('dmHidden');
var rate = <?= LOAN_INTEREST_RATE ?>;

function recalc() {
  var amt = parseFloat(slider.value);
  var mos = parseInt(monthsHidden.value);
  var interest = amt * rate * mos;
  var total = amt + interest;
  var monthly = total / mos;
  document.getElementById('amountDisplay').textContent = '₱' + amt.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  document.getElementById('sumAmount').textContent = '₱' + amt.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  document.getElementById('sumTerm').textContent = mos + ' month(s)';
  document.getElementById('sumInterest').textContent = '₱' + interest.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  document.getElementById('sumTotal').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  document.getElementById('sumMonthly').textContent = '₱' + monthly.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  updateSliderFill(slider);
}

function selectTerm(m) {
  monthsHidden.value = m;
  document.querySelectorAll('.term-btn').forEach(function(b){ b.classList.remove('active'); });
  document.querySelector('.term-btn[data-months="' + m + '"]').classList.add('active');
  recalc();
}

function selectDisbursal(m) {
  dmHidden.value = m;
  var ew = document.getElementById('dmEwallet'), bk = document.getElementById('dmBank');
  if (m === 'ewallet') {
    ew.style.background = 'var(--neutral-0)'; ew.style.color = 'var(--primary-700)'; ew.style.boxShadow = 'var(--shadow-sm)';
    bk.style.background = 'transparent'; bk.style.color = 'var(--neutral-500)'; bk.style.boxShadow = 'none';
  } else {
    bk.style.background = 'var(--neutral-0)'; bk.style.color = 'var(--primary-700)'; bk.style.boxShadow = 'var(--shadow-sm)';
    ew.style.background = 'transparent'; ew.style.color = 'var(--neutral-500)'; ew.style.boxShadow = 'none';
  }
}

slider.addEventListener('input', recalc);
recalc();
</script>
</body>
</html>
