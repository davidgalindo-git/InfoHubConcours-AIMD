<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/auth_tokens.php';

$token = trim((string)($_GET['token'] ?? ''));
$error = null;
$success = null;

if ($token === '') {
  $error = 'Lien de vérification invalide.';
} elseif (!auth_users_has_email_verified_column()) {
  $success = 'La vérification e-mail n’est pas activée sur cette base.';
} else {
  $row = auth_consume_email_verification_token($token);
  if (!$row) {
    $error = 'Ce lien est expiré ou invalide.';
  } else {
    $userId = (int)$row['user_id'];
    db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = :id')->execute(['id' => $userId]);
    auth_log($userId, 'verify_email', 'user', $userId, 'Vérification e-mail');
    $success = 'Adresse e-mail vérifiée. Tu peux maintenant te connecter.';
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-8 max-w-xl mx-auto">
  <div class="card bg-base-200/60 border border-base-content/10">
    <div class="card-body">
      <h1 class="card-title">Confirmation de compte</h1>
      <?php if ($error): ?><div class="alert alert-error text-sm"><?= h($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success text-sm"><?= h($success) ?></div><?php endif; ?>
      <a class="btn btn-primary w-fit" href="index.php?route=sign_in">Aller à la connexion</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
