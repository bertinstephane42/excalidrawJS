<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }
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
</style>

<h2>Visionneuse de dessin en live</h2>
<canvas id="c"></canvas>

<script src="https://cdn.jsdelivr.net/npm/fabric@5.2.4/dist/fabric.min.js"></script>
<script>
const canvas = new fabric.Canvas('c', {
    selection: false,
    interactive: false,
    backgroundColor: '#ffffff',
    preserveObjectStacking: true
});

let lastJSON = null;        // Pour comparer les changements
let interval = 1000;        // Intervalle initial en ms
let inactivityTime = 0;     // Temps d'inactivité en ms
const baseInterval = 1000;  

// Fonction pour verrouiller tous les objets
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

// Fonction de chargement JSON adaptatif
async function loadJSON() {
    try {
        const response = await fetch('json/current.json?_=' + new Date().getTime());
        const json = await response.json();
        const jsonString = JSON.stringify(json);

        if (lastJSON !== jsonString) {
            // Changement détecté
            lastJSON = jsonString;
            inactivityTime = 0;
            interval = baseInterval;  

            canvas.loadFromJSON(json, () => {
                lockAllObjects();  // verrouillage immédiat
                canvas.renderAll();
                
                // Ajuster la taille du canvas selon le premier objet
                const first = canvas.getObjects()[0];
                if (first?.canvasWidth && first?.canvasHeight) {
                    canvas.setWidth(first.canvasWidth);
                    canvas.setHeight(first.canvasHeight);
                } else {
                    canvas.setWidth(1800);
                    canvas.setHeight(900);
                }
            });
        } else {
            // Pas de changement
            inactivityTime += interval;

            // Ajustement progressif de l'intervalle
            if (inactivityTime >= 60000) {       // après 1 minute
                interval = 6000;
            } else if (inactivityTime >= 30000) { // après 30 secondes
                interval = 4000;
            }
        }
    } catch (err) {
        console.error('Erreur lors du chargement du JSON:', err);
    } finally {
        setTimeout(loadJSON, interval);
    }
}

// Lancer le rafraîchissement adaptatif
loadJSON();
</script>