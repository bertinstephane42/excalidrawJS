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
</style>

<h2>Visionneuse de dessin</h2>
<canvas id="c"></canvas>

<script src="https://cdn.jsdelivr.net/npm/fabric@5.2.4/dist/fabric.min.js"></script>
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
        const response = await fetch('get_drawing.php?_=' + Date.now());
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

</script>
