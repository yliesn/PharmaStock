<?php
/**
 * API pour gérer les péremptions
 * GET: Récupère la liste des péremptions
 * POST: Ajoute une nouvelle péremption
 * DELETE: Supprime une péremption
 */

require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = getDbConnection();
    
    // GET: Récupérer les péremptions
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['fourniture_id']) || !is_numeric($_GET['fourniture_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Paramètre fourniture_id manquant ou invalide']);
            exit;
        }
        
        $fourniture_id = (int)$_GET['fourniture_id'];
        
        $stmt = $db->prepare("
            SELECT id, fourniture_id, numero_lot, date_peremption, commentaire, actif
            FROM PEREMPTION
            WHERE fourniture_id = ?
            ORDER BY date_peremption ASC
        ");
        $stmt->execute([$fourniture_id]);
        $peremptions = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'peremptions' => $peremptions
        ]);
        exit;
    }
    
    // POST: Ajouter une péremption
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fourniture_id = isset($_POST['fourniture_id']) ? (int)$_POST['fourniture_id'] : null;
        $numero_lot = isset($_POST['numero_lot']) ? trim($_POST['numero_lot']) : null;
        $date_peremption = isset($_POST['date_peremption']) ? trim($_POST['date_peremption']) : null;
        $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : null;
        
        // Validation
        if (!$fourniture_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Fourniture invalide']);
            exit;
        }
        
        if (!$numero_lot) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Numéro de lot requis']);
            exit;
        }
        
        if (!$date_peremption) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Date de péremption requise']);
            exit;
        }
        
        // Vérifier que la fourniture existe
        $stmt = $db->prepare("SELECT id FROM FOURNITURE WHERE id = ?");
        $stmt->execute([$fourniture_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fourniture non trouvée']);
            exit;
        }
        
        // Vérifier l'unicité du lot pour cette fourniture
        $stmt = $db->prepare("
            SELECT id FROM PEREMPTION 
            WHERE fourniture_id = ? AND numero_lot = ?
        ");
        $stmt->execute([$fourniture_id, $numero_lot]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ce numéro de lot existe déjà pour cette fourniture']);
            exit;
        }
        
        // Valider le format de la date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_peremption)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format de date invalide']);
            exit;
        }
        
        // Insérer la péremption
        $stmt = $db->prepare("
            INSERT INTO PEREMPTION (fourniture_id, numero_lot, date_peremption, commentaire, actif)
            VALUES (?, ?, ?, ?, TRUE)
        ");
        
        if ($stmt->execute([$fourniture_id, $numero_lot, $date_peremption, $commentaire ?: null])) {
            echo json_encode([
                'success' => true,
                'message' => 'Péremption ajoutée avec succès',
                'id' => $db->lastInsertId()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la péremption']);
        }
        exit;
    }
    
    // DELETE: Supprimer une péremption
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Paramètre id manquant ou invalide']);
            exit;
        }
        
        $id = (int)$_GET['id'];
        
        $stmt = $db->prepare("DELETE FROM PEREMPTION WHERE id = ?");
        if ($stmt->execute([$id])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Péremption supprimée avec succès'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Péremption non trouvée']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        exit;
    }
    
    // Méthode non supportée
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non supportée']);
    
} catch (Exception $e) {
    error_log('Erreur dans peremptions.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
