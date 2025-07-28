# Pharmacie - Gestion de Stock

Ce projet est une application web de gestion de stock pour une pharmacie. Elle permet de gérer les approvisionnements, les mouvements de stock, les utilisateurs, et d'autres fonctionnalités essentielles à la gestion quotidienne d'une pharmacie.

## Fonctionnalités principales

- **Gestion des stocks** : Entrées, sorties, mouvements, exportation des mouvements.
- **Gestion des approvisionnements** : Ajout, édition, import/export, visualisation des approvisionnements.
- **Gestion des utilisateurs** : Ajout, édition, gestion des profils, approbations.
- **Sauvegarde et restauration** : Sauvegardes automatiques de la base de données.
- **Authentification** : Connexion, déconnexion, gestion des droits d'accès.
- **Notifications** : Système de notifications pour les mouvements et approbations.

## Structure du projet

- `api/` : Scripts PHP pour l'accès aux données (approbations, mouvements, approvisionnements, etc.)
- `assets/` : Fichiers statiques (CSS, JS, images)
- `auth/` : Gestion de l'authentification
- `backup/` : Sauvegardes SQL de la base de données
- `config/` : Fichiers de configuration (base de données, paramètres)
- `controllers/` : Contrôleurs PHP pour la logique métier
- `includes/` : Fichiers inclus (header, footer, navigation, fonctions)
- `models/` : Modèles PHP (Stock, Approvisionnement, Utilisateur)
- `style/` : Feuilles de style CSS
- `test/` : Scripts de test et de développement
- `views/` : Vues PHP pour l'affichage (stock, approvisionnements, utilisateurs, visiteur)

## Installation

1. **Cloner le dépôt**
   ```bash
   git clone <url-du-repo>
   ```
2. **Configurer la base de données**
   - Importer le fichier `script.sql` ou une sauvegarde depuis le dossier `backup/` dans votre serveur MySQL.
   - Modifier les accès à la base dans `config/database.php`.
3. **Configurer l'environnement**
   - Adapter les paramètres dans `config/config.php` selon vos besoins.
4. **Lancer l'application**
   - Placer le dossier sur un serveur web compatible PHP (ex : Apache, Nginx).
   - Accéder à `index.php` via votre navigateur.

## Dépendances

- PHP >= 7.0
- MySQL/MariaDB
- Serveur web (Apache, Nginx, ...)

## Sécurité

- Les fichiers sensibles et de configuration sont exclus du versionnement (`.gitignore`).
- Les sauvegardes et fichiers de base de données ne sont pas suivis par Git.

## Sauvegardes

Les sauvegardes de la base de données sont stockées dans le dossier `backup/` (exclu du dépôt).

## Auteurs

- [Votre nom ou équipe]

## Licence

Ce projet est sous licence privée. Contactez l'auteur pour toute utilisation ou modification.
