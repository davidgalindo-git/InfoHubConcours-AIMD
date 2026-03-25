<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/agent_logger.php';

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
    agent_log(
      'B',
      'lib/db.php:db',
      'PDO connection established',
      ['DB_HOST' => DB_HOST, 'DB_PORT' => DB_PORT, 'DB_NAME' => DB_NAME],
      'pre'
    );
    // #endregion
  } catch (Throwable $e) {
    // #region agent log
    agent_log(
      'B',
      'lib/db.php:db',
      'PDO connection failed',
      [
        'errorMessage' => $e->getMessage(),
        'errorCode' => $e->getCode(),
        'DB_HOST' => DB_HOST,
        'DB_PORT' => DB_PORT,
        'DB_NAME' => DB_NAME,
      ],
      'pre'
    );
    // #endregion
    throw $e;
  }

  return $pdo;
}

