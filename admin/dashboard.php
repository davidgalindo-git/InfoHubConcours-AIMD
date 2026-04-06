<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();
?>

<?php require __DIR__ . '/header.php'; ?>

<section class="my-2">
  <h1 class="text-xl font-bold mb-5">Dashboard admin</h1>
  <div class="grid gap-4 sm:grid-cols-2">
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 no-underline text-inherit" href="news_manage.php">
      <div class="card-body">
        <h3 class="card-title text-base">Gérer les actualités</h3>
        <p class="text-sm text-base-content/65">Créer et publier des articles (avec “à la une” + concours).</p>
      </div>
    </a>
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 no-underline text-inherit" href="announcements_manage.php">
      <div class="card-body">
        <h3 class="card-title text-base">Gérer les annonces</h3>
        <p class="text-sm text-base-content/65">Catégories : vente, don, covoiturage, aide, etc.</p>
      </div>
    </a>
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 no-underline text-inherit" href="ads_manage.php">
      <div class="card-body">
        <h3 class="card-title text-base">Gérer les pubs</h3>
        <p class="text-sm text-base-content/65">Créer des pubs (avec lien optionnel).</p>
      </div>
    </a>
    <div class="card bg-base-200/40 border border-dashed border-base-content/15">
      <div class="card-body">
        <h3 class="card-title text-base">Astuce</h3>
        <p class="text-sm text-base-content/65">
          Le contenu accepte un Markdown “simple” (titres, gras, italique, liens, code inline).
        </p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
