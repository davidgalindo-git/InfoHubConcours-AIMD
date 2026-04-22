<?php
// test_mail.php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/mailer.php';

// On teste si la fonction existe avant de l'appeler pour éviter le crash
if (!function_exists('mailer_send')) {
    die("Erreur : La fonction mailer_send n'est pas définie. Vérifie lib/mailer.php");
}

$mon_email = 'mouldi.achouri@eduvaud.ch';

echo "Tentative d'envoi...<br>";

$success = mailer_send(
    $mon_email,
    'Utilisateur',
    'Test InfoHub',
    '<h1>Ça marche !</h1><p>Le mailer est bien configuré.</p>'
);

if ($success) {
    echo "✅ Email envoyé avec succès !";
} else {
    echo "❌ Échec de l'envoi. Détail: " . htmlspecialchars(mailer_last_error(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
