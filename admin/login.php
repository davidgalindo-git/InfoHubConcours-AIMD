<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pwd = (string)($_POST['password'] ?? '');
  if (hash_equals(ADMIN_PASSWORD, $pwd)) {
    $_SESSION['admin_logged_in'] = true;
    header('Location: dashboard.php');
    exit;
  }
  $error = 'Mot de passe incorrect.';
}
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - InfoHub</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
<main class="container" style="margin-top: 24px;">
  <div class="empty-state">
    <h1>Connexion admin</h1>
    <p>Mot de passe : modifie `ADMIN_PASSWORD` dans `config.php`.</p>

    <?php if ($error): ?>
      <p style="color:#ffb3b3; font-weight:800;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" class="stack" style="margin-top: 14px;">
      <input
        type="password"
        name="password"
        placeholder="Mot de passe"
        required
        style="flex:1; min-width: 240px; padding: 10px 12px; border-radius: 12px; border:1px solid rgba(255,255,255,.16); background: rgba(0,0,0,.12); color: var(--text);"
      >
      <button class="btn btn-primary" type="submit">Se connecter</button>
    </form>
  </div>
</main>
</body>
</html>

