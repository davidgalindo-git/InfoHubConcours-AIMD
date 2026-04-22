<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/repositories.php';
require_once __DIR__ . '/../lib/upload_announcement.php';

auth_require_login();
if (!auth_has_role('user', 'admin')) {
  http_response_code(403);
  $err_title = 'Accès refusé';
  $err_text = 'Ce rôle ne peut pas publier des annonces.';
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
  $category = (string)($_POST['category_slug'] ?? 'autres');

  if ($title === '' || $content === '') {
    $error = 'Titre et contenu requis.';
  } elseif (!array_key_exists($category, ANNOUNCEMENT_CATEGORIES)) {
    $error = 'Catégorie invalide.';
  } else {
    $up = announcement_process_upload($_FILES['attachment'] ?? null);
    if (!$up['ok']) {
      $error = (string)($up['error'] ?? 'Fichier refusé.');
    } else {
      $imagePath = $up['relative_path'] ?? null;
      $uid = (int)auth_user()['id'];
      db()->prepare(
        'INSERT INTO announcements (title, content, image_path, category_slug, created_by, status, is_featured) VALUES (:t,:c,:i,:cat,:u,:s,0)'
      )->execute([
        't' => $title,
        'c' => $content,
        'i' => $imagePath,
        'cat' => $category,
        'u' => $uid,
        's' => 'visible',
      ]);
      $newId = (int)db()->lastInsertId();
      auth_log($uid, 'create_announcement', 'announcement', $newId, 'Publication annonce');
      $success = 'Annonce publiée.';
    }
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-6 max-w-2xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Nouvelle annonce</h1>
  <p class="text-sm text-base-content/60 mb-4">Pièce jointe facultative : <strong>JPG</strong>, <strong>PNG</strong> ou <strong>PDF</strong> (max 5 Mo). Glisse-dépose sur la zone ou clique pour choisir un fichier.</p>
  <?php if ($error): ?><div class="alert alert-error mb-4 text-sm"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-4 text-sm"><?= h($success) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card bg-base-200/50 border border-base-content/10 p-5 gap-3 flex flex-col">
    <input class="input input-bordered" name="title" placeholder="Titre" required>
    <textarea class="textarea textarea-bordered" rows="8" name="content" placeholder="Contenu" required></textarea>
    <select class="select select-bordered" name="category_slug" required>
      <?php foreach (ANNOUNCEMENT_CATEGORIES as $slug => $label): ?>
        <option value="<?= h($slug) ?>"><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
    <input type="file" id="ann-file" name="attachment" class="hidden" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
    <div id="ann-dropzone" class="rounded-xl border-2 border-dashed border-base-content/25 bg-base-100/50 px-4 py-8 text-center text-sm text-base-content/65 cursor-pointer hover:border-primary/40 transition-colors">
      <span data-fn class="font-medium text-base-content/80">Glisser-déposer ou cliquer pour joindre JPG, PNG ou PDF</span>
    </div>
    <button class="btn btn-primary" type="submit">Publier</button>
  </form>
</section>
<script>
(function () {
  var z = document.getElementById('ann-dropzone');
  var inp = document.getElementById('ann-file');
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
