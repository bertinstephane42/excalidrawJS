<?php
session_start();
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Accès interdit');
}

include __DIR__ . '/inc/header.php';
?>
<div class="main-app manage-lock">
    <h2>Gestion des verrous</h2>
    <p>Depuis cette page, vous pouvez forcer la suppression du verrou côté serveur et effacer le verrouillage local (onglets).</p>

    <button id="releaseLockBtn" class="dash-btn">🔓 Supprimer le verrou</button>
    <div id="result" style="margin-top:15px; font-weight:bold;"></div>
</div>

<script>
document.getElementById("releaseLockBtn").addEventListener("click", () => {
    // 🔹 1. Appel au serveur pour supprimer le lock fichier
    fetch("release_lock.php", { method: "POST", credentials: "include" })
        .then(r => r.text())
        .then(txt => {
            document.getElementById("result").innerText = "Résultat serveur : " + txt;
        })
        .catch(err => {
            document.getElementById("result").innerText = "Erreur : " + err;
        });

    // 🔹 2. Suppression du verrou localStorage
    localStorage.removeItem("dessin_tab_lock");
});
</script>