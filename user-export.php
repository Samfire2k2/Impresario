<?php
/**
 * Export Utilisateur Complet - Toutes les données de l'utilisateur
 */

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$username = getCurrentUsername();
$format = $_GET['format'] ?? 'json'; // json ou sql

try {
    if ($format === 'json') {
        // Récupérer TOUS les projets, intrigues, éléments, tags, dépendances
        $stmt = $pdo->prepare('
            SELECT id, title, description, date_creation, orientation, palette_id
            FROM project WHERE author_id = ? ORDER BY id ASC
        ');
        $stmt->execute([$userId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'username' => $username,
            'projects' => []
        ];
        
        foreach ($projects as $project) {
            $projectData = $project;
            
            // Intrigues
            $stmt = $pdo->prepare('
                SELECT id, title, description, position, color
                FROM intrigue WHERE project_id = ? ORDER BY position
            ');
            $stmt->execute([$project['id']]);
            $projectData['intrigues'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pour chaque intrigue - éléments et tags
            foreach ($projectData['intrigues'] as &$intrigue) {
                $stmt = $pdo->prepare('
                    SELECT id, title, description, type, position, color
                    FROM element WHERE intrigue_id = ? ORDER BY position
                ');
                $stmt->execute([$intrigue['id']]);
                $intrigue['elements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Tags
                $stmt = $pdo->prepare('
                    SELECT id, label, color FROM tag WHERE intrigue_id = ?
                ');
                $stmt->execute([$intrigue['id']]);
                $intrigue['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Éléments de poche (sans intrigue)
            $stmt = $pdo->prepare('
                SELECT id, title, description, type, position, color
                FROM element WHERE intrigue_id IS NULL AND type IN (\'scene\', \'marqueur\')
                ORDER BY position
            ');
            $stmt->execute([]);
            $projectData['pocket'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Dépendances
            $stmt = $pdo->prepare('
                SELECT d.id, d.element_id, d.blocked_element_id,
                       e1.title as element_title, e2.title as blocked_title
                FROM dependency d
                JOIN element e1 ON d.element_id = e1.id
                JOIN element e2 ON d.blocked_element_id = e2.id
                WHERE e1.intrigue_id IN (
                    SELECT id FROM intrigue WHERE project_id = ?
                ) OR e2.intrigue_id IN (
                    SELECT id FROM intrigue WHERE project_id = ?
                )
            ');
            $stmt->execute([$project['id'], $project['id']]);
            $projectData['dependencies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data['projects'][] = $projectData;
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="impresario_export_' . $username . '_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    elseif ($format === 'sql') {
        // Export SQL (sauvegarde complète)
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="impresario_export_' . $username . '_' . date('Y-m-d') . '.sql"');
        
        echo "-- Impresario Export pour: $username\n";
        echo "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Projets
        $stmt = $pdo->prepare('
            SELECT * FROM project WHERE author_id = ? ORDER BY id
        ');
        $stmt->execute([$userId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($projects as $project) {
            echo "-- Projet: {$project['title']}\n";
            echo "INSERT INTO project (author_id, title, description, date_creation, orientation, palette_id) ";
            echo "VALUES ({$project['author_id']}, '{$project['title']}', '{$project['description']}', '{$project['date_creation']}', '{$project['orientation']}', {$project['palette_id']});\n\n";
            
            // Intrigues
            $stmt = $pdo->prepare('SELECT * FROM intrigue WHERE project_id = ? ORDER BY position');
            $stmt->execute([$project['id']]);
            
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $intrigue) {
                echo "INSERT INTO intrigue (project_id, title, description, position, color) ";
                echo "VALUES ({$intrigue['project_id']}, '{$intrigue['title']}', '{$intrigue['description']}', {$intrigue['position']}, '{$intrigue['color']}');\n";
            }
        }
        
        echo "\n-- Fin de l'export\n";
    }
}
catch (Exception $e) {
    http_response_code(400);
    echo 'Erreur: ' . $e->getMessage();
}
