# InfoHub (PHP + SQL)

## Objectif
Plateforme interne (V1) pour élèves/enseignants de la section informatique :
- Accueil (bandeau, concours du mois, 2 actualités "à la une", 2 annonces récentes, 2 pubs récentes)
- Liste + détails des **actualités**
- Liste + détails des **annonces** (filtre par catégories)
- Liste + détails des **pubs**

## Prérequis
- PHP 8+
- MySQL / MariaDB
- Un serveur qui sert le dossier `InfoHub` (Apache, PHP built-in, etc.)
- (Optionnel, pour modifier le style) Node.js 18+ : après changement des classes Tailwind dans les `.php`, exécuter `npm install` puis `npm run build:css` pour régénérer `assets/app.css`.

Le fichier `assets/app.css` est versionné pour que le site fonctionne sans Node en production. Pour le style, le projet utilise **Tailwind CSS** et **DaisyUI** (`assets/src/input.css`, `tailwind.config.js`).

## 1) Créer la base
1. Crée une base MySQL/MariaDB (ex. `inf_hub`)
2. Exécute :
   - `database/schema.sql`
   - puis `database/seed.sql`

## 2) Configurer la connexion DB
Le fichier **`config.php`** est à la **racine du projet** (à côté de `index.php`). S’il manque chez toi, copie `config.example.php` vers `config.php`, puis adapte :

- `DB_NAME`, `DB_USER`, `DB_PASS` (et éventuellement `DB_HOST`, `DB_PORT`). Sous **XAMPP**, le compte `root` a souvent un mot de passe **vide** : dans ce cas mets `const DB_PASS = '';` si la connexion échoue.

Si tu vois « Erreur serveur » :

1. Ouvre dans le navigateur (en local) : **`check_db.php`** à la racine du projet (même dossier que `index.php`). La page affiche l’erreur PDO exacte et liste les tables si la connexion réussit.
2. Mets `APP_DEBUG = true` dans `config.php` : le message détaillé s’affiche aussi sur la page d’erreur du site.

Sous **XAMPP sur Windows**, si la connexion échoue avec `localhost`, utilise `const DB_HOST = '127.0.0.1';` dans `config.php`. Vérifie que le nom de la base dans phpMyAdmin est **exactement** le même que `DB_NAME` (par défaut `infohub`).

Le fichier `database/schema.sql` crée la base **`infohub`** si elle n’existe pas (à adapter dans ce fichier et dans `config.php` si tu préfères un autre nom).

## Admin (création d’articles)
Le dossier `admin/` contient une interface minimale (login + création + suppression) pour :
- actualités (`news`)
- annonces (`announcements`)
- pubs (`ads`)

1. Remplace le mot de passe par le tien dans `config.php` : `ADMIN_PASSWORD`
2. Ouvre `admin/login.php`

## 3) Lancer le site
L’URL dépend de l’emplacement du dossier dans `htdocs` (XAMPP). Exemple si le projet est dans `htdocs/ImageProject/InfoHubConcours-AIMD/` :

- `http://localhost/ImageProject/InfoHubConcours-AIMD/index.php?route=home`

Les liens du menu utilisent une balise `<base>` calculée automatiquement pour fonctionner dans un sous-dossier.

## Images (optionnel)
Les champs `image_path` attendent un chemin (ex. `assets/mon-image.jpg`).
Si tu n’as pas d’images, le site fonctionne quand même (cartes sans image).

## Markdown
Le site supporte un Markdown "simple" (titres `#`, gras `**`, italique `*`, liens `[t](https://...)`, code inline ``code``) via `lib/markdown.php`.

## Membres du groupe
SI-CA2a :
- Anthony Simond
- Imad ElKhattabi
- Mouldi Achouri
- David Galindo
