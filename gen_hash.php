<?php
// Générer un hash bcrypt pour 'test_home'
$password = 'test_home';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";

// Vérifier que le hash fonctionne
if (password_verify($password, $hash)) {
    echo "✓ Le hash vérifie correctement\n";
} else {
    echo "✗ Le hash ne vérifie pas\n";
}
?>
