<?php
/**
 * Liste des fournitures
 * Affiche toutes les fournitures en stock avec possibilité de filtrer et trier
 */

// Inclure le fichier de configuration
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    redirect('index.php');
}

// Définir le titre de la page
$page_title = "Liste des fournitures";

// Définir ROOT_PATH pour le header
if (!defined('ROOT_PATH')) {
    // Définir ROOT_PATH pour le header
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Recherche et filtrage
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Construction de la requête SQL de base
$sql = "SELECT id, reference, designation, description, quantite_stock, seuil_alerte, commande_en_cours FROM FOURNITURE";
$params = [];

// Ajout des conditions de recherche et filtrage
if (!empty($search)) {
    $sql .= " WHERE (reference LIKE ? OR designation LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    
    if ($filter === 'low') {
        $sql .= " AND quantite_stock <= seuil_alerte AND seuil_alerte IS NOT NULL";
    }
    if ($filter === 'ordered') {
        $sql .= " AND commande_en_cours = True"; // Supposons que cette colonne existe pour indiquer une commande en cours
    }
} elseif ($filter === 'low') {
    $sql .= " WHERE quantite_stock <= seuil_alerte AND seuil_alerte IS NOT NULL";
} elseif ($filter === 'ordered') {
    $sql .= " WHERE commande_en_cours = True";
}

// Ordre de tri
// $sql .= " ORDER BY id ASC";

// Récupérer les fournitures
try {
    $db = getDbConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $supplies = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des fournitures: ' . $e->getMessage());
    $supplies = [];
    $error_message = "Une erreur est survenue lors de la récupération des fournitures.";
}

// Inclure l'en-tête
include_once ROOT_PATH . '/includes/header.php';
?>

<style>
    * {
        box-sizing: border-box;
    }

    .supplies-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Header */
    .supplies-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .supplies-page-header h1 {
        font-size: 1.75rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .supplies-header-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Cards */
    .supplies-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .supplies-card-body {
        padding: 1.5rem;
    }

    /* Search Form */
    .supplies-search-form {
        /* display: grid; */
        grid-template-columns: 1fr auto auto auto;
        gap: 1rem;
        align-items: center;
    }

    /* Table */
    .supplies-table-responsive {
        overflow-x: auto;
    }

    .supplies-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .supplies-table thead {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .supplies-table th {
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        transition: background 0.2s;
        white-space: nowrap;
    }

    .supplies-table th:hover {
        background: #e9ecef;
    }

    .supplies-table th .header-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .supplies-sort-icon {
        width: 14px;
        height: 14px;
        color: #6c757d;
    }

    .supplies-table th.sorted-asc .supplies-sort-icon,
    .supplies-table th.sorted-desc .supplies-sort-icon {
        color: #0d6efd;
    }

    .supplies-table tbody tr {
        border-bottom: 1px solid #dee2e6;
        transition: background 0.2s;
    }

    .supplies-table tbody tr:hover {
        background: #f8f9fa;
    }

    .supplies-table td {
        padding: 0.75rem;
    }

    .supplies-table .text-end {
        text-align: right;
    }

    .supplies-table .fw-bold {
        font-weight: 600;
    }

    /* Badges */
    .supplies-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .supplies-badge-success {
        background: #d1e7dd;
        color: #0f5132;
    }

    .supplies-badge-warning {
        background: #fff3cd;
        color: #664d03;
    }

    .supplies-badge-danger {
        background: #f8d7da;
        color: #842029;
    }

    .supplies-badge-info {
        background: #cff4fc;
        color: #055160;
    }

    /* Button Group */
    .supplies-btn-group {
        display: inline-flex;
    }

    .supplies-btn-group .btn {
        border-radius: 0;
        margin: 0;
        border-right: 1px solid rgba(0, 0, 0, 0.1);
    }

    .supplies-btn-group .btn:first-child {
        border-radius: 0.375rem 0 0 0.375rem;
    }

    .supplies-btn-group .btn:last-child {
        border-radius: 0 0.375rem 0.375rem 0;
        border-right: none;
    }

    /* Pagination */
    .supplies-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #dee2e6;
    }

    .supplies-pagination-info {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .supplies-pagination-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .supplies-pagination-controls label {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .supplies-pagination-controls button {
        padding: 0.5rem 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: white;
        color: #212529;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .supplies-pagination-controls button:hover:not(:disabled) {
        background: #f8f9fa;
    }

    .supplies-pagination-controls button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .supplies-page-number {
        padding: 0.5rem 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: white;
        cursor: pointer;
        min-width: 2.5rem;
        text-align: center;
        font-size: 0.875rem;
    }

    .supplies-page-number:hover {
        background: #f8f9fa;
    }

    .supplies-page-number.active {
        background: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    .icon-up { display: none; }
    .icon-down { display: none; }
    .icon-updown { display: inline; }
    
    .supplies-table th.sorted-asc .icon-up { display: inline; }
    .supplies-table th.sorted-asc .icon-down { display: none; }
    .supplies-table th.sorted-asc .icon-updown { display: none; }
    
    .supplies-table th.sorted-desc .icon-up { display: none; }
    .supplies-table th.sorted-desc .icon-down { display: inline; }
    .supplies-table th.sorted-desc .icon-updown { display: none; }

    @media (max-width: 768px) {
        .supplies-search-form {
            grid-template-columns: 1fr;
        }

        .supplies-page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .supplies-header-actions {
            flex-wrap: wrap;
        }
    }
</style>

<!-- Contenu principal -->
<div class="container mt-4 supplies-container">
    <!-- En-tête de page avec bouton d'ajout -->
    <div class="d-flex justify-content-between align-items-center mb-4 supplies-page-header">
        <h1 class="h3"><i class="fas fa-boxes me-2"></i>Liste des fournitures</h1>
        <div class="supplies-header-actions">
            <a href="<?php echo BASE_URL; ?>/views/stock/entry.php" class="btn btn-success me-2">
                <i class="fas fa-plus-circle me-1"></i> Entrée de stock
            </a>
            <a href="<?php echo BASE_URL; ?>/views/supplies/add.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nouvelle fourniture
            </a>
            <a href="<?php echo BASE_URL; ?>/views/supplies/import_supplies.php" class="btn btn-primary">
                <i class="fas fa-file-import me-1"></i> Import fourniture
            </a>
        </div>
    </div>
    
    <!-- Formulaire de recherche et filtrage -->
    <div class="card shadow-sm mb-4 supplies-card">
        <div class="card-body supplies-card-body">
            <form method="GET" action="" class="row g-3 supplies-search-form">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Rechercher par référence, désignation..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Toutes les fournitures</option>
                        <option value="low" <?php echo $filter === 'low' ? 'selected' : ''; ?>>Stock bas</option>
                        <option value="ordered" <?php echo $filter === 'ordered' ? 'selected' : ''; ?>>Commande en cours</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo BASE_URL; ?>/views/supplies/list.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo-alt me-1"></i> Réinitialiser
                    </a>
                    <a href="<?php echo BASE_URL; ?>/views/supplies/export_supplies.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                        <i class="fas fa-file-csv me-1"></i> Exporter en CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Notification d'erreur si nécessaire -->
    <?php if (isset($error_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            notifications.error('Erreur', '<?php echo addslashes($error_message); ?>');
        });
    </script>
    <?php endif; ?>
    
    <!-- Résultats de la recherche -->
    <div class="card shadow-sm supplies-card">
        <div class="card-body supplies-card-body">
            <?php if (empty($supplies)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php if (!empty($search) || $filter === 'low'): ?>
                        Aucune fourniture trouvée avec ces critères.
                    <?php elseif ($filter === 'ordered'): ?>
                        Aucune fourniture en commande.
                    <?php else: ?>
                        Aucune fourniture enregistrée dans le système.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="supplies-table-responsive">
                    <table class="supplies-table" id="supplies-table">
                        <thead>
                            <tr id="table-header"></tr>
                        </thead>
                        <tbody id="table-body"></tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="supplies-pagination">
                    <div class="supplies-pagination-info" id="pagination-info"></div>
                    <div class="supplies-pagination-controls">
                        <label for="items-per-page">Par page :</label>
                        <select id="items-per-page" class="form-select" onchange="changeItemsPerPage(this.value)">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <button onclick="changePage('prev')" id="prev-btn">Précédent</button>
                        <div id="page-numbers"></div>
                        <button onclick="changePage('next')" id="next-btn">Suivant</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Injection des données PHP
    const initialData = <?php echo json_encode($supplies); ?>;

    // Configuration des colonnes
    const columns = [
        { key: 'reference', label: 'Référence' },
        { 
            key: 'designation', 
            label: 'Désignation',
            render: (row) => {
                let html = `<span class="fw-bold">${row.designation}</span>`;
                if (row.description) {
                    const shortDesc = row.description.length > 50 
                        ? row.description.substring(0, 50) + '...' 
                        : row.description;
                    html += `<div class="small text-muted">${shortDesc}</div>`;
                }
                return html;
            }
        },
        { 
            key: 'quantite_stock', 
            label: 'Stock actuel',
            format: (val) => parseInt(val).toLocaleString('fr-FR'),
            className: 'fw-bold text-end'
        },
        { 
            key: 'seuil_alerte', 
            label: "Seuil d'alerte",
            format: (val) => val ? parseInt(val).toLocaleString('fr-FR') : '<span class="text-muted">Non défini</span>',
            className: 'text-end'
        },
        {
            key: 'status',
            label: 'Statut',
            render: (row) => {
                if (row.commande_en_cours == 1 || row.commande_en_cours === true) {
                    return '<span class="supplies-badge supplies-badge-info">Commandé</span>';
                } else if (row.seuil_alerte && parseInt(row.quantite_stock) <= parseInt(row.seuil_alerte)) {
                    if (parseInt(row.quantite_stock) === 0) {
                        return '<span class="supplies-badge supplies-badge-danger">Rupture</span>';
                    } else {
                        return '<span class="supplies-badge supplies-badge-warning">Stock bas</span>';
                    }
                } else {
                    return '<span class="supplies-badge supplies-badge-success">Normal</span>';
                }
            }
        },
        {
            key: 'actions',
            label: 'Actions',
            sortable: false,
            render: (row) => {
                let html = '<div class="supplies-btn-group btn-group-sm">';
                html += `<a href="<?php echo BASE_URL; ?>/views/supplies/view.php?id=${row.id}" class="btn btn-outline-primary" title="Détails"><i class="fas fa-eye"></i></a>`;
                html += `<a href="<?php echo BASE_URL; ?>/views/supplies/edit.php?id=${row.id}" class="btn btn-outline-secondary" title="Modifier"><i class="fas fa-edit"></i></a>`;
                html += `<a href="<?php echo BASE_URL; ?>/views/stock/entry.php?supply_id=${row.id}" class="btn btn-outline-success" title="Entrée de stock"><i class="fas fa-plus"></i></a>`;
                
                if (parseInt(row.quantite_stock) !== 0) {
                    html += `<a href="<?php echo BASE_URL; ?>/views/stock/exit.php?supply_id=${row.id}" class="btn btn-outline-danger" title="Sortie de stock"><i class="fas fa-minus"></i></a>`;
                }
                
                html += `<form method="POST" action="<?php echo BASE_URL; ?>/views/supplies/toggle_order.php" class="d-inline" style="margin: 0;">`;
                html += `<input type="hidden" name="supply_id" value="${row.id}">`;
                html += `<input type="hidden" name="status" value="1">`;
                html += `<input type="hidden" name="redirect" value="list.php">`;
                html += `<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">`;
                html += `<button type="submit" class="btn btn-warning" style="border-radius: 0;" title="Marquer comme commandé"><i class="fas fa-truck"></i></button>`;
                html += `</form>`;
                html += '</div>';
                return html;
            }
        }
    ];

    // État
    let data = [...initialData];
    let sortConfig = { key: null, direction: null };
    let currentPage = 1;
    let itemsPerPage = 15;

    // Icônes SVG
    const icons = {
        updown: '<svg class="supplies-sort-icon icon-updown" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 15 12 20 17 15"></polyline><polyline points="7 9 12 4 17 9"></polyline></svg>',
        up: '<svg class="supplies-sort-icon icon-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>',
        down: '<svg class="supplies-sort-icon icon-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>'
    };

    // Fonctions
    function sortData(key) {
        const column = columns.find(col => col.key === key);
        if (column && column.sortable === false) return;

        let direction = 'asc';
        
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            direction = 'desc';
        } else if (sortConfig.key === key && sortConfig.direction === 'desc') {
            direction = null;
        }

        sortConfig = { key, direction };

        if (direction === null) {
            data = [...initialData];
        } else {
            data.sort((a, b) => {
                let aVal = a[key];
                let bVal = b[key];
                
                if (aVal === null || aVal === undefined) return 1;
                if (bVal === null || bVal === undefined) return -1;
                
                if (typeof aVal === 'string') aVal = aVal.toLowerCase();
                if (typeof bVal === 'string') bVal = bVal.toLowerCase();
                
                if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                return 0;
            });
        }

        currentPage = 1;
        render();
    }

    function changePage(direction) {
        const totalPages = Math.ceil(data.length / itemsPerPage);
        
        if (direction === 'next' && currentPage < totalPages) {
            currentPage++;
        } else if (direction === 'prev' && currentPage > 1) {
            currentPage--;
        } else if (typeof direction === 'number') {
            currentPage = direction;
        }
        
        render();
    }

    function changeItemsPerPage(value) {
        itemsPerPage = parseInt(value);
        currentPage = 1;
        render();
    }

    function getPaginatedData() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        return data.slice(startIndex, endIndex);
    }

    function render() {
        const header = document.getElementById('table-header');
        const body = document.getElementById('table-body');
        const paginationInfo = document.getElementById('pagination-info');
        const pageNumbers = document.getElementById('page-numbers');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');

        const paginatedData = getPaginatedData();
        const totalPages = Math.ceil(data.length / itemsPerPage);
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, data.length);

        // Rendu en-tête
        header.innerHTML = columns.map(column => {
            let sortClass = '';
            if (sortConfig.key === column.key) {
                sortClass = sortConfig.direction === 'asc' ? 'sorted-asc' : 'sorted-desc';
            }
            
            const clickable = column.sortable !== false ? `onclick="sortData('${column.key}')"` : '';
            
            return `
                <th class="${sortClass}" ${clickable}>
                    <div class="header-content">
                        ${column.label}
                        ${column.sortable !== false ? icons.updown + icons.up + icons.down : ''}
                    </div>
                </th>
            `;
        }).join('');

        // Rendu corps
        body.innerHTML = paginatedData.map(row => `
            <tr>
                ${columns.map(column => {
                    let content;
                    if (column.render) {
                        content = column.render(row);
                    } else if (column.format) {
                        content = column.format(row[column.key]);
                    } else {
                        content = row[column.key] || '';
                    }
                    const className = column.className || '';
                    return `<td class="${className}">${content}</td>`;
                }).join('')}
            </tr>
        `).join('');

        // Pagination
        paginationInfo.textContent = `Affichage de ${startItem} à ${endItem} sur ${data.length} fourniture(s)`;
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || data.length === 0;

        // Numéros de page
        let pages = [];
        const maxVisiblePages = 5;
        
        if (totalPages <= maxVisiblePages) {
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            if (currentPage <= 3) {
                pages = [1, 2, 3, 4, '...', totalPages];
            } else if (currentPage >= totalPages - 2) {
                pages = [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
            } else {
                pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
            }
        }

        pageNumbers.innerHTML = pages.map(page => {
            if (page === '...') {
                return '<span class="supplies-page-number" style="cursor: default; border: none;">...</span>';
            }
            const activeClass = page === currentPage ? 'active' : '';
            return `<span class="supplies-page-number ${activeClass}" onclick="changePage(${page})">${page}</span>`;
        }).join('');
    }

    // Initialisation
    if (initialData && initialData.length > 0) {
        render();
    }
</script>

<?php
// Inclure le pied de page
include_once ROOT_PATH . '/includes/footer.php';
?>