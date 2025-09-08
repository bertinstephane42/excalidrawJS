<?php
require_once __DIR__ . '/inc/auth.php';
if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Accès refusé.");
}

$username = trim($_POST['username'] ?? '');
$pwd1     = $_POST['password1'] ?? '';
$pwd2     = $_POST['password2'] ?? '';
$csrf     = $_POST['csrf_token'] ?? '';

// Vérification CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    exit("Token CSRF invalide.");
}

// Vérifs mots de passe
if ($pwd1 !== $pwd2) {
    exit("Les mots de passe ne correspondent pas.");
}
if (strlen($pwd1) < 8) {
    exit("Le mot de passe doit contenir au moins 8 caractères.");
}

// Appel logique
[$ok, $msg] = changeUserPassword($username, $pwd1);

error_log("DEBUG users après change : " . print_r(getAllUsers(), true));

// Réponse texte simple
exit($msg);