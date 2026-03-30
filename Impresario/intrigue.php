<?php
include 'includes/config.php';
include 'includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$intrigueId = $_GET['id'] ?? null;
$projectId = $_GET['project'] ?? null;

if (!$intrigueId || !$projectId) {
    header('Location: dashboard.php');
    exit;
}

$intrigue = getIntrigue($pdo, $intrigueId);
$project = getProject($pdo, $projectId, $userId);

if (!$intrigue || !$project || $intrigue['project_id'] != $projectId) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Ajouter un élément (scène)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add-element') {
        $type = sanitizeInput($_POST['type'] ?? 'scene');
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (empty($title)) {
            $error = 'Le titre de l\'élément est obligatoire.';
        } else {
            $elementId = createElement($pdo, $intrigueId, $type, $title, $description);
            if ($elementId) {
                $success = 'Élément créé avec succès.';
            }
        }
    } elseif ($_POST['action'] === 'edit-element') {
        $elementId = intval($_POST['element_id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? '');
        
        if (empty($title)) {
            $error = 'Le titre de l\'élément est obligatoire.';
        } else {
            $result = updateElement($pdo, $elementId, $title, $description, $type);
            if ($result) {
                $success = 'Élément modifié avec succès.';
            }
        }
    } elseif ($_POST['action'] === 'add-dependency') {
        $elementId = intval($_POST['element_id'] ?? 0);
        $blockedElementId = intval($_POST['blocked_element_id'] ?? 0);
        
        if ($elementId && $blockedElementId) {
            $result = addDependency($pdo, $elementId, $blockedElementId);
            if ($result) {
                $success = 'Dépendance ajoutée.';
            }
        }
    } elseif ($_POST['action'] === 'edit-intrigue') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (empty($title)) {
            $error = 'Le titre de l\'intrigue est obligatoire.';
        } else {
            $result = updateIntrigue($pdo, $intrigueId, $title, $description);
            if ($result) {
                $success = 'Intrigue modifiée avec succès.';
                $intrigue = getIntrigue($pdo, $intrigueId);
            } else {
                $error = 'Erreur lors de la modification de l\'intrigue.';
            }
        }
    } elseif ($_POST['action'] === 'delete-intrigue') {
        $result = deleteIntrigue($pdo, $intrigueId);
        if ($result) {
            header('Location: project.php?id=' . $projectId);
            exit;
        } else {
            $error = 'Erreur lors de la suppression de l\'intrigue.';
        }
    }
}

$elements = getIntrigueElements($pdo, $intrigueId);
$tags = getIntrigueTags($pdo, $intrigueId);
$projectElements = getProjectElements($pdo, $projectId);

// Créer un map des couleurs des tags pour l'affichage
$tagColorMap = [];
foreach ($tags as $tag) {
    $tagColorMap[$tag['id']] = $tag['color'];
}

// Récupérer les dépendances de tous les éléments pour l'affichage
$allDependencies = [];
foreach ($elements as $element) {
    $allDependencies[$element['id']] = getElementDependenciesWithDetails($pdo, $element['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($intrigue['title']); ?> - Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <script>
        // Données des tags disponibles pour cette intrigue (utilisé par le JS pour les checkboxes)
        window.intrigueTagsData = <?php echo json_encode($tags); ?>;
        window.elementCurrentTagIds = {};
    </script>
    <header class="navbar">
        <div class="navbar-container">
            <a href="project.php?id=<?php echo $projectId; ?>" class="logo-link">← <?php echo htmlspecialchars($project['title']); ?></a>
            <h1 class="page-title"><?php echo htmlspecialchars($intrigue['title']); ?></h1>
            <div class="navbar-actions">
                <button class="btn btn-small" data-modal-trigger="edit-intrigue-modal">Modifier</button>
                <button class="btn btn-small btn-danger" data-modal-trigger="delete-intrigue-modal">Supprimer</button>
                <a href="logout.php" class="btn btn-small">Déconnexion</a>
            </div>
        </div>
    </header>
    
    <main class="intrigue-view">
        <div class="intrigue-toolbar">
            <button class="btn btn-primary" data-modal-trigger="add-element-modal">
                + Ajouter un élément
            </button>
            <button class="btn btn-small" data-modal-trigger="manage-tags-modal">
                Gérer les tags
            </button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="intrigue-timeline">
            <div class="timeline-line"></div>
            <div class="elements-container">
                <?php if (empty($elements)): ?>
                    <div class="empty-state">
                        <p>Aucun élément. Créez votre première scène !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($elements as $element): ?>
                        <div class="element-card" data-element-id="<?php echo $element['id']; ?>">
                            <div class="element-header">
                                <h4><?php echo htmlspecialchars($element['title']); ?></h4>
                                <span class="element-type"><?php echo htmlspecialchars($element['type']); ?></span>
                            </div>
                            <p class="element-description">
                                <?php echo htmlspecialchars($element['description'] ?: 'Pas de description'); ?>
                            </p>
                            <?php if ($element['tag_ids']): ?>
                                <div class="element-tags">
                                    <?php 
                                    $tags_array = explode(',', $element['tag_ids']);
                                    $labels_array = explode(',', $element['tag_labels']);
                                    foreach ($tags_array as $i => $tagId):
                                        $color = $tagColorMap[$tagId] ?? '#3498db';
                                    ?>
                                        <span class="tag" data-tag-id="<?php echo $tagId; ?>" style="background-color: <?php echo htmlspecialchars($color); ?>; color: white;">
                                            <?php echo htmlspecialchars($labels_array[$i] ?? ''); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($allDependencies[$element['id']])): ?>
                                <div class="element-dependencies">
                                    <strong style="font-size: 0.85em; color: #e74c3c;">⚠️ Dépendances:</strong>
                                    <ul class="dependencies-list">
                                        <?php foreach ($allDependencies[$element['id']] as $dep): ?>
                                            <li>
                                                <span><?php echo htmlspecialchars($dep['blocked_element_title']); ?></span>
                                                <small style="color: #7f8c8d;">(<?php echo htmlspecialchars($dep['blocked_intrigue_title']); ?>)</small>
                                                <button class="btn-delete-dependency" data-dependency-id="<?php echo $dep['dependency_id']; ?>" title="Supprimer">×</button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <div class="element-actions">
                                <button class="btn btn-small" data-move-element="up" data-element-id="<?php echo $element['id']; ?>" title="Déplacer vers le haut">↑</button>
                                <button class="btn btn-small" data-move-element="down" data-element-id="<?php echo $element['id']; ?>" title="Déplacer vers le bas">↓</button>
                                <button class="btn btn-small" data-modal-trigger="edit-element-modal" data-element-id="<?php echo $element['id']; ?>">Éditer</button>
                                <button class="btn btn-small" data-modal-trigger="manage-element-tags-modal" data-element-id="<?php echo $element['id']; ?>">Tags</button>
                                <button class="btn btn-small" data-modal-trigger="add-dependency-modal" data-element-id="<?php echo $element['id']; ?>">Dépendance</button>
                                <button class="btn btn-small btn-danger" data-delete-element="<?php echo $element['id']; ?>">Supprimer</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Modal pour ajouter un élément -->
    <div id="add-element-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter un élément</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="add-element">
                <div class="form-group">
                    <label for="element-type">Type</label>
                    <select id="element-type" name="type">
                        <option value="scene">Scène</option>
                        <option value="marqueur">Marqueur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="element-title">Titre</label>
                    <input type="text" id="element-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="element-description">Description</label>
                    <textarea id="element-description" name="description" rows="6"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal pour éditer un élément -->
    <div id="edit-element-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Éditer l'élément</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="edit-element">
                <input type="hidden" id="edit-element-id" name="element_id">
                <div class="form-group">
                    <label for="edit-element-type">Type</label>
                    <select id="edit-element-type" name="type">
                        <option value="scene">Scène</option>
                        <option value="marqueur">Marqueur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-element-title">Titre</label>
                    <input type="text" id="edit-element-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit-element-description">Description</label>
                    <textarea id="edit-element-description" name="description" rows="6"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal pour gérer les tags -->
    <div id="manage-tags-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Gérer les tags</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <div class="modal-form">
                <div class="form-group">
                    <label for="new-tag-label">Nouveau tag</label>
                    <input type="text" id="new-tag-label" placeholder="Nom du tag">
                </div>
                <div class="form-group">
                    <label for="new-tag-color">Couleur</label>
                    <input type="color" id="new-tag-color" value="#3498db">
                </div>
                <button type="button" class="btn btn-primary" id="add-tag-btn">Ajouter</button>
                
                <hr>
                
                <h4>Tags existants</h4>
                <div id="tags-list">
                    <?php if (empty($tags)): ?>
                        <p>Aucun tag.</p>
                    <?php else: ?>
                        <?php foreach ($tags as $tag): ?>
                            <div class="tag-item">
                                <span class="tag-label"><?php echo htmlspecialchars($tag['label']); ?></span>
                                <div class="tag-color-preview" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>;"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Fermer</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal pour gérer les tags d'un élément -->
    <div id="manage-element-tags-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Gérer les tags de l'élément</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <div class="modal-form">
                <div id="element-tags-container" class="tags-checklist">
                    <p>Chargement des tags...</p>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-small" data-modal-close>Fermer</button>
            </div>
        </div>
    </div>
    
    <!-- Modal pour ajouter une dépendance -->
    <div id="add-dependency-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter une dépendance</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="add-dependency">
                <input type="hidden" id="dep-element-id" name="element_id">
                <div class="form-group">
                    <label for="dep-blocked-element">Élément à bloquer après celui-ci</label>
                    <select id="dep-blocked-element" name="blocked_element_id" required>
                        <option value="">Sélectionner un élément...</option>
                        <?php 
                        $currentIntrigueId = null;
                        foreach ($projectElements as $elem): 
                            // Si on change d'intrigue, fermer le groupe précédent et en ouvrir un nouveau
                            if ($elem['intrigue_id'] != $currentIntrigueId):
                                if ($currentIntrigueId !== null): 
                                    echo '</optgroup>';
                                endif;
                                $currentIntrigueId = $elem['intrigue_id'];
                                echo '<optgroup label="' . htmlspecialchars($elem['intrigue_title']) . '">';
                            endif;
                            
                            // Afficher l'élément s'il existe
                            if ($elem['id']):
                        ?>
                            <option value="<?php echo $elem['id']; ?>" id="dep-option-<?php echo $elem['id']; ?>">
                                └─ <?php echo htmlspecialchars($elem['title']); ?> (<?php echo htmlspecialchars($elem['type']); ?>)
                            </option>
                        <?php 
                            endif;
                        endforeach;
                        if ($currentIntrigueId !== null):
                            echo '</optgroup>';
                        endif;
                        ?>
                    </select>
                </div>
                <p class="info-text" style="font-size: 0.9em; color: #666; margin-top: 10px;">
                    ℹ️ Sélectionnez un élément qui ne devrait <strong>pas</strong> venir après celui-ci.<br>
                    Les dépendances cross-intrigue aident à maintenir la chronologie globale de votre histoire.
                </p>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal pour éditer l'intrigue -->
    <div id="edit-intrigue-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier l'intrigue</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="edit-intrigue">
                <div class="form-group">
                    <label for="edit-intrigue-title">Titre</label>
                    <input type="text" id="edit-intrigue-title" name="title" value="<?php echo htmlspecialchars($intrigue['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-intrigue-description">Description</label>
                    <textarea id="edit-intrigue-description" name="description" rows="4"><?php echo htmlspecialchars($intrigue['description']); ?></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal pour supprimer l'intrigue -->
    <div id="delete-intrigue-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Supprimer l'intrigue</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <div class="modal-form">
                <p style="color: #e74c3c; font-weight: bold;">⚠️ Attention!</p>
                <p>Êtes-vous sûr de vouloir supprimer cette intrigue ?</p>
                <p style="font-size: 0.9em; color: #666;">Cette action supprimera également tous les éléments de cette intrigue.</p>
            </div>
            <div class="modal-buttons">
                <form method="POST">
                    <input type="hidden" name="action" value="delete-intrigue">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/dynamic-sizer.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
