<?php
// test_mail.php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/mailer.php';

// On teste si la fonction existe avant de l'appeler pour éviter le crash
if (!function_exists('mailer_send')) {
    die("Erreur : La fonction mailer_send n'est pas définie. Vérifie lib/mailer.php");
}

$mon_email = trim((string)($_POST['to'] ?? ''));
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mon_email === '' || !filter_var($mon_email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Adresse email invalide.";
    } else {
        echo "Tentative d'envoi vers " . htmlspecialchars($mon_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "...<br>";
        $success = mailer_send(
            $mon_email,
            'Utilisateur',
            'Test InfoHub',
            '<h1>Ça marche !</h1><p>Le mailer est bien configuré.</p>'
        );

        if ($success) {
            $message = "✅ Email envoyé avec succès !";
        } else {
            $message = "❌ Échec de l'envoi. Détail: " . htmlspecialchars(mailer_last_error(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Test Mail</title>
</head>
<body>
  <h2>Tester l'envoi d'email</h2>
  <form method="post">
    <label for="to">Adresse destinataire :</label><br>
    <input id="to" name="to" type="email" required value="<?= htmlspecialchars($mon_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <button type="submit">Envoyer</button>
  </form>

  <?php if ($message !== ''): ?>
    <p><?= $message ?></p>
  <?php endif; ?>
</body>
</html>
