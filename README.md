# InfoHub — prototype web (PHP + MySQL)

Site **dynamique**, **responsive**, alimenté par une base MySQL : **concours du mois**, **actualités**, **annonces**, **pubs**.
Auth intégrée avec rôles `user`, `collaborateur`, `admin` (mots de passe hashés), plus espace de modération admin.

---

## Déploiement rapide (hébergement PHP + MySQL, ex. Swisscenter)

1. **Transférer** tout le dossier du projet sur l’hébergement (FTP ou git).
2. **phpMyAdmin** (ou équivalent) : onglet *Importer* → fichier **`database/install.sql`** (crée la base `infohub`, les tables et des données de démo).
3. **Configuration** : copier `config.example.php` en **`config.php`**, puis renseigner :
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (fournis par l’hébergeur),
   - `ADMIN_PASSWORD` (mot de passe de connexion à l’admin).
4. Exécuter les migrations auth/e-mail si la base existe déjà :
   - **`database/migrate_auth_email_notifications.sql`** (tokens de vérification + reset + colonne `email_verified_at`).
5. Installer la dépendance e-mail :
   - `composer install` (PHPMailer).
6. Ouvrir **`index.php?route=home`**.
   - Comptes utilisateurs: **Sign up / Sign in** — adresses **@eduvaud.ch** uniquement ; **invitations** admin idem (`admin/invites.php`, rôle collaborateur = pubs uniquement).
   - Rédaction/admin: **`admin/login.php`** (compte admin en base, ex. démo **`admin123`** après import) ou mot de passe legacy `ADMIN_PASSWORD` sans remplir l’email.
   - Base déjà créée avant: exécuter dans phpMyAdmin (onglet **SQL**) **`database/migrate_announcements_ads_columns.sql`** (colonnes `status` / `created_by` sur annonces et pubs — corrige l’erreur « Unknown column status »), puis **`database/migrate_eduvaud_invites.sql`** (invitations + compte admin démo si besoin).
7. **Sécurité** : en production, mettre **`APP_DEBUG`** à **`false`** dans `config.php` (les visiteurs ne doivent pas voir les détails d’erreur PHP).

Configurer SMTP dans `.env` : `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.

---

## Développement local (ex. XAMPP)

- Importer **`database/install.sql`** une fois.
- Le projet doit être servi par Apache **depuis le même dossier que `index.php`** (souvent `htdocs/.../InfoHubConcours-AIMD/`). Si tu travailles sur le Bureau et utilises XAMPP, **copie ou synchronise** le projet vers `htdocs` ou ouvre le dossier `htdocs` dans ton éditeur.
- Sous Windows, si la connexion MySQL échoue, utiliser souvent `DB_HOST = '127.0.0.1'` et un mot de passe `root` adapté à ton MySQL (souvent vide sur XAMPP).
- En cas d’erreur : avec **`APP_DEBUG = true`**, la page « Erreur serveur » affiche le message PDO / PHP.
- Pour les e-mails transactionnels, ajouter les variables SMTP dans `.env` puis lancer `composer install`.

Option **styles** : pour régénérer `assets/app.css` après modification des classes Tailwind dans les `.php` : `npm install` puis `npm run build:css`.

---

## Structure (ordre de lecture)

| Élément | Rôle |
|--------|------|
| `index.php` | Entrée unique, routage `?route=…` |
| `config.php` | Base de données, session, mot de passe admin |
| `lib/db.php` | Connexion PDO |
| `lib/repositories.php` | Requêtes (concours, news, annonces, pubs) |
| `pages/`, `templates/` | Pages publiques |
| `admin/` | Connexion ; **`manage.php?type=news|announcements|ads`** pour publier |
| `database/install.sql` | Installation BDD en un fichier |

---

## Contenu & Markdown

Les champs texte acceptent un **Markdown simple** (`lib/markdown.php`) : titres, gras, italique, liens, code inline.

Les images attendent un **chemin relatif** (ex. `assets/photo.jpg`).

Pour le **concours du mois**:  
   Renseigner `contest_month` au format `YYYY-MM` sur une actualité dans l’admin.  
   Le dernier concours ajouté aux actualités est pris comme le concours du mois.  
   Il est possible de les supprimer une fois passés.

---

## Membres du groupe (SI-CA2a)

- Anthony Simond · Imad ElKhattabi · Mouldi Achouri · David Galindo
