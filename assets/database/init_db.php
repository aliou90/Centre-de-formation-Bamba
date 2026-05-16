<?php
$dbPath = __DIR__ . '/bamba_formation.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Créer la table users avec les champs manquants
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fullname TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT UNIQUE,
        country TEXT,
        profile_image TEXT,
        password TEXT NOT NULL,
        state INTEGER DEFAULT 1,
        activation_code TEXT
    );
");

// Créer la table books
$db->exec("
    CREATE TABLE IF NOT EXISTS books (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        progression INTEGER DEFAULT 0,
        last_page INT DEFAULT 1,
        start_date TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
");

// Créer la table certificates
$db->exec("
    CREATE TABLE IF NOT EXISTS certificates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        book_title TEXT NOT NULL,
        certificate_id TEXT NOT NULL UNIQUE,
        completion_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        progression INTEGER DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
");

// Créer un index unique pour éviter les doublons
$db->exec("
    CREATE UNIQUE INDEX IF NOT EXISTS unique_cert ON certificates(user_id, book_title);
");

echo "Base de données initialisée avec succès." . PHP_EOL;
