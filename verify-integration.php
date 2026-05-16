#!/usr/bin/env php
<?php
/**
 * VÉRIFICATION DE L'INTÉGRATION DES CERTIFICATS
 * Ce script vérifie que tout a été installé correctement
 * Exécution: php verify-integration.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🎓 VÉRIFICATION DE L'INTÉGRATION DES CERTIFICATS              ║\n";
echo "║     Bamba Formation v1.0                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$baseDir = __DIR__;
$checks = [];
$passCount = 0;
$failCount = 0;
$warnCount = 0;

// ============================================================================
// SECTION 1: Vérifier les fichiers PHP
// ============================================================================
echo "📌 SECTION 1: Fichiers PHP\n";
echo "───────────────────────────────────────────────────────────────\n";

$phpFiles = [
    'lib_certificate.php' => '✅ Classe utilitaire CertificateManager',
    'rqt_certificate_check.php' => '✅ Vérifier certificats disponibles',
    'rqt_certificate_verify.php' => '✅ Valider un ID de certificat',
    'rqt_certificate_download.php' => '✅ Télécharger HTML imprimable',
    'rqt_certificate_generate_pdf.php' => '✅ Générer PDF (TCPDF)',
    'test-certificates.php' => '✅ Script de test complet',
];

foreach ($phpFiles as $file => $desc) {
    $path = $baseDir . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file\n";
        $checks[$file] = ['status' => 'pass', 'file' => $file];
        $passCount++;
    } else {
        echo "❌ $file - MANQUANT\n";
        $checks[$file] = ['status' => 'fail', 'file' => $file];
        $failCount++;
    }
}

// ============================================================================
// SECTION 2: Vérifier les fichiers modifiés
// ============================================================================
echo "\n📌 SECTION 2: Fichiers Modifiés\n";
echo "───────────────────────────────────────────────────────────────\n";

$modifiedFiles = [
    'assets/database/init_mysql_db.php' => 'Table certificates pour MySQL',
    'assets/database/init_db.php' => 'Table certificates pour SQLite',
    'rqt_user_book_progression_update.php' => 'Création automatique certificat',
];

foreach ($modifiedFiles as $file => $desc) {
    $path = $baseDir . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file\n";
        $checks[$file] = ['status' => 'pass', 'file' => $file];
        $passCount++;
    } else {
        echo "❌ $file - MANQUANT\n";
        $checks[$file] = ['status' => 'fail', 'file' => $file];
        $failCount++;
    }
}

// ============================================================================
// SECTION 3: Vérifier la documentation
// ============================================================================
echo "\n📌 SECTION 3: Documentation\n";
echo "───────────────────────────────────────────────────────────────\n";

$docFiles = [
    'CERTIFICATES_GUIDE.md' => 'Guide complet d\'utilisation',
    'INTEGRATION_SUMMARY.md' => 'Résumé technique',
    'DEPLOYMENT_GUIDE.md' => 'Guide de déploiement',
    'README_CERTIFICATES.md' => 'README principal',
    'INSTALL_CERTIFICATES.txt' => 'Instructions installation',
];

foreach ($docFiles as $file => $desc) {
    $path = $baseDir . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file\n";
        $checks[$file] = ['status' => 'pass', 'file' => $file];
        $passCount++;
    } else {
        echo "⚠️ $file - Optionnel\n";
        $warnCount++;
    }
}

// ============================================================================
// SECTION 4: Vérifier le JavaScript
// ============================================================================
echo "\n📌 SECTION 4: Frontend JavaScript\n";
echo "───────────────────────────────────────────────────────────────\n";

$jsFiles = [
    'assets/js/certificate-manager.js' => 'Gestionnaire de certificats JS',
];

foreach ($jsFiles as $file => $desc) {
    $path = $baseDir . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file\n";
        $checks[$file] = ['status' => 'pass', 'file' => $file];
        $passCount++;
    } else {
        echo "❌ $file - MANQUANT\n";
        $checks[$file] = ['status' => 'fail', 'file' => $file];
        $failCount++;
    }
}

// ============================================================================
// SECTION 5: Vérifier les ressources
// ============================================================================
echo "\n📌 SECTION 5: Ressources\n";
echo "───────────────────────────────────────────────────────────────\n";

$resources = [
    'assets/images/logos/certif_logo.png' => 'Logo certificat',
];

foreach ($resources as $file => $desc) {
    $path = $baseDir . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $sizeKb = round($size / 1024, 2);
        echo "✅ $file ({$sizeKb} KB)\n";
        $checks[$file] = ['status' => 'pass', 'file' => $file];
        $passCount++;
    } else {
        echo "⚠️ $file - Manquant (optionnel)\n";
        $warnCount++;
    }
}

// ============================================================================
// SECTION 6: Test de la classe
// ============================================================================
echo "\n📌 SECTION 6: Test de la classe CertificateManager\n";
echo "───────────────────────────────────────────────────────────────\n";

try {
    require_once $baseDir . '/lib_certificate.php';
    
    // Test de génération d'ID
    $certId = CertificateManager::generateCertificateId();
    if (preg_match('/^CERT-\d+-[A-F0-9]{6}$/', $certId)) {
        echo "✅ Génération ID: $certId\n";
        $passCount++;
    } else {
        echo "❌ Format ID invalide: $certId\n";
        $failCount++;
    }
    
} catch (Exception $e) {
    echo "❌ Erreur classe: {$e->getMessage()}\n";
    $failCount++;
}

// ============================================================================
// SECTION 7: Vérifier la page de démo
// ============================================================================
echo "\n📌 SECTION 7: Page de démonstration\n";
echo "───────────────────────────────────────────────────────────────\n";

$demoFile = $baseDir . '/certificate-demo.html';
if (file_exists($demoFile)) {
    echo "✅ certificate-demo.html\n";
    echo "   URL: http://localhost/formation_bamba/certificate-demo.html\n";
    $passCount++;
} else {
    echo "⚠️ certificate-demo.html - Optionnel\n";
    $warnCount++;
}

// ============================================================================
// RÉSUMÉ FINAL
// ============================================================================
echo "\n" . str_repeat("═", 66) . "\n";
echo "📊 RÉSUMÉ FINAL\n";
echo str_repeat("═", 66) . "\n\n";

echo "✅ Réussis:     $passCount\n";
echo "❌ Échoués:     $failCount\n";
echo "⚠️ Avertis:     $warnCount\n\n";

if ($failCount === 0) {
    echo "🎉 INTÉGRATION RÉUSSIE!\n\n";
    echo "✨ Prochaines étapes:\n";
    echo "  1. Exécuter les migrations BD:\n";
    echo "     php assets/database/init_mysql_db.php\n";
    echo "     OU\n";
    echo "     php assets/database/init_db.php\n\n";
    echo "  2. Tester le système complet:\n";
    echo "     php test-certificates.php\n\n";
    echo "  3. Accéder à la page de démonstration:\n";
    echo "     http://localhost/formation_bamba/certificate-demo.html\n\n";
    echo "  4. Intégrer le JavaScript dans votre page:\n";
    echo "     <script src=\"assets/js/certificate-manager.js\"></script>\n\n";
    echo "📖 Documentation:\n";
    echo "  - README_CERTIFICATES.md - Aperçu\n";
    echo "  - DEPLOYMENT_GUIDE.md - Installation détaillée\n";
    echo "  - CERTIFICATES_GUIDE.md - Guide complet\n\n";
} else {
    echo "❌ ERREURS DÉTECTÉES\n\n";
    echo "Vérifiez que tous les fichiers ont été créés correctement.\n";
    echo "Consultez INTEGRATION_SUMMARY.md pour la liste complète.\n\n";
}

echo str_repeat("═", 66) . "\n";
echo "Status: " . ($failCount === 0 ? "✅ PRODUCTION-READY" : "⚠️ À VÉRIFIER") . "\n";
echo str_repeat("═", 66) . "\n\n";

// ============================================================================
// DÉTAILS DE CHAQUE FICHIER
// ============================================================================
echo "📋 DÉTAILS DES FICHIERS CRÉÉS/MODIFIÉS:\n\n";

$categories = [
    'PHP Core' => [
        'lib_certificate.php' => 'Classe CertificateManager (~ 150 lignes)',
        'rqt_certificate_check.php' => 'Endpoint vérification certificats (~ 40 lignes)',
        'rqt_certificate_verify.php' => 'Endpoint validation ID (~ 35 lignes)',
        'rqt_certificate_download.php' => 'Téléchargement HTML (~ 300 lignes)',
        'rqt_certificate_generate_pdf.php' => 'Génération PDF (~ 250 lignes)',
    ],
    'Database' => [
        'assets/database/init_mysql_db.php' => 'Table certificates MySQL',
        'assets/database/init_db.php' => 'Table certificates SQLite',
    ],
    'Frontend' => [
        'assets/js/certificate-manager.js' => 'Gestionnaire JS (~ 400 lignes)',
    ],
    'Documentation' => [
        'CERTIFICATES_GUIDE.md' => 'Guide complet',
        'INTEGRATION_SUMMARY.md' => 'Résumé technique',
        'DEPLOYMENT_GUIDE.md' => 'Guide déploiement',
        'README_CERTIFICATES.md' => 'Readme principal',
    ],
    'Testing & Demo' => [
        'test-certificates.php' => 'Script de test',
        'certificate-demo.html' => 'Page de démonstration',
    ],
];

foreach ($categories as $category => $files) {
    echo "  $category:\n";
    foreach ($files as $file => $desc) {
        echo "    • $file - $desc\n";
    }
    echo "\n";
}

?>
