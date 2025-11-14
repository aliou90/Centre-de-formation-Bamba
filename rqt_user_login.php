<?php
// VÉRIFICATION ET L'ACTIVATION DE SESSION
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

// Connexion à la base
require_once __DIR__.'/rqt_db_connect.php'; // fichier de connexion

// Récupérer et décoder les données JSON
$input = json_decode(file_get_contents('php://input'), true);
$login = trim($input['login'] ?? '');
$password = $input['password'] ?? '';

// Vérifier si l'utilisateur existe (par email ou téléphone)
$stmt = $db->prepare("SELECT * FROM users WHERE email = :login");
$stmt->execute([':login' => $login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user']['id'] = $user['id'];
    $_SESSION['user']['fullname'] = $user['fullname'];
    $_SESSION['user']['email'] = $user['email'];
    $_SESSION['user']['phone'] = $user['phone'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect.']);
}
