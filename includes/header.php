<?php
require_once __DIR__ . '/functions.php';
start_session();
$loggedIn = current_user_id() !== null;
$pageTitle = $pageTitle ?? SITE_NAME;
$activeNav = $activeNav ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="/assets/style.css">
  <?php $accentColor = current_accent_color(); ?>
  <?php if ($accentColor && is_valid_hex_color($accentColor)): ?>
    <style>:root { --accent: <?= h($accentColor) ?>; }</style>
  <?php endif; ?>
</head>
<body>
<?php if ($loggedIn): ?>
  <div class="topbar">
    <div class="topbar-inner">
      <button type="button" class="icon-btn" title="Search games" aria-label="Search games" data-open-modal="add-game-modal">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      </button>
      <button type="button" class="icon-btn" title="Messages (coming soon)" aria-label="Messages" disabled>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m2 6 10 7L22 6"></path></svg>
      </button>
      <div class="user-menu">
        <button type="button" class="user-menu-btn" id="user-menu-btn" aria-haspopup="true" aria-expanded="false" title="<?= h(current_username()) ?>">
          <span class="avatar-circle"><?= h(mb_strtoupper(mb_substr(current_username(), 0, 1))) ?></span>
          <svg class="chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="user-menu-dropdown hidden" id="user-menu-dropdown">
          <a href="/profile.php">Edit profile</a>
          <a href="/logout.php">Log out</a>
        </div>
      </div>
    </div>
  </div>
  <div class="header-banner">
    <img class="background" src="/assets/header-background.png" alt="">
    <img class="logo" src="/assets/header-logo.png" alt="<?= h(SITE_NAME) ?>">
  </div>
  <nav class="main-nav">
    <a class="main-nav-btn<?= $activeNav === 'collection' ? ' active' : '' ?>" href="/">Collection</a>
    <a class="main-nav-btn<?= $activeNav === 'playgroups' ? ' active' : '' ?>" href="/playgroups.php">Playgroups</a>
    <a class="main-nav-btn<?= $activeNav === 'gamenights' ? ' active' : '' ?>" href="/gamenights.php">Gamenights</a>
  </nav>
<?php endif; ?>
<main class="container">
