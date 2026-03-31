<?php
/**
 * API Positions - Gestion du tri et du déplacement des éléments
 * Handles: move-element
 */

header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        throw new Exception('Requête invalide');
    }
    
    $action = $_POST['action'];
    
    if ($action === 'move-element') {
        $elementId = intval($_POST['element_id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up'; // 'up' ou 'down'
        
        // Récupérer l'élément
        $element = getElement($pdo, $elementId);
        if (!$element) {
            http_response_code(404);
            echo json_encode(['error' => 'Élément non trouvé']);
            exit;
        }
        
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
        
        // Trouver l'élément adjacent dans l'intrigue
        if ($direction === 'up') {
            // Trouver l'élément avec la position immédiatement inférieure
            $stmt = $pdo->prepare('
                SELECT id, position FROM element 
                WHERE intrigue_id = :intrigue_id AND position < :position
                ORDER BY position DESC
                LIMIT 1
            ');
        } else {
            // Trouver l'élément avec la position immédiatement supérieure
            $stmt = $pdo->prepare('
                SELECT id, position FROM element 
                WHERE intrigue_id = :intrigue_id AND position > :position
                ORDER BY position ASC
                LIMIT 1
            ');
        }
        
        $stmt->execute([':intrigue_id' => $element['intrigue_id'], ':position' => $element['position']]);
        $adjacentElement = $stmt->fetch();
        
        if (!$adjacentElement) {
            throw new Exception('Pas d\'élément dans cette direction');
        }
        
        // Échanger les positions
        $currentPos = $element['position'];
        $newPos = $adjacentElement['position'];
        
        $stmt = $pdo->prepare('UPDATE element SET position = :position WHERE id = :id');
        $stmt->execute([':position' => $newPos, ':id' => $elementId]);
        $stmt->execute([':position' => $currentPos, ':id' => $adjacentElement['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Éléments échangés']);
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
