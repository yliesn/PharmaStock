<?php
require_once '../../config/config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'ADMIN') {
    die('Accès refusé');
}
if (empty($_POST['supply_ids']) || !is_array($_POST['supply_ids'])) {
    die('Aucune fourniture sélectionnée.');
}
$db = getDbConnection();
$ids = array_map('intval', $_POST['supply_ids']);
$in = implode(',', $ids);
$stmt = $db->query("SELECT reference, designation FROM FOURNITURE WHERE id IN ($in)");
$supplies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Codes-barres en lot</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        .barcode-block { display:inline-block; margin:20px; text-align:center; }
        @media print {
            body { background: #fff; }
            .barcode-block { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <?php foreach ($supplies as $supply): ?>
        <div class="barcode-block">
            <svg class="barcode"
                jsbarcode-format="CODE128"
                jsbarcode-value="<?php echo htmlspecialchars($supply['reference']); ?>"
                jsbarcode-textmargin="0"
                jsbarcode-width="1"
                jsbarcode-height="30"
                jsbarcode-margin="10"
                jsbarcode-text="<?php echo htmlspecialchars($supply['designation']); ?>"
            ></svg>
        </div>
    <?php endforeach; ?>
    <script>
        JsBarcode(".barcode").init();
        window.print(); // Ouvre la boîte de dialogue d'impression automatiquement
    </script>
</body>
</html>
