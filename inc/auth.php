<?php
session_start();
require_once 'users.php';

function login($username, $password) {
    global $users;

    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        $_SESSION['user'] = $username;
        $_SESSION['role'] = ($username === 'admin') ? 'admin' : 'etudiant';
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>