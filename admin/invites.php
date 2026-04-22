<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../lib/mail_templates.php';
require_admin();

$error = null;
$success = null;
$newInviteLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $adminId = !empty($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;

  if ($action === 'create') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $role = (string)($_POST['role'] ?? 'user');
    if (!auth_valid_email($email)) {
      $error = 'Adresse e-mail invalide.';
    } elseif (!auth_is_eduvaud_email($email)) {
      $error = 'Les invitations ne sont possibles que pour une adresse se terminant par ' . htmlspecialchars(EDUVAUD_EMAIL_SUFFIX, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '.';
    } elseif (!in_array($role, ['user', 'collaborateur'], true)) {
      $error = 'Rôle invalide.';
    } else {
      $exists = db()->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
      $exists->execute(['e' => $email]);
      if ($exists->fetch()) {
        $error = 'Un compte existe déjà avec cet email.';
      } else {
        $dupInv = db()->prepare(
          'SELECT id FROM user_invites WHERE email = :e AND consumed_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $dupInv->execute(['e' => $email]);
        if ($dupInv->fetch()) {
          $error = 'Une invitation est déjà active pour cet email.';
        } else {
          $token = auth_generate_invite_token();
          $expires = (new DateTimeImmutable('+14 days'))->format('Y-m-d H:i:s');
          db()->prepare(
            'INSERT INTO user_invites (email, token, role, created_by, expires_at) VALUES (:e, :t, :r, :a, :ex)'
          )->execute([
            'e' => $email,
            't' => $token,
            'r' => $role,
            'a' => $adminId,
            'ex' => $expires,
          ]);
          auth_log($adminId, 'create_invite', 'user_invite', (int)db()->lastInsertId(), $email);
          $newInviteLink = rtrim(public_base_url(), '/') . '/index.php?route=sign_up&invite=' . rawurlencode($token);
          $tpl = mail_tpl_invite_signup($email, $newInviteLink, $role);
          $sent = mailer_send($email, $email, $tpl['subject'], $tpl['html'], $tpl['text']);
          if ($sent) {
            $success = 'Invitation créée et e-mail envoyé. Le lien reste disponible ci-dessous.';
          } else {
            $reason = mailer_last_error();
            $error = 'Invitation créée, mais e-mail non envoyé.';
            if ($reason !== '') {
              $error .= ' Détail: ' . $reason;
            }
            $success = null;
          }
        }
      }
    }
  } elseif ($action === 'revoke') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      db()->prepare('DELETE FROM user_invites WHERE id = :id AND consumed_at IS NULL')->execute(['id' => $id]);
      auth_log($adminId, 'revoke_invite', 'user_invite', $id, null);
      $success = 'Invitation supprimée.';
    }
  }
}

$pending = db()->query(
  "SELECT i.*, u.full_name AS creator_name
   FROM user_invites i
   LEFT JOIN users u ON u.id = i.created_by
   WHERE i.consumed_at IS NULL AND i.expires_at > NOW()
   ORDER BY i.created_at DESC"
)->fetchAll();

require __DIR__ . '/header.php';
?>
<section class="my-2">
  <h1 class="text-2xl font-bold mb-2">Invitations</h1>
  <p class="text-sm text-base-content/60 mb-6">Crée un lien d’inscription uniquement pour une adresse <strong>@eduvaud.ch</strong>. Rôle collaborateur = accès pubs uniquement (lecture ailleurs). Le lien pointe vers la page publique <code class="text-xs opacity-90">index.php?route=sign_up</code> à la racine du projet (pas le dossier <code class="text-xs opacity-90">admin</code>). Les e-mails saisis sont visibles dans cette page et en base : même en local, évite les vraies adresses perso si tu fais des tests.</p>

  <?php if ($error): ?><div class="alert alert-error mb-4 text-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($newInviteLink): ?>
    <div class="alert alert-info mb-6 text-sm break-all">
      <span class="font-semibold">Lien :</span>
      <a class="link link-primary" href="<?= htmlspecialchars($newInviteLink) ?>"><?= htmlspecialchars($newInviteLink) ?></a>
    </div>
  <?php endif; ?>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Nouvelle invitation</h2>
        <form method="post" class="flex flex-col gap-3">
          <input type="hidden" name="action" value="create">
          <input class="input input-bordered" type="email" name="email" placeholder="prenom.nom@eduvaud.ch" required pattern=".+@eduvaud\.ch$" title="@eduvaud.ch uniquement">
          <select class="select select-bordered" name="role">
            <option value="user">Utilisateur</option>
            <option value="collaborateur">Collaborateur (publie des pubs)</option>
          </select>
          <button class="btn btn-primary w-fit" type="submit">Générer le lien</button>
        </form>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h2 class="card-title text-lg">Invitations en attente</h2>
        <?php if (!$pending): ?>
          <p class="text-sm text-base-content/60">Aucune invitation active.</p>
        <?php else: ?>
          <ul class="flex flex-col gap-2 text-sm">
            <?php foreach ($pending as $row): ?>
              <li class="rounded-xl border border-base-content/10 p-3 flex flex-wrap justify-between gap-2 items-center">
                <div class="min-w-0 flex-1">
                  <strong><?= htmlspecialchars((string)$row['email']) ?></strong>
                  <span class="text-base-content/55"> · <?= htmlspecialchars((string)$row['role']) ?></span>
                  <div class="text-xs text-base-content/50">Expire <?= htmlspecialchars((string)$row['expires_at']) ?></div>
                  <?php
                    $pendingLink = rtrim(public_base_url(), '/') . '/index.php?route=sign_up&invite=' . rawurlencode((string)$row['token']);
                  ?>
                  <div class="mt-2 text-xs break-all">
                    <span class="text-base-content/55">Lien :</span>
                    <a class="link link-primary" href="<?= htmlspecialchars($pendingLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($pendingLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
                  </div>
                </div>
                <form method="post" onsubmit="return confirm('Supprimer cette invitation ?');">
                  <input type="hidden" name="action" value="revoke">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button class="btn btn-xs btn-outline border-error/40 text-error" type="submit">Révoquer</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
