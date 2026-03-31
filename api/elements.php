<?php
/**
 * API Elements - Gestion des scènes et marqueurs
 * Handles: get-elements, create/update/delete-element, reorder, move-element
 */

header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    if ($method === 'GET') {
        if ($action === 'get-elements-by-intrigue') {
            $intrigueId = $_GET['intrigue_id'] ?? 0;
            
            $stmt = $pdo->prepare('
                SELECT e.*, COUNT(et.id) as tag_count 
                FROM element e
                LEFT JOIN element_tag et ON e.id = et.element_id
                WHERE e.intrigue_id = :intrigue_id
                GROUP BY e.id
                ORDER BY e.position ASC
            ');
            $stmt->execute([':intrigue_id' => $intrigueId]);
            $elements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'elements' => $elements]);
        }
    }
    elseif ($method === 'POST') {
        if ($action === 'create-element') {
            $intrigueId = $_POST['intrigue_id'] ?? null;
            $type = $_POST['type'] ?? 'scene'; // 'scene', 'marqueur'
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (empty($title)) {
                throw new Exception('Titre requis');
            }
            
            // Vérifier l'accès si intrigue_id fourni
            if ($intrigueId) {
                $stmt = $pdo->prepare('
                    SELECT i.id FROM intrigue i
                    JOIN project p ON i.project_id = p.id
                    WHERE i.id = :intrigue_id AND p.author_id = :author_id
                ');
                $stmt->execute([':intrigue_id' => $intrigueId, ':author_id' => $userId]);
                if (!$stmt->fetch()) {
                    throw new Exception('Accès refusé');
                }
            }
            
            // Obtenir la prochaine position
            $stmt = $pdo->prepare('
                SELECT COALESCE(MAX(position), 0) + 1 as new_position 
                FROM element 
                WHERE intrigue_id = :intrigue_id
            ');
            $stmt->execute([':intrigue_id' => $intrigueId]);
            $result = $stmt->fetch();
            $position = $result['new_position'] ?? 1;
            
            // Créer l'élément
            $stmt = $pdo->prepare('
                INSERT INTO element (intrigue_id, type, title, description, position) 
                VALUES (:intrigue_id, :type, :title, :description, :position) 
            ');
            $stmt->execute([
                ':intrigue_id' => $intrigueId,
                ':type' => $type,
                ':title' => $title,
                ':description' => $description,
                ':position' => $position
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Élément créé'
            ]);
        }
        
        elseif ($action === 'update-element') {
            $elementId = $_POST['element_id'] ?? 0;
            $title = $_POST['title'] ?? null;
            $description = $_POST['description'] ?? null;
            $position = $_POST['position'] ?? null;
            $intrigueId = $_POST['intrigue_id'] ?? null;
            $color = $_POST['color'] ?? null;
            
            // Vérifier l'accès
            $stmt = $pdo->prepare('
                SELECT e.id FROM element e
                JOIN intrigue i ON e.intrigue_id = i.id
                JOIN project p ON i.project_id = p.id
                WHERE e.id = :element_id AND p.author_id = :author_id
            ');
            $stmt->execute([':element_id' => $elementId, ':author_id' => $userId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Accès refusé');
            }
            
            // Mettre à jour
            $updates = [];
            $params = [':element_id' => $elementId];
            
            if ($title !== null) {
                $updates[] = 'title = :title';
                $params[':title'] = $title;
            }
            if ($description !== null) {
                $updates[] = 'description = :description';
                $params[':description'] = $description;
            }
            if ($position !== null) {
                $updates[] = 'position = :position';
                $params[':position'] = $position;
            }
            if ($intrigueId !== null) {
                $updates[] = 'intrigue_id = :intrigue_id';
                $params[':intrigue_id'] = $intrigueId;
            }
            if ($color !== null) {
                $updates[] = 'color = :color';
                $params[':color'] = $color;
            }
            
            $updates[] = 'updated_at = NOW()';
            
            if (count($updates) === 1) { // Seulement 'updated_at'
                throw new Exception('Aucune mise à jour');
            }
            
            $sql = 'UPDATE element SET ' . implode(', ', $updates) . ' WHERE id = :element_id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Élément mis à jour']);
        }
        
        elseif ($action === 'delete-element') {
            $elementId = $_POST['element_id'] ?? 0;
            
            // Vérifier l'accès
            $stmt = $pdo->prepare('
                SELECT e.id FROM element e
                JOIN intrigue i ON e.intrigue_id = i.id
                JOIN project p ON i.project_id = p.id
                WHERE e.id = :element_id AND p.author_id = :author_id
            ');
            $stmt->execute([':element_id' => $elementId, ':author_id' => $userId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Accès refusé');
            }
            
            // Supprimer
            $stmt = $pdo->prepare('DELETE FROM element WHERE id = :element_id');
            $stmt->execute([':element_id' => $elementId]);
            
            echo json_encode(['success' => true, 'message' => 'Élément supprimé']);
        }
        
        elseif ($action === 'reorder-elements') {
            $elementIds = $_POST['element_ids'] ?? [];
            
            if (empty($elementIds)) {
                throw new Exception('Aucun élément fourni');
            }
            
            // Mettre à jour les positions
            foreach ($elementIds as $position => $elementId) {
                $stmt = $pdo->prepare('
                    UPDATE element SET position = :position 
                    WHERE id = :element_id
                ');
                $stmt->execute([
                    ':position' => $position,
                    ':element_id' => $elementId
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Éléments réordonnés']);
        }
        
        elseif ($action === 'move-element') {
            $elementId = $_POST['element_id'] ?? null;
            $newIntrigueId = $_POST['intrigue_id'] ?? null;
            
            if (!$elementId) {
                throw new Exception('Element ID requis');
            }
            
            // Vérifier l'accès à l'élément
            $stmt = $pdo->prepare('
                SELECT e.id, e.intrigue_id, i.project_id
                FROM element e
                LEFT JOIN intrigue i ON e.intrigue_id = i.id
                WHERE e.id = :element_id
            ');
            $stmt->execute([':element_id' => $elementId]);
            $element = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$element) {
                throw new Exception('Élément non trouvé');
            }
            
            // Vérifier l'accès au projet
            $projectId = $element['project_id'];
            $stmt = $pdo->prepare('SELECT id FROM project WHERE id = :id AND author_id = :author_id');
            $stmt->execute([':id' => $projectId, ':author_id' => $userId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Accès refusé');
            }
            
            // Si nouvelle intrigue, vérifier qu'elle existe dans le même projet
            if ($newIntrigueId && $newIntrigueId !== 'null') {
                $stmt = $pdo->prepare('
                    SELECT id FROM intrigue 
                    WHERE id = :intrigue_id AND project_id = :project_id
                ');
                $stmt->execute([':intrigue_id' => $newIntrigueId, ':project_id' => $projectId]);
                
                if (!$stmt->fetch()) {
                    throw new Exception('Intrigue invalide');
                }
            }
            
            // Mettre à jour l'élément
            $newIntrigueValue = ($newIntrigueId && $newIntrigueId !== 'null') ? $newIntrigueId : null;
            $stmt = $pdo->prepare('UPDATE element SET intrigue_id = :intrigue_id WHERE id = :element_id');
            $stmt->execute([':intrigue_id' => $newIntrigueValue, ':element_id' => $elementId]);
            
            echo json_encode(['success' => true, 'message' => 'Élément déplacé']);
        }
        
        elseif ($action === 'update-positions') {
            $positions = json_decode(file_get_contents('php://input'), true)['positions'] ?? [];
            
            if (empty($positions)) {
                throw new Exception('Positions vides');
            }
            
            foreach ($positions as $pos) {
                $stmt = $pdo->prepare('
                    UPDATE element SET position = :position 
                    WHERE id = :element_id
                ');
                $stmt->execute([
                    ':position' => $pos['position'],
                    ':element_id' => $pos['element_id']
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Positions mises à jour']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
