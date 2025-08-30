<?php
// save_json.php
session_start();

// Vérifie que l'utilisateur est admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Accès refusé : non administrateur";
    exit;
}

// Récupération des données depuis $_POST['data']
$data = $_POST['data'] ?? '';
if (!$data) {
    http_response_code(400);
    echo "JSON invalide ou vide";
    exit;
}

// Assure-toi que le dossier existe
$dir = __DIR__ . "/json";
if (!is_dir($dir)) mkdir($dir, 0777, true);

// Écriture du fichier
$file = $dir . "/current.json";
if (file_put_contents($file, $data) === false) {
    http_response_code(500);
    echo "Impossible d'écrire le fichier";
    exit;
}

echo "JSON sauvegardé dans $file";