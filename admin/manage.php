<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/auth.php';

require_admin();

$type = (string)($_GET['type'] ?? 'news');
if (!in_array($type, ['news', 'announcements', 'ads'], true)) {
  $type = 'news';
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'create') {
    $title = trim((string)($_POST['title'] ?? ''));
    $content = (string)($_POST['content'] ?? '');
    $image_path = trim((string)($_POST['image_path'] ?? ''));

    if ($title === '' || $content === '') {
      $error = 'Titre et contenu sont requis.';
    } elseif ($type === 'news') {
      $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
      $contest_month = trim((string)($_POST['contest_month'] ?? ''));
      $contest_month = $contest_month !== '' ? $contest_month : null;
      db()->prepare(
        'INSERT INTO news (title, content, image_path, is_featured, contest_month) VALUES (:t, :c, :img, :f, :cm)'
      )->execute([
        't' => $title,
        'c' => $content,
        'img' => $image_path !== '' ? $image_path : null,
        'f' => $is_featured,
        'cm' => $contest_month,
      ]);
      $success = 'Actualité créée.';
    } elseif ($type === 'announcements') {
      $category_slug = (string)($_POST['category_slug'] ?? 'autres');
      if (!array_key_exists($category_slug, ANNOUNCEMENT_CATEGORIES)) {
        $category_slug = 'autres';
      }
      $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
      db()->prepare(
        'INSERT INTO announcements (title, content, image_path, category_slug, is_featured) VALUES (:t, :c, :img, :cat, :f)'
      )->execute([
        't' => $title,
        'c' => $content,
        'img' => $image_path !== '' ? $image_path : null,
        'cat' => $category_slug,
        'f' => $is_featured,
      ]);
      $success = 'Annonce créée.';
    } else {
      $link_url = trim((string)($_POST['link_url'] ?? ''));
      $link_url = $link_url !== '' ? $link_url : null;
      db()->prepare(
        'INSERT INTO ads (title, content, image_path, link_url) VALUES (:t, :c, :img, :l)'
      )->execute([
        't' => $title,
        'c' => $content,
        'img' => $image_path !== '' ? $image_path : null,
        'l' => $link_url,
      ]);
      $success = 'Pub créée.';
    }
  } elseif ($action === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
      if ($type === 'news') {
        db()->prepare('DELETE FROM news WHERE id = :id')->execute(['id' => $deleteId]);
        $success = 'Actualité supprimée.';
      } elseif ($type === 'announcements') {
        db()->prepare('DELETE FROM announcements WHERE id = :id')->execute(['id' => $deleteId]);
        $success = 'Annonce supprimée.';
      } else {
        db()->prepare('DELETE FROM ads WHERE id = :id')->execute(['id' => $deleteId]);
        $success = 'Pub supprimée.';
      }
    }
  }
}

$items = match ($type) {
  'news' => db()->query('SELECT * FROM news ORDER BY published_at DESC LIMIT 50')->fetchAll(),
  'announcements' => db()->query('SELECT * FROM announcements ORDER BY posted_at DESC LIMIT 50')->fetchAll(),
  'ads' => db()->query('SELECT * FROM ads ORDER BY posted_at DESC LIMIT 50')->fetchAll(),
};

$labels = [
  'news' => [
    'page' => 'Gérer les actualités',
    'create' => 'Créer une actualité',
    'list' => 'Dernières actualités',
    'del_confirm' => 'Supprimer cette actualité ?',
    'empty' => 'Aucune actualité.',
  ],
  'announcements' => [
    'page' => 'Gérer les annonces',
    'create' => 'Créer une annonce',
    'list' => 'Dernières annonces',
    'del_confirm' => 'Supprimer cette annonce ?',
    'empty' => 'Aucune annonce.',
  ],
  'ads' => [
    'page' => 'Gérer les pubs',
    'create' => 'Créer une pub',
    'list' => 'Dernières pubs',
    'del_confirm' => 'Supprimer cette pub ?',
    'empty' => 'Aucune pub.',
  ],
];
$L = $labels[$type];

require __DIR__ . '/header.php';
?>

<section class="my-2">
  <h1 class="text-xl font-bold mb-5"><?= htmlspecialchars($L['page']) ?></h1>

  <?php if ($error): ?>
    <div role="alert" class="alert alert-error mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div role="alert" class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="grid gap-6 lg:grid-cols-2 min-w-0">
    <div class="card bg-base-200/50 border border-base-content/10 min-w-0">
      <div class="card-body gap-3 min-w-0">
        <h2 class="card-title text-lg"><?= htmlspecialchars($L['create']) ?></h2>
        <form method="post" action="manage.php?type=<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="flex flex-col gap-3">
          <input type="hidden" name="action" value="create">
          <input class="input input-bordered w-full bg-base-100/70 border-base-content/15" type="text" name="title" placeholder="Titre" required>

          <textarea name="content" placeholder="Contenu (Markdown simple)" rows="8" class="textarea textarea-bordered w-full bg-base-100/70 border-base-content/15 text-base leading-relaxed" required></textarea>

          <input type="text" name="image_path" placeholder="image_path (ex: assets/x.jpg) optionnel"
            class="input input-bordered w-full bg-base-100/70 border-base-content/15">

          <?php if ($type === 'news'): ?>
            <label class="label cursor-pointer justify-start gap-3 py-1">
              <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-primary checkbox-sm">
              <span class="label-text text-base-content/75 font-semibold text-sm">Marquer « à la une »</span>
            </label>
            <input type="text" name="contest_month" placeholder="concours : YYYY-MM (optionnel)"
              class="input input-bordered w-full bg-base-100/70 border-base-content/15">
          <?php elseif ($type === 'announcements'): ?>
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
              <span class="label-text text-base-content/75 font-semibold text-sm">Marquer « à la une »</span>
            </label>
          <?php else: ?>
            <input type="url" name="link_url" placeholder="Lien optionnel (https://...)" pattern="https?://.+"
              class="input input-bordered w-full bg-base-100/70 border-base-content/15">
          <?php endif; ?>

          <button class="btn btn-primary w-fit transition-transform duration-200 hover:scale-[1.02]" type="submit">Créer</button>
        </form>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10 min-w-0">
      <div class="card-body gap-3 min-w-0">
        <h2 class="card-title text-lg"><?= htmlspecialchars($L['list']) ?></h2>
        <?php if (!$items): ?>
          <p class="text-sm text-base-content/55"><?= htmlspecialchars($L['empty']) ?></p>
        <?php else: ?>
          <div class="flex flex-col gap-3">
            <?php foreach ($items as $row): ?>
              <div class="rounded-xl border border-base-content/10 bg-base-100/40 p-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div class="min-w-0">
                    <strong class="text-base-content"><?= htmlspecialchars((string)$row['title']) ?></strong>
                    <div class="text-xs font-bold text-base-content/55 mt-1.5">
                      <?php if ($type === 'news'): ?>
                        <?= !empty($row['is_featured']) ? 'À la une' : '—' ?>
                        <?php if (!empty($row['contest_month'])): ?>
                          · Concours: <?= htmlspecialchars((string)$row['contest_month']) ?>
                        <?php endif; ?>
                        · <?= htmlspecialchars((string)$row['published_at']) ?>
                      <?php elseif ($type === 'announcements'): ?>
                        <?= htmlspecialchars(ANNOUNCEMENT_CATEGORIES[$row['category_slug']] ?? (string)$row['category_slug']) ?>
                        <?php if (!empty($row['is_featured'])): ?> · À la une<?php endif; ?>
                        · <?= htmlspecialchars((string)$row['posted_at']) ?>
                      <?php else: ?>
                        <?= htmlspecialchars((string)$row['posted_at']) ?>
                        <?php if (!empty($row['link_url'])): ?> · lien<?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <form method="post" action="manage.php?type=<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" onsubmit="return confirm('<?= htmlspecialchars($L['del_confirm'], ENT_QUOTES) ?>');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-sm btn-outline border-error/40 text-error hover:bg-error/10" type="submit">Supprimer</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card bg-base-200/40 border border-dashed border-primary/25 min-w-0">
      <div class="card-body">
        <h3 class="card-title text-base">Aide rapide</h3>
        <p class="text-sm text-base-content/65">
          Markdown simple dans les champs texte :
          <ul>
              <li>Titres : # Titre1, ## Titre2</li>
              <li>Gras : **<strong>texte gras</strong>**</li>
              <li>Liens : [titre_lien](url)</li>
          </ul>
        </p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
