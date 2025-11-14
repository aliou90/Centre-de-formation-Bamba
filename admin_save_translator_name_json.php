<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['book']) || !isset($data['translatorName']) || empty(trim($data['translatorName']))) {
    // Vérification des paramètres
    // Si le livre ou le nom arabe est manquant, on renvoie une erreur
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$book = basename($data['book']); // sécurité : évite les chemins relatifs
$configFile = __DIR__ . "/assets/books/$book/config/config.json";

// Si le fichier existe, on lit son contenu ; sinon, on crée une config vide
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (!is_array($config)) {
        $config = [];
    }
} else {
    $config = [];
    // Créer le dossier config s’il n’existe pas
    $configDir = dirname($configFile);
    if (!is_dir($configDir)) {
        mkdir($configDir, 0775, true);
    }
}

// Mettre à jour uniquement type
$config['traducteur'] = $data['translatorName'];

// Sauvegarder la configuration
file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true]);
