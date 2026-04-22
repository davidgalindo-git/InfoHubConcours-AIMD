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

function announcement_attachment_is_image(?string $path): bool
{
  return $path !== null && $path !== '' && (bool)preg_match('/\.(jpe?g|png)$/i', $path);
}

function announcement_attachment_is_pdf(?string $path): bool
{
  return $path !== null && $path !== '' && (bool)preg_match('/\.pdf$/i', $path);
}

/** Lien mailto vers l’auteur (client mail par défaut du visiteur). */
function announcement_author_html(array $row): string
{
  $name = trim((string)($row['author_name'] ?? ''));
  $email = trim((string)($row['author_email'] ?? ''));
  if ($name === '' && $email === '') {
    return '';
  }
  $local = $email !== '' ? preg_replace('/@.+$/', '', $email) : '';
  $label = $name !== '' ? $name : ($local !== '' ? $local : 'Auteur');
  $subj = 'InfoHub — ' . (string)($row['title'] ?? 'Annonce');
  $out = '<span class="text-xs text-base-content/60 flex flex-wrap items-center gap-1">Par ';
  if ($email !== '') {
    $out .= '<a class="link link-primary font-semibold" href="mailto:' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . '?subject=' . rawurlencode($subj) . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
  } else {
    $out .= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
  $out .= '</span>';
  return $out;
}

function db_table_has_column(string $table, string $column): bool
{
  static $cache = [];
  $key = $table . "\0" . $column;
  if (array_key_exists($key, $cache)) {
    return $cache[$key];
  }
  $stmt = db()->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
  );
  $stmt->execute(['t' => $table, 'c' => $column]);
  $cache[$key] = (int)$stmt->fetchColumn() > 0;
  return $cache[$key];
}

/** Annonces créées par l’utilisateur (tous statuts, pour le profil). */
function getAnnouncementsByCreator(int $userId, int $limit = 80): array
{
  $stmt = db()->prepare(
    'SELECT id, title, category_slug, status, posted_at, created_by FROM announcements WHERE created_by = :u ORDER BY posted_at DESC LIMIT :lim'
  );
  $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

/** Pubs créées par l’utilisateur (tous statuts). */
function getAdsByCreator(int $userId, int $limit = 80): array
{
  $stmt = db()->prepare(
    'SELECT id, title, status, posted_at, created_by FROM ads WHERE created_by = :u ORDER BY posted_at DESC LIMIT :lim'
  );
  $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

/** Actualités dont l’utilisateur est l’auteur (si la colonne `created_by` existe). */
function getNewsByCreator(int $userId, int $limit = 50): array
{
  if (!db_table_has_column('news', 'created_by')) {
    return [];
  }
  $stmt = db()->prepare(
    'SELECT id, title, published_at FROM news WHERE created_by = :u ORDER BY published_at DESC LIMIT :lim'
  );
  $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

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
    "SELECT a.*, u.full_name AS author_name, u.email AS author_email
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     WHERE a.status = 'visible' AND a.is_featured = 1 ORDER BY a.posted_at DESC LIMIT :lim"
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
      "SELECT a.*, u.full_name AS author_name, u.email AS author_email
       FROM announcements a
       LEFT JOIN users u ON u.id = a.created_by
       WHERE a.status = 'visible' ORDER BY a.posted_at DESC LIMIT :lim"
    );
    $fallbackStmt->bindValue(':lim', $remaining, PDO::PARAM_INT);
    $fallbackStmt->execute();
    return array_merge($featured, $fallbackStmt->fetchAll());
  }

  $in = implode(',', array_fill(0, count($excludedIds), '?'));
  $fallbackStmt = $pdo->prepare(
    "SELECT a.*, u.full_name AS author_name, u.email AS author_email
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     WHERE a.status = 'visible' AND a.id NOT IN ($in) ORDER BY a.posted_at DESC LIMIT :lim"
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
  $stmt = db()->prepare(
    "SELECT a.*, u.full_name AS author_name, u.email AS author_email
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     WHERE a.status = 'visible' AND a.id = :id LIMIT 1"
  );
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

/** Ligne brute (tous statuts), pour édition / droits auteur. */
function getAnnouncementRowById(int $id): ?array
{
  $stmt = db()->prepare(
    'SELECT a.*, u.full_name AS author_name, u.email AS author_email
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     WHERE a.id = :id LIMIT 1'
  );
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function getAnnouncementsList(string $categorySlug, int $limit, int $offset = 0): array
{
  $params = [];
  $where = "WHERE a.status = 'visible'";
  if ($categorySlug !== 'toutes') {
    $where = "WHERE a.status = 'visible' AND a.category_slug = :cat";
    $params['cat'] = $categorySlug;
  }

  $stmt = db()->prepare(
    "SELECT a.*, u.full_name AS author_name, u.email AS author_email
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     $where ORDER BY a.posted_at DESC LIMIT :lim OFFSET :off"
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
    return (int)db()->query("SELECT COUNT(*) AS c FROM announcements WHERE status = 'visible'")->fetch()['c'];
  }
  $stmt = db()->prepare("SELECT COUNT(*) AS c FROM announcements WHERE status = 'visible' AND category_slug = :cat");
  $stmt->execute(['cat' => $categorySlug]);
  return (int)$stmt->fetch()['c'];
}

function getLatestAds(int $limit): array
{
  $stmt = db()->prepare("SELECT * FROM ads WHERE status = 'visible' ORDER BY posted_at DESC LIMIT :lim");
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function getAdsList(int $limit, int $offset = 0): array
{
  $stmt = db()->prepare("SELECT * FROM ads WHERE status = 'visible' ORDER BY posted_at DESC LIMIT :lim OFFSET :off");
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function countAds(): int
{
  return (int)db()->query("SELECT COUNT(*) AS c FROM ads WHERE status = 'visible'")->fetch()['c'];
}

function getAdById(int $id): ?array
{
  $stmt = db()->prepare("SELECT * FROM ads WHERE status = 'visible' AND id = :id LIMIT 1");
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

/** Ligne brute (tous statuts), pour édition / droits auteur. */
function getAdRowById(int $id): ?array
{
  $stmt = db()->prepare('SELECT * FROM ads WHERE id = :id LIMIT 1');
  $stmt->execute(['id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

/** Mise à jour d'une actualité existante. */
function updateNewsById(int $id, array $data): bool
{
  $stmt = db()->prepare(
    'UPDATE news
     SET title = :t, content = :c, image_path = :img, is_featured = :f, contest_month = :cm
     WHERE id = :id'
  );
  return $stmt->execute([
    't' => (string)($data['title'] ?? ''),
    'c' => (string)($data['content'] ?? ''),
    'img' => $data['image_path'] ?? null,
    'f' => !empty($data['is_featured']) ? 1 : 0,
    'cm' => $data['contest_month'] ?? null,
    'id' => $id,
  ]);
}

/** Mise à jour d'une annonce existante (+ champs optionnels si présents en DB). */
function updateAnnouncementById(int $id, array $data): bool
{
  $sets = ['title = :t', 'content = :c', 'image_path = :img', 'category_slug = :cat', 'is_featured = :f'];
  $params = [
    't' => (string)($data['title'] ?? ''),
    'c' => (string)($data['content'] ?? ''),
    'img' => $data['image_path'] ?? null,
    'cat' => (string)($data['category_slug'] ?? 'autres'),
    'f' => !empty($data['is_featured']) ? 1 : 0,
    'id' => $id,
  ];

  if (db_table_has_column('announcements', 'price')) {
    $sets[] = 'price = :price';
    $params['price'] = $data['price'] ?? null;
  }
  if (db_table_has_column('announcements', 'contact_info')) {
    $sets[] = 'contact_info = :contact';
    $params['contact'] = $data['contact_info'] ?? null;
  }

  $sql = 'UPDATE announcements SET ' . implode(', ', $sets) . ' WHERE id = :id';
  $stmt = db()->prepare($sql);
  return $stmt->execute($params);
}

/** Mise à jour d'une pub existante (+ date d'expiration si présente en DB). */
function updateAdById(int $id, array $data): bool
{
  $sets = ['title = :t', 'content = :c', 'image_path = :img', 'link_url = :l'];
  $params = [
    't' => (string)($data['title'] ?? ''),
    'c' => (string)($data['content'] ?? ''),
    'img' => $data['image_path'] ?? null,
    'l' => $data['link_url'] ?? null,
    'id' => $id,
  ];

  if (db_table_has_column('ads', 'expires_at')) {
    $sets[] = 'expires_at = :exp';
    $params['exp'] = $data['expires_at'] ?? null;
  }

  $sql = 'UPDATE ads SET ' . implode(', ', $sets) . ' WHERE id = :id';
  $stmt = db()->prepare($sql);
  return $stmt->execute($params);
}

