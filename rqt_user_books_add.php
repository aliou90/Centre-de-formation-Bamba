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

    // Vérifier si le livre existe déjà pour éviter les doublons
    $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE user_id = :user_id AND title = :title");
    $stmt->execute(['user_id' => $user_id, 'title' => $title]);
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        // Ajouter le livre
        $stmt = $db->prepare("INSERT INTO books (user_id, title, progression) VALUES (:user_id, :title, 0)");
        $stmt->execute([
            'user_id' => $user_id,
            'title' => $title
        ]);
    }

    echo json_encode(['status' => 'ok', 'message' => 'Livre ajouté']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
