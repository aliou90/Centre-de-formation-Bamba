<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['title'], $data['page'], $data['progression'])) {
    echo json_encode(['status' => 'error', 'message' => 'Paramètres manquants']);
    exit;
}

try {
    require_once __DIR__ . '/rqt_db_connect.php';
    require_once __DIR__ . '/lib_certificate.php';
    
    $user_id = $_SESSION['user']['id'];
    $title = $data['title'];
    $page = (int)$data['page'];
    $progression = (int)$data['progression'];

    // Récupérer la progression actuelle
    $stmt = $db->prepare("SELECT progression FROM books WHERE user_id = :user_id AND title = :title");
    $stmt->execute(['user_id' => $user_id, 'title' => $title]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldProg = (int)($current['progression'] ?? 0);

    if ($progression > $oldProg) {
        // Mettre à jour uniquement si nouvelle progression > ancienne
        $stmt = $db->prepare("UPDATE books SET last_page = :page, progression = :progression WHERE user_id = :user_id AND title = :title");
        $stmt->execute([
            'page' => $page,
            'progression' => $progression,
            'user_id' => $user_id,
            'title' => $title
        ]);
    }

    // Liste des paliers de progression
    $levels = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
    $congrats = [
        10 => "🌟 Bravo pour les 10% ! Continue ainsi !",
        20 => "✨ 20% atteints ! Tu brilles déjà !",
        30 => "🌠 30% franchis ! Impressionnant !",
        40 => "🌟 40% de maîtrise, presque à mi-chemin !",
        50 => "🌟🎉 Moitié atteinte ! Continue sur ta lancée !",
        60 => "🌠 Plus que 40% ! Tu y es presque !",
        70 => "✨ 70% de réussite ! Quelle régularité !",
        80 => "🌟 80% ? Champion(ne) ! 💪",
        90 => "🎉 90% ! Tu touches au but !",
        100 => "🏆✨ 100% accompli ! Félicitations ! Tu es une étoile brillante ! 🌟"
    ];

    // Détecter si un palier a été franchi
    $maxLevel = 0;

    foreach ($levels as $level) {
        if ($oldProg < $level && $progression >= $level) {
            $maxLevel = $level;
        }
    }

    // ===== CRÉATION DU CERTIFICAT À 100% =====
    $certificateMessage = null;
    if ($oldProg < 100 && $progression >= 100 && !CertificateManager::hasCertificate($db, $user_id, $title)) {
        $certResult = CertificateManager::createCertificate($db, $user_id, $title, $progression);
        if ($certResult['status'] === 'created') {
            $certificateMessage = [
                'status' => 'created',
                'certificate_id' => $certResult['certificate_id'],
                'message' => '🎓 Certificat créé ! Vous pouvez maintenant le télécharger.'
            ];
        }
    }

    if ($maxLevel > 0) {
        $response = [
            'status' => 'ok',
            'message' => 'Progression mise à jour (' . $progression . '%) !',
            'congrat' => $congrats[$maxLevel]
        ];
        
        if ($certificateMessage) {
            $response['certificate'] = $certificateMessage;
        }
        
        echo json_encode($response);
        exit;
    }

    // Si aucun palier franchi
    $response = ['status' => 'ok', 'message' => 'Progression mise à jour (' . $progression . '%) !'];
    
    if ($certificateMessage) {
        $response['certificate'] = $certificateMessage;
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
