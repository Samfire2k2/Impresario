<?php
// ==================== WRITER MODE FUNCTIONS ====================
// Gestion du contenu, étatsatistiques, exports pour Impresario Writer

// ==================== WORD COUNT & TEXT STATS ====================

/**
 * Compte les mots dans un texte (strip HTML & markdown)
 */
function countWords($text) {
    // Remove HTML tags
    $text = strip_tags($text);
    // Remove markdown formatting
    $text = preg_replace('/[#*_`\[\]()]/u', '', $text);
    // Trim and split by whitespace
    $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
}

/**
 * Obtient les statistiques de contenu d'un texte
 */
function getTextStats($text) {
    $wordCount = countWords($text);
    $charCount = mb_strlen($text);
    $charCountNoSpaces = mb_strlen(preg_replace('/\s+/u', '', $text));
    $paragraphs = count(array_filter(preg_split('/\n\n+/u', $text)));
    
    return [
        'words' => $wordCount,
        'characters' => $charCount,
        'characters_no_spaces' => $charCountNoSpaces,
        'paragraphs' => $paragraphs,
        'reading_time_minutes' => ceil($wordCount / 200) // Average reading speed
    ];
}

// ==================== ELEMENT CONTENT MANAGEMENT ====================

/**
 * Sauvegarde le contenu d'une scène avec mise à jour du word count
 */
function updateElementContent($pdo, $elementId, $content, $status = null, $writingNotes = null) {
    $wordCount = countWords($content);
    
    $query = 'UPDATE element SET content = :content, word_count = :word_count, updated_at = NOW()';
    $params = [':content' => $content, ':word_count' => $wordCount];
    
    if ($status !== null) {
        $query .= ', status = :status';
        $params[':status'] = $status;
    }
    if ($writingNotes !== null) {
        $query .= ', writing_notes = :writing_notes';
        $params[':writing_notes'] = $writingNotes;
    }
    
    $query .= ' WHERE id = :id';
    $params[':id'] = $elementId;
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute($params);
    
    // Create version history
    if ($result) {
        createElementVersion($pdo, $elementId, $content, $wordCount);
        // Update parent intrigue word count
        updateIntrigueWordCount($pdo, $elementId);
        // Update project word count
        updateProjectWordCount($pdo, $elementId);
    }
    
    return $result;
}

/**
 * Crée une version historique d'une scène
 */
function createElementVersion($pdo, $elementId, $content, $wordCount, $userId = null, $summary = null) {
    // Get current version number
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(version_number), 0) + 1 as next_version FROM element_version WHERE element_id = :id');
    $stmt->execute([':id' => $elementId]);
    $result = $stmt->fetch();
    $versionNumber = $result['next_version'];
    
    $userId = $userId ?? ($_SESSION['user_id'] ?? 1);
    
    $stmt = $pdo->prepare('
        INSERT INTO element_version (element_id, content, word_count, version_number, author_id, change_summary)
        VALUES (:element_id, :content, :word_count, :version_number, :author_id, :summary)
    ');
    
    return $stmt->execute([
        ':element_id' => $elementId,
        ':content' => $content,
        ':word_count' => $wordCount,
        ':version_number' => $versionNumber,
        ':author_id' => $userId,
        ':summary' => $summary
    ]);
}

/**
 * Récupère l'historique des versions d'une scène
 */
function getElementVersionHistory($pdo, $elementId, $limit = 10) {
    $stmt = $pdo->prepare('
        SELECT ev.*, a.name as author_name
        FROM element_version ev
        LEFT JOIN author a ON ev.author_id = a.id
        WHERE ev.element_id = :id
        ORDER BY ev.created_at DESC
        LIMIT :limit
    ');
    $stmt->execute([':id' => $elementId, ':limit' => $limit]);
    return $stmt->fetchAll();
}

/**
 * Récupère une version spécifique
 */
function getElementVersion($pdo, $versionId) {
    $stmt = $pdo->prepare('SELECT * FROM element_version WHERE id = :id');
    $stmt->execute([':id' => $versionId]);
    return $stmt->fetch();
}

// ==================== WORD COUNT AGGREGATION ====================

/**
 * Met à jour le word count d'une intrigue (somme de ses éléments)
 */
function updateIntrigueWordCount($pdo, $elementId) {
    // Get intrigue_id from element
    $stmt = $pdo->prepare('SELECT intrigue_id FROM element WHERE id = :id');
    $stmt->execute([':id' => $elementId]);
    $element = $stmt->fetch();
    
    if ($element) {
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(word_count), 0) as total FROM element 
            WHERE intrigue_id = :intrigue_id
        ');
        $stmt->execute([':intrigue_id' => $element['intrigue_id']]);
        $result = $stmt->fetch();
        
        $stmt = $pdo->prepare('UPDATE intrigue SET word_count = :count WHERE id = :id');
        $stmt->execute([':count' => $result['total'], ':id' => $element['intrigue_id']]);
    }
}

/**
 * Met à jour le word count d'un projet (somme de tous ses éléments)
 */
function updateProjectWordCount($pdo, $elementId) {
    // Get project_id from element via intrigue
    $stmt = $pdo->prepare('
        SELECT p.id FROM project p
        JOIN intrigue i ON p.id = i.project_id
        WHERE i.id = (SELECT intrigue_id FROM element WHERE id = :id)
    ');
    $stmt->execute([':id' => $elementId]);
    $project = $stmt->fetch();
    
    if ($project) {
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(e.word_count), 0) as total FROM element e
            JOIN intrigue i ON e.intrigue_id = i.id
            WHERE i.project_id = :project_id
        ');
        $stmt->execute([':project_id' => $project['id']]);
        $result = $stmt->fetch();
        
        $stmt = $pdo->prepare('UPDATE project SET current_word_count = :count WHERE id = :id');
        $stmt->execute([':count' => $result['total'], ':id' => $project['id']]);
    }
}

// ==================== PROJECT STATISTICS ====================

/**
 * Récupère les statistiques complètes d'un projet
 */
function getProjectStats($pdo, $projectId) {
    // Get project info
    $stmt = $pdo->prepare('
        SELECT 
            p.id,
            p.title,
            p.genre,
            p.status,
            p.target_word_count,
            p.current_word_count,
            p.date_creation,
            COUNT(DISTINCT i.id) as intrigue_count,
            COUNT(DISTINCT e.id) as scene_count,
            COUNT(DISTINCT c.id) as character_count,
            COUNT(DISTINCT wn.id) as note_count,
            ROUND(100.0 * COALESCE(p.current_word_count, 0) / NULLIF(p.target_word_count, 0), 2) as progress_percent
        FROM project p
        LEFT JOIN intrigue i ON p.id = i.project_id
        LEFT JOIN element e ON i.id = e.intrigue_id
        LEFT JOIN character c ON p.id = c.project_id
        LEFT JOIN writing_note wn ON p.id = wn.project_id
        WHERE p.id = :id
        GROUP BY p.id, p.title, p.genre, p.status, p.target_word_count, p.current_word_count, p.date_creation
    ');
    $stmt->execute([':id' => $projectId]);
    return $stmt->fetch();
}

/**
 * Récupère les statistiques détaillées par intrigue
 */
function getIntrigueStats($pdo, $intrigueId) {
    $stmt = $pdo->prepare('
        SELECT 
            i.id,
            i.title,
            i.status,
            i.word_count,
            COUNT(e.id) as scene_count,
            COUNT(CASE WHEN e.status = \'finalized\' THEN 1 END) as finalized_count
        FROM intrigue i
        LEFT JOIN element e ON i.id = e.intrigue_id
        WHERE i.id = :id
        GROUP BY i.id, i.title, i.status, i.word_count
    ');
    $stmt->execute([':id' => $intrigueId]);
    return $stmt->fetch();
}

// ==================== CHARACTER MANAGEMENT ====================

/**
 * Crée un personnage
 */
function createCharacter($pdo, $projectId, $name, $role = null, $description = null) {
    $stmt = $pdo->prepare('
        INSERT INTO character (project_id, name, role, description) 
        VALUES (:project_id, :name, :role, :description)
        RETURNING id
    ');
    $stmt->execute([
        ':project_id' => $projectId,
        ':name' => $name,
        ':role' => $role,
        ':description' => $description
    ]);
    $result = $stmt->fetch();
    return $result['id'];
}

/**
 * Récupère tous les personnages d'un projet
 */
function getProjectCharacters($pdo, $projectId) {
    $stmt = $pdo->prepare('
        SELECT * FROM character WHERE project_id = :id ORDER BY name
    ');
    $stmt->execute([':id' => $projectId]);
    return $stmt->fetchAll();
}

/**
 * Lie un personnage à une scène
 */
function linkCharacterToElement($pdo, $elementId, $characterId, $appearanceType = 'main') {
    $stmt = $pdo->prepare('
        INSERT INTO element_character (element_id, character_id, appearance_type)
        VALUES (:element_id, :character_id, :appearance_type)
        ON CONFLICT (element_id, character_id) DO UPDATE SET appearance_type = :appearance_type
    ');
    return $stmt->execute([
        ':element_id' => $elementId,
        ':character_id' => $characterId,
        ':appearance_type' => $appearanceType
    ]);
}

/**
 * Récupère les personnages d'une scène
 */
function getElementCharacters($pdo, $elementId) {
    $stmt = $pdo->prepare('
        SELECT c.*, ec.appearance_type FROM character c
        JOIN element_character ec ON c.id = ec.character_id
        WHERE ec.element_id = :id
        ORDER BY ec.appearance_type DESC, c.name
    ');
    $stmt->execute([':id' => $elementId]);
    return $stmt->fetchAll();
}

// ==================== WRITING NOTES ====================

/**
 * Crée une note d'écriture
 */
function createWritingNote($pdo, $projectId, $title, $content = null, $noteType = 'general') {
    $stmt = $pdo->prepare('
        INSERT INTO writing_note (project_id, title, content, note_type)
        VALUES (:project_id, :title, :content, :note_type)
        RETURNING id
    ');
    $stmt->execute([
        ':project_id' => $projectId,
        ':title' => $title,
        ':content' => $content,
        ':note_type' => $noteType
    ]);
    $result = $stmt->fetch();
    return $result['id'];
}

/**
 * Récupère toutes les notes d'écriture d'un projet
 */
function getProjectWritingNotes($pdo, $projectId, $noteType = null) {
    $query = 'SELECT * FROM writing_note WHERE project_id = :id';
    $params = [':id' => $projectId];
    
    if ($noteType) {
        $query .= ' AND note_type = :type';
        $params[':type'] = $noteType;
    }
    
    $query .= ' ORDER BY is_pinned DESC, updated_at DESC';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ==================== EXPORT UTILITIES ====================

/**
 * Prépare les données d'un projet pour l'export
 */
function prepareProjectForExport($pdo, $projectId) {
    // Get project
    $stmt = $pdo->prepare('SELECT * FROM project WHERE id = :id');
    $stmt->execute([':id' => $projectId]);
    $project = $stmt->fetch();
    
    if (!$project) return null;
    
    // Get intrigues with word count
    $stmt = $pdo->prepare('
        SELECT i.*, 
            (SELECT COALESCE(SUM(word_count), 0) FROM element WHERE intrigue_id = i.id) as word_count
        FROM intrigue i 
        WHERE i.project_id = :id 
        ORDER BY i.position
    ');
    $stmt->execute([':id' => $projectId]);
    $intrigues = $stmt->fetchAll();
    
    // Get elements for each intrigue
    $elements = [];
    $stmt = $pdo->prepare('SELECT * FROM element WHERE intrigue_id = :id ORDER BY position');
    
    foreach ($intrigues as $intrigue) {
        $stmt->execute([':id' => $intrigue['id']]);
        $elements[$intrigue['id']] = $stmt->fetchAll();
    }
    
    return [
        'project' => $project,
        'intrigues' => $intrigues,
        'elements' => $elements
    ];
}

/**
 * Formate le texte d'export (conversion basique markdown)
 */
function formatContentForExport($content, $format = 'plain') {
    if ($format === 'plain') {
        // Remove HTML/markdown formatting
        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    }
    return $content;
}
?>
