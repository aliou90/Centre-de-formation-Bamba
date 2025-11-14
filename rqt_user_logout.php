<?php 
// VÉRIFICATION ET L'ACTIVATION DE SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Déconnexion
session_destroy();
echo json_encode(['success' => true]);