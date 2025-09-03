<?php
session_start();
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès refusé');
}

// Vérification CSRF
$token = $_POST['csrf_token'] ?? '';
if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Token invalide');
}

// Suppression des fichiers
$chatDir = __DIR__ . '/chat';
$files = ['messages.json','users.json'];
$deleted = [];

foreach($files as $f){
    $path = "$chatDir/$f";
    if(file_exists($path) && unlink($path)){
        $deleted[] = $f;
    }
}

echo $deleted ? "Fichiers supprimés : " . implode(', ', $deleted) : "Aucun fichier trouvé à supprimer.";