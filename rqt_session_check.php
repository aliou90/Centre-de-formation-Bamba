<?php
// rqt_session_check.php
// Ce fichier est utilisé pour récupérer les informations de l'utilisateur connecté
// VÉRIFICATION ET L'ACTIVATION DE SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

if (isset($_SESSION['user'])) {
    echo json_encode([
        'status' => 'ok',
        'user' => $_SESSION['user']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Utilisateur non connecté'
    ]);
}
?>
