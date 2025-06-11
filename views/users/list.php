<?php
/**
 * Liste des utilisateurs
 * Permet à un administrateur de voir et gérer les utilisateurs
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté et a les droits d'administrateur
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'ADMIN') {
    // Rediriger vers le tableau de bord avec un message d'erreur
    $_SESSION['error_message'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
    redirect('dashboard.php');
}

// Définir le titre de la page
$page_title = "Gestion des utilisateurs";

// Définir ROOT_PATH pour le header
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Traitement de la désactivation/activation d'un utilisateur si demandé
if (isset($_POST['toggle_status']) && isset($_POST['user_id']) && isset($_POST['csrf_token'])) {
    // Vérifier le token CSRF
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $user_id = (int)$_POST['user_id'];
        $new_status = (int)$_POST['new_status'];
        
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("UPDATE UTILISATEUR SET actif = ? WHERE id = ?");
            $result = $stmt->execute([$new_status, $user_id]);
            
            if ($result) {
                $status_message = $new_status ? "L'utilisateur a été activé." : "L'utilisateur a été désactivé.";
                $status_type = "success";
            } else {
                $status_message = "Une erreur est survenue lors de la modification du statut.";
                $status_type = "error";
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la modification du statut: ' . $e->getMessage());
            $status_message = "Une erreur est survenue lors de la modification du statut.";
            $status_type = "error";
        }
    } else {
        $status_message = "Erreur de validation du formulaire. Veuillez réessayer.";
        $status_type = "error";
    }
}

// Récupérer la liste des utilisateurs
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, nom, prenom, login, role, date_derniere_connexion, actif FROM UTILISATEUR ORDER BY nom, prenom");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
    $users = [];
    $status_message = "Une erreur est survenue lors de la récupération des utilisateurs.";
    $status_type = "error";
}

// Générer un nouveau token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<!-- Contenu principal -->
<div class="container mt-4">
    <!-- En-tête de page avec bouton d'ajout -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-users me-2"></i>Gestion des utilisateurs</h1>
        <a href="<?php echo BASE_URL; ?>/views/users/add.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Ajouter un utilisateur
        </a>
    </div>
    
    <!-- Notification de statut si nécessaire -->
    <?php if (isset($status_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            notifications.<?php echo $status_type; ?>(
                '<?php echo $status_type === 'success' ? 'Succès' : 'Erreur'; ?>', 
                '<?php echo addslashes($status_message); ?>'
            );
        });
    </script>
    <?php endif; ?>
    
    <!-- Tableau des utilisateurs -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Aucun utilisateur trouvé.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="users-table">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Identifiant</th>
                                <th>Rôle</th>
                                <th>Dernière connexion</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['login']); ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'ADMIN'): ?>
                                            <span class="badge bg-danger">Administrateur</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Utilisateur</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($user['date_derniere_connexion']) {
                                            echo date('d/m/Y', strtotime($user['date_derniere_connexion']));
                                        } else {
                                            echo '<span class="text-muted">Jamais</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user['actif']): ?>
                                            <span class="badge bg-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo BASE_URL; ?>/views/users/edit.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-outline-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): // Empêcher de se désactiver soi-même ?>
                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir <?php echo $user['actif'] ? 'désactiver' : 'activer'; ?> cet utilisateur ?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $user['actif'] ? '0' : '1'; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-outline-<?php echo $user['actif'] ? 'warning' : 'success'; ?>" 
                                                            title="<?php echo $user['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                                        <i class="fas fa-<?php echo $user['actif'] ? 'user-slash' : 'user-check'; ?>"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Scripts spécifiques à la page
// $page_scripts = [
//     'assets/js/datatables.min.js'
// ];

$page_specific_script = "
    // Initialisation de DataTables pour la pagination et la recherche
    $(document).ready(function() {
        $('#users-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json'
            },
            pageLength: 10,
            order: [[0, 'asc']]
        });
    });
";

// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>