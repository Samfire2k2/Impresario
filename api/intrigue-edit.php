<?php
/**
 * API Intrigues Avancée - Éditer, déplacer, dépendances
 */

header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    if ($method === 'POST') {
        $projectId = $_POST['project_id'] ?? 0;
        
        // Vérifier accès projet
        $stmt = $pdo->prepare('SELECT id FROM project WHERE id = ? AND author_id = ?');
        $stmt->execute([$projectId, $userId]);
        if (!$stmt->fetch()) throw new Exception('Accès refusé');
        
        if ($action === 'update-intrigue') {
            $intrigueId = $_POST['intrigue_id'] ?? 0;
            $title = $_POST['title'] ?? null;
            $description = $_POST['description'] ?? null;
            $color = $_POST['color'] ?? null;
            
            $stmt = $pdo->prepare('
                SELECT i.id FROM intrigue i 
                WHERE i.id = ? AND i.project_id = ?
            ');
            $stmt->execute([$intrigueId, $projectId]);
            if (!$stmt->fetch()) throw new Exception('Intrigue non trouvée');
            
            $updates = [];
            $params = [];
            
            if ($title !== null) {
                $updates[] = 'title = ?';
                $params[] = $title;
            }
            if ($description !== null) {
                $updates[] = 'description = ?';
                $params[] = $description;
            }
            if ($color !== null) {
                $updates[] = 'color = ?';
                $params[] = $color;
            }
            
            if (empty($updates)) throw new Exception('Aucune mise à jour');
            
            $params[] = $intrigueId;
            $sql = 'UPDATE intrigue SET ' . implode(', ', $updates) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Intrigue mise à jour']);
        }
        
        elseif ($action === 'move-intrigue') {
            $intrigueId = $_POST['intrigue_id'] ?? 0;
            $direction = $_POST['direction'] ?? 'down'; // up ou down
            
            // Récupérer position actuelle
            $stmt = $pdo->prepare('
                SELECT position FROM intrigue WHERE id = ? AND project_id = ?
            ');
            $stmt->execute([$intrigueId, $projectId]);
            $current = $stmt->fetch();
            if (!$current) throw new Exception('Intrigue non trouvée');
            
            $currentPos = $current['position'];
            
            if ($direction === 'down') {
                // Échanger avec la suivante
                $stmt = $pdo->prepare('
                    SELECT id, position FROM intrigue 
                    WHERE project_id = ? AND position > ?
                    ORDER BY position ASC LIMIT 1
                ');
                $stmt->execute([$projectId, $currentPos]);
                $next = $stmt->fetch();
                if (!$next) throw new Exception('Pas d\'intrigue après');
                
                // Permuter positions
                $pdo->prepare('UPDATE intrigue SET position = ? WHERE id = ?')
                    ->execute([-1, $intrigueId]);
                $pdo->prepare('UPDATE intrigue SET position = ? WHERE id = ?')
                    ->execute([$currentPos, $next['id']]);
                $pdo->prepare('UPDATE intrigue SET position = ? WHERE id = ?')
                    ->execute([$next['position'], $intrigueId]);
            }
            else {
                // Échanger avec la précédente
                $stmt = $pdo->prepare('
                    SELECT id, position FROM intrigue 
                    WHERE project_id = ? AND position < ?
                    ORDER BY position DESC LIMIT 1
                ');
                $stmt->execute([$projectId, $currentPos]);
                $prev = $stmt->fetch();
                if (!$prev) throw new Exception('Pas d\'intrigue avant');
                
                // Permuter positions
                $pdo->prepare('UPDATE intrigue SET position = ? WHERE id = ?')
                    ->execute([-1, $intrigueId]);
                $pdo->prepare('UPDATE intrigue SET position = ? WHERE id = ?')
                    ->execute([$currentPos, $prev['id']]);
                $pdo->prepare('UPDATE intrigue SET position = ? WHERE id = ?')
                    ->execute([$prev['position'], $intrigueId]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Intrigue déplacée']);
        }
    }
    else {
        throw new Exception('Méthode non supportée');
    }
}
catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
