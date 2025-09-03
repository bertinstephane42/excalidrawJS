<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { 
    header('Location: login.php'); 
    exit; 
}
include __DIR__ . '/inc/header.php';
?>

<style>
html, body {
    height: 100%;
    margin: 0;
}
#c {
    border: 1px solid #ccc;
    display: block;
}
/* Conteneur du titre + bouton */
#headerBar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
}

/* Bouton chat */
#chatToggle {
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

/* Modale flottante */
#chatModal {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 300px;
    height: 400px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    z-index: 1000;
    flex-direction: column;
}

/* En-tête du chat */
#chatHeader {
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:4px 8px; 
    border-bottom:1px solid #ccc; 
    background:#f1f1f1;
    border-radius: 10px 10px 0 0;
}
#chatLogout {
    background:none; 
    border:none; 
    cursor:pointer; 
    font-size:16px;
    color:#666;
}
#chatLogout:hover {
    color:#d00;
}

/* Zone messages */
#chatMessages {
    flex: 1;
    padding: 10px;
    overflow-y: auto;
    font-size: 14px;
    background: #f9f9f9;
}

/* Ligne d’envoi */
#chatForm {
    display: flex;
    border-top: 1px solid #ccc;
}
#chatForm input[type="text"] {
    flex: 1;
    border: none;
    padding: 8px;
    font-size: 14px;
}
#chatForm button {
    background: #007bff;
    color: #fff;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
}
#chatLogin {
    display: block;
    width: 100%;       
    box-sizing: border-box; 
    border: none;
    border-bottom: 1px solid #ccc;
    padding: 8px;      
    font-size: 14px;
}
</style>

<div id="headerBar">
  <h2>Visionneuse de dessin</h2>
  <button id="chatToggle">💬</button>
</div>
<canvas id="c"></canvas>

<!-- Modale de chat -->
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
const canvas = new fabric.Canvas('c', {
    selection: false,
    interactive: false,
    backgroundColor: '#ffffff',
    preserveObjectStacking: true
});

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
        setTimeout(loadJSON, 2000); 
    }
}

// --- DOM references
const chatToggle = document.getElementById('chatToggle');
const chatModal = document.getElementById('chatModal');
const chatLogin = document.getElementById('chatLogin');
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');
const chatLogout = document.getElementById('chatLogout');

let connected = false;

// --- toggle chat modal
chatToggle.addEventListener('click', () => {
    chatModal.style.display = (chatModal.style.display === 'flex') ? 'none' : 'flex';
});

// --- logout avec confirmation
chatLogout.addEventListener('click', async () => {
    if (!connected) return;
    if (confirm("Voulez-vous vraiment vous déconnecter du chat ?")) {
        const login = chatLogin.value.trim();
        await fetch('chat_backend.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'logout', login })
        });
        localStorage.removeItem('chatLogin');
        chatLogin.value = '';
        chatLogin.disabled = false;
        chatMessages.innerHTML = '';
        chatInput.value = '';
        connected = false;
        alert("Vous êtes maintenant déconnecté. Entrez un nouveau login pour vous reconnecter.");
    }
});

// --- soumission message
chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const login = chatLogin.value.trim();
    const msg = chatInput.value.trim();
    if (!login) { alert("Veuillez entrer un login."); return; }
    if (!msg) return;

    if (!connected) {
        // vérifier login unique
        const res = await fetch('chat_backend.php?action=checkLogin&login=' + encodeURIComponent(login));
        const data = await res.json();
        if (!data.ok) {
            alert("Ce login est déjà utilisé. Choisissez-en un autre.");
            chatLogin.value = '';
            return;
        }
        localStorage.setItem('chatLogin', login);
        chatLogin.disabled = true;
        connected = true;
    }

    await fetch('chat_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'send', login, message: msg })
    });
    chatInput.value = '';
    loadMessages();
});

// --- chargement messages
async function loadMessages() {
    try {
        const res = await fetch('chat_backend.php?action=list');
        const data = await res.json();
        chatMessages.innerHTML = '';
        data.forEach(m => {
            const div = document.createElement('div');
            div.textContent = `[${m.time}] ${m.login}: ${m.message}`;
            chatMessages.appendChild(div);
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch(err) {
        console.error('Erreur chargement chat', err);
    }
}

// Rafraîchissement automatique
setInterval(loadMessages, 2000);
loadMessages();
loadJSON();
</script>