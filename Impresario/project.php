<?php
include 'includes/config.php';
include 'includes/functions.php';

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

// Ajouter une intrigue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add-intrigue') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Le titre de l\'intrigue est obligatoire.';
    } else {
        $intrigueId = createIntrigue($pdo, $projectId, $title, $description);
        if ($intrigueId) {
            header('Location: intrigue.php?id=' . $intrigueId . '&project=' . $projectId);
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
$stmt = $pdo->prepare('SELECT id, name, role, color_hex FROM character WHERE project_id = :project_id ORDER BY role DESC, name ASC LIMIT 8');
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="logo-link">← Impresario</a>
            <h1 class="page-title"><?php echo htmlspecialchars($project['title']); ?></h1>
            <div class="navbar-actions">
                <a href="project-timeline.php?id=<?php echo $projectId; ?>" class="btn btn-small" title="Affichage timeline">🎨 Vue Timeline</a>
                <a href="logout.php" class="btn btn-small">Déconnexion</a>
            </div>
        </div>
    </header>

    <!-- Writer Mode Toolbar -->
    <style>
        .writer-mode-toolbar {
            background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            padding: calc(16px * var(--size-scale));
            margin: 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 4px 15px rgba(201, 168, 124, 0.2);
        }

        .writer-toolbar-title {
            color: #FFFBF0;
            font-weight: 700;
            font-size: 1.1em;
        }

        .writer-toolbar-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .writer-toolbar-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            background: rgba(255, 251, 240, 0.2);
            border: 1px solid rgba(255, 251, 240, 0.4);
            border-radius: 8px;
            text-decoration: none;
            color: #FFFBF0;
            font-size: 0.95em;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .writer-toolbar-link:hover {
            background: rgba(255, 251, 240, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .writer-mode-toolbar {
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }

            .writer-toolbar-links {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="writer-mode-toolbar">
        <span class="writer-toolbar-title">✍️ Mode Écrivain</span>
        <div class="writer-toolbar-links">
            <a href="writer-dashboard.php?id=<?php echo $projectId; ?>" class="writer-toolbar-link" title="Dashboard avec statistiques">
                📊 Dashboard
            </a>
            <a href="characters.php?id=<?php echo $projectId; ?>" class="writer-toolbar-link" title="Gérer les personnages">
                👥 Personnages
            </a>
            <a href="writing-notes.php?id=<?php echo $projectId; ?>" class="writer-toolbar-link" title="Notes d'écriture">
                📝 Notes
            </a>
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
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Progression</h3>
                        <span class="stat-percentage"><?php echo round($progressPercent); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%"></div>
                    </div>
                    <p class="stat-text"><?php echo number_format($totalWords); ?> / <?php echo number_format($targetWords); ?> mots</p>
                </div>

                <!-- Card Chapitres -->
                <div class="stat-card">
                    <h3>📖 Chapitres</h3>
                    <p class="stat-number"><?php echo count($intrigues); ?></p>
                    <p class="stat-text"><?php echo count($intrigues) === 1 ? 'Chapitre' : 'Chapitres'; ?> en cours</p>
                </div>

                <!-- Card Scènes -->
                <div class="stat-card">
                    <h3>🎬 Scènes</h3>
                    <p class="stat-number"><?php echo $numScenes; ?></p>
                    <p class="stat-text"><?php echo $numScenes === 1 ? 'Scène écrite' : 'Scènes écrites'; ?></p>
                </div>

                <!-- Card Personnages -->
                <div class="stat-card">
                    <h3>👥 Personnages</h3>
                    <p class="stat-number"><?php echo count($characters); ?></p>
                    <p class="stat-text"><?php echo count($characters) === 1 ? 'Personnage' : 'Personnages'; ?> créés</p>
                </div>
            </div>
        </section>

        <!-- SECTION TIMELINE -->
        <?php if (!empty($timelineEvents)): ?>
        <section class="timeline-section">
            <div class="section-header">
                <h2>⏱️ Chronologie</h2>
                <a href="writer-dashboard.php?id=<?php echo $projectId; ?>#timeline" class="link-small">Voir tout →</a>
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
                <h2>📚 Chapitres & Intrigues</h2>
                <button class="btn btn-primary btn-small" data-modal-trigger="add-intrigue-modal">+ Nouveau</button>
            </div>
            
            <?php if (empty($intrigues)): ?>
                <div class="empty-state-full">
                    <p>Commencez par créer une intrigue</p>
                </div>
            <?php else: ?>
                <div class="intrigues-grid">
                    <?php foreach ($intrigues as $idx => $intrigue): ?>
                        <a href="intrigue.php?id=<?php echo $intrigue['id']; ?>&project=<?php echo $projectId; ?>" class="intrigue-card">
                            <div class="intrigue-number">#<?php echo $idx + 1; ?></div>
                            <h3><?php echo htmlspecialchars($intrigue['title']); ?></h3>
                            <?php if ($intrigue['description']): ?>
                                <p><?php echo htmlspecialchars(substr($intrigue['description'], 0, 80)) . (strlen($intrigue['description']) > 80 ? '…' : ''); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="project-content-grid">
            <!-- SECTION PERSONNAGES -->
            <?php if (!empty($characters)): ?>
            <section class="characters-section">
                <div class="section-header">
                    <h3>👥 Personnages Principaux</h3>
                    <a href="characters.php?id=<?php echo $projectId; ?>" class="link-small">Voir tous →</a>
                </div>
                <div class="characters-grid-small">
                    <?php foreach ($characters as $char): ?>
                        <a href="character-editor.php?id=<?php echo $char['id']; ?>&project=<?php echo $projectId; ?>" class="character-badge">
                            <div class="char-circle" style="background-color: <?php echo htmlspecialchars($char['color_hex']); ?>"></div>
                            <h4><?php echo htmlspecialchars($char['name']); ?></h4>
                            <p class="char-role"><?php echo ucfirst($char['role']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- SECTION NOTES ÉPINGLÉES -->
            <?php if (!empty($pinnedNotes)): ?>
            <section class="pinned-notes-section">
                <div class="section-header">
                    <h3>📌 Notes Épinglées</h3>
                    <a href="writing-notes.php?id=<?php echo $projectId; ?>" class="link-small">Toutes les notes →</a>
                </div>
                <div class="notes-list-mini">
                    <?php foreach ($pinnedNotes as $note): ?>
                        <div class="note-mini">
                            <span class="note-type-badge"><?php echo ucfirst($note['note_type']); ?></span>
                            <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                            <p><?php echo htmlspecialchars(substr(strip_tags($note['content']), 0, 100)) . '…'; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Modal pour ajouter une intrigue -->
    <div id="add-intrigue-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter une intrigue</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="add-intrigue">
                <div class="form-group">
                    <label for="intrigue-title">Titre</label>
                    <input type="text" id="intrigue-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="intrigue-description">Description (optionnelle)</label>
                    <textarea id="intrigue-description" name="description" rows="4"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal pour éditer le projet -->
    <div id="edit-project-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier le projet</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="edit-project">
                <div class="form-group">
                    <label for="edit-project-title">Titre</label>
                    <input type="text" id="edit-project-title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-project-description">Description</label>
                    <textarea id="edit-project-description" name="description" rows="4"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal pour supprimer le projet -->
    <div id="delete-project-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Supprimer le projet</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <div class="modal-form">
                <p>Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete-project">
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/dynamic-sizer.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
