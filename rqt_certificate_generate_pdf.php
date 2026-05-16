<?php
/**
 * rqt_certificate_generate_pdf.php
 * Génère un certificat en PDF avec TCPDF (version avancée)
 * 
 * Utilisation:
 * GET /rqt_certificate_generate_pdf.php?certificate_id=CERT-xxx&format=A4
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Non autorisé';
    exit;
}

require_once __DIR__ . '/rqt_db_connect.php';
require_once __DIR__ . '/lib_certificate.php';

function certificateLogoDataUri($logoPath) {
    if (!file_exists($logoPath)) {
        return '';
    }

    return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

function escapeSvgText($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function buildCertificateSvg($certData, $dateFormatted, $logoDataUri) {
    $userName = escapeSvgText($certData['user_name']);
    $courseLatin = escapeSvgText($certData['name_latin']);
    $courseArabic = trim((string) ($certData['name_arabic'] ?? ''));
    $certificateId = escapeSvgText($certData['certificate_id']);
    $date = escapeSvgText($dateFormatted);
    $logoSvg = $logoDataUri !== ''
        ? '<image href="' . escapeSvgText($logoDataUri) . '" x="347" y="52" width="100" height="100" preserveAspectRatio="xMidYMid meet" />'
        : '';

    $arabicBlock = '';
    if ($courseArabic !== '') {
        $arabicBlock = '
            <text x="561.5" y="540" text-anchor="middle"
                  font-family="DejaVu Sans, Arial Unicode MS, sans-serif"
                  font-size="34" font-weight="700" fill="#2980b9"
                  direction="rtl" unicode-bidi="plaintext">' . escapeSvgText($courseArabic) . '</text>';
    }

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1123" height="794" viewBox="0 0 1123 794">
    <defs>
        <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#f5f7fa" />
            <stop offset="100%" stop-color="#c3cfe2" />
        </linearGradient>
    </defs>
    <rect width="1123" height="794" fill="url(#bg)" />
    <rect x="18" y="18" width="1087" height="758" rx="4" fill="none" stroke="#d4a574" stroke-width="6" />
    <rect x="36" y="36" width="1051" height="722" rx="4" fill="none" stroke="#d4a574" stroke-opacity="0.35" stroke-width="3" />
    {$logoSvg}
    <text x="561.5" y="165" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="54" font-weight="700" fill="#2c3e50">CERTIFICAT</text>
    <text x="561.5" y="220" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="24" font-style="italic" fill="#34495e">DE RÉUSSITE</text>
    <text x="561.5" y="335" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="30" font-weight="700" fill="#2c3e50">Félicitations {$userName} !</text>
    <text x="561.5" y="410" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="24" fill="#2c3e50">Vous avez complété avec succès le cours :</text>
    <text x="561.5" y="480" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="28" font-weight="700" fill="#2980b9">{$courseLatin}</text>
    {$arabicBlock}
    <line x1="220" y1="625" x2="903" y2="625" stroke="#95a5a6" stroke-width="1.5" />
    <text x="350" y="690" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="18" font-weight="700" fill="#34495e">Date de réussite</text>
    <text x="350" y="728" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="20" fill="#2c3e50">{$date}</text>
    <text x="773" y="690" text-anchor="middle" font-family="DejaVu Serif, Georgia, serif" font-size="18" font-weight="700" fill="#34495e">ID Certificat</text>
    <text x="773" y="728" text-anchor="middle" font-family="DejaVu Sans Mono, Courier, monospace" font-size="16" fill="#2c3e50">{$certificateId}</text>
</svg>
SVG;
}

function buildSingleImagePdfFromJpeg($jpegData) {
    $imageSize = @getimagesizefromstring($jpegData);
    if ($imageSize === false) {
        throw new RuntimeException('Impossible de lire l’image du certificat.');
    }

    [$imageWidth, $imageHeight] = $imageSize;
    $pageWidth = 595.28;
    $pageHeight = 841.89;
    $contentStream = "q\n{$pageWidth} 0 0 {$pageHeight} 0 0 cm\n/Im0 Do\nQ\n";

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /ProcSet [/PDF /ImageC] /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>";
    $objects[] = "<< /Type /XObject /Subtype /Image /Width {$imageWidth} /Height {$imageHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($jpegData) . " >>\nstream\n{$jpegData}\nendstream";
    $objects[] = "<< /Length " . strlen($contentStream) . " >>\nstream\n{$contentStream}endstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

    return $pdf;
}

$user_id = $_SESSION['user']['id'];
$certificate_id = $_GET['certificate_id'] ?? null;

if (!$certificate_id) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID de certificat manquant']);
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
    
    $completion_date = new DateTime($certData['completion_date']);
    $date_formatted = $completion_date->format('d/m/Y');
    $filename = 'certificat_' . str_replace('/', '_', $certData['certificate_id']) . '.pdf';

    $logoPath = __DIR__ . '/assets/images/logos/certif_logo.png';
    $svg = buildCertificateSvg($certData, $date_formatted, certificateLogoDataUri($logoPath));

    if (!class_exists('Imagick')) {
        header('Location: rqt_certificate_download.php?certificate_id=' . urlencode($certificate_id) . '&mode=html');
        exit;
    }

    $image = new Imagick();
    $image->setBackgroundColor('white');
    $image->setResolution(180, 180);
    $image->readImageBlob($svg);
    $image->setImageBackgroundColor('white');
    $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    $image->setImageColorspace(Imagick::COLORSPACE_SRGB);
    $image->setImageCompression(Imagick::COMPRESSION_JPEG);
    $image->setImageCompressionQuality(92);
    $image->setImageFormat('jpeg');
    $pdfBinary = buildSingleImagePdfFromJpeg($image->getImageBlob());

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBinary));
    echo $pdfBinary;
    $image->clear();
    $image->destroy();

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
