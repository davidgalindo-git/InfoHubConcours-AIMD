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

<section class="my-6 animate-fade-in">
  <h1 class="text-xl font-bold mb-4">Annonces</h1>

  <div role="tablist" class="tabs tabs-boxed flex-nowrap sm:flex-wrap gap-1 bg-base-200/50 p-1 rounded-2xl border border-base-content/10 max-w-full w-full sm:w-fit mb-5 overflow-x-auto overflow-y-hidden scroll-smooth pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden touch-pan-x">
    <a role="tab" class="tab tab-sm rounded-xl shrink-0 transition-all duration-200 <?= $category === 'toutes' ? 'tab-active !bg-primary text-primary-content shadow-sm' : '' ?>" href="index.php?route=announcements&category=toutes">Toutes</a>
    <?php foreach (ANNOUNCEMENT_CATEGORIES as $slug => $label): ?>
      <a role="tab" class="tab tab-sm rounded-xl shrink-0 transition-all duration-200 <?= $category === $slug ? 'tab-active !bg-primary text-primary-content shadow-sm' : '' ?>" href="index.php?route=announcements&category=<?= h($slug) ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70">
      <p>Aucune annonce.</p>
    </div>
  <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <?php foreach ($items as $a): ?>
        <article class="card bg-base-200/60 border border-base-content/10 overflow-hidden min-w-0 max-w-full hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
          <?php
          $attachmentPath = !empty($a['image_path']) ? (string)$a['image_path'] : null;
          require __DIR__ . '/../templates/partials/announcement_card_media.php';
          ?>
          <div class="card-body p-4 gap-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
              <span class="badge badge-outline badge-sm font-semibold w-fit border-base-content/20"><?= h(ANNOUNCEMENT_CATEGORIES[$a['category_slug']] ?? $a['category_slug']) ?></span>
              <?= announcement_author_html($a) ?>
            </div>
            <h3 class="font-bold text-base leading-snug"><?= h($a['title']) ?></h3>
            <p class="text-xs text-base-content/55">Le <?= h(date_format(new DateTime($a['posted_at']), 'd/m/Y')) ?></p>
            <p class="text-sm text-base-content/70 line-clamp-4"><?= h(markdown_snippet((string)$a['content'], 170)) ?></p>
            <a class="link link-primary font-bold text-sm mt-2 w-fit" href="index.php?route=announcement_detail&id=<?= (int)$a['id'] ?>">Voir</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-6">
      <?php if ($page > 1): ?>
        <a class="btn btn-outline border-base-content/20 transition-all duration-200 hover:border-primary/50" href="index.php?route=announcements&category=<?= h($category) ?>&page=<?= $page - 1 ?>">Précédent</a>
      <?php else: ?>
        <span></span>
      <?php endif; ?>
      <span class="text-sm text-base-content/55">Page <?= h((string)$page) ?> / <?= h((string)$totalPages) ?></span>
      <?php if ($page < $totalPages): ?>
        <a class="btn btn-outline border-base-content/20 transition-all duration-200 hover:border-primary/50" href="index.php?route=announcements&category=<?= h($category) ?>&page=<?= $page + 1 ?>">Suivant</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
