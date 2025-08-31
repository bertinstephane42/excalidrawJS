<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }
include __DIR__ . '/inc/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page déjà ouverte</title>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                <span>Admin Panel</span>
            </div>
            <nav class="navbar">
                <a href="dashboard.php" class="nav-btn">Retour à l'accueil</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-app">
        <h1>Page déjà ouverte</h1>
        <p>Cette page est déjà ouverte par un autre administrateur. Vous ne pouvez pas accéder à cette page tant qu'elle reste active.</p>
        <p>Merci de patienter ou de vous déconnecter avant de réessayer.</p>

        <div class="dashboard-buttons">
            <a href="dashboard.php" class="dash-btn">Retour à la page d'accueil</a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2025 ExcalidrawJS.</p>
    </footer>
</body>
</html>