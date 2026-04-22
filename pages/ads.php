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

<section class="my-6 animate-fade-in">
  <h1 class="text-xl font-bold mb-4">Pubs</h1>

  <?php if (!$items): ?>
    <div class="rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70">
      <p>Aucune pub.</p>
    </div>
  <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <?php foreach ($items as $ad): ?>
        <article class="card bg-base-300/50 border border-base-content/10 overflow-x-clip min-w-0 max-w-full hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
          <?php if (!empty($ad['image_path'])): ?>
            <figure class="aspect-[16/10] w-full overflow-hidden bg-base-300/50">
              <?php if (announcement_attachment_is_pdf((string)$ad['image_path'])): ?>
                <div class="flex h-full w-full items-center justify-center bg-base-300/70 text-sm font-semibold text-base-content/70">PDF joint</div>
              <?php elseif (announcement_attachment_is_image((string)$ad['image_path'])): ?>
                <img class="h-full w-full object-cover" src="<?= h($ad['image_path']) ?>" alt="">
              <?php else: ?>
                <div class="flex h-full w-full items-center justify-center bg-base-300/70 text-sm font-semibold text-base-content/70">Fichier joint</div>
              <?php endif; ?>
            </figure>
          <?php else: ?>
            <div class="flex h-36 items-center justify-center bg-base-300/60 text-base-content/45 font-bold border-b border-base-content/10">Pub</div>
          <?php endif; ?>
          <div class="card-body p-4 gap-2">
            <h3 class="font-bold text-base leading-snug"><?= h($ad['title']) ?></h3>
            <p class="text-xs text-base-content/55">Le <?= h(date_format(new DateTime($ad['posted_at']), 'd/m/Y')) ?></p>
            <p class="text-sm text-base-content/70 line-clamp-3"><?= h(markdown_snippet((string)$ad['content'], 160)) ?></p>
            <div class="flex flex-wrap gap-2 mt-1">
              <?php if (!empty($ad['link_url'])): ?>
                <a class="btn btn-primary btn-sm transition-transform duration-200 hover:scale-[1.02]" href="<?= h($ad['link_url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir</a>
              <?php endif; ?>
              <a class="btn btn-outline btn-sm border-base-content/25" href="index.php?route=ad_detail&id=<?= (int)$ad['id'] ?>">Détails</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-6">
      <?php if ($page > 1): ?>
        <a class="btn btn-outline border-base-content/20 transition-all duration-200 hover:border-primary/50" href="index.php?route=ads&page=<?= $page - 1 ?>">Précédent</a>
      <?php else: ?>
        <span></span>
      <?php endif; ?>
      <span class="text-sm text-base-content/55">Page <?= h((string)$page) ?> / <?= h((string)$totalPages) ?></span>
      <?php if ($page < $totalPages): ?>
        <a class="btn btn-outline border-base-content/20 transition-all duration-200 hover:border-primary/50" href="index.php?route=ads&page=<?= $page + 1 ?>">Suivant</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
