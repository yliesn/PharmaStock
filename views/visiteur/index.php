<?php
// Page d'accueil pour les utilisateurs avec le rôle VISITEUR
require_once '../../config/config.php';

// Vérifier si l'utilisateur est bien connecté et a le rôle VISITEUR
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'VISITEUR') {
    // Rediriger vers la page de connexion ou d'accueil générale
    redirect(BASE_URL . '/index.php');
    exit;
}

// Définir ROOT_PATH pour l'inclusion éventuelle du footer
if (!defined('ROOT_PATH')) {
    if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}
}

$page_title = "Espace Visiteur";
// Pas d'inclusion du header ici pour éviter la redirection en boucle

// Connexion à la base de données et récupération des fournitures (stock)
try {
    $db = getDbConnection();
    $sql = "SELECT id, reference, designation, quantite_stock, seuil_alerte FROM FOURNITURE";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $supplies = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des fournitures: ' . $e->getMessage());
    $supplies = [];
    $error_message = "Une erreur est survenue lors de la récupération des fournitures.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Visiteur - PharmaStock</title>
    <!-- Bootstraps -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- icon   -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Système de notifications -->
    <script src="<?php echo BASE_URL; ?>/assets/js/notifications.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-info mb-4">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="#"><i class="fas fa-capsules me-2"></i>PharmaStock</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/index.php"><i class="fas fa-home"></i> Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (isset($_GET['success'])): ?>
                <script>document.addEventListener('DOMContentLoaded', function() { notif.success('Succès', 'Votre demande de sortie a bien été envoyée.'); });</script>
            <?php elseif (isset($_GET['error'])): ?>
                <script>document.addEventListener('DOMContentLoaded', function() { notif.error('Erreur', "Une erreur est survenue lors de l'envoi de la demande."); });</script>
            <?php endif; ?>
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-user"></i> Bienvenue dans l'espace Visiteur</h4>
                </div>
                <div class="card-body">
                    <p class="lead">Bienvenue dans l'espace visiteur. Vous pouvez consulter l'état du stock, mais toute demande de sortie doit être validée par un utilisateur autorisé.</p>
                    <ul>
                        <li>Consultez les informations publiques et le niveau de stock des fournitures.</li>
                        <li>Pour retirer du stock, utilisez le bouton « Demander une sortie » : votre demande sera soumise à validation par un responsable.</li>
                        <li>Pour obtenir plus d'accès ou d'informations, contactez un administrateur.</li>
                    </ul>

                    <hr>
                    <h5 class="mt-4">Informations sur le stock</h5>
                    <?php if (isset(
                        $error_message)) : ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($supplies)) : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-info">
                                    <tr>
                                        <th>Référence</th>
                                        <th>Désignation</th>
                                        <th>Stock actuel</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($supplies as $supply) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($supply['reference']) ?></td>
                                            <td><?= htmlspecialchars($supply['designation']) ?></td>
                                            <td class="fw-bold text-end"><?= number_format($supply['quantite_stock'], 0, ',', ' ') ?></td>
                                            <td>
                                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#sortieModal" 
                                                    data-supply-id="<?= $supply['id'] ?>" 
                                                    data-designation="<?= htmlspecialchars($supply['designation']) ?>">
                                                    <i class="fas fa-minus"></i> Demander une sortie
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal de demande de sortie de stock -->
                        <div class="modal fade" id="sortieModal" tabindex="-1" aria-labelledby="sortieModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg">
                              <form method="post" action="/api/make_approbation.php">
                                <div class="modal-header bg-info text-white">
                                  <h5 class="modal-title d-flex align-items-center" id="sortieModalLabel">
                                    <i class="fas fa-paper-plane me-2"></i> Demande de sortie de stock
                                  </h5>
                                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body bg-light">
                                  <input type="hidden" name="supply_id" id="modal-supply-id">
                                  <div class="mb-3">
                                    <label for="modal-designation" class="form-label fw-bold">Fourniture</label>
                                    <input type="text" class="form-control-plaintext ps-2" id="modal-designation" disabled style="font-weight:bold; color:#0d6efd; background:transparent;">
                                  </div>
                                  <div class="mb-3">
                                    <label for="modal-quantite" class="form-label">Quantité <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control border-info" name="quantite" id="modal-quantite" min="1" required>
                                  </div>
                                  <div class="mb-3">
                                    <label for="modal-motif" class="form-label">Motif <span class="text-danger">*</span></label>
                                    <textarea class="form-control border-info" name="motif" id="modal-motif" rows="2" required placeholder="Ex : usage, remplacement, etc."></textarea>
                                  </div>
                                </div>
                                <div class="modal-footer bg-light border-0">
                                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Annuler</button>
                                  <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Envoyer la demande</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>

                        <script>
                        // Remplir le modal avec les infos de la ligne cliquée
                        var sortieModal = document.getElementById('sortieModal');
                        sortieModal.addEventListener('show.bs.modal', function (event) {
                          var button = event.relatedTarget;
                          var supplyId = button.getAttribute('data-supply-id');
                          var designation = button.getAttribute('data-designation');
                          document.getElementById('modal-supply-id').value = supplyId;
                          document.getElementById('modal-designation').value = designation;
                        });
                        </script>
                        <script>
                            // Initialiser le système de notifications
                            const notif = new NotificationSystem({
                                position: 'top-right',
                                duration: 5000
                            });
                        </script>
                    <?php else : ?>
                        <p>Aucun stock disponible.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once ROOT_PATH . '/includes/footer.php';
?>
