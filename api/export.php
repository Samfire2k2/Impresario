<?php
/**
 * API Export - JSON et CSV
 */

header('Content-Type: application/json');
include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = $_GET['project_id'] ?? 0;
$format = $_GET['format'] ?? 'json'; // json ou csv

try {
    // Vérifier l'accès au projet
    $stmt = $pdo->prepare('
        SELECT id, title, description FROM project 
        WHERE id = :project_id AND author_id = :author_id
    ');
    $stmt->execute([':project_id' => $projectId, ':author_id' => $userId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        throw new Exception('Projet non trouvé');
    }
    
    // Récupérer les intrigues avec leurs éléments
    $stmt = $pdo->prepare('
        SELECT id, title, description, position FROM intrigue 
        WHERE project_id = :project_id
        ORDER BY position ASC
    ');
    $stmt->execute([':project_id' => $projectId]);
    $intrigues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque intrigue, récupérer les éléments
    foreach ($intrigues as &$intrigue) {
        $stmt = $pdo->prepare('
            SELECT id, title, description, type, position FROM element 
            WHERE intrigue_id = :intrigue_id
            ORDER BY position ASC
        ');
        $stmt->execute([':intrigue_id' => $intrigue['id']]);
        $intrigue['elements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupérer les éléments de poche (sans intrigue)
    $stmt = $pdo->prepare('
        SELECT id, title, description, type, position FROM element 
        WHERE project_id = :project_id AND intrigue_id IS NULL
        ORDER BY position ASC
    ');
    $stmt->execute([':project_id' => $projectId]);
    $pocket = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'json') {
        // Export JSON
        $data = [
            'project' => $project,
            'intrigues' => $intrigues,
            'pocket' => $pocket,
            'exported_at' => date('Y-m-d H:i:s')
        ];
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . slugify($project['title']) . '_export.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    elseif ($format === 'csv') {
        // Export CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . slugify($project['title']) . '_export.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, ['Projet', 'Intrigue', 'Élément', 'Type', 'Description']);
        
        // Données
        foreach ($intrigues as $intrigue) {
            foreach ($intrigue['elements'] as $element) {
                fputcsv($output, [
                    $project['title'],
                    $intrigue['title'],
                    $element['title'],
                    $element['type'],
                    $element['description'] ?? ''
                ]);
            }
        }
        
        // Poche
        foreach ($pocket as $element) {
            fputcsv($output, [
                $project['title'],
                '[Poche]',
                $element['title'],
                $element['type'],
                $element['description'] ?? ''
            ]);
        }
        
        fclose($output);
    }
    else {
        throw new Exception('Format non supporté');
    }
}
catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Convertir un string en slug
 */
function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}
