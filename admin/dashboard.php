<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();
?>

<?php require __DIR__ . '/header.php'; ?>

<section class="my-2">
  <h1 class="text-2xl font-bold mb-2">Administration</h1>
  <p class="text-sm text-base-content/60 mb-6 max-w-2xl">Choisis un type de contenu. Tout est enregistré dans MySQL et visible immédiatement sur le site public.</p>
  <div class="grid gap-4 sm:grid-cols-2 min-w-0">
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-[0.99] transition-all duration-300 no-underline text-inherit group min-w-0" href="manage.php?type=news">
      <div class="card-body">
        <h3 class="card-title text-base group-hover:text-primary transition-colors"><span class="mr-1 opacity-90" aria-hidden="true">📰</span> Actualités</h3>
        <p class="text-sm text-base-content/65">Articles, « à la une », <strong class="font-semibold text-base-content/80">concours du mois</strong> (champ mois <code class="text-xs opacity-80">YYYY-MM</code>).</p>
      </div>
    </a>
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-[0.99] transition-all duration-300 no-underline text-inherit group min-w-0" href="manage.php?type=announcements">
      <div class="card-body">
        <h3 class="card-title text-base group-hover:text-primary transition-colors"><span class="mr-1 opacity-90" aria-hidden="true">📌</span> Annonces</h3>
        <p class="text-sm text-base-content/65">Vente, don, covoiturage, aide, petits boulots — image et texte en Markdown.</p>
      </div>
    </a>
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-[0.99] transition-all duration-300 no-underline text-inherit group min-w-0" href="manage.php?type=ads">
      <div class="card-body">
        <h3 class="card-title text-base group-hover:text-primary transition-colors"><span class="mr-1 opacity-90" aria-hidden="true">✨</span> Pubs</h3>
        <p class="text-sm text-base-content/65">Encarts avec lien optionnel vers une URL externe.</p>
      </div>
    </a>
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-[0.99] transition-all duration-300 no-underline text-inherit group min-w-0" href="users.php">
      <div class="card-body">
        <h3 class="card-title text-base group-hover:text-primary transition-colors"><span class="mr-1 opacity-90" aria-hidden="true">👤</span> Comptes utilisateurs</h3>
        <p class="text-sm text-base-content/65">Recherche, suspensions temporisées, levée de suspension, suppression et journaux détaillés.</p>
      </div>
    </a>
    <a class="card bg-base-200/60 border border-base-content/10 shadow-md hover:shadow-xl hover:-translate-y-1 active:scale-[0.99] transition-all duration-300 no-underline text-inherit group min-w-0" href="moderation.php">
      <div class="card-body">
        <h3 class="card-title text-base group-hover:text-primary transition-colors"><span class="mr-1 opacity-90" aria-hidden="true">🛡️</span> Modération & logs</h3>
        <p class="text-sm text-base-content/65">Masquer ou réactiver les annonces et consulter les logs liés à une annonce.</p>
      </div>
    </a>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
