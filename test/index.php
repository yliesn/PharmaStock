<?php 
session_start(); 
// Vérifier si l'utilisateur est connecté et a les droits d'administrateur 
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'ADMIN') {     
    // Rediriger vers le tableau de bord avec un message d'erreur     
    $_SESSION['error_message'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";     
    header('Location: ../dashboard.php');     
    exit; 
} 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Console SQL Web</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --secondary: #3f37c9;
      --accent: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --success: #4cc9f0;
      --border-radius: 8px;
    }
    
    body {
      background-color: #f5f7fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding-top: 40px;
      color: var(--dark);
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .card {
      border-radius: var(--border-radius);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      border: none;
      margin-bottom: 30px;
    }
    
    .card-header {
      background-color: #fff;
      border-bottom: 1px solid rgba(0, 0, 0, 0.07);
      padding: 1.2rem 1.5rem;
      display: flex;
      align-items: center;
    }
    
    .card-header h2 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 600;
    }
    
    .card-body {
      padding: 1.5rem;
    }
    
    textarea.sql-editor {
      font-family: 'Consolas', 'Monaco', monospace;
      resize: vertical;
      min-height: 180px;
      padding: 12px;
      border-radius: var(--border-radius);
      border: 1px solid #dee2e6;
      font-size: 15px;
      line-height: 1.5;
      transition: border-color 0.15s ease-in-out;
    }
    
    textarea.sql-editor:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
    }
    
    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
    }
    
    .btn-primary:hover {
      background-color: var(--secondary);
      border-color: var(--secondary);
    }
    
    .btn-success {
      background-color: var(--success);
      border-color: var(--success);
    }
    
    .result-container {
      background-color: #fff;
      border-radius: var(--border-radius);
      padding: 0;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .result-header {
      padding: 15px 20px;
      background-color: var(--light);
      border-bottom: 1px solid #dee2e6;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .result-body {
      padding: 0;
      max-height: 500px;
      overflow-x: auto;
      overflow-y: auto;
    }
    
    table {
      width: 100%;
      margin-bottom: 0;
      border-collapse: collapse;
    }
    
    table thead th {
      position: sticky;
      top: 0;
      background-color: #f8f9fa; 
      z-index: 1;
      padding: 12px 15px;
      border-bottom: 2px solid #dee2e6;
      font-weight: 600;
    }
    
    table tbody td {
      padding: 12px 15px;
      border-bottom: 1px solid #dee2e6;
      vertical-align: middle;
    }
    
    table tbody tr:nth-child(even) {
      background-color: rgba(0, 0, 0, 0.02);
    }
    
    table tbody tr:hover {
      background-color: rgba(67, 97, 238, 0.05);
    }
    
    .status-bar {
      padding: 8px 15px;
      background-color: var(--light);
      border-top: 1px solid #dee2e6;
      font-size: 0.875rem;
      color: #666;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 0.5rem 1rem;
      font-weight: 500;
    }
    
    .icon-header {
      margin-right: 10px;
      color: var(--primary);
      font-size: 1.25rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-database icon-header"></i>
        <h2>Console SQL Web</h2>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label for="sql" class="form-label fw-bold">Requête SQL :</label>
          <textarea id="sql" class="form-control sql-editor" placeholder="SELECT * FROM utilisateurs LIMIT 10;"></textarea>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" onclick="runQuery()">
            <i class="fas fa-play"></i> Exécuter
          </button>
          <button class="btn btn-success" onclick="exportCSV()">
            <i class="fas fa-file-export"></i> Exporter CSV
          </button>
        </div>
      </div>
    </div>
    
    <div class="card result-container">
      <div class="result-header">
        <h5 class="mb-0">Résultats</h5>
        <span class="badge bg-primary" id="row-count">0 lignes</span>
      </div>
      <div class="result-body" id="result">
        <div class="p-4 text-center text-muted">
          <i class="fas fa-database fa-3x mb-3"></i>
          <p>Exécutez une requête SQL pour afficher les résultats ici</p>
        </div>
      </div>
      <div class="status-bar" id="query-status">Prêt</div>
    </div>
  </div>
  <script src="script.js"></script>
</body>
</html>