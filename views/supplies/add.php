<?php
/**
 * Ajout d'une fourniture
 * Permet d'ajouter une nouvelle fourniture dans le système
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$referencePrefix = getAppConfig('referencePrefix') ?? 'PH';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Définir le titre de la page
$page_title = "Ajouter une fourniture";

// Définir ROOT_PATH pour le header
if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

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
        $reference = isset($_POST['reference']) && $_POST['reference'] !== 'Auto-généré' ? trim($_POST['reference']) : '';
        $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $quantite_initiale = isset($_POST['quantite_initiale']) ? (int)$_POST['quantite_initiale'] : 0;
        $seuil_alerte = isset($_POST['seuil_alerte']) && $_POST['seuil_alerte'] !== '' ? (int)$_POST['seuil_alerte'] : null;
        
        // Validation de base
        $errors = [];
        
        if (empty($reference) && $_POST['reference'] !== 'Auto-généré') {
            $errors[] = "La référence est obligatoire.";
        }
        
        if (empty($designation)) {
            $errors[] = "La désignation est obligatoire.";
        }
        
        if ($quantite_initiale < 0) {
            $errors[] = "La quantité initiale ne peut pas être négative.";
        }
        
        if ($seuil_alerte !== null && $seuil_alerte < 0) {
            $errors[] = "Le seuil d'alerte ne peut pas être négatif.";
        }
        
        if (empty($errors)) {
            try {
                $db = getDbConnection();
                
                // Vérifier si la référence existe déjà (uniquement si personnalisée)
                if (!empty($reference)) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM FOURNITURE WHERE reference = ?");
                    $stmt->execute([$reference]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $message = "Cette référence existe déjà. Veuillez en choisir une autre.";
                        $message_type = 'error';
                        // Sortir du bloc try pour afficher l'erreur
                        throw new Exception("Référence dupliquée");
                    }
                }
                
                // Commencer une transaction
                $db->beginTransaction();
                
                // Si référence vide ou auto-générée, générer une référence automatique
                if (empty($reference) || $_POST['reference'] === 'Auto-généré') {
                    // Récupérer le dernier ID
                    $stmt = $db->query("SELECT MAX(id) FROM FOURNITURE");
                    $last_id = $stmt->fetchColumn();
                    
                    if (!$last_id) {
                        $next_id = 1;
                    } else {
                        $next_id = $last_id + 1;
                    }
                    
                    // Format PH001, PH002, etc.
                    $reference = $referencePrefix . str_pad($next_id, 3, '0', STR_PAD_LEFT);
                }
                
                // Insérer la nouvelle fourniture
                $stmt = $db->prepare("INSERT INTO FOURNITURE (reference, designation, description, quantite_stock, seuil_alerte) VALUES (?, ?, ?, 0, ?)");
                $result = $stmt->execute([$reference, $designation, $description, $seuil_alerte]);
                
                if ($result) {
                    $supply_id = $db->lastInsertId();
                    
                    // Si une quantité initiale est spécifiée, créer un mouvement d'entrée
                    if ($quantite_initiale > 0) {
                        $stmt = $db->prepare("INSERT INTO MOUVEMENT_STOCK (date_mouvement, type, quantite, motif, id_fourniture, id_utilisateur) VALUES (CURRENT_DATE(), 'ENTREE', ?, 'Réapprovisionnement initial', ?, ?)");
                        $movement_result = $stmt->execute([$quantite_initiale, $supply_id, $_SESSION['user_id']]);
                        
                        if (!$movement_result) {
                            throw new Exception("Erreur lors de la création du mouvement de stock initial.");
                        }
                    }
                    
                    // Valider la transaction
                    $db->commit();
                    
                    $message = "La fourniture a été ajoutée avec succès (référence: " . $reference . ").";
                    $message_type = 'success';
                    
                    // Réinitialiser les données du formulaire après succès
                    $reference = 'Auto-généré';
                    $designation = $description = '';
                    $quantite_initiale = 0;
                    $seuil_alerte = null;
                } else {
                    // Annuler la transaction en cas d'erreur
                    $db->rollBack();
                    $message = "Une erreur est survenue lors de l'ajout de la fourniture.";
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                // Annuler la transaction en cas d'exception
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Erreur lors de l\'ajout d\'une fourniture: ' . $e->getMessage());
                if ($message_type !== 'error') {  // Ne pas écraser le message d'erreur spécifique déjà défini
                    $message = "Une erreur est survenue lors de l'ajout de la fourniture.";
                    $message_type = 'error';
                }
            }
        } else {
            $message = implode("<br>", $errors);
            $message_type = 'error';
        }
    }
}

// Générer un nouveau token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Par défaut, la référence est auto-générée
$reference = isset($reference) ? $reference : 'Auto-généré';

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-box-open me-2"></i>Ajouter une nouvelle fourniture</h5>
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
                                <label for="reference" class="form-label">Référence</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="reference" name="reference" 
                                           value="<?php echo htmlspecialchars($reference); ?>" <?php echo $reference === 'Auto-généré' ? 'readonly' : ''; ?>>
                                    <button class="btn btn-outline-secondary" type="button" id="customRefBtn">
                                        <?php echo $reference === 'Auto-généré' ? 'Personnaliser' : 'Auto-générer'; ?>
                                    </button>
                                </div>
                                <div class="form-text">Code unique identifiant la fourniture. Par défaut: <?php echo $referencePrefix; ?> + numéro automatique.</div>
                            </div>
                            <div class="col-md-8">
                                <label for="designation" class="form-label">Désignation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="designation" name="designation" required
                                       value="<?php echo isset($designation) ? htmlspecialchars($designation) : ''; ?>">
                                <div class="form-text">Nom ou libellé de la fourniture.</div>
                                <div class="invalid-feedback">Veuillez saisir une désignation.</div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                            <div class="form-text">Description détaillée de la fourniture (optionnel).</div>
                        </div>
                        
                        <!-- Quantité initiale et Seuil d'alerte -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="quantite_initiale" class="form-label">Quantité initiale</label>
                                <input type="number" class="form-control" id="quantite_initiale" name="quantite_initiale" min="0"
                                       value="<?php echo isset($quantite_initiale) ? $quantite_initiale : '0'; ?>">
                                <div class="form-text">Quantité disponible à l'ajout.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="seuil_alerte" class="form-label">Seuil d'alerte</label>
                                <input type="number" class="form-control" id="seuil_alerte" name="seuil_alerte" min="0"
                                       value="<?php echo isset($seuil_alerte) ? $seuil_alerte : ''; ?>">
                                <div class="form-text">Niveau de stock minimal avant alerte (optionnel).</div>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
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
    
    // Gestion de la référence personnalisée
    document.getElementById('customRefBtn').addEventListener('click', function() {
        const refInput = document.getElementById('reference');
        
        if (refInput.readOnly) {
            refInput.readOnly = false;
            refInput.value = '';
            this.textContent = 'Auto-générer';
            refInput.focus();
        } else {
            refInput.readOnly = true;
            refInput.value = 'Auto-généré';
            this.textContent = 'Personnaliser';
        }
    });
    
    // Conversion de la référence en majuscules (uniquement si elle est personnalisée)
    document.getElementById('reference').addEventListener('input', function() {
        if (!this.readOnly) {
            this.value = this.value.toUpperCase();
        }
    });
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>