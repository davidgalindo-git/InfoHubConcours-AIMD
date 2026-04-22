<?php
declare(strict_types=1);

// -------------------------------------------------------------
// CHARGEMENT MANUEL DU FICHIER .ENV
// -------------------------------------------------------------
// Cette fonction permet à getenv() de fonctionner sans Composer.

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // On ignore les commentaires
        if (str_starts_with(trim($line), '#')) continue;

        // On cherche le signe "="
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\""); // Supprime espaces et guillemets

            // On injecte dans l'environnement PHP
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Charge le fichier .env situé dans le même dossier
loadEnv(__DIR__ . '/.env');

// Fonction de secours pour récupérer une variable d'env

function get_env_var(string $key, $default = null) {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($val !== false && $val !== null) ? $val : $default;
}

// -----------------------------
// Configuration base de données
// -----------------------------
// Remplace ces valeurs par les tiennes.
// Conseil : importe database/install.sql dans MySQL pour créer la base et les tables.

// Sous XAMPP (Windows), prefere 127.0.0.1 si "localhost" provoque une erreur de connexion.
// Utilisation de define() au lieu de const pour permettre l'appel de fonction
define('DB_HOST', get_env_var('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int)get_env_var('DB_PORT', 3306));
define('DB_NAME', get_env_var('DB_NAME', 'infohub'));
define('DB_USER', get_env_var('DB_USER', 'root'));
define('DB_PASS', get_env_var('DB_PASS', ''));

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
// Admin & Debug
// -----------------------------
define('ADMIN_PASSWORD', get_env_var('ADMIN_PASSWORD', 'admin123'));

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

