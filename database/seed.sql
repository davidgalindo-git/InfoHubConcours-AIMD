-- Données de test (exemples pour lancer rapidement le site)
-- IMPORTANT : adapte les chemins image_path si tu ajoutes des images.

SET NAMES utf8mb4;

INSERT INTO news (title, content, image_path, is_featured, contest_month, published_at) VALUES
('Concours du mois : mini-projet IA', '
## Objectif
Construis un mini-projet utilisant l’IA (Chatbot, classification, etc.).

## Format
- Dépot d’une démo (lien ou vidéo)
- Explication courte en Markdown

Bonne chance !
', NULL, 0, '2026-03', '2026-03-15 18:00:00'),
('À la une : atelier Python', 'Petit rappel : atelier Python samedi prochain.', NULL, 1, NULL, '2026-03-20 10:00:00'),
('Projet fil rouge : base de données', 'On met en place une base de données en PHP + SQL. **Bravo à tous** !', NULL, 1, NULL, '2026-03-18 09:30:00');

INSERT INTO announcements (title, content, image_path, category_slug, is_featured, posted_at) VALUES
('Don de câbles USB (fonctionnels)', 'J’ai plusieurs câbles USB à donner : type A -> micro-USB. À venir chercher.', NULL, 'don', 1, '2026-03-22 14:30:00'),
('À vendre : clavier mécanique', 'Clavier mécanique (layout FR), fonctionne parfaitement. Prix : à discuter.', NULL, 'vente', 1, '2026-03-21 16:45:00'),
('Demande d’aide : SQL JOIN', 'Quelqu’un peut m’expliquer les `JOIN` avec un exemple simple ?', NULL, 'aide', 0, '2026-03-19 13:00:00');

INSERT INTO ads (title, content, image_path, link_url, posted_at) VALUES
('Service de tutorat', 'Besoin d’aide pour réussir ? Contacte-nous via le lien ci-dessous.', NULL, 'https://example.com', '2026-03-23 09:00:00'),
('Atelier cybersécurité', 'Rejoignez notre session découverte : bonnes pratiques et CTF.', NULL, NULL, '2026-03-20 12:00:00');

