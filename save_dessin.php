<?php
require_once __DIR__ . '/inc/auth.php';

session_start();

// Vérifie la connexion via la fonction existante
if (!isLoggedIn()) {
    http_response_code(403);
    echo "Non autorisé : session invalide";
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Accès refusé : non administrateur";
    exit;
}

// Dossier de sauvegarde
$dir = __DIR__ . "/dessins";
if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true)) {
        http_response_code(500);
        echo "Erreur : impossible de créer le dossier dessins";
        exit;
    }
}

// Données reçues
$type = strtolower($_POST['type'] ?? 'png');
$data = $_POST['data'] ?? '';

if (!$data) {
    http_response_code(400);
    echo "Erreur : aucune donnée reçue";
    exit;
}

// Nom de fichier sécurisé
$filenameBase = $_POST['filename'] ?? 'dessin';
$filenameBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filenameBase);
$ext = match($type) {
    'png' => 'png',
    'svg' => 'svg',
    'json' => 'json',
    default => null,
};
if (!$ext) {
    http_response_code(400);
    echo "Type de fichier non supporté";
    exit;
}
$filename = $filenameBase . "-" . date("Y-m-d_H-i-s") . "." . $ext;
$path = $dir . "/" . $filename;

try {
    switch ($type) {
        case 'png':
            // Décodage Base64
            $data = preg_replace('#^data:image/\w+;base64,#i', '', $data);
            $data = base64_decode($data);
            if ($data === false) throw new Exception("Erreur de décodage Base64");
            break;

        case 'svg':
            // SVG brut, rien à faire
            break;

        case 'json':
            // Vérifie si le JSON est valide
            json_decode($data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON invalide : " . json_last_error_msg());
            }
            break;
    }

    if (file_put_contents($path, $data) === false) {
        throw new Exception("Impossible d'écrire le fichier");
    }

    echo "OK : fichier sauvegardé sous $filename";

} catch (Exception $e) {
    http_response_code(500);
    echo "Erreur serveur : " . $e->getMessage();
}