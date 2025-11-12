<?php
// Page de comparaison inventaire : stock théorique vs physique sous forme de cartes
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
    $_SESSION['error_message'] = "Inventaire non trouvé.";
    redirect(BASE_URL . '/views/inventaire/create.php');
}

try {
    $db = getDbConnection();
    // Récupérer l'inventaire
    $stmt = $db->prepare("SELECT I.*, U.nom, U.prenom FROM INVENTAIRE I JOIN UTILISATEUR U ON I.utilisateur_id = U.id WHERE I.id = ?");
    $stmt->execute([$id]);
    $inventaire = $stmt->fetch();
    if (!$inventaire) throw new Exception();
    // Récupérer les lignes d'inventaire
    $stmt = $db->prepare("SELECT L.*, F.reference, F.designation FROM INVENTAIRE_LIGNE L JOIN FOURNITURE F ON L.fourniture_id = F.id WHERE L.inventaire_id = ?");
    $stmt->execute([$id]);
    $lignes = $stmt->fetchAll();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Impossible d'afficher l'inventaire.";
    redirect(BASE_URL . '/views/inventaire/create.php');
}
?>
<?php include '../../includes/header.php'; ?>
<div class="container py-4">
    <h1 class="mb-3 text-center">Inventaire du <?= htmlspecialchars(date('d/m/Y H:i', strtotime($inventaire['date_inventaire']))) ?></h1>
    <p class="text-center mb-2">Effectué par : <strong><?= htmlspecialchars($inventaire['prenom'] . ' ' . $inventaire['nom']) ?></strong></p>
    <!-- Correction inventaire -->
    <button type="button" class="center mb-2 btn btn-success sub-btn">Correction </button>

    <?php if (!empty($inventaire['commentaire'])): ?>
        <div class="alert alert-info mx-auto mb-4" style="max-width:600px;">
            <strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($inventaire['commentaire'])) ?>
        </div>
    <?php endif; ?>
    <div class="row g-4 justify-content-center">
    <?php foreach ($lignes as $ligne):
        if ((int)$ligne['quantite_physique'] === -1) {
    ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-secondary">
                <div class="card-body">
                    <h3 class="card-title h5 mb-2"><?= htmlspecialchars($ligne['designation']) ?> <small class="text-muted">(<?= htmlspecialchars($ligne['reference']) ?>)</small></h3>
                    <p class="mb-1"><strong>Stock théorique :</strong> <?= (int)$ligne['quantite_theorique'] ?></p>
                    <p class="text-muted fst-italic">Fourniture passée (non inventoriée)</p>
                </div>
            </div>
        </div>
    <?php
            continue;
        }
        $ecart = (int)$ligne['quantite_physique'] - (int)$ligne['quantite_theorique'];
        $ecart_class = $ecart > 0 ? 'text-success' : ($ecart < 0 ? 'text-danger' : 'text-secondary');
    ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title h5 mb-2"><?= htmlspecialchars($ligne['designation']) ?> <small class="text-muted">(<?= htmlspecialchars($ligne['reference']) ?>)</small></h3>
                    <p class="mb-1"><strong>Stock théorique :</strong> <?= (int)$ligne['quantite_theorique'] ?></p>
                    <p class="mb-1"><strong>Quantité physique relevée :</strong> <?= (int)$ligne['quantite_physique'] ?></p>
                    <p class="fw-bold <?= $ecart_class ?>">
                        <strong>Écart :</strong> <?= $ecart > 0 ? '+' : '' ?><?= $ecart ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="create.php" class="btn btn-outline-primary">&#8592; Faire un nouvel inventaire</a>
    </div>
    <script>
    document.querySelector('.sub-btn').addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir corriger le stock en fonction de cet inventaire ? Cette action est irréversible.')) {
            window.location.href = 'corriger_stock.php?id=<?= $id ?>';
        }
    });
    </script>
</div>
<?php include '../../includes/footer.php'; ?>

