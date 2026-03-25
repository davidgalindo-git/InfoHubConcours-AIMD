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

// #region agent log
$logFile = __DIR__ . '/debug-0c7d6b.log';
file_put_contents($logFile, json_encode([
  'sessionId' => '0c7d6b',
  'runId' => 'pre',
  'hypothesisId' => 'C',
  'location' => 'index.php:route',
  'message' => 'Route dispatch start',
  'data' => ['route' => $route, 'id' => $id],
  'timestamp' => (int)(microtime(true) * 1000),
], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
// #endregion

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
      require __DIR__ . '/pages/not_found.php';
      break;
  }
} catch (Throwable $e) {
  // #region agent log
  file_put_contents($logFile, json_encode([
    'sessionId' => '0c7d6b',
    'runId' => 'pre',
    'hypothesisId' => 'A',
    'location' => 'index.php:catch',
    'message' => 'Unhandled exception during request',
    'data' => [
      'route' => $route,
      'id' => $id,
      'errorMessage' => $e->getMessage(),
      'errorCode' => $e->getCode(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
    ],
    'timestamp' => (int)(microtime(true) * 1000),
  ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
  // #endregion
  http_response_code(500);
  require __DIR__ . '/pages/server_error.php';
}

