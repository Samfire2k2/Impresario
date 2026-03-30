-- ==================== SCHEMA IMPRESARIO ====================

-- Suppression des tables si elles existent
DROP TABLE IF EXISTS element_tag CASCADE;
DROP TABLE IF EXISTS dependency CASCADE;
DROP TABLE IF EXISTS tag CASCADE;
DROP TABLE IF EXISTS element CASCADE;
DROP TABLE IF EXISTS intrigue CASCADE;
DROP TABLE IF EXISTS project CASCADE;
DROP TABLE IF EXISTS author CASCADE;

-- Table des auteurs
CREATE TABLE author (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des projets
CREATE TABLE project (
    id SERIAL PRIMARY KEY,
    author_id INTEGER NOT NULL REFERENCES author(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des intrigues (storylines)
CREATE TABLE intrigue (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    position INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des tags/catégories
CREATE TABLE tag (
    id SERIAL PRIMARY KEY,
    intrigue_id INTEGER NOT NULL REFERENCES intrigue(id) ON DELETE CASCADE,
    label VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des éléments (scènes, marqueurs)
CREATE TABLE element (
    id SERIAL PRIMARY KEY,
    intrigue_id INTEGER NOT NULL REFERENCES intrigue(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL CHECK (type IN ('scene', 'marqueur')),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    position INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de liaison element-tag (many-to-many)
CREATE TABLE element_tag (
    id SERIAL PRIMARY KEY,
    element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tag(id) ON DELETE CASCADE,
    UNIQUE(element_id, tag_id)
);

-- Table des dépendances (empêcher certains éléments après d'autres)
CREATE TABLE dependency (
    id SERIAL PRIMARY KEY,
    element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
    blocked_element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(element_id, blocked_element_id)
);

-- ==================== INDEXES ====================

-- Index pour les recherches fréquentes
CREATE INDEX idx_project_author ON project(author_id);
CREATE INDEX idx_intrigue_project ON intrigue(project_id);
CREATE INDEX idx_element_intrigue ON element(intrigue_id);
CREATE INDEX idx_tag_intrigue ON tag(intrigue_id);
CREATE INDEX idx_element_tag_element ON element_tag(element_id);
CREATE INDEX idx_element_tag_tag ON element_tag(tag_id);
CREATE INDEX idx_dependency_element ON dependency(element_id);
CREATE INDEX idx_dependency_blocked ON dependency(blocked_element_id);

-- ==================== DONNÉES DE TEST ====================

-- Créer un utilisateur de test
INSERT INTO author (name, password) VALUES 
('test_home', '$2y$10$0iMZVPvUllCpBvbJN3m6XuO7KoGF8/4l/nQ/sPXgq.t6UYTx8kPbC');
-- Mot de passe: test_home

-- Créer un projet de test
INSERT INTO project (author_id, title, description) VALUES 
(1, 'Ma première histoire', 'Ceci est un projet de test pour Impresario'),
(1, 'Aventure épique', 'Une grande aventure avec plusieurs intrigues');

-- Créer des intrigues de test
INSERT INTO intrigue (project_id, title, description, position) VALUES 
(1, 'Intrigue principale', 'La quête principale du héros', 1),
(1, 'Développement amoureuse', 'L''amour du héros pour son compagnon', 2),
(2, 'Quête de la couronne', 'Trouver la couronne perdue', 1),
(2, 'Traîtrise au palais', 'Un complot au sein du palais', 2);

-- Créer des tags de test
INSERT INTO tag (intrigue_id, label, color) VALUES 
(1, 'Action', '#e74c3c'),
(1, 'Dialogue', '#3498db'),
(2, 'Romance', '#e91e63'),
(2, 'Conflit', '#ff9800');

-- Créer des éléments de test
INSERT INTO element (intrigue_id, type, title, description, position) VALUES 
(1, 'scene', 'Le départ', 'Le héros quitte son village pour l''aventure', 1),
(1, 'scene', 'Rencontre du guide', 'Le héros rencontre un guide mystérieux', 2),
(1, 'marqueur', 'Point de non-retour', 'Après ce point, impossible de revenir', 3),
(1, 'scene', 'La grande bataille', 'Affrontement final contre le boss', 4),
(2, 'scene', 'Première rencontre', 'Le héros rencontre son futur amour au village', 1),
(2, 'scene', 'Un moment d''intimité', 'Une belle scène romanitique sous les étoiles', 3),
(2, 'scene', 'Réunion après la bataille', 'Les deux personnages se retrouvent', 5);

-- Ajouter des tags aux éléments
INSERT INTO element_tag (element_id, tag_id) VALUES 
(1, 1),
(2, 2),
(4, 1),
(5, 3),
(7, 2);

-- Créer des dépendances (exemples)
INSERT INTO dependency (element_id, blocked_element_id) VALUES 
(3, 5);
-- L'élément 5 ne peut pas venir avant le point de non-retour (élément 3)

-- ==================== PERMISSIONS ====================

-- S'assurer que l'utilisateur axolotl peut accéder à la base de données
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO axolotl;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO axolotl;
