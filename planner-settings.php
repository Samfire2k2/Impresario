<?php
/**
 * Paramètres du Planner - Orientation, palettes, etc.
 */

include 'includes/config.php';
include 'includes/functions.php';
include 'includes/palette-functions.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = $_GET['project'] ?? null;

if (!$projectId) {
    header('Location: dashboard.php');
    exit;
}

$project = getProject($pdo, $projectId, $userId);
if (!$project) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer les palettes disponibles
$palettes = getAllPalettes($pdo);

// Gérer la sauvegarde des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save-settings') {
    $orientation = $_POST['orientation'] ?? 'vertical';
    $palette_id = $_POST['palette_id'] ?? null;
    $font_size = intval($_POST['font_size'] ?? 16);
    $element_size = intval($_POST['element_size'] ?? 100);
    
    $stmt = $pdo->prepare('
        UPDATE project 
        SET orientation = ?, palette_id = ?, font_size = ?, element_size = ?
        WHERE id = ? AND author_id = ?
    ');
    $stmt->execute([$orientation, $palette_id, $font_size, $element_size, $projectId, $userId]);
    
    header('Location: planner-settings.php?project=' . $projectId . '&success=1');
    exit;
}

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - <?php echo htmlspecialchars($project['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <a href="planner.php?project=<?php echo $projectId; ?>" class="logo-link">← Planner</a>
            <h1 class="page-title">Paramètres du Planner</h1>
            <div class="navbar-user">
                <a href="logout.php" class="btn btn-small">Déconnexion</a>
            </div>
        </div>
    </header>
    
    <div class="settings-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check"></i> Paramètres sauvegardés avec succès!
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="save-settings">
            
            <!-- Orientation -->
            <div class="settings-section">
                <h2><i class="fas fa-arrows-alt-v"></i> Orientation</h2>
                
                <div class="form-group">
                    <label for="orientation">Disposition du planner</label>
                    <select id="orientation" name="orientation">
                        <option value="vertical" <?php echo ($project['orientation'] ?? 'vertical') === 'vertical' ? 'selected' : ''; ?>>
                            Vertical (scènes en colonne)
                        </option>
                        <option value="horizontal" <?php echo ($project['orientation'] ?? '') === 'horizontal' ? 'selected' : ''; ?>>
                            Horizontal (scènes en ligne)
                        </option>
                    </select>
                </div>
            </div>
            
            <!-- Palette de couleurs -->
            <div class="settings-section">
                <h2><i class="fas fa-palette"></i> Palette de couleurs</h2>
                
                <div class="form-group">
                    <label for="palette_id">Sélectionnez une palette</label>
                    <select id="palette_id" name="palette_id" onchange="updatePalettePreview()">
                        <option value="">-- Couleurs personnalisées --</option>
                        <?php foreach ($palettes as $palette): ?>
                            <option value="<?php echo $palette['id']; ?>" 
                                    <?php echo ($project['palette_id'] ?? null) == $palette['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($palette['name']); ?>
                                <?php echo $palette['is_default'] ? ' (Par défaut)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <h3>Aperçu de la palette</h3>
                    <div class="palette-preview" id="palette-preview">
                        <!-- Rempli par JavaScript -->
                    </div>
                </div>
                
                <p style="font-size: 12px; color: var(--text-secondary);">
                    Les 5 palettes disponibles incluent:
                    <br><strong>Pastel</strong> - Douce et relaxante
                    <br><strong>Dark</strong> - Moderne et sombre
                    <br><strong>Vibrant</strong> - Vive et colorée
                    <br><strong>Minimal</strong> - Neutre et professionnelle
                    <br><strong>Daltonienne</strong> - Accessible pour tous
                </p>
            </div>
            
            <!-- Taille et Police -->
            <div class="settings-section">
                <h2><i class="fas fa-text-height"></i> Taille et Police</h2>
                
                <div class="form-group">
                    <label for="font_size">Taille de la police: <span id="font-size-value"><?php echo $project['font_size'] ?? 16; ?></span>px</label>
                    <input type="range" id="font_size" name="font_size" 
                           min="12" max="24" step="1" 
                           value="<?php echo $project['font_size'] ?? 16; ?>"
                           onchange="updateFontSizeValue(this.value)"
                           style="width: 100%;">
                    <small style="color: var(--text-secondary);">Contrôle la taille du texte dans le planner</small>
                </div>
                
                <div class="form-group">
                    <label for="element_size">Taille des éléments: <span id="element-size-value"><?php echo $project['element_size'] ?? 100; ?></span>%</label>
                    <input type="range" id="element_size" name="element_size" 
                           min="60" max="150" step="10" 
                           value="<?php echo $project['element_size'] ?? 100; ?>"
                           onchange="updateElementSizeValue(this.value)"
                           style="width: 100%;">
                    <small style="color: var(--text-secondary);">Ajuste la taille des scènes et intrigues</small>
                </div>
            </div>
            
            <!-- Boutons -->
            <div class="button-group" style="justify-content: flex-end;">
                <a href="planner.php?project=<?php echo $projectId; ?>" class="btn btn-small">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
            </div>
        </form>
    </div>
    
    <script src="assets/js/theme-manager.js"></script>
    <script>
        const palettes = <?php echo json_encode($palettes); ?>;
        
        function updatePalettePreview() {
            const paletteId = document.getElementById('palette_id').value;
            const preview = document.getElementById('palette-preview');
            
            if (!paletteId) {
                preview.innerHTML = '<p style="font-size: 12px; color: var(--text-secondary);">Aucune palette sélectionnée</p>';
                return;
            }
            
            const palette = palettes.find(p => p.id == paletteId);
            if (!palette) return;
            
            let html = '';
            // Display colors from palette
            const colors = palette.colors || {};
            for (const [role, color] of Object.entries(colors)) {
                html += '<div title="' + role + '" class="color-box" style="background-color: ' + color + ';"></div>';
            }
            
            if (html === '') {
                html = '<p style="font-size: 12px; color: var(--text-secondary);">Pas de couleurs définies</p>';
            }
            
            preview.innerHTML = html;
        }
        
        function updateFontSizeValue(val) {
            document.getElementById('font-size-value').textContent = val;
        }
        
        function updateElementSizeValue(val) {
            document.getElementById('element-size-value').textContent = val;
        }
        
        // Init preview on load
        document.addEventListener('DOMContentLoaded', updatePalettePreview);
    </script>
</body>
</html>
