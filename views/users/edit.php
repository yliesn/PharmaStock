<?php
/**
 * Modification d'un utilisateur
 * Permet à un administrateur de modifier les informations d'un utilisateur existant
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté et a les droits d'administrateur
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'ADMIN') {
    // Rediriger vers le tableau de bord avec un message d'erreur
    $_SESSION['error_message'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
    redirect('dashboard.php');
}

// Définir ROOT_PATH pour le header
if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Vérifier si l'ID de l'utilisateur est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Identifiant d'utilisateur invalide.";
    redirect(BASE_URL . '/views/users/list.php');
}

$user_id = (int)$_GET['id'];

// Récupérer les informations de l'utilisateur
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, nom, prenom, login, role, actif FROM UTILISATEUR WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error_message'] = "Utilisateur non trouvé.";
        redirect(BASE_URL . '/views/users/list.php');
    }
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des données utilisateur: ' . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération des données.";
    redirect(BASE_URL . '/views/users/list.php');
}

// Définir le titre de la page
$page_title = "Modifier l'utilisateur : " . $user['prenom'] . ' ' . $user['nom'];

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
        $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
        $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
        $login = isset($_POST['login']) ? trim($_POST['login']) : '';
        $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
        $confirmer_mdp = isset($_POST['confirmer_mdp']) ? $_POST['confirmer_mdp'] : '';
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        // Validation de base
        $errors = [];
        
        if (empty($nom)) {
            $errors[] = "Le nom est obligatoire.";
        }
        
        if (empty($prenom)) {
            $errors[] = "Le prénom est obligatoire.";
        }
        
        if (empty($login)) {
            $errors[] = "L'identifiant est obligatoire.";
        }
        
        if (!empty($mot_de_passe) && strlen($mot_de_passe) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        }
        
        if (!empty($mot_de_passe) && $mot_de_passe !== $confirmer_mdp) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        
        if (empty($role) || !in_array($role, ['ADMIN', 'UTILISATEUR', 'VISITEUR'])) {
            $errors[] = "Le rôle sélectionné n'est pas valide.";
        }
        
        // Bloquer la désactivation de son propre compte
        if ($user_id == $_SESSION['user_id'] && !$actif) {
            $errors[] = "Vous ne pouvez pas désactiver votre propre compte.";
        }
        
        if (empty($errors)) {
            try {
                $db = getDbConnection();
                
                // Vérifier si le login existe déjà pour un autre utilisateur
                $stmt = $db->prepare("SELECT COUNT(*) FROM UTILISATEUR WHERE login = ? AND id != ?");
                $stmt->execute([$login, $user_id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $message = "Cet identifiant existe déjà. Veuillez en choisir un autre.";
                    $message_type = 'error';
                } else {
                    // Préparer la requête de mise à jour (avec ou sans mot de passe)
                    if (!empty($mot_de_passe)) {
                        // Hasher le nouveau mot de passe
                        $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                        
                        $stmt = $db->prepare("UPDATE UTILISATEUR SET nom = ?, prenom = ?, login = ?, mot_de_passe = ?, role = ?, actif = ? WHERE id = ?");
                        $result = $stmt->execute([$nom, $prenom, $login, $hashed_password, $role, $actif, $user_id]);
                    } else {
                        $stmt = $db->prepare("UPDATE UTILISATEUR SET nom = ?, prenom = ?, login = ?, role = ?, actif = ? WHERE id = ?");
                        $result = $stmt->execute([$nom, $prenom, $login, $role, $actif, $user_id]);
                    }
                    
                    if ($result) {
                        $message = "L'utilisateur a été modifié avec succès.";
                        $message_type = 'success';
                        
                        // Mettre à jour les infos utilisateur après modification
                        $stmt = $db->prepare("SELECT id, nom, prenom, login, role, actif FROM UTILISATEUR WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();
                    } else {
                        $message = "Une erreur est survenue lors de la modification de l'utilisateur.";
                        $message_type = 'error';
                    }
                }
            } catch (Exception $e) {
                error_log('Erreur lors de la modification d\'un utilisateur: ' . $e->getMessage());
                $message = "Une erreur est survenue lors de la modification de l'utilisateur.";
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
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Modifier l'utilisateur</h5>
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
                        
                        <!-- Nom et prénom sur la même ligne -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required 
                                       value="<?php echo htmlspecialchars($user['nom']); ?>">
                                <div class="invalid-feedback">Veuillez saisir un nom.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required
                                       value="<?php echo htmlspecialchars($user['prenom']); ?>">
                                <div class="invalid-feedback">Veuillez saisir un prénom.</div>
                            </div>
                        </div>
                        
                        <!-- Identifiant -->
                        <div class="mb-3">
                            <label for="login" class="form-label">Identifiant <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="login" name="login" required
                                   value="<?php echo htmlspecialchars($user['login']); ?>">
                            <div class="form-text">L'identifiant doit être unique et sera utilisé pour la connexion.</div>
                            <div class="invalid-feedback">Veuillez saisir un identifiant.</div>
                        </div>
                        
                        <!-- Mot de passe et confirmation sur la même ligne -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" minlength="8">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="mot_de_passe">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Laissez vide pour conserver le mot de passe actuel.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirmer_mdp" class="form-label">Confirmer le mot de passe</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmer_mdp" name="confirmer_mdp">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirmer_mdp">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rôle -->
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled>Sélectionnez un rôle</option>
                                <option value="ADMIN" <?php echo $user['role'] === 'ADMIN' ? 'selected' : ''; ?>>Administrateur</option>
                                <option value="UTILISATEUR" <?php echo $user['role'] === 'UTILISATEUR' ? 'selected' : ''; ?>>Utilisateur</option>
                                <option value="VISITEUR" <?php echo (isset($role) && $role === 'VISITEUR') ? 'selected' : ''; ?>>Visiteur</option>

                            </select>
                            <div class="form-text">Les administrateurs ont accès à toutes les fonctionnalités du système.</div>
                            <div class="invalid-feedback">Veuillez sélectionner un rôle.</div>
                        </div>
                        
                        <!-- Statut du compte -->
                        <div class="mb-4 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="actif" name="actif" 
                                   <?php echo $user['actif'] ? 'checked' : ''; ?>
                                   <?php echo $user_id == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                            <label class="form-check-label" for="actif">Compte actif</label>
                            <div class="form-text">
                                <?php if ($user_id == $_SESSION['user_id']): ?>
                                    Vous ne pouvez pas désactiver votre propre compte.
                                <?php else: ?>
                                    Décochez cette case pour désactiver l'accès au compte.
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/views/users/list.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Enregistrer les modifications
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
    
    // Vérification de la correspondance des mots de passe
    const password = document.getElementById('mot_de_passe');
    const confirm = document.getElementById('confirmer_mdp');
    
    function validatePassword() {
        if (password.value != confirm.value) {
            confirm.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirm.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    confirm.addEventListener('keyup', validatePassword);
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>