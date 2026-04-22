<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_token_plain(int $bytes = 32): string
{
  return bin2hex(random_bytes($bytes));
}

function auth_token_hash(string $plain): string
{
  return hash('sha256', $plain);
}

function auth_store_email_verification_token(int $userId, string $plainToken, int $ttlMinutes = 1440): void
{
  $hash = auth_token_hash($plainToken);
  $expiresAt = date('Y-m-d H:i:s', time() + max(1, $ttlMinutes) * 60);
  db()->prepare('UPDATE email_verification_tokens SET consumed_at = NOW() WHERE user_id = :u AND consumed_at IS NULL')->execute(['u' => $userId]);
  db()->prepare(
    'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (:u, :h, :exp)'
  )->execute(['u' => $userId, 'h' => $hash, 'exp' => $expiresAt]);
}

function auth_consume_email_verification_token(string $plainToken): ?array
{
  $hash = auth_token_hash($plainToken);
  $stmt = db()->prepare(
    'SELECT * FROM email_verification_tokens WHERE token_hash = :h AND consumed_at IS NULL AND expires_at > NOW() LIMIT 1'
  );
  $stmt->execute(['h' => $hash]);
  $row = $stmt->fetch();
  if (!$row) {
    return null;
  }
  db()->prepare('UPDATE email_verification_tokens SET consumed_at = NOW() WHERE id = :id')->execute(['id' => (int)$row['id']]);
  return $row;
}

function auth_store_password_reset_token(int $userId, string $plainToken, int $ttlMinutes = 60): void
{
  $hash = auth_token_hash($plainToken);
  $expiresAt = date('Y-m-d H:i:s', time() + max(1, $ttlMinutes) * 60);
  db()->prepare('UPDATE password_reset_tokens SET consumed_at = NOW() WHERE user_id = :u AND consumed_at IS NULL')->execute(['u' => $userId]);
  db()->prepare(
    'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:u, :h, :exp)'
  )->execute(['u' => $userId, 'h' => $hash, 'exp' => $expiresAt]);
}

function auth_get_valid_password_reset_by_token(string $plainToken): ?array
{
  $hash = auth_token_hash($plainToken);
  $stmt = db()->prepare(
    'SELECT pr.*, u.email, u.full_name
     FROM password_reset_tokens pr
     JOIN users u ON u.id = pr.user_id
     WHERE pr.token_hash = :h AND pr.consumed_at IS NULL AND pr.expires_at > NOW() LIMIT 1'
  );
  $stmt->execute(['h' => $hash]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function auth_consume_password_reset_token(int $tokenId): void
{
  db()->prepare('UPDATE password_reset_tokens SET consumed_at = NOW() WHERE id = :id')->execute(['id' => $tokenId]);
}

function auth_users_has_email_verified_column(): bool
{
  static $cache = null;
  if ($cache !== null) {
    return $cache;
  }
  $stmt = db()->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
  );
  $stmt->execute(['t' => 'users', 'c' => 'email_verified_at']);
  $cache = (int)$stmt->fetchColumn() > 0;
  return $cache;
}
