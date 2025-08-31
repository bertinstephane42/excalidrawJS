<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }
session_start();
$lockFile = __DIR__ . '/lock/dessin.lock';
if (is_file($lockFile)) {
    $owner = @file_get_contents($lockFile);
    if ($owner === session_id()) {
        @touch($lockFile); // met à jour la mtime pour le TTL
    }
}