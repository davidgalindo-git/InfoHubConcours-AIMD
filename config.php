<?php
declare(strict_types=1);

// -----------------------------
// Configuration base de données
// -----------------------------
// Remplace ces valeurs par les tiennes.
// Conseil : importe database/install.sql dans MySQL pour créer la base et les tables.

// Sous XAMPP (Windows), prefere 127.0.0.1 si "localhost" provoque une erreur de connexion.
const DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
const DB_PORT = getenv('DB_PORT') ?: 3306;
const DB_NAME = getenv('DB_NAME') ?: 'infohub'; 
const DB_USER = getenv('DB_USER') ?: 'root';    
const DB_PASS = getenv('DB_PASS') ?: '';        

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
const ADMIN_PASSWORD = getenv('ADMIN_PASSWORD') ?: 'admin123';

// En local : true pour voir le détail des erreurs sur la page « Erreur serveur ». En production : false.
const APP_DEBUG = true;

/**
 * URL de base du script courant (dossier du fichier PHP exécuté), avec slash final.
 * Utile pour la balise base HTML depuis la page courante (y compris dans admin/).
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

/**
 * URL du dossier public où se trouve index.php (racine du projet), avec slash final.
 * Quand tu es dans admin/, base_url() finit par /admin/ : utilise ceci pour les liens vers le site (inscription, etc.).
 */
function public_base_url(): string
{
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
  $script = str_replace('\\', '/', (string)$script);
  $dir = dirname($script);
  if ($dir === '/' || $dir === '.' || $dir === '') {
    $path = '/';
  } else {
    $path = rtrim($dir, '/');
    if (str_ends_with(strtolower($path), '/admin')) {
      $path = substr($path, 0, -strlen('/admin'));
    }
    if ($path === '' || $path === '/') {
      $path = '/';
    } else {
      $path .= '/';
    }
  }
  return $scheme . '://' . $host . $path;
}

