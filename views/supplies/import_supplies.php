<?php
/**
 * Import des fournitures depuis un fichier CSV
 * Permet d'importer en masse des fournitures dans le système
 */


// Inclure le fichier de configuration
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$referencePrefix = getAppConfig('referencePrefix') ?? 'PH';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion si non connecté
    redirect('index.php');
}


// Définir le titre de la page
$page_title = "Import de fournitures";

// Définir ROOT_PATH pour le header
if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Variables pour les messages
$message = '';
$message_type = '';
$imported_count = 0;
$errors = [];

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Erreur de validation du formulaire. Veuillez réessayer.";
        $message_type = 'error';
    } else {
        // Vérifier que le fichier est bien un CSV
        $file = $_FILES['csv_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_ext !== 'csv') {
            $message = "Le fichier doit être au format CSV.";
            $message_type = 'error';
        } else {
            // Lire le contenu du fichier
            $handle = fopen($file['tmp_name'], 'r');
            
            // Vérifier si l'ouverture a réussi
            if ($handle !== false) {
                try {
                    $db = getDbConnection();
                    
                    // Commencer une transaction
                    $db->beginTransaction();
                    
                    // Lire la première ligne (en-têtes)
                    $headers = fgetcsv($handle, 1000, ';');
                    // var_dump($headers);
                    
                    // Convertir les en-têtes en UTF-8 si nécessaire
                    if (function_exists('mb_convert_encoding')) {
                        foreach ($headers as &$header) {
                            $header = mb_convert_encoding($header, 'UTF-8', 'UTF-8,ISO-8859-1');
                        }
                    }
                    
                    // Vérifier que les en-têtes attendus sont présents
                    $required_headers = ['designation', 'quantite_stock', 'seuil_alerte']; // Référence n'est plus obligatoire
                    $headers_map = [];
                    
                    // Trouver l'index des colonnes dans le fichier CSV
                    // TODO :  Fixe le  bug de la boucle qui passe deux fois dans seuil_alerte
                    // foreach ($headers as $index => $header) {
                    //     $clean_header = strtolower(trim(str_replace(['"', "'"], '', $header)));
                        
                    //     // Mapper les headers connus
                    //     if ($clean_header === 'designation') {
                    //         $headers_map['designation'] = $index;
                    //     } elseif ($clean_header === 'quantite_stock' || $clean_header === 'quantite' || $clean_header === 'stock') {
                    //         $headers_map['quantite_stock'] = $index;
                    //     } elseif ($clean_header === 'seuil_alerte' || $clean_header === 'seuil' || $clean_header === 'alerte') {
                    //         $headers_map['seuil_alerte'] = $index;
                    //     } elseif ($clean_header === 'description') {
                    //         $headers_map['description'] = $index;
                    //     }
                    //     // Nous ignorons intentionnellement la colonne 'reference'
                    // }
                    
                    //! Solution temporaire pour le bug de la boucle
                    $headers_map['designation'] = 0;
                    $headers_map['quantite_stock'] =1;
                    $headers_map['seuil_alerte'] = 2;
                    $headers_map['description'] = 3;



                    // Vérifier que les en-têtes obligatoires sont présents
                    foreach ($required_headers as $required) {
                        if (!isset($headers_map[$required])) {
                            throw new Exception("La colonne '$required' est manquante dans le fichier CSV.");
                        }
                    }
                    
                    // Récupérer le dernier ID avant l'importation pour générer des références
                    $stmt = $db->query("SELECT MAX(id) FROM FOURNITURE");
                    $last_id = $stmt->fetchColumn() ?: 0;
                    $next_id = $last_id + 1;
                    
                    // Lire et traiter les données ligne par ligne
                    $line_number = 1;
                    $stmt_insert = $db->prepare("INSERT INTO FOURNITURE (reference, designation, description, quantite_stock, seuil_alerte) VALUES (?, ?, ?, 0, ?)");
                    
                    while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                        $line_number++;
                        
                        // Convertir les données en UTF-8 si nécessaire
                        if (function_exists('mb_convert_encoding')) {
                            foreach ($data as &$field) {
                                $field = mb_convert_encoding($field, 'UTF-8', 'UTF-8,ISO-8859-1');
                            }
                        }
                        
                        // Extraire les données selon le mapping des en-têtes
                        $designation = isset($data[$headers_map['designation']]) ? 
                                      trim($data[$headers_map['designation']]) : '';
                        $description = isset($headers_map['description']) && isset($data[$headers_map['description']]) ? 
                                      trim($data[$headers_map['description']]) : '';
                        $quantite_stock = isset($data[$headers_map['quantite_stock']]) ? 
                                        (int)$data[$headers_map['quantite_stock']] : 0;
                        $seuil_alerte = isset($data[$headers_map['seuil_alerte']]) && $data[$headers_map['seuil_alerte']] !== '' ? (int)$data[$headers_map['seuil_alerte']] : null;

                        
                        // Validation des données
                        if (empty($designation)) {
                            $errors[] = "Ligne $line_number : La désignation est obligatoire.";
                            continue;
                        }
                        
                        if ($quantite_stock < 0) {
                            $errors[] = "Ligne $line_number : La quantité ne peut pas être négative.";
                            continue;
                        }
                        
                        if ($seuil_alerte !== null && $seuil_alerte < 0) {
                            $errors[] = "Ligne $line_number : Le seuil d'alerte ne peut pas être négatif.";
                            continue;
                        }
                        
                        // Auto-générer la référence pour toutes les lignes
                        $reference = $referencePrefix . str_pad($next_id, 3, '0', STR_PAD_LEFT);
                        $next_id++;
                        
                        // Insérer la fourniture
                        $stmt_insert->execute([$reference, $designation, $description, $seuil_alerte]);
                        $imported_count++;
                        
                        // Si une quantité initiale est spécifiée, créer un mouvement d'entrée
                        if ($quantite_stock > 0) {
                            $supply_id = $db->lastInsertId();
                            $stmt_movement = $db->prepare("INSERT INTO MOUVEMENT_STOCK (date_mouvement, type, quantite, motif, id_fourniture, id_utilisateur) VALUES (CURRENT_DATE(), 'ENTREE', ?, 'Import initial', ?, ?)");
                            $stmt_movement->execute([$quantite_stock, $supply_id, $_SESSION['user_id']]);
                        }
                    }
                    
                    // Valider la transaction si aucune erreur grave
                    if (empty($errors) || count($errors) < $line_number - 1) {
                        $db->commit();
                        $message = "$imported_count fourniture(s) importée(s) avec succès.";
                        $message_type = 'success';
                    } else {
                        $db->rollBack();
                        $message = "L'importation a échoué en raison de trop nombreuses erreurs.";
                        $message_type = 'error';
                    }
                    
                } catch (Exception $e) {
                    // Annuler la transaction en cas d'exception
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Erreur lors de l\'importation des fournitures: ' . $e->getMessage());
                    $message = "Une erreur est survenue lors de l'importation : " . $e->getMessage();
                    $message_type = 'error';
                }
                
                // Fermer le fichier
                fclose($handle);
                
            } else {
                $message = "Impossible de lire le fichier CSV.";
                $message_type = 'error';
            }
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
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-import me-2"></i>Importer des fournitures</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Message de résultat -->
                    <?php if (!empty($message)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            notifications.<?php echo $message_type; ?>('<?php echo $message_type === 'success' ? 'Succès' : 'Erreur'; ?>', '<?php echo addslashes($message); ?>');
                        });
                    </script>
                    <?php endif; ?>
                    
                    <!-- Instructions -->
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Instructions</h5>
                        <p>Importez des fournitures en masse à partir d'un fichier CSV. Le fichier doit contenir les colonnes suivantes :</p>
                        <ul>
                            <li><strong>designation</strong> : Nom de la fourniture (obligatoire)</li>
                            <li><strong>quantite_stock</strong> : Quantité initiale (défaut: 0)</li>
                            <li><strong>seuil_alerte</strong> : Seuil d'alerte (optionnel)</li>
                            <li><strong>description</strong> : Description détaillée (optionnel)</li>
                        </ul>
                        <p>Les références seront automatiquement générées au format PH001, PH002, etc.</p>
                        <p class="mb-0">Le fichier doit utiliser le point-virgule (;) comme séparateur et être encodé en UTF-8.</p>
                    </div>
                    
                    <!-- Exemple de modèle -->
                    <div class="mb-4">
                        <h6>Modèle de fichier CSV</h6>
                        <div class="bg-light p-3 rounded">
                            <code>designation;quantite_stock;seuil_alerte;description<br>
                            Stylo bleu;100;20;Stylo à bille de couleur bleue<br>
                            Ramette papier A4;50;10;Ramette de 500 feuilles<br>
                            Cahier grand format;75;15;</code>
                        </div>
                        <div class="mt-2">
                            <p class="small text-muted"><i class="fas fa-info-circle me-1"></i> Les références seront automatiquement générées au format PH001, PH002, etc. même si une colonne "reference" est présente dans le fichier.</p>
                            <a href="#" class="btn btn-sm btn-outline-secondary" onclick="downloadTemplate(); return false;">
                                <i class="fas fa-download me-1"></i> Télécharger le modèle
                            </a>
                        </div>
                    </div>
                    
                    <!-- Formulaire d'import -->
                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Token CSRF caché -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">Fichier CSV</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <div class="form-text">Sélectionnez un fichier CSV contenant les données des fournitures à importer.</div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/views/supplies/list.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i> Importer
                            </button>
                        </div>
                    </form>
                    
                    <!-- Affichage des erreurs -->
                    <?php if (!empty($errors)): ?>
                        <div class="mt-4">
                            <h6 class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreurs détectées</h6>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Script spécifique à la page
$page_specific_script = "
    // Fonction pour télécharger le modèle CSV
    function downloadTemplate() {
        const csvContent = 'designation;quantite_stock;seuil_alerte;description\\nStylo bleu;100;20;Stylo à bille de couleur bleue\\nRamette papier A4;50;10;Ramette de 500 feuilles\\nCahier grand format;75;15;';
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', 'modele_import_fournitures.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>