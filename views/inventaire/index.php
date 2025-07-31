<?php
// Page d'accueil de l'inventaire avec bouton "Commencer l'inventaire"
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inventaire</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .inventaire-btn {
            display: block;
            margin: 60px auto 0 auto;
            padding: 20px 40px;
            font-size: 1.5em;
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 8px #eee;
            transition: background 0.2s;
        }
        .inventaire-btn:hover {
            background: #125ea2;
        }
    </style>
</head>
<body>
<?php include '../../includes/header.php'; ?>
<h1>Gestion des inventaires</h1>
<a href="create.php"><button class="inventaire-btn">Commencer un nouvel inventaire</button></a>
<?php include '../../includes/footer.php'; ?>
</body>
</html>
