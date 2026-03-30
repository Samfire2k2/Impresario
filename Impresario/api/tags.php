<?php
// API pour ajouter un tag à une intrigue
header('Content-Type: application/json');
include '../includes/config.php';
include '../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add-tag') {
        $intrigueId = intval($_POST['intrigue_id'] ?? 0);
        $label = sanitizeInput($_POST['label'] ?? '');
        $color = sanitizeInput($_POST['color'] ?? '#3498db');
        
        // Vérifier que l'intrigue appartient à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT i.id FROM intrigue i 
            JOIN project p ON i.project_id = p.id 
            WHERE i.id = :intrigue_id AND p.author_id = :author_id
        ');
        $stmt->execute([':intrigue_id' => $intrigueId, ':author_id' => $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
        
        if (empty($label)) {
            http_response_code(400);
            echo json_encode(['error' => 'Le label est requis']);
            exit;
        }
        
        $tagId = createTag($pdo, $intrigueId, $label, $color);
        echo json_encode([
            'success' => true,
            'tag' => [
                'id' => $tagId,
                'label' => $label,
                'color' => $color
            ]
        ]);
    } 
    elseif ($action === 'delete-tag') {
        $tagId = intval($_POST['tag_id'] ?? 0);
        
        $stmt = $pdo->prepare('
            SELECT t.id FROM tag t
            JOIN intrigue i ON t.intrigue_id = i.id
            JOIN project p ON i.project_id = p.id
            WHERE t.id = :tag_id AND p.author_id = :author_id
        ');
        $stmt->execute([':tag_id' => $tagId, ':author_id' => $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
        
        $stmt = $pdo->prepare('DELETE FROM tag WHERE id = :id');
        $result = $stmt->execute([':id' => $tagId]);
        
        echo json_encode(['success' => $result]);
    }
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide']);
}
?>
