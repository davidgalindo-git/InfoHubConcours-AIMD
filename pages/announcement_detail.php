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

<section class="my-6 animate-fade-in">
  <?php if (!$ann): ?>
    <div class="rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70">
      <h1 class="text-lg font-bold text-base-content mb-3">Annonce introuvable</h1>
      <a class="btn btn-outline border-base-content/20" href="index.php?route=announcements">Retour</a>
    </div>
  <?php else: ?>
    <article class="card bg-base-200/65 border border-base-content/10 shadow-lg p-6 gap-4">
      <?php if (!empty($ann['image_path'])): ?>
        <img class="w-full max-h-[360px] object-cover rounded-xl border border-base-content/10" src="<?= h($ann['image_path']) ?>" alt="">
      <?php endif; ?>
      <span class="badge badge-outline badge-lg font-semibold w-fit border-base-content/20"><?= h(ANNOUNCEMENT_CATEGORIES[$ann['category_slug']] ?? $ann['category_slug']) ?></span>
      <h1 class="text-2xl font-bold leading-tight"><?= h($ann['title']) ?></h1>
      <p class="text-sm text-base-content/55">Le <?= h(date_format(new DateTime($ann['posted_at']), 'd/m/Y')) ?></p>
      <div class="rich text-base-content/90">
        <?= render_markdown((string)$ann['content']) ?>
      </div>
      <a class="btn btn-outline border-base-content/20 w-fit mt-2 transition-all duration-200 hover:border-primary/50" href="index.php?route=announcements">Retour aux annonces</a>
    </article>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
