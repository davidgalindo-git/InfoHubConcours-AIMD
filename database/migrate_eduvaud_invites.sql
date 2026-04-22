-- À exécuter dans phpMyAdmin si la base existait déjà (tables users/annonces déjà là).
USE infohub;

CREATE TABLE IF NOT EXISTS user_invites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  role ENUM('user','collaborateur') NOT NULL DEFAULT 'user',
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  consumed_at DATETIME NULL,
  KEY idx_invites_email (email),
  KEY idx_invites_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compte admin démo (mot de passe: admin123) — à changer en production.
INSERT INTO users (full_name, email, password_hash, role, status)
SELECT 'Administrateur', 'admin@eduvaud.ch', '$2y$10$K5DM5l6GVIGUfFNzYkPaCenkwdad78uibikC2WRh3MhTKPS/FTCki', 'admin', 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@eduvaud.ch');
