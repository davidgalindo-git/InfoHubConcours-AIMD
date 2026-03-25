<?php
declare(strict_types=1);

$current = $_GET['route'] ?? 'home';
?>
<nav class="navbar">
  <div class="container nav-inner">
    <a class="nav-link <?= $current === 'home' ? 'active' : '' ?>" href="index.php?route=home">Accueil</a>
    <a class="nav-link <?= $current === 'news' || $current === 'news_detail' ? 'active' : '' ?>" href="index.php?route=news">Actualités</a>
    <a class="nav-link <?= $current === 'announcements' || $current === 'announcement_detail' ? 'active' : '' ?>" href="index.php?route=announcements">Annonces</a>
    <a class="nav-link <?= $current === 'ads' || $current === 'ad_detail' ? 'active' : '' ?>" href="index.php?route=ads">Pubs</a>
    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
      <a class="nav-link" href="admin/dashboard.php">Admin</a>
    <?php endif; ?>
  </div>
</nav>

