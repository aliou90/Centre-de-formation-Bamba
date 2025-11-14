<?php
header('Content-Type: application/json');

try {
    // Connexion à la base SQLite
    require_once __DIR__.'/rqt_db_connect.php'; // remplace par ton fichier de connexion

    // Vérification de la méthode POST
    $id = $_POST['id'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($id) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Mot de passe invalide.']);
        exit;
    } elseif (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = :password, state = 1, activation_code = NULL WHERE id = :id");
    $result = $stmt->execute([
        'password' => $hashedPassword,
        'id' => $id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
exit;
