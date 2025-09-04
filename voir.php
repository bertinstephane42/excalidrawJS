<?php
session_start();
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

include __DIR__ . '/inc/header.php';

if (!isset($_SESSION['chat_token'])) {
    $_SESSION['chat_token'] = bin2hex(random_bytes(16));
}
$chat_token = $_SESSION['chat_token'];
$chatDisabled = file_exists(__DIR__ . '/chat/chat.lock');
?>

<style>
html, body { height:100%; margin:0; }
#c { border:1px solid #ccc; display:block; }
#headerBar { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; }
#chatToggle { background:#007bff; color:#fff; border:none; border-radius:50%; width:40px; height:40px; font-size:18px; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,0.3); }
#chatMessages { flex:1; padding:10px; overflow-y:auto; font-size:14px; background:#f9f9f9; }
#chatModal { display:none; position:fixed; bottom:20px; right:20px; width:300px; height:400px; background:#fff; border:1px solid #ccc; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.3); z-index:1000; flex-direction:column; }
#chatHeader { display:flex; justify-content:space-between; align-items:center; padding:4px 8px; border-bottom:1px solid #ccc; background:#f1f1f1; border-radius:10px 10px 0 0; }
#chatLogout { background:none; border:none; cursor:pointer; font-size:16px; color:#666; }
#chatLogout:hover { color:#d00; }
#chatForm { display:flex; border-top:1px solid #ccc; }
#chatForm input[type=text] { flex:1; border:none; padding:8px; font-size:14px; }
#chatForm button { background:#007bff; color:#fff; border:none; padding:8px 12px; cursor:pointer; }
#chatLogin { display:block; width:100%; box-sizing:border-box; border:none; border-bottom:1px solid #ccc; padding:8px; font-size:14px; }
</style>

<div id="headerBar">
    <h2>Visionneuse de dessin</h2>
    <span id="chatContainer">
        <?php if($chatDisabled): ?>
            <span title="Chat désactivé" style="font-size:1.4em; color:#d00;">💬🚫</span>
        <?php else: ?>
            <button id="chatToggle">💬</button>
        <?php endif; ?>
    </span>
</div>

<canvas id="c"></canvas>

<script src="https://cdn.jsdelivr.net/npm/fabric@5.2.4/dist/fabric.min.js"></script>
<script>
let fabricLoaded = false;
let canvas, lastMTime = 0;
let isFetching = false;
let refreshInterval = 2000; // intervalle initial

// Fonction principale pour charger et mettre à jour le dessin
async function loadJSON() {
    if (!fabricLoaded || isFetching) return;
    isFetching = true;

    try {
        const res = await fetch('get_drawing.php?_='+Date.now()+'&mtime='+lastMTime, {
            headers:{'X-Requested-With':'XMLHttpRequest'}
        });
        if(!res.ok) throw new Error('Erreur serveur');

        const result = await res.json();

        if (!result.unchanged) {
            // Mise à jour du dessin
            lastMTime = result.mtime;
            canvas.loadFromJSON(result.data, () => {
                lockAllObjects();
                canvas.renderAll();
                const first = canvas.getObjects()[0];
                canvas.setWidth(first?.canvasWidth||1800);
                canvas.setHeight(first?.canvasHeight||900);
            });
            refreshInterval = 2000; // reset interval normal
        } else {
            // Aucun changement → ralentir encore
            refreshInterval = 7000; // 7 secondes
        }
    } catch(e){
        console.error(e);
        // En cas d'erreur serveur, temporiser pour éviter répétition rapide
        refreshInterval = 10000; // 10 secondes
    } finally {
        isFetching = false;
        // Prochain appel après intervalle adaptatif
        setTimeout(loadJSON, refreshInterval);
    }
}

// Verrouillage des objets
function lockAllObjects() {
    canvas.getObjects().forEach(obj=>{
        obj.selectable = obj.evented = obj.hasControls = false;
        obj.lockMovementX = obj.lockMovementY = obj.lockScalingX = obj.lockScalingY = obj.lockRotation = true;
    });
}

// Initialisation de Fabric
function initFabric() {
    if (canvas) return;
    canvas = new fabric.Canvas('c', { selection:false, interactive:false, backgroundColor:'#fff', preserveObjectStacking:true });
    fabricLoaded = true;
    // Démarrer la boucle après init
    loadJSON();
}

// Chargement de Fabric si nécessaire
if (typeof fabric === "undefined") {
    const script = document.createElement('script');
    script.src = 'js/fabric.min.js';
    script.onload = initFabric;
    document.head.appendChild(script);
} else {
    initFabric();
}
</script>

<script>
const authToken = '<?= $chat_token ?>';
let chatDisabled = <?= $chatDisabled ? 'true' : 'false' ?>;
let connected = false;

let chatModal, chatLogin, chatMessages, chatForm, chatInput, chatLogout, chatToggle;

// --- Création de la modale chat ---
function createChatModal() {
    if (document.getElementById('chatModal')) return;

    chatModal = document.createElement('div');
    chatModal.id = 'chatModal';
    chatModal.style.display = 'none';
    chatModal.style.flexDirection = 'column';
    chatModal.style.position = 'fixed';
    chatModal.style.bottom = '20px';
    chatModal.style.right = '20px';
    chatModal.style.width = '300px';
    chatModal.style.height = '400px';
    chatModal.style.background = '#fff';
    chatModal.style.border = '1px solid #ccc';
    chatModal.style.borderRadius = '10px';
    chatModal.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
    chatModal.style.zIndex = '1000';
    chatModal.style.display = 'flex';
    chatModal.style.flexDirection = 'column';

    chatModal.innerHTML = `
        <div id="chatHeader" style="display:flex; justify-content:space-between; align-items:center; padding:4px 8px; border-bottom:1px solid #ccc; background:#f1f1f1; border-radius:10px 10px 0 0;">
            <span>💬 Chat</span>
            <button id="chatLogout" title="Déconnexion">❌</button>
        </div>
        <input type="text" id="chatLogin" placeholder="Votre login" style="width:100%; box-sizing:border-box; border:none; border-bottom:1px solid #ccc; padding:8px; font-size:14px;">
        <div id="chatMessages" style="flex:1; overflow-y:auto; padding:5px; font-size:14px; background:#f9f9f9;"></div>
        <form id="chatForm" style="display:flex; border-top:1px solid #ccc;">
            <input type="text" id="chatInput" placeholder="Votre message..." style="flex:1; padding:5px;">
            <button type="submit">➤</button>
        </form>
    `;
    document.body.appendChild(chatModal);

    chatLogin = document.getElementById('chatLogin');
    chatMessages = document.getElementById('chatMessages');
    chatForm = document.getElementById('chatForm');
    chatInput = document.getElementById('chatInput');
    chatLogout = document.getElementById('chatLogout');
}

// --- Attache les événements ---
function attachChatEvents() {
    // Toggle du chat
    chatToggle = document.getElementById('chatToggle');
    if (chatToggle && !chatToggle.dataset.bound) {
        chatToggle.onclick = () => {
            chatModal.style.display = (chatModal.style.display === 'flex') ? 'none' : 'flex';
        };
        chatToggle.dataset.bound = "true"; // évite double binding
    }

    // Déconnexion
    if (chatLogout && !chatLogout.dataset.bound) {
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
        chatLogout.dataset.bound = "true";
    }

    // Envoi de message / login
    if (chatForm && !chatForm.dataset.bound) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const login = chatLogin.value.trim();
            const msg = chatInput.value.trim();
            if (!login) { alert("Veuillez entrer un login."); return; }
            if (!msg) return;
            if (chatDisabled) { 
                alert("Chat désactivé par l'administrateur"); 
                chatInput.value = ''; 
                return; 
            }

            // Login si pas encore connecté
            if (!connected) {
                const res = await fetch('chat_backend.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Auth-Token': authToken
                    },
                    body: new URLSearchParams({ login })
                });
                const data = await res.json();
                if (!data.ok) { 
                    alert(data.error || "Impossible de se connecter"); 
                    return; 
                }
                localStorage.setItem('chatLogin', login);
                chatLogin.disabled = true;
                connected = true;
            }

            // Envoi du message
            await fetch('chat_backend.php?action=send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Auth-Token': authToken
                },
                body: new URLSearchParams({ login, message: msg })
            });
            chatInput.value = '';
        });
        chatForm.dataset.bound = "true";
    }
}

// --- Récupère les messages ---
async function loadMessages() {
    if (!connected || chatDisabled) return;
    try {
        const res = await fetch('chat_backend.php?action=list', { headers: { 'X-Auth-Token': authToken } });
        if (!res.ok) throw new Error(res.statusText);
        const data = await res.json();
        chatMessages.innerHTML = '';
        data.forEach(m => {
            const d = document.createElement('div');
            d.textContent = `[${m.time}] ${m.login}: ${m.message}`;
            chatMessages.appendChild(d);
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch (e) {
        console.error("Erreur loadMessages", e);
    }
}

// --- Vérifie le statut du chat ---
async function updateChatStatus() {
    try {
        const res = await fetch('chat_backend.php?action=status', { headers: { 'X-Auth-Token': authToken } });
        const data = await res.json();
        const prevDisabled = chatDisabled;
        chatDisabled = data.disabled;
        const container = document.getElementById('chatContainer');

        if (chatDisabled) {
            container.innerHTML = `<span title="Chat désactivé" style="font-size:1.4em; color:#d00;">💬🚫</span>`;
            chatModal && (chatModal.style.display = 'none');
        } else if (prevDisabled && !chatDisabled) {
            container.innerHTML = `<button id="chatToggle">💬</button>`;
            attachChatEvents(); // ré-attache le toggle
        }
    } catch (e) { console.error("Erreur updateChatStatus", e); }
}

let chatStatusInterval = null;
let chatMessagesInterval = null;

function initChat() {
    if (window.chatInitialized) return; // évite les multiples inits
    window.chatInitialized = true;

    createChatModal();
    attachChatEvents();

    const savedLogin = localStorage.getItem('chatLogin');
    if (savedLogin && !chatDisabled) {
        chatLogin.value = savedLogin;
        chatLogin.disabled = true;
        connected = true;
        loadMessages();
    }

    // Lancer les intervalles une seule fois
    chatStatusInterval = setInterval(updateChatStatus, 2000);
    chatMessagesInterval = setInterval(() => { if (connected) loadMessages(); }, 2000);
}
// --- Démarrage si chat activé ---
initChat();
</script>