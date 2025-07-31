<?php
// Page de comparaison inventaire : stock théorique vs physique sous forme de cartes
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/auth/login.php');
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
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Comparaison Inventaire</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .card-inventaire {
            border: 1px solid #ccc;
            border-radius: 8px;
            margin: 16px auto;
            padding: 20px;
            max-width: 400px;
            box-shadow: 0 2px 8px #eee;
            background: #fafbfc;
        }
        .card-inventaire h3 {
            margin-top: 0;
        }
        .ecart {
            font-weight: bold;
        }
        .ecart.positif { color: #2e7d32; }
        .ecart.negatif { color: #c62828; }
        .ecart.zero { color: #888; }
    </style>
</head>
<body>
<?php include '../../includes/header.php'; ?>
<h1>Comparaison de l'inventaire du <?= htmlspecialchars(date('d/m/Y H:i', strtotime($inventaire['date_inventaire']))) ?></h1>
<p>Effectué par : <?= htmlspecialchars($inventaire['prenom'] . ' ' . $inventaire['nom']) ?></p>
<?php if (!empty($inventaire['commentaire'])): ?>
    <p><strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($inventaire['commentaire'])) ?></p>
<?php endif; ?>

<?php foreach ($lignes as $ligne):
    if ((int)$ligne['quantite_physique'] === -1) {
?>
    <div class="card-inventaire">
        <h3><?= htmlspecialchars($ligne['designation']) ?> <small>(<?= htmlspecialchars($ligne['reference']) ?>)</small></h3>
        <p><strong>Stock théorique :</strong> <?= (int)$ligne['quantite_theorique'] ?></p>
        <p style="color:#888;"><em>Fourniture passée (non inventoriée)</em></p>
    </div>
<?php
        continue;
    }
    $ecart = (int)$ligne['quantite_physique'] - (int)$ligne['quantite_theorique'];
    $ecart_class = $ecart > 0 ? 'positif' : ($ecart < 0 ? 'negatif' : 'zero');
?>
    <div class="card-inventaire">
        <h3><?= htmlspecialchars($ligne['designation']) ?> <small>(<?= htmlspecialchars($ligne['reference']) ?>)</small></h3>
        <p><strong>Stock théorique :</strong> <?= (int)$ligne['quantite_theorique'] ?></p>
        <p><strong>Quantité physique relevée :</strong> <?= (int)$ligne['quantite_physique'] ?></p>
        <p class="ecart <?= $ecart_class ?>">
            <strong>Écart :</strong> <?= $ecart > 0 ? '+' : '' ?><?= $ecart ?>
        </p>
    </div>
<?php endforeach; ?>

<a href="create.php">&#8592; Faire un nouvel inventaire</a>
<?php include '../../includes/footer.php'; ?>
</body>
</html>
