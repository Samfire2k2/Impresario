<?php
/**
 * API Tags - Créer, éditer, assigner, désassigner
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
        if ($action === 'get-tags') {
            $intrigueId = $_GET['intrigue_id'] ?? 0;
            $stmt = $pdo->prepare('
                SELECT t.* FROM tag t 
                JOIN intrigue i ON t.intrigue_id = i.id 
                JOIN project p ON i.project_id = p.id 
                WHERE t.intrigue_id = ? AND p.author_id = ? 
                ORDER BY t.label ASC
            ');
            $stmt->execute([$intrigueId, $userId]);
            echo json_encode(['success' => true, 'tags' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    } elseif ($method === 'POST') {
        if ($action === 'create-tag' || $action === 'add-tag') {
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
                throw new Exception('Accès refusé');
            }
            
            if (empty($label)) {
                throw new Exception('Libellé requis');
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO tag (intrigue_id, label, color) 
                VALUES (?, ?, ?)
                RETURNING id
            ');
            $stmt->execute([$intrigueId, $label, $color]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'tag_id' => $result['id'] ?? null,
                'message' => 'Tag créé'
            ]);
        }
        
        elseif ($action === 'update-tag') {
            $tagId = intval($_POST['tag_id'] ?? 0);
            $label = sanitizeInput($_POST['label'] ?? '');
            $color = sanitizeInput($_POST['color'] ?? '#3498db');
            
            // Vérifier l'accès
            $stmt = $pdo->prepare('
                SELECT t.id FROM tag t 
                JOIN intrigue i ON t.intrigue_id = i.id 
                JOIN project p ON i.project_id = p.id 
                WHERE t.id = :tag_id AND p.author_id = :author_id
            ');
            $stmt->execute([':tag_id' => $tagId, ':author_id' => $userId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Accès refusé');
            }
            
            $stmt = $pdo->prepare('
                UPDATE tag SET label = ?, color = ? WHERE id = ?
            ');
            $stmt->execute([$label, $color, $tagId]);
            
            echo json_encode(['success' => true, 'message' => 'Tag mis à jour']);
        }
        
        elseif ($action === 'delete-tag') {
            $tagId = intval($_POST['tag_id'] ?? 0);
            
            // Vérifier l'accès
            $stmt = $pdo->prepare('
                SELECT t.id FROM tag t 
                JOIN intrigue i ON t.intrigue_id = i.id 
                JOIN project p ON i.project_id = p.id 
                WHERE t.id = :tag_id AND p.author_id = :author_id
            ');
            $stmt->execute([':tag_id' => $tagId, ':author_id' => $userId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Accès refusé');
            }
            
            // Supprimer les assignations et le tag
            $stmt = $pdo->prepare('DELETE FROM element_tag WHERE tag_id = ?');
            $stmt->execute([$tagId]);
            
            $stmt = $pdo->prepare('DELETE FROM tag WHERE id = ?');
            $stmt->execute([$tagId]);
            
            echo json_encode(['success' => true, 'message' => 'Tag supprimé']);
        }
        
        elseif ($action === 'assign-tag') {
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
                throw new Exception('Accès refusé');
            }
            
            // Ajouter le tag à l'élément
            $stmt = $pdo->prepare('
                INSERT INTO element_tag (element_id, tag_id) 
                VALUES (?, ?) 
                ON CONFLICT DO NOTHING
            ');
            $stmt->execute([$elementId, $tagId]);
            
            echo json_encode(['success' => true, 'message' => 'Tag assigné']);
        }
        
        elseif ($action === 'unassign-tag') {
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
                throw new Exception('Accès refusé');
            }
            
            $stmt = $pdo->prepare('
                SELECT et.id FROM element_tag et
                WHERE et.element_id = ? AND et.tag_id = ?
            ');
            $stmt->execute([$elementId, $tagId]);
            if (!$stmt->fetch()) {
                throw new Exception('Assignation non trouvée');
            }
            
            $stmt = $pdo->prepare('DELETE FROM element_tag WHERE element_id = ? AND tag_id = ?');
            $stmt->execute([$elementId, $tagId]);
            echo json_encode(['success' => true, 'message' => 'Tag désassigné']);
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
