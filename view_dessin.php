<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

$dir = __DIR__ . '/dessins';
if (!isset($_GET['file'])) die('Fichier non spécifié');

$filename = basename($_GET['file']); // sécurité
$filepath = $dir . '/' . $filename;

if (!file_exists($filepath)) die('Fichier introuvable');

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$contentType = match($ext) {
    'png' => 'image/png',
    'svg' => 'image/svg+xml',
    'json' => 'application/json',
    default => 'application/octet-stream'
};

// Forcer le téléchargement si demandé
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Disposition: inline; filename="' . $filename . '"');
}

header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
