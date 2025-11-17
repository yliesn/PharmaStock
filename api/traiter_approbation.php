<?php
// Script pour traiter une demande d'approbation (approuver ou refuser)
require_once '../config/config.php';
require_once '../includes/functions.php';

// Vérifier si les approbations sont activées
// try {
//     $db = getDbConnection();
//     $stmt = $db->prepare("SELECT value FROM FEATURE_TOGGLES WHERE feature_key = 'enable_approvals' LIMIT 1");
//     $stmt->execute();
//     $approvals_enabled = $stmt->fetchColumn();
// } catch (Exception $e) { 
//     $approvals_enabled = false;
// }
$approvals_enabled = isFeatureEnabled('enable_approvals');

if (!$approvals_enabled) {
    header('Location: ' . BASE_URL . '/views/users/approbations.php?error=2');
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array($_SESSION['user_role'], ['UTILISATEUR', 'ADMIN'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $user_id = $_SESSION['user_id'];

    if ($id > 0 && in_array($action, ['APPROUVEE', 'REFUSEE'])) {
        try {
            $db = getDbConnection();
            // Récupérer la demande
            $stmt = $db->prepare("SELECT * FROM APPROBATION WHERE id = ? AND statut = 'EN_ATTENTE'");
            $stmt->execute([$id]);
            $demande = $stmt->fetch();
            if (!$demande) {
                header('Location: ' . BASE_URL . '/views/users/approbations.php?error=1');
                exit;
            }
            // Si approuvée, créer le mouvement de stock
            if ($action === 'APPROUVEE') {
                $stmt2 = $db->prepare("INSERT INTO MOUVEMENT_STOCK (date_mouvement, type, quantite, motif, id_fourniture, id_utilisateur) VALUES (CURDATE(), 'SORTIE', ?, ?, ?, ?)");
                $stmt2->execute([
                    $demande['quantite'],
                    $demande['motif'],
                    $demande['supply_id'],
                    $user_id
                ]);
            }
            // Mettre à jour la demande
            $stmt3 = $db->prepare("UPDATE APPROBATION SET statut = ?, date_validation = NOW(), traite_par = ? WHERE id = ?");
            $stmt3->execute([$action, $user_id, $id]);
            header('Location: ' . BASE_URL . '/views/users/approbations.php?success=1');
            exit;
        } catch (Exception $e) {
            error_log('Erreur traitement approbation: ' . $e->getMessage());
            echo '<pre>' . $e->getMessage() . '</pre>';
            exit;
        }
    } else {
        header('Location: ' . BASE_URL . '/views/users/approbations.php?error=3');
        exit;
    }
} else {
    header('Location: ' . BASE_URL . '/views/users/approbations.php');
    exit;
}
