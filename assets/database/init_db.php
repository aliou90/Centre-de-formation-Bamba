<?php
$db = new PDO('sqlite:bamba_formation.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Créer la table users avec les champs manquants
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fullname TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT UNIQUE,
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

echo "Base de données initialisée avec succès." . PHP_EOL;
