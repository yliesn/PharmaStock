<?php
// Page de scan de code-barres pour les fournitures
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect('index.php');
}

define('ROOT_PATH', dirname(dirname(__DIR__)));
$page_title = "Scanner un code-barres";
include_once ROOT_PATH . '/includes/header.php';
?>

<div class="container mt-4">
    <h1 class="h3 mb-4"><i class="fas fa-barcode me-2"></i>Scanner un code-barres</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div id="barcode-scanner" style="width:100%;max-width:400px;height:250px;margin:auto;border:1px solid #ccc;"></div>
                    <div id="scan-result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QuaggaJS pour le scan de code-barres 1D -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function() {
    var resultNode = document.getElementById('scan-result');
    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: document.querySelector('#barcode-scanner'),
            constraints: {
                facingMode: "environment"
            }
        },
        decoder: {
            readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
        },
        locate: true
    }, function(err) {
        if (err) {
            resultNode.innerHTML = '<div class="alert alert-danger">Erreur caméra : ' + err + '</div>';
            return;
        }
        Quagga.start();
    });
    Quagga.onDetected(function(data) {
        var code = data.codeResult.code;
        // Extraction de l'ID après 'PH', suppression des zéros initiaux
        var id = null;
        var match = code.match(/PH(\d+)/i);
        if (match) {
            id = match[1].replace(/^0+/, '');
        }
        if (id) {
            resultNode.innerHTML = '<div class="alert alert-success">Code scanné : <b>' + code + '</b><br>Redirection vers l\'article #' + id + '...</div>';
            Quagga.stop();
            window.location.href = 'view.php?id=' + encodeURIComponent(id);
        } else {
            resultNode.innerHTML = '<div class="alert alert-danger">Format de code-barres non reconnu.<br>Code : <b>' + code + '</b></div>';
        }
    });
});
</script>

<style>
#barcode-scanner video, #barcode-scanner canvas {
    width: 100% !important;
    max-width: 400px !important;
    height: 250px !important;
    object-fit: cover;
    margin: auto;
    display: block;
    border-radius: 8px;
}
#barcode-scanner {
    width: 100%;
    max-width: 400px;
    height: 250px;
    margin: auto;
    position: relative;
    overflow: hidden;
    background: #222;
    border-radius: 8px;
}
</style>

<?php include_once ROOT_PATH . '/includes/footer.php'; ?>
