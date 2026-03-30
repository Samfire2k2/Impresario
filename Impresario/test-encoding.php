<?php
header('Content-Type: text/html; charset=utf-8');
require 'includes/config.php';

echo "<style>body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.test { background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 3px solid #3498db; }
.pass { color: #27ae60; } .fail { color: #e74c3c; font-weight: bold; }
h2 { color: #2c3e50; } code { background: #ecf0f1; padding: 2px 5px; border-radius: 3px; }</style>";

echo "<h1>🔍 Tests d'encodage</h1>";

// ============================================
// 1. PHP Configuration
// ============================================
echo "<div class='test'><h2>1️⃣ Configuration PHP</h2>";
echo "<p><strong>default_charset:</strong> " . (ini_get('default_charset') ?: 'non défini') . "</p>";
echo "<p><strong>Content-Type Header:</strong> <code>" . (function_exists('apache_request_headers') ? (apache_request_headers()['Content-Type'] ?? 'non défini') : 'N/A') . "</code></p>";
echo "<p><strong>Fichier PHP charset:</strong> <code>utf-8</code> (vérifié manuellement)</p>";
echo "</div>";

// ============================================
// 2. Database Configuration
// ============================================
echo "<div class='test'><h2>2️⃣ Configuration PostgreSQL</h2>";

try {
    $query = $pdo->query("SHOW client_encoding");
    $result = $query->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Client Encoding:</strong> <span class='pass'>" . htmlspecialchars($result['client_encoding']) . "</span></p>";
    
    $query = $pdo->query("SHOW server_encoding");
    $result = $query->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Server Encoding:</strong> <span class='pass'>" . htmlspecialchars($result['server_encoding']) . "</span></p>";
    
    $query = $pdo->query("SHOW database_encoding");
    $result = $query->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Database Encoding:</strong> <span class='pass'>" . htmlspecialchars($result['database_encoding']) . "</span></p>";
} catch (Exception $e) {
    echo "<p><span class='fail'>Erreur: " . htmlspecialchars($e->getMessage()) . "</span></p>";
}

echo "</div>";

// ============================================
// 3. Table Collations
// ============================================
echo "<div class='test'><h2>3️⃣ Collations des tables</h2>";

try {
    $tables = ['author', 'project', 'intrigue', 'element', 'tag'];
    
    foreach ($tables as $table) {
        $query = $pdo->query("
            SELECT collation_name 
            FROM information_schema.tables t 
            JOIN information_schema.schemata s ON t.table_schema = s.schema_name
            WHERE t.table_name = '$table' AND s.schema_name = 'public'
        ");
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "<p><strong>$table:</strong> " . htmlspecialchars($result['collation_name']) . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p><span class='fail'>Erreur: " . htmlspecialchars($e->getMessage()) . "</span></p>";
}

echo "</div>";

// ============================================
// 4. Test data in database
// ============================================
echo "<div class='test'><h2>4️⃣ Test d'écriture/lecture</h2>";

try {
    // Test data with French accents
    $test_string = "Test avec accents: é è ê ë à ù ûôôç";
    
    // Try inserting test project
    $stmt = $pdo->prepare("INSERT INTO project (author_id, title, description) VALUES (?, ?, ?) RETURNING id");
    $stmt->execute([1, $test_string, "Description: " . $test_string]);
    $test_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    
    // Read it back
    $query = $pdo->query("SELECT title, description FROM project WHERE id = $test_id");
    $result = $query->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Écrit:</strong> <code>" . htmlspecialchars($test_string) . "</code></p>";
    echo "<p><strong>Lu:</strong> <code>" . htmlspecialchars($result['title']) . "</code></p>";
    
    if ($result['title'] === $test_string) {
        echo "<p><span class='pass'>✅ Encodage OK</span></p>";
    } else {
        echo "<p><span class='fail'>❌ Problème d'encodage détecté</span></p>";
    }
    
    // Clean up
    $pdo->query("DELETE FROM project WHERE id = $test_id");
    
} catch (Exception $e) {
    echo "<p><span class='fail'>Erreur: " . htmlspecialchars($e->getMessage()) . "</span></p>";
}

echo "</div>";

// ============================================
// 5. Form Test
// ============================================
echo "<div class='test'><h2>5️⃣ Test Formulaire</h2>";

echo "<form method='post'>";
echo "<input type='text' name='test_input' value='' placeholder='Tapez du texte avec accents (é, è, ç, etc)'>";
echo "<button type='submit'>Tester</button>";
echo "</form>";

if ($_POST && !empty($_POST['test_input'])) {
    echo "<p><strong>Vous avez entré:</strong> <code>" . htmlspecialchars($_POST['test_input']) . "</code></p>";
    echo "<p><strong>Longueur:</strong> " . strlen($_POST['test_input']) . " bytes</p>";
    echo "<p><strong>UTF-8 valide:</strong> " . (mb_check_encoding($_POST['test_input'], 'UTF-8') ? '<span class="pass">Oui ✅</span>' : '<span class="fail">Non ❌</span>') . "</p>";
}

echo "</div>";

// ============================================
// 6. Recommendations
// ============================================
echo "<div class='test'><h2>💡 Recommandations</h2>";

$fixes = [
    "1. Ajouter UTF-8 meta tag: &lt;meta charset='utf-8'&gt; dans &lt;head&gt;",
    "2. Ajouter Content-Type header: header('Content-Type: text/html; charset=utf-8');",
    "3. Vérifier includes/config.php pour encoder la connexion PDO",
    "4. S'assurer tous les fichiers PHP sont sauvegardés en UTF-8 (BOM-less)",
    "5. Vérifier que PostgreSQL est configuré en UTF-8",
];

foreach ($fixes as $fix) {
    echo "<p>$fix</p>";
}

echo "</div>";

echo "<p style='text-align: center; margin-top: 30px; color: #666;'>";
echo "<a href='index.php' style='color: #3498db; text-decoration: none;'>← Retour</a>";
echo "</p>";
?>
