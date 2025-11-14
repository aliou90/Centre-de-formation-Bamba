<?php
header("Content-Type: application/json");
// Ce fichier est utilisé pour gérer la réinitialisation du mot de passe

require_once __DIR__.'/rqt_db_connect.php'; // Inclure votre connexion DB + fonctions
require_once __DIR__.'/rqt_mailer.php'; // Inclure votre fonction d'envoi d'email

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Entrez une adresse email valide svp.']);
        exit;
    }

    $userQuery = $db->prepare("SELECT * FROM users WHERE email = :email");
    $userQuery->execute(['email' => $email]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $activationCode = bin2hex(random_bytes(8));
        $mailer_response = send_password_mail($app, $user, $activationCode);

        if ($mailer_response) {
            $stmt = $db->prepare("UPDATE users SET state = 0, activation_code = :activation_code WHERE email = :email");
            $stmt->execute([
                'activation_code' => $activationCode,
                'email' => $email
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Un email de réinitialisation a été envoyé. Vérifiez aussi dans vos SPAM."
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => "Erreur lors de l'envoi de l'email."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Aucun compte associé à cet email."]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Entrez votre adresse email."]);
}
?>
