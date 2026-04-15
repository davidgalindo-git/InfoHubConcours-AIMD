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

<section class="my-6 animate-fade-in">
  <?php if (!$ad): ?>
    <div class="rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70">
      <h1 class="text-lg font-bold text-base-content mb-3">Pub introuvable</h1>
      <a class="btn btn-outline border-base-content/20" href="index.php?route=ads">Retour</a>
    </div>
  <?php else: ?>
    <article class="card bg-base-200/65 border border-base-content/10 shadow-lg p-4 sm:p-6 gap-4 min-w-0 max-w-full overflow-x-clip">
      <?php if (!empty($ad['image_path'])): ?>
        <img class="w-full max-w-full max-h-[360px] object-cover rounded-xl border border-base-content/10" src="<?= h($ad['image_path']) ?>" alt="">
      <?php endif; ?>
      <h1 class="text-2xl font-bold leading-tight"><?= h($ad['title']) ?></h1>
      <p class="text-sm text-base-content/55">Le <?= h(date_format(new DateTime($ad['posted_at']), 'd/m/Y')) ?></p>
      <div class="rich text-base-content/90">
        <?= render_markdown((string)$ad['content']) ?>
      </div>
      <div class="flex flex-wrap gap-3 mt-2">
        <?php if (!empty($ad['link_url'])): ?>
          <a class="btn btn-primary transition-transform duration-200 hover:scale-[1.02]" href="<?= h($ad['link_url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir</a>
        <?php endif; ?>
        <a class="btn btn-outline border-base-content/20 transition-all duration-200 hover:border-primary/50" href="index.php?route=ads">Retour aux pubs</a>
      </div>
    </article>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
