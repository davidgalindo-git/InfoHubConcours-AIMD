<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';

if (!isset($id)) {
  http_response_code(400);
  require __DIR__ . '/not_found.php';
  exit;
}

$ad = getAdById((int)$id);
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="section">
  <?php if (!$ad): ?>
    <div class="empty-state">
      <h1>Pub introuvable</h1>
      <a class="btn" href="index.php?route=ads">Retour</a>
    </div>
  <?php else: ?>
    <article class="detail">
      <a class="btn btn-back" href="index.php?route=ads" aria-label="Retour">←</a>
      <?php if (!empty($ad['image_path'])): ?>
        <img class="detail-image" src="<?= h($ad['image_path']) ?>" alt="">
      <?php endif; ?>
      <div class="detail-header <?= empty($ad['image_path']) ? 'detail-header--no-image' : '' ?>">
        <h1 class="detail-title"><?= h($ad['title']) ?></h1>
        <p class="muted">Le <?= h(date_format(new DateTime($ad['posted_at']), 'd/m/Y')) ?></p>
      </div>
      <div class="rich">
        <?= render_markdown((string)$ad['content']) ?>
      </div>
      <?php if (!empty($ad['link_url'])): ?>
        <a class="btn btn-primary" href="<?= h($ad['link_url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir</a>
      <?php endif; ?>
    </article>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>

