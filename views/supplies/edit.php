<?php
/**
 * Modification d'une fourniture
 * Permet de modifier les informations d'une fourniture existante
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Définir ROOT_PATH pour le header
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Vérifier si l'ID de la fourniture est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Identifiant de fourniture invalide.";
    redirect(BASE_URL . '/views/supplies/list.php');
}

$supply_id = (int)$_GET['id'];

// Récupérer les informations de la fourniture
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, reference, designation, description, quantite_stock, seuil_alerte FROM FOURNITURE WHERE id = ?");
    $stmt->execute([$supply_id]);
    $supply = $stmt->fetch();

    if (!$supply) {
        $_SESSION['error_message'] = "Fourniture non trouvée.";
        redirect(BASE_URL . '/views/supplies/list.php');
    }
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des données de fourniture: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération des données.";
    redirect(BASE_URL . '/views/supplies/list.php');
}

// Définir le titre de la page
$page_title = "Modifier : " . $supply['designation'];

// Traitement du formulaire si soumis
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Erreur de validation du formulaire. Veuillez réessayer.";
        $message_type = 'error';
    } else {
        // Récupérer et valider les données du formulaire
        $reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
        $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $seuil_alerte = isset($_POST['seuil_alerte']) && $_POST['seuil_alerte'] !== '' ? (int)$_POST['seuil_alerte'] : null;
        
        // Validation de base
        $errors = [];
        
        if (empty($reference)) {
            $errors[] = "La référence est obligatoire.";
        }
        
        if (empty($designation)) {
            $errors[] = "La désignation est obligatoire.";
        }
        
        if ($seuil_alerte !== null && $seuil_alerte < 0) {
            $errors[] = "Le seuil d'alerte ne peut pas être négatif.";
        }
        
        if (empty($errors)) {
            try {
                // Vérifier si la référence existe déjà pour une autre fourniture
                $stmt = $db->prepare("SELECT COUNT(*) FROM FOURNITURE WHERE reference = ? AND id != ?");
                $stmt->execute([$reference, $supply_id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $message = "Cette référence existe déjà. Veuillez en choisir une autre.";
                    $message_type = 'error';
                } else {
                    // Mettre à jour la fourniture
                    $stmt = $db->prepare("UPDATE FOURNITURE SET reference = ?, designation = ?, description = ?, seuil_alerte = ? WHERE id = ?");
                    $result = $stmt->execute([$reference, $designation, $description, $seuil_alerte, $supply_id]);
                    
                    if ($result) {
                        $message = "La fourniture a été modifiée avec succès.";
                        $message_type = 'success';
                        
                        // Mettre à jour les données affichées
                        $supply['reference'] = $reference;
                        $supply['designation'] = $designation;
                        $supply['description'] = $description;
                        $supply['seuil_alerte'] = $seuil_alerte;
                    } else {
                        $message = "Une erreur est survenue lors de la modification de la fourniture.";
                        $message_type = 'error';
                    }
                }
            } catch (Exception $e) {
                error_log('Erreur lors de la modification d\'une fourniture: ' . $e->getMessage());
                $message = "Une erreur est survenue lors de la modification de la fourniture.";
                $message_type = 'error';
            }
        } else {
            $message = implode("<br>", $errors);
            $message_type = 'error';
        }
    }
}

// Générer un nouveau token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Modifier une fourniture</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Script pour afficher les notifications -->
                    <?php if (!empty($message)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            notifications.<?php echo $message_type; ?>('<?php echo $message_type === 'success' ? 'Succès' : 'Erreur'; ?>', '<?php echo addslashes($message); ?>');
                        });
                    </script>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <!-- Token CSRF caché -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <!-- Référence et Désignation -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="reference" class="form-label">Référence <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reference" name="reference" required 
                                       value="<?php echo htmlspecialchars($supply['reference']); ?>">
                                <div class="form-text">Code unique identifiant la fourniture.</div>
                                <div class="invalid-feedback">Veuillez saisir une référence.</div>
                            </div>
                            <div class="col-md-8">
                                <label for="designation" class="form-label">Désignation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="designation" name="designation" required
                                       value="<?php echo htmlspecialchars($supply['designation']); ?>">
                                <div class="form-text">Nom ou libellé de la fourniture.</div>
                                <div class="invalid-feedback">Veuillez saisir une désignation.</div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Conditionnement</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($supply['description']); ?></textarea>
                            <div class="form-text">Conditionnement de la fourniture (optionnel).</div>
                        </div>
                        
                        <!-- Quantité en stock (affichage uniquement) et Seuil d'alerte -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Quantité en stock</label>
                                <input type="text" class="form-control" value="<?php echo number_format($supply['quantite_stock'], 0, ',', ' '); ?>" readonly>
                                <div class="form-text">
                                    Pour modifier le stock, utilisez les options 
                                    <a href="<?php echo BASE_URL; ?>/views/stock/entry.php?supply_id=<?php echo $supply_id; ?>">Entrée</a> ou 
                                    <a href="<?php echo BASE_URL; ?>/views/stock/exit.php?supply_id=<?php echo $supply_id; ?>">Sortie</a>.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="seuil_alerte" class="form-label">Seuil d'alerte</label>
                                <input type="number" class="form-control" id="seuil_alerte" name="seuil_alerte" min="0"
                                       value="<?php echo $supply['seuil_alerte']; ?>">
                                <div class="form-text">Niveau de stock minimal avant alerte (optionnel).</div>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Carte d'informations sur les mouvements de stock -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Pour ajuster la quantité en stock, veuillez utiliser les opérations d'entrée ou de sortie de stock.
                        Cela permettra de conserver un historique précis des mouvements.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Script spécifique à la page
$page_specific_script = "
    // Validation du formulaire côté client
    (function () {
        'use strict';
        
        // Récupérer tous les formulaires auxquels nous voulons appliquer des styles de validation Bootstrap personnalisés
        var forms = document.querySelectorAll('.needs-validation');
        
        // Boucle pour empêcher la soumission et appliquer la validation
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
    
    // Conversion de la référence en majuscules
    document.getElementById('reference').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>