<?php
session_start();
require_once __DIR__ . '/inc/auth.php';

if (!isAdmin()) {
    http_response_code(403);
    echo "Accès refusé";
    exit;
}

$logFile = __DIR__ . '/logs/users.log';
if (!file_exists($logFile)) {
    echo "Fichier journal introuvable.";
    exit;
}

echo file_get_contents($logFile);