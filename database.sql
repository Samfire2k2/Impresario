-- ==================== SCHEMA IMPRESARIO ====================

-- Suppression des tables et vues si elles existent
DROP VIEW IF EXISTS project_statistics CASCADE;
DROP TABLE IF EXISTS element_version CASCADE;
DROP TABLE IF EXISTS writing_note CASCADE;
DROP TABLE IF EXISTS timeline_event CASCADE;
DROP TABLE IF EXISTS element_character CASCADE;
DROP TABLE IF EXISTS character CASCADE;
DROP TABLE IF EXISTS element_tag CASCADE;
DROP TABLE IF EXISTS dependency CASCADE;
DROP TABLE IF EXISTS palette_color CASCADE;
DROP TABLE IF EXISTS color_palette CASCADE;
DROP TABLE IF EXISTS user_preferences CASCADE;
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

-- Table des palettes de couleurs
CREATE TABLE color_palette (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de liaison palette-couleur (5 rôles: bg_1, bg_2, button, title, scene_default)
CREATE TABLE palette_color (
    id SERIAL PRIMARY KEY,
    palette_id INTEGER NOT NULL REFERENCES color_palette(id) ON DELETE CASCADE,
    role VARCHAR(50) NOT NULL CHECK (role IN ('bg_1', 'bg_2', 'bg_3', 'bg_4', 'button', 'title', 'scene_default')),
    hex_color VARCHAR(7) NOT NULL,
    UNIQUE(palette_id, role)
);

-- Table des préférences utilisateur
CREATE TABLE user_preferences (
    id SERIAL PRIMARY KEY,
    author_id INTEGER NOT NULL UNIQUE REFERENCES author(id) ON DELETE CASCADE,
    theme VARCHAR(20) DEFAULT 'light' CHECK (theme IN ('light', 'dark', 'auto')),
    palette_id INTEGER REFERENCES color_palette(id),
    notifications_enabled BOOLEAN DEFAULT TRUE,
    language VARCHAR(5) DEFAULT 'fr',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des projets
CREATE TABLE project (
    id SERIAL PRIMARY KEY,
    author_id INTEGER NOT NULL REFERENCES author(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Couleurs & apparence PLANNER
    orientation VARCHAR(20) DEFAULT 'vertical',
    bg_color_1 VARCHAR(7) DEFAULT '#fef5e7',
    bg_color_2 VARCHAR(7) DEFAULT '#f8e8d8',
    bg_color_3 VARCHAR(7) DEFAULT NULL,
    bg_color_4 VARCHAR(7) DEFAULT NULL,
    button_color VARCHAR(7) DEFAULT '#d4a574',
    title_color VARCHAR(7) DEFAULT '#5a4a3a',
    scene_default_color VARCHAR(7) DEFAULT '#fef5e7',
    palette_id INTEGER DEFAULT NULL,
    -- Writer Mode Fields (now default)
    genre VARCHAR(100),
    target_word_count INTEGER DEFAULT 100000,
    current_word_count INTEGER DEFAULT 0,
    status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'in_progress', 'completed', 'published')),
    synopsis TEXT,
    -- Planner Settings
    font_size INTEGER DEFAULT 16,
    element_size INTEGER DEFAULT 100
);

-- Table des intrigues (storylines)
CREATE TABLE intrigue (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    position INTEGER NOT NULL,
    color VARCHAR(7) DEFAULT '#d4a574',
    bg_color VARCHAR(7) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'planning', 'writing', 'editing', 'completed')),
    word_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Writer Mode Fields (now default)
    chapter_number INTEGER,
    content TEXT,
    reading_order INTEGER
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
    intrigue_id INTEGER REFERENCES intrigue(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL CHECK (type IN ('scene', 'marqueur', 'pocket')),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    position INTEGER NOT NULL,
    color VARCHAR(7) DEFAULT '#fef5e7',
    is_visible BOOLEAN DEFAULT TRUE,
    writing_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Writer Mode Fields (now default)
    content TEXT,
    word_count INTEGER DEFAULT 0,
    status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'first_read', 'finalized')),
    pov_character VARCHAR(255),
    scene_date DATE,
    location VARCHAR(255)
);

-- Table des personnages
CREATE TABLE character (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(100),
    description TEXT,
    physical_traits TEXT,
    arc_notes TEXT,
    color_hex VARCHAR(7) DEFAULT '#e74c3c',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de liaison element-character (many-to-many)
CREATE TABLE element_character (
    id SERIAL PRIMARY KEY,
    element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
    character_id INTEGER NOT NULL REFERENCES character(id) ON DELETE CASCADE,
    appearance_type VARCHAR(50) DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(element_id, character_id)
);

-- Table des notes d'écriture
CREATE TABLE writing_note (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    note_type VARCHAR(50) DEFAULT 'general' CHECK (note_type IN ('general', 'plot', 'character', 'world', 'timeline', 'idea')),
    title VARCHAR(255) NOT NULL,
    content TEXT,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de la chronologie (timeline events)
CREATE TABLE timeline_event (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    event_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    involves_characters TEXT,
    impact_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table d'historique des versions (element versions)
CREATE TABLE element_version (
    id SERIAL PRIMARY KEY,
    element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    word_count INTEGER,
    version_number INTEGER NOT NULL,
    author_id INTEGER NOT NULL REFERENCES author(id),
    change_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- ==================== VIEWS ====================

-- Vue de statistiques du projet (écrivain)
CREATE VIEW project_statistics AS
SELECT 
    p.id,
    p.title,
    COUNT(DISTINCT i.id) as intrigue_count,
    COUNT(DISTINCT e.id) as scene_count,
    COALESCE(SUM(e.word_count), 0)::INTEGER as total_words,
    p.target_word_count,
    ROUND(100.0 * COALESCE(SUM(e.word_count), 0) / NULLIF(p.target_word_count, 0), 2)::FLOAT as progress_percent,
    COUNT(DISTINCT c.id) as character_count
FROM project p
LEFT JOIN intrigue i ON p.id = i.project_id
LEFT JOIN element e ON i.id = e.intrigue_id
LEFT JOIN character c ON p.id = c.project_id
GROUP BY p.id, p.title, p.target_word_count;

-- ==================== INDEXES ====================

-- Index pour les recherches fréquentes
CREATE INDEX idx_project_author ON project(author_id);
CREATE INDEX idx_project_palette ON project(palette_id);
CREATE INDEX idx_intrigue_project ON intrigue(project_id);
CREATE INDEX idx_element_intrigue ON element(intrigue_id);
CREATE INDEX idx_tag_intrigue ON tag(intrigue_id);
CREATE INDEX idx_character_project ON character(project_id);
CREATE INDEX idx_element_character_element ON element_character(element_id);
CREATE INDEX idx_element_character_character ON element_character(character_id);
CREATE INDEX idx_element_tag_element ON element_tag(element_id);
CREATE INDEX idx_element_tag_tag ON element_tag(tag_id);
CREATE INDEX idx_dependency_element ON dependency(element_id);
CREATE INDEX idx_dependency_blocked ON dependency(blocked_element_id);
CREATE INDEX idx_palette_color_palette ON palette_color(palette_id);
CREATE INDEX idx_user_preferences_author ON user_preferences(author_id);
CREATE INDEX idx_user_preferences_palette ON user_preferences(palette_id);
CREATE INDEX idx_writing_note_project ON writing_note(project_id);
CREATE INDEX idx_timeline_event_project ON timeline_event(project_id);
CREATE INDEX idx_element_version_element ON element_version(element_id);

-- ==================== DONNÉES DE TEST ====================

-- ===== PALETTES DE COULEURS =====
INSERT INTO color_palette (name, code, description, is_default) VALUES 
('Pastel', 'pastel', 'Palette douce et relaxante avec couleurs pastel', TRUE),
('Dark', 'dark', 'Palette sombre et moderne', FALSE),
('Vibrant', 'vibrant', 'Palette vive et colorée', FALSE),
('Minimal', 'minimal', 'Palette minimaliste avec teintes neutres', FALSE),
('Daltonienne', 'daltonienne', 'Palette adaptée aux personnes daltoniennes (accessible)', FALSE);

-- Insérer les couleurs pour chaque palette
-- Pastel (douce, chaude et relaxante)
INSERT INTO palette_color (palette_id, role, hex_color) VALUES 
(1, 'bg_1', '#fef5e7'),      -- beige très clair
(1, 'bg_2', '#f8e8d8'),      -- beige moyen
(1, 'bg_3', '#e8d5c4'),      -- beige chaud
(1, 'bg_4', '#d4c4b0'),      -- beige grisé
(1, 'button', '#d4a574'),    -- bronze
(1, 'title', '#8b6f47'),     -- marron
(1, 'scene_default', '#fef5e7');  -- beige clair

-- Dark (gris sombres et accents bleus)
INSERT INTO palette_color (palette_id, role, hex_color) VALUES 
(2, 'bg_1', '#1a1a1a'),      -- noir profond
(2, 'bg_2', '#2d2d2d'),      -- gris foncé
(2, 'bg_3', '#3d3d3d'),      -- gris moyen
(2, 'bg_4', '#4d4d4d'),      -- gris clair
(2, 'button', '#4a90e2'),    -- bleu
(2, 'title', '#e8e8e8'),     -- blanc clair
(2, 'scene_default', '#2d2d2d');  -- gris foncé

-- Vibrant (multicolore excitant)
INSERT INTO palette_color (palette_id, role, hex_color) VALUES 
(3, 'bg_1', '#fff5f7'),      -- rose pâle
(3, 'bg_2', '#fff0e6'),      -- orange pâle
(3, 'bg_3', '#e6e6fa'),      -- violet pâle
(3, 'bg_4', '#e6f7ff'),      -- bleu pâle
(3, 'button', '#ff6b6b'),    -- rouge vibrant
(3, 'title', '#c92a2a'),     -- rouge foncé
(3, 'scene_default', '#fff5f7');  -- rose pâle

-- Minimal (neutre et professionnel)
INSERT INTO palette_color (palette_id, role, hex_color) VALUES 
(4, 'bg_1', '#ffffff'),      -- blanc
(4, 'bg_2', '#f5f5f5'),      -- gris très clair
(4, 'bg_3', '#eeeeee'),      -- gris clair
(4, 'bg_4', '#e0e0e0'),      -- gris moyen
(4, 'button', '#616161'),    -- gris foncé
(4, 'title', '#212121'),     -- noir
(4, 'scene_default', '#ffffff');  -- blanc

-- Daltonienne (bleu/jaune/noir avec bon contraste)
INSERT INTO palette_color (palette_id, role, hex_color) VALUES 
(5, 'bg_1', '#fffdd0'),      -- jaune pâle
(5, 'bg_2', '#fff8dc'),      -- jaune très pâle
(5, 'bg_3', '#0173b2'),      -- bleu accessible
(5, 'bg_4', '#029e73'),      -- vert accessible
(5, 'button', '#d55e00'),    -- orange accessible
(5, 'title', '#000000'),     -- noir pur
(5, 'scene_default', '#fffdd0');  -- jaune pâle

-- Créer des utilisateurs de test
INSERT INTO author (name, password) VALUES 
('test_home', 'test_home'),
('alice', 'alice123'),
('bob', 'bob456');
-- Test users with plain text passwords

-- Créer les préférences utilisateur pour test
INSERT INTO user_preferences (author_id, theme, palette_id, language) VALUES 
(1, 'light', 1, 'fr'),
(2, 'dark', 2, 'fr'),
(3, 'light', 3, 'en');

-- ===== PROJETS AVEC TOUTES LES FEATURES =====

-- Projet 1: Roman fantasy complet avec toutes les données
INSERT INTO project (author_id, title, description, palette_id, genre, target_word_count, current_word_count, status, synopsis) VALUES 
(1, 'Le Cœur de l''Ombre', 'Un épique fantasy sur la chute d''un empire et la montée d''une nouvelle ère', 1, 'Fantasy', 120000, 45000, 'in_progress', 
'Dans le royaume de Valdris, un ancien secret menace de détruire l''empire. Un groupe de héros doit unir ses forces pour empêcher la catastrophe.');

-- Projet 2: Roman de science-fiction
INSERT INTO project (author_id, title, description, palette_id, genre, target_word_count, current_word_count, status, synopsis) VALUES 
(2, 'Étoile Perdue', 'Les derniers humains cherchent une nouvelle maison dans l''espace', 2, 'Science-fiction', 100000, 0, 'draft',
'L''humanité a quitté la Terre. La flotte générationnelle voyage depuis 250 ans à la recherche d''une habitable.');

-- Projet 3: Roman mystère
INSERT INTO project (author_id, title, description, palette_id, genre, target_word_count, current_word_count, status, synopsis) VALUES 
(3, 'Les Secrets de Willowbrook', 'Un village cache un mystère centenaire', 3, 'Mystère', 80000, 62000, 'completed',
'Une écrivaine revient dans son village d''enfance et découvre que rien n''est comme elle l''a laissé.');

-- ===== INTRIGUES COMPLÈTES =====

-- Projet 1 intrigues
INSERT INTO intrigue (project_id, title, description, position, chapter_number, status, word_count) VALUES 
(1, 'Chapitre 1: L''Appel', 'Le protagoniste apprend la vérité cachée', 1, 1, 'writing', 8000),
(1, 'Chapitre 2: Rassemblement', 'Les alliés se réunissent pour la quête', 2, 2, 'draft', 5000),
(1, 'Chapitre 3: La Traversée', 'Le voyage périlleux commence', 3, 3, 'planning', 0);

-- Projet 2 intrigues
INSERT INTO intrigue (project_id, title, description, position, chapter_number, status, word_count) VALUES 
(2, 'Genèse', 'Le départ de la flotte', 1, 1, 'planning', 0),
(2, 'Les Mille Ans', 'La vie à bord de la flotte', 2, NULL, 'planning', 0);

-- Projet 3 intrigues
INSERT INTO intrigue (project_id, title, description, position, chapter_number, status, word_count) VALUES 
(3, 'Le Retour', 'Retour au village d''enfance', 1, 1, 'completed', 20000),
(3, 'Les Révélations', 'Découverte des secrets', 2, 2, 'completed', 30000),
(3, 'Résolution', 'Le dénouement de l''histoire', 3, 3, 'completed', 12000);

-- ===== TAGS/CATÉGORIES =====

INSERT INTO tag (intrigue_id, label, color) VALUES 
-- Projet 1
(1, 'Action', '#e74c3c'),
(1, 'Mystère', '#8e44ad'),
(1, 'Magie', '#3498db'),
(2, 'Romance', '#e91e63'),
(2, 'Dialogue', '#f39c12'),
(3, 'Voyage', '#27ae60'),
-- Projet 3
(7, 'Suspense', '#2c3e50'),
(7, 'Révélation', '#e67e22'),
(8, 'Confrontation', '#c0392b');

-- ===== PERSONNAGES =====

-- Personnages Projet 1
INSERT INTO character (project_id, name, role, description, physical_traits, arc_notes, color_hex) VALUES 
(1, 'Kael', 'Héros principal', 'Un jeune chevalier en quête de vérité', 'Grand, cheveux noirs, yeux bleus', 'De l''innocence à la sagesse', '#e74c3c'),
(1, 'Lyra', 'Magicienne', 'Une mage puissante cachant un secret', 'Cheveux rouges, silhouette élancée', 'De la peur à la confiance', '#8e44ad'),
(1, 'Aldor', 'Mentor', 'Un ancien guerrier retiré', 'Âgé, cicatrices visible, sagesse dans le regard', 'La lumière du passé', '#d4a574');

-- Personnages Projet 2
INSERT INTO character (project_id, name, role, description, physical_traits, arc_notes, color_hex) VALUES 
(2, 'Dr Elena', 'Commandante', 'Responsable de la flotte', 'Femme 50 ans, cheveux gris coupés court', 'Navigation et espoir', '#3498db'),
(2, 'Marcus', 'Ingénieur', 'Maintient la flotte opérationnelle', 'Homme technophile, barbe noire', 'Doute et détermination', '#2ecc71');

-- Personnages Projet 3
INSERT INTO character (project_id, name, role, description, physical_traits, arc_notes, color_hex) VALUES 
(3, 'Sarah', 'Héroïne', 'L''écrivaine qui revient', 'Femme 30 ans, cheveux châtains', 'Passé vers présent', '#e91e63'),
(3, 'James', 'Amoureux d''enfance', 'Reste au village', 'Homme 32 ans, barbe légère', 'Espoir renouvelé', '#3498db');

-- ===== ÉLÉMENTS/SCÈNES AVEC CONTENU =====

-- Projet 1, Intrigue 1 (Chapitre 1: L'Appel)
INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(1, 'scene', 'L''Aube du Changement', 'Kael se réveille avec des visions mystérieuses', 1, 
'Une lumière dorée filtre à travers les rideaux. Kael sent son cœur s''accélérer. Les images sont claires maintenant - un éclat noir, une couronne brisée.', 
1200, 'finalized', 'Kael', '2026-01-15', 'Château de Valdris');

INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(1, 'scene', 'Première rencontre avec Lyra', 'Kael découvre une magicienne cachée', 2, 
'La ruelle était sombre, mais les yeux de la femme brillaient d''une lumière intérieure. Elle le regarda avec intensité. "Vous voyez aussi, n''est-ce pas?"', 
1800, 'first_read', 'Kael', '2026-01-16', 'Ruelle de la Vieille Ville');

INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(1, 'marqueur', 'Révélation du Secret', 'Point clé où la vérité est dévoilée', 3, NULL, 0, 'draft', NULL, '2026-01-20', NULL);

INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(1, 'scene', 'Fuite du Château', 'Kael et Lyra s''échappent', 4, 
'Les gardes criaient derrière eux. Lyra leva sa main et un mur de feu apparut. "Maintenant!" cria-t-elle. Kael n''hésita pas.', 
950, 'draft', 'Kael', '2026-01-21', 'Château de Valdris');

-- Projet 1, Intrigue 2 (Chapitre 2: Rassemblement)
INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(2, 'scene', 'À la Taverne de l''Épée', 'Le groupe se formule un plan', 1,
'Aldor frappa la table. "Il n''y a qu''une seule façon de arrêter cela. Nous devons atteindre la Tour du Silence avant la pleine lune."',
1200, 'first_read', 'Aldor', '2026-02-01', 'Taverne de l''Épée Rousse');

-- Projet 3, Intrigues finalisées
INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(6, 'scene', 'La Maison Vide', 'Sarah arrive à sa maison d''enfance', 1,
'La porte en bois rouge était devenue grise. Les volets pendaient de travers. Elle prit une profonde respiration et entra.',
800, 'finalized', 'Sarah', '2026-03-01', 'Willowbrook - Maison de Sarah');

INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(7, 'scene', 'La Lettre Cachée', 'Sarah découvre une lettre du passé', 2,
'Sous les planches du grenier, enveloppée dans de la toile cirée jaunie, se trouvait une lettre. L''écriture était impossible à confondre. C''était celle de sa mère.',
1100, 'finalized', 'Sarah', '2026-03-05', 'Maison de Sarah - Grenier');

INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, scene_date, location) VALUES 
(8, 'scene', 'Confrontation au Lac', 'Sarah confronte James avec la vérité', 1,
'Au bord du lac où tout avait commencé, Sarah enfin posa la question qui la torturait depuis des années. James ferma les yeux.',
950, 'finalized', 'Sarah', '2026-03-10', 'Lac de Willowbrook');

-- ===== RELATIONS ÉLEMENT-PERSONNAGE =====

INSERT INTO element_character (element_id, character_id, appearance_type) VALUES 
(1, 1, 'main'),      -- Kael dans "L'Aube du Changement"
(2, 1, 'main'),      -- Kael dans "Première rencontre"
(2, 2, 'main'),      -- Lyra dans "Première rencontre"
(4, 1, 'main'),      -- Kael dans "Fuite du Château"
(4, 2, 'main'),      -- Lyra dans "Fuite du Château"
(5, 3, 'main'),      -- Aldor dans "À la Taverne"
(5, 1, 'secondary'), -- Kael mentionné
(5, 2, 'secondary'), -- Lyra mentionnée
(6, 6, 'main'),      -- Sarah dans "La Maison Vide"
(7, 6, 'main'),      -- Sarah dans "La Lettre Cachée"
(8, 6, 'main'),      -- Sarah dans "Confrontation au Lac"
(8, 7, 'main');      -- James dans "Confrontation au Lac"

-- ===== TAGS SUR LES ÉLÉMENTS =====

INSERT INTO element_tag (element_id, tag_id) VALUES 
(1, 3),   -- "L'Aube du Changement" -> Magie
(2, 1),   -- "Première rencontre" -> Action
(2, 2),   -- "Première rencontre" -> Mystère
(4, 1),   -- "Fuite du Château" -> Action
(5, 2),   -- "À la Taverne" -> Mystère
(5, 5);   -- "À la Taverne" -> Dialogue

-- ===== NOTES D'ÉCRITURE =====

INSERT INTO writing_note (project_id, note_type, title, content, is_pinned) VALUES 
(1, 'plot', 'Structure générale', 'Trois actes: Exposition, Conflit, Résolution. Climax au château abandonné.', true),
(1, 'character', 'Arc de Kael', 'De jeune naïf à guerrier sage. Apprend à maîtriser la magie.', true),
(1, 'world', 'Magie du monde', 'La magie est liée à la limite entre réalité et dimensions parallèles.', true),
(1, 'idea', 'Twist potentiel', 'Aldor pourrait being l''antagoniste caché', false),
(1, 'timeline', 'Timeline du haut-fait', 'Événements se déroulent sur 6 mois de temps diégétique', false),
(3, 'general', 'Notes de révision', 'Chapitre 2 nécessite réécriture - trop lent', false);

-- ===== ÉVÉNEMENTS TIMELINE =====

INSERT INTO timeline_event (project_id, event_date, title, description, involves_characters, impact_notes) VALUES 
(1, '2026-01-15', 'Les Visions Commencent', 'Kael commence à avoir des visions du chaos', 'Kael', 'Point de départ de l''aventure'),
(1, '2026-01-16', 'Rencontre avec Lyra', 'Première rencontre avec la magicienne', 'Kael, Lyra', 'Formation de l''alliance'),
(1, '2026-01-20', 'La Révélation', 'La vérité sur l''empire est dévoilée', 'Kael, Lyra, Aldor', 'Point d''inflexion majeur'),
(1, '2026-01-21', 'Fuite du Château', 'Le groupe fuit avant d''être attrapé', 'Groupe', 'Pas de retour possible'),
(3, '2026-03-01', 'Retour de Sarah', 'Sarah revient à Willowbrook après 15 ans', 'Sarah', 'Début de l''enquête');

-- ===== DÉPENDANCES ENTRE ÉLÉMENTS =====

INSERT INTO dependency (element_id, blocked_element_id) VALUES 
(3, 4),   -- "Révélation du Secret" doit venir avant "Fuite du Château"
(1, 2),   -- "L'Aube" avant "Première rencontre" (logiquement)
(6, 7);   -- "La Maison Vide" avant "La Lettre Cachée"

-- ===== VERSIONS D'ÉLÉMENTS (HISTORIQUE) =====

INSERT INTO element_version (element_id, content, word_count, version_number, author_id, change_summary) VALUES 
(1, 'Une lumière grise filtre à travers les rideaux. Kael sent son cœur s''accélérer.', 800, 1, 1, 'Première version plus courte'),
(1, 'Une lumière dorée filtre à travers les rideaux. Kael sent son cœur s''accélérer. Les images sont claires maintenant - un éclat noir, une couronne brisée.', 1200, 2, 1, 'Ajout de détails et d''atmosphère'),
(2, 'La femme était dans l''ombre. Elle le regarda. "Vous voyez aussi?"', 600, 1, 1, 'Brouillon initial'),
(2, 'La ruelle était sombre, mais les yeux de la femme brillaient d''une lumière intérieure. Elle le regarda avec intensité. "Vous voyez aussi, n''est-ce pas?"', 1800, 2, 1, 'Expansion avec plus de détails'),
(6, 'La porte était grise. Volets cassés. Elle entra.', 50, 1, 3, 'Version extrêmement brève'),
(6, 'La porte en bois rouge était devenue grise. Les volets pendaient de travers. Elle prit une profonde respiration et entra.', 800, 2, 3, 'Version finalisée');

-- ==================== PERMISSIONS ====================

-- Créer le rôle s'il n'existe pas et lui donner les permissions
DO $$ 
BEGIN
    CREATE ROLE axolotl WITH LOGIN;
EXCEPTION WHEN OTHERS THEN
    -- Rôle existe déjà, continuer
END
$$;

-- Accorder les permissions au rôle axolotl
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO axolotl;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO axolotl;
