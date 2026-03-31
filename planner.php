<?php
/**
 * Planner - Interface principale pour la gestion de scènes et des intrigues
 * Phase 2 de la redesign - Planner avec sidebar intrigues
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

// Vérifier si les migrations de couleurs sont appliquées
try {
    $stmt = $pdo->prepare("SELECT bg_color_1 FROM project WHERE id = ? LIMIT 1");
    $stmt->execute([$projectId]);
} catch (PDOException $e) {
    // Les colonnes de couleur n'existent pas - rediriger vers la migration
    header('Location: apply-migrations.php?project=' . $projectId);
    exit;
}

// Récupérer les couleurs du projet
$projectColors = getProjectColors($pdo, $projectId);
$gradient = generateProjectGradient($pdo, $projectId, 'array');

// Déterminer l'intrigue active (par défaut la première)
$activeIntrigueId = $_GET['intrigue'] ?? ($intrigues[0]['id'] ?? null);
$activeIntrigue = null;

if ($activeIntrigueId) {
    foreach ($intrigues as $intrigue) {
        if ($intrigue['id'] == $activeIntrigueId) {
            $activeIntrigue = $intrigue;
            break;
        }
    }
}

// Si intrigue active trouvée, récupérer ses éléments
$elements = [];
if ($activeIntrigue) {
    $stmt = $pdo->prepare("
        SELECT * FROM element 
        WHERE intrigue_id = ? AND (type = 'scene' OR type = 'marqueur')
        ORDER BY position ASC
    ");
    $stmt->execute([$activeIntrigue['id']]);
    $elements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer la poche (éléments sans intrigue)
$stmt = $pdo->prepare("
    SELECT * FROM element 
    WHERE intrigue_id IS NULL AND type IN ('scene', 'marqueur', 'pocket')
    ORDER BY position ASC
");
$stmt->execute([]);
$pocketElements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planner - <?php echo htmlspecialchars($project['title']); ?> - Impresario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="project.php?id=<?php echo $projectId; ?>" style="color: var(--text-primary); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="logo">Impresario - Planner</h1>
            </div>
            <div class="navbar-user">
                <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                <a href="logout.php" class="btn btn-small">Déconnexion</a>
            </div>
        </div>
    </header>
    
    <div class="planner-container">
        <!-- SIDEBAR - Intrigues -->
        <aside class="planner-sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                <div class="project-info">
                    <?php echo count($intrigues); ?> intrigue(s)
                </div>
            </div>
            
            <nav class="intrigues-list">
                <?php foreach ($intrigues as $intrigue):
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM element WHERE intrigue_id = ? AND type IN ('scene', 'marqueur')");
                    $stmt->execute([$intrigue['id']]);
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    $isActive = $activeIntrigue && $activeIntrigue['id'] === $intrigue['id'];
                ?>
                    <a href="?project=<?php echo $projectId; ?>&intrigue=<?php echo $intrigue['id']; ?>" 
                       class="intrigue-item <?php echo $isActive ? 'active' : ''; ?>"
                       data-intrigue-id="<?php echo $intrigue['id']; ?>">
                        <div class="intrigue-name"><?php echo htmlspecialchars($intrigue['title']); ?></div>
                        <div class="intrigue-count"><?php echo $count; ?> élément(s)</div>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <div class="sidebar-footer">
                <button class="btn btn-small" onclick="addIntrigue()">
                    <i class="fas fa-plus"></i> Intrigue
                </button>
                <a href="project.php?id=<?php echo $projectId; ?>" class="btn btn-small" title="Retour au projet">
                    <i class="fas fa-cog"></i> Retour
                </a>
            </div>
        </aside>
        
        <!-- RUBAN LATÉRAL -->
        <div class="planner-ruban">
            <button class="planner-ruban-btn" onclick="event.preventDefault(); document.querySelector('.pocket-panel').scrollIntoView({behavior: 'smooth'}); return false;" title="Poche">
                <i class="fas fa-inbox"></i>
                <div class="planner-ruban-tooltip" style="top: 0px;">Poche</div>
            </button>
            
            <button class="planner-ruban-btn" onclick="addScene()" title="Ajouter une scène">
                <i class="fas fa-plus"></i>
                <div class="planner-ruban-tooltip" style="top: 60px;">+ Scène</div>
            </button>
            
            <button class="planner-ruban-btn" onclick="addMarqueur()" title="Ajouter un marqueur">
                <i class="fas fa-bookmark"></i>
                <div class="planner-ruban-tooltip" style="top: 120px;">+ Marqueur</div>
            </button>
            
            <div class="planner-ruban-separator"></div>
            
            <button class="planner-ruban-btn" onclick="showDependenciesModal()" title="Dépendances">
                <i class="fas fa-link"></i>
                <div class="planner-ruban-tooltip" style="top: 180px;">Dépendances</div>
            </button>
            
            <button class="planner-ruban-btn" onclick="showCreateTagModal()" title="Créer un tag">
                <i class="fas fa-tag"></i>
                <div class="planner-ruban-tooltip" style="top: 240px;">Tags</div>
            </button>
            
            <div class="planner-ruban-separator"></div>
            
            <button class="planner-ruban-btn" onclick="location.href='planner-settings.php?project=<?php echo $projectId; ?>'" title="Paramètres">
                <i class="fas fa-cog"></i>
                <div class="planner-ruban-tooltip" style="top: 300px;">Paramètres</div>
            </button>
            
            <button class="planner-ruban-btn" onclick="location.href='logout.php'" title="Déconnexion" style="margin-top: auto;">
                <i class="fas fa-sign-out-alt"></i>
                <div class="planner-ruban-tooltip" style="bottom: 60px; top: auto;">Déconnexion</div>
            </button>
        </div>
        
        <!-- MAIN CONTENT -->
        <div class="planner-main">
            <?php if ($activeIntrigue): ?>
                <!-- Header -->
                <div class="planner-header">
                    <div class="planner-title">
                        <div class="intrigue-color-indicator" style="background-color: <?php echo $activeIntrigue['color'] ?? '#d4a574'; ?>;"></div>
                        <h1><?php echo htmlspecialchars($activeIntrigue['title']); ?></h1>
                        <div style="display: flex; gap: 8px; margin-left: 15px;">
                            <button class="btn btn-small" onclick="editIntrigue(<?php echo $activeIntrigue['id']; ?>)" title="Éditer l'intrigue">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-small" onclick="moveIntrigue('up')" title="Monter l'intrigue">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button class="btn btn-small" onclick="moveIntrigue('down')" title="Descendre l'intrigue">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="planner-actions">
                        <button class="btn btn-small" onclick="toggleAllDescriptions()" title="Afficher/Cacher les descriptions">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-small" onclick="addScene()">
                            <i class="fas fa-plus"></i> Scène
                        </button>
                        <button class="btn btn-small" onclick="addMarqueur()">
                            <i class="fas fa-bookmark"></i> Marqueur
                        </button>
                        <div style="display:flex; gap:5px;">
                            <a href="api/export.php?project_id=<?php echo $projectId; ?>&format=json" class="btn btn-small" title="Exporter en JSON" download>
                                <i class="fas fa-download"></i> JSON
                            </a>
                            <a href="api/export.php?project_id=<?php echo $projectId; ?>&format=csv" class="btn btn-small" title="Exporter en CSV" download>
                                <i class="fas fa-download"></i> CSV
                            </a>
                        </div>
                        <a href="planner-settings.php?project=<?php echo $projectId; ?>" class="btn btn-small" title="Paramètres">
                            <i class="fas fa-cog"></i>
                        </a>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <label for="font-size-control" style="font-size: 12px; margin: 0;">Taille:</label>
                            <input type="range" id="font-size-control" min="10" max="20" value="14" style="width: 80px;" onchange="changeFontSize(this.value)">
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="planner-content">
                    <!-- Scenes Panel -->
                    <div class="scenes-panel">
                        <div class="scenes-header">
                            <h3>Scènes & Marqueurs</h3>
                            <span class="scene-count"><?php echo count($elements); ?></span>
                        </div>
                        
                        <div class="scenes-list">
                            <?php if (empty($elements)): ?>
                                <div class="empty-state">
                                    <p>Aucune scène pour le moment.</p>
                                    <p style="font-size: 12px;">Cliquez sur "+ Scène" pour commencer.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($elements as $element): ?>
                                    <div class="<?php echo $element['type'] === 'marqueur' ? 'marqueur-item' : 'scene-item'; ?>" 
                                         draggable="true"
                                         data-element-id="<?php echo $element['id']; ?>"
                                         data-element-type="<?php echo $element['type']; ?>"
                                         data-element-color="<?php echo htmlspecialchars($element['color'] ?? '#fef5e7'); ?>"
                                         style="background-color: <?php echo htmlspecialchars($element['color'] ?? '#fef5e7'); ?>; border-left-color: <?php echo htmlspecialchars(isset($element['color']) ? 'rgba(0,0,0,0.2)' : 'var(--border-color)'); ?>;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div>
                                                <div class="<?php echo $element['type'] === 'marqueur' ? 'marqueur-title' : 'scene-title'; ?>">
                                                    <?php echo htmlspecialchars($element['title']); ?>
                                                </div>
                                                <div class="<?php echo $element['type'] === 'marqueur' ? 'marqueur-description' : 'scene-description'; ?>">
                                                    <?php echo htmlspecialchars(substr($element['description'] ?? '', 0, 100)); ?>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 5px; margin-left: 10px;">
                                                <button class="edit-btn" onclick="event.stopPropagation(); editScene(<?php echo $element['id']; ?>)" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 2px 5px;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pocket Panel -->
                    <div class="pocket-panel">
                        <div class="pocket-header">
                            <h3><i class="fas fa-inbox"></i> Poche</h3>
                        </div>
                        
                        <div class="pocket-list">
                            <?php if (empty($pocketElements)): ?>
                                <div class="empty-state" style="padding: 20px 10px;">
                                    <p style="font-size: 12px;">Vos scènes non assignées apparaîtront ici.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pocketElements as $item): ?>
                                    <div class="pocket-item" draggable="true" data-element-id="<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="flex: 1; display: flex; align-items: center; justify-content: center;">
                    <div>
                        <h2>Aucune intrigue</h2>
                        <p>Créez votre première intrigue pour commencer à planifier.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        const PROJECT_ID = <?php echo json_encode($projectId); ?>;
        const INTRIGUE_ID = <?php echo json_encode($activeIntrigueId); ?>;
        const BASE_FONT_SIZE = 14;
        let currentFontSize = BASE_FONT_SIZE;
        let hideAllDesc = false;
        
        // ==================== MODALS GESTION ====================
        
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        // Fermer modal au clic dehors
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('planner-modal')) {
                e.target.style.display = 'none';
            }
        });
        
        // ==================== INTRIGUES ====================
        
        function editIntrigue(intrigueId) {
            fetch(`api/intrigue.php?action=get-intrigues&project_id=${PROJECT_ID}`)
                .then(r => r.json())
                .then(data => {
                    const intrigue = data.intrigues.find(i => i.id == intrigueId);
                    if (intrigue) {
                        document.getElementById('edit-intrigue-id').value = intrigue.id;
                        document.getElementById('edit-intrigue-title').value = intrigue.title;
                        document.getElementById('edit-intrigue-description').value = intrigue.description || '';
                        document.getElementById('edit-intrigue-color').value = intrigue.color || '#d4a574';
                        openModal('edit-intrigue-modal');
                    }
                });
        }
        
        function saveIntrigue() {
            const intrigueId = document.getElementById('edit-intrigue-id').value;
            const title = document.getElementById('edit-intrigue-title').value;
            const description = document.getElementById('edit-intrigue-description').value;
            const color = document.getElementById('edit-intrigue-color').value;
            
            if (!title) {
                alert('Titre requis');
                return;
            }
            
            fetch('api/intrigue-edit.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update-intrigue',
                    project_id: PROJECT_ID,
                    intrigue_id: intrigueId,
                    title, description, color
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.error);
                }
            });
        }
        
        function deleteIntrigue() {
            if (!confirm('Supprimer cette intrigue et toutes ses scènes?')) return;
            
            const intrigueId = document.getElementById('edit-intrigue-id').value;
            fetch('api/intrigue.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'delete-intrigue',
                    project_id: PROJECT_ID,
                    intrigue_id: intrigueId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function moveIntrigue(direction) {
            const intrigueId = INTRIGUE_ID;
            fetch('api/intrigue-edit.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'move-intrigue',
                    project_id: PROJECT_ID,
                    intrigue_id: intrigueId,
                    direction: direction
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            });
        }
        
        // ==================== SCÈNES ====================
        
        function editScene(elementId) {
            fetch(`api/elements.php?action=get-elements-by-intrigue&intrigue_id=${INTRIGUE_ID}`)
                .then(r => r.json())
                .then(data => {
                    const element = data.elements.find(e => e.id == elementId);
                    if (element) {
                        document.getElementById('edit-scene-id').value = element.id;
                        document.getElementById('edit-scene-title').value = element.title;
                        document.getElementById('edit-scene-description').value = element.description || '';
                        document.getElementById('edit-scene-color').value = element.color || '#fef5e7';
                        
                        // Charger les tags
                        loadTagsForScene(elementId);
                        openModal('edit-scene-modal');
                    }
                });
        }
        
        function loadTagsForScene(elementId) {
            // Récupérer tags disponibles
            fetch(`api/tags.php?action=get-tags&intrigue_id=${INTRIGUE_ID}`)
                .then(r => r.json())
                .then(data => {
                    const select = document.getElementById('scene-tag-select');
                    select.innerHTML = '<option value="">-- Ajouter un tag --</option>';
                    data.tags.forEach(tag => {
                        select.innerHTML += `<option value="${tag.id}">${tag.label}</option>`;
                    });
                });
        }
        
        function addTagToScene() {
            const sceneId = document.getElementById('edit-scene-id').value;
            const tagId = document.getElementById('scene-tag-select').value;
            
            if (!tagId) return;
            
            fetch('api/tags.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'assign-tag',
                    element_id: sceneId,
                    tag_id: tagId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadTagsForScene(sceneId);
                }
            });
        }
        
        function saveScene() {
            const sceneId = document.getElementById('edit-scene-id').value;
            const title = document.getElementById('edit-scene-title').value;
            const description = document.getElementById('edit-scene-description').value;
            const color = document.getElementById('edit-scene-color').value;
            
            if (!title) {
                alert('Titre requis');
                return;
            }
            
            fetch('api/elements.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update-element',
                    element_id: sceneId,
                    title, description, color
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function deleteScene() {
            if (!confirm('Supprimer cette scène?')) return;
            
            const sceneId = document.getElementById('edit-scene-id').value;
            fetch('api/elements.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'delete-element',
                    element_id: sceneId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        // ==================== TAGS ====================
        
        function showCreateTagModal() {
            openModal('tag-modal');
        }
        
        function saveTag() {
            const name = document.getElementById('tag-name').value;
            const color = document.getElementById('tag-color').value;
            
            if (!name) {
                alert('Nom du tag requis');
                return;
            }
            
            fetch('api/tags.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'create-tag',
                    intrigue_id: INTRIGUE_ID,
                    label: name,
                    color: color
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal('tag-modal');
                    location.reload();
                }
            });
        }
        
        // ==================== DÉPENDANCES ====================
        
        function showDependenciesModal() {
            openModal('dependencies-modal');
            // Charger dépendances et scènes
            loadScenesList();
            loadDependenciesList();
        }
        
        function loadScenesList() {
            fetch(`api/elements.php?action=get-elements-by-intrigue&intrigue_id=${INTRIGUE_ID}`)
                .then(r => r.json())
                .then(data => {
                    const fromSelect = document.getElementById('dep-from');
                    const toSelect = document.getElementById('dep-to');
                    
                    // Récréer les options
                    fromSelect.innerHTML = '<option value="">-- Scène A (avant) --</option>';
                    toSelect.innerHTML = '<option value="">-- Scène B (après) --</option>';
                    
                    data.elements.forEach(el => {
                        const opt1 = document.createElement('option');
                        opt1.value = el.id;
                        opt1.textContent = el.title;
                        fromSelect.appendChild(opt1);
                        
                        const opt2 = document.createElement('option');
                        opt2.value = el.id;
                        opt2.textContent = el.title;
                        toSelect.appendChild(opt2);
                    });
                });
        }
        
        function loadDependenciesList() {
            // Charger et afficher les dépendances existantes
            fetch(`api/dependencies.php?action=get-dependencies-by-intrigue&intrigue_id=${INTRIGUE_ID}`)
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('dependencies-list');
                    if (!data.dependencies || data.dependencies.length === 0) {
                        list.textContent = 'Aucune dépendance';
                        return;
                    }
                    
                    list.innerHTML = data.dependencies.map(dep => `
                        <div style="display: flex; justify-content: space-between; padding: 10px; margin-bottom: 5px; background: var(--bg-primary); border-radius: 4px;">
                            <span>${dep.element_title} → ${dep.blocked_title}</span>
                            <button onclick="deleteDependency(${dep.id})" style="background: var(--danger); color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;">Supprimer</button>
                        </div>
                    `).join('');
                });
        }
        
        function addDependency() {
            const from = document.getElementById('dep-from').value;
            const to = document.getElementById('dep-to').value;
            
            if (!from || !to) {
                alert('Sélectionner deux scènes');
                return;
            }
            
            if (from === to) {
                alert('Sélectionner deux scènes différentes');
                return;
            }
            
            fetch('api/dependencies.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'create-dependency',
                    element_id: from,
                    blocked_element_id: to
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('dep-from').value = '';
                    document.getElementById('dep-to').value = '';
                    loadDependenciesList();
                } else {
                    alert('Erreur: ' + data.error);
                }
            });
        }
        
        function deleteDependency(depId) {
            if (!confirm('Supprimer cette dépendance?')) return;
            
            fetch('api/dependencies.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'delete-dependency',
                    dependency_id: depId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadDependenciesList();
                }
            });
        }
        
        // ==================== AFFICHAGE ====================
        
        function toggleAllDescriptions() {
            hideAllDesc = !hideAllDesc;
            document.querySelectorAll('.scene-item, .marqueur-item').forEach(item => {
                if (hideAllDesc) {
                    item.classList.add('hide-description');
                } else {
                    item.classList.remove('hide-description');
                }
            });
        }
        
        function changeFontSize(newSize) {
            currentFontSize = newSize;
            document.documentElement.style.setProperty('--base-font-size', newSize + 'px');
            document.querySelectorAll('.scene-item, .marqueur-item, .scene-description, .marqueur-description').forEach(el => {
                el.style.fontSize = (newSize * 0.9) + 'px';
            });
        }
        
        // ==================== ADAPTATION DE COULEURS AU THèME ====================
        
        /**
         * Convertir hex en RGB
         */
        function hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        }
        
        /**
         * Convertir RGB en hex
         */
        function rgbToHex(r, g, b) {
            return '#' + [r, g, b].map(x => {
                const hex = x.toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            }).join('');
        }
        
        /**
         * Éclaircir une couleur (pour light mode)
         */
        function lightenColor(hex, percent) {
            const rgb = hexToRgb(hex);
            if (!rgb) return hex;
            
            const factor = 1 + (percent / 100);
            return rgbToHex(
                Math.min(255, Math.round(rgb.r * factor)),
                Math.min(255, Math.round(rgb.g * factor)),
                Math.min(255, Math.round(rgb.b * factor))
            );
        }
        
        /**
         * Assombrir une couleur (pour dark mode)
         */
        function darkenColor(hex, percent) {
            const rgb = hexToRgb(hex);
            if (!rgb) return hex;
            
            const factor = 1 - (percent / 100);
            return rgbToHex(
                Math.max(0, Math.round(rgb.r * factor)),
                Math.max(0, Math.round(rgb.g * factor)),
                Math.max(0, Math.round(rgb.b * factor))
            );
        }
        
        /**
         * Adapter une couleur en fonction du thème actuel
         */
        function getAdaptedColor(baseColor) {
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            if (isDarkMode) {
                // En dark mode: assombrir la couleur base pour meilleur contraste
                return darkenColor(baseColor, 30);
            } else {
                // En light mode: éclaircir légèrement
                return lightenColor(baseColor, 15);
            }
        }
        
        /**
         * Mettre à jour les couleurs de tous les éléments
         */
        function updateElementColors() {
            document.querySelectorAll('[data-element-color]').forEach(element => {
                const baseColor = element.getAttribute('data-element-color');
                const adaptedColor = getAdaptedColor(baseColor);
                element.style.backgroundColor = adaptedColor;
                element.style.borderLeftColor = darkenColor(adaptedColor, 20);
            });
        }
        
        /**
         * Écouter les changements de thème
         */
        document.addEventListener('themeChanged', (e) => {
            setTimeout(() => {
                updateElementColors();
            }, 50);
        });
        
        // Initialisation au DOMContentLoaded (ajouter à la fin du DOMContentLoaded)
        
        // Appliquer les couleurs adaptées au thème initial
        updateElementColors();

        const modalAddScene = new (function() {
            this.show = function() {
                document.getElementById('modal-scene-title').value = '';
                document.getElementById('modal-scene-description').value = '';
                document.getElementById('add-scene-modal').style.display = 'flex';
            };
            this.hide = function() {
                document.getElementById('add-scene-modal').style.display = 'none';
            };
            this.submit = function() {
                const title = document.getElementById('modal-scene-title').value.trim();
                const description = document.getElementById('modal-scene-description').value.trim();
                
                if (!title) {
                    alert('Titre requîs');
                    return;
                }
                
                fetch('api/elements.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'create-element',
                        intrigue_id: INTRIGUE_ID,
                        type: 'scene',
                        title: title,
                        description: description
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                });
            };
        });
        
        const modalAddMarqueur = new (function() {
            this.show = function() {
                document.getElementById('modal-marqueur-title').value = '';
                document.getElementById('modal-marqueur-description').value = '';
                document.getElementById('add-marqueur-modal').style.display = 'flex';
            };
            this.hide = function() {
                document.getElementById('add-marqueur-modal').style.display = 'none';
            };
            this.submit = function() {
                const title = document.getElementById('modal-marqueur-title').value.trim();
                const description = document.getElementById('modal-marqueur-description').value.trim();
                
                if (!title) {
                    alert('Titre requis');
                    return;
                }
                
                fetch('api/elements.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'create-element',
                        intrigue_id: INTRIGUE_ID,
                        type: 'marqueur',
                        title: title,
                        description: description
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                });
            };
        });
        
        const modalAddIntrigue = new (function() {
            this.show = function() {
                document.getElementById('modal-intrigue-title').value = '';
                document.getElementById('modal-intrigue-description').value = '';
                document.getElementById('add-intrigue-modal').style.display = 'flex';
            };
            this.hide = function() {
                document.getElementById('add-intrigue-modal').style.display = 'none';
            };
            this.submit = function() {
                const title = document.getElementById('modal-intrigue-title').value.trim();
                const description = document.getElementById('modal-intrigue-description').value.trim();
                
                if (!title) {
                    alert('Titre requis');
                    return;
                }
                
                fetch('api/intrigue.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'create-intrigue',
                        project_id: PROJECT_ID,
                        title: title,
                        description: description
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                });
            };
        });
        
        function addScene() {
            if (!INTRIGUE_ID) {
                alert('Veuillez sélectionner une intrigue');
                return;
            }
            modalAddScene.show();
        }
        
        function addMarqueur() {
            if (!INTRIGUE_ID) {
                alert('Veuillez sélectionner une intrigue');
                return;
            }
            modalAddMarqueur.show();
        }
        
        function addIntrigue() {
            modalAddIntrigue.show();
        }
        
        // Drag and drop pour réordonner
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('scenes-container');
            if (container) {
                let draggedItem = null;
                let draggedFromIntrigue = INTRIGUE_ID;
                
                const setupDragListeners = () => {
                    const items = container.querySelectorAll('[data-element-id]');
                    
                    items.forEach(item => {
                        item.addEventListener('dragstart', (e) => {
                            draggedItem = item;
                            draggedFromIntrigue = INTRIGUE_ID;
                            item.style.opacity = '0.5';
                            e.dataTransfer.effectAllowed = 'move';
                            e.dataTransfer.setData('text/html', item.innerHTML);
                        });
                        
                        item.addEventListener('dragend', (e) => {
                            if (item) item.style.opacity = '1';
                            draggedItem = null;
                        });
                        
                        item.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            
                            if (item !== draggedItem && draggedItem) {
                                const rect = item.getBoundingClientRect();
                                const midpoint = rect.top + rect.height / 2;
                                
                                if (e.clientY < midpoint) {
                                    item.parentNode.insertBefore(draggedItem, item);
                                } else {
                                    item.parentNode.insertBefore(draggedItem, item.nextSibling);
                                }
                            }
                        });
                    });
                };
                
                setupDragListeners();
                
                // Enregistrer l'ordre
                container.addEventListener('drop', function(e) {
                    e.preventDefault();
                    
                    const items = Array.from(container.querySelectorAll('[data-element-id]'));
                    const updates = items.map((item, idx) => {
                        return {
                            id: parseInt(item.getAttribute('data-element-id')),
                            position: idx
                        };
                    });
                    
                    // Mettre à jour les positions
                    updates.forEach(update => {
                        fetch('api/elements.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: new URLSearchParams({
                                action: 'update-element',
                                element_id: update.id,
                                position: update.position
                            })
                        });
                    });
                });
            }
            
            // Drag & drop enter pour intrigues (pour déplacer entre intrigues)
            const intrigueItems = document.querySelectorAll('.intrigue-item');
            intrigueItems.forEach(intrigueItem => {
                intrigueItem.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    intrigueItem.style.opacity = '0.7';
                });
                
                intrigueItem.addEventListener('dragleave', (e) => {
                    intrigueItem.style.opacity = '1';
                });
                
                intrigueItem.addEventListener('drop', (e) => {
                    e.preventDefault();
                    intrigueItem.style.opacity = '1';
                    
                    if (draggedItem && draggedFromIntrigue !== INTRIGUE_ID) {
                        const targetIntrigueId = parseInt(intrigueItem.getAttribute('data-intrigue-id'));
                        const elementId = parseInt(draggedItem.getAttribute('data-element-id'));
                        
                        // Déplacer l'élément vers la nouvelle intrigue
                        fetch('api/elements.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: new URLSearchParams({
                                action: 'update-element',
                                element_id: elementId,
                                intrigue_id: targetIntrigueId
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                // Naviguer vers la nouvelle intrigue
                                window.location.href = '?project=' + PROJECT_ID + '&intrigue=' + targetIntrigueId;
                            }
                        });
                    }
                });
            });
            
            // Fermer les modals en cliquant en dehors
            window.addEventListener('click', function(e) {
                const modals = document.querySelectorAll('.planner-modal');
                modals.forEach(modal => {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });
        });
    </script>
    
    <!-- Modals -->
    <div id="add-scene-modal" class="planner-modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Ajouter une scène</h3>
                <button onclick="modalAddScene.hide()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <input type="text" id="modal-scene-title" placeholder="Titre de la scène" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid var(--border-color); border-radius: 4px;">
            <textarea id="modal-scene-description" placeholder="Description" rows="4" style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid var(--border-color); border-radius: 4px;"></textarea>
            <div style="display: flex; gap: 10px;justify-content: flex-end;">
                <button onclick="modalAddScene.hide()" class="btn btn-small">Annuler</button>
                <button onclick="modalAddScene.submit()" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
    
    <div id="add-marqueur-modal" class="planner-modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Ajouter un marqueur</h3>
                <button onclick="modalAddMarqueur.hide()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <input type="text" id="modal-marqueur-title" placeholder="Titre du marqueur" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid var(--border-color); border-radius: 4px;">
            <textarea id="modal-marqueur-description" placeholder="Description" rows="4" style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid var(--border-color); border-radius: 4px;"></textarea>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="modalAddMarqueur.hide()" class="btn btn-small">Annuler</button>
                <button onclick="modalAddMarqueur.submit()" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
    
    <div id="add-intrigue-modal" class="planner-modal" style="display: none;">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Ajouter une intrigue</h3>
                <button onclick="modalAddIntrigue.hide()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <input type="text" id="modal-intrigue-title" placeholder="Titre de l'intrigue" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid var(--border-color); border-radius: 4px;">
            <textarea id="modal-intrigue-description" placeholder="Description" rows="4" style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid var(--border-color); border-radius: 4px;"></textarea>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="modalAddIntrigue.hide()" class="btn btn-small">Annuler</button>
                <button onclick="modalAddIntrigue.submit()" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
    

    
    <!-- MODALS POUR ÉDITER/CRÉER -->
    
    <!-- Modal Éditer Intrigue -->
    <div id="edit-intrigue-modal" class="planner-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="margin-top: 0;">Éditer Intrigue</h3>
            <input type="hidden" id="edit-intrigue-id">
            <div style="margin-bottom: 15px;">
                <label>Titre</label>
                <input type="text" id="edit-intrigue-title" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Description</label>
                <textarea id="edit-intrigue-description" rows="3" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;"></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Couleur</label>
                <input type="color" id="edit-intrigue-color" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; cursor: pointer;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-small" onclick="document.getElementById('edit-intrigue-modal').style.display='none'">Annuler</button>
                <button class="btn btn-small btn-danger" onclick="deleteIntrigue()">Supprimer</button>
                <button class="btn btn-primary" onclick="saveIntrigue()">Enregistrer</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Éditer Scène -->
    <div id="edit-scene-modal" class="planner-modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <h3 style="margin-top: 0;">Éditer Scène</h3>
            <input type="hidden" id="edit-scene-id">
            <div style="margin-bottom: 15px;">
                <label>Titre</label>
                <input type="text" id="edit-scene-title" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Description</label>
                <textarea id="edit-scene-description" rows="4" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;"></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Couleur de la scène</label>
                <input type="color" id="edit-scene-color" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Tags</label>
                <div id="scene-tags-list" style="display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 10px;"></div>
                <div style="display: flex; gap: 5px;">
                    <select id="scene-tag-select" style="flex: 1; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
                        <option value="">-- Ajouter un tag --</option>
                    </select>
                    <button class="btn btn-small" onclick="addTagToScene()">Ajouter</button>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-small" onclick="document.getElementById('edit-scene-modal').style.display='none'">Annuler</button>
                <button class="btn btn-small btn-danger" onclick="deleteScene()">Supprimer</button>
                <button class="btn btn-primary" onclick="saveScene()">Enregistrer</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Créer/Éditer Tag -->
    <div id="tag-modal" class="planner-modal" style="display: none;">
        <div class="modal-content">
            <h3 style="margin-top: 0;">Créer Tag</h3>
            <div style="margin-bottom: 15px;">
                <label>Nom du tag</label>
                <input type="text" id="tag-name" placeholder="ex: Action, Romance" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Couleur du tag</label>
                <input type="color" id="tag-color" value="#3498db" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-small" onclick="document.getElementById('tag-modal').style.display='none'">Annuler</button>
                <button class="btn btn-primary" onclick="saveTag()">Créer</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Dépendances -->
    <div id="dependencies-modal" class="planner-modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <h3 style="margin-top: 0;">Dépendances</h3>
            <p>Certaines scènes doivent être placées avant ou après d'autres.</p>
            <div id="dependencies-list" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px; background: var(--bg-secondary); padding: 15px; border-radius: 4px;"></div>
            <div style="margin-bottom: 15px;">
                <label>Créer une dépendance</label>
                <div style="display: flex; gap: 10px;">
                    <select id="dep-from" style="flex: 1; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
                        <option value="">-- Scène A (avant) --</option>
                    </select>
                    <select id="dep-to" style="flex: 1; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
                        <option value="">-- Scène B (après) --</option>
                    </select>
                    <button class="btn btn-small" onclick="addDependency()">Ajouter</button>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-small" onclick="document.getElementById('dependencies-modal').style.display='none'">Fermer</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Déplacer Intrigues -->
    <div id="reorder-intrigues-modal" class="planner-modal" style="display: none;">
        <div class="modal-content" style="max-width: 400px;">
            <h3 style="margin-top: 0;">Déplacer Intrigues</h3>
            <div id="intrigues-list-reorder" style="display: flex; flex-direction: column; gap: 10px;"></div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="btn btn-small" onclick="document.getElementById('reorder-intrigues-modal').style.display='none'">Fermer</button>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/drag-drop.js"></script>
</body>
</html>
