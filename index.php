<?php
require_once __DIR__ . '/inc/auth.php';

// Redirige selon la connexion
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}
