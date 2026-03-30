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

// Get all intrigues for this project
$stmt = $pdo->prepare('
    SELECT i.id, i.title, i.description, COUNT(e.id) as element_count
    FROM intrigue i
    LEFT JOIN element e ON i.id = e.intrigue_id
    WHERE i.project_id = ?
    GROUP BY i.id, i.title, i.description
    ORDER BY i.id
');
$stmt->execute([$projectId]);
$intrigues = $stmt->fetchAll();

// Get all elements and tags for this project
$projectElements = getProjectElements($pdo, $projectId);

// Get tags grouped by intrigue
$tagsData = [];
foreach ($intrigues as $intrigue) {
    $tagsData[$intrigue['id']] = getIntrigueTags($pdo, $intrigue['id']);
}

// Get dependencies
$dependenciesData = [];
foreach ($projectElements as $elem) {
    $dependenciesData[$elem['id']] = getElementDependenciesWithDetails($pdo, $elem['id']);
}

// Color mapping for tags
$tagColorMap = [];
foreach ($intrigues as $intrigue) {
    if (isset($tagsData[$intrigue['id']])) {
        foreach ($tagsData[$intrigue['id']] as $tag) {
            $tagColorMap[$tag['id']] = $tag['color'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Vue Timeline - Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="logo-link">← Impresario</a>
            <h1 class="page-title"><?php echo htmlspecialchars($project['title']); ?> - Vue Timeline</h1>
            <a href="project.php?id=<?php echo $projectId; ?>" class="btn btn-small">Vue Standard</a>
            <a href="logout.php" class="btn btn-small">Déconnexion</a>
        </div>
    </header>

    <main class="intrigue-view" style="padding: 0;">
        <div class="timeline-container">
            <?php if (!empty($intrigues)): ?>
                <?php foreach ($intrigues as $intrigue):
                    // Get elements for this intrigue
                    $intrigueElements = array_filter($projectElements, function($e) use ($intrigue) {
                        return $e['intrigue_id'] == $intrigue['id'] && !empty($e['id']);
                    });
                    $intrigueElements = array_values($intrigueElements); // Re-index array
                ?>
                    <div class="intrigue-section">
                        <div class="intrigue-header" onclick="toggleIntrigue(this)">
                            <div>
                                <h3><?php echo htmlspecialchars($intrigue['title']); ?></h3>
                                <?php if ($intrigue['description']): ?>
                                    <small style="color: #666;"><?php echo htmlspecialchars(substr($intrigue['description'], 0, 100)); ?><?php echo strlen($intrigue['description']) > 100 ? '...' : ''; ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="intrigue-count"><?php echo count($intrigueElements); ?> scène<?php echo count($intrigueElements) > 1 ? 's' : ''; ?></span>
                            <span class="intrigue-toggle">▼</span>
                        </div>

                        <div class="elements-grid">
                            <?php if (!empty($intrigueElements)): ?>
                                <?php foreach ($intrigueElements as $element):
                                    // Skip if element is NULL (intrigue with no elements)
                                    if (empty($element['id'])) {
                                        continue;
                                    }
                                    
                                    // Get tags for this element
                                    $elementTags = [];
                                    if (isset($tagsData[$intrigue['id']]) && !empty($element['tag_ids'])) {
                                        $elementTagIds = array_filter(explode(',', $element['tag_ids']));
                                        foreach ($tagsData[$intrigue['id']] as $tag) {
                                            if (in_array((string)$tag['id'], $elementTagIds)) {
                                                $elementTags[] = $tag;
                                            }
                                        }
                                    }
                                    
                                    $hasDependencies = !empty($dependenciesData[$element['id']] ?? []);
                                ?>
                                    <div class="element-card" data-element-id="<?php echo $element['id']; ?>" draggable="true">
                                        <div class="element-card-header">
                                            <span class="element-type <?php echo strtolower($element['type']); ?>"><?php echo htmlspecialchars($element['type']); ?></span>
                                        </div>

                                        <div class="element-card-title">
                                            <?php echo htmlspecialchars($element['title']); ?>
                                        </div>

                                        <?php if (!empty($elementTags)): ?>
                                            <div class="element-tags">
                                                <?php foreach ($elementTags as $tag): ?>
                                                    <span class="element-tag" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>;">
                                                        <?php echo htmlspecialchars($tag['label']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($element['description']): ?>
                                            <div class="element-card-description">
                                                <?php echo htmlspecialchars($element['description']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($hasDependencies): ?>
                                            <div class="element-badges">
                                                <?php foreach ($dependenciesData[$element['id']] as $dep): ?>
                                                    <div class="badge">⚠️ Bloque <?php echo htmlspecialchars(substr($dep['title'], 0, 15)); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="element-actions">
                                            <a href="intrigue.php?id=<?php echo $intrigue['id']; ?>&project=<?php echo $projectId; ?>" title="Voir l'intrigue" class="btn-icon">👁️ Voir</a>
                                            <button onclick="viewElement(<?php echo $element['id']; ?>, <?php echo $intrigue['id']; ?>, <?php echo $projectId; ?>)" title="Éditer" class="btn-icon">✏️ Éditer</button>
                                            <button onclick="deleteElement(<?php echo $element['id']; ?>)" title="Supprimer" class="btn-icon btn-danger">🗑️ Suppr</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: #7f8c8d;">
                                    <p>Aucune scène encore. <a href="intrigue.php?id=<?php echo $intrigue['id']; ?>&project=<?php echo $projectId; ?>" style="color: #3498db;">Ajouter une scène →</a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h2>📖 Pas encore d'intrigues</h2>
                    <p>Créez votre première intrigue pour commencer à organiser vos scènes!</p>
                    <a href="project.php?id=<?php echo $projectId; ?>" class="add-intrigue-btn">Créer une intrigue</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleIntrigue(header) {
            const section = header.closest('.intrigue-section');
            const toggle = header.querySelector('.intrigue-toggle');
            
            section.classList.toggle('intrigue-hidden');
            
            if (section.classList.contains('intrigue-hidden')) {
                toggle.textContent = '▶';
            } else {
                toggle.textContent = '▼';
            }
        }

        let draggedElement = null;
        let dragSource = null;

        // Initialize drag & drop
        function initDragDrop() {
            document.querySelectorAll('.element-card').forEach(card => {
                card.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    dragSource = {
                        elementId: this.getAttribute('data-element-id'),
                        grid: this.closest('.elements-grid')
                    };
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });

                card.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    draggedElement = null;
                    dragSource = null;
                });
            });

            // Make grids droppable
            document.querySelectorAll('.elements-grid').forEach(grid => {
                grid.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    if (draggedElement && draggedElement.closest('.elements-grid') !== this) {
                        this.style.background = 'rgba(52, 152, 219, 0.1)';
                    }
                });

                grid.addEventListener('dragleave', function(e) {
                    if (e.target === this) {
                        this.style.background = '';
                    }
                });

                grid.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.style.background = '';
                    
                    if (draggedElement && dragSource) {
                        const targetIntrigue = this.closest('.intrigue-section').querySelector('.intrigue-header h3').textContent;
                        
                        // Si l'élément change d'intrigue, afficher une alerte
                        if (draggedElement.closest('.elements-grid') !== this) {
                            // Pour l'instant, ne pas autoriser le drag entre intrigues
                            // Cette fonctionnalité peut être ajoutée plus tard
                        } else {
                            // Réordonner l'élément dans la même intrigue
                            this.appendChild(draggedElement);
                            
                            // Appeler l'API de repositionnement
                            const allCards = Array.from(this.querySelectorAll('.element-card'));
                            const position = allCards.indexOf(draggedElement);
                            
                            // Pour maintenant, on ne fait que l'affichage
                            // L'API de repositionnement utilise les boutons ↑↓
                        }
                    }
                });
            });
        }

        // Delete element function
        function deleteElement(elementId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet élément? Cette action ne peut pas être annulée.')) {
                fetch('api/elements.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'delete-element',
                        element_id: elementId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + (data.error || 'Suppression échouée'));
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // View element function
        function viewElement(elementId, intrigueId, projectId) {
            window.location.href = 'intrigue.php?id=' + intrigueId + '&project=' + projectId;
        }

        document.addEventListener('DOMContentLoaded', initDragDrop);
    </script>
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/dynamic-sizer.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
