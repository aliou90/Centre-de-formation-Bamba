<?php
// rqt_user_books_get.php
// Récupérer les livres de l'utilisateur connecté
// VÉRIFICATION ET L'ACTIVATION DE SESSION
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté']);
    exit;
}

try {
    // Connexion à la base de données
    require_once __DIR__ . '/rqt_db_connect.php'; // remplace par ton fichier de connexion

    // Vérifier si l'utilisateur est connecté
    $user_id = $_SESSION['user']['id'];

    $stmt = $db->prepare("SELECT title, progression, last_page, start_date FROM books WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter les noms arabes depuis les fichiers config
    $books = [];
    foreach ($results as $book) {
        $title = $book['title'];
        $progression = $book['progression'];
        $last_page = $book['last_page'];
        $configPath = __DIR__ . "/assets/books/" . $title . "/config/config.json";

        $config = [];
        if (file_exists($configPath)) {
            $json = file_get_contents($configPath);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $config = $decoded;
            }
        }
        
        // Fusionner les données de base avec la config du livre
        $books[] = array_merge(
            [
                'title' => $title, 
                'progression' => $progression, 
                'last_page' => $last_page
            ],
            $config // ceci ajoutera tous les paramètres du config.json
        );        
    }

    echo json_encode(['status' => 'ok', 'books' => $books]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
