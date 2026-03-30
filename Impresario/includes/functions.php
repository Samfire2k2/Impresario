<?php
// Fonctions utilitaires pour l'application

// Fonction pour nettoyer les entrées (trim sans échappement HTML)
function sanitizeInput($input) {
    return trim($input);
}

// Fonction pour afficher du texte en HTML en toute sécurité
function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Fonction pour valider un email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fonction pour vérifier si un utilisateur existe
function userExists($pdo, $username) {
    $stmt = $pdo->prepare('SELECT id FROM author WHERE name = :name');
    $stmt->execute([':name' => $username]);
    return $stmt->fetch() !== false;
}

// Fonction pour créer un nouvel utilisateur
function createUser($pdo, $username, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO author (name, password) VALUES (:name, :password)');
    return $stmt->execute([
        ':name' => $username,
        ':password' => $hashedPassword
    ]);
}

// Fonction pour vérifier les identifiants de connexion
function verifyLogin($pdo, $username, $password) {
    $stmt = $pdo->prepare('SELECT id, name, password FROM author WHERE name = :name');
    $stmt->execute([':name' => $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

// Fonction pour obtenir tous les projets d'un utilisateur
function getUserProjects($pdo, $userId) {
    $stmt = $pdo->prepare('SELECT id, title, description, date_creation FROM project WHERE author_id = :author_id ORDER BY date_creation DESC');
    $stmt->execute([':author_id' => $userId]);
    return $stmt->fetchAll();
}

// Fonction pour créer un nouveau projet
function createProject($pdo, $userId, $title, $description = '') {
    $stmt = $pdo->prepare('INSERT INTO project (author_id, title, description, date_creation) VALUES (:author_id, :title, :description, NOW()) RETURNING id');
    $stmt->execute([
        ':author_id' => $userId,
        ':title' => $title,
        ':description' => $description
    ]);
    $result = $stmt->fetch();
    return $result['id'];
}

// Fonction pour obtenir les détails d'un projet
function getProject($pdo, $projectId, $userId) {
    $stmt = $pdo->prepare('SELECT id, title, description, date_creation FROM project WHERE id = :id AND author_id = :author_id');
    $stmt->execute([':id' => $projectId, ':author_id' => $userId]);
    return $stmt->fetch();
}

// Fonction pour mettre à jour un projet
function updateProject($pdo, $projectId, $userId, $title, $description) {
    $stmt = $pdo->prepare('UPDATE project SET title = :title, description = :description WHERE id = :id AND author_id = :author_id');
    return $stmt->execute([
        ':id' => $projectId,
        ':author_id' => $userId,
        ':title' => $title,
        ':description' => $description
    ]);
}

// Fonction pour supprimer un projet
function deleteProject($pdo, $projectId, $userId) {
    $stmt = $pdo->prepare('DELETE FROM project WHERE id = :id AND author_id = :author_id');
    return $stmt->execute([':id' => $projectId, ':author_id' => $userId]);
}

// Fonction pour obtenir toutes les intrigues d'un projet
function getIntrigues($pdo, $projectId) {
    $stmt = $pdo->prepare('SELECT id, title, description FROM intrigue WHERE project_id = :project_id ORDER BY position');
    $stmt->execute([':project_id' => $projectId]);
    return $stmt->fetchAll();
}

// Fonction pour créer une nouvelle intrigue
function createIntrigue($pdo, $projectId, $title, $description = '') {
    $stmt = $pdo->prepare('
        SELECT COALESCE(MAX(position), 0) + 1 as new_position FROM intrigue WHERE project_id = :project_id
    ');
    $stmt->execute([':project_id' => $projectId]);
    $result = $stmt->fetch();
    $position = $result['new_position'];
    
    $stmt = $pdo->prepare('
        INSERT INTO intrigue (project_id, title, description, position) 
        VALUES (:project_id, :title, :description, :position) 
        RETURNING id
    ');
    $stmt->execute([
        ':project_id' => $projectId,
        ':title' => $title,
        ':description' => $description,
        ':position' => $position
    ]);
    $result = $stmt->fetch();
    return $result['id'];
}

// Fonction pour obtenir les détails d'une intrigue
function getIntrigue($pdo, $intrigueId) {
    $stmt = $pdo->prepare('SELECT id, project_id, title, description FROM intrigue WHERE id = :id');
    $stmt->execute([':id' => $intrigueId]);
    return $stmt->fetch();
}

// Fonction pour mettre à jour une intrigue
function updateIntrigue($pdo, $intrigueId, $title, $description) {
    $stmt = $pdo->prepare('UPDATE intrigue SET title = :title, description = :description WHERE id = :id');
    return $stmt->execute([
        ':id' => $intrigueId,
        ':title' => $title,
        ':description' => $description
    ]);
}

// Fonction pour supprimer une intrigue
function deleteIntrigue($pdo, $intrigueId) {
    $stmt = $pdo->prepare('DELETE FROM intrigue WHERE id = :id');
    return $stmt->execute([':id' => $intrigueId]);
}

// Fonction pour obtenir tous les éléments d'une intrigue
function getIntrigueElements($pdo, $intrigueId) {
    $stmt = $pdo->prepare('
        SELECT e.*, 
            string_agg(t.id::text, \',\') as tag_ids, 
            string_agg(t.label, \',\') as tag_labels
        FROM element e
        LEFT JOIN element_tag et ON e.id = et.element_id
        LEFT JOIN tag t ON et.tag_id = t.id
        WHERE e.intrigue_id = :intrigue_id 
        GROUP BY e.id
        ORDER BY e.position
    ');
    $stmt->execute([':intrigue_id' => $intrigueId]);
    return $stmt->fetchAll();
}

// Fonction pour créer un nouvel élément
function createElement($pdo, $intrigueId, $type, $title, $description = '') {
    $stmt = $pdo->prepare('
        SELECT COALESCE(MAX(position), 0) + 1 as new_position FROM element WHERE intrigue_id = :intrigue_id
    ');
    $stmt->execute([':intrigue_id' => $intrigueId]);
    $result = $stmt->fetch();
    $position = $result['new_position'];
    
    $stmt = $pdo->prepare('
        INSERT INTO element (intrigue_id, type, title, description, position) 
        VALUES (:intrigue_id, :type, :title, :description, :position) 
        RETURNING id
    ');
    $stmt->execute([
        ':intrigue_id' => $intrigueId,
        ':type' => $type,
        ':title' => $title,
        ':description' => $description,
        ':position' => $position
    ]);
    $result = $stmt->fetch();
    return $result['id'];
}

// Fonction pour obtenir les détails d'un élément
function getElement($pdo, $elementId) {
    $stmt = $pdo->prepare('SELECT * FROM element WHERE id = :id');
    $stmt->execute([':id' => $elementId]);
    return $stmt->fetch();
}

// Fonction pour mettre à jour un élément
function updateElement($pdo, $elementId, $title, $description, $type = null) {
    if ($type) {
        $stmt = $pdo->prepare('UPDATE element SET title = :title, description = :description, type = :type WHERE id = :id');
        return $stmt->execute([
            ':id' => $elementId,
            ':title' => $title,
            ':description' => $description,
            ':type' => $type
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE element SET title = :title, description = :description WHERE id = :id');
        return $stmt->execute([
            ':id' => $elementId,
            ':title' => $title,
            ':description' => $description
        ]);
    }
}

// Fonction pour supprimer un élément
function deleteElement($pdo, $elementId) {
    $stmt = $pdo->prepare('DELETE FROM element WHERE id = :id');
    return $stmt->execute([':id' => $elementId]);
}

// Fonction pour ajouter une dépendance
function addDependency($pdo, $elementId, $blockedElementId) {
    $stmt = $pdo->prepare('
        INSERT INTO dependency (element_id, blocked_element_id) 
        VALUES (:element_id, :blocked_element_id)
        ON CONFLICT DO NOTHING
    ');
    return $stmt->execute([
        ':element_id' => $elementId,
        ':blocked_element_id' => $blockedElementId
    ]);
}

// Fonction pour obtenir les dépendances d'un élément avec détails
function getElementDependenciesWithDetails($pdo, $elementId) {
    $stmt = $pdo->prepare('
        SELECT d.id as dependency_id, d.blocked_element_id, 
               e.title as blocked_element_title, e.type as blocked_element_type,
               i.title as blocked_intrigue_title
        FROM dependency d
        JOIN element e ON d.blocked_element_id = e.id
        JOIN intrigue i ON e.intrigue_id = i.id
        WHERE d.element_id = :element_id
        ORDER BY d.created_at DESC
    ');
    $stmt->execute([':element_id' => $elementId]);
    return $stmt->fetchAll();
}

// Fonction pour vérifier si un élément peut être placé à une position
function canPlaceElement($pdo, $elementId, $position) {
    $element = getElement($pdo, $elementId);
    if (!$element) return false;
    
    $dependencies = getElementDependencies($pdo, $elementId);
    
    // Si l'élément a des dépendances, vérifier que celles-ci viennent avant
    foreach ($dependencies as $depId) {
        $depElement = getElement($pdo, $depId);
        if ($depElement && $depElement['position'] >= $position) {
            return false;
        }
    }
    
    return true;
}

// Fonction pour créer un tag
function createTag($pdo, $intrigueId, $label, $color = '') {
    $stmt = $pdo->prepare('
        INSERT INTO tag (intrigue_id, label, color) 
        VALUES (:intrigue_id, :label, :color) 
        RETURNING id
    ');
    $stmt->execute([
        ':intrigue_id' => $intrigueId,
        ':label' => $label,
        ':color' => $color
    ]);
    $result = $stmt->fetch();
    return $result['id'];
}

// Fonction pour obtenir tous les tags d'une intrigue
function getIntrigueTags($pdo, $intrigueId) {
    $stmt = $pdo->prepare('SELECT id, label, color FROM tag WHERE intrigue_id = :intrigue_id ORDER BY label');
    $stmt->execute([':intrigue_id' => $intrigueId]);
    return $stmt->fetchAll();
}

// Fonction pour ajouter un tag à un élément
function addTagToElement($pdo, $elementId, $tagId) {
    $stmt = $pdo->prepare('
        INSERT INTO element_tag (element_id, tag_id) 
        VALUES (:element_id, :tag_id)
        ON CONFLICT DO NOTHING
    ');
    return $stmt->execute([
        ':element_id' => $elementId,
        ':tag_id' => $tagId
    ]);
}

// Fonction pour supprimer un tag d'un élément
function removeTagFromElement($pdo, $elementId, $tagId) {
    $stmt = $pdo->prepare('DELETE FROM element_tag WHERE element_id = :element_id AND tag_id = :tag_id');
    return $stmt->execute([
        ':element_id' => $elementId,
        ':tag_id' => $tagId
    ]);
}

// Fonction pour obtenir tous les éléments d'un projet groupés par intrigue (pour les dépendances cross-intrigue)
function getProjectElements($pdo, $projectId) {
    $stmt = $pdo->prepare('
        SELECT i.id as intrigue_id, i.title as intrigue_title, i.position as intrigue_position,
               e.id, e.title, e.type, e.description, e.position,
               string_agg(t.id::text, \',\') as tag_ids, 
               string_agg(t.label, \',\') as tag_labels
        FROM intrigue i
        LEFT JOIN element e ON i.id = e.intrigue_id
        LEFT JOIN element_tag et ON e.id = et.element_id
        LEFT JOIN tag t ON et.tag_id = t.id
        WHERE i.project_id = :project_id
        GROUP BY i.id, i.title, i.position, e.id, e.title, e.type, e.description, e.position
        ORDER BY i.position, e.position
    ');
    $stmt->execute([':project_id' => $projectId]);
    return $stmt->fetchAll();
}

// Fonction pour obtenir les statistiques d'écriture d'un projet
function getProjectWritingStatus($pdo, $projectId) {
    $stmt = $pdo->prepare('
        SELECT 
            COALESCE(SUM(e.word_count), 0) as total_words,
            COUNT(CASE WHEN e.type = \'scene\' THEN 1 END) as total_scenes,
            COUNT(DISTINCT e.intrigue_id) as total_intrigues
        FROM element e
        JOIN intrigue i ON e.intrigue_id = i.id
        WHERE i.project_id = :project_id
    ');
    $stmt->execute([':project_id' => $projectId]);
    return $stmt->fetch();
}
?>
