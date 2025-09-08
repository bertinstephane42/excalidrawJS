<?php
session_start();
require_once __DIR__ . '/users.php';
$GLOBALS['users'] = $users ?? [];

/** ─────────────────────────── UTILITAIRES UTILISATEURS ─────────────────────────── */

function _users_file_path(): string {
    // Fichier users.php à mettre à jour
    return __DIR__ . '/users.php';
}

function getAllUsers(): array {
    // $users vient de l'inclusion en haut de fichier
    return $GLOBALS['users'] ?? [];
}

// ------------------------------
// Sauvegarde sécurisée des utilisateurs
// ------------------------------
function saveUsers(array $users): bool {
    $file = _users_file_path();

    // Génère le code PHP à écrire
    $export = var_export($users, true);
    $php = <<<PHP
<?php
/**
 * Liste des utilisateurs.
 * Chaque entrée : 'login' => ['password' => <hash>, 'role' => 'admin'|'user', 'disabled' => bool]
 * NB : Le compte 'admin' est l’admin principal et NE PEUT PAS être supprimé ni désactivé.
 */
\$users = {$export};

PHP;

    // Écrit dans le fichier
    $bytesWritten = file_put_contents($file, $php, LOCK_EX);

    if ($bytesWritten === false) {
        return false; // écriture échouée
    }

    // Recharge immédiatement le fichier pour mettre à jour $GLOBALS['users']
    require $file;                  // charge $users défini dans users.php
    $GLOBALS['users'] = $users ?? [];

    return true;
}

function userExists(string $username): bool {
    $all = getAllUsers();
    return isset($all[$username]);
}

function isUserDisabled(string $username): bool {
    $all = getAllUsers();
    return !empty($all[$username]['disabled']);
}

function addUser(string $username, string $password, string $role = 'user'): array {
    $all = getAllUsers();
    $username = trim($username);

    if ($username === '' || !preg_match('/^[a-zA-Z0-9_\-\.]{3,32}$/', $username)) {
        return [false, "Nom d'utilisateur invalide (3–32 caractères alphanumériques, « _ - . » autorisés)."];
    }
    if (isset($all[$username])) {
        return [false, "Cet utilisateur existe déjà."];
    }
    if (!in_array($role, ['admin', 'user'], true)) {
        return [false, "Rôle invalide."];
    }
    if (strlen($password) < 8) {
        return [false, "Le mot de passe doit contenir au moins 8 caractères."];
    }

    $all[$username] = [
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role'     => $role,
        'disabled' => false,
    ];
    $ok = saveUsers($all);
    return [$ok, $ok ? "Utilisateur « {$username} » ajouté." : "Erreur lors de l'enregistrement."];
}

// ------------------------------
// Modification du mot de passe
// ------------------------------
function changeUserPassword(string $username, string $newPassword): array {
    $all = getAllUsers();
    
    if (!isset($all[$username])) {
        return [false, "Utilisateur introuvable."];
    }

    if (strlen($newPassword) < 8) {
        return [false, "Le mot de passe doit contenir au moins 8 caractères."];
    }

    // Hash sécurisé
    $all[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
	error_log("DEBUG: Nouveau hash pour $username = " . $all[$username]['password']);

    // Sauvegarde et mise à jour globale
    $ok = saveUsers($all);

    return [$ok, $ok ? "Mot de passe de « {$username} » mis à jour." : "Erreur lors de l'enregistrement."];
}

function setUserRole(string $username, string $role): array {
    $all = getAllUsers();
    if (!isset($all[$username])) return [false, "Utilisateur introuvable."];
    if ($username === 'admin') return [false, "Le rôle de l'admin principal ne peut pas être modifié."];

    if (!in_array($role, ['admin', 'user'], true)) return [false, "Rôle invalide."];

    $all[$username]['role'] = $role;
    $ok = saveUsers($all);
    return [$ok, $ok ? "Rôle mis à jour." : "Erreur lors de l'enregistrement."];
}

function setUserDisabled(string $username, bool $disabled): array {
    $all = getAllUsers();
    if (!isset($all[$username])) return [false, "Utilisateur introuvable."];
    if ($username === 'admin') return [false, "L'admin principal ne peut pas être désactivé."];

    $all[$username]['disabled'] = $disabled;
    $ok = saveUsers($all);
    return [$ok, $ok ? ($disabled ? "Compte désactivé." : "Compte réactivé.") : "Erreur lors de l'enregistrement."];
}

function deleteUser(string $username): array {
    $all = getAllUsers();
    if (!isset($all[$username])) return [false, "Utilisateur introuvable."];
    if ($username === 'admin') return [false, "Le compte 'admin' ne peut pas être supprimé."];

    unset($all[$username]);
    $ok = saveUsers($all);
    return [$ok, $ok ? "Utilisateur supprimé." : "Erreur lors de l'enregistrement."];
}

/** ──────────────────────────────── BRUTE FORCE ──────────────────────────────── */
define('MAX_LOGIN_ATTEMPTS', 3);       // nombre de tentatives autorisées
define('LOGIN_TIMEOUT', 30);            // temps de blocage en secondes après 3 échecs

function recordFailedLogin(): void {
    if (!isset($_SESSION['failed_logins'])) {
        $_SESSION['failed_logins'] = 0;
        $_SESSION['last_failed_login'] = time();
    } else {
        $_SESSION['failed_logins']++;
        $_SESSION['last_failed_login'] = time();
    }
}

function canAttemptLogin(): bool {
    if (!isset($_SESSION['failed_logins'])) return true;

    if ($_SESSION['failed_logins'] < MAX_LOGIN_ATTEMPTS) {
        return true;
    }

    $elapsed = time() - ($_SESSION['last_failed_login'] ?? 0);
    return $elapsed >= LOGIN_TIMEOUT;
}

function resetFailedLogins(): void {
    unset($_SESSION['failed_logins'], $_SESSION['last_failed_login']);
}

function getClientIP(): string {
    // IP du client, prend en compte les proxys
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // peut contenir plusieurs IP séparées par des virgules
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'IP inconnue';
    }
}

function logUserEvent(string $username, string $event, string $details = ''): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/users.log';
    $time = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $line = "[$time] [$username] [$event] [IP: $ip] $details\n";
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/** ──────────────────────────────── AUTH ──────────────────────────────── */
function login(string $username, string $password) {
    if (!canAttemptLogin()) {
        logUserEvent($username, 'timeout', 'Trop de tentatives, attente requise.');
        return 'timeout';
    }

    $all = getAllUsers();

    if (!isset($all[$username])) {
        recordFailedLogin();
        logUserEvent($username, 'failed', 'Utilisateur inexistant.');
        return false;
    }

    if (!empty($all[$username]['disabled'])) {
        logUserEvent($username, 'disabled', 'Compte désactivé.');
        return 'disabled';
    }

    $hash = $all[$username]['password'] ?? '';
    if (is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
        $_SESSION['user'] = $username;
        $_SESSION['role'] = $all[$username]['role'] ?? (($username === 'admin') ? 'admin' : 'user');
        resetFailedLogins();
        logUserEvent($username, 'success', 'Connexion réussie.');
        return true;
    }

    recordFailedLogin();
    logUserEvent($username, 'failed', 'Mot de passe incorrect.');
    return false;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function logout(): void {
    session_destroy();
    header('Location: login.php');
    exit;
}