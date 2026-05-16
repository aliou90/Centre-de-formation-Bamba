<?php
/**
 * Script de connexion pour tester le certificat
 */

session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: http://127.0.0.1:8181/');
    exit;
}

if (isset($_GET['login'])) {
    require_once __DIR__ . '/rqt_db_connect.php';
    
    // Récupérer l'utilisateur avec les certificats les plus récents
    $stmt = $db->query("
        SELECT u.id, u.fullname, u.email, COUNT(c.id) as cert_count
        FROM users u
        LEFT JOIN certificates c ON u.id = c.user_id
        GROUP BY u.id
        ORDER BY u.id DESC
        LIMIT 1
    ");
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'fullname' => $user['fullname'],
            'email' => $user['email']
        ];
        
        echo "✅ Connecté en tant que: " . $user['fullname'] . " (ID: " . $user['id'] . ")\n";
        echo "Certificats: " . $user['cert_count'] . "\n";
        echo "\nAccédez à: http://127.0.0.1:8181/\n";
        exit;
    }
}

// Afficher l'état de la session
if (isset($_SESSION['user'])) {
    echo "✅ Session active\n";
    echo "Utilisateur: " . $_SESSION['user']['fullname'] . " (ID: " . $_SESSION['user']['id'] . ")\n";
    echo "\nDéconnexion: ?logout=1\n";
} else {
    echo "❌ Pas de session\n";
    echo "Connexion: ?login=1\n";
}
?>
