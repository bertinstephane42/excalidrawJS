<?php
require_once __DIR__ . '/inc/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

include __DIR__ . '/inc/header.php';

$dir = __DIR__ . '/dessins';
$files = glob($dir . '/*.{json,png,svg}', GLOB_BRACE);
?>

<div class="main-app">
  <h1>Les Dessins</h1>

  <?php if (empty($files)): ?>
    <p>Aucun dessin enregistré.</p>
  <?php else: ?>
    <div class="files-grid">
      <?php foreach ($files as $file): 
        $name = basename($file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
      ?>
        <div class="file-card">
          <div class="file-name"><?= htmlspecialchars($name) ?></div>
          <div class="file-actions">
            <!-- Serveur PHP intermédiaire -->
            <a href="view_dessin.php?file=<?= urlencode($name) ?>" target="_blank" class="btn">Voir</a>
            <a href="view_dessin.php?file=<?= urlencode($name) ?>&download=1" class="btn">Télécharger</a>
			  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
				<a href="delete_dessin.php?file=<?= urlencode($name) ?>" 
				   class="btn" 
				   onclick="return confirm('Supprimer ce fichier ?')">Supprimer</a>
			  <?php endif; ?>
          </div>
          <div class="file-type"><?= strtoupper($ext) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p style="margin-top:2rem;">
    <a href="dessin.php" class="dash-btn">Créer un nouveau dessin</a>
  </p>
</div>
