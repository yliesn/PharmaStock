<?php
/**
 * Page de connexion à l'application
 * Affiche le formulaire de connexion et gère les redirections et messages d'erreur
 */

// Inclure le fichier de configuration
require_once 'config/config.php';

// Générer un token CSRF pour la sécurité du formulaire
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Rediriger vers le dashboard
    header("Location: dashboard.php");
    exit();
}

// Message d'erreur pour affichage
$error_message = "";
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error_message = "Votre session a expiré. Veuillez vous reconnecter.";
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Effacer le message après utilisation
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Connexion - Gestion de Stock Pharmacie</title>
    <link rel="stylesheet" href="style/loader.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin: 0 auto;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .login-container.visible {
            opacity: 1;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .btn-primary {
            width: 100%;
            padding: 0.8rem;
            background-color: #0d6efd;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .password-toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            color: #6c757d;
        }
    </style>

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#198754">
    <!-- Icône pour l'écran d'accueil -->
    <link rel="icon" type="image/png" sizes="192x192" href="assets/img/logo2.png">
    <link rel="apple-touch-icon" href="assets/img/logo2.png">
    <script>
      // Enregistrement du service worker
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
          navigator.serviceWorker.register('service-worker.js');
        });
      }
    </script>
</head>
<body>
    <!-- Loader -->
    <div class="loader-container" id="loader">
        <div class="loading">
            <svg height="96px" width="128px">
                <polyline id="back" points="0.157 23.954, 14 23.954, 21.843 48, 43 0, 50 24, 64 24"></polyline>
                <polyline id="front" points="0.157 23.954, 14 23.954, 21.843 48, 43 0, 50 24, 64 24"></polyline>
            </svg>
        </div>
    </div>

    <div class="login-container" id="loginForm">
        <!-- Logo -->
        <div class="logo-container">
          <!--  <i class="fas fa-house-medical text-success " style="font-size: 5rem" ></i> -->
             <img src="assets/img/logo2.png" alt="Logo Pharmacie" class="logo">
        </div>
        
        <!-- Titre -->
        <h2 class="text-center mb-4">Connexion</h2>
        
        <!-- Formulaire de connexion -->
        <form method="POST" action="auth/login.php">
            <!-- Token CSRF caché -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Champ Identifiant -->
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Identifiant" required>
                <label for="username">Identifiant</label>
            </div>
            
            <!-- Champ Mot de passe -->
            <div class="form-floating mb-4 position-relative">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
                <span class="password-toggle-icon" id="toggle-password">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            
            <!-- Bouton de connexion -->
            <button type="submit" class="btn btn-primary btn-lg">Se connecter</button>
        </form>
    </div>

    <!-- Bootstrap & JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Système de notifications -->
    <script src="assets/js/notifications.js"></script>
    <script>
        // Initialiser le système de notifications
        const notifications = new NotificationSystem({
            position: 'top-right',
            duration: 5000
        });
        
        // Afficher le message d'erreur en tant que notification si nécessaire
        <?php if (!empty($error_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            notifications.error('Erreur de connexion', '<?php echo addslashes(htmlspecialchars($error_message)); ?>');
        });
        <?php endif; ?>
        
        // Script pour afficher/masquer le mot de passe
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
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
        
        // Script pour le loader
        window.addEventListener('load', function() {
            const loader = document.getElementById('loader');
            const loginForm = document.getElementById('loginForm');
            
            // Afficher le loader pendant 1 seconde (1000ms)
            setTimeout(function() {
                // Masquer le loader
                loader.style.opacity = '0';
                
                // Après la transition de fondu du loader, le cacher complètement
                setTimeout(function() {
                    loader.style.display = 'none';
                    // Afficher le formulaire de connexion
                    loginForm.classList.add('visible');
                }, 300);
            }, 2000);
        });
    </script>
</body>
</html>
