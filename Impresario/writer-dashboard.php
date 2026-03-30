<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/writer-functions.php';

requireLogin();

$userId = getCurrentUserId();
$username = getCurrentUsername();
$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    header('Location: dashboard.php');
    exit;
}

$project = getProject($pdo, $projectId, $userId);
if (!$project) {
    die('Accès refusé');
}

$stats = getProjectStats($pdo, $projectId);
$writingStatus = getProjectWritingStatus($pdo, $projectId);
$characters = getProjectCharacters($pdo, $projectId);
$writingNotes = getProjectWritingNotes($pdo, $projectId);

// Handle export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    $format = $_POST['format'] ?? 'pdf';
    
    if ($format === 'pdf') {
        header('Location: export-pdf.php?id=' . $projectId);
    } elseif ($format === 'docx') {
        header('Location: export-docx.php?id=' . $projectId);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($project['title']); ?> - Dashboard Écrivain - Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .writer-dashboard {
            padding: calc(40px * var(--size-scale));
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 20px;
        }

        .dashboard-title {
            flex: 1;
        }

        .dashboard-title h1 {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            color: var(--text-primary);
        }

        .project-subtitle {
            color: var(--text-secondary);
            font-size: 1em;
            font-style: italic;
        }

        .dashboard-export {
            display: flex;
            gap: 12px;
        }

        .dashboard-export .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-pdf {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .btn-docx {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .btn-docx:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: var(--bronze);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 12px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9em;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            color: var(--bronze);
            margin-bottom: 8px;
        }

        .stat-detail {
            font-size: 0.85em;
            color: var(--text-secondary);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--bg-primary);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 12px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            transition: width 0.3s ease;
        }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
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
            font-size: 1.4em;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-draft {
            background: rgba(241, 196, 15, 0.2);
            color: #f39c12;
        }

        .badge-first-read {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .badge-finalized {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .chapter-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chapter-item {
            background: var(--bg-tertiary);
            border-left: 3px solid var(--bronze);
            padding: 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .chapter-item:hover {
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .chapter-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .chapter-stats {
            display: flex;
            gap: 20px;
            font-size: 0.85em;
            color: var(--text-secondary);
        }

        .character-card {
            background: var(--bg-tertiary);
            border-left: 3px solid;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .character-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .character-role {
            font-size: 0.85em;
            color: var(--text-secondary);
        }

        .note-card {
            background: var(--bg-tertiary);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-left: 3px solid var(--bronze);
        }

        .note-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95em;
            margin-bottom: 4px;
        }

        .note-type {
            font-size: 0.75em;
            background: var(--border-color);
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            margin-bottom: 8px;
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .dashboard-export {
                width: 100%;
            }

            .dashboard-export .btn {
                flex: 1;
            }
        }

        .chapter-item a {
            color: inherit;
            text-decoration: none;
        }

        .quick-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .quick-action:hover {
            background: var(--border-color);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="navbar-container">
        <a href="project.php?id=<?php echo $projectId; ?>" class="logo-link">← Affichage Standard</a>
        <span class="page-title">📊 Dashboard Écrivain</span>
        <span class="username"><?php echo escapeHtml($username); ?></span>
    </div>

    <div class="writer-dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1><?php echo escapeHtml($project['title']); ?></h1>
                <p class="project-subtitle"><?php echo escapeHtml($project['synopsis'] ?? $project['description']); ?></p>
            </div>
            <form method="POST" class="dashboard-export" style="flex: 0 0 auto;">
                <input type="hidden" name="action" value="export">
                <button type="submit" name="format" value="pdf" class="btn btn-pdf" title="Exporter en PDF">
                    📄 PDF
                </button>
                <button type="submit" name="format" value="docx" class="btn btn-docx" title="Exporter en Word">
                    📖 DOCX
                </button>
            </form>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">✍️</div>
                <div class="stat-label">Mots écrits</div>
                <div class="stat-value"><?php echo number_format($stats['current_word_count']); ?></div>
                <?php if ($stats['target_word_count']): ?>
                    <div class="stat-detail"><?php echo $stats['progress_percent']; ?>% de l'objectif</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($stats['progress_percent'], 100); ?>%;"></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-label">Chapitres</div>
                <div class="stat-value"><?php echo $stats['intrigue_count']; ?></div>
                <div class="stat-detail"><?php echo $stats['scene_count']; ?> scènes total</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Personnages</div>
                <div class="stat-value"><?php echo $stats['character_count']; ?></div>
                <div class="stat-detail">Dans le projet</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-label">État de rédaction</div>
                <div style="font-size: 0.95em; line-height: 1.6;">
                    <div><span class="status-badge badge-draft"><?php echo $writingStatus['draft_count']; ?></span> Brouillons</div>
                    <div><span class="status-badge badge-first-read"><?php echo $writingStatus['first_read_count']; ?></span> En relecture</div>
                    <div><span class="status-badge badge-finalized"><?php echo $writingStatus['finalized_count']; ?></span> Finalisés</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Chapters & Scenes -->
            <div class="section">
                <div class="section-header">
                    <h2>📚 Chapitres & Scènes</h2>
                </div>
                <div class="chapter-list">
                    <?php 
                    $intrigues = getIntrigues($pdo, $projectId);
                    foreach ($intrigues as $i => $intrigue):
                        $iStats = getIntrigueStats($pdo, $intrigue['id']);
                        $elements = $pdo->prepare('SELECT * FROM element WHERE intrigue_id = :id ORDER BY position');
                        $elements->execute([':id' => $intrigue['id']]);
                        $scenes = $elements->fetchAll();
                    ?>
                        <div class="chapter-item">
                            <div class="chapter-title">
                                📖 Chapitre <?php echo ($i + 1); ?>: <?php echo escapeHtml($intrigue['title']); ?>
                            </div>
                            <div class="chapter-stats">
                                <span>📊 <?php echo $iStats['word_count']; ?> mots</span>
                                <span>📝 <?php echo $iStats['scene_count']; ?> scènes</span>
                                <span>✅ <?php echo $iStats['finalized_count']; ?> finalisées</span>
                            </div>
                            <?php if ($scenes): ?>
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); font-size: 0.9em;">
                                    <?php foreach ($scenes as $scene): ?>
                                        <a href="scene-editor.php?project=<?php echo $projectId; ?>&intrigue=<?php echo $intrigue['id']; ?>&element=<?php echo $scene['id']; ?>" class="quick-action" style="display: block; margin-bottom: 8px;">
                                            <span><?php echo escapeHtml($scene['title']); ?></span>
                                            <span style="margin-left: auto; color: var(--text-secondary);"><?php echo $scene['word_count']; ?>w</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar: Characters & Notes -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Characters -->
                <div class="section">
                    <div class="section-header">
                        <h2>👥 Personnages</h2>
                    </div>
                    <div>
                        <?php if ($characters): ?>
                            <?php foreach ($characters as $char): ?>
                                <div class="character-card" style="border-left-color: <?php echo escapeHtml($char['color_hex'] ?? '#C9A87C'); ?>;">
                                    <div class="character-name"><?php echo escapeHtml($char['name']); ?></div>
                                    <?php if ($char['role']): ?>
                                        <div class="character-role">📌 <?php echo escapeHtml($char['role']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-secondary); font-style: italic;">Aucun personnage créé</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Writing Notes -->
                <div class="section">
                    <div class="section-header">
                        <h2>📌 Notes</h2>
                    </div>
                    <div>
                        <?php if ($writingNotes): ?>
                            <?php foreach (array_slice($writingNotes, 0, 5) as $note): ?>
                                <div class="note-card">
                                    <div><span class="note-type"><?php echo escapeHtml($note['note_type']); ?></span></div>
                                    <div class="note-title"><?php echo escapeHtml($note['title']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-secondary); font-style: italic;">Aucune note</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-update on character/note actions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Writer dashboard loaded');
        });
    </script>
</body>
</html>
