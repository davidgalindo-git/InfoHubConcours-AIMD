<?php
declare(strict_types=1);

$rawNav = (string)($_GET['route'] ?? 'home');
$current = preg_replace('/[^a-z0-9_]/', '', strtolower($rawNav));
if ($current === '') {
  $current = 'home';
}
$currentUser = $_SESSION['user'] ?? null;
$role = is_array($currentUser) ? (string)($currentUser['role'] ?? '') : '';
$canAnnNav = in_array($role, ['user', 'admin'], true);
$canAdNav = in_array($role, ['collaborateur', 'admin'], true);
$isAdminNav = !empty($_SESSION['admin_logged_in']) || $role === 'admin';
$navProfileTab = preg_replace('/[^a-z]/', '', strtolower((string)($_GET['tab'] ?? 'compte')));
if ($navProfileTab === '') {
  $navProfileTab = 'compte';
}
$navProfileSettingsActive = $current === 'profile' && $navProfileTab === 'compte';
?>
<nav
  id="main-nav"
  class="shell sticky top-0 z-40 mt-3 mb-5 pt-1 -mx-0 animate-fade-in [animation-delay:50ms] [animation-fill-mode:both]
         bg-base-100/85 backdrop-blur-md
         border-b border-base-content/[0.06] sm:border-0
         transition-shadow duration-300"
  aria-label="Navigation principale">
  <div class="nav-shell flex w-full min-w-0 flex-row flex-wrap items-center gap-2">
    <div class="nav-links-row flex min-w-0 flex-1 flex-nowrap items-center gap-2 overflow-x-auto overflow-y-hidden scroll-smooth py-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:overflow-visible touch-pan-x">
      <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'home' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=home">Accueil</a>
      <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'news' || $current === 'news_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=news">Actualités</a>
      <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'announcements' || $current === 'announcement_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=announcements">Annonces</a>
      <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'ads' || $current === 'ad_detail' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=ads">Pubs</a>
      <?php if ($currentUser && $canAnnNav): ?>
        <a class="btn btn-sm shrink-0 min-h-11 sm:min-h-8 btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="index.php?route=create_announcement">Créer annonce</a>
      <?php endif; ?>
      <?php if ($currentUser && $canAdNav): ?>
        <a class="btn btn-sm shrink-0 min-h-11 sm:min-h-8 btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="index.php?route=create_ad">Créer pub</a>
      <?php endif; ?>
      <?php if ($isAdminNav): ?>
        <a class="btn btn-sm shrink-0 min-h-11 sm:min-h-8 btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="admin/dashboard.php">Admin</a>
      <?php endif; ?>
      <?php if ($currentUser): ?>
        <?php
          $pu = trim((string)($currentUser['full_name'] ?? ''));
          $em = (string)($currentUser['email'] ?? '');
          $tip = $pu !== '' ? $pu : ($em !== '' ? $em : 'Paramètres du compte');
          if ($em !== '' && $pu !== '') {
            $tip .= ' — ' . $em;
          }
        ?>
        <a
          class="nav-profile-btn btn btn-sm <?= $navProfileSettingsActive ? 'btn-primary shadow-md border-primary/30' : 'btn-ghost border border-base-content/10 hover:border-primary/35' ?> transition-all duration-200 ml-auto"
          href="index.php?route=profile&amp;tab=compte"
          title="<?= htmlspecialchars($tip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
          aria-label="Paramètres du compte"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
          </svg>
        </a>
      <?php else: ?>
        <div class="nav-end-group ml-auto flex shrink-0 items-center gap-2">
          <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'sign_in' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=sign_in">Sign in</a>
          <a class="btn btn-sm shrink-0 min-h-11 min-w-[2.75rem] sm:min-h-8 sm:min-w-0 <?= $current === 'sign_up' ? 'btn-primary shadow-md' : 'btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200' ?>" href="index.php?route=sign_up">Sign up</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>
