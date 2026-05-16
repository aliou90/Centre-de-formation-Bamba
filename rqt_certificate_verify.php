<?php
/**
 * rqt_certificate_verify.php
 * Vérifier/valider un ID de certificat
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$certificate_id = $input['certificate_id'] ?? null;

if (!$certificate_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de certificat manquant']);
    exit;
}

try {
    require_once __DIR__ . '/rqt_db_connect.php';
    require_once __DIR__ . '/lib_certificate.php';
    
    // Vérifier le certificat
    $result = CertificateManager::verifyCertificateId($db, $certificate_id);
    
    if ($result) {
        echo json_encode([
            'status' => 'valid',
            'message' => 'Certificat valide',
            'certificate_id' => $result['certificate_id'],
            'user_name' => $result['user_name'],
            'book_title' => $result['book_title'],
            'completion_date' => $result['completion_date'],
            'progression' => $result['progression']
        ]);
    } else {
        echo json_encode([
            'status' => 'invalid',
            'message' => 'ID de certificat invalide ou inexistant'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
