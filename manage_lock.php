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
$pageTitle = $isAdmin ? "Administration" : "Gestion des verrous";

if ($isAdmin) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];
    echo "<script>const csrfToken = " . json_encode($csrfToken) . ";</script>";
}
?>
<style>
.button-row {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    align-items: stretch;
}
.uniform-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.7rem 1.2rem;
    font-weight: 500;
    border-radius: 10px;
    text-decoration: none;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.2s;
    white-space: nowrap;
    height: 45px;
}
.uniform-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}
#helpBtn.uniform-btn { background-color: #3b82f6; color: #fff; }
#helpBtn.uniform-btn:hover { background-color: #1d4ed8; }
#releaseLockBtn.uniform-btn { background-color: #10b981; color: #fff; }
#releaseLockBtn.uniform-btn:hover { background-color: #047857; }
#deleteChatBtn.uniform-btn { background-color: #ef4444; color: #fff; }
#deleteChatBtn.uniform-btn:hover { background-color: #b91c1c; }
.submenu {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}
.submenu button {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
}
.submenu button.active { background-color: #3b82f6; color: #fff; }
</style>

<div class="main-app manage-lock">
    <h2><?= $pageTitle ?></h2>

    <?php if ($isAdmin): ?>
        <div class="submenu">
            <button id="tabLock" class="active">Gestion des verrous</button>
            <button id="tabDeleteChat">Supprimer le chat</button>
        </div>

        <div id="sectionLocks">
            <p>Depuis cette section, vous pouvez forcer la suppression du verrou côté serveur et effacer le verrouillage local (onglets).</p>
            <div class="button-row">
                <button id="helpBtn" class="uniform-btn">❓ Aide</button>
                <button id="releaseLockBtn" class="uniform-btn">🔓 Supprimer le verrou serveur + local</button>
            </div>
        </div>

        <div id="sectionDeleteChat" style="display:none;">
            <p>Attention : cette action supprimera tous les messages et utilisateurs du chat.</p>
            <div class="button-row">
                <button id="deleteChatBtn" class="uniform-btn">🗑️ Supprimer le chat</button>
            </div>
            <div id="resultDeleteChat" style="margin-top:15px; font-weight:bold;"></div>
        </div>

    <?php else: ?>
        <p>Depuis cette page, vous pouvez uniquement effacer votre verrouillage local.</p>
        <div class="button-row">
            <button id="helpBtn" class="uniform-btn">❓ Aide</button>
            <button id="releaseLockBtn" class="uniform-btn">🔓 Supprimer le verrou local</button>
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
        <p>Les verrous sont utilisés pour éviter que plusieurs personnes modifient le même dessin en même temps.</p>
        <ul>
            <li><strong>Admin :</strong> peut supprimer le verrou côté serveur et le verrou local des onglets.</li>
            <li><strong>Étudiant :</strong> ne peut supprimer que son verrou local dans le navigateur.</li>
            <li><strong>Verrou côté serveur :</strong> empêche tout autre utilisateur d’ouvrir/modifier le dessin.</li>
            <li><strong>Verrou local :</strong> stocké dans le navigateur pour indiquer un onglet actif.</li>
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
// 🔹 Gestion du menu admin
<?php if ($isAdmin): ?>
const tabLock = document.getElementById("tabLock");
const tabDelete = document.getElementById("tabDeleteChat");
const sectionLocks = document.getElementById("sectionLocks");
const sectionDelete = document.getElementById("sectionDeleteChat");

tabLock.addEventListener("click", () => {
    tabLock.classList.add("active");
    tabDelete.classList.remove("active");
    sectionLocks.style.display = "block";
    sectionDelete.style.display = "none";
});
tabDelete.addEventListener("click", () => {
    tabDelete.classList.add("active");
    tabLock.classList.remove("active");
    sectionLocks.style.display = "none";
    sectionDelete.style.display = "block";
});
<?php endif; ?>

// 🔹 Gestion de la suppression des verrous
document.getElementById("releaseLockBtn").addEventListener("click", () => {
    const LOCK_KEY = "dessin_tab_lock";
    document.getElementById("resultlcl").innerText = "";
    document.getElementById("resultsrv").innerText = "";

    <?php if ($isAdmin): ?>
    fetch("release_lock.php", { method: "POST", credentials: "include" })
        .then(r => r.text())
        .then(txt => { document.getElementById("resultsrv").innerText = "Résultat serveur : " + txt; })
        .catch(err => { document.getElementById("resultsrv").innerText = "Erreur serveur : " + err; });
    <?php endif; ?>

    if (localStorage.getItem(LOCK_KEY)) {
        localStorage.removeItem(LOCK_KEY);
        document.getElementById("resultlcl").innerText = "\nVerrou local supprimé.";
    } else {
        document.getElementById("resultlcl").innerText = "\nAucun verrou local trouvé.";
    }
});

// 🔹 Gestion de la suppression du chat (admin)
<?php if ($isAdmin): ?>
document.getElementById("deleteChatBtn").addEventListener("click", () => {
    if (!confirm("Voulez-vous vraiment supprimer tous les fichiers du chat ?")) return;
	 document.getElementById("resultDeleteChat").innerText = '';

    fetch("delete_chat.php", {
        method: "POST",
        credentials: "include",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "csrf_token=" + encodeURIComponent(csrfToken)
    })
    .then(r => r.text())
    .then(txt => { document.getElementById("resultDeleteChat").innerText = txt; })
    .catch(err => { document.getElementById("resultDeleteChat").innerText = "Erreur : " + err; });
});
<?php endif; ?>

// 🔹 Gestion de la modale d'aide
const helpModal = document.getElementById("helpModal");
document.getElementById("helpBtn").addEventListener("click", () => { helpModal.style.display = "flex"; });
document.getElementById("closeHelpBtn").addEventListener("click", () => { helpModal.style.display = "none"; });
</script>