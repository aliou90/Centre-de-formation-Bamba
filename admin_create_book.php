<?php
// admin_create_book.php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Nom invalide.']);
    exit;
}

$baseDir = __DIR__ . '/assets/books/';
$bookDir = $baseDir . $name;

// Vérifier si déjà existant
if (is_dir($bookDir)) {
    echo json_encode(['success' => false, 'message' => 'Ce livre existe déjà.']);
    exit;
}

// Créer les dossiers
@mkdir($bookDir . '/images', 0777, true);
@mkdir($bookDir . '/audios', 0777, true);
@mkdir($bookDir . '/config', 0777, true);

// Créer fichiers JSON vides
file_put_contents($bookDir . '/config/chapitres.json', json_encode([]));
file_put_contents($bookDir . '/config/config.json', json_encode(['lang' => 'fr', 'trans' => 'wo'], JSON_PRETTY_PRINT));

echo json_encode(['success' => true]);
