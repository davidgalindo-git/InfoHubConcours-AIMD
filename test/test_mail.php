<?php
// test_mail.php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/mailer.php';

// On teste si la fonction existe avant de l'appeler pour éviter le crash
if (!function_exists('mailer_send')) {
    die("Erreur : La fonction mailer_send n'est pas définie. Vérifie lib/mailer.php");
}

//$mon_email = 'mail-récepteur@gmail.com'; // À modifier

echo "Tentative d'envoi...<br>";

// Note : mailer_send prend 4 ou 5 paramètres selon le code
$success = mailer_send(
    $mon_email,      // toEmail
    'Utilisateur',   // toName
    'Test InfoHub',  // subject
    '<h1>Ça marche !</h1><p>Le mailer est bien configuré.</p>' // html
);

if ($success) {
    echo "✅ Email envoyé avec succès !";
} else {
    echo "❌ Échec de l'envoi. Vérifie tes constantes SMTP dans le .env ou config.php";
}