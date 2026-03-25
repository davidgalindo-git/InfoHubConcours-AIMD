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

<section class="section">
  <h1 class="section-title">Gérer les pubs</h1>

  <?php if ($error): ?>
    <div class="empty-state" style="border-style:solid; border-color: rgba(255,179,179,.7);">
      <strong style="color:#ffb3b3;"><?= htmlspecialchars($error) ?></strong>
    </div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="empty-state">
      <strong style="color:#c9f7d1;"><?= htmlspecialchars($success) ?></strong>
    </div>
  <?php endif; ?>

  <div class="grid-two">
    <div>
      <div class="empty-state">
        <h2 style="margin-top:0;">Créer une pub</h2>
        <form method="post">
          <input type="hidden" name="action" value="create">

          <div class="stack" style="margin-bottom:12px;">
            <input class="btn" style="border-radius:12px; flex:1;" type="text" name="title" placeholder="Titre" required>
          </div>

          <textarea
            name="content"
            placeholder="Contenu (Markdown simple)"
            rows="8"
            style="width:100%; padding:10px 12px; border-radius:12px; border:1px solid rgba(255,255,255,.16); background: rgba(0,0,0,.12); color: var(--text);"
            required
          ></textarea>

          <div class="stack" style="margin-top:12px; margin-bottom:12px;">
            <input type="text" name="image_path" placeholder="image_path (ex: assets/x.jpg) optionnel"
              style="flex:1; min-width: 240px; padding: 10px 12px; border-radius: 12px; border:1px solid rgba(255,255,255,.16); background: rgba(0,0,0,.12); color: var(--text);"
            >
          </div>

          <div class="stack" style="margin-bottom:12px;">
            <input type="url" name="link_url" placeholder="link_url optionnel (ex: https://...)" pattern="https?://.+" 
              style="flex:1; min-width: 240px; padding: 10px 12px; border-radius: 12px; border:1px solid rgba(255,255,255,.16); background: rgba(0,0,0,.12); color: var(--text);"
            >
          </div>

          <button class="btn btn-primary" type="submit">Créer</button>
        </form>
      </div>
    </div>

    <div>
      <div class="empty-state">
        <h2 style="margin-top:0;">Dernières pubs</h2>
        <?php if (!$items): ?>
          <p style="color:var(--muted);">Aucune donnée.</p>
        <?php else: ?>
          <div class="stack" style="flex-direction:column; align-items:stretch;">
            <?php foreach ($items as $ad): ?>
              <div style="border:1px solid rgba(255,255,255,.12); border-radius: 12px; padding: 12px; background: rgba(0,0,0,.08);">
                <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                  <div>
                    <strong><?= htmlspecialchars($ad['title']) ?></strong>
                    <div style="color:var(--muted); font-weight:800; margin-top:6px;">
                      <?= htmlspecialchars((string)$ad['posted_at']) ?>
                      <?php if (!empty($ad['link_url'])): ?>
                        · lien
                      <?php endif; ?>
                    </div>
                  </div>
                  <form method="post" onsubmit="return confirm('Supprimer cette pub ?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$ad['id'] ?>">
                    <button class="btn" type="submit" style="border-color: rgba(255,179,179,.45); background: rgba(255,179,179,.08); color: #ffb3b3;">
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

