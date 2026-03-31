<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/palette-functions.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    header('Location: dashboard.php');
    exit;
}

$project = getProject($pdo, $projectId, $userId);
if (!$project) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Gérer les exports
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    $format = $_POST['format'] ?? 'pdf';
    header('Location: api/export.php?project_id=' . $projectId . '&format=' . $format);
    exit;
}

// Ajouter une intrigue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add-intrigue') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Le titre de l\'intrigue est obligatoire.';
    } else {
        $intrigueId = createIntrigue($pdo, $projectId, $title, $description);
        if ($intrigueId) {
            header('Location: planner.php?project=' . $projectId . '&intrigue=' . $intrigueId);
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit-project') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Le titre du projet est obligatoire.';
    } else {
        $result = updateProject($pdo, $projectId, $userId, $title, $description);
        if ($result) {
            $success = 'Projet modifié avec succès.';
            $project = getProject($pdo, $projectId, $userId);
        } else {
            $error = 'Erreur lors de la modification du projet.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete-project') {
    $result = deleteProject($pdo, $projectId, $userId);
    if ($result) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Erreur lors de la suppression du projet.';
    }
}

// Récupérer les données du projet
$intrigues = getIntrigues($pdo, $projectId);

// Récupérer les stats
$stats = getProjectWritingStatus($pdo, $projectId);
$totalWords = $stats['total_words'] ?? 0;
$targetWords = $project['target_word_count'] ?? 100000;
$progressPercent = min(100, ($totalWords / $targetWords) * 100);
$numScenes = $stats['total_scenes'] ?? 0;

// Récupérer les personnages
$stmt = $pdo->prepare('SELECT id, name, role, description, color_hex FROM character WHERE project_id = :project_id ORDER BY role DESC, name ASC LIMIT 8');
$stmt->execute([':project_id' => $projectId]);
$characters = $stmt->fetchAll();

// Récupérer les notes épinglées
$stmt = $pdo->prepare('SELECT id, note_type, title, content FROM writing_note WHERE project_id = :project_id AND is_pinned = true ORDER BY created_at DESC LIMIT 3');
$stmt->execute([':project_id' => $projectId]);
$pinnedNotes = $stmt->fetchAll();

// Récupérer la timeline
$stmt = $pdo->prepare('SELECT id, event_date, title, description FROM timeline_event WHERE project_id = :project_id ORDER BY event_date ASC LIMIT 5');
$stmt->execute([':project_id' => $projectId]);
$timelineEvents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Impresario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="logo-link">← Impresario</a>
            <h1 class="page-title"><?php echo htmlspecialchars($project['title']); ?></h1>
            <div class="navbar-actions">
                <a href="planner-overview.php?project=<?php echo $projectId; ?>" class="btn btn-small" title="Vue globale"><i class="fas fa-th"></i> Vue Globale</a>
                <a href="planner.php?project=<?php echo $projectId; ?>" class="btn btn-small" title="Planner détail"><i class="fas fa-bars"></i> Détail</a>
                <a href="logout.php" class="btn btn-small">Déconnexion</a>
            </div>
        </div>
    </header>

    <!-- Writer Mode Toolbar -->
    <div class="page-toolbar">
        <h3 class="toolbar-title"><i class="fas fa-pen"></i> Mode Écrivain</h3>
        <div class="toolbar-links">
            <a href="characters.php?id=<?php echo $projectId; ?>" class="toolbar-link" title="Gérer les personnages">
                <i class="fas fa-users"></i> Personnages
            </a>
            <a href="writing-notes.php?id=<?php echo $projectId; ?>" class="toolbar-link" title="Notes d'écriture">
                <i class="fas fa-file-alt"></i> Notes
            </a>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="export">
                <input type="hidden" name="format" value="pdf">
                <button type="submit" class="toolbar-link" title="Exporter en PDF">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="export">
                <input type="hidden" name="format" value="docx">
                <button type="submit" class="toolbar-link" title="Exporter en DOCX">
                    📋 Word
                </button>
            </form>
            <div class="toolbar-actions">
                <button class="btn btn-primary btn-small" data-modal-trigger="edit-project-modal" title="Modifier le projet">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button class="btn btn-danger btn-small" data-modal-trigger="delete-project-modal" title="Supprimer le projet">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
    
    <main class="project-view-enhanced">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- SECTION STATS -->
        <section class="project-stats-section">
            <h2>Aperçu du Projet</h2>
            <div class="stats-grid">
                <!-- Card Progression -->
                <a href="dashboard.php?view=writer&project=<?php echo $projectId; ?>" class="stat-card stat-card-clickable">
                    <div class="stat-header">
                        <h3>Progression</h3>
                        <span class="stat-percentage"><?php echo round($progressPercent); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%"></div>
                    </div>
                    <p class="stat-text"><?php echo number_format($totalWords); ?> / <?php echo number_format($targetWords); ?> mots</p>
                </a>

                <!-- Card Chapitres -->
                <a href="planner-overview.php?project=<?php echo $projectId; ?>" class="stat-card stat-card-clickable">
                    <h3><i class="fas fa-book"></i> Chapitres</h3>
                    <p class="stat-number"><?php echo count($intrigues); ?></p>
                    <p class="stat-text"><?php echo count($intrigues) === 1 ? 'Chapitre' : 'Chapitres'; ?> en cours</p>
                </a>

                <!-- Card Scènes -->
                <a href="planner-overview.php?project=<?php echo $projectId; ?>" class="stat-card stat-card-clickable">
                    <h3><i class="fas fa-film"></i> Scènes</h3>
                    <p class="stat-number"><?php echo $numScenes; ?></p>
                    <p class="stat-text"><?php echo $numScenes === 1 ? 'Scène écrite' : 'Scènes écrites'; ?></p>
                </a>

                <!-- Card Personnages -->
                <a href="characters.php?id=<?php echo $projectId; ?>" class="stat-card stat-card-clickable">
                    <h3><i class="fas fa-users"></i> Personnages</h3>
                    <p class="stat-number"><?php echo count($characters); ?></p>
                    <p class="stat-text"><?php echo count($characters) === 1 ? 'Personnage' : 'Personnages'; ?> créés</p>
                </a>
            </div>
        </section>

        <!-- SECTION TIMELINE -->
        <?php if (!empty($timelineEvents)): ?>
        <section class="timeline-section">
            <div class="section-header">
                <h2>⏱️ Chronologie</h2>
                <a href="planner.php?project=<?php echo $projectId; ?>" class="link-small">Voir tout →</a>
            </div>
            <div class="timeline-list">
                <?php foreach ($timelineEvents as $event): ?>
                <div class="timeline-item">
                    <div class="timeline-date"><?php echo date('d/m/Y', strtotime($event['event_date'])); ?></div>
                    <div class="timeline-content">
                        <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                        <p><?php echo htmlspecialchars($event['description']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- SECTION INTRIGUES EN CARDS -->
        <section class="intrigues-section">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> Chapitres & Intrigues</h2>
                <button class="btn btn-primary btn-small" data-modal-trigger="add-intrigue-modal">+ Nouveau</button>
            </div>
            
            <?php if (empty($intrigues)): ?>
                <div class="empty-state-full">
                    <p>Commencez par créer une intrigue</p>
                </div>
            <?php else: ?>
                <div class="intrigues-grid">
                    <?php foreach ($intrigues as $idx => $intrigue): ?>
                        <a href="planner.php?project=<?php echo $projectId; ?>&intrigue=<?php echo $intrigue['id']; ?>" class="intrigue-card">
                            <div class="intrigue-number">#<?php echo $idx + 1; ?></div>
                            <h3><?php echo htmlspecialchars($intrigue['title']); ?></h3>
                            <?php if ($intrigue['description']): ?>
                                <p><?php echo htmlspecialchars(substr($intrigue['description'], 0, 100)); ?>
                                   <?php if (strlen($intrigue['description']) > 100) echo '…'; ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- SECTION PERSONNAGES -->
        <?php if (!empty($characters)): ?>
        <section class="characters-section">
            <div class="section-header">
                <h2><i class="fas fa-users"></i> Personnages Principaux</h2>
                <a href="characters.php?id=<?php echo $projectId; ?>" class="link-small">Voir tous →</a>
            </div>
            
            <div class="characters-preview">
                <?php foreach ($characters as $char): ?>
                    <a href="character-editor.php?id=<?php echo $char['id']; ?>&project=<?php echo $projectId; ?>" class="character-card">
                        <div class="character-card-avatar" style="background-color: <?php echo htmlspecialchars($char['color_hex']); ?>">
                            <span class="character-initials"><?php echo mb_substr($char['name'], 0, 1); ?></span>
                        </div>
                        <div class="character-card-content">
                            <p class="character-card-name"><?php echo htmlspecialchars($char['name']); ?></p>
                            <?php if (!empty($char['role'])): ?>
                                <p class="character-card-role"><?php echo htmlspecialchars($char['role']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($char['description'])): ?>
                                <p class="character-card-description"><?php echo htmlspecialchars(substr($char['description'], 0, 60)); ?><?php if (strlen($char['description']) > 60) echo '…'; ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- SECTION NOTES ÉPINGLÉES -->
        <?php if (!empty($pinnedNotes)): ?>
        <section class="pinned-notes-section">
            <div class="section-header">
                <h2><i class="fas fa-thumbtack"></i> Notes Épinglées</h2>
                <a href="writing-notes.php?id=<?php echo $projectId; ?>" class="link-small">Toutes les notes →</a>
            </div>
            
            <div class="pinned-notes-grid">
                <?php foreach ($pinnedNotes as $note): ?>
                    <div class="pinned-note-card">
                        <span class="note-type-badge"><?php echo ucfirst($note['note_type']); ?></span>
                        <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                        <p><?php echo htmlspecialchars(substr(strip_tags($note['content']), 0, 80)); ?>...</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Modal Ajouter Intrigue -->
    <div id="add-intrigue-modal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ajouter une intrigue</h2>
                <button class="modal-close" data-modal-close="add-intrigue-modal">&times;</button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="add-intrigue">
                <div class="form-group">
                    <label for="intrigue-title">Titre:</label>
                    <input type="text" id="intrigue-title" name="title" required placeholder="Titre de l'intrigue">
                </div>
                <div class="form-group">
                    <label for="intrigue-description">Description:</label>
                    <textarea id="intrigue-description" name="description" rows="4" placeholder="Description..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close="add-intrigue-modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Éditer Projet -->
    <div id="edit-project-modal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Modifier le projet</h2>
                <button class="modal-close" data-modal-close="edit-project-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="edit-project">
                    <div class="form-group">
                        <label for="project-title">Titre:</label>
                        <input type="text" id="project-title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="project-description">Description:</label>
                        <textarea id="project-description" name="description" rows="4"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close="edit-project-modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Sauvegarder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Supprimer Projet -->
    <div id="delete-project-modal" class="modal hidden">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h2>Supprimer le projet</h2>
                <button class="modal-close" data-modal-close="delete-project-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete-project">
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close="delete-project-modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/dynamic-sizer.js"></script>
    
    <script>
        // Gestion des modales
        document.querySelectorAll('[data-modal-trigger]').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal-trigger');
                document.getElementById(modalId).classList.remove('hidden');
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal-close');
                document.getElementById(modalId).classList.add('hidden');
            });
        });

        // Fermer modale en cliquant en dehors
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
