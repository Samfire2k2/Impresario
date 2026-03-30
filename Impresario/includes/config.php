<?php
// Configuration de la connexion PostgreSQL
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'impresario_local');
define('DB_USER', 'postgres');
define('DB_PASS', 'samuel12');

// Établir la connexion à la base de données avec UTF-8
try {
    $pdo = new PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';client_encoding=UTF8',
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données: ' . $e->getMessage());
}

// Set UTF-8 headers globally
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger vers la connexion
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Fonction pour obtenir l'utilisateur actuel
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Fonction pour obtenir le nom d'utilisateur actuel
function getCurrentUsername() {
    global $pdo;
    $userId = getCurrentUserId();
    if (!$userId) return 'Utilisateur';
    
    $stmt = $pdo->prepare('SELECT name FROM author WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    return $user['name'] ?? 'Utilisateur';
}
?>
