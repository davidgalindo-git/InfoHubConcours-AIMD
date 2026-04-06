<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/auth.php';

require_admin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $title = trim((string)($_POST['title'] ?? ''));
  $content = (string)($_POST['content'] ?? '');
  $image_path = trim((string)($_POST['image_path'] ?? ''));
  $link_url = trim((string)($_POST['link_url'] ?? ''));
  $link_url = $link_url !== '' ? $link_url : null;

  if ($title === '' || $content === '') {
    $error = "Titre et contenu sont requis.";
  } else {
    db()->prepare(
      "INSERT INTO ads (title, content, image_path, link_url) VALUES (:t, :c, :img, :l)"
    )->execute([
      't' => $title,
      'c' => $content,
      'img' => $image_path !== '' ? $image_path : null,
      'l' => $link_url,
    ]);
    $success = "Pub créée.";
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $deleteId = (int)($_POST['id'] ?? 0);
  if ($deleteId > 0) {
    db()->prepare("DELETE FROM ads WHERE id = :id")->execute(['id' => $deleteId]);
    $success = "Pub supprimée.";
  }
}

$items = db()->query("SELECT * FROM ads ORDER BY posted_at DESC LIMIT 50")->fetchAll();
?>

<?php require __DIR__ . '/header.php'; ?>

<section class="my-2">
  <h1 class="text-xl font-bold mb-5">Gérer les pubs</h1>

  <?php if ($error): ?>
    <div role="alert" class="alert alert-error mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div role="alert" class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Créer une pub</h2>
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

          <input type="url" name="link_url" placeholder="link_url optionnel (ex: https://...)" pattern="https?://.+"
            class="input input-bordered w-full bg-base-100/70 border-base-content/15"
          >

          <button class="btn btn-primary w-fit transition-transform duration-200 hover:scale-[1.02]" type="submit">Créer</button>
        </form>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Dernières pubs</h2>
        <?php if (!$items): ?>
          <p class="text-sm text-base-content/55">Aucune donnée.</p>
        <?php else: ?>
          <div class="flex flex-col gap-3">
            <?php foreach ($items as $ad): ?>
              <div class="rounded-xl border border-base-content/10 bg-base-100/40 p-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div class="min-w-0">
                    <strong class="text-base-content"><?= htmlspecialchars($ad['title']) ?></strong>
                    <div class="text-xs font-bold text-base-content/55 mt-1.5">
                      <?= htmlspecialchars((string)$ad['posted_at']) ?>
                      <?php if (!empty($ad['link_url'])): ?>
                        · lien
                      <?php endif; ?>
                    </div>
                  </div>
                  <form method="post" onsubmit="return confirm('Supprimer cette pub ?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$ad['id'] ?>">
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
