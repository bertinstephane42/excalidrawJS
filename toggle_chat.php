<?php
session_start();
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Accès interdit"]);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(["error" => "Token CSRF invalide"]);
    exit;
}

$lockFile = __DIR__ . "/chat/chat.lock";

if (file_exists($lockFile)) {
    unlink($lockFile);
    echo json_encode(["state" => false, "message" => "✅ Chat activé"]);
} else {
    file_put_contents($lockFile, "Chat désactivé le " . date("Y-m-d H:i:s"));
    echo json_encode(["state" => true, "message" => "❌ Chat désactivé"]);
}