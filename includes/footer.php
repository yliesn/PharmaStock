<?php
/**
 * Pied de page commun de l'application
 * Contient les scripts JS communs et le copyright
 */
// Vérification de sécurité pour éviter l'accès direct au fichier
if (!defined('ROOT_PATH')) {
    header("Location: /");
    exit;
}
?>
    <!-- Pied de page -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-md-5 text-md-start">
                    <h5>PharmaStock</h5>
                    <p class="text-muted small">Une solution simple et efficace pour gérer vos stocks.</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> PharmaStock</p>
                    <p class="text-muted small">Développé par Nejara Ylies. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>
    <!-- jQuery (nécessaire pour DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Langue française pour DataTables -->
    <script src="https://cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"></script>
   
    <!-- Scripts personnalisés -->
    <?php if (isset($page_scripts) && is_array($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo BASE_URL . '/' . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
   
    <!-- Script spécifique à la page -->
    <?php if (isset($page_specific_script) && $page_specific_script): ?>
    <script>
        <?php echo $page_specific_script; ?>
    </script>
    <?php endif; ?>
    
    <!-- Script pour le mode sombre -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const moonIcon = document.querySelector('.dark-mode-toggle i');
            
            // Fonction pour mettre à jour l'icône
            function updateMoonIcon(isDarkMode) {
                if (isDarkMode) {
                    moonIcon.classList.remove('fa-moon');
                    moonIcon.classList.add('fa-sun');
                } else {
                    moonIcon.classList.remove('fa-sun');
                    moonIcon.classList.add('fa-moon');
                }
            }
            
            // Vérifier si le mode sombre est déjà activé
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
                darkModeToggle.checked = true;
                updateMoonIcon(true);
            }
            
            // Écouter le changement du commutateur
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    // Activer le mode sombre
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('darkMode', 'enabled');
                    updateMoonIcon(true);
                } else {
                    // Désactiver le mode sombre
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('darkMode', 'disabled');
                    updateMoonIcon(false);
                }
            });
        });
    </script>
</body>
</html>
