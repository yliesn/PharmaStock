<?php
/**
 * Liste des fournitures
 * Affiche toutes les fournitures en stock avec possibilité de filtrer et trier
 */

// Inclure le fichier de configuration
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Définir le titre de la page
$page_title = "Liste des fournitures";

// Définir ROOT_PATH pour le header
if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}


// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<div class="container">
    test
</div>

<style>
    .dashboard-widgets {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .widget {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .widget h2 {
        margin-top: 0;
    }
</style>

<?php
// Inclure le pied de page
include_once ROOT_PATH . '/includes/header.php';
?>