<?php
// ============================================================
// Header / Footer Partials (User App)
// ============================================================

function app_head($title = '', $extra_css = '') {
  $t = $title ? e($title) . ' · ' . APP_NAME : APP_NAME;
  $css = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/app.css">';
  if ($extra_css) $css .= '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/' . $extra_css . '">';
  echo '<!DOCTYPE html><html lang="en"><head>'
    . '<meta charset="UTF-8">'
    . '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">'
    . '<meta name="theme-color" content="#059669">'
    . '<title>' . $t . '</title>'
    . '<link rel="preconnect" href="https://fonts.googleapis.com">'
    . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    . '<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">'
    . $css
    . '</head><body>';
}

function app_footer() {
  echo '<script src="' . BASE_URL . '/assets/js/app.js"></script>';
  echo '</body></html>';
}

function bottom_nav($active) {
  $items = [
    'home'     => ['dashboard.php', 'Home', '<path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10"/>'],
    'loan'     => ['apply.php', 'Loan', '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
    'history'  => ['transactions.php', 'History', '<path d="M3 3v18h18M7 12l4-4 4 4 5-5"/>'],
    'profile'  => ['profile.php', 'Profile', '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/>'],
  ];
  echo '<nav class="bottom-nav">';
  foreach ($items as $key => $item) {
    $cls = $active === $key ? 'active' : '';
    echo '<a href="' . BASE_URL . '/' . $item[0] . '" class="' . $cls . '">'
      . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $item[2] . '</svg>'
      . '<span>' . $item[1] . '</span></a>';
  }
  echo '</nav>';
}
