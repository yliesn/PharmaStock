<?php
/**
 * Péremptions des fournitures
 * Affiche les fournitures avec leurs dates de péremption
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect('index.php');
}

// Définir le titre de la page
$page_title = "Péremptions";

// Définir ROOT_PATH pour le header
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Récupérer les fournitures avec leurs péremptions
try {
    $db = getDbConnection();
    
    $sql = "SELECT f.id, f.reference, f.designation, f.conditionnement, 
                   p.id as peremption_id, p.numero_lot, p.date_peremption, p.commentaire
            FROM FOURNITURE f
            LEFT JOIN PEREMPTION p ON f.id = p.fourniture_id
            WHERE p.id IS NOT NULL AND p.actif = TRUE
            ORDER BY f.reference ASC, p.date_peremption ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $peremptions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur: ' . $e->getMessage());
    $peremptions = [];
    $error_message = "Une erreur est survenue.";
}

$_SESSION['PATH'] = 'views/supplies/expiry-date.php';

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-calendar-times me-2"></i>Péremptions des fournitures</h1>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($peremptions)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Référence</th>
                        <th>Désignation</th>
                        <th>Conditionnement</th>
                        <th>Numéro de lot</th>
                        <th>Date de péremption</th>
                        <th>Statut</th>
                        <th>Commentaire</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peremptions as $item): ?>
                        <?php
                            $datePeremption = new DateTime($item['date_peremption']);
                            $today = new DateTime();
                            $daysLeft = $datePeremption->diff($today)->days;
                            
                            if ($datePeremption < $today) {
                                $statut = 'Périmé';
                                $badgeClass = 'bg-danger';
                            } elseif ($daysLeft <= 30) {
                                $statut = 'Expire bientôt (' . $daysLeft . 'j)';
                                $badgeClass = 'bg-warning';
                            } else {
                                $statut = 'Valide';
                                $badgeClass = 'bg-success';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['designation']); ?></td>
                            <td><?php echo htmlspecialchars($item['conditionnement']); ?></td>
                            <td><?php echo htmlspecialchars($item['numero_lot']); ?></td>
                            <td><?php echo $datePeremption->format('d/m/Y'); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $statut; ?></span></td>
                            <td><?php echo htmlspecialchars($item['commentaire'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-info-circle me-2"></i>
            Aucune péremption enregistrée.
        </div>
    <?php endif; ?>
</div>

<?php
// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>

<?php

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';

?>