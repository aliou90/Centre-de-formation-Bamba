<?php
$db = new PDO('sqlite:library.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ajouter des utilisateurs de test
$users = [
    ['fullname' => 'Alioune Diop', 'email' => 'alioune@example.com', 'phone' => '221765456789', 'password' => 'test123'],
    ['fullname' => 'Fatou Ndiaye', 'email' => 'fatou@example.com', 'phone' => '221765456706', 'password' => 'bonjour'],
];

// Préparer l'insertion
$stmtUser = $db->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");

foreach ($users as $user) {
    $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmtUser->execute([$user['fullname'], $user['email'], $user['phone'], $hashedPassword]);
}

// Récupérer les IDs des utilisateurs
$usersFromDB = $db->query("SELECT id, fullname FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Ajouter des livres pour chaque utilisateur
$stmtBook = $db->prepare("INSERT INTO books (user_id, title, progression) VALUES (?, ?, ?)");

foreach ($usersFromDB as $user) {
    $stmtBook->execute([$user['id'], "Introduction au Xassida", rand(10, 100)]);
    $stmtBook->execute([$user['id'], "Les enseignements de Bamba", rand(10, 100)]);
}

echo "Données de test insérées avec succès." . PHP_EOL;
