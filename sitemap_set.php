<?php

declare(strict_types=1);

define('APP_NAME', 'Plateforme de Formation Bamba');
define('DEFAULT_BASE_URL', 'http://localhost/');

$booksDir = __DIR__ . '/assets/books';
$dataDir = __DIR__ . '/assets/data';
$booksJsonFile = $dataDir . '/books.json';
$booksJsonLdFile = $dataDir . '/books.jsonld';
$sitemapFile = __DIR__ . '/sitemap.xml';
$robotsFile = __DIR__ . '/robots.txt';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

function getBaseUrl(): string
{
    $envBaseUrl = getenv('APP_BASE_URL');
    if (is_string($envBaseUrl) && trim($envBaseUrl) !== '') {
        return rtrim(trim($envBaseUrl), '/');
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . ($basePath !== '' ? $basePath : '');
    }

    return rtrim(DEFAULT_BASE_URL, '/');
}

function xmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function safeReadJson(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
}

function getBookLanguageLabel(string $lang, string $trans): string
{
    $labels = [
        'ar' => 'arabe',
        'fr' => 'francais',
        'en' => 'anglais',
        'wo' => 'wolof',
    ];

    $primary = $labels[$lang] ?? $lang;
    $secondary = $trans !== '' && isset($labels[$trans]) ? $labels[$trans] : $trans;

    return trim($primary . ($secondary !== '' ? ' traduit en ' . $secondary : ''));
}

function getBookTypeLabel(string $type): string
{
    return match ($type) {
        'qr' => 'Quran',
        'xs' => 'Xassida',
        'xm' => 'Enseignement religieux',
        default => 'Livre de formation',
    };
}

$baseUrl = getBaseUrl();
$homeUrl = $baseUrl . '/index.php';
$logoUrl = $baseUrl . '/assets/images/logos/logo2.png';
$books = [];

$bookDirectories = array_filter(glob($booksDir . '/*'), 'is_dir');
natsort($bookDirectories);

foreach ($bookDirectories as $bookPath) {
    $folder = basename($bookPath);
    $config = safeReadJson($bookPath . '/config/config.json');
    $images = glob($bookPath . '/images/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
    $audios = glob($bookPath . '/audios/*.{mp3,wav,ogg,m4a}', GLOB_BRACE) ?: [];

    if (empty($images) || empty($config)) {
        continue;
    }

    $nomLatin = trim((string) ($config['nomLatin'] ?? $folder));
    $nomArabe = trim((string) ($config['nomArabe'] ?? ''));
    $lang = trim((string) ($config['lang'] ?? ''));
    $trans = trim((string) ($config['trans'] ?? ''));
    $type = trim((string) ($config['type'] ?? ''));
    $auteur = trim((string) ($config['auteur'] ?? 'Cheikh Ahmadou Bamba'));
    $traducteur = trim((string) ($config['traducteur'] ?? ''));
    $voix = trim((string) ($config['voix'] ?? ''));
    $url = $homeUrl . '?book=' . rawurlencode($folder);

    $books[] = [
        'folder' => $folder,
        'nomLatin' => $nomLatin,
        'nomArabe' => $nomArabe,
        'lang' => $lang,
        'trans' => $trans,
        'type' => $type,
        'typeLabel' => getBookTypeLabel($type),
        'auteur' => $auteur,
        'traducteur' => $traducteur,
        'voix' => $voix,
        'pages' => count($images),
        'audioTracks' => count($audios),
        'url' => $url,
        'description' => sprintf(
            '%s, ouvrage de %s en %s avec traduction audio synchronisee par %s sur %s.',
            $nomLatin,
            $auteur,
            getBookLanguageLabel($lang, $trans),
            $voix !== '' ? $voix : ($traducteur !== '' ? $traducteur : 'la plateforme'),
            APP_NAME
        ),
    ];
}

file_put_contents(
    $booksJsonFile,
    json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

$sitemapEntries = [];
$sitemapEntries[] = '<?xml version="1.0" encoding="UTF-8"?>';
$sitemapEntries[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
$sitemapEntries[] = '  <url>';
$sitemapEntries[] = '    <loc>' . xmlEscape($homeUrl) . '</loc>';
$sitemapEntries[] = '    <changefreq>weekly</changefreq>';
$sitemapEntries[] = '    <priority>1.0</priority>';
$sitemapEntries[] = '  </url>';

foreach ($books as $book) {
    $sitemapEntries[] = '  <url>';
    $sitemapEntries[] = '    <loc>' . xmlEscape($book['url']) . '</loc>';
    $sitemapEntries[] = '    <changefreq>monthly</changefreq>';
    $sitemapEntries[] = '    <priority>0.8</priority>';
    $sitemapEntries[] = '  </url>';
}

$sitemapEntries[] = '</urlset>';
file_put_contents($sitemapFile, implode("\n", $sitemapEntries) . "\n");

$itemListElements = [];
$bookSchemaCollection = [];

foreach ($books as $index => $book) {
    $bookSchema = [
        '@type' => 'Book',
        'name' => $book['nomLatin'],
        'alternateName' => $book['nomArabe'],
        'url' => $book['url'],
        'inLanguage' => $book['lang'] !== '' ? $book['lang'] : 'fr',
        'author' => [
            '@type' => 'Person',
            'name' => $book['auteur'],
        ],
        'translator' => $book['traducteur'] !== '' ? [
            '@type' => 'Person',
            'name' => $book['traducteur'],
        ] : null,
        'abridged' => false,
        'bookFormat' => 'https://schema.org/EBook',
        'numberOfPages' => $book['pages'],
        'description' => $book['description'],
        'image' => $logoUrl,
        'publisher' => [
            '@type' => 'Organization',
            'name' => APP_NAME,
        ],
    ];

    $bookSchema = array_filter($bookSchema, static fn($value) => $value !== null && $value !== '');
    $bookSchemaCollection[] = $bookSchema;
    $itemListElements[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => $book['url'],
        'name' => $book['nomLatin'],
    ];
}

$jsonLdGraph = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebSite',
            '@id' => $homeUrl . '#website',
            'url' => $homeUrl,
            'name' => APP_NAME,
            'description' => 'Application PWA de formation aux enseignements religieux de Cheikh Ahmadou Bamba, avec livres en arabe, traduction wolof et audio synchronise.',
            'publisher' => [
                '@id' => $homeUrl . '#organization',
            ],
            'inLanguage' => ['fr', 'wo', 'ar'],
        ],
        [
            '@type' => 'Organization',
            '@id' => $homeUrl . '#organization',
            'name' => APP_NAME,
            'url' => $homeUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ],
        ],
        [
            '@type' => 'WebApplication',
            '@id' => $homeUrl . '#app',
            'name' => APP_NAME,
            'url' => $homeUrl,
            'applicationCategory' => 'EducationalApplication',
            'operatingSystem' => 'Android, iOS, Web',
            'browserRequirements' => 'Navigateur moderne compatible PWA',
            'description' => 'Plateforme de lecture, d ecoute synchronisee et de formation religieuse autour des ouvrages de Cheikh Ahmadou Bamba.',
            'inLanguage' => ['fr', 'wo', 'ar'],
            'isAccessibleForFree' => true,
            'publisher' => [
                '@id' => $homeUrl . '#organization',
            ],
        ],
        [
            '@type' => 'CollectionPage',
            '@id' => $homeUrl . '#collection',
            'url' => $homeUrl,
            'name' => 'Livres de formation religieuse en arabe et wolof',
            'description' => 'Collection de livres religieux, xassidas et ouvrages d apprentissage avec audio synchronise.',
            'isPartOf' => [
                '@id' => $homeUrl . '#website',
            ],
            'mainEntity' => [
                '@id' => $homeUrl . '#itemlist',
            ],
        ],
        [
            '@type' => 'ItemList',
            '@id' => $homeUrl . '#itemlist',
            'name' => 'Bibliotheque de Formation Bamba',
            'numberOfItems' => count($itemListElements),
            'itemListElement' => $itemListElements,
        ],
        ...$bookSchemaCollection,
    ],
];

file_put_contents(
    $booksJsonLdFile,
    json_encode($jsonLdGraph, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

$robotsLines = [
    'User-agent: *',
    'Allow: /',
    'Disallow: /admin.php',
    'Disallow: /rqt_',
    'Disallow: /assets/database/',
    'Disallow: /data/',
    'Sitemap: ' . $baseUrl . '/sitemap.xml',
];

file_put_contents($robotsFile, implode("\n", $robotsLines) . "\n");

echo 'SEO genere avec succes.' . PHP_EOL;
echo 'Livres indexes : ' . count($books) . PHP_EOL;
echo 'Base URL : ' . $baseUrl . PHP_EOL;
