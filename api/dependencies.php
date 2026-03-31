<?php
/**
 * API Dépendances - Créer, valider cycles, supprimer
 */

header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

/**
 * Vérifie si une dépendance créerait un cycle
 */
function wouldCreateCycle($pdo, $elementId, $blockedId) {
    // Vérifier si on peut atteindre $elementId depuis $blockedId
    $visited = [];
    
    function canReach($from, $to, $pdo, &$visited) {
        if ($from === $to) return true;
        if (isset($visited[$from])) return false;
        $visited[$from] = true;
        
        $stmt = $pdo->prepare('SELECT blocked_element_id FROM dependency WHERE element_id = ?');
        $stmt->execute([$from]);
        $deps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($deps as $dep) {
            if (canReach($dep['blocked_element_id'], $to, $pdo, $visited)) {
                return true;
            }
        }
        return false;
    }
    
    return canReach($blockedId, $elementId, $pdo, $visited);
}

try {
    if ($method === 'GET') {
        if ($action === 'get-dependencies') {
            $elementId = $_GET['element_id'] ?? 0;
            
            $stmt = $pdo->prepare('
                SELECT d.*, 
                       e1.title as element_title, e1.intrigue_id as element_intrigue,
                       e2.title as blocked_title, e2.intrigue_id as blocked_intrigue
                FROM dependency d
                JOIN element e1 ON d.element_id = e1.id
                JOIN element e2 ON d.blocked_element_id = e2.id
                WHERE d.element_id = ?
            ');
            $stmt->execute([$elementId]);
            
            echo json_encode(['success' => true, 'dependencies' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        elseif ($action === 'get-dependencies-by-intrigue') {
            $intrigueId = $_GET['intrigue_id'] ?? 0;
            
            $stmt = $pdo->prepare('
                SELECT d.*, 
                       e1.title as element_title, e1.intrigue_id,
                       e2.title as blocked_title
                FROM dependency d
                JOIN element e1 ON d.element_id = e1.id
                JOIN element e2 ON d.blocked_element_id = e2.id
                WHERE e1.intrigue_id = ?
                ORDER BY e1.position ASC
            ');
            $stmt->execute([$intrigueId]);
            
            echo json_encode(['success' => true, 'dependencies' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    }
    elseif ($method === 'POST') {
        if ($action === 'create-dependency' || $action === 'add-dependency') {
            $elementId = intval($_POST['element_id'] ?? 0);
            $blockedId = intval($_POST['blocked_element_id'] ?? 0);
            
            if ($elementId === $blockedId) {
                throw new Exception('Un élément ne peut pas dépendre de lui-même');
            }
            
            // Vérifier les accès
            $stmt = $pdo->prepare('
                SELECT e.id FROM element e
                LEFT JOIN intrigue i ON e.intrigue_id = i.id
                LEFT JOIN project p ON i.project_id = p.id
                WHERE e.id = ? AND (p.author_id = ? OR (i.id IS NULL AND ? > 0))
            ');
            $stmt->execute([$elementId, $userId, 1]);
            if (!$stmt->fetch()) {
                throw new Exception('Élément non autorisé');
            }
            
            // Vérifier dépendance déjà existe
            $stmt = $pdo->prepare('
                SELECT id FROM dependency WHERE element_id = ? AND blocked_element_id = ?
            ');
            $stmt->execute([$elementId, $blockedId]);
            if ($stmt->fetch()) {
                throw new Exception('Cette dépendance existe déjà');
            }
            
            // Vérifier les cycles
            if (wouldCreateCycle($pdo, $elementId, $blockedId)) {
                throw new Exception('Cette dépendance créerait un cycle');
            }
            
            // Créer la dépendance
            $stmt = $pdo->prepare('
                INSERT INTO dependency (element_id, blocked_element_id)
                VALUES (?, ?)
            ');
            $stmt->execute([$elementId, $blockedId]);
            
            echo json_encode(['success' => true, 'message' => 'Dépendance créée']);
        }
        
        elseif ($action === 'delete-dependency' || $action === 'remove-dependency') {
            $dependencyId = intval($_POST['dependency_id'] ?? 0);
            
            $stmt = $pdo->prepare('DELETE FROM dependency WHERE id = ?');
            $stmt->execute([$dependencyId]);
            
            echo json_encode(['success' => true, 'message' => 'Dépendance supprimée']);
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
?>
        $elementId = intval($_POST['element_id'] ?? 0);
        $blockedElementId = intval($_POST['blocked_element_id'] ?? 0);
        
        // Vérifier que les deux éléments appartiennent à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT e.id FROM element e
            JOIN intrigue i ON e.intrigue_id = i.id
            JOIN project p ON i.project_id = p.id
            WHERE e.id = :element_id AND p.author_id = :author_id
        ');
        $stmt->execute([':element_id' => $elementId, ':author_id' => $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé à l\'élément']);
            exit;
        }
        
        $stmt->execute([':element_id' => $blockedElementId, ':author_id' => $userId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé à l\'élément bloqué']);
            exit;
        }
        
        $result = addDependency($pdo, $elementId, $blockedElementId);
        echo json_encode(['success' => $result]);
    }
    elseif ($action === 'remove-dependency') {
        $dependencyId = intval($_POST['dependency_id'] ?? 0);
        
        // Vérifier que la dépendance appartient à l'utilisateur
        $stmt = $pdo->prepare('
            SELECT d.id FROM dependency d
            JOIN element e ON d.element_id = e.id
            JOIN intrigue i ON e.intrigue_id = i.id
            JOIN project p ON i.project_id = p.id
            WHERE d.id = :dependency_id AND p.author_id = :author_id
        ');
        $stmt->execute([':dependency_id' => $dependencyId, ':author_id' => $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
        
        $stmt = $pdo->prepare('DELETE FROM dependency WHERE id = :id');
        $result = $stmt->execute([':id' => $dependencyId]);
        
        echo json_encode(['success' => $result]);
    }
    elseif ($action === 'check-position') {
        $elementId = intval($_POST['element_id'] ?? 0);
        $position = intval($_POST['position'] ?? 0);
        
        $element = getElement($pdo, $elementId);
        if (!$element) {
            http_response_code(404);
            echo json_encode(['error' => 'Élément non trouvé']);
            exit;
        }
        
        $canPlace = canPlaceElement($pdo, $elementId, $position);
        echo json_encode(['can_place' => $canPlace]);
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
