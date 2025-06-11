<?php
/**
 * Entrée de stock
 * Permet d'enregistrer une entrée de stock pour une fourniture
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

// Définir le titre de la page
$page_title = "Entrée de stock";

// Récupérer l'ID de la fourniture si fourni
$supply_id = isset($_GET['supply_id']) && is_numeric($_GET['supply_id']) ? (int)$_GET['supply_id'] : null;
$selected_supply = null;

// Récupérer la liste des fournitures
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, reference, designation, quantite_stock, seuil_alerte FROM FOURNITURE ORDER BY reference");
    $supplies = $stmt->fetchAll();
    
    // Si une fourniture spécifique est demandée, récupérer ses détails
    if ($supply_id) {
        $stmt = $db->prepare("SELECT id, reference, designation, quantite_stock, seuil_alerte, commande_en_cours FROM FOURNITURE WHERE id = ?");
        $stmt->execute([$supply_id]);
        $selected_supply = $stmt->fetch();
        
        if (!$selected_supply) {
            $_SESSION['error_message'] = "Fourniture non trouvée.";
            redirect(BASE_URL . '/views/stock/entry.php');
        }
    }
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des fournitures: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération des fournitures.";
    redirect(BASE_URL . '/dashboard.php');
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
        $supply_id = isset($_POST['supply_id']) ? (int)$_POST['supply_id'] : null;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $date = isset($_POST['date']) && !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $motif = isset($_POST['motif']) ? trim($_POST['motif']) : '';
        
        // Validation de base
        $errors = [];
        
        if (!$supply_id) {
            $errors[] = "Veuillez sélectionner une fourniture.";
        }
        
        if ($quantity <= 0) {
            $errors[] = "La quantité doit être supérieure à zéro.";
        }
        
        if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = "La date est invalide.";
        }
        
        if (empty($errors)) {
            try {
                $db = getDbConnection();
                
                // Vérifier que la fourniture existe
                $stmt = $db->prepare("SELECT id FROM FOURNITURE WHERE id = ?");
                $stmt->execute([$supply_id]);
                if (!$stmt->fetch()) {
                    throw new Exception("La fourniture sélectionnée n'existe pas.");
                }
                
                // Insérer le mouvement de stock
                $stmt = $db->prepare("
                    INSERT INTO MOUVEMENT_STOCK (date_mouvement, type, quantite, motif, id_fourniture, id_utilisateur) 
                    VALUES (?, 'ENTREE', ?, ?, ?, ?)
                ");
                $result = $stmt->execute([$date, $quantity, $motif, $supply_id, $_SESSION['user_id']]);
                
                if ($result) {
                    // Mettre à jour le flag commande_en_cours si c'est une réception de commande
                    if (isset($_POST['reception_commande']) && $_POST['reception_commande'] == 1) {
                        $stmt = $db->prepare("UPDATE FOURNITURE SET commande_en_cours = FALSE WHERE id = ?");
                        $stmt->execute([$supply_id]);
                    }
                    $message = "L'entrée de stock a été enregistrée avec succès.";
                    $message_type = 'success';
                    
                    // Récupérer les informations mises à jour
                    if ($supply_id) {
                        $stmt = $db->prepare("SELECT id, reference, designation, quantite_stock, seuil_alerte FROM FOURNITURE WHERE id = ?");
                        $stmt->execute([$supply_id]);
                        $selected_supply = $stmt->fetch();
                    }
                    
                    // Réinitialiser les champs du formulaire
                    $quantity = 0;
                    $date = date('Y-m-d');
                    $motif = '';
                } else {
                    $message = "Une erreur est survenue lors de l'enregistrement de l'entrée de stock.";
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                error_log('Erreur lors de l\'enregistrement de l\'entrée de stock: ' . $e->getMessage());
                $message = "Une erreur est survenue lors de l'enregistrement de l'entrée de stock.";
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
    <!-- Entête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-plus-circle me-2 text-success"></i>Entrée de stock</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/views/stock/movements.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-history me-1"></i> Historique des mouvements
            </a>
            <a href="<?php echo BASE_URL; ?>/views/stock/exit.php" class="btn btn-danger">
                <i class="fas fa-minus-circle me-1"></i> Sortie de stock
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Formulaire d'entrée de stock -->
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Enregistrer une entrée de stock</h5>
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
                    
                    <!-- Informations sur la fourniture sélectionnée -->
                    <?php if ($selected_supply): ?>
                        <div class="alert alert-info mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <h5 class="alert-heading mb-1">
                                        <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($selected_supply['designation']); ?>
                                    </h5>
                                    <p class="mb-0">
                                        <strong>Référence:</strong> <?php echo htmlspecialchars($selected_supply['reference']); ?> | 
                                        <strong>Stock actuel:</strong> <?php echo number_format($selected_supply['quantite_stock'], 0, ',', ' '); ?> unité(s)
                                        <?php if ($selected_supply['seuil_alerte'] && $selected_supply['quantite_stock'] <= $selected_supply['seuil_alerte']): ?>
                                            <span class="badge bg-warning text-dark ms-2">Stock bas</span>
                                        <?php endif; ?>
                                        <?php if ($selected_supply['commande_en_cours']): ?>
                                            <span class="badge bg-success ms-2">Commande en cours</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                    <a href="<?php echo BASE_URL; ?>/views/stock/entry.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Changer
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <!-- Token CSRF caché -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <!-- Sélection de la fourniture si non déjà sélectionnée -->
                        <?php if (!$selected_supply): ?>
                            <!-- Barre de recherche -->
                            <div class="mb-3">
                                <label for="search" class="form-label">Rechercher une fourniture</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" placeholder="Référence ou désignation...">
                                    <button class="btn btn-outline-primary" type="button" id="clearSearch">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="form-text">Tapez pour filtrer la liste des fournitures</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="supply_id" class="form-label">Fourniture <span class="text-danger">*</span></label>
                                <select class="form-select" id="supply_id" name="supply_id" required>
                                    <option value="">Sélectionner une fourniture</option>
                                    <?php foreach ($supplies as $supply): ?>
                                        <option value="<?php echo $supply['id']; ?>" 
                                                data-reference="<?php echo htmlspecialchars(strtolower($supply['reference'])); ?>"
                                                data-designation="<?php echo htmlspecialchars(strtolower($supply['designation'])); ?>"
                                                <?php echo $supply_id == $supply['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supply['reference'] . ' - ' . $supply['designation']); ?>
                                            <?php if ($supply['seuil_alerte'] && $supply['quantite_stock'] <= $supply['seuil_alerte']): ?>
                                                (Stock bas: <?php echo $supply['quantite_stock']; ?>)
                                            <?php else: ?>
                                                (Stock: <?php echo $supply['quantite_stock']; ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner une fourniture.</div>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="supply_id" value="<?php echo $selected_supply['id']; ?>">
                        <?php endif; ?>
                        
                        <!-- Quantité et date -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="quantity" class="form-label">Quantité <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required
                                       value="<?php echo isset($quantity) ? $quantity : ''; ?>">
                                <div class="form-text">Nombre d'unités à ajouter au stock.</div>
                                <div class="invalid-feedback">Veuillez saisir une quantité valide (supérieure à zéro).</div>
                            </div>
                            <div class="col-md-6">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date" required
                                       value="<?php echo isset($date) ? $date : date('Y-m-d'); ?>">
                                <div class="form-text">Date de l'entrée en stock.</div>
                                <div class="invalid-feedback">Veuillez sélectionner une date valide.</div>
                            </div>
                        </div>
                        <!-- Option de réception de commande -->
                        <?php if ($selected_supply && isset($selected_supply['commande_en_cours']) && $selected_supply['commande_en_cours']): ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="reception_commande" name="reception_commande" value="1" checked>
                            <label class="form-check-label" for="reception_commande">
                                <i class="fas fa-truck-loading me-1"></i> Réception de la commande 
                                <span class="text-muted">(la fourniture ne sera plus marquée "commande en cours")</span>
                            </label>
                        </div>
                        <?php endif; ?>

                        <!-- Motif -->
                        <div class="mb-4">
                            <label for="motif" class="form-label">Motif</label>
                            <textarea class="form-control" id="motif" name="motif" rows="2"><?php echo isset($motif) ? htmlspecialchars($motif) : ''; ?><?php if ($selected_supply && isset($selected_supply['commande_en_cours']) && $selected_supply['commande_en_cours']): ?>Réception de la commande<?php endif; ?></textarea>
                            <div class="form-text">Raison ou commentaire concernant cette entrée de stock (optionnel).</div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL . '/views/supplies/list.php'; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour
                            </a>
                            <?php if ($selected_supply){ ?>
                                <a href="<?php echo BASE_URL . '/views/supplies/view.php?id=' . $selected_supply['id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-boxes me-1"></i> Fourniture
                                </a>
                            <?php }?>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus-circle me-1"></i> Enregistrer l'entrée
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
// Script spécifique à la page
$page_specific_script = "
    // Fonction de recherche dans le select
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        const clearButton = document.getElementById('clearSearch');
        const supplySelect = document.getElementById('supply_id');
        
        if (searchInput && supplySelect) {
            searchInput.addEventListener('input', function() {
                const searchText = this.value.toLowerCase().trim();
                
                Array.from(supplySelect.options).forEach(option => {
                    if (option.value === '') return; // Ignorer l'option par défaut
                    
                    const reference = option.getAttribute('data-reference') || '';
                    const designation = option.getAttribute('data-designation') || '';
                    
                    if (reference.includes(searchText) || designation.includes(searchText)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
            
            // Bouton pour effacer la recherche
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    searchInput.value = '';
                    // Réafficher toutes les options
                    Array.from(supplySelect.options).forEach(option => {
                        option.style.display = '';
                    });
                    searchInput.focus();
                });
            }
        }
    });

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
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>