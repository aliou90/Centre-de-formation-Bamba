<?php
// Récupération des livres dynamiquement
$books = [];
$bookDir = 'assets/books/';

if (is_dir($bookDir)) {
    foreach (scandir($bookDir) as $book) {
        if ($book !== '.' && $book !== '..' && is_dir($bookDir . $book)) {

            $images = glob($bookDir . $book . '/images/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            $audios = glob($bookDir . $book . '/audios/*.{mp3,wav,ogg,m4a,ogg}', GLOB_BRACE);

            // Initialiser les valeurs par défaut
            $chapitres = [];
            $config = ['lang' => 'ar']; // valeur par défaut

            // Charger les chapitres s'ils existent et sont valides
            $chapitresFile = $bookDir . $book . '/config/chapitres.json';
            if (file_exists($chapitresFile)) {
                $json = file_get_contents($chapitresFile);
                $decoded = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $chapitres = $decoded;
                }
            }

            // Charger le fichier de configuration s’il est valide
            $configFile = $bookDir . $book . '/config/config.json';
            if (file_exists($configFile)) {
                $json = file_get_contents($configFile);
                $decoded = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $config = $decoded;
                }
            }

            if (!empty($images)) {
                // Tri naturel
                natsort($images);

                // Vérifier si le tableau d'audios est vide
                if (!empty($audios)) {
                    natsort($audios); // Tri naturel   
                }                

                // Ajouter les données dans le tableau du livre
                $books[$book] = [
                    'images' => array_values($images),
                    'audios' => array_values($audios),
                    'chapitres' => $chapitres,
                    'config' => $config
                ];
            }
        }
    }
    
}

// Grouper les livres par langue
$langGroups = [
    'ar' => ['label' => '📚 Livres en Arabe', 'books' => []],
    'fr' => ['label' => '📖 Livres en Français', 'books' => []],
    'en' => ['label' => '📘 Livres en Anglais', 'books' => []],
    'wo' => ['label' => '📝 Livres en Wolof', 'books' => []],
    'autres' => ['label' => '📁 Autres Livres', 'books' => []],
];

foreach ($books as $title => $data) {
    $lang = $data['config']['lang'] ?? 'autres';
    if (!isset($langGroups[$lang])) {
        $lang = 'autres';
    }
    $langGroups[$lang]['books'][$title] = $data;
}