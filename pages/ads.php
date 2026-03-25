<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = countAds();
$totalPages = (int)max(1, ceil($total / $perPage));
$items = getAdsList($perPage, $offset);
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="section">
  <h1 class="section-title">Pubs</h1>

  <?php if (!$items): ?>
    <div class="empty-state">
      <p>Aucune pub.</p>
    </div>
  <?php else: ?>
    <div class="cards cards-ads">
      <?php foreach ($items as $ad): ?>
        <article class="card card-ad">
          <?php if (!empty($ad['image_path'])): ?>
            <div class="ad-placeholder">Pub</div>
          <?php else: ?>
            <img class="card-image" src="<?= h($ad['image_path']) ?>" alt="">
          <?php endif; ?>
          <div class="card-body">
            <h3 class="card-title"><?= h($ad['title']) ?></h3>
            <p class="muted">Le <?= h(date_format(new DateTime($ad['posted_at']), 'd/m/Y')) ?></p>
            <p class="card-text"><?= h(markdown_snippet((string)$ad['content'], 160)) ?></p>
            <div class="stack">
              <?php if (!empty($ad['link_url'])): ?>
                <a class="btn btn-primary btn-sm" href="<?= h($ad['link_url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir</a>
              <?php endif; ?>
              <a class="btn btn-sm" href="index.php?route=ad_detail&id=<?= (int)$ad['id'] ?>">Détails</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="pagination">
      <?php if ($page > 1): ?>
        <a class="btn" href="index.php?route=ads&page=<?= $page - 1 ?>">Précédent</a>
      <?php endif; ?>
      <span class="muted">Page <?= h((string)$page) ?> / <?= h((string)$totalPages) ?></span>
      <?php if ($page < $totalPages): ?>
        <a class="btn" href="index.php?route=ads&page=<?= $page + 1 ?>">Suivant</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>

