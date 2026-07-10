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
</head>
<body>
<?php if ($loggedIn): ?>
  <div class="topbar">
    <div class="topbar-inner">
      <button type="button" class="icon-btn" title="Messages (coming soon)" aria-label="Messages" disabled>&#9993;</button>
      <div class="user-menu">
        <button type="button" class="avatar-btn" id="user-menu-btn" aria-haspopup="true" aria-expanded="false" title="<?= h(current_username()) ?>"><?= h(mb_strtoupper(mb_substr(current_username(), 0, 1))) ?></button>
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
