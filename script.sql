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
