function runQuery() {
  const sql = document.getElementById('sql').value.trim();
  if (!sql) {
    alert('Veuillez saisir une requête SQL');
    return;
  }
  
  // Réinitialiser le compteur de lignes pendant le chargement
  document.getElementById('row-count').innerHTML = 'Chargement...';
  
  // Afficher un état de chargement
  document.getElementById('query-status').innerHTML = 'Exécution en cours...';
  document.getElementById('result').innerHTML = `
    <div class="p-4 text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="mt-2">Traitement de la requête...</p>
    </div>`;
  
  // Calculer le temps de départ
  const startTime = performance.now();
  
  // Faire la requête AJAX
  fetch('query.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'query=' + encodeURIComponent(sql)
  })
  .then(res => res.text())
  .then(html => {
    // Calculer le temps écoulé
    const endTime = performance.now();
    const executionTime = ((endTime - startTime) / 1000).toFixed(2);
    
    // Mettre à jour le contenu
    document.getElementById('result').innerHTML = html;
    
    // Mettre à jour le statut avec le temps d'exécution
    document.getElementById('query-status').innerHTML = `Requête exécutée en ${executionTime}s`;
    
    // Compter les lignes de résultat (s'il y a un tableau)
    const tableRows = document.querySelector('#result table tbody')?.querySelectorAll('tr');
    if (tableRows) {
      const rowCount = tableRows.length;
      document.getElementById('row-count').innerHTML = rowCount-1 +' lignes' ;
    } else {
      document.getElementById('row-count').innerHTML = '0 ligne';
    }
  })
  .catch(error => {
    document.getElementById('result').innerHTML = `
      <div class="p-4 text-center text-danger">
        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
        <p>Erreur lors de l'exécution de la requête</p>
        <pre class="text-start bg-light p-3 mt-3">${error.message}</pre>
      </div>`;
    document.getElementById('query-status').innerHTML = 'Erreur de requête';
    document.getElementById('row-count').innerHTML = '0 ligne';
  });
}

function exportCSV() {
  const sql = document.getElementById('sql').value.trim();
  if (!sql) {
    alert('Veuillez saisir une requête SQL');
    return;
  }
  
  // Afficher un indicateur visuel temporaire que l'export est en cours
  const originalStatus = document.getElementById('query-status').innerHTML;
  document.getElementById('query-status').innerHTML = 'Export CSV en cours...';
  
  // Crée une URL avec le paramètre export=1
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'query.php';
  form.target = '_blank';
  
  const queryInput = document.createElement('input');
  queryInput.type = 'hidden';
  queryInput.name = 'query';
  queryInput.value = sql;
  
  const exportInput = document.createElement('input');
  exportInput.type = 'hidden';
  exportInput.name = 'export';
  exportInput.value = '1';
  
  form.appendChild(queryInput);
  form.appendChild(exportInput);
  document.body.appendChild(form);
  form.submit();
  
  // Rétablir le statut original après un court délai
  setTimeout(() => {
    document.getElementById('query-status').innerHTML = originalStatus;
  }, 1000);
  
  document.body.removeChild(form);
}