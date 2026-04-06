<?php
declare(strict_types=1);

/**
 * Diagnostic MySQL (local uniquement). Supprime ce fichier avant mise en production publique.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Diagnostic MySQL — InfoHub</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
    .ok { color: #0a0; }
    .err { color: #c00; }
    code { background: #eee; padding: 2px 6px; border-radius: 4px; }
    ul { line-height: 1.6; }
  </style>
</head>
<body>
  <h1>Diagnostic connexion MySQL</h1>
  <p>Paramètres lus dans <code>config.php</code> :</p>
  <ul>
    <li><strong>DB_HOST</strong> = <code><?= htmlspecialchars(DB_HOST) ?></code></li>
    <li><strong>DB_PORT</strong> = <code><?= (int)DB_PORT ?></code></li>
    <li><strong>DB_NAME</strong> = <code><?= htmlspecialchars(DB_NAME) ?></code></li>
    <li><strong>DB_USER</strong> = <code><?= htmlspecialchars(DB_USER) ?></code></li>
    <li><strong>DB_PASS</strong> = <?= DB_PASS === '' ? '<em>(vide)</em>' : '<code>***</code>' ?></li>
  </ul>

  <?php if (!extension_loaded('pdo_mysql')): ?>
    <p class="err"><strong>Problème :</strong> l’extension PHP <code>pdo_mysql</code> n’est pas activée.</p>
    <p>Ouvre <code>php.ini</code> (XAMPP : Panneau de config → PHP → php.ini), décommente ou ajoute :</p>
    <pre>extension=pdo_mysql</pre>
    <p>Redémarre <strong>Apache</strong>, puis recharge cette page.</p>
  <?php else: ?>
    <p class="ok">Extension <code>pdo_mysql</code> : OK.</p>
    <?php
    try {
      $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
      );
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      ]);
      echo '<p class="ok"><strong>Connexion à la base <code>' . htmlspecialchars(DB_NAME) . '</code> : OK.</strong></p>';

      $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
      if ($tables === []) {
        echo '<p class="err">La base existe mais <strong>aucune table</strong>. Réimporte <code>database/schema.sql</code> (base sélectionnée : <code>' . htmlspecialchars(DB_NAME) . '</code>).</p>';
      } else {
        echo '<p>Tables trouvées (' . count($tables) . ') :</p><ul>';
        foreach ($tables as $t) {
          echo '<li><code>' . htmlspecialchars((string)$t) . '</code></li>';
        }
        echo '</ul>';
        echo '<p>Si le site affiche encore « Erreur serveur », regarde le détail avec <code>APP_DEBUG = true</code> dans <code>config.php</code>, ou compare le nom de la base ci‑dessus avec celle où tu as importé les SQL dans phpMyAdmin.</p>';
      }
    } catch (Throwable $e) {
      echo '<p class="err"><strong>Message MySQL / PDO :</strong><br>' . htmlspecialchars($e->getMessage()) . '</p>';
      echo '<h2>Causes fréquentes (XAMPP / Windows)</h2>';
      echo '<ol>';
      echo '<li><strong>Nom de la base incorrect :</strong> Dans phpMyAdmin, la base doit s’appeler exactement <code>' . htmlspecialchars(DB_NAME) . '</code> (sinon change <code>DB_NAME</code> dans <code>config.php</code> pour qu’il soit identique au nom dans la colonne de gauche).</li>';
      echo '<li><strong>localhost :</strong> Essaye dans <code>config.php</code> : <code>const DB_HOST = \'127.0.0.1\';</code> au lieu de <code>localhost</code>.</li>';
      echo '<li><strong>Mot de passe :</strong> Pour <code>root</code>, XAMPP utilise souvent un mot de passe vide (<code>DB_PASS = \'\'</code>).</li>';
      echo '<li><strong>Import :</strong> Tu dois exécuter <code>schema.sql</code> puis <code>seed.sql</code> <em>dans</em> cette base (sélectionne la base à gauche, puis Importer).</li>';
      echo '</ol>';
    }
    ?>
  <?php endif; ?>

  <p><a href="index.php?route=home">Retour au site</a></p>
</body>
</html>
