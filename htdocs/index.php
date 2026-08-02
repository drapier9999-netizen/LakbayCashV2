<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
  $prog = get_onboarding_progress($_SESSION['user_id']);
  if ($prog['pct'] < 100) {
    for ($i = 1; $i <= 5; $i++) {
      if (!$prog['steps'][$i]) { redirect('onboarding/step' . $i . '.php'); }
    }
  }
  redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#059669">
  <title><?= APP_NAME ?> — <?= APP_TAGLINE ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
</head>
<body>
<div class="landing">
  <div class="app-shell" style="background:transparent; box-shadow:none; max-width:480px;">

    <!-- Hero -->
    <section class="hero">
      <div class="hero-logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4"/><path d="M3 17l9 4 9-4"/>
        </svg>
      </div>
      <h1><span class="accent">Lakbay</span>Cash</h1>
      <p><?= APP_TAGLINE ?> — Your trusted companion for quick, fair, and transparent cash loans.</p>
    </section>

    <!-- Features -->
    <section class="features">
      <div class="feature-card">
        <div class="feature-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div><h3>Fast Approval</h3><p>Get your credit limit in minutes. No long queues, no paperwork delays.</p></div>
      </div>
      <div class="feature-card">
        <div class="feature-icon gold">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </div>
        <div><h3>Fair & Transparent</h3><p>Flat 4% interest with no hidden charges. See exactly what you pay upfront.</p></div>
      </div>
      <div class="feature-card">
        <div class="feature-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div><h3>Secure & Trusted</h3><p>Your data is protected with bank-grade security. Built for every Filipino.</p></div>
      </div>
    </section>

    <!-- Stats -->
    <div class="stats-strip">
      <div class="stat-item"><div class="stat-val">₱500–₱6K</div><div class="stat-lbl">Credit Range</div></div>
      <div class="stat-item"><div class="stat-val">4%</div><div class="stat-lbl">Flat Rate</div></div>
      <div class="stat-item"><div class="stat-val">1–9</div><div class="stat-lbl">Months</div></div>
    </div>

    <!-- CTA -->
    <section class="cta-section">
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Get Started</a>
      <p class="cta-note">By continuing, you agree to our Terms of Service & Privacy Policy.</p>
    </section>

  </div>
</div>
</body>
</html>
