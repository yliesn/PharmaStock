<?php
/**
 * Page de traitement du formulaire de connexion
 * Ce script vérifie les identifiants de l'utilisateur et crée la session
 */

// Inclure le fichier de configuration
require_once '../config/config.php';

// Connexion par token (QR)
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = getDbConnection();
    
    $stmt = $db->prepare(" SELECT lt.id, u.* FROM LOGIN_TOKEN lt JOIN UTILISATEUR u ON u.id = lt.user_id WHERE lt.token = ? AND lt.expire_at > NOW() AND lt.used = 0 AND u.actif = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Invalider le token (optionnel mais recommandé)
        // $db->prepare("UPDATE LOGIN_TOKEN SET used = 1 WHERE id = ?")
        //    ->execute([$user['id']]);

        redirect('dashboard.php');
    } else {
        $_SESSION['error_message'] = "Lien de connexion invalide ou expiré.";
        redirect('index.php');
    }
}


// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Erreur de validation du formulaire. Veuillez réessayer.";
        redirect('index.php');
    }
   
    // Récupérer les données du formulaire
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
   
    // Validation de base
    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires.";
        redirect('index.php');
    }
   
    try {
        // Connexion à la base de données
        $db = getDbConnection();
       
        if (!$db) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
       
        // Rechercher l'utilisateur dans la base de données
        $stmt = $db->prepare("SELECT id, nom, prenom, login, mot_de_passe, role, actif FROM UTILISATEUR WHERE login = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
       
        // Vérifier si l'utilisateur existe, s'il est actif et si le mot de passe est correct
        if ($user && $user['actif'] && password_verify($password, $user['mot_de_passe'])) {
            // Authentification réussie
           
            // Régénérer l'ID de session pour prévenir la fixation de session
            session_regenerate_id(true);
           
            // Stocker les informations de session
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
           
            // Mettre à jour la date de dernière connexion
            $updateStmt = $db->prepare("UPDATE UTILISATEUR SET date_derniere_connexion = CURRENT_DATE() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
           
            // Redirection vers le tableau de bord
            redirect('dashboard.php');
        } elseif ($user && !$user['actif']) {
            // Compte désactivé
            $_SESSION['error_message'] = "Votre compte est désactivé. Veuillez contacter l'administrateur.";
            redirect('index.php');
        } else {
            // Authentification échouée
            $_SESSION['error_message'] = "Identifiant ou mot de passe incorrect";
            redirect('index.php');
        }
    } catch (Exception $e) {
        // Log l'erreur dans un fichier plutôt que de l'afficher
        error_log('Erreur de connexion: ' . $e->getMessage());
        $_SESSION['error_message'] = "Erreur de connexion au serveur. Veuillez réessayer plus tard.";
        redirect('index.php');
    }
} else {
    // Si quelqu'un tente d'accéder directement à login.php
    redirect('index.php');
}