<?php
/**
 * Test complet du système de certificats
 */

require_once __DIR__ . '/rqt_db_connect.php';
require_once __DIR__ . '/lib_certificate.php';

// Créer un nouvel utilisateur de test
$testEmail = 'test' . time() . '@bamba.local';
$testPassword = password_hash('TestPass123', PASSWORD_BCRYPT);
$testPhone = '2217000' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);  // Numéro unique
$testName = 'Test Certificate User ' . time();

$stmt = $db->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (:name, :email, :phone, :password)");
$stmt->execute([
    'name' => $testName,
    'email' => $testEmail,
    'phone' => $testPhone,
    'password' => $testPassword
]);

$user_id = $db->lastInsertId();
$book_title = "Ad Dàliyatu (ar-wo)";

echo "=== TEST CERTIFICAT COMPLET ===\n";
echo "Nouvel utilisateur créé:\n";
echo "  - ID: $user_id\n";
echo "  - Nom: $testName\n";
echo "  - Email: $testEmail\n";
echo "---\n";

// 1. Créer un certificat
$result = CertificateManager::createCertificate($db, $user_id, $book_title, 100);
echo "1. Création certificat: " . ($result['status'] === 'created' ? '✅' : '❌') . "\n";
echo "   - Status: " . $result['status'] . "\n";
echo "   - Message: " . $result['message'] . "\n";

if ($result['status'] === 'created') {
    $certificate_id = $result['certificate_id'];
    echo "   - Certificate ID: " . $certificate_id . "\n";
    
    // 2. Récupérer les données du certificat
    $certData = CertificateManager::getCertificatePdfData($db, $user_id, $certificate_id);
    echo "2. Récupération données PDF: " . ($certData ? '✅' : '❌') . "\n";
    
    if ($certData) {
        echo "   - Utilisateur: " . $certData['user_name'] . "\n";
        echo "   - Livre (Latin): " . $certData['name_latin'] . "\n";
        echo "   - Livre (Arabe): " . ($certData['name_arabic'] ?: '(vide)') . "\n";
        echo "   - Progression: " . $certData['progression'] . "%\n";
        echo "   - Date: " . $certData['completion_date'] . "\n";
    }
    
    // 3. Vérifier le certificat
    $verified = CertificateManager::verifyCertificateId($db, $certificate_id);
    echo "3. Vérification certificat: " . ($verified ? '✅' : '❌') . "\n";
    
    // 4. Tester l'API de vérification des certificats
    $_SESSION['user'] = ['id' => $user_id];
    
    // Simulation de la requête à rqt_certificate_check.php
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
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "4. Requête API rqt_certificate_check.php:\n";
    foreach ($certificates as $cert) {
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
        
        echo "   ✅ Certificat trouvé:\n";
        echo "      - ID: " . $cert['certificate_id'] . "\n";
        echo "      - Livre (Latin): " . $bookConfig['name_latin'] . "\n";
        echo "      - Livre (Arabe): " . ($bookConfig['name_arabic'] ?: '(pas défini)') . "\n";
        echo "      - Progression: " . $cert['progression'] . "%\n";
    }
}

echo "\n=== TEST TERMINÉ ===\n";
?>
