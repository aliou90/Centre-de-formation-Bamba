#!/usr/bin/env php
<?php
/**
 * TEST COMPLET DU SYSTÈME DE CERTIFICATS
 * Exécution: php test-certificates.php
 * 
 * Ce script teste tous les composants du système de certificats
 */

class CertificateSystemTester {
    private $db;
    private $user_id = null;
    private $test_book = 'Test-Book-Certificate';
    private $test_certificate_id = null;
    
    public function __construct() {
        echo "🧪 Système de test des certificats\n";
        echo "==================================\n\n";
    }
    
    public function runAllTests() {
        $this->step1_checkDatabase();
        $this->step2_checkFiles();
        $this->step3_testClassLibrary();
        $this->step4_createTestData();
        $this->step5_testCertificateCreation();
        $this->step6_testCertificateRetrieval();
        $this->step7_testVerification();
        $this->step8_cleanUp();
        
        echo "\n✅ Tous les tests sont terminés!\n";
    }
    
    private function step1_checkDatabase() {
        echo "📌 ÉTAPE 1: Vérification de la base de données\n";
        echo "-------------------------------------------\n";
        
        try {
            require_once __DIR__ . '/rqt_db_connect.php';
            $this->db = $db;
            
            // Vérifier la table users
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'users'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                echo "✅ Table 'users' trouvée\n";
            } else {
                echo "⚠️ Table 'users' non trouvée\n";
            }
            
            // Vérifier la table books
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'books'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                echo "✅ Table 'books' trouvée\n";
            } else {
                echo "⚠️ Table 'books' non trouvée\n";
            }
            
            // Vérifier la table certificates
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'certificates'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                echo "✅ Table 'certificates' trouvée\n";
            } else {
                echo "❌ Table 'certificates' MANQUANTE - Exécutez init_mysql_db.php ou init_db.php\n";
                return false;
            }
            
            echo "\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Erreur: {$e->getMessage()}\n\n";
            return false;
        }
    }
    
    private function step2_checkFiles() {
        echo "📌 ÉTAPE 2: Vérification des fichiers\n";
        echo "-------------------------------------\n";
        
        $files = [
            'lib_certificate.php',
            'rqt_certificate_check.php',
            'rqt_certificate_verify.php',
            'rqt_certificate_download.php',
            'rqt_certificate_generate_pdf.php',
            'assets/js/certificate-manager.js',
            'assets/images/logos/certif_logo.png'
        ];
        
        foreach ($files as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo "✅ $file\n";
            } else {
                echo "❌ $file - MANQUANT\n";
            }
        }
        
        echo "\n";
    }
    
    private function step3_testClassLibrary() {
        echo "📌 ÉTAPE 3: Test de la classe CertificateManager\n";
        echo "----------------------------------------------\n";
        
        try {
            require_once __DIR__ . '/lib_certificate.php';
            
            // Tester la génération d'ID
            $cert_id = CertificateManager::generateCertificateId();
            if (preg_match('/^CERT-\d+-[A-F0-9]{6}$/', $cert_id)) {
                echo "✅ Génération ID certificat: $cert_id\n";
            } else {
                echo "❌ Format ID certificat invalide: $cert_id\n";
            }
            
            $this->test_certificate_id = $cert_id;
            
            echo "\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Erreur: {$e->getMessage()}\n\n";
            return false;
        }
    }
    
    private function step4_createTestData() {
        echo "📌 ÉTAPE 4: Création de données de test\n";
        echo "--------------------------------------\n";
        
        try {
            // Créer un utilisateur de test
            $test_user = [
                'fullname' => 'Test Utilisateur',
                'email' => 'test-' . time() . '@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT)
            ];
            
            try {
                $stmt = $this->db->prepare("INSERT INTO users (fullname, email, password) VALUES (:fullname, :email, :password)");
                $stmt->execute($test_user);
                $this->user_id = $this->db->lastInsertId();
                echo "✅ Utilisateur de test créé (ID: {$this->user_id})\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    throw $e;
                }
                // Récupérer l'ID de l'utilisateur existant
                $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->execute(['email' => $test_user['email']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->user_id = $user['id'];
                echo "⚠️ Utilisateur existant utilisé (ID: {$this->user_id})\n";
            }
            
            // Créer un livre de test
            $stmt = $this->db->prepare("INSERT OR IGNORE INTO books (user_id, title, progression) VALUES (:user_id, :title, :progression)");
            $stmt->execute([
                'user_id' => $this->user_id,
                'title' => $this->test_book,
                'progression' => 100
            ]);
            echo "✅ Livre de test créé\n";
            
            echo "\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Erreur: {$e->getMessage()}\n\n";
            return false;
        }
    }
    
    private function step5_testCertificateCreation() {
        echo "📌 ÉTAPE 5: Test de création de certificat\n";
        echo "-----------------------------------------\n";
        
        try {
            require_once __DIR__ . '/lib_certificate.php';
            
            $result = CertificateManager::createCertificate($this->db, $this->user_id, $this->test_book, 100);
            
            if ($result['status'] === 'created') {
                echo "✅ Certificat créé avec succès\n";
                echo "   ID: {$result['certificate_id']}\n";
                $this->test_certificate_id = $result['certificate_id'];
            } elseif ($result['status'] === 'exists') {
                echo "⚠️ Certificat existant\n";
                // Récupérer l'ID du certificat existant
                $stmt = $this->db->prepare("SELECT certificate_id FROM certificates WHERE user_id = :user_id AND book_title = :book_title");
                $stmt->execute(['user_id' => $this->user_id, 'book_title' => $this->test_book]);
                $cert = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->test_certificate_id = $cert['certificate_id'];
                echo "   ID: {$this->test_certificate_id}\n";
            } else {
                echo "❌ Erreur: {$result['message']}\n";
                return false;
            }
            
            echo "\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Erreur: {$e->getMessage()}\n\n";
            return false;
        }
    }
    
    private function step6_testCertificateRetrieval() {
        echo "📌 ÉTAPE 6: Test de récupération de certificat\n";
        echo "--------------------------------------------\n";
        
        try {
            require_once __DIR__ . '/lib_certificate.php';
            
            // Tester hasCertificate
            if (CertificateManager::hasCertificate($this->db, $this->user_id, $this->test_book)) {
                echo "✅ Certificat existant détecté\n";
            } else {
                echo "❌ Certificat non trouvé\n";
                return false;
            }
            
            // Tester getCertificate
            $cert = CertificateManager::getCertificate($this->db, $this->user_id, $this->test_book);
            if ($cert) {
                echo "✅ Données certificat récupérées\n";
                echo "   ID: {$cert['certificate_id']}\n";
                echo "   Progression: {$cert['progression']}%\n";
            } else {
                echo "❌ Erreur récupération certificat\n";
                return false;
            }
            
            echo "\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Erreur: {$e->getMessage()}\n\n";
            return false;
        }
    }
    
    private function step7_testVerification() {
        echo "📌 ÉTAPE 7: Test de vérification d'ID\n";
        echo "------------------------------------\n";
        
        try {
            require_once __DIR__ . '/lib_certificate.php';
            
            // Vérifier un ID valide
            $result = CertificateManager::verifyCertificateId($this->db, $this->test_certificate_id);
            if ($result) {
                echo "✅ Vérification ID valide réussie\n";
                echo "   User: {$result['user_name']}\n";
                echo "   Cours: {$result['book_title']}\n";
            } else {
                echo "❌ Vérification ID échouée\n";
                return false;
            }
            
            // Vérifier un ID invalide
            $invalid_result = CertificateManager::verifyCertificateId($this->db, 'CERT-INVALID');
            if ($invalid_result === null) {
                echo "✅ Rejet ID invalide correct\n";
            } else {
                echo "❌ ID invalide devrait retourner null\n";
                return false;
            }
            
            echo "\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Erreur: {$e->getMessage()}\n\n";
            return false;
        }
    }
    
    private function step8_cleanUp() {
        echo "📌 ÉTAPE 8: Nettoyage des données de test\n";
        echo "----------------------------------------\n";
        
        try {
            if ($this->user_id) {
                // Supprimer le certificat
                $stmt = $this->db->prepare("DELETE FROM certificates WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $this->user_id]);
                
                // Supprimer les livres
                $stmt = $this->db->prepare("DELETE FROM books WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $this->user_id]);
                
                // Supprimer l'utilisateur
                $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute(['id' => $this->user_id]);
                
                echo "✅ Données de test supprimées\n\n";
            }
            
        } catch (Exception $e) {
            echo "⚠️ Erreur nettoyage: {$e->getMessage()}\n\n";
        }
    }
}

// Lancer les tests
$tester = new CertificateSystemTester();
$tester->runAllTests();
?>
