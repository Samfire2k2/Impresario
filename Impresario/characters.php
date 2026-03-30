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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .characters-container {
            padding: calc(40px * var(--size-scale));
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 20px;
        }

        .characters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .character-card {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }

        .character-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: var(--bronze);
        }

        .character-color {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-block;
            margin-bottom: 12px;
            border: 2px solid var(--border-color);
        }

        .character-name {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .character-role {
            font-size: 0.9em;
            color: var(--bronze);
            font-weight: 600;
            margin-bottom: 12px;
        }

        .character-description {
            font-size: 0.9em;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 16px;
            min-height: 40px;
        }

        .character-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .character-actions .btn {
            flex: 1;
            padding: 8px;
            font-size: 0.85em;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-edit:hover {
            background: var(--border-color);
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }

        .create-form {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 40px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .create-form .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .create-form .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .characters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar-container">
        <a href="project.php?id=<?php echo $projectId; ?>" class="logo-link">← Retour</a>
        <span class="page-title">👥 Personnages</span>
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
                            <div class="character-role">📌 <?php echo escapeHtml($char['role']); ?></div>
                        <?php endif; ?>
                        
                        <div class="character-description">
                            <?php echo nl2br(escapeHtml(substr($char['description'] ?? '', 0, 150))); 
                                if (strlen($char['description'] ?? '') > 150) echo '...'; 
                            ?>
                        </div>

                        <div class="character-actions">
                            <a href="character-editor.php?id=<?php echo $char['id']; ?>&project=<?php echo $projectId; ?>" class="btn btn-edit">✏️ Éditer</a>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="action" value="delete-character">
                                <input type="hidden" name="character_id" value="<?php echo $char['id']; ?>">
                                <button type="submit" class="btn btn-delete" onclick="return confirm('Supprimer ce personnage?');">🗑️ Supprimer</button>
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
</body>
</html>
