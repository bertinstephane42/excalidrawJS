<?php
require_once 'inc/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

include 'inc/header.php';
?>

<div class="main-app dashboard">
    <h2>Bienvenue, <?= htmlspecialchars($_SESSION['user']) ?> <span class="role">(<?= $_SESSION['role'] ?>)</span></h2>

    <div class="dashboard-buttons">
        <a class="dash-btn" href="dessin.php">Ouvrir l'éditeur de dessin</a>
         <a class="dash-btn" href="voir.php">Voir le live</a>
        <a class="dash-btn" href="fichiers.php">Voir les dessins enregistrés</a>
		<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a class="dash-btn" href="manage_lock.php">Gérer les verrous</a>
        <?php endif; ?>
        <a class="dash-btn logout" href="logout.php">Se déconnecter</a>
    </div>
</div>
