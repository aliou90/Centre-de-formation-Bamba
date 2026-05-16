<?php
/**
 * rqt_certificate_download.php
 * Générer et télécharger le certificat en PDF
 * Supporte deux modes:
 * 1. mode=html -> retourne du HTML à imprimer
 * 2. mode=pdf -> génère directement un PDF (si TCPDF est disponible)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Non autorisé');
}

require_once __DIR__ . '/rqt_db_connect.php';
require_once __DIR__ . '/lib_certificate.php';

$user_id = $_SESSION['user']['id'];
$certificate_id = $_GET['certificate_id'] ?? null;
$mode = $_GET['mode'] ?? 'html'; // 'html' ou 'pdf'

if (!$certificate_id) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID de certificat manquant']);
    exit;
}

if ($mode === 'pdf') {
    header('Location: rqt_certificate_generate_pdf.php?certificate_id=' . urlencode($certificate_id));
    exit;
}

try {
    // Récupérer les données du certificat
    $certData = CertificateManager::getCertificatePdfData($db, $user_id, $certificate_id);
    
    if (!$certData) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Certificat non trouvé']);
        exit;
    }
    
    // Formater la date
    $completion_date = new DateTime($certData['completion_date']);
    $date_formatted = $completion_date->format('d/m/Y');
    
    // Mode HTML pour impression/téléchargement
    if ($mode === 'html') {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="certificat_' . $certData['certificate_id'] . '.html"');

        $logoPath = __DIR__ . '/assets/images/logos/certif_logo.png';
        $logoData = '';

        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMimeType = 'image/png';
        }

        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat de Réussite - {{CERTIFICATE_ID}}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            font-family: Georgia, serif;
            background: #f5f5f5;
            padding: 12px;
        }

        .certificate-stage {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .certificate-container {
            width: min(297mm, calc(100vw - 24px));
            min-height: min(210mm, calc((100vw - 24px) / 1.4142));
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0 auto;
            padding: clamp(28px, 5vw, 60px) clamp(20px, 4vw, 40px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            border: 3px solid #d4a574;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            aspect-ratio: 297 / 210;
            overflow: hidden;
        }

        .certificate-container::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px solid #d4a574;
            opacity: 0.3;
            border-radius: 5px;
        }

        .certificate-content {
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .certificate-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .certificate-logo img {
            max-width: 100px;
            height: auto;
        }

        .certificate-title {
            font-size: clamp(34px, 5vw, 48px);
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .certificate-subtitle {
            font-size: clamp(18px, 3vw, 20px);
            color: #34495e;
            margin-bottom: 40px;
            font-style: italic;
        }

        .certificate-text {
            font-size: clamp(15px, 2.4vw, 16px);
            color: #2c3e50;
            margin: 30px 0;
            line-height: 1.8;
        }

        .course-name {
            font-size: clamp(17px, 2.6vw, 20px);
            font-weight: bold;
            margin: 15px 0;
            color: #2980b9;
        }

        .course-name-arabic {
            font-size: clamp(20px, 3vw, 24px);
            direction: rtl;
            unicode-bidi: plaintext;
            font-weight: bold;
            color: #2980b9;
            margin: 10px 0 30px 0;
            font-family: 'DejaVu Sans', 'Arial Unicode MS', Arial, sans-serif;
            text-align: center;
        }

        .certificate-footer {
            margin-top: 50px;
            font-size: 14px;
            color: #7f8c8d;
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: flex-end;
        }

        .footer-date,
        .footer-id {
            text-align: center;
            flex: 1;
        }

        .footer-label {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #34495e;
        }

        .footer-value {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            border-top: 1px solid #95a5a6;
            padding-top: 8px;
            overflow-wrap: anywhere;
        }

        .certificate-actions {
            text-align: center;
            margin: 16px 0 12px;
        }

        .print-button {
            appearance: none;
            border: none;
            background: #2c7be5;
            color: white;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .certificate-actions {
                display: none;
            }
            .certificate-container {
                margin: 0;
                box-shadow: none;
                page-break-inside: avoid;
                width: 297mm;
                min-height: 210mm;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-actions">
        <button class="print-button" type="button" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>
    <div class="certificate-stage">
        <div class="certificate-container">
            <div class="certificate-content">
                <div class="certificate-logo">
                    {{LOGO_IMG}}
                </div>
                
                <div class="certificate-title">CERTIFICAT</div>
                <div class="certificate-subtitle">DE RÉUSSITE</div>
                
                <div class="certificate-text">
                    <strong>Félicitations {{USER_NAME}} !</strong>
                </div>
                
                <div class="certificate-text">
                    Vous avez complété avec succès le cours :
                </div>
                
                <div class="course-name">{{COURSE_NAME_LATIN}}</div>
                {{COURSE_NAME_ARABIC_HTML}}
                
                <div class="certificate-footer">
                    <div class="footer-date">
                        <div class="footer-label">Date de réussite</div>
                        <div class="footer-value">{{DATE}}</div>
                    </div>
                    <div class="footer-id">
                        <div class="footer-label">ID Certificat</div>
                        <div class="footer-value">{{CERTIFICATE_ID}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
        
        // Remplacer les placeholders
        $logoImg = $logoData 
            ? '<img src="data:' . $logoMimeType . ';base64,' . $logoData . '" alt="Logo">'
            : '<!-- Logo non trouvé -->';
        
        // Afficher le nom arabe seulement s'il n'est pas vide
        $arabicNameHtml = '';
        if (!empty($certData['name_arabic']) && trim($certData['name_arabic']) !== '') {
            $arabicNameHtml = '<div class="course-name-arabic">' . htmlspecialchars($certData['name_arabic']) . '</div>';
        }
        
        $html = str_replace('{{LOGO_IMG}}', $logoImg, $html);
        $html = str_replace('{{CERTIFICATE_ID}}', htmlspecialchars($certData['certificate_id']), $html);
        $html = str_replace('{{USER_NAME}}', htmlspecialchars($certData['user_name']), $html);
        $html = str_replace('{{COURSE_NAME_LATIN}}', htmlspecialchars($certData['name_latin']), $html);
        $html = str_replace('{{COURSE_NAME_ARABIC_HTML}}', $arabicNameHtml, $html);
        $html = str_replace('{{DATE}}', $date_formatted, $html);
        
        echo $html;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
