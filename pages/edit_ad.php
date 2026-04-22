<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/repositories.php';
require_once __DIR__ . '/../lib/upload_announcement.php';

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
$savedPath = (string)($row['image_path'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? 'update');
  if ($action === 'delete') {
    announcement_delete_uploaded_file($savedPath !== '' ? $savedPath : null);
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
    $up = announcement_process_upload($_FILES['attachment'] ?? null, 'ads');
    if (!$up['ok']) {
      $error = (string)($up['error'] ?? 'Fichier refusé.');
    } else {
      $newPath = $savedPath !== '' ? $savedPath : null;
      if (!empty($_POST['remove_attachment'])) {
        $newPath = null;
      }
      if (!empty($up['relative_path'])) {
        $newPath = $up['relative_path'];
      }

      db()->prepare(
        'UPDATE ads SET title=:t, content=:c, image_path=:i, link_url=:l WHERE id=:id'
      )->execute([
        't' => $title,
        'c' => $content,
        'i' => $newPath,
        'l' => $link !== '' ? $link : null,
        'id' => (int)$id,
      ]);

      if ($savedPath !== '' && $savedPath !== (string)$newPath) {
        announcement_delete_uploaded_file($savedPath);
      }

      auth_log($uid, 'update_ad', 'ad', (int)$id, null);
      $success = 'Pub enregistrée.';
      $row = getAdRowById((int)$id) ?? $row;
      $savedPath = (string)($row['image_path'] ?? '');
    }
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-6 max-w-2xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Modifier la pub</h1>
  <p class="text-sm text-base-content/60 mb-4">Pièce jointe facultative : <strong>JPG</strong>, <strong>PNG</strong> ou <strong>PDF</strong> (max 5 Mo). Glisse-dépose sur la zone ou clique pour choisir un fichier.</p>
  <?php if ($error): ?><div class="alert alert-error mb-4 text-sm"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-4 text-sm"><?= h($success) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card bg-base-200/50 border border-base-content/10 p-5 gap-3 flex flex-col">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
    <input class="input input-bordered" name="title" placeholder="Titre" required value="<?= h((string)$row['title']) ?>">
    <textarea class="textarea textarea-bordered" rows="8" name="content" placeholder="Contenu" required><?= h((string)$row['content']) ?></textarea>
    <?php if ($savedPath !== ''): ?>
      <label class="label cursor-pointer justify-start gap-2 py-1">
        <input type="checkbox" name="remove_attachment" value="1" class="checkbox checkbox-sm checkbox-primary">
        <span class="label-text text-sm">Retirer la pièce jointe actuelle</span>
      </label>
    <?php endif; ?>
    <input type="file" id="ad-file-edit" name="attachment" class="hidden" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
    <div id="ad-dropzone-edit" class="rounded-xl border-2 border-dashed border-base-content/25 bg-base-100/50 px-4 py-8 text-center text-sm text-base-content/65 cursor-pointer hover:border-primary/40 transition-colors">
      <span data-fn class="font-medium text-base-content/80"><?= $savedPath !== '' ? h(basename($savedPath)) : 'Glisser-déposer ou cliquer pour joindre (optionnel)' ?></span>
    </div>
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
<script>
(function () {
  var z = document.getElementById('ad-dropzone-edit');
  var inp = document.getElementById('ad-file-edit');
  if (!z || !inp) return;
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
    z.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); });
  });
  z.addEventListener('dragover', function () { z.classList.add('border-primary', 'bg-primary/5'); });
  z.addEventListener('dragleave', function () { z.classList.remove('border-primary', 'bg-primary/5'); });
  z.addEventListener('drop', function (e) {
    z.classList.remove('border-primary', 'bg-primary/5');
    var f = e.dataTransfer.files[0];
    if (!f) return;
    inp.files = e.dataTransfer.files;
    var el = z.querySelector('[data-fn]');
    if (el) el.textContent = f.name;
  });
  z.addEventListener('click', function () { inp.click(); });
})();
</script>
<?php require __DIR__ . '/../templates/footer.php'; ?>
