<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';

if (!isset($id)) {
  http_response_code(400);
  require __DIR__ . '/not_found.php';
  exit;
}

$news = getNewsById((int)$id);
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="section">
  <?php if (!$news): ?>
    <div class="empty-state">
      <h1>Actualité introuvable</h1>
      <a class="btn" href="index.php?route=news">Retour</a>
    </div>
  <?php else: ?>
    <article class="detail">
      <?php if (!empty($news['image_path'])): ?>
        <img class="detail-image" src="<?= h($news['image_path']) ?>" alt="">
      <?php endif; ?>
      <h1 class="detail-title"><?= h($news['title']) ?></h1>
      <p class="muted">
        Publié le <?= h(date_format(new DateTime($news['published_at']), 'd/m/Y')) ?>
        <?php if (!empty($news['contest_month'])): ?>
          · Concours (<?= h((string)$news['contest_month']) ?>)
        <?php endif; ?>
      </p>
      <div class="rich">
        <?= render_markdown((string)$news['content']) ?>
      </div>
      <a class="btn" href="index.php?route=news">Retour aux actualités</a>
    </article>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>

