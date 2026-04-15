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

<section class="my-6 animate-fade-in">
  <h1 class="text-xl font-bold mb-4">Actualités</h1>

  <?php if (!$items): ?>
    <div class="rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70">
      <p>Aucune actualité trouvée.</p>
    </div>
  <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <?php foreach ($items as $n): ?>
        <article class="card bg-base-200/60 border border-base-content/10 overflow-x-clip min-w-0 max-w-full hover:-translate-y-0.5 hover:shadow-lg transition-all duration-300">
          <?php if (!empty($n['image_path'])): ?>
            <figure class="aspect-[16/10] w-full overflow-hidden bg-base-300/50">
              <img class="h-full w-full object-cover" src="<?= h($n['image_path']) ?>" alt="">
            </figure>
          <?php endif; ?>
          <div class="card-body p-4 gap-1">
            <h3 class="font-bold text-base leading-snug"><?= h($n['title']) ?></h3>
            <p class="text-xs text-base-content/55">Publié le <?= h(date_format(new DateTime($n['published_at']), 'd/m/Y')) ?></p>
            <p class="text-sm text-base-content/70 line-clamp-4"><?= h(markdown_snippet((string)$n['content'], 180)) ?></p>
            <a class="link link-primary font-bold text-sm mt-2 w-fit" href="index.php?route=news_detail&id=<?= (int)$n['id'] ?>">Lire</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-6">
      <?php if ($page > 1): ?>
        <a class="btn btn-outline border-base-content/20 transition-all duration-200 hover:border-primary/50" href="index.php?route=news&page=<?= $page - 1 ?>">Précédent</a>
      <?php else: ?>
        <span></span>
      <?php endif; ?>

      <span class="text-sm text-base-content/55">Page <?= h((string)$page) ?> / <?= h((string)$totalPages) ?></span>

      <?php if ($page < $totalPages): ?>
        <a class="btn btn-outline border-base-content/20 transition-all duration-200 hover:border-primary/50" href="index.php?route=news&page=<?= $page + 1 ?>">Suivant</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
