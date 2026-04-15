# InfoHub — prototype web (PHP + MySQL)

Site **dynamique**, **responsive**, alimenté par une base MySQL : **concours du mois**, **actualités**, **annonces**, **pubs**. Espace **admin** intégré pour créer et supprimer le contenu (sans framework imposé).

---

## Déploiement rapide (hébergement PHP + MySQL, ex. Swisscenter)

1. **Transférer** tout le dossier du projet sur l’hébergement (FTP ou git).
2. **phpMyAdmin** (ou équivalent) : onglet *Importer* → fichier **`database/install.sql`** (crée la base `infohub`, les tables et des données de démo).
3. **Configuration** : copier `config.example.php` en **`config.php`**, puis renseigner :
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (fournis par l’hébergeur),
   - `ADMIN_PASSWORD` (mot de passe de connexion à l’admin).
4. Ouvrir **`index.php?route=home`**. Rédaction : **`admin/login.php`**, puis onglets du menu ou **`admin/manage.php?type=news`** (annonces : `announcements`, pubs : `ads`).
5. **Sécurité** : en production, mettre **`APP_DEBUG`** à **`false`** dans `config.php` (les visiteurs ne doivent pas voir les détails d’erreur PHP).

Aucune étape Node ni Composer n’est nécessaire en production : les styles sont dans `assets/app.css`.

---

## Développement local (ex. XAMPP)

- Importer **`database/install.sql`** une fois.
- Le projet doit être servi par Apache **depuis le même dossier que `index.php`** (souvent `htdocs/.../InfoHubConcours-AIMD/`). Si tu travailles sur le Bureau et utilises XAMPP, **copie ou synchronise** le projet vers `htdocs` ou ouvre le dossier `htdocs` dans ton éditeur.
- Sous Windows, si la connexion MySQL échoue, utiliser souvent `DB_HOST = '127.0.0.1'` et un mot de passe `root` adapté à ton MySQL (souvent vide sur XAMPP).
- En cas d’erreur : avec **`APP_DEBUG = true`**, la page « Erreur serveur » affiche le message PDO / PHP.

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

Pour le **concours du mois**, renseigner `contest_month` au format `YYYY-MM` sur une actualité dans l’admin.

---

## Membres du groupe (SI-CA2a)

- Anthony Simond · Imad ElKhattabi · Mouldi Achouri · David Galindo
