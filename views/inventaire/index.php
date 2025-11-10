<?php
// Page d'accueil de l'inventaire avec bouton "Commencer l'inventaire"
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/auth/login.php');
}

// Vérifier si la fonctionnalité d'inventaire est activée
if (!isFeatureEnabled('enable_inventory')) {
    $_SESSION['error_message'] = "La fonctionnalité d'inventaire est actuellement désactivée.";
    redirect('dashboard.php');
}
// Définir ROOT_PATH pour le header
// define('ROOT_PATH', dirname(dirname(__DIR__)));

// Récupérer la liste des inventaires
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT I.id, I.date_inventaire, I.commentaire, U.nom, U.prenom FROM INVENTAIRE I JOIN UTILISATEUR U ON I.utilisateur_id = U.id ORDER BY I.date_inventaire DESC");
    $inventaires = $stmt->fetchAll();
} catch (Exception $e) {
    $inventaires = [];
}
?>

<?php include_once ROOT_PATH . '/includes/header.php'; ?>
<div class="container mt-4">
    <div class="row justify-content-center mb-4 mt-5">
        <div class="col-auto">
            <a href="create.php" class="btn btn-primary btn-lg d-block mx-auto">
                <i class="fas fa-plus me-2"></i>Commencer un nouvel inventaire
            </a>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0">Historique des inventaires</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($inventaires)): ?>
                        <div class="alert alert-info mb-0">Aucun inventaire enregistré pour le moment.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <!-- <th class="d-none d-md-table-cell">Commentaire</th> -->
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($inventaires as $inv): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($inv['date_inventaire']))) ?></td>
                                    <td><?= htmlspecialchars($inv['prenom'] . ' ' . $inv['nom']) ?></td>
                                    <!-- <td class="d-none d-md-table-cell text-truncate" style="max-width:220px;" title="<?= htmlspecialchars($inv['commentaire']) ?>">
                                        <?= htmlspecialchars(mb_substr($inv['commentaire'], 0, 60)) ?><?= mb_strlen($inv['commentaire']) > 60 ? '...' : '' ?>
                                    </td> -->
                                    <td><a class="btn btn-outline-primary btn-sm" href="compare.php?id=<?= $inv['id'] ?>">Consulter</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once ROOT_PATH . '/includes/footer.php'; ?>

