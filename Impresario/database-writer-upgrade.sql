-- ==================== UPGRADE BDD POUR WRITER MODE ====================
-- Migration pour ajouter funcionnalités d'écriture

-- 1. Ajouter colonnes à PROJECT pour métadonnées d'écriture
ALTER TABLE project ADD COLUMN genre VARCHAR(100);
ALTER TABLE project ADD COLUMN target_word_count INTEGER DEFAULT 0;
ALTER TABLE project ADD COLUMN current_word_count INTEGER DEFAULT 0;
ALTER TABLE project ADD COLUMN status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'in_progress', 'completed', 'published'));
ALTER TABLE project ADD COLUMN synopsis TEXT;

-- 2. Ajouter colonnes à INTRIGUE (chapters/arcs)
ALTER TABLE intrigue ADD COLUMN chapter_number INTEGER;
ALTER TABLE intrigue ADD COLUMN content TEXT;
ALTER TABLE intrigue ADD COLUMN word_count INTEGER DEFAULT 0;
ALTER TABLE intrigue ADD COLUMN status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'first_read', 'finalized'));
ALTER TABLE intrigue ADD COLUMN reading_order INTEGER;

-- 3. Ajouter colonnes à ELEMENT pour contenu d'écriture
ALTER TABLE element ADD COLUMN content TEXT;
ALTER TABLE element ADD COLUMN word_count INTEGER DEFAULT 0;
ALTER TABLE element ADD COLUMN status VARCHAR(50) DEFAULT 'draft' CHECK (status IN ('draft', 'first_read', 'finalized'));
ALTER TABLE element ADD COLUMN writing_notes TEXT;
ALTER TABLE element ADD COLUMN pov_character VARCHAR(255);
ALTER TABLE element ADD COLUMN scene_date DATE;
ALTER TABLE element ADD COLUMN location VARCHAR(255);

-- 4. Nouvelle table pour historique de version
CREATE TABLE IF NOT EXISTS element_version (
    id SERIAL PRIMARY KEY,
    element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    word_count INTEGER,
    version_number INTEGER NOT NULL,
    author_id INTEGER NOT NULL REFERENCES author(id),
    change_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Nouvelle table pour personnages (character registry)
CREATE TABLE IF NOT EXISTS character (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(100),
    description TEXT,
    physical_traits TEXT,
    arc_notes TEXT,
    color_hex VARCHAR(7) DEFAULT '#e74c3c',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Relation many-to-many entre éléments et personnages
CREATE TABLE IF NOT EXISTS element_character (
    id SERIAL PRIMARY KEY,
    element_id INTEGER NOT NULL REFERENCES element(id) ON DELETE CASCADE,
    character_id INTEGER NOT NULL REFERENCES character(id) ON DELETE CASCADE,
    appearance_type VARCHAR(50) DEFAULT 'main' CHECK (appearance_type IN ('main', 'secondary', 'mentioned')),
    UNIQUE(element_id, character_id)
);

-- 7. Nouvelle table pour notes d'écriture (writing board)
CREATE TABLE IF NOT EXISTS writing_note (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    note_type VARCHAR(50) DEFAULT 'general' CHECK (note_type IN ('general', 'plot', 'character', 'world', 'timeline', 'idea')),
    title VARCHAR(255) NOT NULL,
    content TEXT,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Nouvelle table pour timeline (world building)
CREATE TABLE IF NOT EXISTS timeline_event (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    event_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    involves_characters TEXT,
    impact_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. Index pour perfs
CREATE INDEX idx_element_version_element ON element_version(element_id);
CREATE INDEX idx_character_project ON character(project_id);
CREATE INDEX idx_element_character_element ON element_character(element_id);
CREATE INDEX idx_element_character_character ON element_character(character_id);
CREATE INDEX idx_writing_note_project ON writing_note(project_id);
CREATE INDEX idx_timeline_event_project ON timeline_event(project_id);

-- 10. Views utiles
CREATE OR REPLACE VIEW project_statistics AS
SELECT 
    p.id,
    p.title,
    COUNT(DISTINCT i.id) as intrigue_count,
    COUNT(DISTINCT e.id) as scene_count,
    COALESCE(SUM(e.word_count), 0) as total_words,
    p.target_word_count,
    ROUND(100.0 * COALESCE(SUM(e.word_count), 0) / NULLIF(p.target_word_count, 0), 2) as progress_percent,
    COUNT(DISTINCT c.id) as character_count
FROM project p
LEFT JOIN intrigue i ON p.id = i.project_id
LEFT JOIN element e ON i.id = e.intrigue_id
LEFT JOIN character c ON p.id = c.project_id
GROUP BY p.id, p.title, p.target_word_count;

-- ==================== MIGRATION COMPLETE ====================
