<?php
/**
 * Export des mouvements de stock en CSV
 * Génère un fichier CSV contenant l'historique des mouvements de stock
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Récupérer les paramètres de filtrage
$supply_id = isset($_GET['supply_id']) && is_numeric($_GET['supply_id']) ? (int)$_GET['supply_id'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$movement_type = isset($_GET['type']) && in_array($_GET['type'], ['ENTREE', 'SORTIE', 'all']) ? $_GET['type'] : 'all';

// Construction de la requête SQL de base
$sql = "
    SELECT 
        m.id,
        m.date_mouvement,
        m.date_creation,
        m.type,
        m.quantite,
        m.motif,
        f.reference as fourniture_reference,
        f.designation as fourniture_designation,
        u.nom as user_nom,
        u.prenom as user_prenom
    FROM MOUVEMENT_STOCK m
    JOIN FOURNITURE f ON m.id_fourniture = f.id
    JOIN UTILISATEUR u ON m.id_utilisateur = u.id
    WHERE 1=1
";
$params = [];

// Ajout des conditions de filtrage
if ($supply_id) {
    $sql .= " AND m.id_fourniture = ?";
    $params[] = $supply_id;
}

if (!empty($start_date)) {
    $sql .= " AND m.date_mouvement >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $sql .= " AND m.date_mouvement <= ?";
    $params[] = $end_date;
}

if ($movement_type !== 'all') {
    $sql .= " AND m.type = ?";
    $params[] = $movement_type;
}

// Ordre de tri (du plus récent au plus ancien)
$sql .= " ORDER BY m.date_mouvement DESC, m.date_creation DESC";

try {
    $db = getDbConnection();
    
    // Récupérer les données
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $movements = $stmt->fetchAll();
    
    // Si aucun mouvement trouvé
    if (empty($movements)) {
        $_SESSION['error_message'] = "Aucun mouvement ne correspond aux critères d'exportation.";
        redirect(BASE_URL . '/views/stock/movements.php');
    }
    
    // Définir les en-têtes HTTP pour le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=mouvements_stock_' . date('Y-m-d') . '.csv');
    
    // Créer le gestionnaire de fichier CSV
    $output = fopen('php://output', 'w');
    
    // Forcer l'encodage UTF-8 avec BOM pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Ajouter l'en-tête CSV
    fputcsv($output, [
        'ID',
        'Date du mouvement',
        'Type',
        'Quantité',
        'Référence fourniture',
        'Désignation fourniture',
        'Motif',
        'Utilisateur',
        'Date création'
    ], ';');
    
    // Ajouter les données
    foreach ($movements as $row) {
        fputcsv($output, [
            $row['id'],
            date('d/m/Y', strtotime($row['date_mouvement'])),
            $row['type'] === 'ENTREE' ? 'Entrée' : 'Sortie',
            $row['quantite'],
            $row['fourniture_reference'],
            $row['fourniture_designation'],
            $row['motif'],
            $row['user_prenom'] . ' ' . $row['user_nom'],
            date('d/m/Y H:i', strtotime($row['date_creation']))
        ], ';');
    }
    
    // Fermer le fichier
    fclose($output);
    exit;
    
} catch (Exception $e) {
    error_log('Erreur lors de l\'exportation des mouvements: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de l'exportation des mouvements.";
    redirect(BASE_URL . '/views/stock/movements.php');
}