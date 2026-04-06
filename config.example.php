<?php
declare(strict_types=1);

// Copie ce fichier en `config.php` à la racine du projet, puis adapte les valeurs.

// -----------------------------
// Configuration base de données
// -----------------------------
// XAMPP Windows : souvent 127.0.0.1 au lieu de localhost si la connexion échoue.
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'infohub';
const DB_USER = 'root';
// XAMPP : souvent '' (vide) pour l’utilisateur root. À toi de vérifier dans phpMyAdmin.
const DB_PASS = '';

// -----------------------------
// Configuration site
// -----------------------------
date_default_timezone_set('Europe/Zurich');

const ASSETS_DIR = 'assets';
const SITE_TITLE = 'InfoHub';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// -----------------------------
// Admin (hackathon V1)
// -----------------------------
const ADMIN_PASSWORD = 'admin123';

const APP_DEBUG = true;

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
