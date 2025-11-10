<?php
// Page d'accueil améliorée pour les utilisateurs avec le rôle VISITEUR
require_once '../../config/config.php';

// Vérifier si l'utilisateur est bien connecté et a le rôle VISITEUR
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'VISITEUR') {
    redirect(BASE_URL . '/index.php');
    exit;
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

$page_title = "Espace Visiteur";

// Récupération des fournitures
try {
    $db = getDbConnection();
    $sql = "SELECT id, reference, designation, quantite_stock, seuil_alerte, description FROM FOURNITURE ORDER BY designation ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $supplies = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des fournitures: ' . $e->getMessage());
    $supplies = [];
    $error_message = "Une erreur est survenue lors de la récupération des fournitures.";
}

// Vérifier si les approbations sont activées (renvoyera '1' ou '0')
try {
    $stmt = $db->prepare("SELECT value FROM FEATURE_TOGGLES WHERE feature_key = 'enable_approvals' LIMIT 1");
    $stmt->execute();
    $approvals_enabled = (bool) $stmt->fetchColumn();
} catch (Exception $e) {
    $approvals_enabled = false;
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
        /* Simple, très lisible pour utilisateurs non-initiés */
        body { font-size: 1.05rem; }
        .big-btn { padding: .75rem 1.25rem; font-size: 1.05rem; }
        .supply-card { cursor: default; }
        .low-stock { border-left: 4px solid #dc3545; }
        .ok-stock { border-left: 4px solid #28a745; }
        .center-empty { text-align: center; padding: 3rem 1rem; }
        .small-muted { font-size: .9rem; color: #6c757d; }
        /* Table pour les utilisateurs avancés, mais on met des cartes par défaut */
        @media (min-width: 992px) {
            .cards-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-info">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#"><i class="fas fa-capsules me-2"></i><span>PharmaStock</span></a>
    <div class="d-flex ms-auto gap-2">
      <a href="<?= BASE_URL ?>/views/visiteur/approbations.php" class="btn btn-outline-light" aria-label="Approbations"><i class="fas fa-clock me-1"></i> Mes approbations</a>
      <a class="btn btn-outline-light" href="<?= BASE_URL ?>/auth/logout.php" aria-label="Déconnexion"><i class="fas fa-sign-out-alt me-1"></i> Déconnexion</a>
    </div>
  </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1">Espace Visiteur</h3>
                        <p class="small-muted mb-0">Consultez facilement le stock. pour faire une demande de sortie de stock, cliquez sur le bouton rouge associé à l'article.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications handled by assets/js/notifications.js -->

        <div class="col-12 col-md-8">
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Rechercher un produit</h5>
                    <div class="d-flex gap-2">
                        <input id="searchInput" type="search" class="form-control" placeholder="Tapez une référence ou désignation..." aria-label="Rechercher une fourniture">
                        <button id="clearBtn" class="btn btn-outline-secondary">Effacer</button>
                    </div>
                    <div class="form-text small-muted mt-2">Astuce : vous pouvez taper seulement une partie du nom.</div>
                </div>
            </div>

            <div id="cardsContainer" class="cards-grid">
                <?php if (!empty($supplies)) : ?>
                    <?php foreach ($supplies as $supply) :
                        $low = ($supply['seuil_alerte'] !== null && $supply['quantite_stock'] <= $supply['seuil_alerte']);
                    ?>
                    <div class="card supply-card shadow-sm <?= $low ? 'low-stock' : 'ok-stock' ?>" data-reference="<?= htmlspecialchars($supply['reference']) ?>" data-designation="<?= htmlspecialchars($supply['designation']) ?>" data-id="<?= (int)$supply['id'] ?>">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold" style="font-size:1.05rem;"><?= htmlspecialchars($supply['designation']) ?></div>
                                <div class="small-muted"><?= htmlspecialchars($supply['description']) ?></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold" style="font-size:1.25rem;"><?= number_format($supply['quantite_stock'], 0, ',', ' ') ?></div>
                                <div class="small-muted">en stock</div>
                                <div class="mt-2">
                                    <button class="btn btn-danger btn-sm request-btn" data-id="<?= (int)$supply['id'] ?>" data-designation="<?= htmlspecialchars($supply['designation']) ?>" aria-label="Sortie stock pour <?= htmlspecialchars($supply['designation']) ?>">Sortie stock</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card center-empty shadow-sm">
                        <div class="card-body">
                            <h5>Aucun stock disponible</h5>
                            <p class="small-muted">Si vous pensez que c'est une erreur, contactez un administrateur.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title">Informations</h5>
                    <p class="small-muted mb-1">Interface simplifiée pour utilisateurs peu familiers avec l'informatique :</p>
                    <ul>
                        <li>Grandes actions clairement identifiées (bouton rouge pour faire une demande de sortie de stock).</li>
                        <li>Recherche rapide pour trouver un article.</li>
                        <li>Indication visuelle des articles en faible stock (trait rouge).</li>
                    </ul>
                    <hr>
                    <h6 class="mt-2">Besoin d'aide ?</h6>
                    <p class="small-muted">Cliquez sur <strong>Aide rapide</strong> pour afficher un guide pas-à-pas.</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Actions rapides</h5>
                    <p class="small-muted">Pour retirer un article, appuyez sur <span class="badge bg-danger">Sortie stock</span> et suivez les étapes.</p>
                    <div class="d-grid gap-2 mt-2">
                        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary">Retour</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal simple et clair -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form id="requestForm" method="post" action="<?= $approvals_enabled ? '/api/make_approbation.php' : '/api/make_request.php' ?>">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="requestModalLabel"><i class="fas fa-paper-plane me-2"></i> Demande de sortie</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="supply_id" id="modalSupplyId" required>
            <div class="mb-3">
                <label class="form-label fw-bold">Fourniture</label>
                <input type="text" id="modalDesignation" class="form-control-plaintext" disabled>
            </div>
            <div class="mb-3">
                <label for="modalQuantite" class="form-label">Quantité <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="modalQuantite" name="quantite" min="1" required aria-required="true">
            </div>
            <div class="mb-3">
                <label for="modalMotif" class="form-label">Motif <span class="text-danger">*</span></label>
                <textarea id="modalMotif" name="motif" class="form-control" rows="2" required placeholder="Ex : Inter, Manoeuvre, JSP, ..."></textarea>
            </div>
            <div id="modalHelp" class="small-muted">Votre demande sera envoyée au responsable pour validation.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Envoyer la demande</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/notifications.js"></script>
<script>
// Instancier le système de notifications (durée en ms: 8000 pour conserver le comportement précédent)
const notifier = new NotificationSystem({ duration: 5000, position: 'top-right' });

// Recherche simple (client-side)
const searchInput = document.getElementById('searchInput');
const clearBtn = document.getElementById('clearBtn');
const cards = Array.from(document.querySelectorAll('.supply-card'));

function filterCards(){
    const q = searchInput.value.trim().toLowerCase();
    if(!q){
        cards.forEach(c => c.style.display='');
        return;
    }
    cards.forEach(c => {
        const ref = c.getAttribute('data-reference') || '';
        const des = c.getAttribute('data-designation') || '';
        const ok = ref.toLowerCase().includes(q) || des.toLowerCase().includes(q);
        c.style.display = ok ? '' : 'none';
    });
}
searchInput.addEventListener('input', filterCards);
clearBtn.addEventListener('click', ()=>{searchInput.value=''; filterCards(); searchInput.focus();});

// Ouvrir modal et pré-remplir
const requestBtns = Array.from(document.querySelectorAll('.request-btn'));
const requestModal = new bootstrap.Modal(document.getElementById('requestModal'));
requestBtns.forEach(btn => {
    btn.addEventListener('click', function(){
        const id = this.getAttribute('data-id');
        const designation = this.getAttribute('data-designation');
        document.getElementById('modalSupplyId').value = id;
        document.getElementById('modalDesignation').value = designation;
        document.getElementById('modalQuantite').value = 1;
        document.getElementById('modalMotif').value = '';
        requestModal.show();
    });
});

// Validation simple avant envoi (UX)
document.getElementById('requestForm').addEventListener('submit', function(e){
    const q = Number(document.getElementById('modalQuantite').value);
    const m = document.getElementById('modalMotif').value.trim();
    if(!q || q < 1){
        e.preventDefault();
        notifier.error('Erreur', 'Veuillez indiquer une quantité valide.');
        return false;
    }
    if(!m){
        e.preventDefault();
        notifier.error('Erreur', 'Le motif est requis.');
        return false;
    }
    // Optionnel: afficher un petit résumé avant envoi (non bloquant)
    // Laisser le formulaire se soumettre normalement vers l'API serveur
});

// Gérer messages GET success/error venant du backend
(function(){
    const params = new URLSearchParams(window.location.search);
    if(params.has('success')) notifier.success('Succès', 'Votre demande de sortie a bien été envoyée.');
    if(params.has('error')) notifier.error('Erreur', 'Une erreur est survenue lors de l\'envoi de la demande.');
})();
</script>

<?php include_once ROOT_PATH . '/includes/footer.php'; ?>
</body>
</html>
