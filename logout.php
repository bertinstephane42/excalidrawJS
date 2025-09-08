<?php
require_once 'inc/auth.php';

// Déconnexion sécurisée
if (isLoggedIn()) {
    logout();
} else {
    header('Location: login.php');
    exit;
}