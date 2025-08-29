<?php
require_once __DIR__ . '/inc/auth.php';

session_start();

// Vérifie la connexion via la fonction existante
if (!isLoggedIn()) {
    http_response_code(403);
    echo "Non autorisé : session invalide";
    exit;
}

// Reste du code inchangé
$dir = __DIR__ . "/dessins";
if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true)) {
        http_response_code(500);
        echo "Erreur : impossible de créer le dossier dessins";
        exit;
    }
}

$type = $_POST['type'] ?? 'png';
$data = $_POST['data'] ?? '';

if (!$data) {
    http_response_code(400);
    echo "Erreur : aucune donnée reçue";
    exit;
}

$filenameBase = $_POST['filename'] ?? 'dessin';
$filenameBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filenameBase);
$filename = $filenameBase . "-" . date("Y-m-d_H-i-s") . "." . $type;
$path = $dir . "/" . $filename;

try {
    if ($type === 'png') {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $data);
        $data = base64_decode($data);
        if ($data === false) {
            throw new Exception("Erreur de décodage Base64");
        }
    } elseif ($type === 'svg') {
        // SVG brut, rien à faire
    } else {
        http_response_code(400);
        echo "Type de fichier non supporté";
        exit;
    }

    if (file_put_contents($path, $data) === false) {
        throw new Exception("Impossible d'écrire le fichier");
    }

    echo "OK : fichier sauvegardé sous $filename";

} catch (Exception $e) {
    http_response_code(500);
    echo "Erreur serveur : " . $e->getMessage();
}
