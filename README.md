# Pharmacie - Gestion de Stock

Ce projet est une application web de gestion de stock initialement conçue pour une pharmacie. Elle permet de gérer les approvisionnements, les mouvements de stock, les utilisateurs, et d'autres fonctionnalités essentielles à la gestion quotidienne d'une pharmacie.

> **Note :** Bien que le projet soit orienté pharmacie, il est facilement adaptable à d'autres domaines nécessitant une gestion de stock (bureautique, fournitures, matériel, etc.) en modifiant quelques éléments du code et des vues. Mon besoin initial était la gestion de stock de pharmacie, mais la structure reste générique et flexible.

## Fonctionnalités principales détaillées

- **Gestion des stocks**
  - Saisie des entrées et sorties de stock (ajout/retrait de quantités)
  - Historique complet des mouvements (qui, quoi, quand, pourquoi)
  - Exportation des mouvements de stock (CSV, Excel, etc.)
  - Alertes sur les seuils bas de stock
- **Gestion des codes-barres**
  - Génération automatique d'un code-barres pour chaque fourniture (affichage sur la fiche)
  - Téléchargement du code-barres au format SVG
  - Impression en lot de codes-barres pour plusieurs fournitures (sélection et génération groupée, réservé aux administrateurs)
  - Scan de code-barres pour retrouver rapidement une fiche fourniture
- **Gestion des approvisionnements**
  - Ajout, édition et suppression de fournitures
  - Import/export de la liste des fournitures
  - Visualisation détaillée de chaque fourniture (stock, historique, alertes)
  - Gestion des commandes en cours
- **Gestion des utilisateurs**
  - Création, modification et suppression de comptes utilisateurs
  - Attribution de rôles (admin, gestionnaire, utilisateur simple, etc.)
  - Gestion des droits d'accès selon le rôle
  - Suivi des connexions et activité des utilisateurs
- **Approbations et validation**
  - Système de demandes d'approbation pour les sorties sensibles
  - Validation ou refus par un utilisateur autorisé
  - Historique des approbations et motifs
- **Sauvegarde et restauration**
  - Sauvegardes automatiques ou manuelles de la base de données
  - Restauration rapide à partir d'une sauvegarde
- **Authentification et sécurité**
  - Connexion sécurisée par login/mot de passe (hashé)
  - Déconnexion, gestion de session, protection contre les accès non autorisés
- **Notifications**
  - Notifications internes pour les mouvements, approbations, seuils critiques
  - (Possibilité d'ajouter des notifications par email ou autres canaux)

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
- `views/` : Vues PHP pour l'affichage (stock, approvisionnements, utilisateurs, visiteur)

## Installation détaillée

1. **Cloner le dépôt**
   ```bash
   git clone <url-du-repo>
   ```
2. **Préparer la configuration**
   - Copier les fichiers modèles de configuration :
     ```bash
     cp config/config.model.php config/config.php
     cp config/database.model.php config/database.php
     ```
   - Modifier `config/database.php` avec vos identifiants réels de base de données (hôte, nom, utilisateur, mot de passe).
   - Adapter les paramètres dans `config/config.php` selon vos besoins (URL, options, etc).
3. **Installer la base de données**
   - Importer le fichier `script.sql` ou une sauvegarde depuis le dossier `backup/` dans votre serveur MySQL/MariaDB.
4. **Lancer l'application**
   - Placer le dossier sur un serveur web compatible PHP (ex : Apache, Nginx).
   - Accéder à `index.php` via votre navigateur.

## Dépendances

- PHP >= 7.0
- MySQL/MariaDB
- Serveur web (Apache, Nginx, ...)

## Sauvegardes

Les sauvegardes de la base de données sont stockées dans le dossier `backup/` (exclu du dépôt).

## Auteurs

- Nejara Ylies

## Fonctionnalités à venir

- Génération automatique de rapports PDF
- Statistiques avancées sur les mouvements et consommations
- Gestion multi-dépôts (plusieurs lieux de stockage)
- Système de notifications par email/SMS
- Interface mobile responsive améliorée
- Journal d’audit détaillé (logs d’actions)
- Intégration possible avec d’autres systèmes (API)
- Système de gestion des inventaires physiques
- Gestion des alertes personnalisées (par type de produit, par utilisateur)
- Tableau de bord personnalisable
- Exportation/importation avancée (formats multiples, automatisation)
- Historique complet des modifications (traçabilité)
- Gestion des accès par groupes d’utilisateurs
- Archivage automatique des mouvements anciens
- Prise en charge du multilingue (internationalisation)
- Génération de QR codes ou codes-barres pour les fournitures
- Système de recherche avancée et filtres dynamiques
- Prise en charge des unités multiples (ex : boîtes, pièces, litres)
- Planification automatique des commandes selon seuils
- Support pour API mobile dédiée

N’hésitez pas à proposer d’autres idées ou à contribuer !