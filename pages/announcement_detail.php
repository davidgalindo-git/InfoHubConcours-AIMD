<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';

if (!isset($id)) {
  http_response_code(400);
  require __DIR__ . '/not_found.php';
  exit;
}

$ann = getAnnouncementById((int)$id);
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="section">
  <?php if (!$ann): ?>
    <div class="empty-state">
      <h1>Annonce introuvable</h1>
      <a class="btn" href="index.php?route=announcements">Retour</a>
    </div>
  <?php else: ?>
    <article class="detail">
      <a class="btn btn-back" href="index.php?route=announcements" aria-label="Retour">←</a>
      <?php if (!empty($ann['image_path'])): ?>
        <img class="detail-image" src="<?= h($ann['image_path']) ?>" alt="">
      <?php endif; ?>
      <div class="detail-header <?= empty($ann['image_path']) ? 'detail-header--no-image' : '' ?>">
        <div class="pill pill-lg"><?= h(ANNOUNCEMENT_CATEGORIES[$ann['category_slug']] ?? $ann['category_slug']) ?></div>
        <h1 class="detail-title"><?= h($ann['title']) ?></h1>
        <p class="muted">Le <?= h(date_format(new DateTime($ann['posted_at']), 'd/m/Y')) ?></p>
      </div>
      <div class="rich">
        <?= render_markdown((string)$ann['content']) ?>
      </div>
    </article>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>

