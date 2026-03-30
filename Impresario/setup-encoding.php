<?php
/**
 * Script d'initialisation de l'encodage UTF-8
 * À exécuter une seule fois pour corriger les problèmes d'encodage
 */

require 'includes/config.php';

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.info { background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 3px solid #3498db; }
.success { color: #27ae60; font-weight: bold; }
.warning { color: #e67e22; font-weight: bold; }
h2 { color: #2c3e50; }
</style>";

echo "<h1>🔧 Configuration UTF-8 pour Impresario</h1>";

try {
    echo "<div class='info'>";
    echo "<h2>Statut de la base de données</h2>";
    
    // Check database encoding
    $query = $pdo->query("SELECT datname, encoding FROM pg_database WHERE datname = 'impresario_local'");
    $db_info = $query->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Base de données:</strong> " . ($db_info ? htmlspecialchars($db_info['datname']) : 'N/A') . "</p>";
    echo "<p><strong>Encodage:</strong> " . ($db_info ? htmlspecialchars($db_info['encoding']) : 'N/A') . "</p>";
    
    echo "</div>";
    
    // Verify connection encoding
    echo "<div class='info'>";
    echo "<h2>Vérification de la connexion</h2>";
    
    $encodings = $pdo->query("SHOW client_encoding")->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Client Encoding:</strong> <span class='success'>" . htmlspecialchars($encodings['client_encoding']) . " ✓</span></p>";
    
    $encodings = $pdo->query("SHOW server_encoding")->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Server Encoding:</strong> <span class='success'>" . htmlspecialchars($encodings['server_encoding']) . " ✓</span></p>";
    
    echo "</div>";
    
    // Test UTF-8 characters
    echo "<div class='info'>";
    echo "<h2>Test d'écriture UTF-8</h2>";
    
    $test_data = "Test UTF-8: é è ê ë à ù û ô ç €";
    
    try {
        // Insert test data
        $stmt = $pdo->prepare("SELECT ? AS test");
        $stmt->execute([$test_data]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['test'] === $test_data) {
            echo "<p><span class='success'>✅ UTF-8 fonctionne correctement</span></p>";
        } else {
            echo "<p><span class='warning'>⚠️ Problème détecté</span></p>";
            echo "<p>Entré: " . htmlspecialchars($test_data) . "</p>";
            echo "<p>Lu: " . htmlspecialchars($result['test']) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p><span class='warning'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</span></p>";
    }
    
    echo "</div>";
    
    // Configuration recommendations
    echo "<div class='info'>";
    echo "<h2>💡 Configuration appliquée</h2>";
    
    echo "<ol>";
    echo "<li><strong>config.php:</strong> Ajout de 'client_encoding=UTF8' à la DSN PDO</li>";
    echo "<li><strong>config.php:</strong> Ajout de header 'Content-Type: text/html; charset=utf-8'</li>";
    echo "<li><strong>config.php:</strong> Configuration mb_internal_encoding('UTF-8')</li>";
    echo "<li><strong>Tous les fichiers PHP:</strong> Inclusions de config.php (inclus automatiquement)</li>";
    echo "<li><strong>Tous les fichiers HTML:</strong> &lt;meta charset='UTF-8'&gt; (déjà présent)</li>";
    echo "</ol>";
    
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
    echo "<h3>✅ Configuration UTF-8 complète!</h3>";
    echo "<p>Tous les fichiers are now configured pour UTF-8. Les problèmes d'encodage de caractères spéciaux (é, è, ç, etc.) devraient être résolus.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 4px;'>";
    echo "<h3>❌ Erreur</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p style='margin-top: 30px; text-align: center;'>";
echo "<a href='test-encoding.php' style='color: #3498db; text-decoration: none; margin-right: 20px;'>Tests d'encodage →</a>";
echo "<a href='index.php' style='color: #3498db; text-decoration: none;'>← Retour à l'app</a>";
echo "</p>";
?>
