<?php

/**
 * Vérifie si une fonctionnalité est activée
 * @param string $featureKey La clé de la fonctionnalité à vérifier
 * @return bool True si la fonctionnalité est activée, False sinon
 */
function isFeatureEnabled($featureKey) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT value FROM FEATURE_TOGGLES WHERE feature_key = ?");
        $stmt->execute([$featureKey]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['value'] == 1;
    } catch (Exception $e) {
        error_log('Erreur lors de la vérification de la fonctionnalité ' . $featureKey . ': ' . $e->getMessage());
        return false;
    }
}
