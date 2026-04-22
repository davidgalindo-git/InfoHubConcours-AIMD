<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/repositories.php';

header('Content-Type: application/json; charset=utf-8');

auth_refresh_user();
$user = auth_user();
if (!$user || (string)($user['status'] ?? '') !== 'active') {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Authentification requise.']);
  exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['PUT', 'PATCH', 'POST'], true)) {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée.']);
  exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode((string)$raw, true);
if (!is_array($payload)) {
  $payload = $_POST;
}

$type = (string)($payload['type'] ?? '');
$id = (int)($payload['id'] ?? 0);
$data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

if (!in_array($type, ['news', 'announcements', 'ads'], true) || $id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'type/id invalides.']);
  exit;
}

$ok = false;
if ($type === 'news') {
  if (!auth_has_role('admin')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Droits insuffisants pour éditer une actualité.']);
    exit;
  }
  $ok = updateNewsById($id, [
    'title' => (string)($data['title'] ?? ''),
    'content' => (string)($data['content'] ?? ''),
    'image_path' => trim((string)($data['image_path'] ?? '')) ?: null,
    'is_featured' => !empty($data['is_featured']),
    'contest_month' => trim((string)($data['contest_month'] ?? '')) ?: null,
  ]);
  auth_log((int)$user['id'], 'update_news_api', 'news', $id, $method);
} elseif ($type === 'announcements') {
  $row = getAnnouncementRowById($id);
  if (!$row || !auth_can_manage_announcement($user, $row)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Droits insuffisants pour éditer cette annonce.']);
    exit;
  }
  $category = (string)($data['category_slug'] ?? 'autres');
  if (!array_key_exists($category, ANNOUNCEMENT_CATEGORIES)) {
    $category = 'autres';
  }
  $ok = updateAnnouncementById($id, [
    'title' => (string)($data['title'] ?? ''),
    'content' => (string)($data['content'] ?? ''),
    'image_path' => trim((string)($data['image_path'] ?? '')) ?: null,
    'category_slug' => $category,
    'is_featured' => !empty($data['is_featured']),
    'price' => trim((string)($data['price'] ?? '')) ?: null,
    'contact_info' => trim((string)($data['contact_info'] ?? '')) ?: null,
  ]);
  auth_log((int)$user['id'], 'update_announcement_api', 'announcement', $id, $method);
} else {
  $row = getAdRowById($id);
  if (!$row || !auth_can_manage_ad($user, $row)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Droits insuffisants pour éditer cette pub.']);
    exit;
  }
  $link = trim((string)($data['link_url'] ?? ''));
  if ($link !== '' && !preg_match('/^https?:\/\/.+/i', $link)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'URL invalide.']);
    exit;
  }
  $expiresAt = trim((string)($data['expires_at'] ?? ''));
  $ok = updateAdById($id, [
    'title' => (string)($data['title'] ?? ''),
    'content' => (string)($data['content'] ?? ''),
    'image_path' => trim((string)($data['image_path'] ?? '')) ?: null,
    'link_url' => $link !== '' ? $link : null,
    'expires_at' => $expiresAt !== '' ? str_replace('T', ' ', $expiresAt) . ':00' : null,
  ]);
  auth_log((int)$user['id'], 'update_ad_api', 'ad', $id, $method);
}

echo json_encode([
  'ok' => (bool)$ok,
  'type' => $type,
  'id' => $id,
]);
