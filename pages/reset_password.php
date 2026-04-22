<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/auth_tokens.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../lib/mail_templates.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$error = null;
$success = null;
$tokenRow = $token !== '' ? auth_get_valid_password_reset_by_token($token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $p1 = (string)($_POST['password'] ?? '');
  $p2 = (string)($_POST['password_confirm'] ?? '');
  if (!$tokenRow) {
    $error = 'Lien invalide ou expiré.';
  } elseif ($p1 === '' || $p2 === '') {
    $error = 'Tous les champs sont requis.';
  } elseif ($p1 !== $p2) {
    $error = 'Les mots de passe ne correspondent pas.';
  } elseif (strlen($p1) < 8) {
    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
  } else {
    $uid = (int)$tokenRow['user_id'];
    db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')->execute([
      'h' => password_hash($p1, PASSWORD_DEFAULT),
      'id' => $uid,
    ]);
    auth_consume_password_reset_token((int)$tokenRow['id']);
    $tpl = mail_tpl_password_changed((string)$tokenRow['full_name'], date('d/m/Y H:i:s'));
    mailer_send((string)$tokenRow['email'], (string)$tokenRow['full_name'], $tpl['subject'], $tpl['html'], $tpl['text']);
    auth_log($uid, 'reset_password', 'user', $uid, 'Réinitialisation réussie');
    header('Location: index.php?route=sign_in&reset=ok');
    exit;
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-8 max-w-xl mx-auto">
  <div class="card bg-base-200/60 border border-base-content/10">
    <div class="card-body">
      <h1 class="card-title">Réinitialiser le mot de passe</h1>
      <?php if ($error): ?><div class="alert alert-error text-sm"><?= h($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success text-sm"><?= h($success) ?></div><?php endif; ?>
      <?php if (!$success): ?>
        <?php if (!$tokenRow): ?>
          <div class="alert alert-warning text-sm">Le lien est invalide ou a expiré.</div>
        <?php else: ?>
          <form method="post" class="flex flex-col gap-3">
            <input type="hidden" name="token" value="<?= h($token) ?>">
            <input class="input input-bordered" type="password" name="password" placeholder="Nouveau mot de passe" required>
            <input class="input input-bordered" type="password" name="password_confirm" placeholder="Confirmer le mot de passe" required>
            <button class="btn btn-primary w-fit" type="submit">Mettre à jour</button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <a class="btn btn-primary w-fit" href="index.php?route=sign_in">Aller à la connexion</a>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
