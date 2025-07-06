<?php
// API pour obtenir le nombre de demandes d'approbation en attente
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array($_SESSION['user_role'], ['UTILISATEUR', 'ADMIN'])) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT COUNT(*) as count FROM APPROBATION WHERE statut = 'EN_ATTENTE'");
    $row = $stmt->fetch();
    echo json_encode(['count' => (int)$row['count']]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
