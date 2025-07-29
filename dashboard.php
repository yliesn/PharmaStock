<?php
/**
 * Dashboard de l'application
 * Page principale après connexion
 */
// Inclure le fichier de configuration
require_once 'config/config.php';
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion si non connecté
    redirect('index.php');
}

// Récupérer les fournitures avec stock bas
try {
    $db = getDbConnection();
    $stmt = $db->query("
        SELECT id, reference, designation, description, quantite_stock, seuil_alerte
        FROM FOURNITURE
        WHERE seuil_alerte IS NOT NULL
        AND quantite_stock <= seuil_alerte
        AND commande_en_cours = FALSE
        ORDER BY (quantite_stock = 0) DESC, (quantite_stock / seuil_alerte) ASC
    ");
    $low_stock_items = $stmt->fetchAll();
    
    // Obtenir quelques statistiques générales
    $stmt = $db->query("SELECT COUNT(*) as total FROM FOURNITURE");
    $total_supplies = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM FOURNITURE WHERE quantite_stock = 0");
    $out_of_stock = $stmt->fetch()['count'];
    
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM FOURNITURE 
        WHERE seuil_alerte IS NOT NULL 
        AND quantite_stock <= seuil_alerte 
        AND quantite_stock > 0
    ");
    $low_stock = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM MOUVEMENT_STOCK WHERE date_mouvement = CURRENT_DATE()");
    $today_movements = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des fournitures à stock bas: ' . $e->getMessage());
    $low_stock_items = [];
    $total_supplies = 0;
    $out_of_stock = 0;
    $low_stock = 0;
    $today_movements = 0;
}

// Récupérer les fournitures en commande
try {
    $stmt = $db->query("
        SELECT id, reference, designation, description, quantite_stock, seuil_alerte, commande_en_cours
        FROM FOURNITURE
        WHERE commande_en_cours = TRUE
        ORDER BY reference
    ");
    $ordered_items = $stmt->fetchAll();
    
    // Compter les fournitures en commande
    $stmt = $db->query("SELECT COUNT(*) as count FROM FOURNITURE WHERE commande_en_cours = TRUE");
    $ordered_count = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des fournitures en commande: ' . $e->getMessage());
    $ordered_items = [];
    $ordered_count = 0;
}

// Définir le titre de la page
$page_title = "Tableau de bord";
// Définir ROOT_PATH pour le header
define('ROOT_PATH', dirname(__FILE__));
// Inclure l'en-tête
include_once 'includes/header.php';
?>
<!-- Contenu principal -->
<div class="container mt-4">
    <!-- Message de bienvenue -->
    <div class="alert alert-success" role="alert">
        <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Connexion réussie !</h4>
        <p>Bienvenue <strong><?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?></strong> dans le système de gestion de stock de la pharmacie.</p>
        <hr>
        <p class="mb-0">Vous êtes connecté en tant que <strong><?php echo $_SESSION['user_role']; ?></strong>.</p>
    </div> 
    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <!-- Carte Approbations -->
        <!-- <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo BASE_URL; ?>/views/users/approbations.php" class="text-decoration-none position-relative">
                <div class="card border-left-primary shadow h-100 py-2 hover-effect">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Approbations à traiter</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="approbations-count">...</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('<?php echo BASE_URL; ?>/api/approbations_count.php')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('approbations-count').textContent = data.count;
                })
                .catch(() => {
                    document.getElementById('approbations-count').textContent = '?';
                });
        });
        </script> -->
        <!-- Carte des fournitures totales -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php" class="text-decoration-none">
                <div class="card border-left-primary shadow h-100 py-2 hover-effect">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total des fournitures</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_supplies, 0, ',', ' '); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-boxes fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Carte des ruptures de stock -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php?filter=low" class="text-decoration-none">
                <div class="card border-left-danger shadow h-100 py-2 hover-effect">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Ruptures de stock</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($out_of_stock, 0, ',', ' '); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Carte des stocks bas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php?filter=low" class="text-decoration-none">
                <div class="card border-left-warning shadow h-100 py-2 hover-effect">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Stocks bas</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($low_stock, 0, ',', ' '); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-battery-quarter fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Carte des commandes en cours -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php?filter=ordered" class="text-decoration-none">
                <div class="card border-left-success shadow h-100 py-2 hover-effect">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Commandes en cours</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($ordered_count, 0, ',', ' '); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-truck-loading fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    
    
    <!-- Fournitures à commander -->
    <div class="card shadow mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold"><i class="fas fa-shopping-cart me-2"></i>Fournitures à commander</h5>
            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php?filter=low" class="btn btn-sm btn-outline-light">
                Voir tout
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($low_stock_items)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Aucune fourniture à commander pour le moment. Tous les stocks sont à des niveaux satisfaisants.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Désignation</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Seuil</th>
                                <th class="text-center">Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['reference']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['designation']); ?></strong>
                                        <?php if (!empty($item['description'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars(mb_substr($item['description'], 0, 50)) . (mb_strlen($item['description']) > 50 ? '...' : ''); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-weight-bold <?php echo $item['quantite_stock'] == 0 ? 'text-danger' : 'text-warning'; ?>">
                                        <?php echo number_format($item['quantite_stock'], 0, ',', ' '); ?>
                                    </td>
                                    <td class="text-center"><?php echo number_format($item['seuil_alerte'], 0, ',', ' '); ?></td>
                                    <td class="text-center">
                                        <?php if ($item['quantite_stock'] == 0): ?>
                                            <span class="badge bg-danger">Rupture</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Stock bas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- <div class="btn-group btn-group-sm">
                                            <a href="<?php echo BASE_URL; ?>/views/stock/entry.php?supply_id=<?php echo $item['id']; ?>" class="btn btn-success" title="Entrée de stock">
                                                <i class="fas fa-plus-circle"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/views/supplies/view.php?id=<?php echo $item['id']; ?>" class="btn btn-primary" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div> -->
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo BASE_URL; ?>/views/stock/entry.php?supply_id=<?php echo $item['id']; ?>" class="btn btn-success" title="Entrée de stock">
                                                <i class="fas fa-plus-circle"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/views/supplies/view.php?id=<?php echo $item['id']; ?>" class="btn btn-primary" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!$item['commande_en_cours']): ?>
                                                <form method="POST" action="<?php echo BASE_URL; ?>/views/supplies/toggle_order.php" class="d-inline">
                                                    <input type="hidden" name="supply_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="status" value="1">
                                                    <input type="hidden" name="redirect" value="dashboard.php">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" class="btn btn-warning " style="border-radius: 0;" title="Marquer comme commandé">
                                                        <i class="fas fa-truck"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($low_stock_items) > 5): ?>
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>/views/supplies/list.php?filter=low" class="btn btn-primary">
                        <i class="fas fa-list me-1"></i> Voir toutes les fournitures à commander
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- Commandes en cours -->
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold"><i class="fas fa-truck-loading me-2"></i>Commandes en cours</h5>
            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php?filter=ordered" class="btn btn-sm btn-outline-light">
                Voir tout
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($ordered_items)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Aucune commande en cours actuellement.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Désignation</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Seuil</th>
                                <th class="text-center">Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordered_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['reference']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['designation']); ?></strong>
                                        <?php if (!empty($item['description'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars(mb_substr($item['description'], 0, 50)) . (mb_strlen($item['description']) > 50 ? '...' : ''); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-weight-bold <?php echo $item['quantite_stock'] == 0 ? 'text-danger' : ($item['quantite_stock'] <= $item['seuil_alerte'] ? 'text-warning' : ''); ?>">
                                        <?php echo number_format($item['quantite_stock'], 0, ',', ' '); ?>
                                    </td>
                                    <td class="text-center"><?php echo $item['seuil_alerte'] ? number_format($item['seuil_alerte'], 0, ',', ' ') : '-'; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info">Commandé</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo BASE_URL; ?>/views/stock/entry.php?supply_id=<?php echo $item['id']; ?>" class="btn btn-success" title="Réceptionner">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/views/supplies/toggle_order.php" class="d-inline">
                                                <input type="hidden" name="supply_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="status" value="0">
                                                <input type="hidden" name="redirect" value="dashboard.php">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <button type="submit" class="btn btn-warning" title="Annuler la commande">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </form>
                                            <a href="<?php echo BASE_URL; ?>/views/supplies/view.php?id=<?php echo $item['id']; ?>" class="btn btn-primary" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Activité récente -->
    <!-- <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="m-0 font-weight-bold"><i class="fas fa-history me-2"></i>Activité récente</h5>
        </div>
        <div class="card-body">
            <?php 
            // Récupérer les 5 derniers mouvements
            try {
                $stmt = $db->query("
                    SELECT m.*, f.reference, f.designation, u.nom, u.prenom
                    FROM MOUVEMENT_STOCK m
                    JOIN FOURNITURE f ON m.id_fourniture = f.id
                    JOIN UTILISATEUR u ON m.id_utilisateur = u.id
                    ORDER BY m.date_creation DESC
                    LIMIT 5
                ");
                $recent_movements = $stmt->fetchAll();
            } catch (Exception $e) {
                $recent_movements = [];
            }
            ?>
            
            <?php if (empty($recent_movements)): ?>
                <p class="text-muted">Aucune activité récente.</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($recent_movements as $movement): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <?php echo $movement['type'] === 'ENTREE' ? 
                                        '<span class="badge bg-success">Entrée</span>' : 
                                        '<span class="badge bg-danger">Sortie</span>'; ?>
                                    <strong><?php echo htmlspecialchars($movement['reference']); ?></strong> - 
                                    <?php echo htmlspecialchars($movement['designation']); ?>
                                </h6>
                                <small><?php echo date('d/m/Y H:i', strtotime($movement['date_creation'])); ?></small>
                            </div>
                            <p class="mb-1">Quantité: <strong><?php echo $movement['quantite']; ?></strong></p>
                            <?php if (!empty($movement['motif'])): ?>
                                <p class="mb-1 text-muted small"><i class="fas fa-comment-alt me-1"></i> <?php echo htmlspecialchars($movement['motif']); ?></p>
                            <?php endif; ?>
                            <small>Par <?php echo htmlspecialchars($movement['prenom'] . ' ' . $movement['nom']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>/views/stock/movements.php" class="btn btn-sm btn-outline-info">
                        Voir tout l'historique
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div> -->

    <!-- Carte de présentation -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-tachometer-alt me-2"></i>Présentation
        </div>
        <div class="card-body">
            <h5 class="card-title">Système de gestion de stock de pharmacie</h5>
            <p class="card-text">
                Ce système vous permet de gérer efficacement le stock des fournitures de votre pharmacie.
                Vous pouvez consulter, ajouter, modifier et supprimer des produits, ainsi que suivre tous les mouvements de stock.
            </p>
            <p>
                Utilisez le menu pour naviguer entre les différentes fonctionnalités.
            </p>
        </div>
    </div>
</div>

<style>
    .border-left-primary {
        border-left: 4px solid #4e73df;
    }
    .border-left-success {
        border-left: 4px solid #1cc88a;
    }
    .border-left-info {
        border-left: 4px solid #36b9cc;
    }
    .border-left-warning {
        border-left: 4px solid #f6c23e;
    }
    .border-left-danger {
        border-left: 4px solid #e74a3b;
    }
    .text-gray-300 {
        color: #dddfeb;
    }
    .text-gray-800 {
        color: #5a5c69;
    }
    /* Effet hover sur les cartes d'infos */
    .hover-effect {
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .hover-effect:hover {
        box-shadow: 0 8px 24px rgba(80, 80, 80, 0.15), 0 1.5px 6px rgba(80, 80, 80, 0.10);
        transform: translateY(-4px) scale(1.03);
        z-index: 2;
    }
</style>

<?php
// Inclure le pied de page
include_once 'includes/footer.php';
?>