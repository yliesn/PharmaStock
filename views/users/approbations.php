<?php
// Page de gestion des demandes de sortie de stock à approuver (pour UTILISATEUR/ADMIN)
require_once '../../config/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] !== 'UTILISATEUR' && $_SESSION['user_role'] !== 'ADMIN')) {
    redirect(BASE_URL . '/index.php');
    exit;
}

if (!defined('ROOT_PATH')) {
    if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}
}

$page_title = "Approbations de sorties de stock";

// Récupérer les demandes en attente
try {
    $db = getDbConnection();
    $sql = "SELECT a.id, a.supply_id, a.quantite, a.motif, a.date_creation, f.reference, f.designation FROM APPROBATION a JOIN FOURNITURE f ON a.supply_id = f.id WHERE a.statut = 'EN_ATTENTE' ORDER BY a.date_creation ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $demandes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur récupération approbations: ' . $e->getMessage());
    $demandes = [];
}

include_once ROOT_PATH . '/includes/header.php';
?>
<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-clipboard-check me-2"></i>Demandes de sortie à approuver</h2>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET['success'])): ?>
            notifications.success('Succès', 'La demande a été traitée avec succès.');
        <?php elseif (isset($_GET['error'])): ?>
            notifications.error('Erreur', 'Une erreur est survenue lors du traitement.');
        <?php endif; ?>
    });
    </script>
    <div class="card">
        <div class="card-body">
            <?php if (empty($demandes)): ?>
                <div class="alert alert-info">Aucune demande en attente.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Désignation</th>
                                <th>Quantité</th>
                                <th>Motif</th>
                                <th>Date de demande</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($demandes as $demande): ?>
                            <tr>
                                <td><?= htmlspecialchars($demande['reference']) ?></td>
                                <td><?= htmlspecialchars($demande['designation']) ?></td>
                                <td><?= (int)$demande['quantite'] ?></td>
                                <td><?= nl2br(htmlspecialchars($demande['motif'])) ?></td>
                                <td><?= htmlspecialchars($demande['date_creation']) ?></td>
                                <td>
                                    <form method="post" action="/api/traiter_approbation.php" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $demande['id'] ?>">
                                        <input type="hidden" name="action" value="APPROUVEE">
                                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approuver</button>
                                    </form>
                                    <form method="post" action="/api/traiter_approbation.php" class="d-inline ms-1">
                                        <input type="hidden" name="id" value="<?= $demande['id'] ?>">
                                        <input type="hidden" name="action" value="REFUSEE">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Refuser</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
include_once ROOT_PATH . '/includes/footer.php';
?>
