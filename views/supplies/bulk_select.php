<?php
require_once '../../config/config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'ADMIN') {
    die('Accès refusé');
}

define('ROOT_PATH', dirname(dirname(__DIR__)));
$page_title = "Générer des codes-barres en lot";
include_once ROOT_PATH . '/includes/header.php';

$db = getDbConnection();
$supplies = $db->query("SELECT id, reference, designation FROM FOURNITURE ORDER BY designation")->fetchAll();
?>
<div class="container mt-4">
    <h1 class="h3 mb-4"><i class="fas fa-barcode me-2"></i>Générer des codes-barres en lot</h1>
    <form id="bulk-barcode-form" method="post" action="bulk_barcodes.php" target="_blank">
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-barcode me-1"></i> Générer les codes-barres sélectionnés
            </button>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="select-all"></th>
                                <th>Référence</th>
                                <th>Désignation</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($supplies as $supply): ?>
                            <tr>
                                <td><input type="checkbox" name="supply_ids[]" value="<?php echo $supply['id']; ?>"></td>
                                <td><?php echo htmlspecialchars($supply['reference']); ?></td>
                                <td><?php echo htmlspecialchars($supply['designation']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
document.getElementById('select-all').addEventListener('change', function() {
    document.querySelectorAll('input[name="supply_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
<?php include_once ROOT_PATH . '/includes/footer.php'; ?>
