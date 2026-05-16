<?php
function ensureUserProfileColumns(PDO $db): void {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('country', $columns, true)) {
            $db->exec("ALTER TABLE users ADD COLUMN country VARCHAR(120) NULL AFTER phone");
        }

        if (!in_array('profile_image', $columns, true)) {
            $db->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER country");
        }

        return;
    }

    if ($driver === 'sqlite') {
        $columns = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');

        if (!in_array('country', $columnNames, true)) {
            $db->exec("ALTER TABLE users ADD COLUMN country TEXT");
        }

        if (!in_array('profile_image', $columnNames, true)) {
            $db->exec("ALTER TABLE users ADD COLUMN profile_image TEXT");
        }
    }
}

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
    ensureUserProfileColumns($db);
    // echo "✅ Connecté à la base MySQL ($mysqlDb)";
}
catch (PDOException $e) {
    // En cas d’échec, on bascule sur SQLite
    $dbPath = __DIR__ . '/assets/database/bamba_formation.db';
    try {
        $db = new PDO("sqlite:" . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        ensureUserProfileColumns($db);
        // echo "⚠️ MySQL indisponible, basculement sur SQLite";
    } catch (PDOException $ex) {
        die("❌ Échec de connexion MySQL et SQLite : " . $ex->getMessage());
    }
}
?>
