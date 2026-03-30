<?php
include 'includes/config.php';

echo "<h1>🔧 UTF-8 Encoding Diagnostic & Repair</h1>";

// 1. Check current data in database
echo "<h2>1. Diagnostique des données existantes</h2>";

try {
    // Check projects
    $stmt = $pdo->query('SELECT id, title FROM project LIMIT 5');
    $projects = $stmt->fetchAll();
    
    echo "<p><strong>Projets actuels:</strong></p>";
    foreach ($projects as $proj) {
        echo "<p>ID: {$proj['id']}, Title: <code>" . htmlspecialchars($proj['title']) . "</code></p>";
        // Check if contains HTML entities
        if (preg_match('/&#\d+;/', $proj['title'])) {
            echo "<span style='color: red;'>❌ Contient des entités HTML</span><br>";
        } else {
            echo "<span style='color: green;'>✓ OK</span><br>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. Auto-fix corrupted data
echo "<h2>2. Correction automatique</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'fix') {
    try {
        // Fix projects
        $stmt = $pdo->query('SELECT id, title FROM project');
        $projects = $stmt->fetchAll();
        $fixed_count = 0;
        
        foreach ($projects as $proj) {
            if (preg_match('/&#\d+;/', $proj['title'])) {
                $decoded_title = html_entity_decode($proj['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $update = $pdo->prepare('UPDATE project SET title = :title WHERE id = :id');
                $update->execute([':title' => $decoded_title, ':id' => $proj['id']]);
                $fixed_count++;
                echo "<p>Corrigé: ID {$proj['id']} → " . htmlspecialchars($decoded_title) . "</p>";
            }
        }
        
        // Fix intrigues
        $stmt = $pdo->query('SELECT id, title FROM intrigue');
        $intrigues = $stmt->fetchAll();
        
        foreach ($intrigues as $int) {
            if (preg_match('/&#\d+;/', $int['title'])) {
                $decoded_title = html_entity_decode($int['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $update = $pdo->prepare('UPDATE intrigue SET title = :title WHERE id = :id');
                $update->execute([':title' => $decoded_title, ':id' => $int['id']]);
                $fixed_count++;
                echo "<p>Corrigé intrigue: ID {$int['id']} → " . htmlspecialchars($decoded_title) . "</p>";
            }
        }
        
        // Fix elements
        $stmt = $pdo->query('SELECT id, title FROM element');
        $elements = $stmt->fetchAll();
        
        foreach ($elements as $elem) {
            if (preg_match('/&#\d+;/', $elem['title'])) {
                $decoded_title = html_entity_decode($elem['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $update = $pdo->prepare('UPDATE element SET title = :title WHERE id = :id');
                $update->execute([':title' => $decoded_title, ':id' => $elem['id']]);
                $fixed_count++;
                echo "<p>Corrigé élément: ID {$elem['id']} → " . htmlspecialchars($decoded_title) . "</p>";
            }
        }
        
        echo "<p style='color: green;'><strong>✅ {$fixed_count} items corrigés!</strong></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur lors de la correction: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3. Show form to trigger fix
echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='fix'>";
echo "<button type='submit' class='btn' onclick=\"return confirm('Êtes-vous sûr de vouloir corriger les données?');\">🔧 Corriger les données corrompues</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='dashboard.php'>← Retour au dashboard</a></p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #FFFBF0;
    color: #1a1410;
    padding: 20px;
    line-height: 1.6;
}
h1, h2 {
    color: #8B4513;
}
code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}
.btn {
    background: #C9A87C;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}
.btn:hover {
    background: #8B7355;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26, 20, 16, 0.15);
}
p {
    margin: 10px 0;
}
</style>
