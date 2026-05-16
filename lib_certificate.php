<?php
/**
 * Classe utilitaire pour gérer les certificats
 */
class CertificateManager {
    /**
     * Récupérer les noms du livre depuis son config.json.
     */
    public static function resolveBookNames($book_title) {
        $configPath = __DIR__ . "/assets/books/" . $book_title . "/config/config.json";

        $bookConfig = [
            'name_latin' => $book_title,
            'name_arabic' => ''
        ];

        if (!file_exists($configPath)) {
            return $bookConfig;
        }

        $json = file_get_contents($configPath);
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $bookConfig;
        }

        $bookConfig['name_latin'] = $decoded['nomLatin']
            ?? $decoded['name']
            ?? $book_title;
        $bookConfig['name_arabic'] = $decoded['nomArabe']
            ?? $decoded['name_arabic']
            ?? '';

        return $bookConfig;
    }
    
    /**
     * Générer un ID de certificat unique
     * Format: CERT-{timestamp}-{random}
     * Exemple: CERT-1715779200-A7K9M2
     */
    public static function generateCertificateId() {
        $timestamp = time();
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        return "CERT-{$timestamp}-{$random}";
    }
    
    /**
     * Créer un certificat dans la base de données
     */
    public static function createCertificate($pdo, $user_id, $book_title, $progression) {
        try {
            // Vérifier si un certificat existe déjà
            $stmt = $pdo->prepare("SELECT id FROM certificates WHERE user_id = :user_id AND book_title = :book_title");
            $stmt->execute(['user_id' => $user_id, 'book_title' => $book_title]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Le certificat existe déjà
                return ['status' => 'exists', 'message' => 'Certificat déjà généré'];
            }
            
            // Générer un nouvel ID de certificat
            $certificateId = self::generateCertificateId();
            
            // Insérer le certificat
            $stmt = $pdo->prepare("
                INSERT INTO certificates (user_id, book_title, certificate_id, completion_date, progression)
                VALUES (:user_id, :book_title, :certificate_id, CURRENT_TIMESTAMP, :progression)
            ");
            
            $stmt->execute([
                'user_id' => $user_id,
                'book_title' => $book_title,
                'certificate_id' => $certificateId,
                'progression' => $progression
            ]);
            
            return [
                'status' => 'created',
                'message' => 'Certificat créé avec succès',
                'certificate_id' => $certificateId
            ];
            
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Récupérer les infos d'un certificat
     */
    public static function getCertificate($pdo, $user_id, $book_title) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM certificates 
                WHERE user_id = :user_id AND book_title = :book_title
            ");
            
            $stmt->execute(['user_id' => $user_id, 'book_title' => $book_title]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $cert ?: null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Vérifier si un certificat existe pour un utilisateur et un cours
     */
    public static function hasCertificate($pdo, $user_id, $book_title) {
        $cert = self::getCertificate($pdo, $user_id, $book_title);
        return $cert !== null;
    }
    
    /**
     * Récupérer les détails pour le PDF du certificat
     */
    public static function getCertificatePdfData($pdo, $user_id, $certificate_id) {
        try {
            // Récupérer les infos du certificat
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    u.fullname as user_name
                FROM certificates c
                JOIN users u ON c.user_id = u.id
                WHERE c.user_id = :user_id AND c.certificate_id = :certificate_id
            ");
            
            $stmt->execute(['user_id' => $user_id, 'certificate_id' => $certificate_id]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cert) {
                return null;
            }
            
            $cert = array_merge($cert, self::resolveBookNames($cert['book_title']));
            
            return $cert;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Vérifier si un certificat_id est valide
     */
    public static function verifyCertificateId($pdo, $certificate_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    u.fullname as user_name
                FROM certificates c
                JOIN users u ON c.user_id = u.id
                WHERE c.certificate_id = :certificate_id
            ");
            
            $stmt->execute(['certificate_id' => $certificate_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result;
            
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
