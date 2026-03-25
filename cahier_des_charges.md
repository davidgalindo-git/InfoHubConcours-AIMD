# Cahier des Charges : InfoHub

## 1. Objectif du projet
L'association **CPNV Info** souhaite créer une plateforme web interne destinée aux élèves et aux enseignants de la section informatique. L'objectif de ce hackathon est de concevoir et développer une première version fonctionnelle du site, capable d'évoluer par la suite.

## 2. Concept Général
Le site s'articule autour de trois axes principaux :

### Page d'accueil
La page d'accueil doit afficher de manière synthétique :
* Un **bandeau** avec image et logo.
* Le **concours du mois**.
* Les deux **actualités** les plus récentes (ou "à la une").
* Les deux **annonces** les plus récentes (ou "à la une").
* Les deux **publicités** les plus récentes en bas de page.
* Un **menu** de navigation vers les sections Actualités, Annonces et Pubs.

### Module d'actualités
* Publication d'articles contenant **une image** et du **texte** (format simple ou Markdown).
* Thèmes : événements, projets, conférences, etc.
* Le "concours du mois" peut être traité comme une actualité spéciale.

### Système d'annonces
Les annonces sont classées par catégories (À vendre, À donner, Covoiturage, Demande d'aide, Petits boulots).
Chaque annonce comprend :
* Une image et une description.
* Champs : catégorie, prix (facultatif), mode de contact, date de publication et de péremption (1 semaine par défaut).
* Statut : visible ou cachée.
* **Fonctionnalité clé :** Espace de discussion et questions sous chaque annonce.

## 3. Gestion des Utilisateurs
* **Utilisateurs (élèves/profs) :** Peuvent proposer des annonces, des articles et commenter.
* **Administrateurs :** Peuvent valider/refuser les publications, masquer le contenu et publier directement.
* **Flux :** Toutes les publications doivent être validées par un admin avant d'être visibles.

## 4. Aspects Techniques
* **Hébergement :** Swisscenter (infohub.mycpnv.ch).
* **Base de données :** MySQL.
* **Langage :** PHP (avec ou sans framework), design responsive.
* **Authentification :** Système Login/Register avec mot de passe hashé (transition vers eduvaud.ch prévue à terme).