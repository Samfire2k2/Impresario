<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/writer-functions.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = $_GET['project'] ?? null;
$intrigueId = $_GET['intrigue'] ?? null;
$elementId = $_GET['element'] ?? null;

if (!$projectId || !$intrigueId || !$elementId) {
    header('Location: dashboard.php');
    exit;
}

// Verify access
$project = getProject($pdo, $projectId, $userId);
if (!$project) {
    die('Accès refusé');
}

$intrigue = getIntrigue($pdo, $intrigueId);
if (!$intrigue || $intrigue['project_id'] != $projectId) {
    die('Accès refusé');
}

$element = getElement($pdo, $elementId);
if (!$element || $element['intrigue_id'] != $intrigueId) {
    die('Accès refusé');
}

$error = '';
$success = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save-content') {
    $content = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $writingNotes = $_POST['writing_notes'] ?? null;
    $pov = $_POST['pov_character'] ?? null;
    $location = $_POST['location'] ?? null;
    
    if (empty($content)) {
        $error = 'Le contenu ne peut pas être vide.';
    } else {
        $result = updateElementContent($pdo, $elementId, $content, $status, $writingNotes);
        
        // Update additional fields
        $stmt = $pdo->prepare('UPDATE element SET pov_character = :pov, location = :location WHERE id = :id');
        $stmt->execute([':pov' => $pov, ':location' => $location, ':id' => $elementId]);
        
        if ($result) {
            $success = 'Scène sauvegardée avec succès!';
            $element = getElement($pdo, $elementId); // Reload
        } else {
            $error = 'Erreur lors de la sauvegarde.';
        }
    }
}

// Get characters for this project
$characters = getProjectCharacters($pdo, $projectId);
$elementCharacters = getElementCharacters($pdo, $elementId);
$characterIds = array_map(fn($c) => $c['id'], $elementCharacters);

// Get version history
$versions = getElementVersionHistory($pdo, $elementId, 5);

// Get stats
$stats = getTextStats($element['content'] ?? '');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($element['title']); ?> - Éditeur - Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- TinyMCE Rich Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
    <style>
        .editor-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            margin-top: 20px;
        }

        .editor-main {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
        }

        .editor-sidebar {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            max-height: 600px;
            overflow-y: auto;
        }

        .toolbar-editor {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .toolbar-editor .btn {
            flex: 1;
            min-width: 120px;
        }

        .tox {
            border-color: var(--border-color) !important;
        }

        .tox-tinymce {
            background: var(--bg-primary) !important;
            border-radius: 8px !important;
        }

        .stats-box {
            background: var(--bg-tertiary);
            border-left: 3px solid var(--bronze);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.9em;
        }

        .stats-box strong {
            display: block;
            color: var(--bronze);
            margin-bottom: 4px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .version-history {
            border-top: 1px solid var(--border-color);
            padding-top: 16px;
            margin-top: 16px;
        }

        .version-item {
            font-size: 0.85em;
            padding: 8px;
            background: var(--bg-tertiary);
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .version-item:hover {
            background: var(--border-color);
            transform: translateX(2px);
        }

        .version-date {
            color: var(--text-secondary);
            font-size: 0.8em;
        }

        @media (max-width: 1024px) {
            .editor-layout {
                grid-template-columns: 1fr;
            }
            .editor-sidebar {
                max-height: none;
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            margin-bottom: 12px;
        }

        .characters-list {
            padding: 8px 0;
        }

        .character-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin: 2px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .character-badge:hover {
            transform: scale(1.05);
        }

        .character-badge.active {
            background: var(--bronze);
            box-shadow: 0 0 8px rgba(201, 168, 124, 0.5);
        }
    </style>
</head>
<body>
    <div class="navbar-container">
        <a href="project.php?id=<?php echo $projectId; ?>" class="logo-link">← Retour au projet</a>
        <span class="page-title">✍️ <?php echo escapeHtml($element['title']); ?></span>
        <span class="username"><?php echo escapeHtml($username); ?></span>
    </div>

    <div class="container" style="padding: calc(40px * var(--size-scale));">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escapeHtml($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeHtml($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="save-content">

            <div class="editor-layout">
                <!-- Main Editor -->
                <div class="editor-main">
                    <div class="form-group">
                        <label>Impact de la scène</label>
                        <textarea name="writing_notes" placeholder="Notes d'écriture, idées..."><?php echo escapeHtml($element['writing_notes'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label>POV Personnage</label>
                            <input type="text" name="pov_character" placeholder="Point de vue..." value="<?php echo escapeHtml($element['pov_character'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Lieu</label>
                            <input type="text" name="location" placeholder="Localisation..." value="<?php echo escapeHtml($element['location'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Contenu de la scène</label>
                        <textarea id="editor" name="content"><?php echo escapeHtml($element['content'] ?? ''); ?></textarea>
                    </div>

                    <div class="toolbar-editor">
                        <select name="status" class="form-select" style="flex: 1;">
                            <option value="draft" <?php echo $element['status'] === 'draft' ? 'selected' : ''; ?>>📝 Brouillon</option>
                            <option value="first_read" <?php echo $element['status'] === 'first_read' ? 'selected' : ''; ?>>👁️ Première relecture</option>
                            <option value="finalized" <?php echo $element['status'] === 'finalized' ? 'selected' : ''; ?>>✅ Finalisé</option>
                        </select>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%); color: white;">💾 Sauvegarder</button>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="editor-sidebar">
                    <div class="stats-box">
                        <strong>📊 Statistiques</strong>
                        <div class="stat-row">
                            <span>Mots:</span>
                            <span><?php echo $stats['words']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span>Caractères:</span>
                            <span><?php echo $stats['characters']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span>Paragraphes:</span>
                            <span><?php echo $stats['paragraphs']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span>Lecture:</span>
                            <span><?php echo $stats['reading_time_minutes']; ?> min</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>👥 Personnages</label>
                        <div class="characters-list">
                            <?php if ($characters): ?>
                                <?php foreach ($characters as $char): ?>
                                    <label style="display: flex; align-items: center; margin: 8px 0;">
                                        <input type="checkbox" name="characters[]" value="<?php echo $char['id']; ?>" 
                                               <?php echo in_array($char['id'], $characterIds) ? 'checked' : ''; ?>
                                               style="width: auto; margin-right: 8px;">
                                        <span><?php echo escapeHtml($char['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 0.85em; color: var(--text-secondary);">Aucun personnage créé.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($versions): ?>
                        <div class="version-history">
                            <strong style="color: var(--bronze); display: block; margin-bottom: 12px;">📝 Historique</strong>
                            <?php foreach ($versions as $v): ?>
                                <div class="version-item">
                                    <strong>v<?php echo $v['version_number']; ?></strong> - <?php echo $v['word_count']; ?> mots
                                    <div class="version-date"><?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <script>
        tinymce.init({
            selector: '#editor',
            language: 'fr_FR',
            height: 500,
            plugins: 'link image lists code help wordcount',
            toolbar: 'formatselect | bold italic underline strikethrough | bullist numlist | link image | undo redo | removeformat | code | help',
            content_css: false,
            skin: 'oxide',
            content_style: `
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    color: #1a1410;
                    line-height: 1.6;
                }
                p { margin: 0.75em 0; }
            `,
            menubar: 'file edit view insert format tools help',
            statusbar: true,
            branding: false
        });

        // Character selection
        document.querySelectorAll('input[name="characters[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                // Handle character linking (we'll implement this after save)
                console.log('Character selected:', this.value, this.checked);
            });
        });
    </script>
</body>
</html>
