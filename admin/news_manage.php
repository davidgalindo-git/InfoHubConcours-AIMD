<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/auth.php';

require_admin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $title = trim((string)($_POST['title'] ?? ''));
  $content = (string)($_POST['content'] ?? '');
  $image_path = trim((string)($_POST['image_path'] ?? ''));
  $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
  $contest_month = trim((string)($_POST['contest_month'] ?? ''));
  $contest_month = $contest_month !== '' ? $contest_month : null;

  if ($title === '' || $content === '') {
    $error = "Titre et contenu sont requis.";
  } else {
    $stmt = db()->prepare(
      "INSERT INTO news (title, content, image_path, is_featured, contest_month) VALUES (:t, :c, :img, :f, :cm)"
    );
    $stmt->execute([
      't' => $title,
      'c' => $content,
      'img' => $image_path !== '' ? $image_path : null,
      'f' => $is_featured,
      'cm' => $contest_month,
    ]);
    $success = "Actualité créée.";
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $deleteId = (int)($_POST['id'] ?? 0);
  if ($deleteId > 0) {
    db()->prepare("DELETE FROM news WHERE id = :id")->execute(['id' => $deleteId]);
    $success = "Actualité supprimée.";
  }
}

$items = db()->query("SELECT * FROM news ORDER BY published_at DESC LIMIT 50")->fetchAll();
?>

<?php require __DIR__ . '/header.php'; ?>

<section class="my-2">
  <h1 class="text-xl font-bold mb-5">Gérer les actualités</h1>

  <?php if ($error): ?>
    <div role="alert" class="alert alert-error mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div role="alert" class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Créer une actualité</h2>
        <form method="post" class="flex flex-col gap-3">
          <input type="hidden" name="action" value="create">

          <input class="input input-bordered w-full bg-base-100/70 border-base-content/15" type="text" name="title" placeholder="Titre" required>

          <textarea
            name="content"
            placeholder="Contenu (Markdown simple)"
            rows="8"
            class="textarea textarea-bordered w-full bg-base-100/70 border-base-content/15 text-base leading-relaxed"
            required
          ></textarea>

          <input type="text" name="image_path" placeholder="image_path (ex: assets/x.jpg) optionnel"
            class="input input-bordered w-full bg-base-100/70 border-base-content/15"
          >

          <label class="label cursor-pointer justify-start gap-3 py-1">
            <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-primary checkbox-sm">
            <span class="label-text text-base-content/75 font-semibold text-sm">Marquer “à la une”</span>
          </label>

          <input type="text" name="contest_month" placeholder="contest_month (YYYY-MM) optionnel"
            class="input input-bordered w-full bg-base-100/70 border-base-content/15"
          >

          <button class="btn btn-primary w-fit transition-transform duration-200 hover:scale-[1.02]" type="submit">Créer</button>
        </form>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Dernières actualités</h2>
        <?php if (!$items): ?>
          <p class="text-sm text-base-content/55">Aucune donnée.</p>
        <?php else: ?>
          <div class="flex flex-col gap-3">
            <?php foreach ($items as $n): ?>
              <div class="rounded-xl border border-base-content/10 bg-base-100/40 p-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div class="min-w-0">
                    <strong class="text-base-content"><?= htmlspecialchars($n['title']) ?></strong>
                    <div class="text-xs font-bold text-base-content/55 mt-1.5">
                      <?= $n['is_featured'] ? 'À la une' : '—' ?>
                      <?php if (!empty($n['contest_month'])): ?>
                        · Concours: <?= htmlspecialchars((string)$n['contest_month']) ?>
                      <?php endif; ?>
                      · <?= htmlspecialchars((string)$n['published_at']) ?>
                    </div>
                  </div>
                  <form method="post" onsubmit="return confirm('Supprimer cette actualité ?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                    <button class="btn btn-sm btn-outline border-error/40 text-error hover:bg-error/10" type="submit">
                      Supprimer
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
