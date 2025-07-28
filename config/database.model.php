<?php
/**
 * Configuration de la base de données
 * Ce fichier contient les paramètres de connexion à la base de données
 */

// Informations de connexion à la base de données
define('DB_HOST', '127.0.0.1'); // Ou '127.0.0.1' puisque MySQL est sur le même VPS
define('DB_NAME', 'db_Name'); // Le nom de votre base de données
define('DB_USER', 'user'); // Votre utilisateur MySQL
define('DB_PASSWORD', 'password'); // Remplacez par votre mot de passe
define('DB_CHARSET', 'utf8mb4');    // Encodage de caractères

/**
 * Fonction pour établir une connexion à la base de données
 * @return PDO|null Instance PDO ou null en cas d'échec
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        // En production, utiliser un système de log au lieu d'afficher l'erreur
        error_log('Erreur de connexion à la base de données : ' . $e->getMessage());
        return null;
    }
}