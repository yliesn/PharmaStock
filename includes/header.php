<?php
/**
 * En-tête commun de l'application
 * Contient la barre de navigation principale
 */
require_once __DIR__ . '/functions.php';

// Vérification de sécurité pour éviter l'accès direct au fichier
if (!defined('ROOT_PATH')) {
    header("Location: /");
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$nom = isset($_SESSION['user_nom']) ? htmlspecialchars($_SESSION['user_nom']) : '';
$prenom = isset($_SESSION['user_prenom']) ? htmlspecialchars($_SESSION['user_prenom']) : '';
$role = isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : '';

// Vérifier si le mode sombre est activé dans les paramètres

$dark_mode_enabled = isFeatureEnabled('enable_dark_mode');

// Vérifier si la fonctionnalité d'inventaire est activée
$inventory_enabled = isFeatureEnabled('enable_inventory');

// Vérifier si l'utilisateur a les droits d'accès
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['error_message'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
    redirect('dashboard.php');
}
if ($_SESSION['user_role'] === 'VISITEUR') {
    $_SESSION['error_message'] = "Espace réservé aux visiteurs.";
    redirect('views/visiteur/index.php');
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
        /**
         * Variables de couleurs globales pour le mode sombre
         * Modifiez ces variables pour changer les couleurs dans tout le thème
         */
        :root {
            /* Couleurs de base */
            --dark-bg: #121212;          /* Fond principal */
            --dark-surface: #2d2d2d;     /* Surface des cartes et éléments */
            --dark-surface-light: #383838;/* Surface plus claire (hover) */
            --dark-border: #444;         /* Bordures */
            --dark-text: #e0e0e0;        /* Texte principal  */
            --dark-text-muted: #adb5bd;  /* Texte secondaire */
            
            /* Couleurs spécifiques */
            --dark-header: #1e1e1e;      /* En-têtes, navigation */
            --dark-input: #2d2d2d;       /* Champs de formulaire */
            --dark-hover: #3d3d3d;       /* État hover */
            --dark-striped: #252525;     /* Lignes alternées */
            
            /* Couleurs Bootstrap originales */
            --bs-light: #f8f9fa;
            --bs-dark: #212529;
        }
        
        /**
         * Styles de base du mode sombre
         * Fond et texte principaux
         */
        body.dark-mode {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }
        
        /**
         * Barre de navigation
         * Menu principal et sous-menus
         */
        body.dark-mode .navbar {
            background-color: var(--dark-header) !important;
        }
        
        /**
         * Cartes (Cards)
         * Utilisées pour les conteneurs principaux
         */
        body.dark-mode .card {
            background-color: var(--dark-surface);
            border-color: var(--dark-border);
        }
        body.dark-mode .card-header {
            border-bottom-color: var(--dark-border);
        }
        
        /**
         * Tableaux
         * Styles pour tous les tableaux de données
         */
        body.dark-mode .table {
            color: var(--dark-text);
            background-color: var(--dark-surface);
        }
        /* Cellules de tableau */
        body.dark-mode .table th,
        body.dark-mode .table td {
            background-color: var(--dark-surface);
            border-color: var(--dark-border);
        }
        /* En-têtes de tableau */
        body.dark-mode .table thead th {
            background-color: var(--dark-header);
            border-bottom-color: var(--dark-border);
            color: #fff;
        }
        /* Lignes alternées */
        body.dark-mode .table-striped>tbody>tr:nth-of-type(odd),
        body.dark-mode .table-striped>tbody>tr:nth-of-type(odd) td {
            background-color: var(--dark-striped);
        }
        /* Effet hover sur les lignes */
        body.dark-mode .table-hover>tbody>tr:hover,
        body.dark-mode .table-hover>tbody>tr:hover td {
            background-color: var(--dark-surface-light);
        }
        /* Variante claire des tableaux */
        body.dark-mode .table-light, 
        body.dark-mode .table-light>td, 
        body.dark-mode .table-light>th {
            background-color: var(--dark-surface);
            color: var(--dark-text);
        }
        
        /**
         * Éléments de formulaire
         * Champs texte, select, checkboxes
         */
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: var(--dark-input);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: var(--dark-input);
            color: var(--dark-text);
        }
        /* Cases à cocher dans les tableaux */
        body.dark-mode .table input[type="checkbox"] {
            background-color: var(--dark-surface);
            border-color: #666;
        }
        
        /**
         * Textes et étiquettes
         * Variations de couleur pour le texte
         */
        body.dark-mode .text-muted {
            color: var(--dark-text-muted) !important;
        }
        /* S'assurer que tous les textes sont bien visibles */
        body.dark-mode .text-dark,
        body.dark-mode .text-black-50,
        body.dark-mode .text-body,
        body.dark-mode td {
            color: var(--dark-text) !important;
        }
        /* Style spécifique pour les références et codes */
        body.dark-mode td span:not(.badge),
        body.dark-mode td strong,
        body.dark-mode .reference-text {
            color: #fff !important;
        }
        
        /**
         * Listes groupées
         * Utilisées dans les menus et panneaux
         */
        body.dark-mode .list-group-item {
            background-color: var(--dark-surface);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }
        
        /**
         * Alertes et notifications
         * Messages système et retours utilisateur
         */
        body.dark-mode .alert-success {
            background-color: #1c3a25;
            border-color: #2a5a3a;
            color: #d1e7dd;
        }
        
        /**
         * Badges
         * Étiquettes et indicateurs
         */
        body.dark-mode .badge.bg-warning.text-dark {
            color: #000000ff !important;
        }
        
        /**
         * Menus déroulants
         * Sous-menus et listes déroulantes
         */
        body.dark-mode .dropdown-menu {
            background-color: var(--dark-surface);
            border-color: var(--dark-border);
        }
        body.dark-mode .dropdown-item {
            color: var(--dark-text);
        }
        body.dark-mode .dropdown-item:hover, 
        body.dark-mode .dropdown-item:focus {
            background-color: var(--dark-hover);
            color: #fff;
        }
        body.dark-mode .dropdown-divider {
            border-top-color: var(--dark-border);
        }
        
        /**
         * Pied de page
         */
        body.dark-mode footer.bg-light {
            background-color: var(--dark-header) !important;
            color: var(--dark-text);
        }
        
        /**
         * DataTables
         * Styles spécifiques pour les tableaux dynamiques
         */
        body.dark-mode .dataTables_wrapper .dataTables_length,
        body.dark-mode .dataTables_wrapper .dataTables_filter,
        body.dark-mode .dataTables_wrapper .dataTables_info,
        body.dark-mode .dataTables_wrapper .dataTables_processing,
        body.dark-mode .dataTables_wrapper .dataTables_paginate {
            color: var(--dark-text);
        }
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--dark-text) !important;
        }
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #666 !important;
        }
        /* En-têtes triables */
        body.dark-mode .table thead .sorting,
        body.dark-mode .table thead .sorting_asc,
        body.dark-mode .table thead .sorting_desc {
            background-color: var(--dark-header);
        }
        
        /**
         * Switch du mode sombre
         * Styles du bouton toggle
         */
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
                    <?php
                    // Afficher le lien Scanner CB seulement si le toggle est activé
                    $CB_toggle = isFeatureEnabled('enable_barcode_scanner');
                    if ($CB_toggle) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/views/supplies/scan.php">
                            <i class="fas fa-barcode me-1"></i> Scanner CB
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php
                    // Vérifier si les approbations sont activées
                    $approvals_enabled = isFeatureEnabled('enable_approvals');
                    if ($approvals_enabled && in_array($_SESSION['user_role'], ['UTILISATEUR', 'ADMIN'])) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/views/users/approbations.php">
                            <i class="fas fa-clipboard-check me-1"></i> Approbations
                        </a>
                    </li>
                    <?php endif; ?>
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
                    <?php if ($inventory_enabled): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/views/inventaire/index.php">
                            <i class="fas fa-warehouse me-1"></i> Inventaire
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i> Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/users/list.php">Utilisateurs</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/users/add.php">Ajouter un utilisateur</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/admin/settings.php"><i class="fas fa-toggle-on me-1"></i> Paramètres</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?php echo BASE_URL; ?>/test">Console SQL</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/supplies/bulk_select.php"><i class="fas fa-barcode me-1"></i> Générer codes-barres en lot</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                </ul>
                
                <!-- Menu utilisateur -->
                <ul class="navbar-nav ms-auto">
                    <!-- Toggle dark mode -->
                    <?php if ($dark_mode_enabled): ?>
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

    <!-- Initialisation du système de notifications et dark mode -->
    <script>
        // Initialiser le système de notifications
        const notifications = new NotificationSystem({
            position: 'top-right',
            duration: 5000
        });

        <?php if ($dark_mode_enabled): ?>
        // Dark mode toggle
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.body;
            
            // Vérifier la préférence sauvegardée
            const darkMode = localStorage.getItem('darkMode') === 'enabled';
            if (darkMode) {
                body.classList.add('dark-mode');
                darkModeToggle.checked = true;
            }

            // Gérer le changement de mode
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    body.classList.add('dark-mode');
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    body.classList.remove('dark-mode');
                    localStorage.setItem('darkMode', 'disabled');
                }
            });
        });
        <?php endif; ?>
    </script>
