<?php 
session_start();
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès interdit');
}

$lockFile = __DIR__ . '/lock/dessin.lock';
$result = "Aucun lock trouvé ou non supprimé";

if (is_file($lockFile)) {
    $owner = @file_get_contents($lockFile);
    if ($owner === session_id()) {
        @unlink($lockFile);
        $result = "Lock supprimé côté serveur";
    } else {
        $result = "Lock présent mais appartient à une autre session";
    }
}

echo $result;