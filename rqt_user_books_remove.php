<?php
// Activation de la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté']);
    exit;
}

try {
    require_once __DIR__ . '/rqt_db_connect.php'; // Ton fichier de connexion
    $user_id = $_SESSION['user']['id'];

    // Récupérer les données envoyées
    $data = json_decode(file_get_contents('php://input'), true);
    $title = $data['title'] ?? '';

    if (!$title) {
        echo json_encode(['status' => 'error', 'message' => 'Titre manquant']);
        exit;
    }

    // Supprimer le livre
    $stmt = $db->prepare("DELETE FROM books WHERE user_id = :user_id AND title = :title");
    $stmt->execute([
        'user_id' => $user_id,
        'title' => $title
    ]);

    echo json_encode(['status' => 'ok', 'message' => 'Livre retiré']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
