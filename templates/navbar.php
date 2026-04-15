<?php
declare(strict_types=1);

$current = $_GET['route'] ?? 'home';
?>
<nav class="shell sticky top-0 z-40 mt-3 mb-5 pt-1 -mx-0 animate-fade-in [animation-delay:50ms] [animation-fill-mode:both] bg-base-100/85 backdrop-blur-md sm:bg-transparent sm:backdrop-blur-none border-b border-base-content/[0.06] sm:border-0" aria-label="Navigation principale">
  <div class="flex flex-nowrap sm:flex-wrap gap-2 py-1 overflow-x-auto overflow-y-hidden scroll-smooth pb-2 sm:pb-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:overflow-visible touch-pan-x">
    <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'home' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=home">Accueil</a>
    <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'news' || $current === 'news_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=news">Actualités</a>
    <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'announcements' || $current === 'announcement_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=announcements">Annonces</a>
    <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'ads' || $current === 'ad_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=ads">Pubs</a>
    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
      <a class="btn btn-sm shrink-0 min-h-11 sm:min-h-8 btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="admin/dashboard.php">Admin</a>
    <?php endif; ?>
  </div>
</nav>
