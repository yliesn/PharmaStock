<?php
// Script de correction de stock à partir d'un inventaire
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect('/auth/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('ID inventaire manquant ou invalide.');
}

try {
    $db = getDbConnection();
    // Récupérer l'inventaire
    $stmt = $db->prepare("SELECT * FROM INVENTAIRE WHERE id = ?");
    $stmt->execute([$id]);
    $inventaire = $stmt->fetch();
    if (!$inventaire) throw new Exception('Inventaire non trouvé.');
    // Récupérer les lignes d'inventaire
    $stmt = $db->prepare("SELECT * FROM INVENTAIRE_LIGNE WHERE inventaire_id = ?");
    $stmt->execute([$id]);
    $lignes = $stmt->fetchAll();
    $utilisateur_id = $inventaire['utilisateur_id'];
    $nb_mouvements = 0;
    $db->beginTransaction();
    foreach ($lignes as $ligne) {
        $fourniture_id = $ligne['fourniture_id'];
        $qte_theorique = (int)$ligne['quantite_theorique'];
        $qte_physique = (int)$ligne['quantite_physique'];
        if ($qte_physique === -1 || $qte_physique === $qte_theorique) continue; // pas de correction
        $ecart = $qte_physique - $qte_theorique;
        $type = $ecart > 0 ? 'entree' : 'sortie';
        $quantite = abs($ecart);
        // Enregistrer le mouvement
        $stmtMv = $db->prepare("INSERT INTO MOUVEMENT (date_mouvement, fourniture_id, type, quantite, utilisateur_id, commentaire) VALUES (NOW(), ?, ?, ?, ?, ?)");
        $commentaire = 'Correction inventaire #'.$id;
        $stmtMv->execute([$fourniture_id, $type, $quantite, $utilisateur_id, $commentaire]);
        $nb_mouvements++;
    }
    $db->commit();
    echo '<div style="margin:2em auto;max-width:500px;text-align:center">';
    echo '<h2>Correction terminée</h2>';
    echo '<p>'.$nb_mouvements.' mouvement(s) de stock créés pour l\'inventaire #'.$id.'.</p>';
    echo '<a href="/views/inventaire/compare.php?id='.$id.'">Retour à l\'inventaire</a>';
    echo '</div>';
} catch (Exception $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    echo '<div style="color:red;text-align:center;margin-top:2em">Erreur : '.htmlspecialchars($e->getMessage()).'</div>';
}
