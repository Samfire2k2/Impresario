<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/writer-functions.php';

requireLogin();

$userId = getCurrentUserId();
$characterId = $_GET['id'] ?? null;
$projectId = $_GET['project'] ?? null;

if (!$characterId || !$projectId) {
    header('Location: characters.php?id=' . $projectId);
    exit;
}

$project = getProject($pdo, $projectId, $userId);
if (!$project) {
    die('Accès refusé');
}

// Get character
$stmt = $pdo->prepare('SELECT * FROM character WHERE id = :id AND project_id = :project_id');
$stmt->execute([':id' => $characterId, ':project_id' => $projectId]);
$character = $stmt->fetch();

if (!$character) {
    die('Personnage non trouvé');
}

$error = '';
$success = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update-character') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $description = $_POST['description'] ?? '';
    $physicalTraits = $_POST['physical_traits'] ?? '';
    $arcNotes = $_POST['arc_notes'] ?? '';
    $colorHex = $_POST['color_hex'] ?? '#e74c3c';
    
    $stmt = $pdo->prepare('
        UPDATE character 
        SET name = :name, role = :role, description = :desc, 
            physical_traits = :physical, arc_notes = :arc, color_hex = :color
        WHERE id = :id
    ');
    
    $result = $stmt->execute([
        ':name' => $name,
        ':role' => $role,
        ':desc' => $description,
        ':physical' => $physicalTraits,
        ':arc' => $arcNotes,
        ':color' => $colorHex,
        ':id' => $characterId
    ]);
    
    if ($result) {
        $success = 'Personnage mis à jour!';
        $stmt = $pdo->prepare('SELECT * FROM character WHERE id = :id');
        $stmt->execute([':id' => $characterId]);
        $character = $stmt->fetch();
    } else {
        $error = 'Erreur lors de la mise à jour.';
    }
}

// Get scenes with this character
$stmt = $pdo->prepare('
    SELECT e.id, e.title, i.title as intrigue_title, ec.appearance_type
    FROM element_character ec
    JOIN element e ON ec.element_id = e.id
    JOIN intrigue i ON e.intrigue_id = i.id
    WHERE ec.character_id = :id
    ORDER BY i.position, e.position
');
$stmt->execute([':id' => $characterId]);
$sceneAppearances = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($character['name']); ?> - Impresario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
    <div class="navbar-container">
        <a href="characters.php?id=<?php echo $projectId; ?>" class="logo-link">← Personnages</a>
        <span class="page-title"><i class="fas fa-user"></i> <?php echo escapeHtml($character['name']); ?></span>
        <span class="username"><?php echo escapeHtml(getCurrentUsername()); ?></span>
    </div>

    <div class="editor-container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escapeHtml($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeHtml($success); ?></div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form method="POST" class="editor-form">
            <input type="hidden" name="action" value="update-character">
            <h2 style="margin-top: 0;">Éditer le personnage</h2>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du personnage</label>
                    <input type="text" name="name" value="<?php echo escapeHtml($character['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Rôle</label>
                    <input type="text" name="role" value="<?php echo escapeHtml($character['role'] ?? ''); ?>" placeholder="Héroïne, Antagoniste...">
                </div>

                <div class="form-group">
                    <label>Couleur d'identification</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="color_hex" value="<?php echo escapeHtml($character['color_hex'] ?? '#e74c3c'); ?>" id="color-picker">
                        <div class="color-preview" id="color-preview" style="background-color: <?php echo escapeHtml($character['color_hex'] ?? '#e74c3c'); ?>;"></div>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Description & Personnalité</label>
                <textarea name="description" placeholder="Qui est ce personnage? Ses traits de caractère..."><?php echo escapeHtml($character['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Traits physiques</label>
                <textarea name="physical_traits" placeholder="Apparence physique, signes distinctifs..."><?php echo escapeHtml($character['physical_traits'] ?? ''); ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Arc narratif & Évolution</label>
                <textarea name="arc_notes" placeholder="Comment ce personnage évolue-t-il? Ses objectifs, secrets..."><?php echo escapeHtml($character['arc_notes'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Sauvegarder</button>
                <a href="characters.php?id=<?php echo $projectId; ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>

        <!-- Scene Appearances -->
        <?php if ($sceneAppearances): ?>
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-book"></i> Apparitions dans les scènes</h2>
                </div>
                <div class="appearance-list">
                    <?php foreach ($sceneAppearances as $app): ?>
                        <div class="appearance-item">
                            <div class="appearance-info">
                                <div class="appearance-title"><?php echo escapeHtml($app['title']); ?></div>
                                <div class="appearance-meta">Chapitre: <?php echo escapeHtml($app['intrigue_title']); ?></div>
                            </div>
                            <span class="appearance-type"><?php echo $app['appearance_type'] === 'main' ? '<i class="fas fa-star" style="color: #f39c12;"></i> Principal' : ($app['appearance_type'] === 'secondary' ? '<i class="fas fa-handshake" style="color: #3498db;"></i> Secondaire' : '<i class="fas fa-location-dot" style="color: #95a5a6;"></i> Mentionn\u00e9'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-book"></i> Apparitions dans les scènes</h2>
                </div>
                <div class="empty-state">
                    Ce personnage n'apparaît pas encore dans les scènes.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Update color preview
        document.getElementById('color-picker').addEventListener('change', (e) => {
            document.getElementById('color-preview').style.backgroundColor = e.target.value;
        });
    </script>
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
