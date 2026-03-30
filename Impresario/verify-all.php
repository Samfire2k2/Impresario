<?php
/**
 * COMPREHENSIVE VERIFICATION SCRIPT
 * Tests all functionality of Impresario
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresario - Verification Complète</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .verification-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        .test-section {
            background: rgba(251, 245, 230, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--bronze);
        }
        .test-item {
            padding: 10px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .test-pass { color: #6B8E23; font-weight: bold; }
        .test-fail { color: #8B3A3A; font-weight: bold; }
        h2 {
            color: var(--bronze);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h1 style="text-align: center; color: var(--bronze); margin-bottom: 30px;">✓ Vérification Complète d'Impresario</h1>

        <?php
        $passed = 0;
        $failed = 0;

        // ========== 1. DATABASE CONNECTION ==========
        echo '<div class="test-section"><h2>1. Connexion Base de Données</h2>';
        try {
            require_once 'includes/config.php';
            if ($pdo) {
                echo '<div class="test-item"><span>PostgreSQL Connection</span><span class="test-pass">✓ OK</span></div>';
                $passed++;
                
                // Check tables
                $tables = ['users', 'projects', 'intrigues', 'elements', 'dependencies', 'tags', 'element_tags'];
                foreach ($tables as $table) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
                    $stmt->execute();
                    echo '<div class="test-item"><span>' . ucfirst($table) . '</span><span class="test-pass">✓ Existe</span></div>';
                    $passed++;
                }
            }
        } catch (Exception $e) {
            echo '<div class="test-item"><span>Database</span><span class="test-fail">✗ ' . htmlspecialchars($e->getMessage()) . '</span></div>';
            $failed++;
        }
        echo '</div>';

        // ========== 2. REQUIRED FILES ==========
        echo '<div class="test-section"><h2>2. Fichiers Requis</h2>';
        $required_files = [
            'includes/config.php' => 'Configuration',
            'includes/functions.php' => 'Fonctions',
            'assets/css/style.css' => 'CSS',
            'assets/js/auth.js' => 'Auth JS',
            'assets/js/main.js' => 'Main JS',
            'login.php' => 'Login Page',
            'dashboard.php' => 'Dashboard',
            'project.php' => 'Project View',
            'intrigue.php' => 'Intrigue View',
            'project-timeline.php' => 'Timeline View'
        ];
        foreach ($required_files as $file => $name) {
            if (file_exists($file)) {
                echo '<div class="test-item"><span>' . $name . '</span><span class="test-pass">✓ Existe</span></div>';
                $passed++;
            } else {
                echo '<div class="test-item"><span>' . $name . '</span><span class="test-fail">✗ Manquant</span></div>';
                $failed++;
            }
        }
        echo '</div>';

        // ========== 3. PHP FUNCTIONS ==========
        echo '<div class="test-section"><h2>3. Fonctions PHP</h2>';
        $functions = [
            'sanitizeInput', 'isValidEmail', 'userExists', 'createUser', 'verifyLogin',
            'getUserProjects', 'createProject', 'getProject', 'updateProject', 'deleteProject',
            'getIntrigues', 'createIntrigue', 'getIntrigue', 'updateIntrigue', 'deleteIntrigue',
            'getIntrigueElements', 'createElement', 'getElement', 'updateElement', 'deleteElement',
            'addDependency', 'getElementDependenciesWithDetails', 'createTag', 'getIntrigueTags', 'addTagToElement'
        ];
        foreach ($functions as $func) {
            if (function_exists($func)) {
                echo '<div class="test-item"><span>' . $func . '</span><span class="test-pass">✓ Existe</span></div>';
                $passed++;
            } else {
                echo '<div class="test-item"><span>' . $func . '</span><span class="test-fail">✗ Manquant</span></div>';
                $failed++;
            }
        }
        echo '</div>';

        // ========== 4. CSS CHECK ==========
        echo '<div class="test-section"><h2>4. Classes CSS</h2>';
        $css_file = file_get_contents('assets/css/style.css');
        $css_classes = [
            '.auth-box', '.element-card', '.modal-content', '.navbar', '.project-card',
            '.btn-primary', '.btn-small', '.element-type', '.intrigue-section', '.dashboard'
        ];
        foreach ($css_classes as $class) {
            if (strpos($css_file, $class) !== false) {
                echo '<div class="test-item"><span>' . $class . '</span><span class="test-pass">✓ Définie</span></div>';
                $passed++;
            } else {
                echo '<div class="test-item"><span>' . $class . '</span><span class="test-fail">✗ Manquante</span></div>';
                $failed++;
            }
        }
        echo '</div>';

        // ========== 5. UTF-8 ENCODING ==========
        echo '<div class="test-section"><h2>5. Encodage UTF-8</h2>';
        try {
            $result = $pdo->query("SHOW client_encoding")->fetch();
            if (strpos($result[0], 'UTF') !== false || strpos($result[0], 'utf') !== false) {
                echo '<div class="test-item"><span>Client Encoding</span><span class="test-pass">✓ UTF-8</span></div>';
                $passed++;
            } else {
                echo '<div class="test-item"><span>Client Encoding</span><span class="test-fail">✗ ' . htmlspecialchars($result[0]) . '</span></div>';
                $failed++;
            }
        } catch (Exception $e) {
            echo '<div class="test-item"><span>Encoding Check</span><span class="test-fail">✗ Erreur</span></div>';
            $failed++;
        }
        echo '</div>';

        // ========== SUMMARY ==========
        echo '<div class="test-section" style="background: linear-gradient(135deg, rgba(251, 245, 230, 0.8), rgba(232, 220, 200, 0.8));">';
        echo '<h2>Résumé</h2>';
        $total = $passed + $failed;
        $percentage = $total > 0 ? round(($passed / $total) * 100) : 0;
        echo '<div style="text-align: center; font-size: 1.5em;">';
        echo '<div class="test-pass" style="font-size: 2em; margin-bottom: 10px;">' . $passed . ' ✓ Réussis</div>';
        if ($failed > 0) {
            echo '<div class="test-fail" style="font-size: 1.2em;">' . $failed . ' ✗ Échoués</div>';
        }
        echo '<div style="color: var(--bronze); font-size: 1.2em; margin-top: 10px; font-weight: bold;">' . $percentage . '% Fonctionnel</div>';
        echo '</div>';
        
        if ($failed === 0) {
            echo '<div style="text-align: center; margin-top: 20px; padding: 15px; background: rgba(107, 142, 35, 0.1); border-radius: 8px; color: #6B8E23; font-weight: bold; font-size: 1.1em;">✓ TOUS LES TESTS RÉUSSIS!</div>';
        }
        echo '</div>';

        // ========== ACTION BUTTONS ==========
        echo '<div style="text-align: center; margin-top: 30px;">';
        echo '<a href="login.php" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; text-decoration: none; margin-right: 10px;">🔐 Connexion</a>';
        echo '<a href="dashboard.php" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; text-decoration: none;">📊 Dashboard</a>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
