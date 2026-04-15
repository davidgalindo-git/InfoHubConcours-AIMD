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
<html lang="fr" data-theme="infohub" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <base href="<?= htmlspecialchars(base_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <title>Admin - InfoHub</title>
  <link rel="stylesheet" href="../assets/app.css">
</head>
<body class="min-h-screen flex items-center justify-center px-3 min-[400px]:px-4 overflow-x-clip">
<main class="w-full min-w-0 max-w-md animate-fade-in">
  <div class="card bg-base-200/70 border border-base-content/10 shadow-xl min-w-0">
    <div class="card-body gap-4">
      <h1 class="card-title text-2xl font-bold justify-center">Espace rédaction</h1>
      <p class="text-sm text-base-content/60 text-center">Mot de passe défini dans <code class="text-xs">config.php</code> → constante <code class="text-xs">ADMIN_PASSWORD</code>.</p>

      <?php if ($error): ?>
        <div role="alert" class="alert alert-error text-sm py-3">
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" class="flex flex-col gap-3 mt-2">
        <input
          type="password"
          name="password"
          placeholder="Mot de passe"
          required
          class="input input-bordered w-full bg-base-100/80 border-base-content/15 focus:border-primary transition-colors duration-200"
        >
        <button class="btn btn-primary transition-transform duration-200 hover:scale-[1.01]" type="submit">Se connecter</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>
