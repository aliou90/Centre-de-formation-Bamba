<?php
$data = json_decode(file_get_contents("php://input"), true);
$old = trim($data['oldName'] ?? '');
$new = trim($data['newName'] ?? '');

// Enlever les anciennes infos de langue du nouveau nom
// Règles de suppression :
// - enlever les parenthèses qui ont entre 0 et 5 caractères entre elles
// - et contiennent un tiret à l'intérieur
$newNameWithoutLang = preg_replace('/\s*\([^\)]{0,5}-[^\)]{0,5}\)\s*/', '', $new);
$oldNameWithoutLang = preg_replace('/\s*\([^\)]{0,5}-[^\)]{0,5}\)\s*/', '', $old);

// Vérification des noms
if (!$old || !$new || !file_exists(__DIR__."/assets/books/$old")) {
    echo json_encode(['success' => false, 'message' => 'Livre introuvable:' . $old]);
    exit;
}

if (file_exists(__DIR__."/assets/books/$new") && $old !== $oldNameWithoutLang) {
    echo json_encode(['success' => false, 'message' => 'Un livre avec ce nom existe déjà.']);
    exit;
}

// Vérification des permissions
if (!is_writable(__DIR__."/assets/books/$old")) {
    echo json_encode(['success' => false, 'message' => 'Permission refusée.']);
    exit;
}
// Vérification du nom
if (preg_match('/[\/\\\:\*\?\"<>\|]/', $new)) {
    echo json_encode(['success' => false, 'message' => 'Nom invalide.']);
    exit;
}
// Vérification de la longueur
if (strlen($new) > 255) {
    echo json_encode(['success' => false, 'message' => 'Le nom est trop long.']);
    exit;
}

// Récupération des informations de langue pour le nom 
$oldConfigFile = __DIR__ . "/assets/books/$old/config/config.json";

// Si le fichier existe, on lit son contenu ; sinon, on crée une config vide
if (file_exists($oldConfigFile)) {
    $oldConfig = json_decode(file_get_contents($oldConfigFile), true);
    if (!is_array($oldConfig)) {
        $oldConfig = [];
    }
} else {
    $oldConfig = [];
    // Créer le dossier config s’il n’existe pas
    $configDir = dirname($oldConfigFile);
    if (!is_dir($configDir)) {
        mkdir($configDir, 0775, true);
    }
}

// Préparer le nouveau avec les informations de langue
$bookLang = ' (' . $oldConfig['lang'] ?? ''; // Langue écriture du livre
$bookTrans = '-' . $oldConfig['trans'] . ')'  ?? ''; // Langue traduction du livre

// Ajouter les infos de langue au nouveau nom
$newFullName = $newNameWithoutLang . $bookLang . $bookTrans;

// Renommer le dossier
rename(__DIR__."/assets/books/$old", __DIR__."/assets/books/$newFullName");

// Mettre à jour le nom dans la base de données
// Connexion à la base de données
require __DIR__ . '/rqt_db_connect.php';

// Mettre à jour le nom dans le fichier de configuration
$configFile = __DIR__ . "/assets/books/$newFullName/config/config.json";
// Récupérer le contenu du fichier de configuration
$config = json_decode(file_get_contents($configFile), true);
if (!is_array($config)) {
    $config = [];
}
// Mettre à jour le nom latin du livre dans le fichier de configuration
$config['nomLatin'] = $newFullName;

// Sauvegarder la configuration
file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Mise à jour dans la base de données (assumant que le champ s'appelle 'title')
$updateQuery = $db->prepare("UPDATE books SET title = :new WHERE title = :old");
$success = $updateQuery->execute(['new' => $newFullName, 'old' => $old]);

echo json_encode(['success' => true , 'message' => 'Livre renommé avec succès.', 'newName' => $newFullName]);
