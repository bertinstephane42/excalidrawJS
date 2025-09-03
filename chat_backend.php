<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { 
    header('Location: login.php'); 
    exit; 
}

// --- Dossier de stockage
$chatDir = __DIR__ . '/chat';
$chatFile = $chatDir . '/messages.json';
$usersFile = $chatDir . '/users.json';

// --- Création auto du dossier si nécessaire
if (!is_dir($chatDir)) {
    mkdir($chatDir, 0755, true);
}

// --- Création fichiers si inexistants
if (!file_exists($chatFile)) {
    file_put_contents($chatFile, json_encode([]));
}
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, json_encode([]));
}

// --- Charger les messages et utilisateurs
$messages = json_decode(file_get_contents($chatFile), true);
if (!is_array($messages)) $messages = [];

$users = json_decode(file_get_contents($usersFile), true);
if (!is_array($users)) $users = [];

// --- Action demandée
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

// --- Vérifier si login disponible
if ($action === 'checkLogin') {
    $login = trim($_GET['login'] ?? '');
    $ok = ($login !== '' && !in_array($login, $users));
    if ($ok) {
        $users[] = $login;
        file_put_contents($usersFile, json_encode($users));
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => $ok]);
    exit;
}

// --- Déconnexion
if ($action === 'logout') {
    $login = trim($_POST['login'] ?? '');
    if (($key = array_search($login, $users)) !== false) {
        unset($users[$key]);
        $users = array_values($users);
        file_put_contents($usersFile, json_encode($users));
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// --- Envoi message
if ($action === 'send') {
    $login = trim($_POST['login'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($login !== '' && $message !== '') {
        $messages[] = [
            'login' => htmlspecialchars($login, ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'time' => date('H:i:s')
        ];

        // Limiter le fichier à 200 derniers messages
        if (count($messages) > 200) {
            $messages = array_slice($messages, -200);
        }

        file_put_contents($chatFile, json_encode($messages));
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

// --- Liste des messages
if ($action === 'list') {
    header('Content-Type: application/json');
    echo json_encode($messages);
    exit;
}

// --- Action inconnue
http_response_code(400);
echo json_encode(['error' => 'Action invalide']);