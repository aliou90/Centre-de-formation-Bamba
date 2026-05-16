<?php
/**
 * Script de test pour simuler une mise à jour de progression à 100%
 */

session_start();
$_SESSION['user'] = ['id' => 2, 'fullname' => 'Test User', 'email' => 'test@bamba.local'];

// Simuler la requête POST
$_POST['bookTitle'] = 'Ad Dàliyatu (ar-wo)';
$_POST['currentPage'] = 98;  // Page 98 pour arriver à 100% (si ~100 pages)
$_POST['currentPage'] = 1; // Ou page 1 si le livre n'a qu'une page

require_once __DIR__ . '/rqt_db_connect.php';
require_once __DIR__ . '/lib_certificate.php';

// Inclure les dépendances de rqt_user_book_progression_update.php
$_REQUEST['bookTitle'] = 'Ad Dàliyatu (ar-wo)';
$_REQUEST['currentPage'] = 0;  // Page 0 = première page
$_REQUEST['page'] = 1;  // Numéro de page
$_REQUEST['progression'] = 100;  // Progression à 100%

// Inclure le fichier de mise à jour de la progression
require_once __DIR__ . '/rqt_user_book_progression_update.php';
?>
