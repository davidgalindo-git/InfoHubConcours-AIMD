-- Corrige « Unknown column 'status' » si les tables announcements / ads
-- ont été créées avant l’ajout des colonnes (CREATE TABLE IF NOT EXISTS ne les met pas à jour).
-- À exécuter dans phpMyAdmin (onglet SQL), une seule fois. Idempotent.

USE infohub;

SET @db = DATABASE();

-- announcements.created_by
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE announcements ADD COLUMN created_by INT NULL',
    'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'created_by'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- announcements.status
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE announcements ADD COLUMN status ENUM(\'visible\',\'hidden\') NOT NULL DEFAULT \'visible\'',
    'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ads.created_by
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE ads ADD COLUMN created_by INT NULL',
    'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'ads' AND COLUMN_NAME = 'created_by'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ads.status
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE ads ADD COLUMN status ENUM(\'visible\',\'hidden\') NOT NULL DEFAULT \'visible\'',
    'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'ads' AND COLUMN_NAME = 'status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
