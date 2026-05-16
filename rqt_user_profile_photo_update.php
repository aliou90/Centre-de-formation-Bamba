<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
    exit;
}

if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Aucune image valide reçue.']);
    exit;
}

require_once __DIR__ . '/rqt_db_connect.php';

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

$tmpFile = $_FILES['profile_image']['tmp_name'];
$mimeType = mime_content_type($tmpFile) ?: '';

if (!isset($allowedMimeTypes[$mimeType])) {
    echo json_encode(['status' => 'error', 'message' => 'Format non supporté. Utilisez JPG, PNG ou WEBP.']);
    exit;
}

$uploadDir = __DIR__ . '/assets/images/profiles';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    echo json_encode(['status' => 'error', 'message' => 'Impossible de créer le dossier de profil.']);
    exit;
}

$extension = $allowedMimeTypes[$mimeType];
$filename = 'user_' . (int) $_SESSION['user']['id'] . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . '/' . $filename;
$relativePath = 'assets/images/profiles/' . $filename;

if (!move_uploaded_file($tmpFile, $targetPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Impossible d’enregistrer l’image.']);
    exit;
}

$stmt = $db->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$currentImage = $stmt->fetchColumn();

$update = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
$success = $update->execute([$relativePath, $_SESSION['user']['id']]);

if (!$success) {
    echo json_encode(['status' => 'error', 'message' => 'Impossible de mettre à jour la photo de profil.']);
    exit;
}

$_SESSION['user']['profile_image'] = $relativePath;

if (!empty($currentImage)) {
    $oldFile = __DIR__ . '/' . ltrim($currentImage, '/');
    if (is_file($oldFile) && strpos($oldFile, '/assets/images/profiles/user_') !== false) {
        @unlink($oldFile);
    }
}

echo json_encode([
    'status' => 'ok',
    'profile_image' => $relativePath
]);
