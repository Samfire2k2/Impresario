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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .editor-container {
            padding: calc(40px * var(--size-scale));
            max-width: 900px;
            margin: 0 auto;
        }

        .editor-form {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .color-picker-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 12px;
        }

        .color-picker-wrapper input[type="color"] {
            height: 44px;
            width: 60px;
            border-radius: 6px;
            cursor: pointer;
        }

        .color-preview {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
        }

        .form-actions {
            display: flex;
            gap: 12px;
        }

        .form-actions .btn {
            padding: 12px 24px;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.3em;
        }

        .appearance-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .appearance-item {
            background: var(--bg-tertiary);
            padding: 12px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .appearance-info {
            flex: 1;
        }

        .appearance-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .appearance-meta {
            font-size: 0.85em;
            color: var(--text-secondary);
        }

        .appearance-type {
            font-size: 0.8em;
            background: var(--border-color);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="navbar-container">
        <a href="characters.php?id=<?php echo $projectId; ?>" class="logo-link">← Personnages</a>
        <span class="page-title">👤 <?php echo escapeHtml($character['name']); ?></span>
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
                <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                <a href="characters.php?id=<?php echo $projectId; ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>

        <!-- Scene Appearances -->
        <?php if ($sceneAppearances): ?>
            <div class="section">
                <div class="section-header">
                    <h2>📖 Apparitions dans les scènes</h2>
                </div>
                <div class="appearance-list">
                    <?php foreach ($sceneAppearances as $app): ?>
                        <div class="appearance-item">
                            <div class="appearance-info">
                                <div class="appearance-title"><?php echo escapeHtml($app['title']); ?></div>
                                <div class="appearance-meta">Chapitre: <?php echo escapeHtml($app['intrigue_title']); ?></div>
                            </div>
                            <span class="appearance-type"><?php echo $app['appearance_type'] === 'main' ? '⭐ Principal' : ($app['appearance_type'] === 'secondary' ? '🤝 Secondaire' : '📍 Mentionné'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="section">
                <div class="section-header">
                    <h2>📖 Apparitions dans les scènes</h2>
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
</body>
</html>
