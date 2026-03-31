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
    'general' => 'Général',
    'plot' => 'Intrigue',
    'character' => 'Personnage',
    'world' => 'Monde',
    'timeline' => 'Timeline',
    'idea' => 'Idée'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes d'écriture - Impresario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
    <div class="navbar-container">
        <a href="project.php?id=<?php echo $projectId; ?>" class="logo-link">← Retour</a>
        <span class="page-title"><i class="fas fa-file-alt"></i> Notes d'écriture</span>
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
                                    <button type="submit" class="note-action-btn" title="Épingler"><?php echo $note['is_pinned'] ? '<i class="fas fa-thumbtack" style="color: var(--bronze);"></i>' : '<i class="fas fa-thumbtack" style="opacity: 0.5;"></i>'; ?></button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete-note">
                                    <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" class="note-action-btn" title="Supprimer" onclick="return confirm('Supprimer?');"><i class="fas fa-trash"></i></button>
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
                <div class="empty-state-icon"><i class="fas fa-file-alt" style="font-size: 3em; color: #bbb;"></i></div>
                <div class="empty-state-text">Aucune note pour cette catégorie. Créez votre première note!</div>
            </div>
        <?php endif; ?>
    </div>
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
