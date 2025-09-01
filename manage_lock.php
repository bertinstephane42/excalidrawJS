<?php
session_start();
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/inc/header.php';

// Déterminer le rôle pour adapter l'affichage
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>
<style>
.button-row {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    align-items: stretch; /* étire tous les boutons à la même hauteur */
}

/* Boutons uniformes */
.uniform-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.7rem 1.2rem;
    font-weight: 500;
    border-radius: 10px;
    text-decoration: none;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.2s;
    white-space: nowrap; /* empêche le texte de revenir à la ligne */
    height: 45px;        /* force une hauteur identique */
}

/* Hover identique */
.uniform-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}

/* Couleurs */
#helpBtn.uniform-btn {
    background-color: #3b82f6;
    color: #fff;
}
#helpBtn.uniform-btn:hover {
    background-color: #1d4ed8;
}

#releaseLockBtn.uniform-btn {
    background-color: #10b981;
    color: #fff;
}
#releaseLockBtn.uniform-btn:hover {
    background-color: #047857;
}
</style>
<div class="main-app manage-lock">
    <h2>Gestion des verrous</h2>

     <?php if ($isAdmin): ?>
        <p>Depuis cette page, vous pouvez forcer la suppression du verrou côté serveur et effacer le verrouillage local (onglets).</p>
		<div class="button-row">
			<button id="helpBtn" class="dashadm-btn uniform-btn" style="margin-bottom:15px;">❓ Aide</button>
			<button id="releaseLockBtn" class="dashadm-btn uniform-btn">🔓 Supprimer le verrou serveur + local</button>
		</div>
    <?php else: ?>
        <p>Depuis cette page, vous pouvez uniquement effacer votre verrouillage local.</p>
		  <div class="button-row">
			<button id="helpBtn" class="dashadm-btn uniform-btn" style="margin-bottom:15px;">❓ Aide</button>
			<button id="releaseLockBtn" class="dashadm-btn uniform-btn">🔓 Supprimer le verrou local</button>
		  </div>
    <?php endif; ?>

    <div id="resultsrv" style="margin-top:15px; font-weight:bold;"></div>
    <div id="resultlcl" style="margin-top:15px; font-weight:bold;"></div>
</div>

<!-- Modale d'aide -->
<div id="helpModal" style="
    display:none;
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
    z-index:1000;
">
    <div style="
        background:#fff;
        padding:20px;
        border-radius:10px;
        max-width:500px;
        width:90%;
        box-shadow:0 4px 12px rgba(0,0,0,0.3);
        position:relative;
    ">
        <h3>Aide : Fonctionnement des verrous</h3>
		<p>Les verrous sont utilisés pour éviter que plusieurs personnes modifient le même dessin en même temps, ce qui pourrait provoquer des conflits ou perdre des données.</p>
		<ul>
			<li>Le fonctionnement dépend de votre rôle sur cette page :</li>
			<ul>
				<li><strong>Admin :</strong> peut supprimer le verrou côté serveur et le verrou local des onglets. Cela permet de débloquer l’accès pour tous les utilisateurs si un verrou est resté actif.</li>
				<li><strong>Étudiant :</strong> ne peut supprimer que son verrou local dans le navigateur. Cela ne libère pas le verrou pour les autres utilisateurs.</li>
			</ul>
			<li><strong>Verrou côté serveur :</strong> stocké sur le serveur pour empêcher tout autre utilisateur d’ouvrir ou modifier le dessin tant que le verrou est actif. Supprimer ce verrou le libère pour tous.</li>
			<li><strong>Verrou local (localStorage) :</strong> stocké uniquement dans votre navigateur pour indiquer que vous avez déjà un onglet actif sur ce dessin. Supprimer ce verrou permet de rouvrir l’éditeur dans le même navigateur, mais n’affecte pas les autres utilisateurs.</li>
		</ul>

        <button id="closeHelpBtn" style="
            position:absolute;
            top:10px; right:10px;
            background:#ef4444; color:#fff; border:none; padding:5px 10px;
            border-radius:5px; cursor:pointer;
        ">✖</button>
    </div>
</div>

<script>
// 🔹 Gestion de la suppression des verrous
document.getElementById("releaseLockBtn").addEventListener("click", () => {
    const LOCK_KEY = "dessin_tab_lock";
    document.getElementById("resultlcl").innerText = "";
    document.getElementById("resultsrv").innerText = "";

    <?php if ($isAdmin): ?>
    // Admin : suppression côté serveur
    fetch("release_lock.php", { method: "POST", credentials: "include" })
        .then(r => r.text())
        .then(txt => {
            document.getElementById("resultsrv").innerText = "Résultat serveur : " + txt;
        })
        .catch(err => {
            document.getElementById("resultsrv").innerText = "Erreur serveur : " + err;
        });
    <?php endif; ?>

    // Tous les utilisateurs : suppression du verrou localStorage
    if (localStorage.getItem(LOCK_KEY)) {
        localStorage.removeItem(LOCK_KEY);
        document.getElementById("resultlcl").innerText = "\nVerrou local supprimé.";
    } else {
        document.getElementById("resultlcl").innerText = "\nAucun verrou local trouvé.";
    }
});

// 🔹 Gestion de la modale d'aide
const helpModal = document.getElementById("helpModal");
document.getElementById("helpBtn").addEventListener("click", () => {
    helpModal.style.display = "flex";
});
document.getElementById("closeHelpBtn").addEventListener("click", () => {
    helpModal.style.display = "none";
});
</script>