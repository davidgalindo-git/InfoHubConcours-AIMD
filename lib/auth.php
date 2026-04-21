<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

function auth_valid_email(string $email): bool
{
  return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

/** Inscription libre + invitations : domaine Eduvaud uniquement. */
const EDUVAUD_EMAIL_SUFFIX = '@eduvaud.ch';

function auth_is_eduvaud_email(string $email): bool
{
  $e = strtolower(trim($email));
  if ($e === '' || !str_contains($e, '@')) {
    return false;
  }
  return str_ends_with($e, strtolower(EDUVAUD_EMAIL_SUFFIX));
}

/** Admin : tout. Utilisateur : uniquement ses annonces (created_by). Collaborateur : jamais. */
function auth_can_manage_announcement(?array $user, array $row): bool
{
  if (!$user || (string)($user['status'] ?? '') !== 'active') {
    return false;
  }
  $role = (string)($user['role'] ?? '');
  if ($role === 'admin') {
    return true;
  }
  if ($role !== 'user') {
    return false;
  }
  $cid = isset($row['created_by']) ? (int)$row['created_by'] : 0;
  return $cid > 0 && $cid === (int)$user['id'];
}

/** Admin : tout. Propriétaire : sa pub uniquement (created_by). */
function auth_can_manage_ad(?array $user, array $row): bool
{
  if (!$user || (string)($user['status'] ?? '') !== 'active') {
    return false;
  }
  $role = (string)($user['role'] ?? '');
  if ($role === 'admin') {
    return true;
  }
  $cid = isset($row['created_by']) ? (int)$row['created_by'] : 0;
  return $cid > 0 && $cid === (int)$user['id'];
}

/** Annonce visible pour tous ; masquée seulement pour admin ou auteur (utilisateur). */
function auth_can_view_announcement(?array $user, array $row): bool
{
  if (($row['status'] ?? 'visible') === 'visible') {
    return true;
  }
  if (!$user) {
    return false;
  }
  $role = (string)($user['role'] ?? '');
  if ($role === 'admin') {
    return true;
  }
  if ($role !== 'user') {
    return false;
  }
  $cid = isset($row['created_by']) ? (int)$row['created_by'] : 0;
  return $cid > 0 && $cid === (int)$user['id'];
}

/** Pub visible pour tous ; masquée pour admin ou auteur. */
function auth_can_view_ad(?array $user, array $row): bool
{
  if (($row['status'] ?? 'visible') === 'visible') {
    return true;
  }
  if (!$user) {
    return false;
  }
  $role = (string)($user['role'] ?? '');
  if ($role === 'admin') {
    return true;
  }
  $cid = isset($row['created_by']) ? (int)$row['created_by'] : 0;
  return $cid > 0 && $cid === (int)$user['id'];
}

/** Token hex 64 caractères (32 octets). */
function auth_generate_invite_token(): string
{
  return bin2hex(random_bytes(32));
}

/** Invitation valide (non consommée, non expirée). */
function auth_fetch_valid_invite(string $token): ?array
{
  $clean = preg_replace('/[^a-f0-9]/i', '', $token);
  $token = strtolower(is_string($clean) ? $clean : '');
  if (strlen($token) !== 64) {
    return null;
  }
  $stmt = db()->prepare(
    'SELECT * FROM user_invites WHERE token = :t AND consumed_at IS NULL AND expires_at > NOW() LIMIT 1'
  );
  $stmt->execute(['t' => $token]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function auth_user(): ?array
{
  if (empty($_SESSION['user'])) {
    return null;
  }
  return is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function auth_is_logged_in(): bool
{
  return auth_user() !== null;
}

function auth_has_role(string ...$roles): bool
{
  $user = auth_user();
  if (!$user) {
    return false;
  }
  return in_array((string)$user['role'], $roles, true);
}

function auth_require_login(string $redirectRoute = 'sign_in'): void
{
  if (!auth_is_logged_in()) {
    header('Location: index.php?route=' . rawurlencode($redirectRoute));
    exit;
  }
}

function auth_users_has_suspended_until_column(): bool
{
  static $cache = null;
  if ($cache !== null) {
    return $cache;
  }
  try {
    $stmt = db()->prepare(
      'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute(['t' => 'users', 'c' => 'suspended_until']);
    $cache = (int)$stmt->fetchColumn() > 0;
  } catch (Throwable $e) {
    $cache = false;
  }
  return $cache;
}

/** Réactive les comptes dont la suspension temporaire est expirée (heure serveur MySQL). */
function auth_expire_timed_suspensions_global(): void
{
  if (!auth_users_has_suspended_until_column()) {
    return;
  }
  db()->exec(
    "UPDATE users SET status = 'active', suspended_until = NULL
     WHERE status = 'suspended' AND suspended_until IS NOT NULL AND suspended_until <= NOW()"
  );
}

function auth_user_row_select_columns(): string
{
  $base = 'id, full_name, email, role, status';
  return auth_users_has_suspended_until_column() ? $base . ', suspended_until' : $base;
}

function auth_refresh_user(): void
{
  $user = auth_user();
  if (!$user) {
    return;
  }

  auth_expire_timed_suspensions_global();

  $stmt = db()->prepare('SELECT ' . auth_user_row_select_columns() . ' FROM users WHERE id = :id LIMIT 1');
  $stmt->execute(['id' => (int)$user['id']]);
  $fresh = $stmt->fetch();
  if (!$fresh || (string)$fresh['status'] !== 'active') {
    unset($_SESSION['user'], $_SESSION['admin_logged_in']);
    return;
  }
  $_SESSION['user'] = $fresh;
}

function auth_log(?int $actorUserId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
{
  db()->prepare(
    'INSERT INTO activity_logs (actor_user_id, action, target_type, target_id, details) VALUES (:u, :a, :t, :tid, :d)'
  )->execute([
    'u' => $actorUserId,
    'a' => $action,
    't' => $targetType,
    'tid' => $targetId,
    'd' => $details,
  ]);
}

/**
 * Détails d’audit pour une suspension (parsable : stats admin).
 * @param string|null $untilDatetime Valeur MySQL DATETIME telle que renvoyée par DATE_ADD(NOW(), …).
 */
function auth_suspend_activity_details(bool $permanent, ?int $planMinutes, ?string $untilDatetime): string
{
  if ($permanent) {
    return 'mode=permanent';
  }
  $m = max(0, (int)($planMinutes ?? 0));
  $u = trim((string)($untilDatetime ?? ''));
  $uNorm = str_replace(' ', 'T', $u);
  return 'mode=timed&plan_minutes=' . $m . '&until=' . $uNorm;
}

/**
 * Compte les suspensions enregistrées et la somme des durées planifiées (minutes) pour les suspensions temporaires.
 *
 * @return array{suspension_count:int, planned_minutes:int}
 */
function auth_user_suspension_stats(int $userId): array
{
  $stmt = db()->prepare(
    "SELECT details FROM activity_logs WHERE action = 'suspend_user' AND target_type = 'user' AND target_id = :id"
  );
  $stmt->execute(['id' => $userId]);
  $count = 0;
  $minutes = 0;
  foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $d) {
    $count++;
    $parts = [];
    foreach (explode('&', (string)$d) as $p) {
      if ($p === '') {
        continue;
      }
      $kv = explode('=', $p, 2);
      if (count($kv) === 2) {
        $parts[$kv[0]] = $kv[1];
      }
    }
    if (($parts['mode'] ?? '') === 'timed' && isset($parts['plan_minutes'])) {
      $minutes += max(0, (int)$parts['plan_minutes']);
    }
  }

  return ['suspension_count' => $count, 'planned_minutes' => $minutes];
}

