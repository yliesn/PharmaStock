<?php
// Page de création d'un inventaire
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/auth/login.php');
}

// Récupérer la liste des fournitures
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, reference, designation, quantite_stock FROM FOURNITURE ORDER BY designation ASC");
    $fournitures = $stmt->fetchAll();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des fournitures.";
    redirect(BASE_URL . '/views/supplies/list.php');
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
        redirect(BASE_URL . '/views/inventaire/compare.php?id=' . $inventaire_id);
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Erreur lors de l'enregistrement de l'inventaire.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvel inventaire</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .card-inventaire {
            border: 1px solid #ccc;
            border-radius: 8px;
            margin: 40px auto 16px auto;
            padding: 20px;
            max-width: 400px;
            box-shadow: 0 2px 8px #eee;
            background: #fafbfc;
            display: none;
        }
        .card-inventaire.active {
            display: block;
        }
        .card-inventaire h3 {
            margin-top: 0;
        }
        .qte-input {
            width: 80px;
            font-size: 1.1em;
            padding: 4px;
        }
        .slide-controls {
            text-align: center;
            margin-bottom: 20px;
        }
        .slide-controls button {
            margin: 0 10px;
            padding: 8px 20px;
            font-size: 1em;
            border-radius: 6px;
            border: 1px solid #1976d2;
            background: #1976d2;
            color: #fff;
            cursor: pointer;
        }
        .slide-controls button:disabled {
            background: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<?php include '../../includes/header.php'; ?>
<h1>Nouvel inventaire</h1>
<form method="post" id="inventaireForm">
    <?php foreach ($fournitures as $i => $fourniture): ?>
        <div class="card-inventaire<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
            <h3><?= htmlspecialchars($fourniture['designation']) ?> <small>(<?= htmlspecialchars($fourniture['reference']) ?>)</small></h3>
            <p><strong>Stock théorique :</strong> <?= (int)$fourniture['quantite_stock'] ?></p>
            <label>
                <strong>Quantité physique relevée :</strong>
                <input class="qte-input" type="number" name="quantite_physique[<?= $fourniture['id'] ?>]" min="0" placeholder="Laisser vide pour passer">
            </label>
            <div class="slide-controls">
                <button type="button" class="prev-btn" <?= $i === 0 ? 'disabled' : '' ?>>Précédent</button>
                <button type="button" class="next-btn" <?= $i === count($fournitures) - 1 ? 'style="display:none"' : '' ?>>Suivant</button>
                <?php if ($i === count($fournitures) - 1): ?>
                    <button type="submit" class="submit-btn">Enregistrer l'inventaire</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <p style="text-align:center">
        <label>Commentaire (optionnel) :<br>
            <textarea name="commentaire" rows="2" cols="60"></textarea>
        </label>
    </p>
</form>
<script>
    const cards = document.querySelectorAll('.card-inventaire');
    let current = 0;
    
    function showCard(idx) {
        cards.forEach((c, i) => {
            c.classList.toggle('active', i === idx);
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
</body>
</html>