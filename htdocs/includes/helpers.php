<?php
// ============================================================
// Helper Functions
// ============================================================

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function redirect($path) {
  header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
  exit;
}

function current_url_path() {
  return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
}

function format_peso($n) {
  return '₱' . number_format((float)$n, 2);
}

function format_date($d) {
  if (!$d) return '—';
  $ts = strtotime($d);
  return $ts ? date('M j, Y', $ts) : '—';
}

function format_datetime($d) {
  if (!$d) return '—';
  $ts = strtotime($d);
  return $ts ? date('M j, Y g:i A', $ts) : '—';
}

function time_ago($d) {
  if (!$d) return '—';
  $ts = strtotime($d);
  $diff = time() - $ts;
  if ($diff < 60) return 'just now';
  if ($diff < 3600) return floor($diff/60) . 'm ago';
  if ($diff < 86400) return floor($diff/3600) . 'h ago';
  return floor($diff/86400) . 'd ago';
}

function generate_otp() {
  return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function generate_credit_limit() {
  return random_int(LOAN_MIN_LIMIT, LOAN_MAX_LIMIT);
}

function calculate_loan($amount, $months, $rate = LOAN_INTEREST_RATE) {
  $interest = $amount * $rate * $months;
  $total = $amount + $interest;
  $monthly = $total / $months;
  return [
    'interest'      => $interest,
    'total'         => $total,
    'monthly'       => $monthly,
  ];
}

function get_setting($key, $default = null) {
  $stmt = db()->prepare('SELECT setting_val FROM app_settings WHERE setting_key = ?');
  $stmt->execute([$key]);
  $row = $stmt->fetch();
  return $row ? $row['setting_val'] : $default;
}

function set_setting($key, $val) {
  $stmt = db()->prepare('INSERT INTO app_settings (setting_key, setting_val) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)');
  $stmt->execute([$key, $val]);
}

function save_upload($field, $subdir = 'documents') {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','gif','webp','pdf','heic'];
  if (!in_array($ext, $allowed)) return null;
  $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
  $dir = UPLOAD_DIR . $subdir . '/';
  if (!is_dir($dir)) mkdir($dir, 0775, true);
  $path = $dir . $name;
  if (move_uploaded_file($_FILES[$field]['tmp_name'], $path)) {
    return $subdir . '/' . $name;
  }
  return null;
}

function upload_url($rel) {
  if (!$rel) return null;
  return UPLOAD_URL . $rel;
}

// ── Onboarding progress ──────────────────────────────────────
function get_onboarding_progress($userId) {
  $steps = [];
  $db = db();

  // Step 1: Personal Info
  $stmt = $db->prepare('SELECT * FROM personal_info WHERE user_id = ?');
  $stmt->execute([$userId]);
  $pi = $stmt->fetch();
  $steps[1] = $pi ? true : false;

  // Step 2: Employment
  $stmt = $db->prepare('SELECT * FROM employment WHERE user_id = ?');
  $stmt->execute([$userId]);
  $emp = $stmt->fetch();
  $steps[2] = $emp ? true : false;

  // Step 3: Identity
  $stmt = $db->prepare('SELECT * FROM identity_verification WHERE user_id = ?');
  $stmt->execute([$userId]);
  $idv = $stmt->fetch();
  $steps[3] = $idv ? true : false;

  // Step 4: Disbursal
  $stmt = $db->prepare('SELECT * FROM disbursal_method WHERE user_id = ?');
  $stmt->execute([$userId]);
  $dis = $stmt->fetch();
  $steps[4] = $dis ? true : false;

  // Step 5: Emergency contacts (need exactly 3)
  $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM emergency_contacts WHERE user_id = ?');
  $stmt->execute([$userId]);
  $ec = $stmt->fetch();
  $steps[5] = ((int)$ec['cnt']) >= 3;

  $done = 0;
  foreach ($steps as $s) if ($s) $done++;
  $pct = (int)round(($done / 5) * 100);

  return ['steps' => $steps, 'done' => $done, 'total' => 5, 'pct' => $pct];
}

function require_onboarding() {
  $uid = $_SESSION['user_id'] ?? null;
  if (!$uid) { redirect('login.php'); return; }
  $prog = get_onboarding_progress($uid);
  if ($prog['pct'] < 100) {
    for ($i = 1; $i <= 5; $i++) {
      if (!$prog['steps'][$i]) { redirect('onboarding/step' . $i . '.php'); return; }
    }
  }
}

function require_login() {
  if (!isset($_SESSION['user_id'])) redirect('login.php');
}

function user_has_active_loan($userId) {
  $stmt = db()->prepare("SELECT COUNT(*) as cnt FROM loans WHERE user_id = ? AND status IN ('pending','approved')");
  $stmt->execute([$userId]);
  return ((int)$stmt->fetch()['cnt']) > 0;
}

function get_user_row($userId) {
  $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
  $stmt->execute([$userId]);
  return $stmt->fetch();
}

// ── Auto-evaluation ──────────────────────────────────────────
// Scores the user's profile against a threshold. If the score passes,
// the loan is auto-approved. If not, it stays pending for admin final
// review with a "final assessment" message shown to the user.
// Returns ['score' => int, 'passed' => bool, 'factors' => string[]]
function evaluate_loan_profile($userId, $loanId) {
  $db = db();
  $score = 0;
  $factors = [];

  $user = get_user_row($userId);
  $prog = get_onboarding_progress($userId);

  // Factor 1: Credit limit (higher = stronger profile)
  $creditLimit = (float)($user['credit_limit'] ?? 0);
  if ($creditLimit >= 3000) { $score += 25; $factors[] = 'Strong credit limit'; }
  elseif ($creditLimit >= 1500) { $score += 15; $factors[] = 'Moderate credit limit'; }
  else { $score += 5; $factors[] = 'Low credit limit'; }

  // Factor 2: Employment income
  $stmt = $db->prepare('SELECT amount_of_pay FROM employment WHERE user_id = ?');
  $stmt->execute([$userId]);
  $emp = $stmt->fetch();
  $pay = $emp ? (float)$emp['amount_of_pay'] : 0;
  if ($pay >= 20000) { $score += 25; $factors[] = 'Strong income'; }
  elseif ($pay >= 10000) { $score += 15; $factors[] = 'Moderate income'; }
  elseif ($pay > 0) { $score += 8; $factors[] = 'Low income'; }
  else { $score += 0; $factors[] = 'No income data'; }

  // Factor 3: Onboarding completeness
  if ($prog['pct'] === 100) { $score += 20; $factors[] = 'Complete profile'; }
  else { $score += 0; $factors[] = 'Incomplete profile'; }

  // Factor 4: Loan term (shorter terms are lower risk)
  $stmt = $db->prepare('SELECT term_months FROM loans WHERE id = ?');
  $stmt->execute([$loanId]);
  $loan = $stmt->fetch();
  $term = (int)($loan['term_months'] ?? 12);
  if ($term <= 3) { $score += 15; $factors[] = 'Short term'; }
  elseif ($term <= 6) { $score += 10; $factors[] = 'Medium term'; }
  else { $score += 5; $factors[] = 'Long term'; }

  // Factor 5: Identity verification uploaded
  $stmt = $db->prepare('SELECT id_front, id_back, face_scan FROM identity_verification WHERE user_id = ?');
  $stmt->execute([$userId]);
  $idv = $stmt->fetch();
  if ($idv && $idv['id_front'] && $idv['id_back'] && $idv['face_scan']) {
    $score += 15; $factors[] = 'Identity verified';
  } else {
    $score += 0; $factors[] = 'Identity incomplete';
  }

  $threshold = 60;
  $passed = $score >= $threshold;

  return [
    'score'     => $score,
    'threshold' => $threshold,
    'passed'    => $passed,
    'factors'   => $factors,
  ];
}

// Run auto-evaluation on a loan if enough time has passed.
// Updates the loan status in the DB. Returns the updated status.
function run_auto_evaluation($loanId, $userId) {
  $db = db();
  $stmt = $db->prepare('SELECT * FROM loans WHERE id = ?');
  $stmt->execute([$loanId]);
  $loan = $stmt->fetch();

  if (!$loan || $loan['auto_evaluated']) return $loan ? $loan['status'] : 'pending';
  if ($loan['status'] !== 'pending') return $loan['status'];

  $autoDelay = (int)get_setting('auto_approve_delay', 300);
  $elapsed = time() - strtotime($loan['submitted_at']);
  if ($elapsed < $autoDelay) return 'pending';

  $result = evaluate_loan_profile($userId, $loanId);
  $newStatus = $result['passed'] ? 'approved' : 'pending';
  $note = 'Auto-evaluated: Score ' . $result['score'] . '/' . $result['threshold'] . '. ' . implode('; ', $result['factors']) . '.';

  $db->prepare('UPDATE loans SET auto_evaluated = 1, evaluated_at = NOW(), status = ?, admin_note = CONCAT(IFNULL(admin_note,""), ?) WHERE id = ?')
    ->execute([$newStatus, "\n" . $note, $loanId]);

  return $newStatus;
}
