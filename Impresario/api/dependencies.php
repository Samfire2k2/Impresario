<?php
// API pour gérer les dépendances
header('Content-Type: application/json');
include '../includes/config.php';
include '../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add-dependency') {
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
