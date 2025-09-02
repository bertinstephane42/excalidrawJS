<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) {
    http_response_code(403);
    exit('Accès interdit');
}

$jsonPath = __DIR__ . '/json/current.json';
if (!file_exists($jsonPath)) {
    http_response_code(404);
    exit('Fichier non trouvé');
}

// Forcer compression si dispo
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Forcer le type de contenu JSON compressible
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Lire et envoyer
readfile($jsonPath);
exit;