<?php
/**
 * Liste des fournitures
 * Affiche toutes les fournitures en stock avec possibilité de filtrer et trier
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Définir le titre de la page
$page_title = "Liste des fournitures";

// Définir ROOT_PATH pour le header
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Recherche et filtrage
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Construction de la requête SQL de base
$sql = "SELECT id, reference, designation, description, quantite_stock, seuil_alerte FROM FOURNITURE";
$params = [];

// Ajout des conditions de recherche et filtrage
if (!empty($search)) {
    $sql .= " WHERE (reference LIKE ? OR designation LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    
    if ($filter === 'low') {
        $sql .= " AND quantite_stock <= seuil_alerte AND seuil_alerte IS NOT NULL";
    }
} elseif ($filter === 'low') {
    $sql .= " WHERE quantite_stock <= seuil_alerte AND seuil_alerte IS NOT NULL";
}

// Ordre de tri
// $sql .= " ORDER BY id ASC";

// Récupérer les fournitures
try {
    $db = getDbConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $supplies = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des fournitures: ' . $e->getMessage());
    $supplies = [];
    $error_message = "Une erreur est survenue lors de la récupération des fournitures.";
}

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <!-- En-tête de page avec bouton d'ajout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-boxes me-2"></i>Liste des fournitures</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/views/stock/entry.php" class="btn btn-success me-2">
                <i class="fas fa-plus-circle me-1"></i> Entrée de stock
            </a>
            <a href="<?php echo BASE_URL; ?>/views/supplies/add.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nouvelle fourniture
            </a>
            <a href="<?php echo BASE_URL; ?>/views/supplies/import_supplies.php" class="btn btn-primary">
                <i class="fas fa-file-import me-1"></i> Import fourniture
            </a>
        </div>
    </div>
    
    <!-- Formulaire de recherche et filtrage -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Rechercher par référence, désignation..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Toutes les fournitures</option>
                        <option value="low" <?php echo $filter === 'low' ? 'selected' : ''; ?>>Stock bas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo BASE_URL; ?>/views/supplies/list.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo-alt me-1"></i> Réinitialiser
                    </a>
                    <a href="<?php echo BASE_URL; ?>/views/supplies/export_supplies.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                        <i class="fas fa-file-csv me-1"></i> Exporter en CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Notification d'erreur si nécessaire -->
    <?php if (isset($error_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            notifications.error('Erreur', '<?php echo addslashes($error_message); ?>');
        });
    </script>
    <?php endif; ?>
    
    <!-- Résultats de la recherche -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($supplies)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php if (!empty($search) || $filter === 'low'): ?>
                        Aucune fourniture trouvée avec ces critères.
                    <?php else: ?>
                        Aucune fourniture enregistrée dans le système.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="supplies-table">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Désignation</th>
                                <th>Stock actuel</th>
                                <th>Seuil d'alerte</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplies as $supply): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($supply['reference']); ?></td>
                                    <td>
                                        <span class="fw-bold"><?php echo htmlspecialchars($supply['designation']); ?></span>
                                        <?php if (!empty($supply['description'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars(mb_substr($supply['description'], 0, 50)) . (mb_strlen($supply['description']) > 50 ? '...' : ''); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-end"><?php echo number_format($supply['quantite_stock'], 0, ',', ' '); ?></td>
                                    <td class="text-end">
                                        <?php if ($supply['seuil_alerte']): ?>
                                            <?php echo number_format($supply['seuil_alerte'], 0, ',', ' '); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non défini</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($supply['seuil_alerte'] && $supply['quantite_stock'] <= $supply['seuil_alerte']) {
                                            if ($supply['quantite_stock'] == 0) {
                                                echo '<span class="badge bg-danger">Rupture</span>';
                                            } else {
                                                echo '<span class="badge bg-warning text-dark">Stock bas</span>';
                                            }
                                        } else {
                                            echo '<span class="badge bg-success">Normal</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo BASE_URL; ?>/views/supplies/view.php?id=<?php echo $supply['id']; ?>" 
                                               class="btn btn-outline-primary" title="Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/views/supplies/edit.php?id=<?php echo $supply['id']; ?>" 
                                               class="btn btn-outline-secondary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/views/stock/entry.php?supply_id=<?php echo $supply['id']; ?>" 
                                               class="btn btn-outline-success" title="Entrée de stock">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                            <?php  if ($supply['quantite_stock'] != 0) { ?>
                                            <a href="<?php echo BASE_URL; ?>/views/stock/exit.php?supply_id=<?php echo $supply['id']; ?>" 
                                               class="btn btn-outline-danger" title="Sortie de stock">
                                                <i class="fas fa-minus"></i>
                                            </a>
                                            <?php } ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/views/supplies/toggle_order.php" class="d-inline">
                                                    <input type="hidden" name="supply_id" value="<?php echo $supply['id']; ?>">
                                                    <input type="hidden" name="status" value="1">
                                                    <input type="hidden" name="redirect" value="dashboard.php">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" class="btn btn-warning " style="border-radius: 0;" title="Marquer comme commandé">
                                                        <i class="fas fa-truck"></i>
                                                    </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Affichage du nombre total -->
                <div class="mt-3">
                    <p class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> 
                        <?php echo count($supplies); ?> fourniture(s) trouvée(s)
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Scripts spécifiques à la page
// $page_scripts = [
//     'assets/js/datatables.min.js'
// ];

$page_specific_script = "
    // Initialisation de DataTables pour la pagination et la recherche
    $(document).ready(function() {
        $('#supplies-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json'
            },
            pageLength: 15,
            order: [[0, 'asc']]
        });
    });
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>