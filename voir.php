<?php
session_start(); // Toujours au tout début
require_once __DIR__ . '/inc/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/inc/header.php';

// Générer le token seulement s'il n'existe pas
if (!isset($_SESSION['chat_token'])) {
    $_SESSION['chat_token'] = bin2hex(random_bytes(16));
}
$chat_token = $_SESSION['chat_token'];
$lockFile = __DIR__ . '/chat/chat.lock';
$chatDisabled = file_exists($lockFile);
?>

<style>
html, body { height: 100%; margin: 0; }
#c { border: 1px solid #ccc; display: block; }
#headerBar { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; }
#chatToggle { background: #007bff; color: #fff; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 18px; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
#chatModal { display: none; position: fixed; bottom: 20px; right: 20px; width: 300px; height: 400px; background: #fff; border: 1px solid #ccc; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); z-index: 1000; flex-direction: column; }
#chatHeader { display:flex; justify-content:space-between; align-items:center; padding:4px 8px; border-bottom:1px solid #ccc; background:#f1f1f1; border-radius: 10px 10px 0 0; }
#chatLogout { background:none; border:none; cursor:pointer; font-size:16px; color:#666; }
#chatLogout:hover { color:#d00; }
#chatMessages { flex: 1; padding: 10px; overflow-y: auto; font-size: 14px; background: #f9f9f9; }
#chatForm { display: flex; border-top: 1px solid #ccc; }
#chatForm input[type="text"] { flex: 1; border: none; padding: 8px; font-size: 14px; }
#chatForm button { background: #007bff; color: #fff; border: none; padding: 8px 12px; cursor: pointer; }
#chatLogin { display: block; width: 100%; box-sizing: border-box; border: none; border-bottom: 1px solid #ccc; padding: 8px; font-size: 14px; }
</style>

<div id="headerBar">
    <h2>Visionneuse de dessin</h2>
    <?php if (!$chatDisabled): ?>
        <button id="chatToggle">💬</button>
    <?php else: ?>
      <span title="Chat désactivé" style="font-size:1.4em; color:#d00;">
        💬🚫
	  </span>
    <?php endif; ?>
</div>

<canvas id="c"></canvas>

<!-- Modale de chat -->
<?php if (!$chatDisabled): ?>
	<div id="chatModal">
		<div id="chatHeader">
			<span>💬 Chat</span>
			<button id="chatLogout" title="Déconnexion">❌</button>
		</div>
		<input type="text" id="chatLogin" placeholder="Votre login">
		<div id="chatMessages"></div>
		<form id="chatForm">
			<input type="text" id="chatInput" placeholder="Votre message...">
			<button type="submit">➤</button>
		</form>
	</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/fabric@5.2.4/dist/fabric.min.js"></script>
<script>
<!-- Fallback local si le CDN échoue -->
  if (typeof fabric === "undefined") {
    var script = document.createElement("script");
    script.src = "js/fabric.min.js"; // copie locale
    document.head.appendChild(script);
  }
</script>

<script>
const authToken = '<?= $chat_token ?>'; // Token généré côté PHP
const chatDisabled = <?= $chatDisabled ? 'true' : 'false' ?>;
</script>

<script>
const canvas = new fabric.Canvas('c', {
    selection: false,
    interactive: false,   // interdit toute interaction
    backgroundColor: '#ffffff',
    preserveObjectStacking: true
});

// verrouillage total des objets
function lockAllObjects() {
    canvas.getObjects().forEach(obj => {
        obj.selectable = false;
        obj.evented = false;
        obj.hasControls = false;
        obj.lockMovementX = true;
        obj.lockMovementY = true;
        obj.lockScalingX = true;
        obj.lockScalingY = true;
        obj.lockRotation = true;
    });
}

// charger le dessin depuis PHP (lecture seule)
async function loadJSON() {
    try {
        const response = await fetch('get_drawing.php?_=' + Date.now(), {
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		});
        if (!response.ok) throw new Error("Erreur serveur");

        const json = await response.json();

        canvas.loadFromJSON(json, () => {
            lockAllObjects();
            canvas.renderAll();

            // ajuster la taille
            const first = canvas.getObjects()[0];
            if (first?.canvasWidth && first?.canvasHeight) {
                canvas.setWidth(first.canvasWidth);
                canvas.setHeight(first.canvasHeight);
            } else {
                canvas.setWidth(1800);
                canvas.setHeight(900);
            }
        });
    } catch (err) {
        console.error("Impossible de charger le dessin :", err);
    } finally {
        setTimeout(loadJSON, 2000); // rafraîchit toutes les 5s
    }
}
// démarrage
loadJSON();

// --- JS du chat simplifié et fonctionnel

const chatToggle = document.getElementById('chatToggle');
const chatModal = document.getElementById('chatModal');
const chatLogin = document.getElementById('chatLogin');
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');
const chatLogout = document.getElementById('chatLogout');

let connected = false;

// --- toggle modal
chatToggle.addEventListener('click', () => {
    chatModal.style.display = (chatModal.style.display === 'flex') ? 'none' : 'flex';
});

// --- logout
chatLogout.addEventListener('click', async () => {
    if (!connected) return;
    if (!confirm("Déconnexion du chat ?")) return;

    const login = chatLogin.value.trim();
    await fetch('chat_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'logout', login })
    });

    localStorage.removeItem('chatLogin');
    chatLogin.value = '';
    chatLogin.disabled = false;
    chatMessages.innerHTML = '';
    chatInput.value = '';
    connected = false;
});

// --- soumission message
chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const login = chatLogin.value.trim();
    const msg = chatInput.value.trim();
    if (!login) { alert("Veuillez entrer un login."); return; }
    if (!msg) return;
	
	// Vérifier si le chat a été désactivé dynamiquement
    if (chatDisabled) {
        alert("Chat désactivé par l'administrateur"); // même message que pour login
        chatInput.value = '';
        return;
    }

    // --- connexion si nécessaire
    if (!connected) {
        try {
            const res = await fetch('chat_backend.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Auth-Token': authToken // <-- Header sécurisé
                },
                body: new URLSearchParams({ login })
            });

            const data = await res.json();
            if (!data.ok) {
                alert(data.error || "Impossible de se connecter");
                return;
            }

            // Stocker le login et verrouiller le champ
            localStorage.setItem('chatLogin', login);
            chatLogin.disabled = true;
            connected = true;

        } catch (err) {
            console.error("Erreur de connexion :", err);
            alert("Erreur de connexion. Réessayez.");
            return;
        }
    }

    // --- envoi message
    try {
        await fetch('chat_backend.php?action=send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Auth-Token': authToken // <-- Header sécurisé
            },
            body: new URLSearchParams({ login, message: msg })
        });

        chatInput.value = '';
        loadMessages();

    } catch (err) {
        console.error("Erreur lors de l'envoi du message :", err);
    }
});

// --- chargement messages
async function loadMessages() {
    try {
        const res = await fetch('chat_backend.php?action=list', {
            method: 'GET',
            headers: {
                'X-Auth-Token': authToken // <-- Header sécurisé
            }
        });

        if (!res.ok) {
            console.error("Erreur lors de la récupération des messages :", res.statusText);
            return;
        }

        const data = await res.json();
        chatMessages.innerHTML = '';

        data.forEach(m => {
            const div = document.createElement('div');
            div.textContent = `[${m.time}] ${m.login}: ${m.message}`;
            chatMessages.appendChild(div);
        });

        chatMessages.scrollTop = chatMessages.scrollHeight;

    } catch(err) {
        console.error("Erreur dans loadMessages :", err);
    }
}

setInterval(loadMessages, 2000);
loadMessages();
</script>