<?php
/**
 * Script d'installation/migration pour Impresario Writer Mode
 * Exécute les upgrades de BDD pour supporter l'écriture
 */

include 'includes/config.php';

$error = '';
$success = '';

// Check if already migrated
try {
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'element' AND column_name = 'content'");
    if ($stmt->fetch()) {
        $alreadyMigrated = true;
    } else {
        $alreadyMigrated = false;
    }
} catch (Exception $e) {
    $alreadyMigrated = false;
}

// Handle migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'migrate') {
    try {
        $pdo->beginTransaction();

        // 1. Add columns to PROJECT
        $migrations = [
            // Project columns
            "ALTER TABLE project ADD COLUMN IF NOT EXISTS genre VARCHAR(100)",
            "ALTER TABLE project ADD COLUMN IF NOT EXISTS target_word_count INTEGER DEFAULT 0",
            "ALTER TABLE project ADD COLUMN IF NOT EXISTS current_word_count INTEGER DEFAULT 0",
            "ALTER TABLE project ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'in_progress', 'completed', 'published'))",
            "ALTER TABLE project ADD COLUMN IF NOT EXISTS synopsis TEXT",
            
            // Intrigue columns
            "ALTER TABLE intrigue ADD COLUMN IF NOT EXISTS chapter_number INTEGER",
            "ALTER TABLE intrigue ADD COLUMN IF NOT EXISTS content TEXT",
            "ALTER TABLE intrigue ADD COLUMN IF NOT EXISTS word_count INTEGER DEFAULT 0",
            "ALTER TABLE intrigue ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'first_read', 'finalized'))",
            "ALTER TABLE intrigue ADD COLUMN IF NOT EXISTS reading_order INTEGER",
            
            // Element columns
            "ALTER TABLE element ADD COLUMN IF NOT EXISTS content TEXT",
            "ALTER TABLE element ADD COLUMN IF NOT EXISTS word_count INTEGER DEFAULT 0",
            "ALTER TABLE element ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'first_read', 'finalized'))",
            "ALTER TABLE element ADD COLUMN IF NOT EXISTS writing_notes TEXT",
            "ALTER TABLE element ADD COLUMN IF NOT EXISTS pov_character VARCHAR(255)",
            "ALTER TABLE element ADD COLUMN IF NOT EXISTS scene_date DATE",
            "ALTER TABLE element ADD COLUMN IF NOT EXISTS location VARCHAR(255)",
        ];

        foreach ($migrations as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                // Column might already exist, continue
            }
        }

        // 2. Create new tables
        $tables = [
            // Element version history
            "CREATE TABLE IF NOT EXISTS element_version (
                id SERIAL PRIMARY KEY,
                element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
                content TEXT NOT NULL,
                word_count INTEGER,
                version_number INTEGER NOT NULL,
                author_id INTEGER NOT NULL REFERENCES author(id),
                change_summary TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Character registry
            "CREATE TABLE IF NOT EXISTS character (
                id SERIAL PRIMARY KEY,
                project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
                name VARCHAR(255) NOT NULL,
                role VARCHAR(100),
                description TEXT,
                physical_traits TEXT,
                arc_notes TEXT,
                color_hex VARCHAR(7) DEFAULT '#e74c3c',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Element-Character relation
            "CREATE TABLE IF NOT EXISTS element_character (
                id SERIAL PRIMARY KEY,
                element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
                character_id INTEGER NOT NULL REFERENCES character(id) ON DELETE CASCADE,
                appearance_type VARCHAR(50) DEFAULT 'main' CHECK (appearance_type IN ('main', 'secondary', 'mentioned')),
                UNIQUE(element_id, character_id)
            )",
            
            // Writing notes
            "CREATE TABLE IF NOT EXISTS writing_note (
                id SERIAL PRIMARY KEY,
                project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
                note_type VARCHAR(50) DEFAULT 'general' CHECK (note_type IN ('general', 'plot', 'character', 'world', 'timeline', 'idea')),
                title VARCHAR(255) NOT NULL,
                content TEXT,
                is_pinned BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Timeline events
            "CREATE TABLE IF NOT EXISTS timeline_event (
                id SERIAL PRIMARY KEY,
                project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
                event_date DATE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                involves_characters TEXT,
                impact_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];

        foreach ($tables as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                // Table might already exist
            }
        }

        // 3. Create indexes
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_element_version_element ON element_version(element_id)",
            "CREATE INDEX IF NOT EXISTS idx_character_project ON character(project_id)",
            "CREATE INDEX IF NOT EXISTS idx_element_character_element ON element_character(element_id)",
            "CREATE INDEX IF NOT EXISTS idx_element_character_character ON element_character(character_id)",
            "CREATE INDEX IF NOT EXISTS idx_writing_note_project ON writing_note(project_id)",
            "CREATE INDEX IF NOT EXISTS idx_timeline_event_project ON timeline_event(project_id)",
        ];

        foreach ($indexes as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                // Index might already exist
            }
        }

        // 4. Create views
        $views = [
            "CREATE OR REPLACE VIEW project_statistics AS
            SELECT 
                p.id,
                p.title,
                COUNT(DISTINCT i.id) as intrigue_count,
                COUNT(DISTINCT e.id) as scene_count,
                COALESCE(SUM(e.word_count), 0)::INTEGER as total_words,
                p.target_word_count,
                ROUND(100.0 * COALESCE(SUM(e.word_count), 0) / NULLIF(p.target_word_count, 0), 2)::FLOAT as progress_percent,
                COUNT(DISTINCT c.id) as character_count
            FROM project p
            LEFT JOIN intrigue i ON p.id = i.project_id
            LEFT JOIN element e ON i.id = e.intrigue_id
            LEFT JOIN character c ON p.id = c.project_id
            GROUP BY p.id, p.title, p.target_word_count"
        ];

        foreach ($views as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                // View might already exist or have errors
            }
        }

        $pdo->commit();
        $success = '✅ Migration réussie! Impresario Writer Mode est activé.';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Erreur de migration: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Impresario Writer - Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }

        .setup-header {
            background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            padding: 40px 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(201, 168, 124, 0.2);
        }

        .setup-header h1 {
            color: white;
            font-size: 2.5em;
            margin: 0;
            font-weight: 700;
        }

        .setup-header p {
            color: rgba(255, 251, 240, 0.9);
            font-size: 1.1em;
            margin: 12px 0 0;
        }

        .setup-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 40px;
            background: var(--bg-secondary);
            border: 2px solid var(--bronze);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(201, 168, 124, 0.15);
        }

        .setup-container h2 {
            color: var(--text-primary);
            font-size: 1.4em;
            margin-bottom: 20px;
            text-align: center;
        }

        .setup-container p {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 20px;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            background: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .feature-item {
            text-align: center;
            padding: 12px;
            background: rgba(201, 168, 124, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(201, 168, 124, 0.2);
            color: var(--text-primary);
            font-size: 0.95em;
            font-weight: 500;
        }

        .feature-item .emoji {
            display: block;
            font-size: 1.8em;
            margin-bottom: 6px;
        }

        .setup-form {
            margin-top: 30px;
            text-align: center;
        }

        .setup-form .btn {
            padding: 14px 40px;
            background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.05em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(201, 168, 124, 0.3);
        }

        .setup-form .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(201, 168, 124, 0.4);
        }

        .setup-form .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.4s ease-out;
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 2px solid #e74c3c;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 2px solid #2ecc71;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.95em;
            font-weight: 600;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: rgba(201, 168, 124, 0.2);
            border: 1px solid var(--bronze);
            border-radius: 8px;
            color: var(--bronze);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(201, 168, 124, 0.3);
            transform: translateX(-4px);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .setup-header h1 {
                font-size: 1.8em;
            }

            .setup-container {
                margin: 20px;
                padding: 20px;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="setup-header">
        <h1>✨ Writer Mode</h1>
        <p>Transformez Impresario en outil d'écriture complet</p>
    </div>

    <div class="setup-container">
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo escapeHtml($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo escapeHtml($success); ?></div>
            <p>Vous pouvez maintenant utiliser toutes les fonctionnalités d'écriture avancées!</p>
            <a href="dashboard.php" class="back-link">← Retour au Dashboard</a>
        <?php else: ?>
            <h2>Nouvelles Fonctionnalités</h2>
            <p>Activez Writer Mode pour débloquer:</p>
            
            <div class="features-grid">
                <div class="feature-item">
                    <span class="emoji">✨</span>
                    <span>Éditeur WYSIWYG</span>
                </div>
                <div class="feature-item">
                    <span class="emoji">📊</span>
                    <span>Dashboard Stats</span>
                </div>
                <div class="feature-item">
                    <span class="emoji">👥</span>
                    <span>Personnages</span>
                </div>
                <div class="feature-item">
                    <span class="emoji">📝</span>
                    <span>Notes</span>
                </div>
                <div class="feature-item">
                    <span class="emoji">📤</span>
                    <span>Export PDF/DOCX</span>
                </div>
                <div class="feature-item">
                    <span class="emoji">⏮️</span>
                    <span>Versioning</span>
                </div>
                <div class="feature-item">
                    <span class="emoji">📈</span>
                    <span>Word Count</span>
                </div>
                <div class="feature-item">
                    <span class="emoji">🌍</span>
                    <span>Timeline</span>
                </div>
            </div>

            <?php if ($alreadyMigrated): ?>
                <div class="status-badge">✅ Writer Mode déjà activé</div>
                <p style="margin-top: 20px;">Les outils sont déjà disponibles sur votre Dashboard!</p>
                <a href="dashboard.php" class="back-link">← Retour au Dashboard</a>
            <?php else: ?>
                <form method="POST" class="setup-form">
                    <input type="hidden" name="action" value="migrate">
                    <button type="submit" class="btn">🚀 Activer Writer Mode Maintenant</button>
                </form>
                <p style="font-size: 0.85em; color: var(--text-secondary); margin-top: 20px;">
                    Cette opération créera les tables nécessaires dans votre base de données.<br>
                    Elle est sûre et peut être exécutée plusieurs fois.
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
