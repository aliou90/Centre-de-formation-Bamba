<?php
// --- Configuration MySQL ---
$mysqlHost = 'localhost';
$mysqlDb   = 'bamba_formation_db';
$mysqlUser = 'root';
$mysqlPass = '';

try {
    // Tentative de connexion MySQL
    $db = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Vérification rapide de la connexion
    $db->query('SELECT 1');
    // echo "✅ Connecté à la base MySQL ($mysqlDb)";
}
catch (PDOException $e) {
    // En cas d’échec, on bascule sur SQLite
    $dbPath = __DIR__ . '/assets/database/bamba_formation.db';
    try {
        $db = new PDO("sqlite:" . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // echo "⚠️ MySQL indisponible, basculement sur SQLite";
    } catch (PDOException $ex) {
        die("❌ Échec de connexion MySQL et SQLite : " . $ex->getMessage());
    }
}
?>
