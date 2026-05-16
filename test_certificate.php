<?php
/**
 * Script de test pour certificats
 * Simule un utilisateur à 100% sur un livre et vérifie la création du certificat
 */

session_start();
require_once __DIR__ . '/rqt_db_connect.php';
require_once __DIR__ . '/lib_certificate.php';

// Récupérer le dernier utilisateur inscrit
$stmt = $db->query("SELECT id, fullname, email FROM users ORDER BY id DESC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['error' => 'Aucun utilisateur trouvé']);
    exit;
}

$user_id = $user['id'];
$book_title = "Ad Dàliyatu (ar-wo)";

echo "=== TEST CERTIFICAT ===\n";
echo "Utilisateur: " . $user['fullname'] . " (ID: " . $user_id . ")\n";
echo "Livre: " . $book_title . "\n";
echo "---\n";

// 1. Vérifier si un certificat existe déjà
$hasCert = CertificateManager::hasCertificate($db, $user_id, $book_title);
echo "Certificat existant: " . ($hasCert ? "OUI" : "NON") . "\n";

// 2. Créer un certificat à 100%
$result = CertificateManager::createCertificate($db, $user_id, $book_title, 100);
echo "Création certificat: " . json_encode($result) . "\n";

// 3. Vérifier que le certificat est créé
$hasCert2 = CertificateManager::hasCertificate($db, $user_id, $book_title);
echo "Certificat après création: " . ($hasCert2 ? "OUI" : "NON") . "\n";

// 4. Récupérer les données du certificat pour le PDF
if ($result['status'] === 'created') {
    $certData = CertificateManager::getCertificatePdfData($db, $user_id, $result['certificate_id']);
    echo "Données certificat: " . json_encode($certData) . "\n";
    
    // 5. Vérifier le certificat
    $verified = CertificateManager::verifyCertificateId($db, $result['certificate_id']);
    echo "Vérification certificat: " . json_encode($verified) . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
?>
