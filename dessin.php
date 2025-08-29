<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }
include __DIR__ . '/inc/header.php';
?>

<style>
  /* Mise en page simple, responsive, compatible mutualisé */
  html, body {
	  height: 100%;
	  margin: 0;
	}
	.editor-wrap {
	  display: flex;
	  flex-direction: column;
	  height: 100vh;   /* occupe tout l'écran */
	}

	.canvas-wrap {
	  display: block;
	  width: 100%;
	  height: 100%;
	  overflow: auto; /* Permet le scroll si le canvas est plus grand */
	}

	.status {
	  margin-top: .5rem;
	  display: flex;
	  justify-content: space-between; /* outil à gauche, dimensions à droite */
	  font-size: .9rem;
	  color: #444;
	}

	#c {
	  display: block;
	  /* supprimer width/height fixes */
	}
  h2 {
    margin: 0 0 1rem 0;
    color: #0f172a;
  }

  .toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
    box-shadow: 0 1px 2px rgba(0,0,0,.05);
  }
  .toolbar .group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    padding-right: 0.5rem;
    border-right: 1px solid #e2e8f0;
  }
  .toolbar .group:last-child {
    border-right: none;
  }

  button.tool {
    border: 1px solid #cbd5e1;
    background: white;
    border-radius: 0.5rem;
    padding: 0.4rem 0.6rem;
    cursor: pointer;
  }
  button.tool.active {
    outline: 2px solid #2563eb;
  }

  .color-input {
    width: 36px;
    height: 36px;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0;
  }

  .range {
    vertical-align: middle;
  }

  .hidden {
    display: none;
  }

  #filenameInput {
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  padding: 0.3rem 0.5rem;
  min-width: 150px;
  font-size: 0.9rem;
}
/* Import JSON bouton + input */
#btnImportJSON {
  border: 1px solid #cbd5e1;
  background: white;
  border-radius: 0.5rem;
  padding: 0.4rem 0.6rem;
  cursor: pointer;
  font-size: 0.9rem;
}

#btnImportJSON:hover {
  background: #f1f5f9;
}

#importJSON {
  display: none; /* toujours caché, utilisé uniquement par JS */
}

/* Ajuste l'alignement dans la toolbar */
.toolbar .group input[type="file"] {
  display: none; /* s'assure qu'il reste invisible */
}

.toolbar .group button#btnImportJSON {
  margin-left: 0.3rem;
}

/* Modale aide */
.modal {
  display: none; 
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.6);
}

.modal-content {
  background: #f8fafc;
  margin: 5% auto;          /* réduit un peu la marge pour plus d'espace */
  padding: 1rem 2rem;
  border-radius: 1rem;
  width: 90%;
  max-width: 500px;
  max-height: 80%;           /* limite la hauteur à 80% de l'écran */
  overflow-y: auto;          /* active l'ascenseur vertical si besoin */
  color: #0f172a;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.close {
  float: right;
  font-size: 1.5rem;
  cursor: pointer;
}

.close:hover {
  color: red;
}

</style>

<div class="editor-wrap">
  <h2>Éditeur de dessin</h2>
  <div class="toolbar" id="toolbar">
    <!-- Outils -->
    <div class="group" aria-label="Outils">
      <button class="tool" data-tool="select" title="Sélection (V)">Sélection</button>
      <button class="tool" data-tool="rect" title="Rectangle (R)">▭</button>
	  <button class="tool" data-tool="diamond" title="Losange (D)">◇</button>
      <button class="tool" data-tool="ellipse" title="Ellipse (O)">◯</button>
      <button class="tool" data-tool="line" title="Ligne (L)">／</button>
      <button class="tool" data-tool="arrow" title="Flèche (A)">➤</button>
      <select id="arrowType" title="Type de flèche">
        <option value="straight">Droite</option>
        <option value="curved">Courbée</option>
        <option value="elbow">Coudée</option>
      </select>
      <button class="tool" data-tool="freedraw" title="Dessin libre (P)">✎</button>
      <button class="tool" data-tool="text" title="Texte (T)">T</button>
    </div>

    <!-- Style -->
    <div class="group" aria-label="Style">
      <label>Trait <input id="strokeColor" type="color" class="color-input" value="#1f2937"></label>
      <label>Rempl. <input id="fillColor" type="color" class="color-input" value="#ffffff"></label>
      <label>Épaisseur <input id="strokeWidth" type="range" class="range" min="1" max="20" value="2"></label>
      <label><input type="checkbox" id="noFill"> Sans remplissage</label>
	  <label>Police 
		  <select id="fontFamily">
			<option value="Arial" selected>Arial</option>
			<option value="Helvetica">Helvetica</option>
			<option value="Times New Roman">Times New Roman</option>
			<option value="Courier New">Courier New</option>
			<option value="Verdana">Verdana</option>
			<option value="Georgia">Georgia</option>
			<option value="Comic Sans MS">Comic Sans MS</option>
		  </select>
	</label>

    </div>

    <!-- Calques -->
    <div class="group" aria-label="Calques">
      <button class="tool" id="bringFront" title="Amener au premier plan">⬆︎</button>
      <button class="tool" id="sendBack" title="Renvoyer à l'arrière">⬇︎</button>
      <button class="tool" id="duplicate" title="Dupliquer (Ctrl+D)">⎘</button>
      <button class="tool" id="delete" title="Supprimer (Suppr)">🗑️</button>
    </div>

    <!-- Historique -->
    <div class="group" aria-label="Historique">
      <button class="tool" id="undo" title="Annuler (Ctrl+Z)">↶</button>
      <button class="tool" id="redo" title="Rétablir (Ctrl+Y / Ctrl+Shift+Z)">↷</button>
    </div>
	
	<button id="helpBtn" title="Aide">❓ Aide</button>

    <!-- Export -->
 	<div class="group" aria-label="Export">
	  <button class="tool" id="exportPNG">Exporter PNG</button>
	  <button class="tool" id="exportSVG">Exporter SVG</button>
	  <button class="tool" id="exportJSON">Exporter JSON</button> <!-- Nouveau -->
	  <input type="text" id="filenameInput" placeholder="Nom du dessin" />
	  <input type="file" id="importJSON" accept=".json" style="display:none;">
		<button id="btnImportJSON">Importer JSON</button>
		<button class="tool" id="resetCanvas">Réinitialiser</button>
	</div>
  </div>

  <div class="canvas-wrap">
    <canvas id="c"></canvas>
  </div>
  <div class="status">
    <span id="statusTool">Outil : Sélection</span>
    <span id="statusDim">0 × 0</span>
  </div>
 <div id="helpModal" class="modal">
  <div class="modal-content">
    <span id="closeHelp" class="close">&times;</span>
    <h2>Guide des outils</h2>
    <ul>
      <li><b>🖱️ Sélection :</b> déplacer, redimensionner et supprimer les objets.</li>
      <li><b>⬛ Rectangle :</b> dessiner un rectangle sur la grille.</li>
      <li><b>⚪ Ellipse :</b> dessiner une ellipse.</li>
      <li><b>🔷 Losange :</b> dessiner un losange (forme en diamant).</li>
      <li><b>🔤 Texte :</b> insérer du texte éditable.</li>
      <li><b>✏️ Crayon :</b> dessiner à main levée.</li>
      <li><b>➔ Flèche :</b> dessiner une flèche droite, courbée ou en angle.</li>
      <li><b>📋 Dupliquer :</b> copier un objet sélectionné.</li>
      <li><b>⏫ Amener au premier plan :</b> placer l’objet sélectionné au-dessus des autres.</li>
      <li><b>⏬ Renvoyer à l’arrière :</b> placer l’objet sélectionné derrière les autres.</li>
      <li><b>↺ / ↻ Undo / Redo :</b> annuler ou rétablir une action.</li>
      <li><b>⬆ Export :</b> sauvegarder en PNG / JSON.</li>
      <li><b>⬇ Import :</b> recharger un dessin JSON sauvegardé.</li>
    </ul>

    <h3>⚡ Raccourcis & touches spéciales</h3>
    <ul>
      <li><b>SHIFT + Molette de souris :</b> zoom avant/arrière.</li>
      <li><b>SHIFT + redimensionnement :</b> conserver les proportions d’un objet.</li>
      <li><b>CTRL + C / CTRL + V :</b> copier-coller un objet sélectionné.</li>
      <li><b>DEL / SUPPR :</b> supprimer l’objet sélectionné.</li>
      <li><b>CTRL + Z :</b> annuler la dernière action.</li>
      <li><b>CTRL + Y :</b> rétablir une action annulée.</li>
      <li><b>Flèches clavier :</b> déplacer finement l’objet sélectionné (par pas de 1px).</li>
      <li><b>SHIFT + Flèches :</b> déplacement rapide (par pas de 10px).</li>
	  <li><b>Export :</b> sauvegarder en PNG / SVG / JSON.</li>
	  <li><b>ALT + lettre :</b> changer rapidement d’outil (ALT+R Rectangle, ALT+C Cercle, ALT+L Ligne, ALT+T Texte, ALT+S Sélection, ALT+P Pinceau).</li>
    </ul>

    <p style="margin-top:10px; font-size:0.9em; color:#666;">
      Astuce : combine les outils et raccourcis pour gagner du temps (ex. ALT pour te déplacer pendant que tu ajoutes plusieurs formes).
    </p>
  </div>
</div>
</div>

<!-- Fabric.js depuis CDN (compatible mutualisé, pas de Node) -->
<script src="https://cdn.jsdelivr.net/npm/fabric@5.2.4/dist/fabric.min.js"></script>
<script>
   const currentRole = <?php echo json_encode($_SESSION['role'] ?? 'etudiant'); ?>;
(function(){
  const canvasEl = document.getElementById('c');
  const canvas = new fabric.Canvas('c', {
    selection: true,
    backgroundColor: '#ffffff',
    preserveObjectStacking: true
  });

  // --- Variables globales du canvas ---
  const grid = 20;
  const CANVAS_MIN_WIDTH = 1800;
  const CANVAS_MIN_HEIGHT = 900;
  let gridLines = [];
    // -- Redimensionnement fiable de .canvas-wrap
  const wrap = canvasEl.parentElement; // .canvas-wrap

	function calculateOptimalSize() {
	  const containerRect = wrap.getBoundingClientRect();
	  const width = Math.max(CANVAS_MIN_WIDTH, containerRect.width);
	  const height = Math.max(CANVAS_MIN_HEIGHT, containerRect.height);
	  return { width, height };
	}

	let isDrawingGrid = false;

	function drawGrid() {
	  // Supprimer les anciennes lignes de grille
	  gridLines.forEach(line => canvas.remove(line));
	  gridLines = [];

	  const width = Math.floor(canvas.getWidth());
	  const height = Math.floor(canvas.getHeight());

	  const cols = Math.floor(width / grid);
	  const rows = Math.floor(height / grid);

	  // Colonnes
	  for (let i = 0; i <= cols; i++) {
		const x = i * grid;
		const line = new fabric.Line([x, 0, x, height], {
		  stroke: '#ccc',
		  selectable: false,
		  evented: false,
		  excludeFromExport: true
		});
		canvas.add(line);
		gridLines.push(line);
	  }

	  // Lignes
	  for (let i = 0; i <= rows; i++) {
		const y = i * grid;
		const line = new fabric.Line([0, y, width, y], {
		  stroke: '#ccc',
		  selectable: false,
		  evented: false,
		  excludeFromExport: true
		});
		canvas.add(line);
		gridLines.push(line);
	  }

	  // Toujours renvoyer la grille en arrière-plan
	  gridLines.forEach(line => line.sendToBack());
	}

	function saveState(){
	  if (isRestoring || isDrawingGrid) return;
	  undoStack.push(JSON.stringify(canvas.toJSON(['selectable'])));
	  if(undoStack.length > MAX_HISTORY) undoStack.shift();
	  redoStack.length = 0;
	}

  function applySize(w, h) {
    canvas.setDimensions({ width: w, height: h }); // width/height internes du <canvas>
    drawGrid();
    canvas.renderAll();
    const sd = document.getElementById('statusDim');
    if (sd) sd.textContent = Math.round(w) + ' × ' + Math.round(h);
  }

  // 1) Observe tous les changements de taille (flex, barres qui wrap, etc.)
  const ro = new ResizeObserver(() => {
	  const { width, height } = calculateOptimalSize();
	  applySize(width, height);
	});
	ro.observe(wrap);
// -- Initialisation au chargement
const { width, height } = calculateOptimalSize();
applySize(width, height);
  
  let currentTool = 'select';
  let isDrawingShape = false;
  let isDrawingArrow = false;
  let startPoint = null;
  let tempObj = null;
  let isPanning = false;
  let lastPosX = 0;
  let lastPosY = 0;
  let typeFleche = 'straight';
  let isEditingText = false;
  
	const strokeColor = document.getElementById('strokeColor');
	const fillColor = document.getElementById('fillColor');
	const strokeWidth = document.getElementById('strokeWidth');
	const noFill = document.getElementById('noFill');
	const fontFamily = document.getElementById('fontFamily'); // <-- ajouté
  
  // Ces listeners doivent être définis une fois, au démarrage
	canvas.on('text:editing:entered', () => { 
		isEditingText = true; 
	});

	canvas.on('text:editing:exited', () => { 
		isEditingText = false; 
	});
  
  document.getElementById('exportJSON').addEventListener('click', () => {
	  // Sérialisation du canvas en JSON
	  const json = canvas.toJSON();

	  // Conversion en chaîne
	  const jsonStr = JSON.stringify(json, null, 2);

	  // Création d'un fichier téléchargeable
	  const blob = new Blob([jsonStr], {type: "application/json"});
	  const url = URL.createObjectURL(blob);

	  const a = document.createElement('a');
	  a.href = url;
	  const now = new Date();
	  const filename = `dessin_${now.getFullYear()}-${now.getMonth()+1}-${now.getDate()}.json`;
	  a.download = filename;
	  document.body.appendChild(a);
	  a.click();
	  document.body.removeChild(a);
	  URL.revokeObjectURL(url);
	});

	// Appliquer la police lors de la création d'un texte
	function getTextProps() {
		return {
			fontFamily: fontFamily.value,
			fill: strokeColor.value,
			fontSize: 20
		};
	}

	// --- Zoom avec la molette ---
	canvas.on('mouse:down', function(opt) {
		const evt = opt.e;
		const p = canvas.getPointer(evt);

		// --- Panning ALT ---
		if (evt.altKey) {
			isPanning = true;
			this.selection = false;
			lastPosX = evt.clientX;
			lastPosY = evt.clientY;
			return;
		}

		// --- Éditer un texte existant ---
		const active = canvas.getActiveObject();
		if (active && (active.type === 'i-text' || active.type === 'textbox')) {
			isEditingText = true;
			active.enterEditing();
			active.selectAll();
			return;
		}

		startPoint = p;

		// --- Outil texte ---
		if (currentTool === 'text') {
			const it = new fabric.IText('Texte', {
				left: p.x,
				top: p.y,
				...getTextProps()
			});
			canvas.add(it).setActiveObject(it);
			it.enterEditing();
			it.selectAll();
			isEditingText = true;
			tempObj = null;
			return;
		}

		// --- Formes simples ou flèches / losange ---
		isDrawingShape = ['rect','ellipse','line','arrow','diamond'].includes(currentTool);
		if (!isDrawingShape) return;

		const props = getCommonProps();

		if (currentTool === 'rect') {
			tempObj = new fabric.Rect({
				left: p.x, top: p.y, width: 1, height: 1, ...props
			});
		} else if (currentTool === 'ellipse') {
			tempObj = new fabric.Ellipse({
				left: p.x, top: p.y, rx: 0.5, ry: 0.5,
				originX: 'left', originY: 'top',
				...props
			});
		} else if (currentTool === 'line') {
			tempObj = new fabric.Line([p.x, p.y, p.x, p.y], {
				...props, fill: undefined
			});
		} else if (currentTool === 'arrow') {
			isDrawingArrow = true; // création finale dans mouse:up
			tempObj = null;        // juste pour flag
		} else if (currentTool === 'diamond') {
			const points = [
				{ x: 0, y: -50 },  // haut
				{ x: 50, y: 0 },   // droite
				{ x: 0, y: 50 },   // bas
				{ x: -50, y: 0 }   // gauche
			];
			tempObj = new fabric.Polygon(points, {
				left: p.x, top: p.y,
				originX: 'center', originY: 'center',
				...props
			});
		}

		if (tempObj) canvas.add(tempObj);
	});

	// --- MOUSE MOVE ---
	canvas.on('mouse:move', function(opt) {
		const evt = opt.e;
		const p = canvas.getPointer(evt);

		// --- Panning ---
		if (isPanning) {
			const vpt = this.viewportTransform;
			vpt[4] += evt.clientX - lastPosX;
			vpt[5] += evt.clientY - lastPosY;
			this.requestRenderAll();
			lastPosX = evt.clientX;
			lastPosY = evt.clientY;
			return;
		}

		if (!isDrawingShape || !startPoint) return;

		// --- Aperçu des formes ---
		if (tempObj && ['rect','ellipse','line'].includes(currentTool)) {
			if (currentTool === 'rect') {
				const w = p.x - startPoint.x;
				const h = p.y - startPoint.y;
				tempObj.set({
					width: Math.abs(w),
					height: Math.abs(h),
					left: w < 0 ? p.x : startPoint.x,
					top: h < 0 ? p.y : startPoint.y
				});
			} else if (currentTool === 'ellipse') {
				const rx = Math.abs(p.x - startPoint.x)/2;
				const ry = Math.abs(p.y - startPoint.y)/2;
				tempObj.set({ rx, ry, left: Math.min(p.x, startPoint.x), top: Math.min(p.y, startPoint.y) });
			} else if (currentTool === 'line') {
				tempObj.set({ x2: p.x, y2: p.y });
			}
			tempObj.setCoords();
			canvas.requestRenderAll();
		}

		// --- Aperçu losange ---
		if (currentTool === 'diamond' && tempObj) {
			const cx = (startPoint.x + p.x) / 2;
			const cy = (startPoint.y + p.y) / 2;
			const w = Math.abs(p.x - startPoint.x);
			const h = Math.abs(p.y - startPoint.y);

			tempObj.set({
				left: cx,
				top: cy,
				points: [
					{ x: 0,   y: -h/2 },
					{ x: w/2, y: 0    },
					{ x: 0,   y:  h/2 },
					{ x:-w/2, y: 0    }
				],
				dirty: true
			});
			tempObj.setCoords();
			canvas.requestRenderAll();
		}

		// --- Aperçu flèche ---
		if (currentTool === 'arrow' && isDrawingArrow) {
			canvas.requestRenderAll(); // optionnel: juste rafraîchir le canevas
		}
	});

	canvas.on('mouse:up', function(opt) {
	  isPanning = false;
	  this.selection = true;

	  const active = canvas.getActiveObject();

	  // --- Si on était en train d’éditer un texte ---
	  if (isEditingText) {
		// On ne touche pas au tool; on laisse l'édition gérer sa fin via 'editing:exited'
		tempObj = null;
		startPoint = null;
		return;
	  }

	  const p = canvas.getPointer(opt.e);

	  // --- Flèche ---
	 if (currentTool === 'arrow') {
		const pointer = canvas.getPointer(opt.e);
		const arrow = addArrow({ x: startPoint.x, y: startPoint.y }, pointer, typeFleche);
		canvas.add(arrow);
		finalizeObject(arrow);  // pour activer et rendre sélectionnable
		tempObj = null;
		startPoint = null;
		isDrawingShape = false;
		isDrawingArrow = false;
		currentTool = 'select';
		return;
	}

	  // --- Losange ---
	  if (currentTool === 'diamond' && startPoint) {
		const cx = (startPoint.x + p.x) / 2;
		const cy = (startPoint.y + p.y) / 2;
		const w = Math.abs(p.x - startPoint.x);
		const h = Math.abs(p.y - startPoint.y);
		const props = getCommonProps();

		if (tempObj) canvas.remove(tempObj); // par sécurité

		const diamond = new fabric.Polygon([
		  { x: 0,   y: -h/2 },
		  { x: w/2, y: 0    },
		  { x: 0,   y:  h/2 },
		  { x:-w/2, y: 0    }
		], {
		  left: cx, top: cy, originX: 'center', originY: 'center',
		  perPixelTargetFind: true, hitStrokeWidth: 12,
		  hoverCursor: 'move', hasControls: true,
		  lockMovementX: false, lockMovementY: false,
		  objectCaching: false, ...props
		});

		canvas.add(diamond);
		diamond.setCoords();
		finalizeObject(diamond);
		canvas.setActiveObject(diamond);

		tempObj = null;
		startPoint = null;
		isDrawingShape = false;
		currentTool = 'select';
		return;
	  }

	  // --- Gardes pour les autres formes ---
	  if (!tempObj || !startPoint) return;

	  // --- Texte (au cas où) ---
	  if (tempObj && (tempObj.type === 'text' || tempObj.type === 'i-text' || tempObj.type === 'textbox')) {
		finalizeObject(tempObj);
		tempObj = null;
		startPoint = null;
		return;
	  }

	  // --- Rect / Ellipse / Ligne ---
	  finalizeObject(tempObj);
	  tempObj.set({ selectable: true, evented: true, hasControls: true, lockMovementX: false, lockMovementY: false });
	  tempObj.setCoords();
	  canvas.setActiveObject(tempObj);

	  tempObj = null;
	  startPoint = null;
	  isDrawingShape = false;
	  currentTool = 'select';
	});

  // --- Tools State ---
  const toolbar = document.getElementById('toolbar');
  const statusTool = document.getElementById('statusTool');

	// --- Gestion des outils ---
	function setActiveTool(tool){
		currentTool = tool;
		canvas.isDrawingMode = (tool === 'freedraw');

		if (canvas.isDrawingMode) {
			canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
			canvas.freeDrawingBrush.width = parseInt(strokeWidth.value, 10) || 2;
			canvas.freeDrawingBrush.color = strokeColor.value;
		}

		// Mise à jour des boutons
		[...toolbar.querySelectorAll('button.tool[data-tool]')].forEach(b => b.classList.remove('active'));
		const activeBtn = toolbar.querySelector(`button.tool[data-tool="${tool}"]`);
		if(activeBtn) activeBtn.classList.add('active');

		statusTool.textContent = 'Outil : ' + {
			select:'Sélection', rect:'Rectangle', ellipse:'Ellipse',
			line:'Ligne', arrow:'Flèche', freedraw:'Dessin libre', text:'Texte'
		}[tool];
	}

	setActiveTool('select');

	// --- Flèches sécurisées ---
	function addArrow(start, end, type) {
		const strokeWidthVal = parseInt(strokeWidth.value,10) || 2;
		const color = strokeColor.value;

		if (type === 'straight') {
			const line = new fabric.Line([start.x, start.y, end.x, end.y], {
				stroke: color,
				strokeWidth: strokeWidthVal,
				selectable: true,
				objectCaching: false
			});

			// Calcul de la flèche
			const angle = Math.atan2(end.y - start.y, end.x - start.x);
			const headLength = 10 + strokeWidthVal; // taille flèche
			const arrowHead = new fabric.Triangle({
				left: end.x,
				top: end.y,
				originX: 'center',
				originY: 'center',
				width: headLength,
				height: headLength,
				angle: angle * 180 / Math.PI + 90, // orienter correctement
				fill: color,
				selectable: true,
				objectCaching: false
			});

			return new fabric.Group([line, arrowHead], { selectable: true });
		} 
		else if (type === 'curved') {
			const path = new fabric.Path(
				`M ${start.x} ${start.y} Q ${(start.x+end.x)/2} ${(start.y+end.y)/2-50}, ${end.x} ${end.y}`,
				{
					stroke: color,
					fill: '',
					strokeWidth: strokeWidthVal,
					selectable: true,
					objectCaching: false
				}
			);
			// Ajouter flèche finale si nécessaire (triangle)
			const angle = Math.atan2(end.y - start.y, end.x - start.x);
			const headLength = 10 + strokeWidthVal;
			const arrowHead = new fabric.Triangle({
				left: end.x,
				top: end.y,
				originX: 'center',
				originY: 'center',
				width: headLength,
				height: headLength,
				angle: angle * 180 / Math.PI + 90,
				fill: color,
				selectable: true,
				objectCaching: false
			});
			return new fabric.Group([path, arrowHead], { selectable: true });
		} 
		else if (type === 'elbow') {
			const path = new fabric.Path(
				`M ${start.x} ${start.y} L ${start.x} ${end.y} L ${end.x} ${end.y}`,
				{
					stroke: color,
					fill: '',
					strokeWidth: strokeWidthVal,
					selectable: true,
					objectCaching: false
				}
			);
			const angle = Math.atan2(end.y - start.y, end.x - start.x);
			const headLength = 10 + strokeWidthVal;
			const arrowHead = new fabric.Triangle({
				left: end.x,
				top: end.y,
				originX: 'center',
				originY: 'center',
				width: headLength,
				height: headLength,
				angle: angle * 180 / Math.PI + 90,
				fill: color,
				selectable: true,
				objectCaching: false
			});
			return new fabric.Group([path, arrowHead], { selectable: true });
		}
	}
	
	canvas.on('mouse:wheel', function(opt) {
		const evt = opt.e;

		// Zoom uniquement si ALT est appuyé
		if (!evt.shiftKey) return;

		evt.preventDefault();  // empêche le scroll de la page
		evt.stopPropagation();

		const delta = evt.deltaY;
		let zoom = canvas.getZoom();
		zoom *= 0.999 ** delta; // ajustement sensible du zoom

		// Limites du zoom
		if (zoom > 20) zoom = 20;
		if (zoom < 0.1) zoom = 0.1;

		canvas.zoomToPoint({ x: evt.offsetX, y: evt.offsetY }, zoom);
	});

  toolbar.addEventListener('click', (e)=>{
    const btn = e.target.closest('button.tool');
    if (!btn) return;
    const tool = btn.getAttribute('data-tool');
    if (tool) setActiveTool(tool);
  });

  // --- History (Undo/Redo) ---
  const undoStack = [];
  const redoStack = [];
  let isRestoring = false;
  const MAX_HISTORY = 50;

	let startX, startY;

	function loadFrom(json){
	  isRestoring = true;
	  canvas.loadFromJSON(json, ()=>{
		drawGrid(); // redessiner la grille
		// Mettre la grille à l'arrière
		gridLines.forEach(line => line.sendToBack());
		// Tous les objets (hors grille) devant
		canvas.getObjects().forEach(obj => {
		  if (!obj.excludeFromExport) obj.bringToFront();
		});
		canvas.renderAll();
		isRestoring = false;
	  });
	}

  document.getElementById('undo').addEventListener('click', ()=>{
    if (!undoStack.length) return;
    const current = canvas.toDatalessJSON();
    const prev = undoStack.pop();
    redoStack.push(current);
    loadFrom(prev);
  });
  document.getElementById('redo').addEventListener('click', ()=>{
    if (!redoStack.length) return;
    const current = canvas.toDatalessJSON();
    const next = redoStack.pop();
    undoStack.push(current);
    loadFrom(next);
  });

  // Save after mutation events
  ['object:added','object:modified','object:removed'].forEach(evt=>{
    canvas.on(evt, ()=> saveState());
  });
  // initial state
  saveState();

	function finalizeObject(obj){
		canvas.setActiveObject(obj);
		canvas.requestRenderAll();
		setActiveTool('select'); // bascule automatique sur sélection
	}

  // --- Drawing primitives ---
  function getCommonProps(){
    const fill = noFill.checked ? 'rgba(0,0,0,0)' : fillColor.value;
    return {
      fill,
      stroke: strokeColor.value,
      strokeWidth: parseInt(strokeWidth.value, 10) || 2,
      selectable: true,
      objectCaching: false,
    };
  }

	// Exemple : menu pour changer de type
	document.getElementById('arrowType').addEventListener('change', (e)=>{
		typeFleche = e.target.value; // straight / curved / elbow
	});

  // Sync brush/style
  function applyStyleToActive(){
    const obj = canvas.getActiveObject();
    if (!obj) return;
    const props = getCommonProps();
    obj.set({ stroke: props.stroke, strokeWidth: props.strokeWidth });
    if (obj.type !== 'line' && obj.type !== 'group'){
      obj.set({ fill: props.fill });
    }
    canvas.requestRenderAll();
  }
  strokeColor.addEventListener('change', ()=>{
    if (canvas.isDrawingMode) canvas.freeDrawingBrush.color = strokeColor.value;
    applyStyleToActive();
  });
  fillColor.addEventListener('change', applyStyleToActive);
  noFill.addEventListener('change', applyStyleToActive);
  strokeWidth.addEventListener('input', ()=>{
    if (canvas.isDrawingMode) canvas.freeDrawingBrush.width = parseInt(strokeWidth.value,10)||2;
    applyStyleToActive();
  });

  // Layering & actions
  document.getElementById('bringFront').addEventListener('click', ()=>{
    const obj = canvas.getActiveObject(); if (!obj) return; obj.bringToFront(); canvas.requestRenderAll();
  });
  document.getElementById('sendBack').addEventListener('click', ()=>{
    const obj = canvas.getActiveObject(); if (!obj) return; obj.sendToBack(); canvas.requestRenderAll();
  });
  document.getElementById('delete').addEventListener('click', ()=>{
    const obj = canvas.getActiveObject(); if (!obj) return; if (obj.type === 'activeSelection'){ obj.getObjects().forEach(o=>canvas.remove(o)); } else { canvas.remove(obj); }
  });
  // --- Duplication sécurisée ---
	document.getElementById('duplicate').addEventListener('click', ()=>{
		const obj = canvas.getActiveObject();
		if(!obj) return;

		if(obj.type==='activeSelection'){
			const objects = obj.getObjects();
			const clonePromises = objects.map(o => new Promise(resolve=>{
				o.clone(cloned=>{
					cloned.set({ left:(o.left||0)+20, top:(o.top||0)+20 });
					canvas.add(cloned);
					resolve(cloned);
				});
			}));

			Promise.all(clonePromises).then(clones=>{
				const selection = new fabric.ActiveSelection(clones, { canvas });
				canvas.setActiveObject(selection);
				canvas.requestRenderAll();
			});

		} else {
			obj.clone(cloned=>{
				cloned.set({ left:(obj.left||0)+20, top:(obj.top||0)+20 });
				canvas.add(cloned).setActiveObject(cloned);
				canvas.requestRenderAll();
			});
		}
	});

  // Keyboard shortcuts
	document.addEventListener('keydown', (e) => {
		const ctrl = e.ctrlKey || e.metaKey;
		const alt = e.altKey;

		// --- Undo / Redo / Duplicate (reste Ctrl pour compatibilité) ---
		if (ctrl && e.key.toLowerCase() === 'z') { 
			e.preventDefault(); 
			document.getElementById('undo').click(); 
		}
		if ((ctrl && e.key.toLowerCase() === 'y') || (ctrl && e.shiftKey && e.key.toLowerCase() === 'z')) { 
			e.preventDefault(); 
			document.getElementById('redo').click(); 
		}
		if (ctrl && e.key.toLowerCase() === 'd') { 
			e.preventDefault(); 
			document.getElementById('duplicate').click(); 
		}

		// --- Delete ---
		if (e.key === 'Delete' || e.key === 'Backspace') { 
			if (!['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) { 
				e.preventDefault(); 
				document.getElementById('delete').click(); 
			} 
		}

		// --- Tool quick keys avec ALT ---
		const active = canvas.getActiveObject();

		if (!isEditingText && alt) {
			const map = { 'v':'select','r':'rect','o':'ellipse','l':'line','a':'arrow','p':'freedraw','t':'text' };
			const k = e.key.toLowerCase();
			if (map[k]) {
				e.preventDefault();
				setActiveTool(map[k]);
			}
		}
		if (!active) return;

		let step = 1; // par défaut
		if (e.shiftKey) step = 10;

		switch(e.key){
			case 'ArrowUp':
				e.preventDefault();
				active.top -= step;
				active.setCoords();
				canvas.requestRenderAll();
				break;
			case 'ArrowDown':
				e.preventDefault();
				active.top += step;
				active.setCoords();
				canvas.requestRenderAll();
				break;
			case 'ArrowLeft':
				e.preventDefault();
				active.left -= step;
				active.setCoords();
				canvas.requestRenderAll();
				break;
			case 'ArrowRight':
				e.preventDefault();
				active.left += step;
				active.setCoords();
				canvas.requestRenderAll();
				break;
		}
	});

	function saveToServer(type, data) {
		if(currentRole !== 'admin') {
			console.log("Sauvegarde côté serveur réservée à l'admin.");
			return; // arrêt pour les étudiants
		}
		const formData = new FormData();

		// Récupération du nom choisi par l'étudiant
		let filename = document.getElementById("filenameInput").value || "dessin";

		formData.append("type", type);
		formData.append("data", data);
		formData.append("filename", filename); // on envoie aussi le nom

		fetch("save_dessin.php", {
			method: "POST",
			body: formData,
			credentials: "include"
		})
		.then(r => r.text())
		.then(console.log);
	}

  // Export PNG
  function download(filename, dataUrl){
    const a = document.createElement('a');
    a.href = dataUrl; a.download = filename; document.body.appendChild(a); a.click(); document.body.removeChild(a);
  }
	// Export PNG
	document.getElementById('exportPNG').addEventListener('click', () => {
		const dataUrl = canvas.toDataURL({ format: 'png', multiplier: 2, enableRetinaScaling: true });
		const filename = new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '-dessin.png';

		// Téléchargement côté client
		download(filename, dataUrl);

		// Copie côté serveur
		saveToServer("png", dataUrl);
	});

	// Export SVG
	document.getElementById('exportSVG').addEventListener('click', () => {
		const svg = canvas.toSVG();
		const filename = new Date().toISOString().slice(0,19).replace(/[:T]/g,'-') + '-dessin.svg';
		const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
		const url = URL.createObjectURL(blob);

		// Téléchargement côté client
		download(filename, url);

		// Copie côté serveur (SVG en texte brut)
		saveToServer("svg", svg);

		setTimeout(() => URL.revokeObjectURL(url), 1000);
	});

  // Sélection par défaut
  setActiveTool('select');

	// --- 2. Ajout d'objets de test ---
	/*const rect = new fabric.Rect({
	  left: 100, top: 100,
	  width: 60, height: 60,
	  fill: 'skyblue'
	});
	const circle = new fabric.Circle({
	  left: 200, top: 150,
	  radius: 30,
	  fill: 'lightgreen'
	});
	canvas.add(rect, circle);*/

	// --- 3. Aimantation sur la grille (position)
	canvas.on('object:moving', function (options) {
	  const obj = options.target;
	  if (!obj) return;

	  if (obj.type === 'group') {
		// Snap sur le centre du groupe
		const center = obj.getCenterPoint();
		const snappedX = Math.round(center.x / grid) * grid;
		const snappedY = Math.round(center.y / grid) * grid;
		const dx = snappedX - center.x;
		const dy = snappedY - center.y;
		obj.left += dx;
		obj.top += dy;
		obj.setCoords();
	  } else {
		obj.left = Math.round(obj.left / grid) * grid;
		obj.top = Math.round(obj.top / grid) * grid;
		obj.setCoords();
	  }
	});

	// --- 4. Aimantation sur redimensionnement
	canvas.on('object:scaling', function (options) {
	  const obj = options.target;
	  if (!obj.width || !obj.height) return;

	  if (obj.type === 'group') {
		// On applique le scaling global du groupe sur la grille
		const newScaleX = Math.round((obj.scaleX * obj.width) / grid) * grid / obj.width;
		const newScaleY = Math.round((obj.scaleY * obj.height) / grid) * grid / obj.height;
		obj.scaleX = newScaleX || obj.scaleX;
		obj.scaleY = newScaleY || obj.scaleY;
		obj.setCoords();
	  } else {
		const newScaleX = Math.round((obj.scaleX * obj.width) / grid) * grid / obj.width;
		const newScaleY = Math.round((obj.scaleY * obj.height) / grid) * grid / obj.height;
		obj.set({ scaleX: newScaleX || obj.scaleX, scaleY: newScaleY || obj.scaleY });
	  }
	});
	
	// Clic sur le bouton pour ouvrir le sélecteur de fichier
	document.getElementById('btnImportJSON').addEventListener('click', ()=>{
	  document.getElementById('importJSON').click();
	});

	// Lorsqu'un fichier est sélectionné
	document.getElementById('importJSON').addEventListener('change', (e) => {
	  const file = e.target.files[0];
	  if (!file) return;

	  const reader = new FileReader();
	  reader.onload = (evt) => {
		try {
		  const json = JSON.parse(evt.target.result);

		  // 1. Charger le JSON
		  canvas.loadFromJSON(json, () => {
				// Redimensionner
				const { width, height } = calculateOptimalSize();
				canvas.setDimensions({ width, height });

				// Redessiner la grille
				drawGrid();

				// Mettre la grille à l'arrière-plan
				gridLines.forEach(line => line.sendToBack());

				// Mettre tous les objets importés devant la grille
				canvas.getObjects().forEach(obj => {
					if (!gridLines.includes(obj)) obj.bringToFront();
				});

				// Rendu final et sauvegarde
				canvas.renderAll();
				saveState();
			});
		} catch(err) {
		  alert('Erreur lors de l’import JSON : ' + err.message);
		}
	  };
	  reader.readAsText(file);
	});

	// Bouton Réinitialiser
	document.getElementById('resetCanvas').addEventListener('click', () => {
	  if (!confirm("Voulez-vous vraiment réinitialiser la grille et effacer tout le dessin ?")) return;

	  // 1) Supprimer proprement sans rater d’objets
	  canvas.getObjects().slice().forEach(obj => {
		if (!obj.excludeFromExport) canvas.remove(obj);
	  });

	  // 2) État visuel neutre
	  canvas.setBackgroundColor('#ffffff', canvas.renderAll.bind(canvas));
	  canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);

	  // 3) Recalcule taille + grille via tes helpers
	  const { width, height } = calculateOptimalSize();
	  applySize(width, height); // (appel déjà drawGrid() + renderAll() dans ton code)

	  // 4) Réinitialiser l’historique (évite des “undo” vers des objets fantômes)
	  undoStack.length = 0;
	  redoStack.length = 0;
	  isRestoring = false;
	  saveState();
	    // 5) Important : permettre de ré-importer le même fichier ensuite
	  const fileInput = document.getElementById('importJSON');
	  if (fileInput) fileInput.value = '';
	});
	
	// --- Appliquer la police à un texte sélectionné ---
	fontFamily.addEventListener('change', () => {
		const obj = canvas.getActiveObject();
		if (!obj) return;
		if (obj.type === 'i-text' || obj.type === 'textbox') {
			obj.set({ fontFamily: fontFamily.value });
			canvas.requestRenderAll();
		}
	});

	// --- Inclure la police dans applyStyleToActive ---
	function applyStyleToActive() {
		const obj = canvas.getActiveObject();
		if (!obj) return;
		const props = getCommonProps();
		obj.set({ stroke: props.stroke, strokeWidth: props.strokeWidth });
		if (obj.type !== 'line' && obj.type !== 'group'){
			obj.set({ fill: props.fill });
		}
		if (obj.type === 'i-text' || obj.type === 'textbox') {
			obj.set({ fontFamily: fontFamily.value });
		}
		canvas.requestRenderAll();
	}
	
	// Intercepter tous les liens sensibles dans le header
	document.querySelectorAll('.navbar .nav-btn').forEach(link => {
		link.addEventListener('click', function(e) {
			// On définit les liens qui nécessitent une confirmation
			const sensitive = ['dashboard.php', 'dessin.php', 'fichiers.php', 'logout.php']; // exemple : quitter ou supprimer

			if (sensitive.some(s => link.href.includes(s))) {
				const msg = `Voulez-vous vraiment quitter cette page et perdre vos modifications non sauvegardées ?`;
				if (!confirm(msg)) {
					e.preventDefault(); // bloque le clic
				}
			}
		});
	});
	
	// Modale d'aide
	const helpBtn = document.getElementById("helpBtn");
	const helpModal = document.getElementById("helpModal");
	const closeHelp = document.getElementById("closeHelp");

	helpBtn.addEventListener("click", () => {
	  helpModal.style.display = "block";
	});
	closeHelp.addEventListener("click", () => {
	  helpModal.style.display = "none";
	});
	window.addEventListener("click", (e) => {
	  if (e.target === helpModal) helpModal.style.display = "none";
	});
})();
</script>

</body></html>
