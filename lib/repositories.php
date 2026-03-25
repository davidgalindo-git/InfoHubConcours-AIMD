<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/markdown.php';

// Catégories annonces (slugs)
const ANNOUNCEMENT_CATEGORIES = [
  'vente' => 'À vendre',
  'don' => 'À donner',
  'covoiturage' => 'Covoiturage',
  'aide' => 'Demande d’aide',
  'petits_boulots' => 'Petits boulots',
  'autres' => 'Autres idées bienvenues',
];

function getContestOfMonth(string $yyyyMm): ?array
{
  $sql = "SELECT * FROM news WHERE contest_month = :month ORDER BY published_at DESC LIMIT 1";
  $stmt = db()->prepare($sql);
  $stmt->execute(['month' => $yyyyMm]);
  $row = $stmt->fetch();
  if ($row) {
    return $row;
  }

  // Fallback : dernier concours disponible
  $sql = "SELECT * FROM news WHERE contest_month IS NOT NULL ORDER BY published_at DESC LIMIT 1";
  $stmt = db()->query($sql);
  $row = $stmt->fetch();
  return $row ?: null;
}

/**
 * Retourne jusqu'à $limit actualités : en priorité celles marquées "à la une".
 * Si insuffisant, complète avec les plus récentes.
 */
function getLatestFeaturedNews(int $limit): array
{
  $pdo = db();

  $featuredStmt = $pdo->prepare(
    "SELECT * FROM news WHERE is_featured = 1 ORDER BY published_at DESC LIMIT :lim"
  );
  $featuredStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $featuredStmt->execute();
  $featured = $featuredStmt->fetchAll();

  if (count($featured) >= $limit) {
    return $featured;
  }

  $excludedIds = array_filter(array_map(static fn ($r) => (int)$r['id'], $featured));
  $remaining = $limit - count($featured);

  if (!$excludedIds) {
    $fallbackStmt = $pdo->prepare(
      "SELECT * FROM news ORDER BY published_at DESC LIMIT :lim"
    );
    $fallbackStmt->bindValue(':lim', $remaining, PDO::PARAM_INT);
    $fallbackStmt->execute();
    return array_merge($featured, $fallbackStmt->fetchAll());
  }

  $in = implode(',', array_fill(0, count($excludedIds), '?'));
  $fallbackStmt = $pdo->prepare(
    "SELECT * FROM news WHERE id NOT IN ($in) ORDER BY published_at DESC LIMIT :lim"
  );
  $i = 1;
  foreach ($excludedIds as $id) {
    $fallbackStmt->bindValue($i, $id, PDO::PARAM_INT);
    $i++;
  }
  $fallbackStmt->bindValue(':lim', $remaining, PDO::PARAM_INT);
  $fallbackStmt->execute();

  return array_merge($featured, $fallbackStmt->fetchAll());
}

function getNewsById(int $id): ?array
{
  $stmt = db()->prepare("SELECT * FROM news WHERE id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function getNewsList(int $limit, int $offset = 0): array
{
  $stmt = db()->prepare(
    "SELECT * FROM news ORDER BY published_at DESC LIMIT :lim OFFSET :off"
  );
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function countNews(): int
{
  return (int)db()->query("SELECT COUNT(*) AS c FROM news")->fetch()['c'];
}

function getLatestFeaturedAnnouncements(int $limit): array
{
  $pdo = db();

  $featuredStmt = $pdo->prepare(
    "SELECT * FROM announcements WHERE is_featured = 1 ORDER BY posted_at DESC LIMIT :lim"
  );
  $featuredStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $featuredStmt->execute();
  $featured = $featuredStmt->fetchAll();

  if (count($featured) >= $limit) {
    return $featured;
  }

  $excludedIds = array_filter(array_map(static fn ($r) => (int)$r['id'], $featured));
  $remaining = $limit - count($featured);

  if (!$excludedIds) {
    $fallbackStmt = $pdo->prepare(
      "SELECT * FROM announcements ORDER BY posted_at DESC LIMIT :lim"
    );
    $fallbackStmt->bindValue(':lim', $remaining, PDO::PARAM_INT);
    $fallbackStmt->execute();
    return array_merge($featured, $fallbackStmt->fetchAll());
  }

  $in = implode(',', array_fill(0, count($excludedIds), '?'));
  $fallbackStmt = $pdo->prepare(
    "SELECT * FROM announcements WHERE id NOT IN ($in) ORDER BY posted_at DESC LIMIT :lim"
  );
  $i = 1;
  foreach ($excludedIds as $id) {
    $fallbackStmt->bindValue($i, $id, PDO::PARAM_INT);
    $i++;
  }
  $fallbackStmt->bindValue(':lim', $remaining, PDO::PARAM_INT);
  $fallbackStmt->execute();

  return array_merge($featured, $fallbackStmt->fetchAll());
}

function getAnnouncementById(int $id): ?array
{
  $stmt = db()->prepare("SELECT * FROM announcements WHERE id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function getAnnouncementsList(string $categorySlug, int $limit, int $offset = 0): array
{
  $params = [];
  $where = '';
  if ($categorySlug !== 'toutes') {
    $where = 'WHERE category_slug = :cat';
    $params['cat'] = $categorySlug;
  }

  $stmt = db()->prepare(
    "SELECT * FROM announcements $where ORDER BY posted_at DESC LIMIT :lim OFFSET :off"
  );
  foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
  }
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function countAnnouncements(string $categorySlug): int
{
  if ($categorySlug === 'toutes') {
    return (int)db()->query("SELECT COUNT(*) AS c FROM announcements")->fetch()['c'];
  }
  $stmt = db()->prepare("SELECT COUNT(*) AS c FROM announcements WHERE category_slug = :cat");
  $stmt->execute(['cat' => $categorySlug]);
  return (int)$stmt->fetch()['c'];
}

function getLatestAds(int $limit): array
{
  $stmt = db()->prepare("SELECT * FROM ads ORDER BY posted_at DESC LIMIT :lim");
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function getAdsList(int $limit, int $offset = 0): array
{
  // #region agent log
  $logFile = dirname(__DIR__) . '/debug-0c7d6b.log';
  file_put_contents($logFile, json_encode([
    'sessionId' => '0c7d6b',
    'runId' => 'pre',
    'hypothesisId' => 'A',
    'location' => 'lib/repositories.php:getAdsList',
    'message' => 'Query ads list start',
    'data' => ['limit' => $limit, 'offset' => $offset],
    'timestamp' => (int)(microtime(true) * 1000),
  ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
  // #endregion

  try {
    $stmt = db()->prepare("SELECT * FROM ads ORDER BY posted_at DESC LIMIT :lim OFFSET :off");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // #region agent log
    file_put_contents($logFile, json_encode([
      'sessionId' => '0c7d6b',
      'runId' => 'pre',
      'hypothesisId' => 'A',
      'location' => 'lib/repositories.php:getAdsList',
      'message' => 'Query ads list success',
      'data' => ['rowsCount' => count($rows)],
      'timestamp' => (int)(microtime(true) * 1000),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    // #endregion

    return $rows;
  } catch (Throwable $e) {
    // #region agent log
    file_put_contents($logFile, json_encode([
      'sessionId' => '0c7d6b',
      'runId' => 'pre',
      'hypothesisId' => 'A',
      'location' => 'lib/repositories.php:getAdsList',
      'message' => 'Query ads list failed',
      'data' => ['errorMessage' => $e->getMessage(), 'errorCode' => $e->getCode()],
      'timestamp' => (int)(microtime(true) * 1000),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    // #endregion
    throw $e;
  }
}

function countAds(): int
{
  // #region agent log
  $logFile = dirname(__DIR__) . '/debug-0c7d6b.log';
  file_put_contents($logFile, json_encode([
    'sessionId' => '0c7d6b',
    'runId' => 'pre',
    'hypothesisId' => 'A',
    'location' => 'lib/repositories.php:countAds',
    'message' => 'Query ads count start',
    'data' => new stdClass(),
    'timestamp' => (int)(microtime(true) * 1000),
  ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
  // #endregion

  try {
    $row = db()->query("SELECT COUNT(*) AS c FROM ads")->fetch();
    $c = (int)($row['c'] ?? 0);

    // #region agent log
    file_put_contents($logFile, json_encode([
      'sessionId' => '0c7d6b',
      'runId' => 'pre',
      'hypothesisId' => 'A',
      'location' => 'lib/repositories.php:countAds',
      'message' => 'Query ads count success',
      'data' => ['count' => $c],
      'timestamp' => (int)(microtime(true) * 1000),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    // #endregion

    return $c;
  } catch (Throwable $e) {
    // #region agent log
    file_put_contents($logFile, json_encode([
      'sessionId' => '0c7d6b',
      'runId' => 'pre',
      'hypothesisId' => 'A',
      'location' => 'lib/repositories.php:countAds',
      'message' => 'Query ads count failed',
      'data' => ['errorMessage' => $e->getMessage(), 'errorCode' => $e->getCode()],
      'timestamp' => (int)(microtime(true) * 1000),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    // #endregion
    throw $e;
  }
}

function getAdById(int $id): ?array
{
  $stmt = db()->prepare("SELECT * FROM ads WHERE id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

