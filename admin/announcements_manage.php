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
  $category_slug = (string)($_POST['category_slug'] ?? 'autres');
  if (!array_key_exists($category_slug, ANNOUNCEMENT_CATEGORIES)) {
    $category_slug = 'autres';
  }
  $is_featured = !empty($_POST['is_featured']) ? 1 : 0;

  if ($title === '' || $content === '') {
    $error = "Titre et contenu sont requis.";
  } else {
    db()->prepare(
      "INSERT INTO announcements (title, content, image_path, category_slug, is_featured) VALUES (:t, :c, :img, :cat, :f)"
    )->execute([
      't' => $title,
      'c' => $content,
      'img' => $image_path !== '' ? $image_path : null,
      'cat' => $category_slug,
      'f' => $is_featured,
    ]);
    $success = "Annonce créée.";
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $deleteId = (int)($_POST['id'] ?? 0);
  if ($deleteId > 0) {
    db()->prepare("DELETE FROM announcements WHERE id = :id")->execute(['id' => $deleteId]);
    $success = "Annonce supprimée.";
  }
}

$items = db()->query("SELECT * FROM announcements ORDER BY posted_at DESC LIMIT 50")->fetchAll();
?>

<?php require __DIR__ . '/header.php'; ?>

<section class="my-2">
  <h1 class="text-xl font-bold mb-5">Gérer les annonces</h1>

  <?php if ($error): ?>
    <div role="alert" class="alert alert-error mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div role="alert" class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Créer une annonce</h2>
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

          <label class="form-control w-full">
            <span class="label-text text-sm font-bold text-base-content/65">Catégorie</span>
            <select name="category_slug" class="select select-bordered w-full bg-base-100/70 border-base-content/15">
              <?php foreach (ANNOUNCEMENT_CATEGORIES as $slug => $label): ?>
                <option value="<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="label cursor-pointer justify-start gap-3 py-1">
            <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-primary checkbox-sm">
            <span class="label-text text-base-content/75 font-semibold text-sm">Marquer “à la une”</span>
          </label>

          <button class="btn btn-primary w-fit transition-transform duration-200 hover:scale-[1.02]" type="submit">Créer</button>
        </form>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Dernières annonces</h2>
        <?php if (!$items): ?>
          <p class="text-sm text-base-content/55">Aucune donnée.</p>
        <?php else: ?>
          <div class="flex flex-col gap-3">
            <?php foreach ($items as $a): ?>
              <div class="rounded-xl border border-base-content/10 bg-base-100/40 p-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div class="min-w-0">
                    <strong class="text-base-content"><?= htmlspecialchars($a['title']) ?></strong>
                    <div class="text-xs font-bold text-base-content/55 mt-1.5">
                      <?= htmlspecialchars(ANNOUNCEMENT_CATEGORIES[$a['category_slug']] ?? (string)$a['category_slug']) ?>
                      <?php if (!empty($a['is_featured'])): ?>
                        · À la une
                      <?php endif; ?>
                      · <?= htmlspecialchars((string)$a['posted_at']) ?>
                    </div>
                  </div>
                  <form method="post" onsubmit="return confirm('Supprimer cette annonce ?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
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
