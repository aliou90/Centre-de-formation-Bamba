<?php
$data = json_decode(file_get_contents("php://input"), true);
$book = trim($data['book'] ?? '');

if (!$book || !file_exists(__DIR__."/assets/books/$book")) {
    echo json_encode(['success' => false, 'message' => 'Livre introuvable.']);
    exit;
}

// Suppression récursive
function rrmdir($dir) {
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $path = "$dir/$item";
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

rrmdir(__DIR__."/assets/books/$book");

echo json_encode(['success' => true]);
