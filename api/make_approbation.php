<?php
// Script pour enregistrer une demande de sortie de stock (approbation) depuis l'espace visiteur
require_once '../config/config.php';

// Vérifier si les approbations sont activées
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT value FROM FEATURE_TOGGLES WHERE feature_key = 'enable_approvals' LIMIT 1");
    $stmt->execute();
    $approvals_enabled = $stmt->fetchColumn();
} catch (Exception $e) { 
    $approvals_enabled = false;
}

if (!$approvals_enabled) {
    header('Location: ' . BASE_URL . '/views/visiteur/index.php?error=2');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supply_id = isset($_POST['supply_id']) ? (int)$_POST['supply_id'] : 0;
    $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 0;
    $motif = isset($_POST['motif']) ? trim($_POST['motif']) : '';

    // Validation simple
    if ($supply_id > 0 && $quantite > 0 && !empty($motif)) {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("INSERT INTO APPROBATION (supply_id, quantite, motif) VALUES (?, ?, ?)");
            $stmt->execute([$supply_id, $quantite, $motif]);
            // Redirection avec succès
            header('Location: ' . BASE_URL . '/views/visiteur/index.php?success=1');
            exit;
        } catch (Exception $e) {
            error_log('Erreur insertion approbation: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/views/visiteur/index.php?error=1');
            exit;
        }
    } else {
        // Données invalides
        header('Location: ' . BASE_URL . '/views/visiteur/index.php?error=1');
        exit;
    }
} else {
    // Accès direct interdit
    header('Location: ' . BASE_URL . '/views/visiteur/index.php');
    exit;
}
