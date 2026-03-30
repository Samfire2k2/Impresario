-- ===== SEED DATA POUR IMPRESARIO WRITER MODE =====
-- Script SQL pour insérer des données de test complètes

-- ===== 0. NETTOYER LES DONNÉES EXISTANTES =====
-- Supprimer en cascade en respectant l'ordre des dépendances
DELETE FROM timeline_event WHERE project_id IN (SELECT id FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie')));
DELETE FROM writing_note WHERE project_id IN (SELECT id FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie')));
DELETE FROM element_character WHERE element_id IN (SELECT id FROM element WHERE intrigue_id IN (SELECT id FROM intrigue WHERE project_id IN (SELECT id FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie')))));
DELETE FROM character WHERE project_id IN (SELECT id FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie')));
DELETE FROM element_version WHERE element_id IN (SELECT id FROM element WHERE intrigue_id IN (SELECT id FROM intrigue WHERE project_id IN (SELECT id FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie')))));
DELETE FROM element WHERE intrigue_id IN (SELECT id FROM intrigue WHERE project_id IN (SELECT id FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie'))));
DELETE FROM intrigue WHERE project_id IN (SELECT id FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie')));
DELETE FROM project WHERE author_id IN (SELECT id FROM author WHERE name IN ('alice', 'bob', 'charlie'));
DELETE FROM author WHERE name IN ('alice', 'bob', 'charlie');

-- ===== 1. CRÉER LES UTILISATEURS =====
-- Hashes bcrypt valides pour alice123, bob123, charlie123
INSERT INTO author (name, password) VALUES 
  ('alice', '$2y$10$bmIpGg6EZsT/KJE0/DPbY.S6kqCv6Z0BYRnxYNfPgc21f/3FH.3pG'),  -- alice123
  ('bob', '$2y$10$9K4XZiNysuJW0OPOFSbJYukkHQUDhJBL09L/rSkN8iWQKKpufB/Si'),    -- bob123
  ('charlie', '$2y$10$fcbQq0rsfqsHROva8yzlAu70suomttlS1orqFqyzAy8va5Q0.Rc86')  -- charlie123
ON CONFLICT (name) DO NOTHING;

-- ===== 2. CRÉER LES PROJETS =====
INSERT INTO project (author_id, title, description, genre, target_word_count, status, date_creation)
SELECT 
  CASE 
    WHEN author_name = 'alice' THEN (SELECT id FROM author WHERE name = 'alice')
    WHEN author_name = 'bob' THEN (SELECT id FROM author WHERE name = 'bob')
    WHEN author_name = 'charlie' THEN (SELECT id FROM author WHERE name = 'charlie')
  END as author_id,
  title, description, genre, target_word_count, status, NOW()
FROM (
  VALUES 
    ('alice', 'L''Héritage de la Couronne', 'Epic fantasy sur la lutte pour le pouvoir dans un royaume en péril', 'Fantasy', 100000, 'in_progress'),
    ('bob', 'Détective Mystère: Le Cas du Manoir Maudit', 'Thriller policier classique avec rebondissements', 'Thriller', 80000, 'in_progress'),
    ('charlie', 'Amours à Prague', 'Romance contemporaine dans une belle ville historique', 'Romance', 60000, 'in_progress')
) AS t(author_name, title, description, genre, target_word_count, status)
ON CONFLICT DO NOTHING;

-- ===== 3. CRÉER LES INTRIGUES (CHAPITRES) =====
WITH project_data AS (
  SELECT id, title, author_id FROM project 
  WHERE title IN ('L''Héritage de la Couronne', 'Détective Mystère: Le Cas du Manoir Maudit')
)
INSERT INTO intrigue (project_id, title, description, position, status, created_at)
SELECT 
  CASE 
    WHEN project_title = 'L''Héritage de la Couronne' THEN (SELECT id FROM project WHERE title = 'L''Héritage de la Couronne')
    WHEN project_title = 'Détective Mystère: Le Cas du Manoir Maudit' THEN (SELECT id FROM project WHERE title = 'Détective Mystère: Le Cas du Manoir Maudit')
  END as project_id,
  title, description, position, status, NOW()
FROM (
  VALUES 
    ('L''Héritage de la Couronne', 'Prologue: Le Crépuscule du Roi', 'Le vieux roi est mourant', 1, 'draft'),
    ('L''Héritage de la Couronne', 'Chapitre 1: L''Appel du Destin', 'Les prétendants se lèvent', 2, 'draft'),
    ('L''Héritage de la Couronne', 'Chapitre 2: Alliances Dangereuses', 'Des secrets viennent au jour', 3, 'draft'),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'Acte I: Découverte du Cadavre', 'Le crime est découvert', 1, 'draft'),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'Acte II: Interrogatoires et Secrets', 'Chacun a quelque chose à cacher', 2, 'draft'),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'Acte III: La Vérité Émerge', 'Les pièces du puzzle s''assemblent', 3, 'draft')
) AS t(project_title, title, description, position, status)
ON CONFLICT DO NOTHING;

-- ===== 4. CRÉER LES SCÈNES =====
WITH intrigue_data AS (
  SELECT i.id as intrigue_id, p.title as project_title, i.title as intrigue_title, i.position
  FROM intrigue i
  JOIN project p ON i.project_id = p.id
)
INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, location, created_at, updated_at)
SELECT 
  i.intrigue_id,
  'scene' as type,
  'Scène ' || ROW_NUMBER() OVER (PARTITION BY i.intrigue_id ORDER BY i.intrigue_id),
  'Scène importante',
  1,
  CASE 
    WHEN i.project_title = 'L''Héritage de la Couronne' AND i.position = 1 THEN '<p>Le roi repose sur son lit de mort. Les chandeliers vacillent, projetant des ombres dansantes sur les murs du château. Au loin, les cloches de la ville sonnent lentement...</p><p>Sa fille aînée, Elara, est agenouillée à son chevet. Ses yeux sont rouges de larmes, mais elle retient son chagrin. Elle doit être forte. Elle doit être reine.</p>'
    WHEN i.project_title = 'L''Héritage de la Couronne' AND i.position = 2 THEN '<p>Trois jours après la mort du roi, les prétendants commencent à arriver. Des cavaliers de provinces lointaines, des lords venant de leurs châteaux fortifiés. Tous veulent la couronne. Tous croient qu''elle leur appartient de droit.</p><p>Elara les observe du haut de la tour du château. Elle voit leurs ambitions dans leurs yeux. Elle entend leurs promesses creuses.</p>'
    WHEN i.project_title = 'L''Héritage de la Couronne' AND i.position = 3 THEN '<p>Kael Blackthorn arrive enfin. Ses yeux noirs croisent les siens. Dans ce moment, elle sait que rien ne sera jamais simple. Rien ne sera jamais comme avant.</p>'
    WHEN i.project_title = 'Détective Mystère: Le Cas du Manoir Maudit' AND i.position = 1 THEN '<p>L''inspecteur Marcus Ward se tient dans le hall du manoir, regardant le corps inerte du maître des lieux. Sir Edmund Blackwell. Connu dans la région pour sa richesse et ses secrets.</p><p>Maintenant, il ne sera plus jamais un secret.</p>'
    WHEN i.project_title = 'Détective Mystère: Le Cas du Manoir Maudit' AND i.position = 2 THEN '<p>Le serviteur, James Cuthbert, jure ne rien avoir entendu la nuit du meurtre. Mais ses mains tremblent. L''inspecteur Ward le remarque.</p><p>"Vous êtes certains?" demande-t-il, sa voix douce mais pénétrante.</p>'
    WHEN i.project_title = 'Détective Mystère: Le Cas du Manoir Maudit' AND i.position = 3 THEN '<p>La vérité commence à émerger. Victoria Blackwell n''était pas la victime innocente qu''elle prétendait être. Elle était complice.</p>'
    ELSE '<p>Placeholder content</p>'
  END as content,
  CASE 
    WHEN i.project_title = 'L''Héritage de la Couronne' THEN 147
    WHEN i.project_title = 'Détective Mystère: Le Cas du Manoir Maudit' THEN 156
    ELSE 100
  END as word_count,
  'draft' as status,
  CASE 
    WHEN i.project_title = 'L''Héritage de la Couronne' THEN 'Elara'
    WHEN i.project_title = 'Détective Mystère: Le Cas du Manoir Maudit' THEN 'Marcus Ward'
    ELSE 'Protagoniste'
  END as pov_character,
  CASE 
    WHEN i.project_title = 'L''Héritage de la Couronne' THEN 'Le Château Royal'
    WHEN i.project_title = 'Détective Mystère: Le Cas du Manoir Maudit' THEN 'Manoir Blackwell'
    ELSE 'Lieu inconnu'
  END as location,
  NOW(),
  NOW()
FROM intrigue_data i
ON CONFLICT DO NOTHING;

-- ===== 5. CRÉER LES PERSONNAGES =====
WITH project_ids AS (
  SELECT id, title FROM project 
  WHERE title IN ('L''Héritage de la Couronne', 'Détective Mystère: Le Cas du Manoir Maudit')
)
INSERT INTO character (project_id, name, role, description, physical_traits, arc_notes, color_hex, created_at)
SELECT 
  CASE 
    WHEN project_title = 'L''Héritage de la Couronne' THEN (SELECT id FROM project WHERE title = 'L''Héritage de la Couronne')
    WHEN project_title = 'Détective Mystère: Le Cas du Manoir Maudit' THEN (SELECT id FROM project WHERE title = 'Détective Mystère: Le Cas du Manoir Maudit')
  END as project_id,
  name, role, description, physical_traits, arc_notes, color_hex, NOW()
FROM (
  VALUES 
    ('L''Héritage de la Couronne', 'Elara Stormborn', 'main', 'Fille aînée du roi, héritière au trône. Intelligente, ambitieuse mais juste.', 'Cheveux blonds platine, yeux gris acier. Silhouette élégante.', 'De héritière hésitante à reine puissante', '#FF6B6B'),
    ('L''Héritage de la Couronne', 'Kael Blackthorn', 'main', 'Prétendant au trône du Sud. Guerrier redoutable et politicien retors.', 'Cheveux noirs, barbe taillée. Musculature impressionnante. Cicatrice au visage.', 'Ennemi juré qui pourrait devenir allié', '#4ECDC4'),
    ('L''Héritage de la Couronne', 'Roi Alderic', 'secondary', 'Le vieux roi, père d''Elara. Sage mais fatigué.', 'Cheveux blancs, regard bienveillant. Environ 70 ans.', 'Son décès déclenche les événements', '#45B7D1'),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'Inspecteur Marcus Ward', 'main', 'Détective expérimenté. À la recherche de la vérité, peu importe le prix.', 'Cheveux gris, regard perçant. Environ 55 ans. Vêtements de couleur sombre.', 'Confronté à ses propres démons en résolvant le cas', '#F39C12'),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'Victoria Blackwell', 'secondary', 'Veuve du défunt Sir Edmund. Riche héritière avec des secrets.', 'Élégante, environ 45 ans. Toujours vêtue de noir.', 'Suspecte principale qui devient complice inattendue', '#9B59B6'),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'Sir Edmund Blackwell', 'secondary', 'La victime. Homme riche et puissant avec des secrets obscurs.', 'Environ 60 ans, allure distinguée. Mort au lever du jour.', 'Son meurtre démarre l''intrigue', '#E74C3C')
) AS t(project_title, name, role, description, physical_traits, arc_notes, color_hex)
ON CONFLICT DO NOTHING;

-- ===== 6. CRÉER LES NOTES D'ÉCRITURE =====
WITH project_ids AS (
  SELECT id, title FROM project 
  WHERE title IN ('L''Héritage de la Couronne', 'Détective Mystère: Le Cas du Manoir Maudit')
)
INSERT INTO writing_note (project_id, note_type, title, content, is_pinned, created_at, updated_at)
SELECT 
  CASE 
    WHEN project_title = 'L''Héritage de la Couronne' THEN (SELECT id FROM project WHERE title = 'L''Héritage de la Couronne')
    WHEN project_title = 'Détective Mystère: Le Cas du Manoir Maudit' THEN (SELECT id FROM project WHERE title = 'Détective Mystère: Le Cas du Manoir Maudit')
  END as project_id,
  note_type, title, content, is_pinned, NOW(), NOW()
FROM (
  VALUES 
    ('L''Héritage de la Couronne', 'plot', 'Twist majeur - Acte 2', 'Révéler que Kael est en réalité le frère perdu d''Elara!', true),
    ('L''Héritage de la Couronne', 'character', 'Développement d''Elara', 'Elle doit apprendre à faire confiance à ses instincts politiques. Le pouvoir la change.', true),
    ('L''Héritage de la Couronne', 'world', 'Magie du monde', 'La magie est liée au sang royal. Seuls les descendants du premier roi peuvent la canaliser.', true),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'plot', 'Révélation finale', 'Le meurtrier n''est pas qui on croit. C''est un acte de vengeance personnelle.', true),
    ('Détective Mystère: Le Cas du Manoir Maudit', 'idea', 'Scène bonus', 'Ajouter un flashback montrant la relation de Victoria avec le vrai coupable.', false)
) AS t(project_title, note_type, title, content, is_pinned)
ON CONFLICT DO NOTHING;

-- ===== 7. CRÉER LES ÉVÉNEMENTS TIMELINE =====
WITH project_ids AS (
  SELECT id, title FROM project 
  WHERE title = 'L''Héritage de la Couronne'
)
INSERT INTO timeline_event (project_id, event_date, title, description, involves_characters, impact_notes, created_at)
SELECT 
  (SELECT id FROM project WHERE title = 'L''Héritage de la Couronne') as project_id,
  event_date, title, description, involves_characters, impact_notes, NOW()
FROM (
  VALUES 
    ('1200-01-01'::DATE, 'Fondation du Royaume', 'Le premier roi unit les terres', 'Roi Alderic I', 'Établit les bases de la sorcellerie royale'),
    ('1500-06-15'::DATE, 'Mort du Roi Ancien', 'Le roi meurt, laissant le trône sans héritier clair', 'Roi Alderic, Elara, Kael', 'Déclenche la crise dont parle l''histoire')
) AS t(event_date, title, description, involves_characters, impact_notes)
ON CONFLICT DO NOTHING;

-- ===== VÉRIFICATION =====
SELECT '✅ DONNÉES INSÉRÉES AVEC SUCCÈS!' as status;
SELECT COUNT(*) as utilisateurs FROM author WHERE name IN ('alice', 'bob', 'charlie');
SELECT COUNT(*) as projets FROM project WHERE title IN ('L''Héritage de la Couronne', 'Détective Mystère: Le Cas du Manoir Maudit', 'Amours à Prague');
SELECT COUNT(*) as intrigues FROM intrigue;
SELECT COUNT(*) as scenes FROM element WHERE type = 'scene';
SELECT COUNT(*) as personnages FROM character;
SELECT COUNT(*) as notes FROM writing_note;
SELECT COUNT(*) as timeline_events FROM timeline_event;
