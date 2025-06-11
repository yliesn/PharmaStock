<?php
/**
 * Marquer une fourniture comme commandée ou annuler la commande
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Erreur de validation du formulaire. Veuillez réessayer.";
    } else {
        // Récupérer les paramètres
        $supply_id = isset($_POST['supply_id']) ? (int)$_POST['supply_id'] : 0;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'views/supplies/list.php';
        
        try {
            $db = getDbConnection();
            
            // Mettre à jour le statut de commande
            $stmt = $db->prepare("UPDATE FOURNITURE SET commande_en_cours = ? WHERE id = ?");
            $result = $stmt->execute([$status, $supply_id]);
            
            if ($result) {
                $_SESSION['success_message'] = $status 
                    ? "La fourniture a été marquée comme commandée." 
                    : "La commande a été annulée.";
            } else {
                $_SESSION['error_message'] = "Une erreur est survenue lors de la mise à jour du statut.";
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour du statut de commande: ' . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors de la mise à jour du statut.";
        }
    }
    
    // Rediriger vers la page spécifiée
    redirect($redirect);
} else {
    // Si accès direct à cette page, rediriger vers la liste des fournitures
    redirect('views/supplies/list.php');
}