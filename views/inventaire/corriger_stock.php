<?php
// Script de correction de stock à partir d'un inventaire
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/auth/login.php');
}

// Vérifier si la fonctionnalité d'inventaire est activée
if (!isFeatureEnabled('enable_inventory')) {
    $_SESSION['error_message'] = "La fonctionnalité d'inventaire est actuellement désactivée.";
    redirect(BASE_URL . '/dashboard.php');
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
    if ($inventaire['corrigee'] == 1) {
        throw new Exception('Cet inventaire a déjà été corrigé.');
    }
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
        $type = $ecart > 0 ? 'ENTREE' : 'SORTIE';
        $quantite = abs($ecart);
        // Enregistrer le mouvement
        $stmtMv = $db->prepare("INSERT INTO MOUVEMENT_STOCK (date_mouvement, date_creation, id_fourniture, type, quantite, id_utilisateur, motif) VALUES (NOW(), NOW(), ?, ?, ?, ?, ?)");
        $commentaire = 'Correction inventaire #'.$id;
        $stmtMv->execute([$fourniture_id, $type, $quantite, $utilisateur_id, 'inventaire #'.$id]);
        $nb_mouvements++;
    }
    // Marquer l'inventaire comme corrigé
    $stmtUp = $db->prepare("UPDATE INVENTAIRE SET corrigee = 1 WHERE id = ?");
    $stmtUp->execute([$id]);
    $db->commit();
    echo '<div style="margin:2em auto;max-width:500px;text-align:center">';
    echo '<h2>Correction terminée</h2>';
    echo '<p>'.$nb_mouvements.' mouvement(s) de stock créés pour l\'inventaire #'.$id.'.</p>';
    echo '<a href="/views/inventaire/compare.php?id='.$id.'">Retour à l\'inventaire</a>';
    echo '</div>';
} catch (Exception $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    // echo '<div style="color:red;text-align:center;margin-top:2em">Erreur : '.htmlspecialchars($e->getMessage()).'</div>';
    echo '<div style="margin:2em auto;max-width:500px;text-align:center">';
    echo '<h2>Erreur lors de la correction</h2>';
    echo '<p style="color:red;">'.htmlspecialchars($e->getMessage()).'</p>';
    echo '<a href="/views/inventaire/compare.php?id='.$id.'">Retour à l\'inventaire</a>';
    echo '</div>';
}
