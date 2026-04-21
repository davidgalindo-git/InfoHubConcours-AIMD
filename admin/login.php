<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $pwd = (string)($_POST['password'] ?? '');
  $skipLegacy = false;

  if ($email !== '') {
    if (!auth_valid_email($email)) {
      $error = 'Adresse e-mail invalide.';
      $skipLegacy = true;
    } elseif (!auth_is_eduvaud_email($email)) {
      $error = 'Connexion admin par e-mail : adresse ' . htmlspecialchars(EDUVAUD_EMAIL_SUFFIX, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' uniquement.';
      $skipLegacy = true;
    } else {
      auth_expire_timed_suspensions_global();
      $stmt = db()->prepare('SELECT ' . auth_user_row_select_columns() . ', password_hash FROM users WHERE email = :email LIMIT 1');
      $stmt->execute(['email' => $email]);
      $row = $stmt->fetch();
      if ($row && password_verify($pwd, (string)$row['password_hash']) && (string)$row['role'] === 'admin' && (string)$row['status'] === 'active') {
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
        $_SESSION['admin_logged_in'] = true;
        auth_log((int)$row['id'], 'admin_signin', 'user', (int)$row['id'], 'Connexion admin');
        header('Location: dashboard.php');
        exit;
      }
      $error = 'Adresse e-mail ou mot de passe incorrect.';
      $skipLegacy = true;
    }
  }

  if (!$skipLegacy && hash_equals(ADMIN_PASSWORD, $pwd)) {
    $_SESSION['admin_logged_in'] = true;
    header('Location: dashboard.php');
    exit;
  }

  if ($error === null) {
    $error = 'Adresse e-mail ou mot de passe incorrect.';
  }
}
?>

<!doctype html>
<html lang="fr" data-theme="infohub" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <base href="<?= htmlspecialchars(base_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <title>Admin - InfoHub</title>
  <link rel="stylesheet" href="../assets/app.css">
  <link rel="stylesheet" href="../assets/site.css">
</head>
<body class="min-h-screen flex items-center justify-center px-3 min-[400px]:px-4 overflow-x-clip">
<main class="w-full min-w-0 max-w-lg animate-fade-in">
  <div class="card bg-base-200/70 border border-base-content/10 shadow-xl min-w-0 overflow-hidden">
    <div class="card-body gap-4 px-5 py-8 sm:px-8">
      <h1 class="card-title text-2xl font-bold justify-center">Espace rédaction</h1>
      <p class="text-sm text-base-content/60 text-center">Connexion avec un compte <strong>admin</strong> en base, ou mode démo : laisser l’email vide et utiliser <code class="text-xs">ADMIN_PASSWORD</code> depuis <code class="text-xs">config.php</code>.</p>

      <?php if ($error): ?>
        <div role="alert" class="alert alert-error text-sm py-3">
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" class="flex flex-col gap-3 mt-2">
        <input
          type="email"
          name="email"
          placeholder="Email admin"
          autocomplete="username"
          class="input input-bordered w-full min-h-11 bg-base-100/80 border-base-content/15 focus:border-primary transition-colors duration-200"
        >
        <?php
        $pwId = 'pw-admin';
        $pwName = 'password';
        $pwPlaceholder = 'Mot de passe';
        $pwAutocomplete = 'current-password';
        require __DIR__ . '/../templates/partials/password_field.php';
        ?>
        <button class="btn btn-primary transition-transform duration-200 hover:scale-[1.01]" type="submit">Se connecter</button>
      </form>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../templates/partials/password_toggle_script.php'; ?>
</body>
</html>
