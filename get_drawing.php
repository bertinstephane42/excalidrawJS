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

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(403);
    exit('Appel interdit');
}

// Forcer compression si dispo
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Forcer le type de contenu JSON
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Récupérer le dernier mtime envoyé par le client
$clientMtime = isset($_GET['mtime']) ? (int)$_GET['mtime'] : 0;
$serverMtime = filemtime($jsonPath);

// Si le fichier n’a pas changé → renvoyer juste l’état
if ($clientMtime >= $serverMtime) {
    echo json_encode([
        'unchanged' => true,
        'mtime' => $serverMtime
    ]);
    exit;
}

// Sinon → lire le JSON complet
$content = file_get_contents($jsonPath);
$data = json_decode($content, true);
if ($data === null) {
    http_response_code(500);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

// Renvoi du JSON complet + timestamp
echo json_encode([
    'unchanged' => false,
    'mtime' => $serverMtime,
    'data' => $data
]);
exit;