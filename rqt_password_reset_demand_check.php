<?php
header('Content-Type: application/json');

// Connexion à la base SQLite
require_once __DIR__.'/rqt_db_connect.php'; // remplace par ton fichier de connexion

// Récupération des données GET
$id = $_GET['id'] ?? '';
$activationCode = $_GET['activation_code'] ?? '';

if (empty($id) || empty($activationCode)) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
    exit;
}

// Vérification dans la base
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Aucun utilisateur trouvé.']);
    exit;
}

// Vérification du code et état
if ($user['state'] == 0 && $user['activation_code'] === $activationCode) {
    echo json_encode(['success' => true, 'message' => 'Créez un nouveau mot de passe s\'il vous plaît.']);
} elseif ($user['state'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Ce compte a été déjà réactivé.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ce lien d\'activation a expiré.']);
}
