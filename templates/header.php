<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(SITE_TITLE) ?></title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="site-header">
    <div class="banner">
      <div class="banner-inner">
        <div class="brand">
          <div class="brand-logo" aria-label="Logo">
            <img src="assets/logo.png" alt="Logo" onerror="this.style.display='none'">
            <span class="brand-fallback"><?= htmlspecialchars(SITE_TITLE) ?></span>
          </div>
          <div class="brand-text">
            <div class="brand-subtitle">Section informatique</div>
          </div>
        </div>
      </div>
    </div>
  </header>
  <?php require __DIR__ . '/navbar.php'; ?>
  <main class="container">

