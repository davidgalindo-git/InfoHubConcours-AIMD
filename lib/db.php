<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Connexion PDO (singleton).
 */
function db(): PDO
{
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }

  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_PORT,
    DB_NAME
  );

  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // #region agent log
    $logFile = dirname(__DIR__) . '/debug-0c7d6b.log';
    file_put_contents($logFile, json_encode([
      'sessionId' => '0c7d6b',
      'runId' => 'pre',
      'hypothesisId' => 'B',
      'location' => 'lib/db.php:db',
      'message' => 'PDO connection established',
      'data' => ['DB_HOST' => DB_HOST, 'DB_PORT' => DB_PORT, 'DB_NAME' => DB_NAME],
      'timestamp' => (int)(microtime(true) * 1000),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    // #endregion
  } catch (Throwable $e) {
    // #region agent log
    $logFile = dirname(__DIR__) . '/debug-0c7d6b.log';
    file_put_contents($logFile, json_encode([
      'sessionId' => '0c7d6b',
      'runId' => 'pre',
      'hypothesisId' => 'B',
      'location' => 'lib/db.php:db',
      'message' => 'PDO connection failed',
      'data' => [
        'errorMessage' => $e->getMessage(),
        'errorCode' => $e->getCode(),
        'DB_HOST' => DB_HOST,
        'DB_PORT' => DB_PORT,
        'DB_NAME' => DB_NAME,
      ],
      'timestamp' => (int)(microtime(true) * 1000),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    // #endregion
    throw $e;
  }

  return $pdo;
}

