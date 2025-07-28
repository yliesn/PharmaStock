<?php
/**
 * Configuration générale de l'application
 * Ce fichier contient les paramètres généraux de l'application
 */

// Configurations générales
define('APP_NAME', 'Gestion de Stock Pharmacie');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://test.fr'); // À adapter selon votre configuration

// Configurations des chemins
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Configuration des sessions
ini_set('session.cookie_httponly', 1); // Protection contre les attaques XSS
ini_set('session.use_only_cookies', 1); // Forcer l'utilisation des cookies pour les sessions
ini_set('session.cookie_secure', 0);    // Mettre à 1 en production avec HTTPS

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Paramètres de sécurité
define('HASH_COST', 10); // Coût du hachage bcrypt

// Inclure la configuration de la base de données
require_once ROOT_PATH . '/config/database.php';

// // Paramètres pour les alertes de stock
// define('EMAIL_ADMIN', 'admin@pharmacie.com'); // Email pour les alertes
// define('ENABLE_STOCK_ALERTS', true);         // Activer/désactiver les alertes de stock

/**
 * Fonction pour charger automatiquement les classes
 * @param string $class_name Nom de la classe à charger
 */
spl_autoload_register(function ($class_name) {
    // Déterminer si c'est un modèle ou un contrôleur
    if (strpos($class_name, 'Controller') !== false) {
        $file = ROOT_PATH . '/controllers/' . $class_name . '.php';
    } else {
        $file = ROOT_PATH . '/models/' . $class_name . '.php';
    }
    
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Démarre la session si elle n'est pas déjà démarrée
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Redirection vers une URL
 * @param string $path Chemin relatif à rediriger
 */
function redirect($path) {
    header('Location: ' . BASE_URL . '/' . $path);
    exit;
}

// Démarrer la session
startSession();