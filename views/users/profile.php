<?php
/**
 * Profil de l'utilisateur
 * Permet à un utilisateur de consulter et modifier ses informations personnelles
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
$page_title = "Mon profil";

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, nom, prenom, login, role, date_derniere_connexion FROM UTILISATEUR WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération de vos informations.";
        redirect(BASE_URL . '/dashboard.php');
    }
} catch (Exception $e) {
    error_log('Erreur lors de la récupération du profil: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération de vos informations.";
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
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action === 'update_info') {
            // Mise à jour des informations personnelles
            $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
            $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
            
            // Validation
            $errors = [];
            
            if (empty($nom)) {
                $errors[] = "Le nom est obligatoire.";
            }
            
            if (empty($prenom)) {
                $errors[] = "Le prénom est obligatoire.";
            }
            
            if (empty($errors)) {
                try {
                    $stmt = $db->prepare("UPDATE UTILISATEUR SET nom = ?, prenom = ? WHERE id = ?");
                    $result = $stmt->execute([$nom, $prenom, $user_id]);
                    
                    if ($result) {
                        // Mettre à jour les informations de session
                        $_SESSION['user_nom'] = $nom;
                        $_SESSION['user_prenom'] = $prenom;
                        
                        // Mettre à jour les informations utilisateur pour l'affichage
                        $user['nom'] = $nom;
                        $user['prenom'] = $prenom;
                        
                        $message = "Vos informations ont été mises à jour avec succès.";
                        $message_type = 'success';
                    } else {
                        $message = "Une erreur est survenue lors de la mise à jour de vos informations.";
                        $message_type = 'error';
                    }
                } catch (Exception $e) {
                    error_log('Erreur lors de la mise à jour du profil: ' . $e->getMessage());
                    $message = "Une erreur est survenue lors de la mise à jour de vos informations.";
                    $message_type = 'error';
                }
            } else {
                $message = implode("<br>", $errors);
                $message_type = 'error';
            }
        } elseif ($action === 'change_password') {
            // Changement de mot de passe
            $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Validation
            $errors = [];
            
            if (empty($current_password)) {
                $errors[] = "Le mot de passe actuel est obligatoire.";
            }
            
            if (empty($new_password)) {
                $errors[] = "Le nouveau mot de passe est obligatoire.";
            } elseif (strlen($new_password) < 8) {
                $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = "Les mots de passe ne correspondent pas.";
            }
            
            if (empty($errors)) {
                try {
                    // Vérifier le mot de passe actuel
                    $stmt = $db->prepare("SELECT mot_de_passe FROM UTILISATEUR WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_password = $stmt->fetchColumn();
                    
                    if (!$user_password || !password_verify($current_password, $user_password)) {
                        $message = "Le mot de passe actuel est incorrect.";
                        $message_type = 'error';
                    } else {
                        // Mettre à jour le mot de passe
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        $stmt = $db->prepare("UPDATE UTILISATEUR SET mot_de_passe = ? WHERE id = ?");
                        $result = $stmt->execute([$hashed_password, $user_id]);
                        
                        if ($result) {
                            $message = "Votre mot de passe a été modifié avec succès.";
                            $message_type = 'success';
                        } else {
                            $message = "Une erreur est survenue lors de la modification de votre mot de passe.";
                            $message_type = 'error';
                        }
                    }
                } catch (Exception $e) {
                    error_log('Erreur lors du changement de mot de passe: ' . $e->getMessage());
                    $message = "Une erreur est survenue lors de la modification de votre mot de passe.";
                    $message_type = 'error';
                }
            } else {
                $message = implode("<br>", $errors);
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
        <div class="col-lg-8 mx-auto">
        
            <!-- Script pour afficher les notifications -->
            <?php if (!empty($message)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    notifications.<?php echo $message_type; ?>('<?php echo $message_type === 'success' ? 'Succès' : 'Erreur'; ?>', '<?php echo addslashes($message); ?>');
                });
            </script>
            <?php endif; ?>
            
            <!-- Entête de page -->
            <div class="d-flex align-items-center mb-4">
                <h1 class="h3 mb-0"><i class="fas fa-user-circle me-2"></i>Mon profil</h1>
            </div>
            
            <!-- Informations du profil -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-3 text-center">
                            <div class="avatar-circle mb-3">
                                <span class="initials">
                                    <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
                            <p class="text-muted mb-1"><i class="fas fa-id-badge me-2"></i><?php echo htmlspecialchars($user['login']); ?></p>
                            <p class="text-muted mb-1"><i class="fas fa-user-tag me-2"></i>
                                <?php 
                                if ($user['role'] === 'ADMIN') {
                                    echo '<span class="badge bg-danger">Administrateur</span>';
                                } else {
                                    echo '<span class="badge bg-info">Utilisateur</span>';
                                }
                                ?>
                            </p>
                            <p class="text-muted mb-0"><i class="fas fa-clock me-2"></i>Dernière connexion : 
                                <?php 
                                if ($user['date_derniere_connexion']) {
                                    echo date('d/m/Y', strtotime($user['date_derniere_connexion']));
                                } else {
                                    echo '<span class="text-muted">Jamais</span>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <form method="POST" action="" class="mt-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="update_info">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Mettre à jour mes informations
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Changement de mot de passe -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Changer mon mot de passe</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="password-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-1"></i> Changer mon mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    background-color: #0d6efd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
.initials {
    font-size: 42px;
    color: white;
    font-weight: bold;
}
</style>

<?php
// Script spécifique à la page
$page_specific_script = "
    // Afficher/masquer le mot de passe
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Validation du mot de passe
    const passwordForm = document.getElementById('password-form');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    passwordForm.addEventListener('submit', function(e) {
        if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            notifications.error('Erreur', 'Les mots de passe ne correspondent pas.');
        }
    });
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>