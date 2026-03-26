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
    <!-- Bandeau assemblé (logo + bandeau.jpg) -->
    <div class="header">
      <div class="header-logo">
        <img
          class="header-logo-img"
          src="assets/logo.png"
          alt="logo.png"
        >
      </div>
      <div class="header-banner" aria-label="Bandeau">
        <img class="header-banner-img" src="assets/bandeau.jpg" alt="bandeau.jpg">
      </div>
    </div>
  </header>
  <?php require __DIR__ . '/navbar.php'; ?>
  <main class="container">

