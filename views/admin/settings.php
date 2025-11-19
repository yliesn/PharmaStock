<?php
/**
 * Page de gestion des feature toggles (activation/désactivation de fonctionnalités)
 * Accessible uniquement aux administrateurs
 */

require_once '../../config/config.php';

// Vérifier les droits ADMIN
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'ADMIN') {
    $_SESSION['error_message'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
    redirect('dashboard.php');
}

$page_title = 'Paramètres - Fonctionnalités';

// Définir ROOT_PATH pour l'inclusion de header si nécessaire
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

$message = '';
$message_type = '';

try {
    $db = getDbConnection();
    if (!$db) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérification CSRF
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $message = 'Erreur de validation du formulaire.';
            $message_type = 'error';
        } else {
            // Récupérer toutes les toggles existantes
            $stmt = $db->query("SELECT feature_key FROM FEATURE_TOGGLES");
            $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $updateStmt = $db->prepare("UPDATE FEATURE_TOGGLES SET value = ? WHERE feature_key = ?");

            foreach ($keys as $key) {
                $val = isset($_POST['features']) && in_array($key, $_POST['features']) ? 1 : 0;
                $updateStmt->execute([$val, $key]);
            }

            $message = 'Paramètres enregistrés.';
            $message_type = 'success';
        }
    }

    // Recharger les toggles
    $stmt = $db->query("SELECT * FROM FEATURE_TOGGLES ORDER BY feature_key");
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Erreur page settings: ' . $e->getMessage());
    $message = 'Une erreur est survenue.';
    $message_type = 'error';
    $features = [];
}

// Générer un token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-toggle-on me-2"></i>Paramètres - Fonctionnalités</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            notifications.<?php echo $message_type; ?>(<?php echo json_encode($message_type === 'success' ? 'Succès' : 'Erreur'); ?>, <?php echo json_encode($message); ?>);
                        });
                    </script>
                    <?php endif; ?>

                    <?php if (empty($features)): ?>
                        <div class="alert alert-info">Aucun paramètre trouvé.</div>
                    <?php else: ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="list-group mb-3">
                            <?php foreach ($features as $f): ?>
                                <label class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($f['label']); ?></strong>
                                        <?php if (!empty($f['conditionnement'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($f['conditionnement']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($f['feature_key']); ?>" <?php echo $f['value'] ? 'checked' : ''; ?> class="form-check-input">
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . '/includes/footer.php'; ?>
