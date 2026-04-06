<?php
declare(strict_types=1);

$current = $_GET['route'] ?? 'home';
?>
<nav class="px-4 mt-3 mb-5 max-w-[1100px] mx-auto animate-fade-in [animation-delay:50ms] [animation-fill-mode:both]">
  <div class="flex flex-wrap gap-2">
    <a class="btn btn-sm <?= $current === 'home' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=home">Accueil</a>
    <a class="btn btn-sm <?= $current === 'news' || $current === 'news_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=news">Actualités</a>
    <a class="btn btn-sm <?= $current === 'announcements' || $current === 'announcement_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=announcements">Annonces</a>
    <a class="btn btn-sm <?= $current === 'ads' || $current === 'ad_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=ads">Pubs</a>
    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
      <a class="btn btn-sm btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="admin/dashboard.php">Admin</a>
    <?php endif; ?>
  </div>
</nav>
