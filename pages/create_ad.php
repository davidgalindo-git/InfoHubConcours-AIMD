<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

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
    $uid = (int)auth_user()['id'];
    $imagePath = trim((string)($_POST['image_path'] ?? ''));
    db()->prepare(
      'INSERT INTO ads (title, content, image_path, link_url, created_by, status) VALUES (:t,:c,:i,:l,:u,:s)'
    )->execute([
      't' => $title,
      'c' => $content,
      'i' => $imagePath !== '' ? $imagePath : null,
      'l' => $link !== '' ? $link : null,
      'u' => $uid,
      's' => 'visible',
    ]);
    $newId = (int)db()->lastInsertId();
    auth_log($uid, 'create_ad', 'ad', $newId, 'Publication pub');
    $success = 'Pub publiée.';
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-6 max-w-2xl mx-auto">
  <h1 class="text-2xl font-bold mb-4">Nouvelle pub</h1>
  <?php if ($error): ?><div class="alert alert-error mb-4 text-sm"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-4 text-sm"><?= h($success) ?></div><?php endif; ?>
  <form method="post" class="card bg-base-200/50 border border-base-content/10 p-5 gap-3 flex flex-col">
    <input class="input input-bordered" name="title" placeholder="Titre" required>
    <textarea class="textarea textarea-bordered" rows="8" name="content" placeholder="Contenu" required></textarea>
    <input class="input input-bordered" name="image_path" placeholder="Image (optionnel: assets/x.jpg)">
    <input class="input input-bordered" name="link_url" placeholder="Lien (https://...)">
    <button class="btn btn-primary" type="submit">Publier</button>
  </form>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>

