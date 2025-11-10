<?php
/**
 * Historique des mouvements de stock
 * Affiche l'historique complet des mouvements de stock avec options de filtrage
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Définir ROOT_PATH pour le header
if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Initialiser les paramètres de filtrage
$supply_id = isset($_GET['supply_id']) && is_numeric($_GET['supply_id']) ? (int)$_GET['supply_id'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$movement_type = isset($_GET['type']) && in_array($_GET['type'], ['ENTREE', 'SORTIE', 'all']) ? $_GET['type'] : 'all';

// Construction de la requête SQL de base
$sql = "
    SELECT m.id, m.date_mouvement, m.date_creation, m.type, m.quantite, m.motif,
           f.id as fourniture_id, f.reference, f.designation,
           u.id as user_id, u.nom as user_nom, u.prenom as user_prenom
    FROM MOUVEMENT_STOCK m
    JOIN FOURNITURE f ON m.id_fourniture = f.id
    JOIN UTILISATEUR u ON m.id_utilisateur = u.id
    WHERE 1=1
    
";
$params = [];

// Ajout des conditions de filtrage
if ($supply_id) {
    $sql .= " AND m.id_fourniture = ?";
    $params[] = $supply_id;
}

if (!empty($start_date)) {
    $sql .= " AND m.date_mouvement >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $sql .= " AND m.date_mouvement <= ?";
    $params[] = $end_date;
}

if ($movement_type !== 'all') {
    $sql .= " AND m.type = ?";
    $params[] = $movement_type;
}

// Ordre de tri (du plus récent au plus ancien)
$sql .= " ORDER BY m.date_mouvement DESC, m.date_creation DESC";
// $sql .= " ORDER BY m.date_mouvement DESC";

// Récupérer les mouvements
try {
    $db = getDbConnection();
    
    // Si on filtre par fourniture, récupérer ses informations
    $supply = null;
    if ($supply_id) {
        $stmt = $db->prepare("SELECT id, reference, designation FROM FOURNITURE WHERE id = ?");
        $stmt->execute([$supply_id]);
        $supply = $stmt->fetch();
        
        if (!$supply) {
            $_SESSION['error_message'] = "Fourniture non trouvée.";
            redirect(BASE_URL . '/views/stock/movements.php');
        }
    }
    
    // Récupérer les mouvements
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $movements = $stmt->fetchAll();
    
    // Récupérer la liste des fournitures pour le filtre
    $stmt = $db->query("SELECT id, reference, designation FROM FOURNITURE ORDER BY reference");
    $supplies = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des mouvements: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération des mouvements.";
    redirect(BASE_URL . '/dashboard.php');
}

// Définir le titre de la page
if ($supply) {
    $page_title = "Mouvements de stock : " . $supply['designation'];
} else {
    $page_title = "Historique des mouvements de stock";
}

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <!-- Entête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="fas fa-exchange-alt me-2"></i>
            <?php if ($supply): ?>
                Mouvements de stock : <span class="text-primary"><?php echo htmlspecialchars($supply['designation']); ?></span>
            <?php else: ?>
                Historique des mouvements de stock
            <?php endif; ?>
        </h1>
        <div>
            <?php if ($supply): ?>
                <a href="<?php echo BASE_URL; ?>/views/supplies/view.php?id=<?php echo $supply_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour à la fourniture
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/views/stock/entry.php" class="btn btn-success me-2">
                    <i class="fas fa-plus-circle me-1"></i> Nouvelle entrée
                </a>
                <a href="<?php echo BASE_URL; ?>/views/stock/exit.php" class="btn btn-danger">
                    <i class="fas fa-minus-circle me-1"></i> Nouvelle sortie
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filtres de recherche -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <?php if ($supply_id): ?>
                    <input type="hidden" name="supply_id" value="<?php echo $supply_id; ?>">
                <?php else: ?>
                    <div class="col-md-4">
                        <label for="supply_id" class="form-label">Fourniture</label>
                        <select class="form-select" id="supply_id" name="supply_id">
                            <option value="">Toutes les fournitures</option>
                            <?php foreach ($supplies as $item): ?>
                                <option value="<?php echo $item['id']; ?>" <?php echo $supply_id == $item['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['reference'] . ' - ' . $item['designation']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="all" <?php echo $movement_type === 'all' ? 'selected' : ''; ?>>Tous</option>
                        <option value="ENTREE" <?php echo $movement_type === 'ENTREE' ? 'selected' : ''; ?>>Entrées</option>
                        <option value="SORTIE" <?php echo $movement_type === 'SORTIE' ? 'selected' : ''; ?>>Sorties</option>
                    </select>
                </div>
                
                <div class="col-12 d-flex justify-content-end">
                    <a href="<?php echo BASE_URL; ?>/views/stock/movements.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-redo-alt me-1"></i> Réinitialiser
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Filtrer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/views/stock/export_movements.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                        <i class="fas fa-file-csv me-1"></i> Exporter en CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Résultats -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($movements)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Aucun mouvement de stock ne correspond aux critères de recherche.
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <p class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> 
                        <?php echo count($movements); ?> mouvement(s) trouvé(s)
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="movements-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <?php if (!$supply): ?>
                                    <th>Fourniture</th>
                                <?php endif; ?>
                                <th>Type</th>
                                <th>Quantité</th>
                                <th>Motif</th>
                                <th>Utilisateur</th>
                                <th>Date création</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $movement): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($movement['date_mouvement'])); ?></td>
                                    
                                    <?php if (!$supply): ?>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/views/supplies/view.php?id=<?php echo $movement['fourniture_id']; ?>" class="link-primary">
                                                <strong><?php echo htmlspecialchars($movement['reference']); ?></strong>
                                                - <?php echo htmlspecialchars($movement['designation']); ?>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <td>
                                        <?php if ($movement['type'] === 'ENTREE'): ?>
                                            <span class="badge bg-success">Entrée</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Sortie</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="fw-bold text-end"><?php echo number_format($movement['quantite'], 0, ',', ' '); ?></td>
                                    
                                    <td><?php echo htmlspecialchars($movement['motif'] ?: 'Non précisé'); ?></td>
                                    
                                    <td><?php echo htmlspecialchars($movement['user_prenom'] . ' ' . $movement['user_nom']); ?></td>
                                    
                                    <td><?php echo date('d/m/Y H:i', strtotime($movement['date_creation'])); ?></td>
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
// Scripts spécifiques à la page
$page_scripts = [
    'assets/js/datatables.min.js'
];

$page_specific_script = "
    // Initialisation de DataTables pour la pagination et la recherche
    $(document).ready(function() {
        $('#movements-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json'
            },
            pageLength: 25
        });
    });
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>