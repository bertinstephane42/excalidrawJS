<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$dir = __DIR__ . '/dessins';

if (isset($_GET['file'])) {
    $file = basename($_GET['file']); 
    $path = $dir . '/' . $file;

    // Vérif extension
    $allowed_ext = ['json','png','svg'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        die("Type de fichier non autorisé.");
    }

    // Vérif chemin réel
    $realBase = realpath($dir);
    $realPath = realpath($path);
    if ($realPath === false || strpos($realPath, $realBase) !== 0) {
        die("Accès refusé.");
    }

    // Suppression
    if (is_file($path)) {
        unlink($path);
        header("Location: dashboard.php?deleted=" . urlencode($file));
        exit;
    }
}

header('Location: dashboard.php');
exit;
