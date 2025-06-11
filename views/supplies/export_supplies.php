<?php
/**
 * Export des fournitures en CSV
 * Génère un fichier CSV contenant la liste des fournitures
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Récupérer les paramètres de filtrage
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Construction de la requête SQL de base
$sql = "SELECT id, reference, designation, description, quantite_stock, seuil_alerte FROM FOURNITURE";
$params = [];

// Ajout des conditions de recherche et filtrage
if (!empty($search)) {
    $sql .= " WHERE (reference LIKE ? OR designation LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    
    if ($filter === 'low') {
        $sql .= " AND quantite_stock <= seuil_alerte AND seuil_alerte IS NOT NULL";
    } elseif ($filter === 'empty') {
        $sql .= " AND quantite_stock = 0";
    }
} elseif ($filter === 'low') {
    $sql .= " WHERE quantite_stock <= seuil_alerte AND seuil_alerte IS NOT NULL";
} elseif ($filter === 'empty') {
    $sql .= " WHERE quantite_stock = 0";
}

// Ordre de tri
$sql .= " ORDER BY designation ASC";

try {
    $db = getDbConnection();
    
    // Récupérer les données
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $supplies = $stmt->fetchAll();
    
    // Si aucune fourniture trouvée
    if (empty($supplies)) {
        $_SESSION['error_message'] = "Aucune fourniture ne correspond aux critères d'exportation.";
        redirect(BASE_URL . '/views/supplies/list.php');
    }
    
    // Définir les en-têtes HTTP pour le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=fournitures_' . date('Y-m-d') . '.csv');
    
    // Créer le gestionnaire de fichier CSV
    $output = fopen('php://output', 'w');
    
    // Forcer l'encodage UTF-8 avec BOM pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Ajouter l'en-tête CSV
    fputcsv($output, [
        'ID',
        'Référence',
        'Désignation',
        'Description',
        'Quantité en stock',
        'Seuil d\'alerte',
        'Statut'
    ], ';');
    
    // Ajouter les données
    foreach ($supplies as $supply) {
        // Déterminer le statut
        $status = '';
        if ($supply['seuil_alerte'] && $supply['quantite_stock'] <= $supply['seuil_alerte']) {
            $status = $supply['quantite_stock'] == 0 ? 'Rupture de stock' : 'Stock bas';
        } else {
            $status = 'Stock normal';
        }
        
        fputcsv($output, [
            $supply['id'],
            $supply['reference'],
            $supply['designation'],
            $supply['description'],
            $supply['quantite_stock'],
            $supply['seuil_alerte'] ?: 'Non défini',
            $status
        ], ';');
    }
    
    // Fermer le fichier
    fclose($output);
    exit;
    
} catch (Exception $e) {
    error_log('Erreur lors de l\'exportation des fournitures: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de l'exportation des fournitures.";
    redirect(BASE_URL . '/views/supplies/list.php');
}