<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/admin-header.php';

// Stats
$totalUsers = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalLoans = (int)db()->query('SELECT COUNT(*) FROM loans')->fetchColumn();
$pendingLoans = (int)db()->query("SELECT COUNT(*) FROM loans WHERE status='pending'")->fetchColumn();
$approvedLoans = (int)db()->query("SELECT COUNT(*) FROM loans WHERE status='approved'")->fetchColumn();
$totalDisbursed = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM loans WHERE status='approved'")->fetchColumn();

// Recent users
$stmt = db()->query('SELECT id, name, email, mobile, credit_limit, created_at FROM users ORDER BY created_at DESC LIMIT 5');
$recentUsers = $stmt->fetchAll();

// Recent loans
$stmt = db()->query('SELECT l.id, l.amount, l.status, l.submitted_at, u.name FROM loans l JOIN users u ON l.user_id = u.id ORDER BY l.submitted_at DESC LIMIT 5');
$recentLoans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php admin_head('Dashboard'); ?>
</head>
<body class="admin-body">
<div class="admin-layout">
  <?php admin_sidebar('dashboard'); ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <button class="admin-menu-btn" onclick="toggleAdminSidebar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1>Dashboard</h1>
      <div style="margin-left:auto; font-size:.88rem; color:var(--neutral-500);">Welcome, <?= e($_SESSION['admin_name'] ?? 'Admin') ?></div>
    </div>

    <!-- Stat Cards -->
    <div class="admin-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-50); color:var(--primary-600);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $totalUsers ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#e0f2fe; color:var(--secondary-600);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-label">Total Loans</div>
        <div class="stat-value"><?= $totalLoans ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7; color:#d97706;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-label">Pending Loans</div>
        <div class="stat-value"><?= $pendingLoans ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5; color:#059669;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-label">Approved Loans</div>
        <div class="stat-value"><?= $approvedLoans ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:var(--accent-50); color:var(--accent-600);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-label">Total Disbursed</div>
        <div class="stat-value"><?= format_peso($totalDisbursed) ?></div>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="admin-table-wrap mb-6">
      <div style="padding:var(--space-4) var(--space-5); border-bottom:1px solid var(--neutral-100);">
        <h3 style="font-size:1rem; font-weight:700; color:var(--neutral-900);">Recent Users</h3>
      </div>
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Credit Limit</th><th>Joined</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recentUsers as $u): ?>
          <tr>
            <td style="font-weight:600; color:var(--neutral-900);"><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['mobile']) ?></td>
            <td><?= $u['credit_limit'] ? format_peso($u['credit_limit']) : '—' ?></td>
            <td><?= time_ago($u['created_at']) ?></td>
            <td><a href="<?= BASE_URL ?>/admin/user-detail.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent Loans -->
    <div class="admin-table-wrap">
      <div style="padding:var(--space-4) var(--space-5); border-bottom:1px solid var(--neutral-100);">
        <h3 style="font-size:1rem; font-weight:700; color:var(--neutral-900);">Recent Loan Applications</h3>
      </div>
      <table class="admin-table">
        <thead><tr><th>Loan ID</th><th>User</th><th>Amount</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recentLoans as $l): ?>
          <tr>
            <td>#<?= $l['id'] ?></td>
            <td style="font-weight:600; color:var(--neutral-900);"><?= e($l['name']) ?></td>
            <td><?= format_peso($l['amount']) ?></td>
            <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
            <td><?= time_ago($l['submitted_at']) ?></td>
            <td><a href="<?= BASE_URL ?>/admin/loans.php" class="btn btn-secondary btn-sm">Manage</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
