<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<?php
$currentMonth = date('Y-m');
$contest = getContestOfMonth($currentMonth);
$news = getLatestFeaturedNews(2);
$announcements = getLatestFeaturedAnnouncements(2);
$ads = getLatestAds(2);
?>

<section class="my-6 animate-fade-in [animation-delay:80ms] [animation-fill-mode:both]">
  <h1 class="text-xl font-bold mb-4">Concours du mois</h1>
  <?php if ($contest): ?>
    <article class="card lg:card-side bg-base-200/65 border border-base-content/10 shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
      <figure class="lg:w-[min(100%,360px)] shrink-0 bg-base-300/40 p-3">
        <?php if (!empty($contest['image_path'])): ?>
          <img class="h-40 lg:h-full min-h-[160px] w-full object-cover rounded-xl border border-base-content/10" src="<?= h($contest['image_path']) ?>" alt="">
        <?php endif; ?>
      </figure>
      <div class="card-body gap-3">
        <h2 class="card-title text-2xl font-bold"><?= h($contest['title']) ?></h2>
        <p class="text-sm text-base-content/60">
          Publié le <?= h(date_format(new DateTime($contest['published_at']), 'd/m/Y')) ?>
        </p>
        <div class="rich text-base-content/90">
          <?= render_markdown(markdown_snippet((string)$contest['content'], 260)) ?>
        </div>
        <a class="btn btn-primary w-fit transition-transform duration-200 hover:scale-[1.02]" href="index.php?route=news_detail&id=<?= (int)$contest['id'] ?>">Voir l’actualité</a>
      </div>
    </article>
  <?php else: ?>
    <div class="rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70">
      <h2 class="text-lg font-bold text-base-content mb-2">Pas de concours enregistré</h2>
      <p>Ajoute un article avec `contest_month = <?= h($currentMonth) ?>` dans la base.</p>
    </div>
  <?php endif; ?>
</section>

<section class="my-8 grid gap-8 lg:grid-cols-2 animate-fade-in [animation-delay:120ms] [animation-fill-mode:both]">
  <div>
    <h1 class="text-xl font-bold mb-4">Actualités à la une</h1>
    <div class="grid gap-4 sm:grid-cols-2">
      <?php foreach ($news as $n): ?>
        <article class="card bg-base-200/60 border border-base-content/10 overflow-hidden hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
          <?php if (!empty($n['image_path'])): ?>
            <figure class="aspect-[16/10] w-full overflow-hidden bg-base-300/50">
              <img class="h-full w-full object-cover" src="<?= h($n['image_path']) ?>" alt="">
            </figure>
          <?php endif; ?>
          <div class="card-body p-4 gap-1">
            <h3 class="font-bold text-base leading-snug"><?= h($n['title']) ?></h3>
            <p class="text-xs text-base-content/55">Publié le <?= h(date_format(new DateTime($n['published_at']), 'd/m/Y')) ?></p>
            <p class="text-sm text-base-content/70 line-clamp-3"><?= h(markdown_snippet((string)$n['content'], 140)) ?></p>
            <a class="link link-primary font-bold text-sm mt-2 w-fit" href="index.php?route=news_detail&id=<?= (int)$n['id'] ?>">Lire</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <a class="btn btn-outline border-base-content/20 mt-4 transition-all duration-200 hover:border-primary/50" href="index.php?route=news">Voir toutes les actualités</a>
  </div>

  <div>
    <h1 class="text-xl font-bold mb-4">Annonces récentes</h1>
    <div class="grid gap-4 sm:grid-cols-2">
      <?php foreach ($announcements as $a): ?>
        <article class="card bg-base-200/60 border border-base-content/10 overflow-hidden hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
          <?php if (!empty($a['image_path'])): ?>
            <figure class="aspect-[16/10] w-full overflow-hidden bg-base-300/50">
              <img class="h-full w-full object-cover" src="<?= h($a['image_path']) ?>" alt="">
            </figure>
          <?php endif; ?>
          <div class="card-body p-4 gap-1">
            <span class="badge badge-outline badge-sm font-semibold w-fit border-base-content/20"><?= h($a['category_slug']) ?></span>
            <h3 class="font-bold text-base leading-snug"><?= h($a['title']) ?></h3>
            <p class="text-xs text-base-content/55">Le <?= h(date_format(new DateTime($a['posted_at']), 'd/m/Y')) ?></p>
            <p class="text-sm text-base-content/70 line-clamp-3"><?= h(markdown_snippet((string)$a['content'], 140)) ?></p>
            <a class="link link-primary font-bold text-sm mt-2 w-fit" href="index.php?route=announcement_detail&id=<?= (int)$a['id'] ?>">Voir</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <a class="btn btn-outline border-base-content/20 mt-4 transition-all duration-200 hover:border-primary/50" href="index.php?route=announcements">Voir toutes les annonces</a>
  </div>
</section>

<section class="my-8 animate-fade-in [animation-delay:160ms] [animation-fill-mode:both]">
  <h1 class="text-xl font-bold mb-4">Pubs récentes</h1>
  <div class="grid gap-4 sm:grid-cols-2">
    <?php foreach ($ads as $ad): ?>
      <article class="card bg-base-300/50 border border-base-content/10 overflow-hidden hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
        <?php if (!empty($ad['image_path'])): ?>
          <figure class="aspect-[16/10] w-full overflow-hidden bg-base-300/50">
            <img class="h-full w-full object-cover" src="<?= h($ad['image_path']) ?>" alt="">
          </figure>
        <?php endif; ?>
        <div class="card-body p-4 gap-2">
          <h3 class="font-bold text-base leading-snug"><?= h($ad['title']) ?></h3>
          <p class="text-xs text-base-content/55">Le <?= h(date_format(new DateTime($ad['posted_at']), 'd/m/Y')) ?></p>
          <p class="text-sm text-base-content/70 line-clamp-3"><?= h(markdown_snippet((string)$ad['content'], 160)) ?></p>
          <?php if (!empty($ad['link_url'])): ?>
            <a class="btn btn-primary btn-sm w-fit transition-transform duration-200 hover:scale-[1.02]" href="<?= h($ad['link_url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir</a>
          <?php else: ?>
            <a class="btn btn-primary btn-sm w-fit transition-transform duration-200 hover:scale-[1.02]" href="index.php?route=ad_detail&id=<?= (int)$ad['id'] ?>">Détails</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-outline border-base-content/20 mt-4 transition-all duration-200 hover:border-primary/50" href="index.php?route=ads">Voir toutes les pubs</a>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
