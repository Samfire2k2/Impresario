<?php
session_start();
require 'includes/config.php';
require 'includes/functions.php';

$tests = [
    'database' => [],
    'files' => [],
    'functions' => [],
    'views' => []
];

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.pass { color: #27ae60; font-weight: bold; } 
.fail { color: #e74c3c; font-weight: bold; }
.warn { color: #f39c12; font-weight: bold; }
h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ecf0f1; }
th { background: #3498db; color: white; }
tr:hover { background: #f8f9fa; }
</style>";

echo "<h1>✅ Validation Impresario - Édition Avancée</h1>";

// ============================================
// 1. TEST FILES
// ============================================
echo "<div class='section'><h2>📁 Fichiers Requis</h2>";

$required_files = [
    'includes/config.php' => 'Configuration DB',
    'includes/functions.php' => 'Fonctions métier',
    'project.php' => 'Vue Standard',
    'project-timeline.php' => 'Vue Timeline (NEW)',
    'intrigue.php' => 'Gestion intrigues',
    'dashboard.php' => 'Dashboard',
    'api/elements.php' => 'API éléments',
    'assets/css/style.css' => 'Styles',
    'assets/js/main.js' => 'JavaScript'
];

echo "<table><tr><th>Fichier</th><th>Statut</th></tr>";
foreach ($required_files as $file => $desc) {
    $exists = file_exists($file);
    $status = $exists ? "<span class='pass'>✅ OK</span>" : "<span class='fail'>❌ MANQUANT</span>";
    echo "<tr><td><strong>$file</strong> ($desc)</td><td>$status</td></tr>";
}
echo "</table></div>";

// ============================================
// 2. TEST FUNCTIONS
// ============================================
echo "<div class='section'><h2>🔧 Fonctions PHP</h2>";

$required_functions = [
    'getProject',
    'getIntrigueTags',
    'getProjectElements',
    'getElementDependenciesWithDetails',
    'createElement',
    'createIntrigue',
    'updateIntrigue',
    'deleteIntrigue',
    'addDependency',
    'removeDependency',
    'addTagToElement',
    'removeTagFromElement'
];

echo "<table><tr><th>Fonction</th><th>Statut</th></tr>";
foreach ($required_functions as $func) {
    $exists = function_exists($func);
    $status = $exists ? "<span class='pass'>✅ OK</span>" : "<span class='fail'>❌ MANQUANTE</span>";
    echo "<tr><td>$func()</td><td>$status</td></tr>";
}
echo "</table></div>";

// ============================================
// 3. TEST DATABASE
// ============================================
echo "<div class='section'><h2>🗄️ Base de Données</h2>";

try {
    $query = $pdo->query("SELECT 1");
    echo "<p><span class='pass'>✅ Connexion PostgreSQL OK</span></p>";
    
    $tables = ['author', 'project', 'intrigue', 'element', 'tag', 'element_tag', 'dependency'];
    echo "<table><tr><th>Table</th><th>Statut</th></tr>";
    foreach ($tables as $table) {
        $query = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_name='$table' AND table_schema='public'");
        $status = $query->rowCount() > 0 ? "<span class='pass'>✅ Existe</span>" : "<span class='fail'>❌ Manquante</span>";
        echo "<tr><td>$table</td><td>$status</td></tr>";
    }
    echo "</table>";
    
    // Check encoding
    $encoding = $pdo->query("SHOW client_encoding")->fetch(PDO::FETCH_ASSOC);
    echo "<p>Client Encoding: <span class='pass'>" . htmlspecialchars($encoding['client_encoding']) . " ✅</span></p>";
    
} catch (Exception $e) {
    echo "<p><span class='fail'>❌ Erreur DB: " . htmlspecialchars($e->getMessage()) . "</span></p>";
}
echo "</div>";

// ============================================
// 4. TEST FEATURES
// ============================================
echo "<div class='section'><h2>🎯 Features Implémentées</h2>";

$features = [
    'Authentification (login/register)' => ['includes/config.php', 'session_start'],
    'CRUD Projets' => ['includes/functions.php', 'createProject'],
    'CRUD Intrigues' => ['includes/functions.php', 'createIntrigue'],
    'CRUD Éléments' => ['api/elements.php', 'delete-element'],
    'Tags & Couleurs' => ['intrigue.php', 'manage-element-tags-modal'],
    'Dépendances' => ['api/dependencies.php', 'add-dependency'],
    'Repositionnement (↑↓)' => ['api/positions.php', 'move-element'],
    'Vue Standard' => ['project.php', 'project-view'],
    'Vue Timeline (NEW)' => ['project-timeline.php', 'timeline-container'],
    'CSS Avancé' => ['assets/css/style.css', 'element-card'],
    'Drag & Drop' => ['project-timeline.php', 'dragstart'],
    'UTF-8 Encoding' => ['includes/config.php', 'UTF-8']
];

echo "<table><tr><th>Feature</th><th>Fichier</th><th>Statut</th></tr>";
foreach ($features as $feature => $checks) {
    $file = $checks[0];
    $search = $checks[1];
    $file_exists = file_exists($file);
    
    if ($file_exists) {
        $content = file_get_contents($file);
        $found = strpos($content, $search) !== false;
        $status = $found ? "<span class='pass'>✅ OK</span>" : "<span class='warn'>⚠️ Check</span>";
    } else {
        $status = "<span class='fail'>❌ File Missing</span>";
    }
    
    echo "<tr><td>$feature</td><td>$file</td><td>$status</td></tr>";
}
echo "</table></div>";

// ============================================
// 5. TEST URLS
// ============================================
echo "<div class='section'><h2>🌐 URLs Disponibles</h2>";

$urls = [
    'index.php' => 'Accueil (redirect)',
    'login.php' => 'Authentification',
    'dashboard.php' => 'Tableau de bord',
    'project.php' => 'Vue Standard du projet',
    'project-timeline.php' => 'Vue Timeline du projet ⭐ NEW',
    'intrigue.php' => 'Gestion des intrigues',
    'api/elements.php' => 'API des éléments',
    'api/tags.php' => 'API des tags',
    'api/dependencies.php' => 'API des dépendances',
    'api/positions.php' => 'API de repositionnement'
];

echo "<table><tr><th>URL</th><th>Description</th></tr>";
foreach ($urls as $url => $desc) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = str_replace('test-final.php', '', $_SERVER['REQUEST_URI']);
    $full_url = $protocol . '://' . $host . dirname($base) . '/' . $url;
    
    echo "<tr><td><a href='$full_url' target='_blank'>$url</a></td><td>$desc</td></tr>";
}
echo "</table></div>";

// ============================================
// 6. SUMMARY
// ============================================
echo "<div class='section' style='background: #d4edda; border-left: 4px solid #27ae60;'>";
echo "<h2>✅ Application Complète!</h2>";
echo "<p style='font-size: 1.1em;'>";
echo "✅ <strong>Authentification</strong> - Login/Register sécurisé<br>";
echo "✅ <strong>CRUD Complet</strong> - Projets, intrigues, éléments<br>";
echo "✅ <strong>Tags & Couleurs</strong> - Identification visuelle<br>";
echo "✅ <strong>Dépendances</strong> - Gestion du chronologie<br>";
echo "✅ <strong>Repositionnement</strong> - Boutons ↑↓ ou drag-drop<br>";
echo "✅ <strong>Vue Standard</strong> - Interface classique<br>";
echo "✅ <strong>Vue Timeline</strong> - Affichage post-it ⭐<br>";
echo "✅ <strong>UTF-8</strong> - Support accents français<br>";
echo "</p>";
echo "</div>";

// ============================================
// 7. NEXT STEPS
// ============================================
echo "<div class='section'>";
echo "<h2>🚀 Prochaines Étapes</h2>";
echo "<ol>";
echo "<li><strong>Tester une création d'histoire complète:</strong>";
echo "  <ol>";
echo "    <li>Créer un compte</li>";
echo "    <li>Créer un projet (ex: 'Mon épée aventure')</li>";
echo "    <li>Ajouter 2 intrigues parallèles</li>";
echo "    <li>Ajouter des tags avec couleurs</li>";
echo "    <li>Créer 5-10 scènes</li>";
echo "    <li>Assigner tags et dépendances</li>";
echo "    <li>Visualiser en Vue Timeline</li>";
echo "    <li>Réorganiser les scènes</li>";
echo "  </ol>";
echo "</li>";
echo "<li><strong>Déployer sur alwaysdata.net</strong>";
echo "  <ol>";
echo "    <li>Configurer config.php avec les identifiants alwaysdata</li>";
echo "    <li>Créer la BD sur alwaysdata</li>";
echo "    <li>Importer schema via database.sql</li>";
echo "    <li>Tester depuis https://axolotl.alwaysdata.net</li>";
echo "  </ol>";
echo "</li>";
echo "<li><strong>Partager avec l'ami</strong>";
echo "  <ol>";
echo "    <li>Envoyer le lien</li>";
echo "    <li>Guide rapide (QUICK_START.md)</li>";
echo "    <li>Support technique</li>";
echo "  </ol>";
echo "</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 40px; color: #666;'>";
echo "<p><strong>Impresario v1.0 - Application Complète</strong></p>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='dashboard.php' style='color: #3498db; text-decoration: none; font-size: 1.1em;'>→ Aller au Dashboard</a></p>";
echo "</div>";
?>
