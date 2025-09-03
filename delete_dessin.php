<?php
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$dir = __DIR__ . '/dessins';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Token CSRF invalide");
    }

    // Vérification du fichier envoyé en POST
    if (!empty($_POST['file'])) {
        $file = basename($_POST['file']);

        // Vérification du nom
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
            die("Nom de fichier invalide.");
        }

        $path = $dir . '/' . $file;

        // Vérification extension
        $allowed_ext = ['json','png','svg'];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            die("Type de fichier non autorisé.");
        }

        // Vérification chemin réel
        $realBase = realpath($dir);
        $realPath = realpath($path);
        if ($realPath === false || strpos($realPath, $realBase) !== 0) {
            die("Accès refusé.");
        }

        // Suppression
        if (is_file($path)) {
            unlink($path);
            header("Location: fichiers.php");
            exit;
        } else {
            die("Fichier introuvable.");
        }
    }
}

// Redirection par défaut
header('Location: fichiers.php');
exit;