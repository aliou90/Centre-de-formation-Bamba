<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['book']) || !isset($data['chapitres'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Champs requis manquants.']);
    exit;
}

$book = basename($data['book']);
$savePath = __DIR__ . "/assets/books/$book/config/chapitres.json";

file_put_contents($savePath, json_encode($data['chapitres'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true]);
