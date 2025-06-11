<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Icônes de Pharmacie Font Awesome</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .icon-card {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .icon-card:hover {
            background-color: #f8f9fa;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .icon-display {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #198754;
        }
        .icon-name {
            font-size: 0.9rem;
        }
        .icon-code {
            font-size: 0.8rem;
            color: #6c757d;
            background-color: #f8f9fa;
            padding: 4px;
            border-radius: 4px;
        }
        .category-title {
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #198754;
            color: #198754;
        }
        .search-section {
            margin-bottom: 30px;
        }
        .copy-success {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #198754;
            color: white;
            border-radius: 4px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">Icônes de Pharmacie Font Awesome</h1>
        
        <div class="search-section">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <input type="text" id="searchIcon" class="form-control" placeholder="Rechercher une icône...">
                </div>
            </div>
        </div>

        <h2 class="category-title">Médicaments et outils pharmaceutiques</h2>
        <div class="row">
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-pills')">
                    <div class="icon-display"><i class="fas fa-pills"></i></div>
                    <div class="icon-name">Pills</div>
                    <div class="icon-code">fas fa-pills</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-prescription-bottle')">
                    <div class="icon-display"><i class="fas fa-prescription-bottle"></i></div>
                    <div class="icon-name">Prescription Bottle</div>
                    <div class="icon-code">fas fa-prescription-bottle</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-prescription-bottle-medical')">
                    <div class="icon-display"><i class="fas fa-prescription-bottle-medical"></i></div>
                    <div class="icon-name">Prescription Bottle Medical</div>
                    <div class="icon-code">fas fa-prescription-bottle-medical</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-capsules')">
                    <div class="icon-display"><i class="fas fa-capsules"></i></div>
                    <div class="icon-name">Capsules</div>
                    <div class="icon-code">fas fa-capsules</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-mortar-pestle')">
                    <div class="icon-display"><i class="fas fa-mortar-pestle"></i></div>
                    <div class="icon-name">Mortar Pestle</div>
                    <div class="icon-code">fas fa-mortar-pestle</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-tablets')">
                    <div class="icon-display"><i class="fas fa-tablets"></i></div>
                    <div class="icon-name">Tablets</div>
                    <div class="icon-code">fas fa-tablets</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-prescription')">
                    <div class="icon-display"><i class="fas fa-prescription"></i></div>
                    <div class="icon-name">Prescription</div>
                    <div class="icon-code">fas fa-prescription</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-syringe')">
                    <div class="icon-display"><i class="fas fa-syringe"></i></div>
                    <div class="icon-name">Syringe</div>
                    <div class="icon-code">fas fa-syringe</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-vial')">
                    <div class="icon-display"><i class="fas fa-vial"></i></div>
                    <div class="icon-name">Vial</div>
                    <div class="icon-code">fas fa-vial</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-vials')">
                    <div class="icon-display"><i class="fas fa-vials"></i></div>
                    <div class="icon-name">Vials</div>
                    <div class="icon-code">fas fa-vials</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-eye-dropper')">
                    <div class="icon-display"><i class="fas fa-eye-dropper"></i></div>
                    <div class="icon-name">Eye Dropper</div>
                    <div class="icon-code">fas fa-eye-dropper</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-band-aid')">
                    <div class="icon-display"><i class="fas fa-band-aid"></i></div>
                    <div class="icon-name">Band Aid</div>
                    <div class="icon-code">fas fa-band-aid</div>
                </div>
            </div>
        </div>

        <h2 class="category-title">Établissements et services médicaux</h2>
        <div class="row">
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-hospital')">
                    <div class="icon-display"><i class="fas fa-hospital"></i></div>
                    <div class="icon-name">Hospital</div>
                    <div class="icon-code">fas fa-hospital</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('far fa-hospital')">
                    <div class="icon-display"><i class="far fa-hospital"></i></div>
                    <div class="icon-name">Hospital (Regular)</div>
                    <div class="icon-code">far fa-hospital</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-house-medical')">
                    <div class="icon-display"><i class="fas fa-house-medical"></i></div>
                    <div class="icon-name">House Medical</div>
                    <div class="icon-code">fas fa-house-medical</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-clinic-medical')">
                    <div class="icon-display"><i class="fas fa-clinic-medical"></i></div>
                    <div class="icon-name">Clinic Medical</div>
                    <div class="icon-code">fas fa-clinic-medical</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-store-alt')">
                    <div class="icon-display"><i class="fas fa-store-alt"></i></div>
                    <div class="icon-name">Store Alt</div>
                    <div class="icon-code">fas fa-store-alt</div>
                </div>
            </div>
        </div>

        <h2 class="category-title">Symboles médicaux et de santé</h2>
        <div class="row">
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-plus')">
                    <div class="icon-display"><i class="fas fa-plus"></i></div>
                    <div class="icon-name">Plus</div>
                    <div class="icon-code">fas fa-plus</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-staff-snake')">
                    <div class="icon-display"><i class="fas fa-staff-snake"></i></div>
                    <div class="icon-name">Staff Snake</div>
                    <div class="icon-code">fas fa-staff-snake</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-star-of-life')">
                    <div class="icon-display"><i class="fas fa-star-of-life"></i></div>
                    <div class="icon-name">Star of Life</div>
                    <div class="icon-code">fas fa-star-of-life</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-first-aid')">
                    <div class="icon-display"><i class="fas fa-first-aid"></i></div>
                    <div class="icon-name">First Aid</div>
                    <div class="icon-code">fas fa-first-aid</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-medkit')">
                    <div class="icon-display"><i class="fas fa-medkit"></i></div>
                    <div class="icon-name">Medkit</div>
                    <div class="icon-code">fas fa-medkit</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-briefcase-medical')">
                    <div class="icon-display"><i class="fas fa-briefcase-medical"></i></div>
                    <div class="icon-name">Briefcase Medical</div>
                    <div class="icon-code">fas fa-briefcase-medical</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-kit-medical')">
                    <div class="icon-display"><i class="fas fa-kit-medical"></i></div>
                    <div class="icon-name">Kit Medical</div>
                    <div class="icon-code">fas fa-kit-medical</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-notes-medical')">
                    <div class="icon-display"><i class="fas fa-notes-medical"></i></div>
                    <div class="icon-name">Notes Medical</div>
                    <div class="icon-code">fas fa-notes-medical</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-heart')">
                    <div class="icon-display"><i class="fas fa-heart"></i></div>
                    <div class="icon-name">Heart</div>
                    <div class="icon-code">fas fa-heart</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-heartbeat')">
                    <div class="icon-display"><i class="fas fa-heartbeat"></i></div>
                    <div class="icon-name">Heartbeat</div>
                    <div class="icon-code">fas fa-heartbeat</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-heart-pulse')">
                    <div class="icon-display"><i class="fas fa-heart-pulse"></i></div>
                    <div class="icon-name">Heart Pulse</div>
                    <div class="icon-code">fas fa-heart-pulse</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-stethoscope')">
                    <div class="icon-display"><i class="fas fa-stethoscope"></i></div>
                    <div class="icon-name">Stethoscope</div>
                    <div class="icon-code">fas fa-stethoscope</div>
                </div>
            </div>
        </div>

        <h2 class="category-title">Personnes et professionnels</h2>
        <div class="row">
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-user-md')">
                    <div class="icon-display"><i class="fas fa-user-md"></i></div>
                    <div class="icon-name">User MD</div>
                    <div class="icon-code">fas fa-user-md</div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="icon-card" onclick="copyToClipboard('fas fa-user-nurse')">
                    <div class="icon-display"><i class="fas fa-user-nurse"></i></div>
                    <div class="icon-name">User Nurse</div>
                    <div class="icon-code">fas fa-user-nurse</div>
                </div>
            </div>
        </div>

        <div class="copy-success" id="copySuccess">
            Copié dans le presse-papiers!
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour copier le code de l'icône dans le presse-papiers
        function copyToClipboard(text) {
            navigator.clipboard.writeText('<i class="' + text + '"></i>').then(function() {
                const copySuccess = document.getElementById('copySuccess');
                copySuccess.style.display = 'block';
                setTimeout(function() {
                    copySuccess.style.display = 'none';
                }, 2000);
            });
        }

        // Fonction de recherche d'icônes
        document.getElementById('searchIcon').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const iconCards = document.querySelectorAll('.icon-card');
            
            iconCards.forEach(function(card) {
                const iconName = card.querySelector('.icon-name').innerText.toLowerCase();
                const iconCode = card.querySelector('.icon-code').innerText.toLowerCase();
                
                if (iconName.includes(searchText) || iconCode.includes(searchText)) {
                    card.parentElement.style.display = '';
                } else {
                    card.parentElement.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>