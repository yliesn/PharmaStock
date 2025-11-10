-- Script de création de la base de données pour une application de gestion de stock
-- Compatible avec MariaDB

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS pharmacie;

-- Utilisation de la base de données
USE pharmacie;

-- Suppression des tables si elles existent déjà (pour réinitialisation)
-- L'ordre est important à cause des clés étrangères
DROP TABLE IF EXISTS MOUVEMENT_STOCK;
DROP TABLE IF EXISTS APPROBATION;
DROP TABLE IF EXISTS FOURNITURE;
DROP TABLE IF EXISTS UTILISATEUR;

-- Création de la table UTILISATEUR
CREATE TABLE UTILISATEUR (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    date_derniere_connexion DATE,
    actif BOOLEAN DEFAULT TRUE,
    CONSTRAINT uk_utilisateur_login UNIQUE (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création de la table FOURNITURE
CREATE TABLE FOURNITURE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL,
    designation VARCHAR(255) NOT NULL,
    description TEXT,
    quantite_stock INT NOT NULL DEFAULT 0,
    seuil_alerte INT,
    commande_en_cours BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT uk_fourniture_reference UNIQUE (reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE APPROBATION (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_id INT NOT NULL,
    quantite INT NOT NULL,
    motif TEXT, -- le motif de la demande de sortie
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- date de création de la demande
    date_validation DATETIME NULL, -- date de validation ou de refus
    statut ENUM('EN_ATTENTE', 'APPROUVEE', 'REFUSEE') DEFAULT 'EN_ATTENTE',
    traite_par INT, -- l'utilisateur qui a traité la demande
    FOREIGN KEY (supply_id) REFERENCES FOURNITURE(id),
    FOREIGN KEY (traite_par) REFERENCES UTILISATEUR(id)
);

-- Création de la table MOUVEMENT_STOCK
CREATE TABLE MOUVEMENT_STOCK (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_mouvement DATE NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    type ENUM('ENTREE', 'SORTIE') NOT NULL,
    quantite INT NOT NULL,
    motif VARCHAR(255),
    id_fourniture INT NOT NULL,
    id_utilisateur INT NOT NULL,
    CONSTRAINT fk_mouvement_fourniture FOREIGN KEY (id_fourniture) REFERENCES FOURNITURE(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mouvement_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES UTILISATEUR(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE INVENTAIRE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_inventaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    utilisateur_id INT NOT NULL,
    commentaire TEXT,
    FOREIGN KEY (utilisateur_id) REFERENCES UTILISATEUR(id)
);

CREATE TABLE INVENTAIRE_LIGNE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    fourniture_id INT NOT NULL,
    quantite_theorique INT NOT NULL,
    quantite_physique INT NOT NULL,
    ecart INT AS (quantite_physique - quantite_theorique) STORED,
    commentaire TEXT,
    FOREIGN KEY (inventaire_id) REFERENCES INVENTAIRE(id),
    FOREIGN KEY (fourniture_id) REFERENCES FOURNITURE(id)
);

-- Création d'un index sur les colonnes fréquemment utilisées pour les recherches
CREATE INDEX idx_fourniture_reference ON FOURNITURE(reference);
CREATE INDEX idx_mouvement_date ON MOUVEMENT_STOCK(date_mouvement);
CREATE INDEX idx_mouvement_type ON MOUVEMENT_STOCK(type);

-- Ajout d'un trigger pour mettre à jour automatiquement le stock lors d'un mouvement
DELIMITER //
CREATE TRIGGER after_mouvement_stock_insert 
AFTER INSERT ON MOUVEMENT_STOCK
FOR EACH ROW 
BEGIN
    IF NEW.type = 'ENTREE' THEN
        UPDATE FOURNITURE 
        SET quantite_stock = quantite_stock + NEW.quantite 
        WHERE id = NEW.id_fourniture;
    ELSEIF NEW.type = 'SORTIE' THEN
        UPDATE FOURNITURE 
        SET quantite_stock = quantite_stock - NEW.quantite 
        WHERE id = NEW.id_fourniture;
    END IF;
END//
DELIMITER ;

-- Insertion de données de test (à commenter en production)
-- INSERT INTO UTILISATEUR (nom, prenom, login, mot_de_passe, role) VALUES
-- ('Admin', 'System', 'root', '$2y$10$JHaxZmEJr5Llo5e65ytdk.3jtR8v3fvFultDY3ZmGSL.kb5Mv4eSS', 'ADMIN');


-- INSERT INTO FOURNITURE (reference, designation, description, quantite_stock, seuil_alerte) VALUES
-- ('F001', 'Stylo bleu', 'Stylo à bille de couleur bleue', 100, 20),
-- ('F002', 'Ramette papier A4', 'Ramette de 500 feuilles blanches A4 80g', 50, 10),
-- ('F003', 'Cahier grand format', 'Cahier 24x32 à grands carreaux', 75, 15);

-- -- Désactiver temporairement les contraintes de clé étrangère
-- SET FOREIGN_KEY_CHECKS = 0;

-- -- Vider la table MOUVEMENT_STOCK et remet à zéro les index
-- TRUNCATE TABLE MOUVEMENT_STOCK;
-- -- Vider la table APPROBATION et remet à zéro les index
-- TRUNCATE TABLE APPROBATION;
-- -- Vider la table FOURNITURE et remet à zéro les index
-- TRUNCATE TABLE FOURNITURE;
-- -- Vider la table UTILISATEUR et remet à zéro les index
-- TRUNCATE TABLE UTILISATEUR;

-- -- Réactiver les contraintes de clé étrangère
-- SET FOREIGN_KEY_CHECKS = 1;

-- Table pour les feature toggles (activé/désactivé des fonctionnalités)
DROP TABLE IF EXISTS FEATURE_TOGGLES;
CREATE TABLE FEATURE_TOGGLES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature_key VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    value TINYINT(1) NOT NULL DEFAULT 0,
    description TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Toggles de fonctionnalités système
INSERT INTO FEATURE_TOGGLES (feature_key, label, value, description) VALUES
('enable_barcode_scanner', 'Activer le scanner de codes-barres', 1, 'Permet l''utilisation du scanner de codes-barres dans l''application'),
('enable_bulk_import', 'Activer import en lot', 0, 'Permet l''import en masse des fournitures via fichier CSV'),
('enable_stock_alerts', 'Activer alertes de stock', 1, 'Envoie des notifications quand le stock atteint le seuil d''alerte'),
('enable_auto_ordering', 'Activer commandes automatiques', 0, 'Marque automatiquement les articles à commander quand ils atteignent le seuil'),
('require_approval_for_exits', 'Approbation des sorties', 1, 'Exige une approbation pour les sorties de stock importantes'),
('enable_export_pdf', 'Export PDF', 1, 'Permet l''export des rapports et listes en format PDF'),
('enable_dark_mode', 'Mode sombre', 1, 'Active l''option du thème sombre dans l''interface'),
('enable_movement_history', 'Historique détaillé', 1, 'Conserve un historique détaillé des mouvements de stock'),
('enable_advanced_search', 'Recherche avancée', 0, 'Active les fonctionnalités de recherche avancée dans les listes'),
('enable_email_notifications', 'Notifications par email', 0, 'Envoie des notifications par email pour les événements importants');
