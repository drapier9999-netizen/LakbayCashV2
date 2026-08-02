<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/admin-header.php';

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'date';
$sortMap = [
  'date'  => 'u.created_at DESC',
  'alpha' => 'u.name ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['date'];

if ($search !== '') {
  $stmt = db()->prepare("SELECT u.* FROM users u WHERE u.name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? ORDER BY $orderBy");
  $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
  $stmt = db()->query("SELECT u.* FROM users u ORDER BY $orderBy");
}
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php admin_head('Users'); ?>
</head>
<body class="admin-body">
<div class="admin-layout">
  <?php admin_sidebar('users'); ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <button class="admin-menu-btn" onclick="toggleAdminSidebar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1>User Management</h1>
    </div>

    <!-- Toolbar -->
    <form method="GET" class="admin-toolbar">
      <input type="text" name="search" class="admin-search" placeholder="Search by name, email, or mobile..." value="<?= e($search) ?>">
      <select name="sort" class="field-select" style="width:auto;" onchange="this.form.submit()">
        <option value="date" <?= $sort==='date'?'selected':'' ?>>Sort: Newest</option>
        <option value="alpha" <?= $sort==='alpha'?'selected':'' ?>>Sort: A–Z</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
    </form>

    <!-- Users Table -->
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Credit Limit</th><th>Status</th><th>Joined</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="7" style="text-align:center; padding:var(--space-8); color:var(--neutral-400);">No users found.</td></tr>
          <?php else: ?>
            <?php foreach ($users as $u): ?>
            <tr>
              <td style="font-weight:600; color:var(--neutral-900);"><?= e($u['name']) ?></td>
              <td><?= e($u['email']) ?></td>
              <td><?= e($u['mobile']) ?></td>
              <td><?= $u['credit_limit'] ? format_peso($u['credit_limit']) : '—' ?></td>
              <td>
                <?php if ($u['status'] === 'active'): ?>
                  <span class="badge badge-approved">Active</span>
                <?php else: ?>
                  <span class="badge badge-rejected">Suspended</span>
                <?php endif; ?>
              </td>
              <td><?= format_date($u['created_at']) ?></td>
              <td><a href="<?= BASE_URL ?>/admin/user-detail.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
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
