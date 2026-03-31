<?php
/**
 * API Intrigues - Gestion CRUD complète
 * Handles: get-intrigues, create-intrigue, delete-intrigue
 */

header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    $projectId = $_GET['project_id'] ?? $_POST['project_id'] ?? 0;
    
    // Vérifier accès projet
    $stmt = $pdo->prepare('SELECT id FROM project WHERE id = ? AND author_id = ?');
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) throw new Exception('Accès refusé');
    
    if ($action === 'get-intrigues') {
        // Récupérer toutes les intrigues du projet
        $stmt = $pdo->prepare('
            SELECT id, title, description, color 
            FROM intrigue 
            WHERE project_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$projectId]);
        $intrigues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'intrigues' => $intrigues
        ]);
        
    } elseif ($action === 'create-intrigue') {
        // Créer une nouvelle intrigue
        if ($method !== 'POST') throw new Exception('Méthode non autorisée');
        
        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? '';
        $color = $_POST['color'] ?? '#d4a574';
        
        if (!$title) throw new Exception('Titre requis');
        
        $stmt = $pdo->prepare('
            INSERT INTO intrigue (project_id, title, description, color, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$projectId, $title, $description, $color]);
        
        $newId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'Intrigue créée'
        ]);
        
    } elseif ($action === 'delete-intrigue') {
        // Supprimer une intrigue et ses éléments
        if ($method !== 'POST') throw new Exception('Méthode non autorisée');
        
        $intrigueId = $_POST['intrigue_id'] ?? 0;
        
        // Vérifier l'existence et l'accès
        $stmt = $pdo->prepare('SELECT id FROM intrigue WHERE id = ? AND project_id = ?');
        $stmt->execute([$intrigueId, $projectId]);
        if (!$stmt->fetch()) throw new Exception('Intrigue non trouvée');
        
        // Supprimer les éléments associés
        $stmt = $pdo->prepare('DELETE FROM element WHERE intrigue_id = ?');
        $stmt->execute([$intrigueId]);
        
        // Supprimer l'intrigue
        $stmt = $pdo->prepare('DELETE FROM intrigue WHERE id = ? AND project_id = ?');
        $stmt->execute([$intrigueId, $projectId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Intrigue supprimée'
        ]);
        
    } else {
        throw new Exception('Action non reconnue: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
