<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) {
    http_response_code(403);
    exit('Accès interdit');
}

// fichier JSON en lecture seule
$jsonPath = __DIR__ . '/json/current.json';
if (!file_exists($jsonPath)) {
    http_response_code(404);
    exit('Fichier non trouvé');
}

// Forcer le type de contenu JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
readfile($jsonPath);
exit;