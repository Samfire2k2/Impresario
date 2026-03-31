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

// Handle create character
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create-character') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        $error = 'Le nom du personnage est obligatoire.';
    } else {
        $charId = createCharacter($pdo, $projectId, $name, $role, $description);
        if ($charId) {
            $success = 'Personnage créé avec succès!';
        } else {
            $error = 'Erreur lors de la création.';
        }
    }
}

// Handle delete character
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete-character') {
    $charId = $_POST['character_id'] ?? null;
    if ($charId) {
        $stmt = $pdo->prepare('DELETE FROM character WHERE id = :id AND project_id = :project_id');
        $stmt->execute([':id' => $charId, ':project_id' => $projectId]);
        $success = 'Personnage supprimé.';
    }
}

$characters = getProjectCharacters($pdo, $projectId);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnages - Impresario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
    <div class="navbar-container">
        <a href="project.php?id=<?php echo $projectId; ?>" class="logo-link">← Retour</a>
        <span class="page-title"><i class="fas fa-users"></i> Personnages</span>
        <span class="username"><?php echo escapeHtml(getCurrentUsername()); ?></span>
    </div>

    <div class="characters-container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escapeHtml($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo escapeHtml($success); ?></div>
        <?php endif; ?>

        <!-- Create Character Form -->
        <div class="create-form">
            <h2 style="margin-top: 0; margin-bottom: 24px;">Créer un nouveau personnage</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create-character">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom du personnage *</label>
                        <input type="text" name="name" required placeholder="ex: Alice Dupont">
                    </div>
                    <div class="form-group">
                        <label>Rôle</label>
                        <input type="text" name="role" placeholder="ex: Héroïne, Antagoniste...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description physique & personnalité</label>
                    <textarea name="description" placeholder="Description du personnage..."></textarea>
                </div>

                <button type="submit" class="btn">+ Ajouter</button>
            </form>
        </div>

        <!-- Characters Grid -->
        <h2 style="margin-bottom: 24px;">Personnages du projet (<?php echo count($characters); ?>)</h2>
        
        <?php if ($characters): ?>
            <div class="characters-grid">
                <?php foreach ($characters as $char): ?>
                    <div class="character-card">
                        <div class="character-color" style="background-color: <?php echo escapeHtml($char['color_hex'] ?? '#C9A87C'); ?>;"></div>
                        
                        <div class="character-name"><?php echo escapeHtml($char['name']); ?></div>
                        
                        <?php if ($char['role']): ?>
                            <div class="character-role"><i class="fas fa-thumbtack"></i> <?php echo escapeHtml($char['role']); ?></div>
                        <?php endif; ?>
                        
                        <div class="character-description">
                            <?php echo nl2br(escapeHtml(substr($char['description'] ?? '', 0, 150))); 
                                if (strlen($char['description'] ?? '') > 150) echo '...'; 
                            ?>
                        </div>

                        <div class="character-actions">
                            <a href="character-editor.php?id=<?php echo $char['id']; ?>&project=<?php echo $projectId; ?>" class="btn btn-edit"><i class="fas fa-pen"></i> Éditer</a>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="action" value="delete-character">
                                <input type="hidden" name="character_id" value="<?php echo $char['id']; ?>">
                                <button type="submit" class="btn btn-delete" onclick="return confirm('Supprimer ce personnage?');"><i class="fas fa-trash"></i> Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <p style="color: var(--text-secondary); font-size: 1.1em;">Aucun personnage créé. Commencez à construire votre univers!</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
