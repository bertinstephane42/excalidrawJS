<?php
session_start();
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/inc/header.php';

// Rôle pour adapter l'affichage
$isAdmin = isAdmin();
$pageTitle = $isAdmin ? "Administration" : "Gestion des verrous";

// CSRF pour actions admin
if ($isAdmin) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];
    echo "<script>const csrfToken = " . json_encode($csrfToken) . ";</script>";
}

$lockFile = __DIR__ . '/chat/chat.lock';
$chatDisabled = file_exists($lockFile);

/** ───────────────────────── TRAITEMENT FORMULAIRES UTILISATEURS ───────────────────────── */
$userFeedback = '';

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $userFeedback = "Token CSRF invalide.";
    } else {
        $action = $_POST['action'];
        $username = trim($_POST['username'] ?? '');
        $role     = trim($_POST['role'] ?? '');
        $pwd1     = $_POST['password1'] ?? '';
        $pwd2     = $_POST['password2'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        switch ($action) {
            case 'add_user':
                if ($pwd1 !== $pwd2) {
                    $userFeedback = "Les mots de passe ne correspondent pas.";
                    break;
                }
                [$ok, $msg] = addUser($username, $pwd1, in_array($role, ['admin','user'], true) ? $role : 'user');
                $userFeedback = $msg;
                break;

            case 'delete_user':
                if ($confirm !== 'YES') {
                    $userFeedback = "Confirmation manquante. Tapez YES pour confirmer.";
                    break;
                }
                [$ok, $msg] = deleteUser($username);
                $userFeedback = $msg;
                // Si on supprime l'utilisateur courant (hors admin principal), déconnexion par sécurité
                if ($ok && isset($_SESSION['user']) && $_SESSION['user'] === $username) {
                    logout();
                }
                break;

            case 'toggle_disable':
                $target = $username;
                $all = getAllUsers();
                if (!isset($all[$target])) {
                    $userFeedback = "Utilisateur introuvable.";
                    break;
                }
                $newState = empty($all[$target]['disabled']); // toggle
                [$ok, $msg] = setUserDisabled($target, $newState);
                $userFeedback = $msg;
                // Si on désactive l'utilisateur courant, déconnexion
                if ($ok && $newState && isset($_SESSION['user']) && $_SESSION['user'] === $target) {
                    logout();
                }
                break;

             case 'change_role':
                $all = getAllUsers();
                if (!isset($all[$username])) {
                    $userFeedback = "Utilisateur introuvable.";
                    break;
                }

                $currentRole = $all[$username]['role'] ?? null;

                if ($role !== '' && $currentRole !== $role) {
                    [$ok, $msg] = setUserRole($username, $role);
                    $userFeedback = $msg ?: ($ok ? "Rôle de « {$username} » mis à jour." : "Erreur lors de la mise à jour du rôle.");
                } else {
                    $userFeedback = "Aucun changement détecté pour « {$username} ».";
                }
                break;

            case 'change_password':
                if ($username === '') {
                    $userFeedback = "Utilisateur manquant.";
                    break;
                }
                if ($pwd1 !== $pwd2) {
                    $userFeedback = "Les mots de passe ne correspondent pas.";
                    break;
                }
                if (strlen($pwd1) < 8) {
                    $userFeedback = "Le mot de passe doit contenir au moins 8 caractères.";
                    break;
                }

                $all = getAllUsers();
                if (!isset($all[$username])) {
                    $userFeedback = "Utilisateur introuvable.";
                    break;
                }

                [$ok, $msg] = changeUserPassword($username, $pwd1);
                $userFeedback = $msg ?: ($ok ? "Mot de passe mis à jour avec succès." : "Erreur lors de la mise à jour du mot de passe.");
                break;
        }
        // Recharge la liste à jour
        $usersList = getAllUsers();
    }
}

// Toujours dispo pour l’affichage
$usersList = getAllUsers();
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
.uniform-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.2); }
#helpBtn.uniform-btn { background-color: #3b82f6; color: #fff; }
#helpBtn.uniform-btn:hover { background-color: #1d4ed8; }
#releaseLockBtn.uniform-btn { background-color: #10b981; color: #fff; }
#releaseLockBtn.uniform-btn:hover { background-color: #047857; }
#deleteChatBtn.uniform-btn { background-color: #ef4444; color: #fff; }
#deleteChatBtn.uniform-btn:hover { background-color: #b91c1c; }
#toggleChatBtn.uniform-btn { background-color: #f59e0b; color: #fff; }
#toggleChatBtn.uniform-btn:hover { background-color: #b45309; }
.submenu { display: flex; gap: 10px; margin-bottom: 15px; }
.submenu button { padding: 0.5rem 1rem; border-radius: 8px; border: none; cursor: pointer; }
.submenu button.active { background-color: #3b82f6; color: #fff; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; }
.badge { padding: 2px 8px; border-radius: 999px; font-size: 12px; }
.badge-admin { background:#1f2937; color:#fff; }
.badge-user { background:#e5e7eb; color:#111827; }
.badge-on { background:#dcfce7; color:#065f46; }
.badge-off { background:#fee2e2; color:#991b1b; }
.form-inline { display:flex; gap:8px; align-items:center; flex-wrap: wrap; }
input[type="text"], input[type="password"], select {
    padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px;
}
.confirm {
    border: 1px dashed #fca5a5; background: #fff7f7;
}
.section-content {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    background: #f9fafb;
    margin-top: 10px;
}
.section-content .uniform-btn {
    min-width: 110px; /* assure une largeur uniforme */
    font-size: 14px;  /* harmoniser la taille du texte */
    height: 40px;     /* hauteur standard */
    padding: 0.5rem 1rem;
    background-color: #3b82f6; /* couleur principale par défaut */
    color: #fff;
    border: none;
    cursor: pointer;
    border-radius: 8px;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.2s;
}
.section-content .uniform-btn:hover {
    background-color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Différencier les actions spécifiques si besoin */
.section-content .btn-role { background-color: #10b981; }       /* rôle */
.section-content .btn-password { background-color: #f59e0b; }  /* mot de passe */
.section-content .btn-toggle { background-color: #ef4444; }    /* désactiver / activer */
.section-content .btn-delete { background-color: #b91c1c; }    /* supprimer */
/* Couleurs au survol plus foncées */
.section-content .btn-role:hover { background-color: #047857; }      /* rôle */
.section-content .btn-password:hover { background-color: #b45309; }  /* mot de passe */
.section-content .btn-toggle:hover { background-color: #b91c1c; }    /* désactiver / activer */
.section-content .btn-delete:hover { background-color: #7f1d1d; }    /* supprimer */
</style>

<div class="main-app manage-lock">
    <h2><?= htmlspecialchars($pageTitle) ?></h2>

    <?php if ($isAdmin): ?>
        <div class="submenu">
		    <button id="tabUsers" class="active">Utilisateurs</button>
            <button id="tabLock">Gestion des verrous</button>
            <button id="tabDeleteChat">Supprimer le chat</button>
            <button id="tabToggleChat">Activation du chat</button>
			<button id="tabLogs">Journal</button>
        </div>

        <!-- Section verrous -->
        <div id="sectionLocks" class="section-content" style="display:none;">
            <p>Depuis cette section, vous pouvez forcer la suppression du verrou côté serveur et effacer le verrouillage local (onglets).</p>
            <div class="button-row">
                <button id="helpBtn" class="uniform-btn">❓ Aide</button>
                <button id="releaseLockBtn" class="uniform-btn">🔓 Supprimer le verrou serveur + local</button>
            </div>
        </div>

        <!-- Section suppression du chat -->
        <div id="sectionDeleteChat" class="section-content" style="display:none;">
            <p>Attention : cette action supprimera tous les messages et utilisateurs du chat.</p>
            <div class="button-row">
                <button id="deleteChatBtn" class="uniform-btn">🗑️ Supprimer le chat</button>
            </div>
            <div id="resultDeleteChat" style="margin-top:15px; font-weight:bold;"></div>
        </div>

        <!-- Section activation/désactivation du chat -->
        <div id="sectionToggleChat" class="section-content" style="display:none;">
            <p>État actuel du chat :
                <strong id="chatState"><?= $chatDisabled ? "❌ Désactivé" : "✅ Activé" ?></strong>
            </p>
            <div class="button-row">
                <button id="toggleChatBtn" class="uniform-btn">
                    <?= $chatDisabled ? "Activer le chat" : "Désactiver le chat" ?>
                </button>
            </div>
            <div id="resultToggleChat" style="margin-top:15px; font-weight:bold;"></div>
        </div>
		
		<!-- Modale Journal -->
		<div id="logsModal" style="
			display:none; position:fixed; top:0; left:0; right:0; bottom:0;
			background: rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
			<div style="
				background:#fff; padding:20px; border-radius:10px; max-width:800px; width:90%;
				max-height:80vh; overflow:auto; box-shadow:0 4px 12px rgba(0,0,0,0.3); position:relative;">
				<h3>Journal des connexions</h3>
				<pre id="logsContent" style="white-space: pre-wrap; font-family:monospace;"></pre>
				<button id="closeLogsBtn" style="
					position:absolute; top:10px; right:10px; background:#ef4444; color:#fff; border:none; padding:5px 10px;
					border-radius:5px; cursor:pointer;">✖</button>
			</div>
		</div>

        <!-- Section utilisateurs -->
        <div id="sectionUsers" class="section-content" >
            <?php if ($userFeedback): ?>
                <div id="user-feedback" style="margin:10px 0; padding:10px; border-radius:8px; background:#f1f5f9;">
                    <?= htmlspecialchars($userFeedback) ?>
                </div>
            <?php endif; ?>

            <h3>Ajouter un utilisateur</h3>
            <form method="post" class="form-inline" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="add_user">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required placeholder="ex : jdupont" minlength="3" maxlength="32" pattern="[A-Za-z0-9_.\-]+">
                <label>Rôle</label>
                <select name="role">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                </select>
                <label>Mot de passe</label>
                <input type="password" name="password1" required minlength="8" placeholder="Min. 8 caractères">
                <label>Confirmer</label>
                <input type="password" name="password2" required minlength="8" placeholder="Répéter le mot de passe">
                <button class="uniform-btn" type="submit" title="créer un nouveau compte utilisateur">➕ Créer</button>
            </form>

            <hr style="margin:16px 0;">

	<h3>Utilisateurs existants</h3>
	<table class="table">
		<thead>
			<tr>
				<th>Utilisateur</th>
				<th>Rôle</th>
				<th>Statut</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($usersList as $u => $info): ?>
			    <?php
					$roleUser = isset($info['role']) ? strtolower(trim($info['role'])) : 'user';
					$roleUser = ($roleUser === 'admin') ? 'admin' : 'user';
					$disabled = !empty($info['disabled']);
				?>
			<tr>
				<td><strong><?= htmlspecialchars($u) ?></strong></td>
				<td>
					<?php if ($u === 'admin'): ?>
						<span class="badge badge-admin">admin</span>
					<?php else: ?>
						<form method="post" class="form-inline" style="gap:6px;">
							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
							<input type="hidden" name="action" value="change_role">
							<input type="hidden" name="username" value="<?= htmlspecialchars($u) ?>">
							<select name="role">
								<option value="user"<?= $roleUser === 'user' ? ' selected' : '' ?>>user</option>
								<option value="admin"<?= $roleUser === 'admin' ? ' selected' : '' ?>>admin</option>
							</select>
							<button class="uniform-btn btn-role" type="submit" title="changer le rôle de l'utilisateur">👤</button>
						</form>
					<?php endif; ?>
				</td>
				<td>
					<span class="badge <?= $disabled ? 'badge-off' : 'badge-on' ?>">
						<?= $disabled ? 'Désactivé' : 'Actif' ?>
					</span>
				</td>
				<td style="display:flex; gap:8px; flex-wrap:wrap;">
					<!-- Changer mot de passe -->
					<form method="post" class="form-inline">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
						<input type="hidden" name="action" value="change_password">
						<input type="hidden" name="username" value="<?= htmlspecialchars($u) ?>">
						<input type="password" name="password1" required
							   placeholder="Nouveau mot de passe (min. 8 caractères)"
							   minlength="8" autocomplete="new-password"
							   title="Le mot de passe doit contenir au moins 8 caractères.">
						<input type="password" name="password2" required
							   placeholder="Confirmer (min. 8 caractères)"
							   minlength="8" autocomplete="new-password"
							   title="Répétez le mot de passe (min. 8 caractères).">
						<button class="uniform-btn btn-password" type="submit" title="changer le mot de passe de l'utilisateur">🔑</button>
					</form>

					<!-- (Dé)activer -->
					<?php if ($u !== 'admin'): ?>
					<form method="post" class="form-inline">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
						<input type="hidden" name="action" value="toggle_disable">
						<input type="hidden" name="username" value="<?= htmlspecialchars($u) ?>">
						<button class="uniform-btn btn-toggle" type="submit" title="activer/désactiver le compte l'utilisateur">
							<?= $disabled ? '✅ Réactiver' : '⛔ Désactiver' ?>
						</button>
					</form>
					<?php endif; ?>

					<!-- Supprimer -->
					<?php if ($u !== 'admin'): ?>
					<form method="post" class="form-inline confirm" onsubmit="return confirm('Confirmer la suppression de <?= htmlspecialchars($u) ?> ?');">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
						<input type="hidden" name="action" value="delete_user">
						<input type="hidden" name="username" value="<?= htmlspecialchars($u) ?>">
						<input type="text" name="confirm" placeholder="Tapez YES" required pattern="YES" title="Entrez YES pour confirmer">
						<button class="uniform-btn btn-delete" type="submit" title="supprimer le compte de l'utilisateur">🗑️ Supprimer</button>
					</form>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
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
    display:none; position:fixed; top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="
        background:#fff; padding:20px; border-radius:10px; max-width:500px; width:90%;
        box-shadow:0 4px 12px rgba(0,0,0,0.3); position:relative;">
        <h3>Aide : Fonctionnement des verrous</h3>
        <p>Les verrous sont utilisés pour éviter que plusieurs personnes modifient le même dessin en même temps.</p>
        <ul>
            <li><strong>Admin :</strong> peut supprimer le verrou côté serveur et le verrou local des onglets.</li>
            <li><strong>Étudiant :</strong> ne peut supprimer que son verrou local dans le navigateur.</li>
            <li><strong>Verrou côté serveur :</strong> empêche tout autre utilisateur d’ouvrir/modifier le dessin.</li>
            <li><strong>Verrou local :</strong> stocké dans le navigateur pour indiquer un onglet actif.</li>
        </ul>
        <button id="closeHelpBtn" style="
            position:absolute; top:10px; right:10px; background:#ef4444; color:#fff; border:none; padding:5px 10px;
            border-radius:5px; cursor:pointer;">✖</button>
    </div>
</div>

<script>
// 🔹 Menu admin
<?php if ($isAdmin): ?>
const tabLock   = document.getElementById("tabLock");
const tabDelete = document.getElementById("tabDeleteChat");
const tabToggle = document.getElementById("tabToggleChat");
const tabUsers  = document.getElementById("tabUsers");
const sectionLocks  = document.getElementById("sectionLocks");
const sectionDelete = document.getElementById("sectionDeleteChat");
const sectionToggle = document.getElementById("sectionToggleChat");
const sectionUsers  = document.getElementById("sectionUsers");

function activate(tab) {
    [tabLock, tabDelete, tabToggle, tabUsers, tabLogs].forEach(b => b.classList.remove("active"));
    tab.classList.add("active");
}
tabLock.addEventListener("click", () => { activate(tabLock);  sectionLocks.style.display="block"; sectionDelete.style.display="none"; sectionToggle.style.display="none"; sectionUsers.style.display="none"; });
tabDelete.addEventListener("click", () => { activate(tabDelete); sectionLocks.style.display="none";  sectionDelete.style.display="block"; sectionToggle.style.display="none"; sectionUsers.style.display="none"; });
tabToggle.addEventListener("click", () => { activate(tabToggle); sectionLocks.style.display="none";  sectionDelete.style.display="none"; sectionToggle.style.display="block"; sectionUsers.style.display="none"; });
tabUsers.addEventListener("click", () => { activate(tabUsers);  sectionLocks.style.display="none";  sectionDelete.style.display="none"; sectionToggle.style.display="none"; sectionUsers.style.display="block"; });
<?php endif; ?>

// 🔹 Suppression des verrous
document.getElementById("releaseLockBtn").addEventListener("click", () => {
    const LOCK_KEY = "dessin_tab_lock";
    const resultSrv = document.getElementById("resultsrv");
    const resultLcl = document.getElementById("resultlcl");

    resultLcl.innerText = "";
    resultSrv.innerText = "";

    <?php if ($isAdmin): ?>
    fetch("release_lock.php", { method: "POST", credentials: "include" })
        .then(r => r.text())
        .then(txt => {
            resultSrv.innerText = "Résultat serveur : " + txt;
            // Efface le message après 5 secondes
            setTimeout(() => { resultSrv.innerText = ""; }, 5000);
        })
        .catch(err => { 
            resultSrv.innerText = "Erreur serveur : " + err;
            setTimeout(() => { resultSrv.innerText = ""; }, 5000);
        });
    <?php endif; ?>

    if (localStorage.getItem(LOCK_KEY)) {
        localStorage.removeItem(LOCK_KEY);
        resultLcl.innerText = "\nVerrou local supprimé.";
		setTimeout(() => { resultLcl.innerText = ""; }, 5000);
    } else {
        resultLcl.innerText = "\nAucun verrou local trouvé.";
		setTimeout(() => { resultLcl.innerText = ""; }, 5000);
    }
});

// 🔹 Suppression du chat (admin)
<?php if ($isAdmin): ?>
document.getElementById("deleteChatBtn").addEventListener("click", () => {
    if (!confirm("Voulez-vous vraiment supprimer tous les fichiers du chat ?")) return;
    document.getElementById("resultDeleteChat").innerText = '';

    fetch("delete_chat.php", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "csrf_token=" + encodeURIComponent(csrfToken)
    })
    .then(r => r.text())
    .then(txt => { document.getElementById("resultDeleteChat").innerText = txt; })
    .catch(err => { document.getElementById("resultDeleteChat").innerText = "Erreur : " + err; });
});

// 🔹 Activation/désactivation du chat
document.getElementById("toggleChatBtn").addEventListener("click", () => {
    fetch("toggle_chat.php", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "csrf_token=" + encodeURIComponent(csrfToken)
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById("chatState").innerText = data.state ? "❌ Désactivé" : "✅ Activé";
        document.getElementById("toggleChatBtn").innerText = data.state ? "Activer le chat" : "Désactiver le chat";
        document.getElementById("resultToggleChat").innerText = data.message;
        setTimeout(() => { document.getElementById("resultToggleChat").innerText = ""; }, 3000);
    })
    .catch(err => { document.getElementById("resultToggleChat").innerText = "Erreur : " + err; });
});
<?php endif; ?>

        setTimeout(function() {
            let feedback = document.getElementById("user-feedback");
            if (feedback) {
                feedback.style.transition = "opacity 0.5s ease";
                feedback.style.opacity = "0";
                setTimeout(() => feedback.remove(), 500); // suppression après fondu
            }
        }, 5000); // 5 secondes

// 🔹 Modale d'aide
const helpModal = document.getElementById("helpModal");
document.getElementById("helpBtn").addEventListener("click", () => { helpModal.style.display = "flex"; });
document.getElementById("closeHelpBtn").addEventListener("click", () => { helpModal.style.display = "none"; });

<?php if ($isAdmin): ?>
const tabLogs    = document.getElementById("tabLogs");
const logsModal  = document.getElementById("logsModal");
const logsContent = document.getElementById("logsContent");

tabLogs.addEventListener("click", () => {
    activate(tabLogs);
    sectionLocks.style.display="none";
    sectionDelete.style.display="none";
    sectionToggle.style.display="none";
    sectionUsers.style.display="none";

    // Affiche la modale
    logsModal.style.display = "flex";
    logsContent.innerHTML = "Chargement...";

    // Récupère le contenu du fichier logs/users.log
    fetch("get_logs.php")
        .then(r => r.text())
        .then(txt => {
            // Sépare les lignes et applique couleur
            const lines = txt.split("\n");
            logsContent.innerHTML = lines.map(l => {
                if (/failed/i.test(l)) return `<span style="color:red;">${l}</span>`;
                if (/success/i.test(l)) return `<span style="color:green;">${l}</span>`;
                return l;
            }).join("\n");
        })
        .catch(err => {
            logsContent.innerHTML = "Erreur : " + err;
        });
});

// Fermer la modale
document.getElementById("closeLogsBtn").addEventListener("click", () => { logsModal.style.display = "none"; });
<?php endif; ?>
</script>