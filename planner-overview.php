<?php
/**
 * Planner Overview - Vue multi-intrigue
 * Affiche toutes les intrigues du projet côte à côte
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

// Récupérer toutes les intrigues du projet
$stmt = $pdo->prepare("
    SELECT * FROM intrigue 
    WHERE project_id = ? 
    ORDER BY position ASC
");
$stmt->execute([$projectId]);
$intrigues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les couleurs du projet
$projectColors = getProjectColors($pdo, $projectId);
$gradient = generateProjectGradient($pdo, $projectId, 'array');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planner Overview - <?php echo htmlspecialchars($project['title']); ?> - Impresario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .planner-overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .planner-overview-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .intrigues-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 20px;
            padding: 20px;
            background: var(--bg-primary);
            border-radius: 8px;
        }

        .intrigue-column {
            background: var(--bg-secondary);
            border: 2px solid var(--border-light);
            border-radius: 10px;
            padding: 0;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 200px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .intrigue-column:hover {
            border-color: var(--bronze);
            box-shadow: 0 0 15px rgba(201, 168, 124, 0.2);
        }

        .intrigue-column-header {
            background: linear-gradient(135deg, var(--bronze-light) 0%, var(--bronze-dark) 100%);
            color: #FFFBF0;
            padding: 16px;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
            border-radius: 8px 8px 0 0;
        }

        .intrigue-column-header h3 {
            margin: 0 0 4px 0;
            font-size: 1.1em;
        }

        .intrigue-column-info {
            font-size: 0.85em;
            opacity: 0.9;
            margin-top: 4px;
        }

        .intrigue-column-content {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .intrigue-column-content:empty::after {
            content: 'Aucune scène';
            text-align: center;
            color: var(--text-tertiary);
            padding: 40px 12px;
            font-size: 0.9em;
        }

        .intrigue-column.drag-over {
            background: var(--bg-tertiary);
            border-color: var(--bronze);
            box-shadow: 0 0 20px rgba(201, 168, 124, 0.3),
                        inset 0 0 20px rgba(201, 168, 124, 0.1);
        }

        .intrigue-column-footer {
            padding: 12px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 8px;
        }

        .intrigue-column-footer .btn {
            flex: 1;
            font-size: 0.85em;
            padding: 8px 10px;
        }

        .column-scene-item {
            padding: 12px;
            background: var(--bg-primary);
            border-left: 4px solid var(--bronze);
            border-radius: 6px;
            cursor: grab;
            transition: all 0.2s ease;
            user-select: none;
        }

        .intrigue-column-footer .btn {
            cursor: pointer !important;
        }

        .column-scene-item:hover,
        .btn:hover {
            cursor: pointer;
        }

        .column-scene-item:active {
            cursor: grabbing;
        }

        .column-scene-item:hover {
            background: var(--bg-tertiary);
            transform: translateX(4px);
            box-shadow: 0 0 12px rgba(201, 168, 124, 0.2);
        }

        .column-scene-item.dragging {
            opacity: 0.5;
            background: var(--bg-secondary);
        }

        .column-scene-title {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 4px;
            font-size: 0.95em;
        }

        .column-scene-desc {
            font-size: 0.8em;
            color: #000;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .column-scene-type {
            display: inline-block;
            font-size: 0.7em;
            padding: 2px 6px;
            background: var(--bronze);
            color: white;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .column-scene-type.marqueur {
            background: var(--warning);
        }

        .planner-overview-container {
            padding: 20px;
            min-height: 100vh;
            background: var(--bg-primary);
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="project.php?id=<?php echo $projectId; ?>" style="color: var(--text-primary); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="logo">Impresario - Vue Globale</h1>
            </div>
            <div class="navbar-user">
                <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                <a href="logout.php" class="btn btn-small">Déconnexion</a>
            </div>
        </div>
    </header>

    <main class="planner-overview-container">
        <div class="planner-overview-header">
            <div>
                <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                <p style="color: var(--text-secondary); margin: 0;">
                    <?php echo count($intrigues); ?> intrigue(s) • Vue d'ensemble
                </p>
            </div>
            <div class="planner-overview-actions">
                <button class="btn btn-primary btn-small" onclick="addIntrigueGlobal()">
                    <i class="fas fa-plus"></i> Intrigue
                </button>
                <a href="planner.php?project=<?php echo $projectId; ?>" class="btn btn-secondary btn-small" title="Vue par intrigue">
                    <i class="fas fa-bars"></i> Détail
                </a>
            </div>
        </div>

        <?php if (empty($intrigues)): ?>
            <div class="empty-state-full">
                <p>Commencez par créer une intrigue</p>
                <button class="btn btn-primary" onclick="addIntrigueGlobal()">
                    + Créer une intrigue
                </button>
            </div>
        <?php else: ?>
            <div class="intrigues-columns">
                <?php foreach ($intrigues as $intrigue):
                    $stmt = $pdo->prepare("
                        SELECT * FROM element 
                        WHERE intrigue_id = ? AND type IN ('scene', 'marqueur')
                        ORDER BY position ASC
                    ");
                    $stmt->execute([$intrigue['id']]);
                    $elements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM element WHERE intrigue_id = ? AND type IN ('scene', 'marqueur')");
                    $stmt->execute([$intrigue['id']]);
                    $elementCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                ?>
                    <div class="intrigue-column" data-intrigue-id="<?php echo $intrigue['id']; ?>" data-project-id="<?php echo $projectId; ?>">
                        <div class="intrigue-column-header">
                            <h3><?php echo htmlspecialchars($intrigue['title']); ?></h3>
                            <div class="intrigue-column-info">
                                <?php echo $elementCount; ?> scène(s)
                            </div>
                        </div>

                        <div class="intrigue-column-content">
                            <?php foreach ($elements as $element): ?>
                                <div class="column-scene-item" draggable="true" data-element-id="<?php echo $element['id']; ?>" data-intrigue-id="<?php echo $intrigue['id']; ?>">
                                    <div class="column-scene-title">
                                        <?php echo htmlspecialchars($element['title']); ?>
                                    </div>
                                    <?php if ($element['description']): ?>
                                        <div class="column-scene-desc">
                                            <?php echo htmlspecialchars(substr($element['description'], 0, 80)); ?>
                                            <?php if (strlen($element['description']) > 80) echo '…'; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="column-scene-type <?php echo $element['type']; ?>">
                                        <?php echo $element['type'] === 'marqueur' ? '🔖 Marqueur' : '🎬 Scène'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="intrigue-column-footer">
                            <button class="btn btn-primary" onclick="addSceneToIntrigue(<?php echo $intrigue['id']; ?>, <?php echo $projectId; ?>)">
                                + Scène
                            </button>
                            <a href="planner.php?project=<?php echo $projectId; ?>&intrigue=<?php echo $intrigue['id']; ?>" class="btn btn-secondary">
                                Détail
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Drag & Drop multi-intrigue
        let draggedElement = null;
        let draggedFrom = null;

        document.querySelectorAll('.column-scene-item').forEach(item => {
            item.addEventListener('dragstart', function(e) {
                draggedElement = this;
                draggedFrom = this.getAttribute('data-intrigue-id');
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
                draggedElement = null;
                document.querySelectorAll('.intrigue-column').forEach(col => col.classList.remove('drag-over'));
            });
        });

        document.querySelectorAll('.intrigue-column').forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
            });

            column.addEventListener('dragleave', function(e) {
                if (e.target === this) {
                    this.classList.remove('drag-over');
                }
            });

            column.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('drag-over');

                if (!draggedElement) return;

                const elementId = draggedElement.getAttribute('data-element-id');
                const newIntrigueId = this.getAttribute('data-intrigue-id');
                const projectId = this.getAttribute('data-project-id');

                if (draggedFrom === newIntrigueId) return;

                // Move element to new intrigue
                moveElementToIntrigue(elementId, newIntrigueId, projectId);
            });
        });

        function moveElementToIntrigue(elementId, intrigueId, projectId) {
            fetch('api/elements.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update-element&element_id=${elementId}&intrigue_id=${intrigueId}`
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible de déplacer'));
                }
            })
            .catch(err => console.error('Error:', err));
        }

        function addIntrigueGlobal() {
            const title = prompt('Titre de la nouvelle intrigue:');
            if (!title) return;

            fetch('api/intrigue.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=create-intrigue&project_id=<?php echo $projectId; ?>&title=${encodeURIComponent(title)}`
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible de créer'));
                }
            })
            .catch(err => console.error('Error:', err));
        }

        function addSceneToIntrigue(intrigueId, projectId) {
            const title = prompt('Titre de la scène:');
            if (!title) return;

            fetch('api/elements.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=create-element&intrigue_id=${intrigueId}&type=scene&title=${encodeURIComponent(title)}`
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible de créer'));
                }
            })
            .catch(err => console.error('Error:', err));
        }
    </script>
</body>
</html>
