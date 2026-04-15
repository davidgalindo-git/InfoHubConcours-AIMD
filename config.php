<?php
declare(strict_types=1);

// -----------------------------
// Configuration base de données
// -----------------------------
// Remplace ces valeurs par les tiennes.
// Conseil : importe database/install.sql dans MySQL pour créer la base et les tables.

// Sous XAMPP (Windows), prefere 127.0.0.1 si "localhost" provoque une erreur de connexion.
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'infohub'; // <-- à adapter
const DB_USER = 'root';    // <-- à adapter
const DB_PASS = '';        // <-- à adapter

// -----------------------------
// Configuration site
// -----------------------------
date_default_timezone_set('Europe/Zurich');

// Dossier public des assets
const ASSETS_DIR = 'assets';

// Pour les URL depuis index.php (liens internes).
const SITE_TITLE = 'infoHub';

// Sécurité basique session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// -----------------------------
// Admin (hackathon V1)
// -----------------------------
// Remplace par un mot de passe perso avant utilisation.
// (Astuce : mets-le à la valeur aléatoire et ne le commit pas si tu partages le projet.)
const ADMIN_PASSWORD = 'admin123';

// En local : true pour voir le détail des erreurs sur la page « Erreur serveur ». En production : false.
const APP_DEBUG = true;

/**
 * URL de base du script courant (dossier contenant le fichier PHP appelé), avec slash final.
 * À utiliser dans <base href="..."> pour que les liens relatifs fonctionnent dans un sous-dossier (ex. /ImageProject/InfoHubConcours-AIMD/).
 */
function base_url(): string
{
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '/';
  $script = str_replace('\\', '/', (string)$script);
  $dir = dirname($script);
  if ($dir === '/' || $dir === '.' || $dir === '') {
    $path = '/';
  } else {
    $path = rtrim($dir, '/') . '/';
  }
  return $scheme . '://' . $host . $path;
}

