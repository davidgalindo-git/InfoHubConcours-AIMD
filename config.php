<?php
declare(strict_types=1);

/**
 * Charge un fichier .env simple (KEY=VALUE) dans l'environnement PHP.
 */
function load_env_file(string $path): void
{
  if (!is_file($path) || !is_readable($path)) {
    return;
  }
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!is_array($lines)) {
    return;
  }
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '' || str_starts_with($line, '#')) {
      continue;
    }
    $pos = strpos($line, '=');
    if ($pos === false) {
      continue;
    }
    $key = trim(substr($line, 0, $pos));
    $val = trim(substr($line, $pos + 1));
    if ($key === '') {
      continue;
    }
    if (
      (str_starts_with($val, '"') && str_ends_with($val, '"'))
      || (str_starts_with($val, "'") && str_ends_with($val, "'"))
    ) {
      $val = substr($val, 1, -1);
    }
    putenv($key . '=' . $val);
    $_ENV[$key] = $val;
    $_SERVER[$key] = $val;
  }
}

load_env_file(__DIR__ . '/.env');

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

// -----------------------------
// E-mails transactionnels
// -----------------------------
const SMTP_HOST = getenv('SMTP_HOST') ?: '';
const SMTP_PORT = (int)(getenv('SMTP_PORT') ?: 587);
const SMTP_USERNAME = getenv('SMTP_USERNAME') ?: '';
const SMTP_PASSWORD = getenv('SMTP_PASSWORD') ?: (getenv('MAIL_SERVICE_API_KEY') ?: '');
const SMTP_ENCRYPTION = getenv('SMTP_ENCRYPTION') ?: 'tls';
const MAIL_FROM_ADDRESS = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@infohub.local';
const MAIL_FROM_NAME = getenv('MAIL_FROM_NAME') ?: SITE_TITLE;

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

