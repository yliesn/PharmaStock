<?php
/**
 * En-tête commun de l'application
 * Contient la barre de navigation principale
 */

// Vérification de sécurité pour éviter l'accès direct au fichier
if (!defined('ROOT_PATH')) {
    header("Location: /");
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$nom = isset($_SESSION['user_nom']) ? htmlspecialchars($_SESSION['user_nom']) : '';
$prenom = isset($_SESSION['user_prenom']) ? htmlspecialchars($_SESSION['user_prenom']) : '';
$role = isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : '';

// Vérifier si l'utilisateur a les droits d'accès
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['error_message'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
    redirect('dashboard.php');
}
if ($_SESSION['user_role'] === 'VISITEUR') {
    $_SESSION['error_message'] = "Espace réservé aux visiteurs.";
    redirect('/views/visiteur/index.php');
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
    <!-- Système de notifications -->
    <script src="<?php echo BASE_URL; ?>/assets/js/notifications.js"></script>
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>PharmaStock</title>
    
    <!-- Styles pour le mode sombre -->
    <style>
        :root {
            --bs-light: #f8f9fa;
            --bs-dark: #212529;
        }
        
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }
        
        /* Navigation */
        body.dark-mode .navbar {
            background-color: #1e1e1e !important;
        }
        
        /* Cards */
        body.dark-mode .card {
            background-color: #2d2d2d;
            border-color: #444;
        }
        body.dark-mode .card-header {
            border-bottom-color: #444;
        }
        
        /* Tables */
        body.dark-mode .table {
            color: #e0e0e0;
        }
        body.dark-mode .table-striped>tbody>tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        body.dark-mode .table-hover>tbody>tr:hover {
            background-color: rgba(255, 255, 255, 0.075);
        }
        body.dark-mode .table-light, 
        body.dark-mode .table-light>td, 
        body.dark-mode .table-light>th {
            background-color: #343a40;
            color: #e0e0e0;
        }
        
        /* Form controls */
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #2d2d2d;
            border-color: #444;
            color: #e0e0e0;
        }
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: #2d2d2d;
            color: #e0e0e0;
        }
        
        /* Text colors */
        body.dark-mode .text-muted {
            color: #adb5bd !important;
        }
        
        /* List groups */
        body.dark-mode .list-group-item {
            background-color: #2d2d2d;
            border-color: #444;
            color: #e0e0e0;
        }
        
        /* Alerts */
        body.dark-mode .alert-success {
            background-color: #1c3a25;
            border-color: #2a5a3a;
            color: #d1e7dd;
        }
        
        /* Badges */
        body.dark-mode .badge.bg-warning.text-dark {
            color: #ffc107 !important;
        }
        
        /* Dropdown menus */
        body.dark-mode .dropdown-menu {
            background-color: #2d2d2d;
            border-color: #444;
        }
        body.dark-mode .dropdown-item {
            color: #e0e0e0;
        }
        body.dark-mode .dropdown-item:hover, 
        body.dark-mode .dropdown-item:focus {
            background-color: #3d3d3d;
            color: #fff;
        }
        body.dark-mode .dropdown-divider {
            border-top-color: #444;
        }
        
        /* Footer */
        body.dark-mode footer.bg-light {
            background-color: #1e1e1e !important;
            color: #e0e0e0;
        }
        
        /* DataTables */
        body.dark-mode .dataTables_wrapper .dataTables_length,
        body.dark-mode .dataTables_wrapper .dataTables_filter,
        body.dark-mode .dataTables_wrapper .dataTables_info,
        body.dark-mode .dataTables_wrapper .dataTables_processing,
        body.dark-mode .dataTables_wrapper .dataTables_paginate {
            color: #e0e0e0;
        }
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #e0e0e0 !important;
        }
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #666 !important;
        }
        
        /* Dark mode toggle switch styles */
        .dark-mode-toggle {
            display: flex;
            align-items: center;
        }
        .dark-mode-toggle i {
            margin-right: 6px;
            font-size: 1.2rem;
        }
        .dark-mode-toggle .form-check {
            margin-bottom: 0;
        }
    </style>
    <meta name="robots" content="noindex">
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#198754">
    <!-- Icône pour l'écran d'accueil -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo BASE_URL; ?>/assets/img/logo2.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/img/logo2.png">
    <script>
      // Enregistrement du service worker
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
          navigator.serviceWorker.register('<?php echo BASE_URL; ?>/service-worker.js');
        });
      }
    </script>
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/dashboard.php">
                <i class="fas fa-pills me-2"></i>
                PharmaStock
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Menu principal -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/views/users/approbations.php">
                            <i class="fas fa-clipboard-check me-1"></i> Approbations
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="stockDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-boxes me-1"></i> Stock
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/supplies/list.php">Liste des fournitures</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/supplies/add.php">Ajouter une fourniture</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/supplies/import_supplies.php">Importer des fournitures</a></li>
                            <!-- <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/stock/inventory.php">Inventaire</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/stock/shortages.php">Alertes de stock</a></li> -->
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="movementsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-exchange-alt me-1"></i> Mouvements
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/stock/movements.php">Historique</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/stock/entry.php">Entrée de stock</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/stock/exit.php">Sortie de stock</a></li>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="movementsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-code me-1"></i> Developpement
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/supplies/import_supplies.php">import TEST</a></li>
                        </ul>
                        
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i> Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/users/list.php">Utilisateurs</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/users/add.php">Ajouter un utilisateur</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?php echo BASE_URL; ?>/test">Console SQL</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                </ul>
                
                <!-- Menu utilisateur -->
                <ul class="navbar-nav ms-auto">
                    <!-- Toggle dark mode -->
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN'): ?>
                    <li class="nav-item me-3 d-flex align-items-center dark-mode-toggle">
                        <i class="fas fa-moon text-light"></i>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="darkModeToggle">
                            <label class="form-check-label visually-hidden" for="darkModeToggle">Mode sombre</label>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i><?php echo $prenom . ' ' . $nom; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/users/profile.php">Mon profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Initialisation du système de notifications -->
    <script>
        // Initialiser le système de notifications
        const notifications = new NotificationSystem({
            position: 'top-right',
            duration: 5000
        });
    </script>
