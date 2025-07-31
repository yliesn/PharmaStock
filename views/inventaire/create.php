<?php
// Page de création d'un inventaire
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect('/auth/login.php');
}

// Récupérer la liste des fournitures
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, reference, designation, quantite_stock FROM FOURNITURE ORDER BY designation ASC");
    $fournitures = $stmt->fetchAll();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des fournitures.";
    redirect('/views/supplies/list.php');
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantites_physiques = $_POST['quantite_physique'] ?? [];
    $commentaire = trim($_POST['commentaire'] ?? '');
    $utilisateur_id = $_SESSION['user_id'];

    try {
        $db->beginTransaction();
        // Créer l'inventaire
        $stmt = $db->prepare("INSERT INTO INVENTAIRE (date_inventaire, utilisateur_id, commentaire) VALUES (NOW(), ?, ?)");
        $stmt->execute([$utilisateur_id, $commentaire]);
        $inventaire_id = $db->lastInsertId();

        // Insérer les lignes d'inventaire
        foreach ($fournitures as $fourniture) {
            $fid = $fourniture['id'];
            $qte_theorique = $fourniture['quantite_stock'];
            
            // Si le champ est vide ou null, on met -1
            if (isset($quantites_physiques[$fid]) && $quantites_physiques[$fid] !== '' && $quantites_physiques[$fid] !== null) {
                $qte_physique = (int)$quantites_physiques[$fid];
            } else {
                $qte_physique = -1;
            }
            
            $stmtLigne = $db->prepare("INSERT INTO INVENTAIRE_LIGNE (inventaire_id, fourniture_id, quantite_theorique, quantite_physique) VALUES (?, ?, ?, ?)");
            $stmtLigne->execute([$inventaire_id, $fid, $qte_theorique, $qte_physique]);
        }
        $db->commit();
        $_SESSION['success_message'] = "Inventaire enregistré avec succès.";
        redirect('/views/inventaire/compare.php?id=' . $inventaire_id);
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Erreur lors de l'enregistrement de l'inventaire.";
    }
}
?>
<?php include '../../includes/header.php'; ?>
<div class="container py-4">
    <h1 class="mb-4 text-center">Nouvel inventaire</h1>
    <form method="post" id="inventaireForm">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <?php foreach ($fournitures as $i => $fourniture): ?>
                    <div class="card card-inventaire mb-4<?= $i === 0 ? ' d-block' : ' d-none' ?>" data-index="<?= $i ?>">
                        <div class="card-body">
                            <h3 class="card-title h5 mb-3"><?= htmlspecialchars($fourniture['designation']) ?> <small class="text-muted">(<?= htmlspecialchars($fourniture['reference']) ?>)</small></h3>
                            <p class="mb-2"><strong>Stock théorique :</strong> <?= (int)$fourniture['quantite_stock'] ?></p>
                            <div class="mb-3">
                                <label class="form-label"><strong>Quantité physique relevée :</strong></label>
                                <input class="form-control qte-input" type="number" name="quantite_physique[<?= $fourniture['id'] ?>]" min="0" placeholder="Laisser vide pour passer">
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <button type="button" class="btn btn-outline-secondary prev-btn" <?= $i === 0 ? 'disabled' : '' ?>>Précédent</button>
                                <button type="button" class="btn btn-primary next-btn" <?= $i === count($fournitures) - 1 ? 'style=\"display:none\"' : '' ?>>Suivant</button>
                                <?php if ($i === count($fournitures) - 1): ?>
                                    <button type="submit" class="btn btn-success submit-btn">Enregistrer l'inventaire</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="mb-3">
                    <label for="commentaire" class="form-label">Commentaire (optionnel)&nbsp;:</label>
                    <textarea id="commentaire" name="commentaire" class="form-control" rows="2" placeholder="Ajouter un commentaire..."></textarea>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    const cards = document.querySelectorAll('.card-inventaire');
    let current = 0;
    function showCard(idx) {
        cards.forEach((c, i) => {
            c.classList.toggle('d-block', i === idx);
            c.classList.toggle('d-none', i !== idx);
        });
    }
    document.querySelectorAll('.next-btn').forEach((btn, i) => {
        btn.addEventListener('click', function() {
            if (current < cards.length - 1) {
                current++;
                showCard(current);
            }
        });
    });
    document.querySelectorAll('.prev-btn').forEach((btn, i) => {
        btn.addEventListener('click', function() {
            if (current > 0) {
                current--;
                showCard(current);
            }
        });
    });
</script>
<?php include '../../includes/footer.php'; ?>
