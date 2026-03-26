<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';

$category = (string)($_GET['category'] ?? 'toutes');
if ($category !== 'toutes' && !array_key_exists($category, ANNOUNCEMENT_CATEGORIES)) {
  $category = 'toutes';
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$total = countAnnouncements($category);
$totalPages = (int)max(1, ceil($total / $perPage));
$items = getAnnouncementsList($category, $perPage, $offset);
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="section">
  <h1 class="section-title">Annonces</h1>

  <div class="tabs">
    <a class="tab <?= $category === 'toutes' ? 'active' : '' ?>" href="index.php?route=announcements&category=toutes">Toutes</a>
    <?php foreach (ANNOUNCEMENT_CATEGORIES as $slug => $label): ?>
      <a class="tab <?= $category === $slug ? 'active' : '' ?>" href="index.php?route=announcements&category=<?= h($slug) ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="empty-state">
      <p>Aucune annonce.</p>
    </div>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($items as $a): ?>
        <a class="card card-clickable" href="index.php?route=announcement_detail&id=<?= (int)$a['id'] ?>">
          <?php if (!empty($a['image_path'])): ?>
            <img class="card-image" src="<?= h($a['image_path']) ?>" alt="">
          <?php endif; ?>
          <div class="card-body">
            <div class="pill"><?= h(ANNOUNCEMENT_CATEGORIES[$a['category_slug']] ?? $a['category_slug']) ?></div>
            <h3 class="card-title"><?= h($a['title']) ?></h3>
            <p class="muted">Le <?= h(date_format(new DateTime($a['posted_at']), 'd/m/Y')) ?></p>
            <p class="card-text"><?= h(markdown_snippet((string)$a['content'], 170)) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="pagination">
      <?php if ($page > 1): ?>
        <a class="btn" href="index.php?route=announcements&category=<?= h($category) ?>&page=<?= $page - 1 ?>">Précédent</a>
      <?php endif; ?>
      <span class="muted">Page <?= h((string)$page) ?> / <?= h((string)$totalPages) ?></span>
      <?php if ($page < $totalPages): ?>
        <a class="btn" href="index.php?route=announcements&category=<?= h($category) ?>&page=<?= $page + 1 ?>">Suivant</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>

