<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/writer-functions.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    header('Location: dashboard.php');
    exit;
}

$project = getProject($pdo, $projectId, $userId);
if (!$project) {
    die('Accès refusé');
}

$error = '';
$success = '';
$filter = $_GET['filter'] ?? null;

// Handle create note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create-note') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'general';
    $content = $_POST['content'] ?? '';
    
    if (empty($title)) {
        $error = 'Le titre est obligatoire.';
    } else {
        $noteId = createWritingNote($pdo, $projectId, $title, $content, $type);
        if ($noteId) {
            $success = 'Note créée!';
        } else {
            $error = 'Erreur lors de la création.';
        }
    }
}

// Handle delete note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete-note') {
    $noteId = $_POST['note_id'] ?? null;
    if ($noteId) {
        $stmt = $pdo->prepare('DELETE FROM writing_note WHERE id = :id AND project_id = :project_id');
        $stmt->execute([':id' => $noteId, ':project_id' => $projectId]);
        $success = 'Note supprimée.';
    }
}

// Handle toggle pin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle-pin') {
    $noteId = $_POST['note_id'] ?? null;
    if ($noteId) {
        $stmt = $pdo->prepare('UPDATE writing_note SET is_pinned = NOT is_pinned WHERE id = :id AND project_id = :project_id');
        $stmt->execute([':id' => $noteId, ':project_id' => $projectId]);
    }
}

// Get notes
$notes = getProjectWritingNotes($pdo, $projectId, $filter);

$noteTypes = ['general', 'plot', 'character', 'world', 'timeline', 'idea'];
$noteTypeNames = [
    'general' => '📌 Général',
    'plot' => '📖 Intrigue',
    'character' => '👥 Personnage',
    'world' => '🌍 Monde',
    'timeline' => '⏱️ Timeline',
    'idea' => '💡 Idée'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes d'écriture - Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .notes-container {
            padding: calc(40px * var(--size-scale));
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 20px;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

       .filter-tab {
            padding: 8px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 0.9em;
        }

        .filter-tab:hover,
        .filter-tab.active {
            background: var(--border-color);
            border-color: var(--bronze);
            color: var(--bronze);
        }

        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .note-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            position: relative;
        }

        .note-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: var(--bronze);
        }

        .note-card.pinned {
            border-left: 4px solid var(--bronze);
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .note-type {
            font-size: 0.8em;
            background: var(--bg-tertiary);
            padding: 4px 8px;
            border-radius: 4px;
            color: var(--text-secondary);
        }

        .note-actions {
            display: flex;
            gap: 8px;
        }

        .note-action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
            opacity: 0.7;
            transition: all 0.2s ease;
        }

        .note-action-btn:hover {
            opacity: 1;
            transform: scale(1.2);
        }

        .note-title {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
            word-break: break-word;
        }

        .note-content {
            font-size: 0.95em;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 16px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .note-meta {
            font-size: 0.8em;
            color: var(--text-secondary);
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .create-note-form {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }

        .create-note-form .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .create-note-form .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 16px;
        }

        .empty-state-text {
            color: var(--text-secondary);
            font-size: 1.1em;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .notes-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .header-section {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="navbar-container">
        <a href="project.php?id=<?php echo $projectId; ?>" class="logo-link">← Retour</a>
        <span class="page-title">📝 Notes d'écriture</span>
        <span class="username"><?php echo escapeHtml(getCurrentUsername()); ?></span>
    </div>

    <div class="notes-container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escapeHtml($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeHtml($success); ?></div>
        <?php endif; ?>

        <!-- Create Note Form -->
        <div class="create-note-form">
            <h2 style="margin-top: 0; margin-bottom: 20px;">Créer une note</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create-note">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Titre *</label>
                        <input type="text" name="title" required placeholder="ex: Arc de l'après-climax">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type">
                            <?php foreach ($noteTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $noteTypeNames[$type]; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Contenu</label>
                    <textarea name="content" placeholder="Écrivez vos notes..."></textarea>
                </div>

                <button type="submit" class="btn">+ Créer</button>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?id=<?php echo $projectId; ?>" class="filter-tab <?php echo !$filter ? 'active' : ''; ?>">Toutes</a>
            <?php foreach ($noteTypes as $type): ?>
                <a href="?id=<?php echo $projectId; ?>&filter=<?php echo $type; ?>" class="filter-tab <?php echo $filter === $type ? 'active' : ''; ?>">
                    <?php echo $noteTypeNames[$type]; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Notes Grid -->
        <?php if ($notes): ?>
            <div class="notes-grid">
                <?php foreach ($notes as $note): ?>
                    <div class="note-card <?php echo $note['is_pinned'] ? 'pinned' : ''; ?>">
                        <div class="note-header">
                            <span class="note-type"><?php echo $noteTypeNames[$note['note_type']]; ?></span>
                            <div class="note-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle-pin">
                                    <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" class="note-action-btn" title="Épingler"><?php echo $note['is_pinned'] ? '📌' : '📍'; ?></button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete-note">
                                    <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" class="note-action-btn" title="Supprimer" onclick="return confirm('Supprimer?');">🗑️</button>
                                </form>
                            </div>
                        </div>

                        <div class="note-title"><?php echo escapeHtml($note['title']); ?></div>
                        
                        <?php if ($note['content']): ?>
                            <div class="note-content">
                                <?php echo nl2br(escapeHtml($note['content'])); ?>
                            </div>
                        <?php endif; ?>

                        <div class="note-meta">
                            <?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <div class="empty-state-text">Aucune note pour cette catégorie. Créez votre première note!</div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
