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
<footer class="site-footer" style="
    background-color: #f5f5f5;
    padding: 1.5rem 2rem;
    text-align: center;
    font-size: 0.9rem;
    color: #555;
    border-top: 1px solid #ddd;
    margin-top: 2rem;
">
    <p>&copy; 2025 ExcalidrawJS.</p>
</footer>
</body>
</html>