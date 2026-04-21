<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/repositories.php';

auth_require_login();

if (!isset($id)) {
  http_response_code(400);
  require __DIR__ . '/not_found.php';
  exit;
}

$row = getAdRowById((int)$id);
if (!$row) {
  http_response_code(404);
  require __DIR__ . '/not_found.php';
  exit;
}

if (!auth_can_manage_ad(auth_user(), $row)) {
  http_response_code(403);
  $err_title = 'Accès refusé';
  $err_text = 'Tu ne peux modifier que tes propres pubs (ou tout en tant qu’admin).';
  $err_style = 'warning';
  $err_show_home = true;
  require __DIR__ . '/error.php';
  return;
}

$error = null;
$success = null;
$uid = (int)auth_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? 'update');
  if ($action === 'delete') {
    db()->prepare('DELETE FROM ads WHERE id = :id')->execute(['id' => (int)$id]);
    auth_log($uid, 'delete_ad', 'ad', (int)$id, null);
    header('Location: index.php?route=ads');
    exit;
  }

  $title = trim((string)($_POST['title'] ?? ''));
  $content = trim((string)($_POST['content'] ?? ''));
  $link = trim((string)($_POST['link_url'] ?? ''));
  if ($title === '' || $content === '') {
    $error = 'Titre et contenu requis.';
  } elseif ($link !== '' && !preg_match('/^https?:\/\/.+/i', $link)) {
    $error = 'Lien invalide.';
  } else {
    $imagePath = trim((string)($_POST['image_path'] ?? ''));
    db()->prepare(
      'UPDATE ads SET title=:t, content=:c, image_path=:i, link_url=:l WHERE id=:id'
    )->execute([
      't' => $title,
      'c' => $content,
      'i' => $imagePath !== '' ? $imagePath : null,
      'l' => $link !== '' ? $link : null,
      'id' => (int)$id,
    ]);
    auth_log($uid, 'update_ad', 'ad', (int)$id, null);
    $success = 'Pub enregistrée.';
    $row = getAdRowById((int)$id) ?? $row;
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-6 max-w-2xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Modifier la pub</h1>
  <?php if ($error): ?><div class="alert alert-error mb-4 text-sm"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-4 text-sm"><?= h($success) ?></div><?php endif; ?>
  <form method="post" class="card bg-base-200/50 border border-base-content/10 p-5 gap-3 flex flex-col">
    <input type="hidden" name="action" value="update">
    <input class="input input-bordered" name="title" placeholder="Titre" required value="<?= h((string)$row['title']) ?>">
    <textarea class="textarea textarea-bordered" rows="8" name="content" placeholder="Contenu" required><?= h((string)$row['content']) ?></textarea>
    <input class="input input-bordered" name="image_path" placeholder="Image (optionnel)" value="<?= h((string)($row['image_path'] ?? '')) ?>">
    <input class="input input-bordered" name="link_url" placeholder="Lien (https://...)" value="<?= h((string)($row['link_url'] ?? '')) ?>">
    <div class="flex flex-wrap gap-2">
      <button class="btn btn-primary" type="submit">Enregistrer</button>
      <a class="btn btn-ghost border border-base-content/15" href="index.php?route=ad_detail&id=<?= (int)$id ?>">Voir la pub</a>
    </div>
  </form>
  <form method="post" class="mt-6" onsubmit="return confirm('Supprimer définitivement cette pub ?');">
    <input type="hidden" name="action" value="delete">
    <button class="btn btn-outline border-error/40 text-error" type="submit">Supprimer la pub</button>
  </form>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
