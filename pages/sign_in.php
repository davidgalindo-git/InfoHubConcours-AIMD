<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $password = (string)($_POST['password'] ?? '');

  if ($email === '') {
    $error = 'Saisis ton adresse e-mail.';
  } elseif (!auth_valid_email($email)) {
    $error = 'Adresse e-mail invalide.';
  } elseif (!auth_is_eduvaud_email($email)) {
    $error = 'Seuls les comptes se terminant par ' . htmlspecialchars(EDUVAUD_EMAIL_SUFFIX, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' peuvent se connecter.';
  } else {
    auth_expire_timed_suspensions_global();

    $stmt = db()->prepare('SELECT ' . auth_user_row_select_columns() . ', password_hash FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, (string)$row['password_hash'])) {
      $error = 'Adresse e-mail ou mot de passe incorrect.';
    } elseif ((string)$row['status'] !== 'active') {
      $untilRaw = isset($row['suspended_until']) ? (string)$row['suspended_until'] : '';
      if ($untilRaw !== '' && $untilRaw !== '0000-00-00 00:00:00') {
        try {
          $dt = new DateTimeImmutable($untilRaw);
          $error = 'Ce compte est suspendu jusqu’au ' . $dt->format('d/m/Y à H:i') . ' (heure du serveur).';
        } catch (Throwable $e) {
          $error = 'Ce compte est suspendu pour une durée déterminée.';
        }
      } else {
        $error = 'Ce compte est suspendu sans date de fin automatique. Contacte un administrateur.';
      }
    } else {
      $sess = [
        'id' => (int)$row['id'],
        'full_name' => (string)$row['full_name'],
        'email' => (string)$row['email'],
        'role' => (string)$row['role'],
        'status' => (string)$row['status'],
      ];
      if (auth_users_has_suspended_until_column()) {
        $sess['suspended_until'] = $row['suspended_until'] !== null && $row['suspended_until'] !== ''
          ? (string)$row['suspended_until']
          : null;
      }
      $_SESSION['user'] = $sess;
      if ((string)$row['role'] === 'admin') {
        $_SESSION['admin_logged_in'] = true;
      }
      auth_log((int)$row['id'], 'signin', 'user', (int)$row['id'], 'Connexion');
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
        <h1 class="card-title text-2xl font-bold justify-center mb-0">Sign in</h1>
        <p class="text-sm text-base-content/60 text-center">Compte <strong class="text-base-content/80">@eduvaud.ch</strong> uniquement.</p>
        <?php if ($error): ?>
          <div role="alert" class="alert alert-error text-sm py-3"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" class="flex flex-col gap-3 mt-1">
          <input class="input input-bordered w-full min-h-11 bg-base-100/80 border-base-content/15 focus:border-primary transition-colors duration-200" type="email" name="email" placeholder="prenom.nom@eduvaud.ch" required autocomplete="username" pattern=".+@eduvaud\.ch$" title="Adresse @eduvaud.ch uniquement">
          <?php
          $pwId = 'pw-signin';
          $pwName = 'password';
          $pwPlaceholder = 'Mot de passe';
          $pwAutocomplete = 'current-password';
          require __DIR__ . '/../templates/partials/password_field.php';
          ?>
          <button class="btn btn-primary transition-transform duration-200 hover:scale-[1.01]" type="submit">Se connecter</button>
          <p class="text-center text-sm text-base-content/60 pt-1">
            <a class="link link-primary link-hover" href="index.php?route=sign_up">Pas de compte ? <span class="font-medium">Sign up</span></a>
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../templates/footer.php'; ?>
