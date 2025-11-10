<?php
/**
 * Vue détaillée d'une fourniture
 * Affiche les détails d'une fourniture et son historique de mouvements
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

// Vérifier si l'ID de la fourniture est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Identifiant de fourniture invalide.";
    redirect(BASE_URL . '/views/supplies/list.php');
}

$supply_id = (int)$_GET['id'];

// Récupérer les informations de la fourniture
try {
    $db = getDbConnection();
    
    // Informations de base de la fourniture
    $stmt = $db->prepare("
        SELECT id, reference, designation, description, quantite_stock, seuil_alerte
        FROM FOURNITURE 
        WHERE id = ?
    ");
    $stmt->execute([$supply_id]);
    $supply = $stmt->fetch();

    if (!$supply) {
        $_SESSION['error_message'] = "Fourniture non trouvée.";
        redirect(BASE_URL . '/views/supplies/list.php');
    }
    
    // Historique des mouvements (limités aux 10 derniers)
    $stmt = $db->prepare("
        SELECT m.id, m.date_mouvement, m.date_creation, m.type, m.quantite, m.motif,
               u.nom as user_nom, u.prenom as user_prenom
        FROM MOUVEMENT_STOCK m
        JOIN UTILISATEUR u ON m.id_utilisateur = u.id
        WHERE m.id_fourniture = ?
        ORDER BY m.date_creation DESC
        LIMIT 10
    ");
    $stmt->execute([$supply_id]);
    $movements = $stmt->fetchAll();
    
    // Calcul des statistiques
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_movements,
            SUM(CASE WHEN type = 'ENTREE' THEN quantite ELSE 0 END) as total_entries,
            SUM(CASE WHEN type = 'SORTIE' THEN quantite ELSE 0 END) as total_exits,
            MAX(date_mouvement) as last_movement_date
        FROM MOUVEMENT_STOCK
        WHERE id_fourniture = ?
    ");
    $stmt->execute([$supply_id]);
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des données de fourniture: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération des données.";
    redirect(BASE_URL . '/views/supplies/list.php');
}

// Définir le titre de la page
$page_title = "Détails : " . $supply['designation'];

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <!-- Entête avec boutons d'action -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-box me-2"></i>Détails de la fourniture</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/views/stock/entry.php?supply_id=<?php echo $supply['id']; ?>" class="btn btn-success me-2">
                <i class="fas fa-plus-circle me-1"></i> Entrée de stock
            </a>
            <a href="<?php echo BASE_URL; ?>/views/stock/exit.php?supply_id=<?php echo $supply['id']; ?>" class="btn btn-danger me-2">
                <i class="fas fa-minus-circle me-1"></i> Sortie de stock
            </a>
            <a href="<?php echo BASE_URL; ?>/views/supplies/edit.php?id=<?php echo $supply['id']; ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit me-1"></i> Modifier
            </a>
            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations de la fourniture -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold text-muted">Référence:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($supply['reference']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold text-muted">Désignation:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($supply['designation']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold text-muted">Conditionnement:</div>
                        <div class="col-md-8">
                            <?php echo !empty($supply['description']) ? nl2br(htmlspecialchars($supply['description'])) : '<em class="text-muted">Non renseignée</em>'; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold text-muted">Stock actuel:</div>
                        <div class="col-md-8">
                            <span class="fw-bold <?php echo ($supply['seuil_alerte'] && $supply['quantite_stock'] <= $supply['seuil_alerte']) ? 'text-danger' : 'text-success'; ?>">
                                <?php echo number_format($supply['quantite_stock'], 0, ',', ' '); ?> unité(s)
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold text-muted">Seuil d'alerte:</div>
                        <div class="col-md-8">
                            <?php 
                            if ($supply['seuil_alerte']) {
                                echo number_format($supply['seuil_alerte'], 0, ',', ' ') . ' unité(s)';
                            } else {
                                echo '<em class="text-muted">Non défini</em>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 fw-bold text-muted">Statut:</div>
                        <div class="col-md-8">
                            <?php 
                            if ($supply['seuil_alerte'] && $supply['quantite_stock'] <= $supply['seuil_alerte']) {
                                if ($supply['quantite_stock'] == 0) {
                                    echo '<span class="badge bg-danger">Rupture de stock</span>';
                                } else {
                                    echo '<span class="badge bg-warning text-dark">Stock bas</span>';
                                }
                            } else {
                                echo '<span class="badge bg-success">Stock normal</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button id="barcode-btn" class="btn btn-primary me-2" type="button">
                                <i class="fa-regular fa-file me-1"></i> Télécharger
                            </button>
                            <!-- SVG visible pour le code-barres -->
                            <div class="mt-3">
                                <svg id="barcode"></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistiques</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Total des mouvements</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_movements'], 0, ',', ' '); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Dernier mouvement</h6>
                                    <h5 class="mb-0">
                                        <?php 
                                        if ($stats['last_movement_date']) {
                                            echo date('d/m/Y', strtotime($stats['last_movement_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Total des entrées</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_entries'], 0, ',', ' '); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>Total des sorties</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_exits'], 0, ',', ' '); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo BASE_URL; ?>/views/stock/movements.php?supply_id=<?php echo $supply['id']; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-history me-1"></i> Voir tout l'historique
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/stock/export_movements.php?supply_id=<?php echo $supply['id']; ?>" class="btn btn-outline-success">
                            <i class="fas fa-file-csv me-1"></i> Exporter les mouvements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Historique des mouvements récents -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Derniers mouvements de stock</h5>
        </div>
        <div class="card-body">
            <?php if (empty($movements)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Aucun mouvement de stock enregistré pour cette fourniture.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantité</th>
                                <th>Motif</th>
                                <th>Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $movement): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($movement['date_mouvement'])); ?></td>
                                    <td>
                                        <?php if ($movement['type'] === 'ENTREE'): ?>
                                            <span class="badge bg-success">Entrée</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Sortie</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?php echo number_format($movement['quantite'], 0, ',', ' '); ?></td>
                                    <td><?php echo htmlspecialchars($movement['motif'] ?: 'Non précisé'); ?></td>
                                    <td><?php echo htmlspecialchars($movement['user_prenom'] . ' ' . $movement['user_nom']); ?></td>
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
// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>
<!-- JsBarcode et script de génération/téléchargement du code-barres -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
// Génère le code-barres dès l'arrivée sur la page
window.addEventListener('DOMContentLoaded', function() {
    var reference = <?php echo json_encode($supply['reference']); ?>;
    var desc = <?php echo json_encode($supply['designation']); ?>;
    var svg = document.getElementById('barcode');
    JsBarcode(svg, reference, {
        format: "CODE128",
        displayValue: true,
        width: 2,
        height: 40,
        margin: 10,
        text: desc
    });
});
// Téléchargement au clic sur le bouton

document.getElementById('barcode-btn').addEventListener('click', function() {
    var reference = <?php echo json_encode($supply['reference']); ?>;
    var svg = document.getElementById('barcode');
    var serializer = new XMLSerializer();
    var svgString = serializer.serializeToString(svg);
    var blob = new Blob([svgString], {type: "image/svg+xml"});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = "barcode_" + reference + ".svg";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
});
</script>