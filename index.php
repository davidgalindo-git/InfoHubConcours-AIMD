<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/markdown.php';
require_once __DIR__ . '/lib/repositories.php';

function h(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$route = $_GET['route'] ?? 'home';

// Paramètres ID (pour pages détails)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
  switch ($route) {
    case 'home':
      require __DIR__ . '/pages/home.php';
      break;

    case 'news':
      require __DIR__ . '/pages/news.php';
      break;

    case 'news_detail':
      if (!$id) {
        http_response_code(400);
        echo 'Paramètre `id` manquant.';
        exit;
      }
      require __DIR__ . '/pages/news_detail.php';
      break;

    case 'announcements':
      require __DIR__ . '/pages/announcements.php';
      break;

    case 'announcement_detail':
      if (!$id) {
        http_response_code(400);
        echo 'Paramètre `id` manquant.';
        exit;
      }
      require __DIR__ . '/pages/announcement_detail.php';
      break;

    case 'ads':
      require __DIR__ . '/pages/ads.php';
      break;

    case 'ad_detail':
      if (!$id) {
        http_response_code(400);
        echo 'Paramètre `id` manquant.';
        exit;
      }
      require __DIR__ . '/pages/ad_detail.php';
      break;

    default:
      http_response_code(404);
      $err_title = 'Page introuvable';
      $err_text = "La page demandée n'existe pas.";
      $err_style = 'neutral';
      $err_show_home = true;
      require __DIR__ . '/pages/error.php';
      break;
  }
} catch (Throwable $e) {
  http_response_code(500);
  $err_title = 'Erreur serveur';
  $err_text = 'Une erreur est survenue. Vérifie la configuration de la base de données.';
  $err_style = 'danger';
  $err_show_home = false;
  $err_debug = $e;
  require __DIR__ . '/pages/error.php';
}

