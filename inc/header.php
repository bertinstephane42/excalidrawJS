<?php 
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
} 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Application de dessin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                🎨 <span>ExcalidrawJS</span>
            </div>
            <nav class="navbar">
                <a class="nav-btn" href="dashboard.php">Accueil</a>
                <a class="nav-btn" href="dessin.php">Éditeur</a>
                <a class="nav-btn" href="fichiers.php">Fichiers</a>
                <a class="nav-btn logout" href="logout.php">Déconnexion</a>
            </nav>
        </div>
    </header>
