<?php
// VÉRIFICATION ET L'ACTIVATION DE SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Inclure ta connexion à la base de données
require_once __DIR__.'/rqt_db_connect.php'; // Remplace ceci par le nom réel de ton fichier de connexion

// Récupération des données envoyées via fetch (JSON)
$data = json_decode(file_get_contents("php://input"), true);

$fullname = trim($data['fullname'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

// Validation des champs
if (!preg_match('/^[a-zA-Z0-9 ]{3,}$/', $fullname)) {
    echo json_encode(['success' => false, 'message' => 'Le nom complet doit contenir au moins 3 caractères alphanumériques.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format d\'email invalide.']);
    exit;
}

if (!empty($phone) && !preg_match('/^\+?\d{9,15}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Le téléphone doit contenir entre 9 et 15 chiffres.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
    exit;
}

// Vérification de l'unicité de l'email ou du téléphone
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR phone = :phone");
$stmt->execute(['email' => $email, 'phone' => $phone]);
if ($stmt->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'Email ou téléphone déjà utilisé.']);
    exit;
}

// Hachage du mot de passe
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insertion de l'utilisateur
$insert = $db->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (:fullname, :email, :phone, :password)");
$success = $insert->execute([
    'fullname' => $fullname,
    'email' => $email,
    'phone' => $phone,
    'password' => $hashedPassword
]);

if ($success) {
    $userId = $db->lastInsertId();

    // Récupération des infos complètes de l'utilisateur
    $userStmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // (Optionnel) envoi de mail
    include_once __DIR__.'/rqt_mailer.php';
    send_welcome_mail($app, $user);

    // Stockage en session
    $_SESSION['user'] = $user;

    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription.']);
}
?>
