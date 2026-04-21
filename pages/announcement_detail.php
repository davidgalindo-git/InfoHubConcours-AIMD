<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/repositories.php';

if (!isset($id)) {
  http_response_code(400);
  require __DIR__ . '/not_found.php';
  exit;
}

$row = getAnnouncementRowById((int)$id);
$viewer = auth_user();
if (!$row || !auth_can_view_announcement($viewer, $row)) {
  $ann = null;
} else {
  $ann = $row;
}
$canEdit = $ann && auth_can_manage_announcement($viewer, $ann);
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="my-6 animate-fade-in">
  <?php if (!$ann): ?>
    <div class="rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70">
      <h1 class="text-lg font-bold text-base-content mb-3">Annonce introuvable</h1>
      <a class="btn btn-outline border-base-content/20" href="index.php?route=announcements">Retour</a>
    </div>
  <?php else: ?>
    <article class="card bg-base-200/65 border border-base-content/10 shadow-lg p-4 sm:p-6 gap-4 min-w-0 max-w-full overflow-x-clip">
      <?php if (($ann['status'] ?? '') === 'hidden'): ?>
        <div class="alert alert-warning text-sm">Cette annonce n’est pas visible publiquement.</div>
      <?php endif; ?>
      <?php if (!empty($ann['image_path'])): ?>
        <?php if (announcement_attachment_is_pdf((string)$ann['image_path'])): ?>
          <a class="btn btn-outline border-base-content/25 w-fit" href="<?= h((string)$ann['image_path']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir le PDF joint</a>
        <?php elseif (announcement_attachment_is_image((string)$ann['image_path'])): ?>
          <img class="w-full max-w-full max-h-[360px] object-cover rounded-xl border border-base-content/10" src="<?= h((string)$ann['image_path']) ?>" alt="">
        <?php else: ?>
          <a class="link link-primary w-fit" href="<?= h((string)$ann['image_path']) ?>" target="_blank" rel="noopener noreferrer">Pièce jointe</a>
        <?php endif; ?>
      <?php endif; ?>
      <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <span class="badge badge-outline badge-lg font-semibold w-fit border-base-content/20"><?= h(ANNOUNCEMENT_CATEGORIES[$ann['category_slug']] ?? $ann['category_slug']) ?></span>
        <?= announcement_author_html($ann) ?>
      </div>
      <h1 class="text-2xl font-bold leading-tight"><?= h($ann['title']) ?></h1>
      <p class="text-sm text-base-content/55">Le <?= h(date_format(new DateTime($ann['posted_at']), 'd/m/Y')) ?></p>
      <div class="rich text-base-content/90">
        <?= render_markdown((string)$ann['content']) ?>
      </div>
      <div class="flex flex-wrap gap-2 mt-2">
        <a class="btn btn-outline border-base-content/20 w-fit transition-all duration-200 hover:border-primary/50" href="index.php?route=announcements">Retour aux annonces</a>
        <?php if ($canEdit): ?>
          <a class="btn btn-primary w-fit" href="index.php?route=edit_announcement&id=<?= (int)$ann['id'] ?>">Modifier ou supprimer</a>
        <?php endif; ?>
      </div>
    </article>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
