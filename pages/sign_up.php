<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

$inviteTokenGet = isset($_GET['invite']) ? trim((string)$_GET['invite']) : '';
$inviteRow = $inviteTokenGet !== '' ? auth_fetch_valid_invite($inviteTokenGet) : null;
if ($inviteRow !== null && !auth_is_eduvaud_email((string)$inviteRow['email'])) {
  $inviteRow = null;
}

$error = null;
$genericSignupError = 'Impossible de créer le compte avec ces informations. Si tu as déjà un compte, connecte-toi.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim((string)($_POST['full_name'] ?? ''));
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $password = (string)($_POST['password'] ?? '');
  $postedInviteToken = trim((string)($_POST['invite_token'] ?? ''));
  $inviteForPost = $postedInviteToken !== '' ? auth_fetch_valid_invite($postedInviteToken) : null;

  if ($fullName === '' || $email === '' || $password === '') {
    $error = 'Tous les champs sont requis.';
  } elseif (strcasecmp($fullName, $email) === 0) {
    $error = 'Le nom d’utilisateur ne doit pas être identique à l’adresse e-mail.';
  } elseif (!auth_valid_email($email)) {
    $error = 'Adresse e-mail invalide.';
  } elseif (!auth_is_eduvaud_email($email)) {
    $error = 'L’inscription n’est possible qu’avec une adresse se terminant par ' . htmlspecialchars(EDUVAUD_EMAIL_SUFFIX, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '.';
  } elseif (strlen($password) < 8) {
    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
  } elseif ($postedInviteToken !== '' && !$inviteForPost) {
    $error = 'Invitation invalide ou expirée.';
  } elseif ($inviteForPost) {
    if (!auth_is_eduvaud_email((string)$inviteForPost['email'])) {
      $error = 'Cette invitation n’est plus valide (adresse non Eduvaud).';
    } else {
      $role = (string)$inviteForPost['role'];
      if (!in_array($role, ['user', 'collaborateur'], true)) {
        $role = 'user';
      }
      if (strtolower((string)$inviteForPost['email']) !== $email) {
        $error = 'Impossible de valider cette invitation. Vérifie le lien ou demande-en un nouveau à un administrateur.';
      } else {
        $exists = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetch()) {
          $error = $genericSignupError;
        } else {
          db()->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, status) VALUES (:n, :e, :p, :r, :s)'
          )->execute([
            'n' => $fullName,
            'e' => $email,
            'p' => password_hash($password, PASSWORD_DEFAULT),
            'r' => $role,
            's' => 'active',
          ]);
          $id = (int)db()->lastInsertId();
          db()->prepare('UPDATE user_invites SET consumed_at = NOW() WHERE id = :id')->execute(['id' => (int)$inviteForPost['id']]);
          $_SESSION['user'] = [
            'id' => $id,
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'status' => 'active',
          ];
          auth_log($id, 'signup', 'user', $id, 'Création de compte (invitation)');
          header('Location: index.php?route=home');
          exit;
        }
      }
    }
  } else {
    $role = 'user';
    $exists = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $exists->execute(['email' => $email]);
    if ($exists->fetch()) {
      $error = $genericSignupError;
    } else {
      db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, status) VALUES (:n, :e, :p, :r, :s)'
      )->execute([
        'n' => $fullName,
        'e' => $email,
        'p' => password_hash($password, PASSWORD_DEFAULT),
        'r' => $role,
        's' => 'active',
      ]);
      $id = (int)db()->lastInsertId();
      $_SESSION['user'] = [
        'id' => $id,
        'full_name' => $fullName,
        'email' => $email,
        'role' => $role,
        'status' => 'active',
      ];
      auth_log($id, 'signup', 'user', $id, 'Création de compte');
      header('Location: index.php?route=home');
      exit;
    }
  }
}
?>
<?php require __DIR__ . '/../templates/header.php'; ?>
<section class="my-8 flex justify-center px-3 min-[400px]:px-4">
  <div class="w-full max-w-lg animate-fade-in min-w-0">
    <div class="card bg-base-200/70 border border-base-content/10 shadow-xl min-w-0 overflow-hidden">
      <div class="card-body gap-4 px-5 py-8 sm:px-8">
        <h1 class="card-title text-2xl font-bold justify-center mb-0">Sign up</h1>
        <p class="text-sm text-base-content/60 text-center">
          Inscription libre ou par invitation : <strong class="text-base-content/80"><?= h(EDUVAUD_EMAIL_SUFFIX) ?></strong> uniquement.
          Invitation possible pour le rôle <strong class="text-base-content/80">collaborateur</strong> (pubs).
        </p>
        <?php if ($inviteTokenGet !== '' && !$inviteRow): ?>
          <div role="alert" class="alert alert-warning text-sm py-3">Lien d’invitation invalide ou expiré. Demande un nouveau lien à un administrateur.</div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div role="alert" class="alert alert-error text-sm py-3"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" class="flex flex-col gap-3 mt-1">
          <?php if ($inviteRow): ?>
            <input type="hidden" name="invite_token" value="<?= h((string)$inviteRow['token']) ?>">
            <label class="form-control w-full">
              <span class="label-text text-sm font-semibold">Email (invitation)</span>
              <input class="input input-bordered w-full min-h-11 bg-base-300/40 border-base-content/15" type="email" name="email" value="<?= h((string)$inviteRow['email']) ?>" readonly required>
            </label>
            <p class="text-xs text-base-content/60 text-center">Rôle attribué : <strong><?= h((string)$inviteRow['role']) ?></strong></p>
          <?php else: ?>
            <input class="input input-bordered w-full min-h-11 bg-base-100/80 border-base-content/15 focus:border-primary transition-colors duration-200" type="email" name="email" placeholder="prenom.nom@eduvaud.ch" required autocomplete="email" pattern=".+@eduvaud\.ch$" title="Adresse @eduvaud.ch uniquement">
            <p class="text-xs text-base-content/55 text-center -mt-1">Compte <strong>utilisateur</strong> par défaut. <strong>Collaborateur</strong> uniquement via invitation admin.</p>
          <?php endif; ?>
          <label class="form-control w-full">
            <span class="label-text text-sm font-semibold">Nom d’utilisateur (affiché sur le site)</span>
            <input class="input input-bordered w-full min-h-11 bg-base-100/80 border-base-content/15 focus:border-primary transition-colors duration-200" name="full_name" placeholder="ex. user (pas ton e-mail)" required autocomplete="username" inputmode="text">
          </label>
          <?php
          $pwId = 'pw-signup';
          $pwName = 'password';
          $pwPlaceholder = 'Mot de passe (8 caractères minimum)';
          $pwAutocomplete = 'new-password';
          require __DIR__ . '/../templates/partials/password_field.php';
          ?>
          <button class="btn btn-primary transition-transform duration-200 hover:scale-[1.01]" type="submit">Créer un compte</button>
          <p class="text-center text-sm text-base-content/60 pt-1">
            <a class="link link-primary link-hover" href="index.php?route=sign_in">Déjà un compte ? <span class="font-medium">Sign in</span></a>
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
