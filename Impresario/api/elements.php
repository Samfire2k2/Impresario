<?php
// API pour gérer les éléments
header('Content-Type: application/json');
include '../includes/config.php';
include '../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'delete-element') {
        $elementId = intval($_POST['element_id'] ?? 0);
        
        // Vérifier que l'élément appartient à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT e.id FROM element e
            JOIN intrigue i ON e.intrigue_id = i.id
            JOIN project p ON i.project_id = p.id
            WHERE e.id = :element_id AND p.author_id = :author_id
        ');
        $stmt->execute([':element_id' => $elementId, ':author_id' => $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
        
        $result = deleteElement($pdo, $elementId);
        echo json_encode(['success' => $result]);
    }
    elseif ($action === 'add-tag-to-element') {
        $elementId = intval($_POST['element_id'] ?? 0);
        $tagId = intval($_POST['tag_id'] ?? 0);
        
        // Vérifier que l'élément appartient à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT e.id FROM element e
            JOIN intrigue i ON e.intrigue_id = i.id
            JOIN project p ON i.project_id = p.id
            WHERE e.id = :element_id AND p.author_id = :author_id
        ');
        $stmt->execute([':element_id' => $elementId, ':author_id' => $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
        
        $result = addTagToElement($pdo, $elementId, $tagId);
        echo json_encode(['success' => $result]);
    }
    elseif ($action === 'remove-tag-from-element') {
        $elementId = intval($_POST['element_id'] ?? 0);
        $tagId = intval($_POST['tag_id'] ?? 0);
        
        // Vérifier que l'élément appartient à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT e.id FROM element e
            JOIN intrigue i ON e.intrigue_id = i.id
            JOIN project p ON i.project_id = p.id
            WHERE e.id = :element_id AND p.author_id = :author_id
        ');
        $stmt->execute([':element_id' => $elementId, ':author_id' => $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
        
        $result = removeTagFromElement($pdo, $elementId, $tagId);
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
