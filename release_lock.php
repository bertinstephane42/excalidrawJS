<?php
session_start();
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès interdit');
}

$lockFile = __DIR__ . '/lock/dessin.lock';
$maxInactivity = 60; // secondes avant qu'un lock puisse être considéré inactif
$result = "Aucun lock trouvé ou non supprimé";

if (is_file($lockFile)) {
    $owner = @file_get_contents($lockFile);
    $lockMtime = filemtime($lockFile);

    $now = time();

    // 1️⃣ Si le lock appartient à la session courante → suppression immédiate
    if ($owner === session_id()) {
        @unlink($lockFile);
        $result = "Lock supprimé côté serveur (session propriétaire)";
    } 
    // 2️⃣ Lock appartient à une autre session
    else {
        if (($now - $lockMtime) > $maxInactivity) {
            @unlink($lockFile);
            $result = "Lock supprimé (autre session inactive depuis plus de $maxInactivity secondes)";
        } else {
            $result = "Lock actif : un autre admin est en train de dessiner. Suppression refusée";
        }
    }
}

echo $result;