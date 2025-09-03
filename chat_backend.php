<?php
session_start();
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

$chatDir = __DIR__ . '/chat';
$chatFile = $chatDir . '/messages.json';
$usersFile = $chatDir . '/users.json';

if (!is_dir($chatDir)) mkdir($chatDir, 0755, true);
if (!file_exists($chatFile)) file_put_contents($chatFile, json_encode([]));
if (!file_exists($usersFile)) file_put_contents($usersFile, json_encode([]));

$messages = json_decode(file_get_contents($chatFile), true) ?: [];
$users = json_decode(file_get_contents($usersFile), true) ?: [];

// --- Durée d'inactivité maximale en secondes
define('MAX_INACTIVITY', 60);

// --- Nettoyage des utilisateurs inactifs
function cleanupUsers(&$users, $usersFile) {
    $changed = false;
    foreach ($users as $login => $data) {
        $sess = $data['session_id'] ?? '';
        // Si la session n'existe plus ou n'est plus active
        if ($sess && $sess !== session_id() && !file_exists(session_save_path() . "/sess_$sess")) {
            unset($users[$login]);
            $changed = true;
        }
    }
    if ($changed) {
        file_put_contents($usersFile, json_encode($users));
    }
}

// --- Mettre à jour l'activité d'un utilisateur
function updateUserActivity(&$users, $login, $usersFile) {
    $users[$login] = [
        'session_id' => session_id(),
        'last_active' => time()
    ];
    file_put_contents($usersFile, json_encode($users));
}

// --- Vérification du token pour les actions sécurisées
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$secureActions = ['send', 'logout', 'list']; // actions à sécuriser
if (in_array($action, $secureActions)) {
    $headerToken = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if ($headerToken === '' || $headerToken !== ($_SESSION['chat_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Acces interdit : token invalide']);
        exit;
    }
}

// --- Login
if ($action === 'login') {
    $login = trim($_POST['login'] ?? '');
    
    // Nettoyage des utilisateurs dont la session n'existe plus
    foreach ($users as $key => $user) {
        $sess_file = session_save_path() . "/sess_$user[session_id]";
        if (!file_exists($sess_file)) {
            unset($users[$key]);
        }
    }

    // Vérifier que le login n'est pas déjà actif dans une session différente
    if ($login === '' || (isset($users[$login]) && $users[$login]['session_id'] !== session_id())) {
        echo json_encode(['ok' => false, 'error' => 'Login indisponible']);
        exit;
    }

    // Ajouter / mettre à jour l'utilisateur
    $users[$login] = ['session_id'=>session_id(), 'last_active'=>time()];
    file_put_contents($usersFile, json_encode($users));

    $_SESSION['login'] = $login;
    $_SESSION['chat_token'] = $_POST['token'] ?? ($_SESSION['chat_token'] ?? bin2hex(random_bytes(16)));

    echo json_encode(['ok' => true, 'token' => $_SESSION['chat_token']]);
    exit;
}

// --- Logout
if ($action === 'logout') {
    $login = $_POST['login'] ?? $_SESSION['login'] ?? '';
    if ($login !== '' && isset($users[$login])) {
        unset($users[$login]);
        file_put_contents($usersFile, json_encode($users));
    }

    unset($_SESSION['login'], $_SESSION['chat_token']);
    echo json_encode(['ok' => true]);
    exit;
}

// --- Envoi message
if ($action === 'send') {
    $login = trim($_POST['login'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($login !== '' && $message !== '') {
        updateUserActivity($users, $login, $usersFile);

        $messages[] = ['login'=>htmlspecialchars($login),'message'=>htmlspecialchars($message),'time'=>date('H:i:s')];
        if (count($messages)>200) $messages = array_slice($messages,-200);
        file_put_contents($chatFile, json_encode($messages));
    }
    echo json_encode(['status'=>'ok']);
    exit;
}

// --- Liste messages
if ($action === 'list') {
    $login = $_SESSION['login'] ?? '';
    if ($login !== '') {
        updateUserActivity($users, $login, $usersFile);
    }
    cleanupUsers($users, $usersFile);
    echo json_encode($messages);
    exit;
}

http_response_code(400);
echo json_encode(['error'=>'Action invalide']);