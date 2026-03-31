<?php
/**
 * API Palettes de couleurs
 * Endpoints:
 * - GET /api/palette.php?action=get_all
 * - GET /api/palette.php?action=get&id=<palette_id>
 * - GET /api/palette.php?action=get_user_prefs
 * - POST /api/palette.php?action=update_user_prefs
 * - GET /api/palette.php?action=get_project_colors&project_id=<id>
 */

header('Content-Type: application/json');

include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/functions.php';
include __DIR__ . '/../includes/palette-functions.php';

requireLogin();

$author_id = getCurrentUserId();
$action = $_GET['action'] ?? 'get_all';

try {
    switch ($action) {
        case 'get_all':
            // Récupérer toutes les palettes
            $palettes = getAllPalettes($pdo);
            echo json_encode([
                'success' => true,
                'palettes' => $palettes
            ]);
            break;
            
        case 'get':
            // Récupérer une palette spécifique avec ses couleurs
            $palette_id = $_GET['id'] ?? null;
            if (!$palette_id) {
                throw new Exception('ID de palette requis');
            }
            
            $palette = getPaletteById($pdo, $palette_id);
            if (!$palette) {
                throw new Exception('Palette non trouvée');
            }
            
            echo json_encode([
                'success' => true,
                'palette' => $palette
            ]);
            break;
            
        case 'get_user_prefs':
            // Récupérer les préférences de l'utilisateur
            $prefs = getUserPreferences($pdo, $author_id);
            
            if (!$prefs) {
                // Créer les préférences par défaut
                $default_palette = getDefaultPalette($pdo);
                updateUserPreferences($pdo, $author_id, 'light', $default_palette['id'], 'fr');
                $prefs = getUserPreferences($pdo, $author_id);
            }
            
            echo json_encode([
                'success' => true,
                'preferences' => $prefs
            ]);
            break;
            
        case 'update_user_prefs':
            // Mettre à jour les préférences de l'utilisateur
            $data = json_decode(file_get_contents('php://input'), true);
            
            $theme = $data['theme'] ?? null;
            $palette_id = $data['palette_id'] ?? null;
            $language = $data['language'] ?? null;
            
            if (!$theme && !$palette_id && !$language) {
                throw new Exception('Au moins un paramètre doit être fourni');
            }
            
            $success = updateUserPreferences($pdo, $author_id, $theme, $palette_id, $language);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Préférences mises à jour' : 'Erreur lors de la mise à jour'
            ]);
            break;
            
        case 'get_project_colors':
            // Récupérer les couleurs d'un projet
            $project_id = $_GET['project_id'] ?? null;
            if (!$project_id) {
                throw new Exception('ID de projet requis');
            }
            
            // Vérifier que l'utilisateur a accès au projet
            $stmt = $pdo->prepare("
                SELECT id FROM project 
                WHERE id = ? AND author_id = ?
            ");
            $stmt->execute([$project_id, $author_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Accès refusé');
            }
            
            $colors = getProjectColors($pdo, $project_id);
            $gradient = generateProjectGradient($pdo, $project_id);
            
            echo json_encode([
                'success' => true,
                'colors' => $colors,
                'gradient' => $gradient
            ]);
            break;
            
        default:
            throw new Exception('Action inconnue: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
