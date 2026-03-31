<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/palette-functions.php';

requireLogin();

$userId = getCurrentUserId();
$username = getCurrentUsername();
$error = '';
$success = '';

// Gérer création de projet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create-project') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Le titre du projet est obligatoire.';
    } else {
        $projectId = createProject($pdo, $userId, $title, $description);
        if ($projectId) {
            header('Location: project.php?id=' . $projectId);
            exit;
        } else {
            $error = 'Erreur lors de la création du projet.';
        }
    }
}

// Récupérer tous les projets
$projects = getUserProjects($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresario - Tableau de bord</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <h1 class="logo">Impresario</h1>
            <div class="navbar-user" style="display: flex; gap: 10px; align-items: center;">
                <span class="username"><?php echo htmlspecialchars($username); ?></span>
                <a href="user-export.php?format=json" class="btn btn-small" title="Exporter JSON" download>
                    <i class="fas fa-download"></i>
                </a>
                <a href="user-export.php?format=sql" class="btn btn-small" title="Exporter SQL" download>
                    <i class="fas fa-database"></i>
                </a>
                <a href="logout.php" class="btn btn-small">Déconnexion</a>
            </div>
        </div>
    </header>
    
    <main class="dashboard">
        <div class="dashboard-container">
            <section class="dashboard-header">
                <h2>Mes projets</h2>
                <button class="btn btn-primary" data-modal-trigger="create-project-modal">
                    + Nouveau projet
                </button>
            </section>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <section class="projects-grid">
                <?php if (empty($projects)): ?>
                    <div class="empty-state">
                        <p>Aucun projet pour le moment. Créez votre première histoire !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <?php 
                            $gradient = generateProjectGradient($pdo, $project['id'], 'css');
                            $gradientStyle = !empty($gradient) ? "background: {$gradient};" : '';
                        ?>
                        <div class="project-card" data-project-id="<?php echo $project['id']; ?>" style="cursor: pointer; <?php echo $gradientStyle; ?>">
                            <div class="project-card-header">
                                <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                <span class="project-date">
                                    <?php echo date('d/m/Y', strtotime($project['date_creation'])); ?>
                                </span>
                            </div>
                            <p class="project-description">
                                <?php echo htmlspecialchars($project['description'] ?: 'Aucune description'); ?>
                            </p>
                            <div class="project-actions">
                                <a href="project.php?id=<?php echo $project['id']; ?>" class="btn btn-small">Ouvrir</a>
                                <button class="btn btn-small btn-danger" data-modal-trigger="delete-project-modal" data-project-id="<?php echo $project['id']; ?>">Supprimer</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>
    
    <!-- Modal de création de projet -->
    <div id="create-project-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Créer un nouveau projet</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="create-project">
                <div class="form-group">
                    <label for="project-title">Titre du projet</label>
                    <input type="text" id="project-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="project-description">Description (optionnelle)</label>
                    <textarea id="project-description" name="description" rows="4"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de suppression de projet -->
    <div id="delete-project-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Supprimer le projet</h3>
                <button class="close-modal" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: var(--brown); margin-bottom: 20px;">Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.</p>
                <form method="POST" id="delete-form">
                    <input type="hidden" name="action" value="delete-project">
                    <div class="modal-buttons" style="justify-content: flex-end;">
                        <button type="button" class="btn btn-small" data-modal-close>Annuler</button>
                        <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/dynamic-sizer.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Handle project card clicks
        document.addEventListener('DOMContentLoaded', function() {
            const projectCards = document.querySelectorAll('.project-card[data-project-id]');
            projectCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't navigate if clicking on buttons or action links
                    if (e.target.closest('.project-actions')) {
                        return;
                    }
                    const projectId = this.getAttribute('data-project-id');
                    window.location.href = 'project.php?id=' + projectId;
                });
            });

            // Handle delete project modal
            const deleteButtons = document.querySelectorAll('[data-modal-trigger="delete-project-modal"]');
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-project-id');
                    const deleteForm = document.getElementById('delete-form');
                    deleteForm.innerHTML = '<input type="hidden" name="action" value="delete-project"><input type="hidden" name="project_id" value="' + projectId + '">';
                    const buttons = deleteForm.innerHTML + '<div class="modal-buttons" style="justify-content: flex-end;"><button type="button" class="btn btn-small" data-modal-close>Annuler</button><button type="submit" class="btn btn-small btn-danger">Supprimer</button></div>';
                    deleteForm.innerHTML = buttons;
                });
            });
        });
    </script>
</body>
</html>
