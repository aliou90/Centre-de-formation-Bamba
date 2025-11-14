<?php
// Paramètres de connexion MySQL / MariaDB
$host = 'localhost';                // ou 127.0.0.1
$dbname = 'bamba_formation_db';        // nom de ta base
$username = 'root';                 // à adapter selon ton serveur
$password = '';                     // idem

try {
    // Connexion PDO
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Créer la base si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    $pdo->exec("USE `$dbname`;");

    // --- TABLE users ---
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(50) UNIQUE,
            password VARCHAR(255) NOT NULL,
            state TINYINT DEFAULT 1,
            activation_code VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");

    // --- TABLE books ---
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            progression INT DEFAULT 0,
            last_page INT DEFAULT 1,
            start_date DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");

    echo '✅ Base de données MySQL initialisée avec succès.' . PHP_EOL;

} catch (PDOException $e) {
    echo '❌ Erreur : ' . $e->getMessage() . PHP_EOL;
    exit;
}
?>
