<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/admin-header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $loanId = (int)($_POST['loan_id'] ?? 0);
  $newStatus = $_POST['status'] ?? '';
  $note = trim($_POST['admin_note'] ?? '');

  if (in_array($newStatus, ['pending', 'approved', 'rejected']) && $loanId > 0) {
    $stmt = db()->prepare('UPDATE loans SET status = ?, admin_note = ?, evaluated_at = IFNULL(evaluated_at, NOW()) WHERE id = ?');
    $stmt->execute([$newStatus, $note ?: null, $loanId]);
  }
}

$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$query = "SELECT l.*, u.name, u.email, u.mobile FROM loans l JOIN users u ON l.user_id = u.id WHERE 1=1";
$params = [];
if ($statusFilter !== 'all') {
  $query .= " AND l.status = ?";
  $params[] = $statusFilter;
}
if ($search !== '') {
  $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}
$query .= " ORDER BY l.submitted_at DESC";
$stmt = db()->prepare($query);
$stmt->execute($params);
$loans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php admin_head('Loan Management'); ?>
</head>
<body class="admin-body">
<div class="admin-layout">
  <?php admin_sidebar('loans'); ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <button class="admin-menu-btn" onclick="toggleAdminSidebar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1>Loan Management</h1>
    </div>

    <!-- Toolbar -->
    <form method="GET" class="admin-toolbar">
      <input type="text" name="search" class="admin-search" placeholder="Search user..." value="<?= e($search) ?>">
      <select name="status" class="field-select" style="width:auto;" onchange="this.form.submit()">
        <option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All Statuses</option>
        <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
        <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>Approved</option>
        <option value="rejected" <?= $statusFilter==='rejected'?'selected':'' ?>>Rejected</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>

    <!-- Loans Table -->
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Term</th><th>Total</th><th>Method</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead>
        <tbody>
          <?php if (empty($loans)): ?>
          <tr><td colspan="9" style="text-align:center; padding:var(--space-8); color:var(--neutral-400);">No loans found.</td></tr>
          <?php else: ?>
            <?php foreach ($loans as $l): ?>
            <?php $awaitingFinal = ((int)$l['auto_evaluated'] === 1 && $l['status'] === 'pending'); ?>
            <tr>
              <td>#<?= $l['id'] ?></td>
              <td style="font-weight:600; color:var(--neutral-900);"><?= e($l['name']) ?><br><span class="text-sm text-muted"><?= e($l['mobile']) ?></span></td>
              <td><?= format_peso($l['amount']) ?></td>
              <td><?= $l['term_months'] ?> mo</td>
              <td><?= format_peso($l['total_repayable']) ?></td>
              <td><?= ucfirst($l['disbursal_method']) ?></td>
              <td>
                <span class="badge badge-<?= $l['status'] ?>"><?= $awaitingFinal ? 'Final Assessment' : ucfirst($l['status']) ?></span>
                <?php if ($l['auto_evaluated'] && $l['admin_note']): ?>
                <div class="text-sm text-muted mt-1" style="font-size:.72rem; max-width:200px; white-space:normal;"><?= e($l['admin_note']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= format_datetime($l['submitted_at']) ?></td>
              <td>
                <form method="POST" style="display:flex; gap:4px;">
                  <input type="hidden" name="loan_id" value="<?= $l['id'] ?>">
                  <select name="status" class="field-select" style="padding:6px 8px; font-size:.8rem; width:auto;">
                    <option value="pending" <?= $l['status']==='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $l['status']==='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $l['status']==='rejected'?'selected':'' ?>>Rejected</option>
                  </select>
                  <button type="submit" class="btn btn-primary btn-sm" style="padding:6px 12px; font-size:.8rem;">Update</button>
                </form>
              </td>
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
