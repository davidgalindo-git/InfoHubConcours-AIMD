<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/auth_tokens.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../lib/mail_templates.php';

$error = null;
$success = null;
$warning = null;

$mailIssue = mailer_configuration_issue();
if ($mailIssue !== '') {
  $warning = 'Le service e-mail semble indisponible: ' . $mailIssue;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  if ($email === '' || !auth_valid_email($email)) {
    $error = 'Adresse e-mail invalide.';
  } else {
    $stmt = db()->prepare('SELECT id, full_name, email FROM users WHERE email = :e LIMIT 1');
    $stmt->execute(['e' => $email]);
    $user = $stmt->fetch();
    if ($user) {
      $plain = auth_token_plain();
      auth_store_password_reset_token((int)$user['id'], $plain, 60);
      $url = public_base_url() . 'index.php?route=reset_password&token=' . rawurlencode($plain);
      $tpl = mail_tpl_password_reset((string)$user['full_name'], $url);
      $sent = mailer_send((string)$user['email'], (string)$user['full_name'], $tpl['subject'], $tpl['html'], $tpl['text']);
      if (!$sent && APP_DEBUG) {
        $warning = 'E-mail non envoyé: ' . mailer_last_error();
      }
      auth_log((int)$user['id'], 'request_password_reset', 'user', (int)$user['id'], 'Demande reset');
    }
    $success = 'Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.';
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-8 max-w-xl mx-auto">
  <div class="card bg-base-200/60 border border-base-content/10">
    <div class="card-body">
      <h1 class="card-title">Mot de passe oublié</h1>
      <?php if ($error): ?><div class="alert alert-error text-sm"><?= h($error) ?></div><?php endif; ?>
      <?php if ($warning): ?><div class="alert alert-warning text-sm"><?= h($warning) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success text-sm"><?= h($success) ?></div><?php endif; ?>
      <form method="post" class="flex flex-col gap-3">
        <input class="input input-bordered" type="email" name="email" placeholder="prenom.nom@eduvaud.ch" required>
        <button class="btn btn-primary w-fit" type="submit">Envoyer le lien</button>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
