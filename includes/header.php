<?php
require_once __DIR__ . '/functions.php';
start_session();
$loggedIn = current_user_id() !== null;
$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<?php if ($loggedIn): ?>
  <div class="header-banner">
    <img class="background" src="/assets/header-background.png" alt="">
    <img class="logo" src="/assets/header-logo.png" alt="<?= h(SITE_NAME) ?>">
  </div>
  <header class="navbar">
    <div class="navbar-left">
      <a class="navbar-brand" href="/">My Game Circle</a>
      <nav class="navbar-links">
        <a href="/">Collectie</a>
        <a href="/playgroups.php">Playgroups</a>
        <a href="/speelavond.php">Speelavond</a>
      </nav>
    </div>
    <div class="navbar-right">
      <span><?= h(current_username()) ?></span>
      <a class="btn btn-secondary" href="/logout.php">Uitloggen</a>
    </div>
  </header>
<?php endif; ?>
<main class="container">
