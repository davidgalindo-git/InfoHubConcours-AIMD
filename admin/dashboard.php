<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();
?>

<?php require __DIR__ . '/header.php'; ?>

<section class="section">
  <h1 class="section-title">Dashboard admin</h1>
  <div class="cards" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
    <a class="card nav-card" href="news_manage.php" style="text-decoration:none;">
      <div class="card-body">
        <h3 class="card-title">Gérer les actualités</h3>
        <p class="card-text">Créer et publier des articles (avec “à la une” + concours).</p>
      </div>
    </a>
    <a class="card nav-card" href="announcements_manage.php" style="text-decoration:none;">
      <div class="card-body">
        <h3 class="card-title">Gérer les annonces</h3>
        <p class="card-text">Catégories : vente, don, covoiturage, aide, etc.</p>
      </div>
    </a>
    <a class="card nav-card" href="ads_manage.php" style="text-decoration:none;">
      <div class="card-body">
        <h3 class="card-title">Gérer les pubs</h3>
        <p class="card-text">Créer des pubs (avec lien optionnel).</p>
      </div>
    </a>
    <div class="card">
      <div class="card-body">
        <h3 class="card-title">Astuce</h3>
        <p class="card-text">
          Le contenu accepte un Markdown “simple” (titres, gras, italique, liens, code inline).
        </p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>

