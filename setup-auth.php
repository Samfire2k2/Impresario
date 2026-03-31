<?php
include 'includes/config.php';
include 'includes/functions.php';

// Vérifier et créer/mettre à jour l'utilisateur de test
try {
    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare('SELECT id FROM author WHERE name = :name');
    $stmt->execute([':name' => 'test_home']);
    $user = $stmt->fetch();
    
    if ($user) {
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash('test_home', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE author SET password = :password WHERE name = :name');
        $stmt->execute([
            ':password' => $hashedPassword,
            ':name' => 'test_home'
        ]);
        echo "✓ Mot de passe mis à jour pour test_home<br>";
        echo "Hash: " . $hashedPassword . "<br>";
    } else {
        // Créer l'utilisateur
        if (createUser($pdo, 'test_home', 'test_home')) {
            echo "✓ Utilisateur test_home créé<br>";
            // Récupérer le hash généré
            $stmt = $pdo->prepare('SELECT password FROM author WHERE name = :name');
            $stmt->execute([':name' => 'test_home']);
            $result = $stmt->fetch();
            echo "Hash: " . $result['password'] . "<br>";
        } else {
            echo "✗ Erreur lors de la création de l'utilisateur<br>";
        }
    }
} catch (Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "<br>";
}
?>
