-- Optionnel : auteur des actualités (profil « Mes actualités »).
-- Idempotent. À exécuter une fois dans phpMyAdmin si la colonne n’existe pas encore.

SET @db = DATABASE();

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE news ADD COLUMN created_by INT NULL AFTER published_at',
    'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'news' AND COLUMN_NAME = 'created_by'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
