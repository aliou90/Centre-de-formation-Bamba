<?php
// VÉRIFICATION ET L'ACTIVATION DE SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
    exit;
}

// Connexion à la base
require_once __DIR__.'/rqt_db_connect.php'; // remplace par ton fichier de connexion

$userId = $_SESSION['user']['id'];
$fullname = trim($_POST['fullname'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$country = trim($_POST['country'] ?? '');
$password = $_POST['userOldPassword'] ?? '';
$newPassword = $_POST['userNewPassword'] ?? '';

// Vérifier les champs requis
if ($fullname === '' || $phone === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Tous les champs obligatoires doivent être remplis.']);
    exit;
}

// Vérifier le mot de passe actuel
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Mot de passe actuel incorrect.']);
    exit;
}

// Mettre à jour les données
$newHashedPassword = $newPassword ? password_hash($newPassword, PASSWORD_DEFAULT) : $user['password'];
$update = $db->prepare("UPDATE users SET fullname = ?, phone = ?, country = ?, password = ? WHERE id = ?");
$success = $update->execute([$fullname, $phone, $country !== '' ? $country : null, $newHashedPassword, $userId]);

if ($success) {
    // Mettre à jour la session
    $_SESSION['user']['fullname'] = $fullname;
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['country'] = $country;
    echo json_encode([
        'status' => 'ok',
        'user' => [
            'fullname' => $fullname,
            'email' => $_SESSION['user']['email'] ?? '',
            'phone' => $phone,
            'country' => $country,
            'profile_image' => $_SESSION['user']['profile_image'] ?? ''
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la mise à jour.']);
}
