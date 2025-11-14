<?php
// admin_upload_files.php

$book = $_POST['book'] ?? '';
$bookDir = __DIR__ . "/assets/books/" . $book;

if (!$book || !is_dir($bookDir)) {
    die("❌ Livre invalide.");
}

// Fonction de sauvegarde des fichiers
function saveUploadedFiles($files, $targetDir, $acceptedTypes = []) {
    $messages = [];

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    foreach ($files['name'] as $index => $filename) {
        if ($files['error'][$index] !== UPLOAD_ERR_OK) {
            $messages[] = "❌ Erreur sur le fichier '$filename'.";
            continue;
        }

        $tmpName = $files['tmp_name'][$index];
        $type = mime_content_type($tmpName);

        if ($acceptedTypes && !in_array($type, $acceptedTypes)) {
            $messages[] = "❌ Type non autorisé pour '$filename' ($type).";
            continue;
        }

        $targetPath = $targetDir . '/' . basename($filename);

        if (file_exists($targetPath)) {
            $messages[] = "⚠️ '$filename' existe déjà.";
            continue;
        }

        if (move_uploaded_file($tmpName, $targetPath)) {
            $messages[] = "✅ '$filename' importé.";
        } else {
            $messages[] = "❌ Impossible de déplacer '$filename'.";
        }
    }

    return $messages;
}

$feedback = [];

// Traitement des images
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $feedback[] = "<strong>Images :</strong>";
    $feedback = array_merge($feedback, saveUploadedFiles($_FILES['images'], $bookDir . '/images', ['image/jpeg', 'image/jpg', 'image/gif', 'image/png', 'image/webp']));
}

// Traitement des audios
if (!empty($_FILES['audios']) && is_array($_FILES['audios']['name'])) {
    $feedback[] = "<strong>Audios :</strong>";
    $feedback = array_merge($feedback, saveUploadedFiles($_FILES['audios'], $bookDir . '/audios', ['audio/mp3', 'audio/m4a', 'audio/mpeg', 'audio/wav', 'audio/ogg']));
}

// Affichage des résultats
echo "<div style='background:#fff;padding:20px;margin:20px;border:1px solid #ccc;border-radius:8px;'>";
echo "<h3>Résultat de l'importation :</h3>";
echo "<ul>";
foreach ($feedback as $line) {
    echo "<li>$line</li>";
}
echo "</ul>";
echo "<a href='admin.php?selected_book=" . urlencode($book) . "'>⬅️ Retour au livre</a>";
echo "</div>";
