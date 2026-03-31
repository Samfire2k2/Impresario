<?php
/**
 * Fonctions helper pour les palettes de couleurs
 */

/**
 * Récupère une palette complète avec toutes ses couleurs
 */
function getPaletteById($db, $palette_id) {
    $stmt = $db->prepare("
        SELECT 
            cp.id, cp.name, cp.code, cp.description,
            GROUP_CONCAT(pc.role || ':' || pc.hex_color) as colors
        FROM color_palette cp
        LEFT JOIN palette_color pc ON cp.id = pc.palette_id
        WHERE cp.id = ?
        GROUP BY cp.id, cp.name, cp.code, cp.description
    ");
    $stmt->execute([$palette_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Parse les couleurs en array
        $colors = [];
        if ($result['colors']) {
            $pairs = explode(',', $result['colors']);
            foreach ($pairs as $pair) {
                [$role, $hex] = explode(':', $pair);
                $colors[$role] = $hex;
            }
        }
        $result['colors'] = $colors;
    }
    
    return $result;
}

/**
 * Récupère toutes les palettes disponibles
 */
function getAllPalettes($db) {
    $stmt = $db->prepare("
        SELECT id, name, code, description, is_default 
        FROM color_palette 
        ORDER BY is_default DESC, name ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère la palette par défaut
 */
function getDefaultPalette($db) {
    $stmt = $db->prepare("
        SELECT 
            cp.id, cp.name, cp.code, cp.description,
            GROUP_CONCAT(pc.role || ':' || pc.hex_color) as colors
        FROM color_palette cp
        LEFT JOIN palette_color pc ON cp.id = pc.palette_id
        WHERE cp.is_default = true
        GROUP BY cp.id, cp.name, cp.code, cp.description
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $colors = [];
        if ($result['colors']) {
            $pairs = explode(',', $result['colors']);
            foreach ($pairs as $pair) {
                [$role, $hex] = explode(':', $pair);
                $colors[$role] = $hex;
            }
        }
        $result['colors'] = $colors;
    }
    
    return $result;
}

/**
 * Récupère la couleur spécifique d'une palette pour un rôle
 */
function getPaletteColor($db, $palette_id, $role) {
    $stmt = $db->prepare("
        SELECT hex_color 
        FROM palette_color 
        WHERE palette_id = ? AND role = ?
    ");
    $stmt->execute([$palette_id, $role]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['hex_color'] : null;
}

/**
 * Génère les CSS variables pour une palette
 */
function generatePaletteCSSVariables($db, $palette_id) {
    $palette = getPaletteById($db, $palette_id);
    
    if (!$palette) {
        return '';
    }
    
    $colors = $palette['colors'];
    $css = ':root {
    /* Palette: ' . $palette['name'] . ' */
    --bg-color-1: ' . ($colors['bg_1'] ?? '#fef5e7') . ';
    --bg-color-2: ' . ($colors['bg_2'] ?? '#f8e8d8') . ';
    --bg-color-3: ' . ($colors['bg_3'] ?? '#e8d5c4') . ';
    --bg-color-4: ' . ($colors['bg_4'] ?? '#d4c4b0') . ';
    --button-color: ' . ($colors['button'] ?? '#d4a574') . ';
    --title-color: ' . ($colors['title'] ?? '#8b6f47') . ';
    --scene-default-color: ' . ($colors['scene_default'] ?? '#fef5e7') . ';
}';
    
    return $css;
}

/**
 * Récupère les préférences d'utilisateur
 */
function getUserPreferences($db, $author_id) {
    $stmt = $db->prepare("
        SELECT * FROM user_preferences WHERE author_id = ?
    ");
    $stmt->execute([$author_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Met à jour les préférences d'utilisateur
 */
function updateUserPreferences($db, $author_id, $theme = null, $palette_id = null, $language = null) {
    // Vérifier si des préférences existent déjà
    $prefs = getUserPreferences($db, $author_id);
    
    if (!$prefs) {
        // Créer de nouvelles préférences
        $stmt = $db->prepare("
            INSERT INTO user_preferences (author_id, theme, palette_id, language)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $author_id,
            $theme ?? 'light',
            $palette_id ?? 1,
            $language ?? 'fr'
        ]);
    } else {
        // Mettre à jour les préférences existantes
        $updates = [];
        $values = [];
        
        if ($theme !== null) {
            $updates[] = "theme = ?";
            $values[] = $theme;
        }
        if ($palette_id !== null) {
            $updates[] = "palette_id = ?";
            $values[] = $palette_id;
        }
        if ($language !== null) {
            $updates[] = "language = ?";
            $values[] = $language;
        }
        
        if (empty($updates)) {
            return true; // Rien à mettre à jour
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $author_id;
        
        $sql = "UPDATE user_preferences SET " . implode(', ', $updates) . " WHERE author_id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }
}

/**
 * Récupère les couleurs CSS d'un projet
 */
function getProjectColors($db, $project_id) {
    try {
        $stmt = $db->prepare("
            SELECT 
                bg_color_1, bg_color_2, bg_color_3, bg_color_4,
                button_color, title_color, scene_default_color
            FROM project
            WHERE id = ?
        ");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            return [
                'bg_1' => $project['bg_color_1'] ?? '#fef5e7',
                'bg_2' => $project['bg_color_2'] ?? '#f8e8d8',
                'bg_3' => $project['bg_color_3'] ?? '#e8d5c4',
                'bg_4' => $project['bg_color_4'] ?? '#d4c4b0',
                'button' => $project['button_color'] ?? '#d4a574',
                'title' => $project['title_color'] ?? '#8b6f47',
                'scene_default' => $project['scene_default_color'] ?? '#fef5e7'
            ];
        }
    } catch (PDOException $e) {
        // Les colonnes n'existent pas encore - les migrations doivent être appliquées
        error_log("Colonnes de couleur manquantes dans la table project: " . $e->getMessage());
    }
    
    // Retourner les couleurs par défaut
    return getDefaultPalette($db)['colors'] ?? [];
}

/**
 * Génère CSS gradient pour un projet
 */
function generateProjectGradient($db, $project_id, $format = 'css') {
    $colors = getProjectColors($db, $project_id);
    
    if (empty($colors)) {
        return '';
    }
    
    $bg1 = $colors['bg_1'] ?? '#fef5e7';
    $bg2 = $colors['bg_2'] ?? '#f8e8d8';
    
    if ($format === 'css') {
        return "linear-gradient(135deg, {$bg1} 0%, {$bg2} 100%)";
    }
    
    return [
        'from' => $bg1,
        'to' => $bg2
    ];
}
