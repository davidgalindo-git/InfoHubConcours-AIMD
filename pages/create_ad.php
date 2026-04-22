<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/upload_announcement.php';

auth_require_login();
if (!auth_has_role('collaborateur', 'admin')) {
  http_response_code(403);
  $err_title = 'Accès refusé';
  $err_text = 'Seuls les collaborateurs et admins peuvent publier des pubs.';
  $err_style = 'warning';
  $err_show_home = true;
  require __DIR__ . '/error.php';
  return;
}

$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
      $uid = (int)auth_user()['id'];
      $imagePath = $up['relative_path'] ?? null;
      db()->prepare(
        'INSERT INTO ads (title, content, image_path, link_url, created_by, status) VALUES (:t,:c,:i,:l,:u,:s)'
      )->execute([
        't' => $title,
        'c' => $content,
        'i' => $imagePath,
        'l' => $link !== '' ? $link : null,
        'u' => $uid,
        's' => 'visible',
      ]);
      $newId = (int)db()->lastInsertId();
      auth_log($uid, 'create_ad', 'ad', $newId, 'Publication pub');
      $success = 'Pub publiée.';
    }
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-6 max-w-2xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Nouvelle pub</h1>
  <p class="text-sm text-base-content/60 mb-4">Pièce jointe facultative : <strong>JPG</strong>, <strong>PNG</strong> ou <strong>PDF</strong> (max 5 Mo). Glisse-dépose sur la zone ou clique pour choisir un fichier.</p>
  <?php if ($error): ?><div class="alert alert-error mb-4 text-sm"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-4 text-sm"><?= h($success) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card bg-base-200/50 border border-base-content/10 p-5 gap-3 flex flex-col">
    <input class="input input-bordered" name="title" placeholder="Titre" required>
    <textarea class="textarea textarea-bordered" rows="8" name="content" placeholder="Contenu" required></textarea>
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
    <input type="file" id="ad-file" name="attachment" class="hidden" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
    <div id="ad-dropzone" class="rounded-xl border-2 border-dashed border-base-content/25 bg-base-100/50 px-4 py-8 text-center text-sm text-base-content/65 cursor-pointer hover:border-primary/40 transition-colors">
      <span data-fn class="font-medium text-base-content/80">Glisser-déposer ou cliquer pour joindre JPG, PNG ou PDF</span>
    </div>
    <input class="input input-bordered" name="link_url" placeholder="Lien (https://...)">
    <button class="btn btn-primary" type="submit">Publier</button>
  </form>
</section>
<script>
(function () {
  var z = document.getElementById('ad-dropzone');
  var inp = document.getElementById('ad-file');
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
