<?php
function admin_head($title = '') {
  $t = $title ? e($title) . ' · Admin' : 'Admin · ' . APP_NAME;
  echo '<!DOCTYPE html><html lang="en"><head>'
    . '<meta charset="UTF-8">'
    . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
    . '<meta name="robots" content="noindex,nofollow">'
    . '<title>' . $t . '</title>'
    . '<link rel="preconnect" href="https://fonts.googleapis.com">'
    . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    . '<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">'
    . '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/app.css">'
    . '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/landing.css">'
    . '</head><body class="admin-body">';
}

function admin_sidebar($active) {
  $items = [
    'dashboard' => ['index.php', 'Dashboard', '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
    'users'     => ['users.php', 'Users', '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>'],
    'loans'     => ['loans.php', 'Loans', '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
    'settings'  => ['settings.php', 'Settings', '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/>'],
  ];
  echo '<aside class="admin-sidebar" id="adminSidebar">'
    . '<div class="admin-sidebar-brand"><span class="logo-dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4"/><path d="M3 17l9 4 9-4"/></svg></span>' . APP_NAME . '</div>'
    . '<nav class="admin-nav">';
  foreach ($items as $key => $item) {
    $cls = $active === $key ? 'active' : '';
    echo '<a href="' . BASE_URL . '/admin/' . $item[0] . '" class="' . $cls . '">'
      . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $item[2] . '</svg>'
      . '<span>' . $item[1] . '</span></a>';
  }
  echo '</nav>'
    . '<div style="position:absolute; bottom:var(--space-6); left:var(--space-4); right:var(--space-4);">'
    . '<a href="' . BASE_URL . '/admin/logout.php" style="display:flex; align-items:center; gap:var(--space-2); color:var(--neutral-400); font-size:.88rem;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg> Logout</a>'
    . '</div>'
    . '</aside>';
}
