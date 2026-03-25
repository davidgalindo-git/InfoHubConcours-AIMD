# InfoHub (PHP + SQL)

## Objectif
Plateforme interne (V1) pour élèves/enseignants de la section informatique :
- Accueil (bandeau, concours du mois, 2 actualités “à la une”, 2 annonces récentes, 2 pubs récentes)
- Liste + détails des **actualités**
- Liste + détails des **annonces** (filtre par catégories)
- Liste + détails des **pubs**

## Prérequis
- PHP 8+
- MySQL / MariaDB
- Un serveur qui sert le dossier `InfoHub` (Apache, PHP built-in, etc.)

## 1) Créer la base
1. Crée une base MySQL/MariaDB (ex. `inf_hub`)
2. Exécute :
   - `database/schema.sql`
   - puis `database/seed.sql`

## 2) Configurer la connexion DB
Ouvre `config.php` et adapte :
- `DB_NAME`, `DB_USER`, `DB_PASS` (et éventuellement `DB_HOST`, `DB_PORT`)

## Admin (création d’articles)
Le dossier `admin/` contient une interface minimale (login + création + suppression) pour :
- actualités (`news`)
- annonces (`announcements`)
- pubs (`ads`)

1. Remplace le mot de passe par le tien dans `config.php` : `ADMIN_PASSWORD`
2. Ouvre `admin/login.php`

## 3) Lancer le site
Ouvre :
- `http://localhost/…/InfoHub/index.php?route=home`

## Images (optionnel)
Les champs `image_path` attendent un chemin (ex. `assets/mon-image.jpg`).
Si tu n’as pas d’images, le site fonctionne quand même (cartes sans image).

## Markdown
Le site supporte un Markdown “simple” (titres `#`, gras `**`, italique `*`, liens `[t](https://...)`, code inline `` `code` ``) via `lib/markdown.php`.

