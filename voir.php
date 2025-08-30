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
let interval = 2000;        // Intervalle initial en ms
let inactivityTime = 0;     // Temps d'inactivité en ms
const baseInterval = 2000;  

async function loadJSON() {
    try {
        const response = await fetch('json/current.json?_=' + new Date().getTime());
        const json = await response.json();
        const jsonString = JSON.stringify(json);

        if (lastJSON !== jsonString) {
            // Si changement détecté
            lastJSON = jsonString;
            inactivityTime = 0;
            interval = baseInterval;  // Revenir à 2s
            canvas.loadFromJSON(json, () => {
                canvas.renderAll();
                canvas.setWidth(canvas.getObjects()[0]?.canvasWidth || 1800);
                canvas.setHeight(canvas.getObjects()[0]?.canvasHeight || 900);
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
        // Planifier le prochain rafraîchissement
        setTimeout(loadJSON, interval);
    }
}

// Lancer le rafraîchissement adaptatif
loadJSON();
</script>
