<?php
declare(strict_types=1);

// -----------------------------
// Configuration base de données
// -----------------------------
// Remplace ces valeurs par les tiennes.
// Conseil : crée une base MySQL/MariaDB et importe database/schema.sql puis database/seed.sql.

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'inf_hub'; // <-- à adapter
const DB_USER = 'root';    // <-- à adapter
const DB_PASS = '';        // <-- à adapter

// -----------------------------
// Configuration site
// -----------------------------
date_default_timezone_set('Europe/Zurich');

// Dossier public des assets
const ASSETS_DIR = 'assets';

// Pour les URL depuis index.php (liens internes).
const SITE_TITLE = 'InfoHub';

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

