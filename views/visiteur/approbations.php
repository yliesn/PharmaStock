<?php
require_once '../../config/config.php';

// Vérifier si l'utilisateur est bien connecté et a le rôle VISITEUR
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'VISITEUR') {
    redirect(BASE_URL . '/index.php');
    exit;
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

$page_title = "Mes approbations en attente";

// Récupération des approbations en attente
try {
    $db = getDbConnection();
    $sql = "SELECT a.id, a.date_creation, a.quantite, a.motif, a.statut, f.designation 
            FROM APPROBATION a 
            JOIN FOURNITURE f ON a.supply_id = f.id 
            WHERE a.statut = 'EN_ATTENTE' 
            ORDER BY a.date_creation DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $approbations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des approbations: ' . $e->getMessage());
    $approbations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - PharmaStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .small-muted { font-size: .9rem; color: #6c757d; }
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
        }
        .status-en-attente {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-info">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#"><i class="fas fa-capsules me-2"></i><span>PharmaStock</span></a>
        <div class="d-flex ms-auto gap-2">
            <a href="<?= BASE_URL ?>/views/visiteur/index.php" class="btn btn-outline-light" aria-label="Stock"><i class="fas fa-box me-1"></i> Stock</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline-light" aria-label="Déconnexion"><i class="fas fa-sign-out-alt me-1"></i> Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($page_title) ?></h3>
                    <p class="small-muted mb-0">Liste de vos demandes de sortie en attente d'approbation.</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <?php if (!empty($approbations)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle bg-white shadow-sm rounded">
                        <thead class="table-light">
                            <tr>
                                <th>Date demande</th>
                                <th>Fourniture</th>
                                <th>Quantité</th>
                                <th>Motif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approbations as $approbation): ?>
                                                <tr>
                    <td><?= (new DateTime($approbation['date_creation']))->format('d/m/Y H:i') ?></td>
                    <td><?= htmlspecialchars($approbation['designation']) ?></td>
                    <td><?= number_format($approbation['quantite'], 0, ',', ' ') ?></td>
                    <td><?= htmlspecialchars($approbation['motif']) ?></td>
                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <p class="mb-0">Aucune approbation en attente.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/notifications.js"></script>
<script>
const notifier = new NotificationSystem({ duration: 8000, position: 'top-right' });

// Gérer messages GET success/error
(function(){
    const params = new URLSearchParams(window.location.search);
    if(params.has('success')) notifier.success('Succès', params.get('success') || 'Opération réussie.');
    if(params.has('error')) notifier.error('Erreur', params.get('error') || 'Une erreur est survenue.');
})();
</script>

<?php include_once ROOT_PATH . '/includes/footer.php'; ?>
</body>
</html>