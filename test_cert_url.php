<?php
/**
 * Simuler une progression à 100% et vérifier le bouton certificat
 * À appeler après une connexion en session
 */

session_start();
require_once __DIR__ . '/rqt_db_connect.php';
require_once __DIR__ . '/lib_certificate.php';

// Utiliser l'utilisateur connecté ou créer un nouveau
if (!isset($_SESSION['user'])) {
    // Créer un utilisateur de test
    $testPhone = '2217000' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $stmt = $db->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (:name, :email, :phone, :password)");
    $stmt->execute([
        'name' => 'Test Cert ' . time(),
        'email' => 'cert' . time() . '@test.local',
        'phone' => $testPhone,
        'password' => password_hash('Test123', PASSWORD_BCRYPT)
    ]);
    
    $_SESSION['user'] = [
        'id' => $db->lastInsertId(),
        'fullname' => 'Test Cert ' . time(),
        'email' => 'cert' . time() . '@test.local'
    ];
}

$user_id = $_SESSION['user']['id'];
$book_title = "Ad Dàliyatu (ar-wo)";

echo "=== SIMULATION PROGRESSION 100% ===\n";
echo "Utilisateur: ID " . $user_id . " (" . $_SESSION['user']['fullname'] . ")\n";
echo "Livre: " . $book_title . "\n";

// Simuler une progression à 100%
$result = CertificateManager::createCertificate($db, $user_id, $book_title, 100);

echo "\nRésultat création certificat:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if ($result['status'] === 'created') {
    $cert_id = $result['certificate_id'];
    echo "\n✅ Certificat créé avec succès!\n";
    echo "ID: " . $cert_id . "\n";
    
    echo "\nURL de téléchargement (HTML):\n";
    echo "http://127.0.0.1:8181/rqt_certificate_download.php?certificate_id=" . $cert_id . "&mode=html\n";
    
    echo "\nURL de téléchargement (PDF):\n";
    echo "http://127.0.0.1:8181/rqt_certificate_download.php?certificate_id=" . $cert_id . "&mode=pdf\n";
    
    // Vérifier via l'API
    echo "\n=== Vérification via l'API ===\n";
    $stmt = $db->prepare("
        SELECT 
            c.certificate_id, 
            c.completion_date, 
            c.book_title,
            c.progression
        FROM certificates c
        WHERE c.user_id = :user_id
    ");
    
    $stmt->execute(['user_id' => $user_id]);
    $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Certificats trouvés: " . count($certs) . "\n";
    foreach ($certs as $cert) {
        $book_title = $cert['book_title'];
        $configPath = __DIR__ . "/assets/books/" . $book_title . "/config/config.json";
        $bookConfig = [
            'name_latin' => $book_title,
            'name_arabic' => ''
        ];
        
        if (file_exists($configPath)) {
            $json = file_get_contents($configPath);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $bookConfig['name_latin'] = $decoded['name'] ?? $book_title;
                $bookConfig['name_arabic'] = $decoded['name_arabic'] ?? '';
            }
        }
        
        echo "\n📜 Certificat:\n";
        echo "   - ID: " . $cert['certificate_id'] . "\n";
        echo "   - Livre: " . $bookConfig['name_latin'] . "\n";
        if ($bookConfig['name_arabic']) {
            echo "   - Arabe: " . $bookConfig['name_arabic'] . "\n";
        }
        echo "   - Progression: " . $cert['progression'] . "%\n";
        echo "   - Date: " . $cert['completion_date'] . "\n";
    }
}

echo "\n=== Fin de la simulation ===\n";
?>
