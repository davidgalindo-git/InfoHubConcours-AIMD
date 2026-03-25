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

<section class="section">
  <h1 class="section-title">Concours du mois</h1>
  <?php if ($contest): ?>
    <article class="featured-card">
      <div class="featured-media">
        <?php if (!empty($contest['image_path'])): ?>
          <img class="card-image" src="<?= h($contest['image_path']) ?>" alt="">
        <?php endif; ?>
      </div>
      <div class="featured-body">
        <h2 class="featured-title"><?= h($contest['title']) ?></h2>
        <p class="muted">
          Publié le <?= h(date_format(new DateTime($contest['published_at']), 'd/m/Y')) ?>
        </p>
        <div class="rich">
          <?= render_markdown(markdown_snippet((string)$contest['content'], 260)) ?>
        </div>
        <a class="btn btn-primary" href="index.php?route=news_detail&id=<?= (int)$contest['id'] ?>">Voir l’actualité</a>
      </div>
    </article>
  <?php else: ?>
    <div class="empty-state">
      <h2>Pas de concours enregistré</h2>
      <p>Ajoute un article avec `contest_month = <?= h($currentMonth) ?>` dans la base.</p>
    </div>
  <?php endif; ?>
</section>

<section class="section grid-two">
  <div>
    <h1 class="section-title">Actualités à la une</h1>
    <div class="cards">
      <?php foreach ($news as $n): ?>
        <article class="card">
          <?php if (!empty($n['image_path'])): ?>
            <img class="card-image" src="<?= h($n['image_path']) ?>" alt="">
          <?php endif; ?>
          <div class="card-body">
            <h3 class="card-title"><?= h($n['title']) ?></h3>
            <p class="muted">Publié le <?= h(date_format(new DateTime($n['published_at']), 'd/m/Y')) ?></p>
            <p class="card-text"><?= h(markdown_snippet((string)$n['content'], 140)) ?></p>
            <a class="link" href="index.php?route=news_detail&id=<?= (int)$n['id'] ?>">Lire</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <a class="btn" href="index.php?route=news">Voir toutes les actualités</a>
  </div>

  <div>
    <h1 class="section-title">Annonces récentes</h1>
    <div class="cards">
      <?php foreach ($announcements as $a): ?>
        <article class="card">
          <?php if (!empty($a['image_path'])): ?>
            <img class="card-image" src="<?= h($a['image_path']) ?>" alt="">
          <?php endif; ?>
          <div class="card-body">
            <div class="pill"><?= h($a['category_slug']) ?></div>
            <h3 class="card-title"><?= h($a['title']) ?></h3>
            <p class="muted">Le <?= h(date_format(new DateTime($a['posted_at']), 'd/m/Y')) ?></p>
            <p class="card-text"><?= h(markdown_snippet((string)$a['content'], 140)) ?></p>
            <a class="link" href="index.php?route=announcement_detail&id=<?= (int)$a['id'] ?>">Voir</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <a class="btn" href="index.php?route=announcements">Voir toutes les annonces</a>
  </div>
</section>

<section class="section">
  <h1 class="section-title">Pubs récentes</h1>
  <div class="cards cards-ads">
    <?php foreach ($ads as $ad): ?>
      <article class="card card-ad">
        <?php if (!empty($ad['image_path'])): ?>
          <img class="card-image" src="<?= h($ad['image_path']) ?>" alt="">
        <?php endif; ?>
        <div class="card-body">
          <h3 class="card-title"><?= h($ad['title']) ?></h3>
          <p class="muted">Le <?= h(date_format(new DateTime($ad['posted_at']), 'd/m/Y')) ?></p>
          <p class="card-text"><?= h(markdown_snippet((string)$ad['content'], 160)) ?></p>
          <?php if (!empty($ad['link_url'])): ?>
            <a class="btn btn-primary btn-sm" href="<?= h($ad['link_url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir</a>
          <?php else: ?>
            <a class="btn btn-primary btn-sm" href="index.php?route=ad_detail&id=<?= (int)$ad['id'] ?>">Détails</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <a class="btn" href="index.php?route=ads">Voir toutes les pubs</a>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>

