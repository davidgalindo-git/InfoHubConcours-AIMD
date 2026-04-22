<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/markdown.php';
require_once __DIR__ . '/lib/repositories.php';

auth_expire_timed_suspensions_global();
auth_refresh_user();

function h(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$rawRoute = (string)($_GET['route'] ?? 'home');
$route = preg_replace('/[^a-z0-9_]/', '', strtolower($rawRoute));
if ($route === '') {
  $route = 'home';
}

$allowedRoutes = [
  'home',
  'news',
  'news_detail',
  'announcements',
  'announcement_detail',
  'ads',
  'ad_detail',
  'sign_in',
  'sign_up',
  'profile',
  'logout',
  'create_announcement',
  'create_ad',
  'edit_announcement',
  'edit_ad',
];

if (!in_array($route, $allowedRoutes, true)) {
  http_response_code(404);
  $err_title = 'Page introuvable';
  $err_text = "La page demandée n'existe pas.";
  $err_style = 'neutral';
  $err_show_home = true;
  $err_debug = null;
  require __DIR__ . '/pages/error.php';
  exit;
}

// Paramètre `id` : entier strict 1…999999999 (évite cast PHP bizarre, énumération)
$id = null;
if (array_key_exists('id', $_GET) && $_GET['id'] !== null && $_GET['id'] !== '') {
  $idStr = (string)$_GET['id'];
  if (!preg_match('/^[1-9]\d{0,8}$/', $idStr)) {
    http_response_code(400);
    $err_title = 'Paramètre invalide';
    $err_text = "L'identifiant dans l'URL n'est pas valide.";
    $err_style = 'neutral';
    $err_show_home = true;
    $err_debug = null;
    require __DIR__ . '/pages/error.php';
    exit;
  }
  $id = (int)$idStr;
}

try {
  ob_start();
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

    case 'sign_in':
      require __DIR__ . '/pages/sign_in.php';
      break;

    case 'sign_up':
      require __DIR__ . '/pages/sign_up.php';
      break;

    case 'profile':
      require __DIR__ . '/pages/profile.php';
      break;

    case 'logout':
      unset($_SESSION['user'], $_SESSION['admin_logged_in']);
      header('Location: index.php?route=home');
      exit;

    case 'create_announcement':
      require __DIR__ . '/pages/create_announcement.php';
      break;

    case 'create_ad':
      require __DIR__ . '/pages/create_ad.php';
      break;

    case 'edit_announcement':
      if (!$id) {
        http_response_code(400);
        echo 'Paramètre `id` manquant.';
        exit;
      }
      require __DIR__ . '/pages/edit_announcement.php';
      break;

    case 'edit_ad':
      if (!$id) {
        http_response_code(400);
        echo 'Paramètre `id` manquant.';
        exit;
      }
      require __DIR__ . '/pages/edit_ad.php';
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
  if (ob_get_level() > 0) {
    ob_end_flush();
  }
} catch (Throwable $e) {
  if (ob_get_level() > 0) {
    ob_end_clean();
  }
  http_response_code(500);
  $err_title = 'Erreur serveur';
  $err_text = 'Une erreur est survenue. Vérifie la configuration de la base de données.';
  $err_style = 'danger';
  $err_show_home = false;
  $err_debug = $e;
  require __DIR__ . '/pages/error.php';
}

