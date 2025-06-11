<?php

$host = '127.0.0.1';
$dbname = 'pharmacie';
$user = 'root';
$pass = '44oLjF93cS2b8Tz';
// Connexion PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Requête SQL reçue
$query = $_POST['query'] ?? '';
$export = isset($_POST['export']) && $_POST['export'] == '1';

// Si export demandé
if ($export) {
    try {
        $stmt = $pdo->query($query);

        if ($stmt->columnCount() === 0) {
            die("La requête ne retourne aucun résultat exportable.");
        }

        // En-têtes pour téléchargement CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="resultats.csv"');

        $output = fopen("php://output", "w");

        // En-têtes de colonnes
        $headers = [];
        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $meta = $stmt->getColumnMeta($i);
            $headers[] = $meta['name'];
        }
        fputcsv($output, $headers);

        // Lignes de données
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;

    } catch (PDOException $e) {
        die("Erreur SQL : " . htmlspecialchars($e->getMessage()));
    }
}

// Affichage HTML si pas d'export
if (trim($query) === '') {
    echo "Aucune requête envoyée.";
    exit;
}

try {
    $stmt = $pdo->query($query);

    if ($stmt->columnCount() > 0) {
        // Requête SELECT
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $col = $stmt->getColumnMeta($i);
            echo "<th>" . htmlspecialchars($col['name']) . "</th>";
        }
        echo "</tr>";

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
    } else {
        // Requête INSERT / UPDATE / DELETE
        $count = $stmt->rowCount();
        echo "Requête exécutée avec succès. $count ligne(s) affectée(s).";
    }

} catch (PDOException $e) {
    echo "Erreur SQL : " . htmlspecialchars($e->getMessage());
}