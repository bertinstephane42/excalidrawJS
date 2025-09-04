<?php 
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

include __DIR__ . '/inc/header.php';

$dir = __DIR__ . '/dessins';
$files = glob($dir . '/*.{json,png,svg}', GLOB_BRACE);

// Séparer les fichiers par préfixe et par type
$sections = [
    'bts1' => ['png'=>[], 'svg'=>[], 'json'=>[]],
    'bts2' => ['png'=>[], 'svg'=>[], 'json'=>[]],
    'lic3' => ['png'=>[], 'svg'=>[], 'json'=>[]]
];

foreach ($files as $file) {
    $name = basename($file);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    
    if (str_starts_with($name, 'bts1') && isset($sections['bts1'][$ext])) $sections['bts1'][$ext][] = $file;
    elseif (str_starts_with($name, 'bts2') && isset($sections['bts2'][$ext])) $sections['bts2'][$ext][] = $file;
    elseif (str_starts_with($name, 'lic3') && isset($sections['lic3'][$ext])) $sections['lic3'][$ext][] = $file;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<div class="main-app">
  <h1>Les Dessins</h1>
  
<?php 
// Vérifie si toutes les sections sont vides
$allEmpty = true;
foreach ($sections as $types) {
    if (array_sum(array_map('count', $types)) > 0) {
        $allEmpty = false;
        break;
    }
}
?>

<?php if ($allEmpty): ?>
    <p style="margin-top:2rem; font-style:italic; color:#555;">
        Aucun fichier n'a été enregistré pour le moment. Revenez plus tard pour consulter les dessins disponibles.
    </p>
<?php else: ?>
    <?php foreach ($sections as $sectionName => $types): ?>
        <?php if (array_sum(array_map('count', $types)) === 0) continue; // Ignore les sections vides ?>
        <div class="section">
          <h2 class="section-header" data-section="<?= $sectionName ?>">
            <?= strtoupper($sectionName) ?> 
            (<?= array_sum(array_map('count', $types)) ?>) 
            <button class="toggle-section-btn">▾</button>
          </h2>

          <?php foreach ($types as $ext => $sectionFiles): ?>
              <?php if (empty($sectionFiles)) continue; ?>
              <div class="sub-section">
                  <h3><?= strtoupper($ext) ?> (<?= count($sectionFiles) ?>)</h3>

                  <div class="sub-section-controls">
                      Trier par : 
                      <button class="sort-btn" data-section="<?= $sectionName ?>-<?= $ext ?>" data-sort="name">Nom</button>
                      <button class="sort-btn" data-section="<?= $sectionName ?>-<?= $ext ?>" data-sort="date">Date</button>
                  </div>

                  <div class="files-grid" id="grid-<?= $sectionName ?>-<?= $ext ?>">
                      <?php foreach ($sectionFiles as $file): 
                          $name = basename($file);
                          $nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
                          $timestamp = filemtime($file);
                      ?>
                      <div class="file-card" data-name="<?= htmlspecialchars($name) ?>" data-date="<?= $timestamp ?>">
                        <div class="file-name"><?= htmlspecialchars($nameWithoutExt) ?></div>
                        <div class="file-actions">
                          <a href="view_dessin.php?file=<?= urlencode($name) ?>" target="_blank" class="btn">Voir</a>
                          <a href="view_dessin.php?file=<?= urlencode($name) ?>&download=1" class="btn">Télécharger</a>
                          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
							<form method="POST" action="delete_dessin.php" style="display:inline;" onsubmit="return confirm('Supprimer ce fichier ?')">
								<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
								<input type="hidden" name="file" value="<?= htmlspecialchars($name) ?>">
								<button type="submit" class="btn">Supprimer</button>
							</form>
						<?php endif; ?>
                        </div>
                      </div>
                      <?php endforeach; ?>
                  </div>
              </div>
          <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

  <p style="margin-top:2rem;">
    <a href="dessin.php" class="dash-btn">Créer un nouveau dessin</a>
  </p>
</div>

<script>
// ▸ Stocke l’état du tri pour chaque section-sous-section
const sortState = {};

// ▸ Tri des fichiers dans une sous-section avec alternance croissant/décroissant
document.querySelectorAll('.sort-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const section = btn.dataset.section;
        const sortType = btn.dataset.sort;
        const grid = document.getElementById('grid-' + section);
        const cards = Array.from(grid.querySelectorAll('.file-card'));

        if (!sortState[section]) sortState[section] = {};
        if (!sortState[section][sortType]) sortState[section][sortType] = 'asc';

        const order = sortState[section][sortType] === 'asc' ? 1 : -1;

        // Trie les cartes
        cards.sort((a, b) => {
            if (sortType === 'name') return a.dataset.name.localeCompare(b.dataset.name) * order;
            else if (sortType === 'date') return (a.dataset.date - b.dataset.date) * order;
        });

        cards.forEach(card => grid.appendChild(card));

        // Alterne l'état pour le prochain clic
        sortState[section][sortType] = sortState[section][sortType] === 'asc' ? 'desc' : 'asc';

        // ▸ Mise à jour des flèches sur les boutons
        document.querySelectorAll(`.sort-btn[data-section="${section}"]`).forEach(b => {
            if (b.dataset.sort === sortType) {
                b.textContent = b.textContent.replace(/[\u2191\u2193]/g,'') + (order === 1 ? ' ↑' : ' ↓');
            } else {
                // Retire la flèche des boutons inactifs
                b.textContent = b.textContent.replace(/[\u2191\u2193]/g,'');
            }
        });
    });
});

// ▸ Collapse / expand des sections
document.querySelectorAll('.toggle-section-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const header = btn.closest('.section-header');
        const sectionDiv = header.parentElement;
        const subSections = sectionDiv.querySelectorAll('.sub-section');
        const isVisible = subSections[0].style.display !== 'none';

        subSections.forEach(s => s.style.display = isVisible ? 'none' : '');
        btn.textContent = isVisible ? '▸' : '▾';
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.section').forEach(section => {
        const subSections = section.querySelectorAll('.sub-section');
        subSections.forEach(s => s.style.display = 'none');

        // Modifier le bouton pour indiquer que c'est fermé
        const toggleBtn = section.querySelector('.toggle-section-btn');
        if (toggleBtn) toggleBtn.textContent = '▸';
    });
});
</script>