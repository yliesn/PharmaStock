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

// Fonction pour lire le fichier JSON de configuration
function getAppConfig($key = null) {
    $configPath = __DIR__ . '/../config/app_config.json';

    if (!file_exists($configPath)) {
        throw new Exception("Le fichier de configuration n'existe pas : " . $configPath);
    }

    $configContent = file_get_contents($configPath);
    $config = json_decode($configContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erreur lors du décodage du fichier JSON : " . json_last_error_msg());
    }

    if ($key !== null) {
        return $config[$key] ?? null;
    }

    return $config;
}
