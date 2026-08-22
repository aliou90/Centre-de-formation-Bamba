<?php
/**
 * books_get.php
 * Fix : GLOB_BRACE remplacé par scandir() + preg_match()
 * GLOB_BRACE n'est pas garanti sur PHP Android NDK — retourne false
 * → books ignorés car !empty($images) == false
 */

// ── Helpers ──────────────────────────────────────────────────────────────────

function isBookImage(string $f): bool {
    return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|avif|tiff?)$/i', $f);
}

function isBookAudio(string $f): bool {
    return (bool) preg_match('/\.(mp3|wav|ogg|m4a|aac|flac)$/i', $f);
}

/**
 * Liste et trie naturellement les fichiers d'un dossier selon un filtre callback.
 * Utilise scandir() — pas de GLOB_BRACE — compatible Android NDK.
 * Retourne les chemins complets (baseDir + nom fichier).
 */
function listFiles(string $baseDir, callable $filter): array {
    if (!is_dir($baseDir)) return [];
    $files = array_diff(scandir($baseDir), ['.', '..']);
    $files = array_values(array_filter($files, $filter));
    natsort($files);
    return array_values(array_map(fn($f) => $baseDir . $f, $files));
}

// ── Lecture des livres ────────────────────────────────────────────────────────

$books   = [];
$bookDir = __DIR__ . '/assets/books/';   // chemin absolu — indépendant du CWD Apache

if (is_dir($bookDir)) {
    foreach (scandir($bookDir) as $book) {
        if ($book === '.' || $book === '..') continue;
        $bookPath = $bookDir . $book . '/';
        if (!is_dir($bookPath)) continue;

        $images = listFiles($bookPath . 'images/', 'isBookImage');
        $audios = listFiles($bookPath . 'audios/', 'isBookAudio');

        // Un livre sans aucune image est ignoré (même comportement qu'avant)
        if (empty($images)) continue;

        // Chapitres
        $chapitres = [];
        $chapFile  = $bookPath . 'config/chapitres.json';
        if (file_exists($chapFile)) {
            $decoded = json_decode(file_get_contents($chapFile), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $chapitres = $decoded;
            }
        }

        // Config
        $config   = ['lang' => 'ar'];
        $confFile = $bookPath . 'config/config.json';
        if (file_exists($confFile)) {
            $decoded = json_decode(file_get_contents($confFile), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $config = $decoded;
            }
        }

        // Convertir les chemins absolus en chemins relatifs pour le navigateur
        $relBase = 'assets/books/' . $book . '/';
        $books[$book] = [
            'images'   => array_values(array_map(
                fn($p) => $relBase . 'images/' . rawurlencode(basename($p)),
                $images
            )),
            'audios'   => array_values(array_map(
                fn($p) => $relBase . 'audios/' . rawurlencode(basename($p)),
                $audios
            )),
            'chapitres' => $chapitres,
            'config'    => $config,
        ];
    }
}

// ── Groupement par langue ─────────────────────────────────────────────────────

$langGroups = [
    'ar'     => ['label' => '📚 Livres en Arabe',    'books' => []],
    'fr'     => ['label' => '📖 Livres en Français', 'books' => []],
    'en'     => ['label' => '📘 Livres en Anglais',  'books' => []],
    'wo'     => ['label' => '📝 Livres en Wolof',    'books' => []],
    'autres' => ['label' => '📁 Autres Livres',      'books' => []],
];

foreach ($books as $title => $data) {
    $lang = $data['config']['lang'] ?? 'autres';
    if (!isset($langGroups[$lang])) $lang = 'autres';
    $langGroups[$lang]['books'][$title] = $data;
}