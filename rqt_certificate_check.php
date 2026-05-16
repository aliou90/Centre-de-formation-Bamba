<?php
/**
 * rqt_certificate_check.php
 * Vérifier si un utilisateur a un certificat pour un cours
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté']);
    exit;
}

try {
    require_once __DIR__ . '/rqt_db_connect.php';
    require_once __DIR__ . '/lib_certificate.php';
    
    $user_id = $_SESSION['user']['id'];
    
    // Récupérer les livres de l'utilisateur avec certificats
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
    
    $certificatesInfo = [];
    
    foreach ($certificates as $cert) {
        $book_title = $cert['book_title'];
        $bookConfig = CertificateManager::resolveBookNames($book_title);
        
        $certificatesInfo[] = [
            'book_title' => $book_title,
            'course_name_latin' => $bookConfig['name_latin'],
            'course_name_arabic' => $bookConfig['name_arabic'],
            'progression' => $cert['progression'],
            'has_certificate' => true,
            'certificate_id' => $cert['certificate_id'],
            'completion_date' => $cert['completion_date']
        ];
    }
    
    echo json_encode([
        'status' => 'ok',
        'certificates' => $certificatesInfo
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
