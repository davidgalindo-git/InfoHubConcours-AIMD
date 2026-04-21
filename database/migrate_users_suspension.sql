-- Suspension temporaire : date de fin côté serveur (ne dépend pas de l’horloge du navigateur).
-- Idempotent.

SET @db = DATABASE();

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE users ADD COLUMN suspended_until DATETIME NULL DEFAULT NULL AFTER status',
    'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'suspended_until'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
