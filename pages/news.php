<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = countNews();
$totalPages = (int)max(1, ceil($total / $perPage));

$items = getNewsList($perPage, $offset);
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="section">
  <h1 class="section-title">Actualités</h1>

  <?php if (!$items): ?>
    <div class="empty-state">
      <p>Aucune actualité trouvée.</p>
    </div>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($items as $n): ?>
        <a class="card card-clickable" href="index.php?route=news_detail&id=<?= (int)$n['id'] ?>">
          <?php if (!empty($n['image_path'])): ?>
            <img class="card-image" src="<?= h($n['image_path']) ?>" alt="">
          <?php endif; ?>
          <div class="card-body">
            <h3 class="card-title"><?= h($n['title']) ?></h3>
            <p class="muted">Publié le <?= h(date_format(new DateTime($n['published_at']), 'd/m/Y')) ?></p>
            <p class="card-text"><?= h(markdown_snippet((string)$n['content'], 180)) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="pagination">
      <?php if ($page > 1): ?>
        <a class="btn" href="index.php?route=news&page=<?= $page - 1 ?>">Précédent</a>
      <?php endif; ?>

      <span class="muted">Page <?= h((string)$page) ?> / <?= h((string)$totalPages) ?></span>

      <?php if ($page < $totalPages): ?>
        <a class="btn" href="index.php?route=news&page=<?= $page + 1 ?>">Suivant</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>

