<?php
/**
 * Script de génération de données de test
 * Crée des utilisateurs, projets, intrigues, éléments et personnages de test
 */

include 'includes/config.php';
include 'includes/functions.php';
include 'includes/writer-functions.php';

// Vérifier la migration Writer Mode
$stmt = $pdo->query("SELECT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_name = 'element' AND column_name = 'content') AS writer_mode_active");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$result['writer_mode_active']) {
    die('❌ Erreur: Writer Mode n\'est pas activé. Exécutez setup-writer.php d\'abord.');
}

$success_count = 0;
$error_count = 0;

try {
    // ===== 1. CRÉER DES UTILISATEURS DE TEST =====
    echo "<h2>📝 Création des utilisateurs de test</h2>";
    
    $test_users = [
        ['username' => 'alice', 'password' => 'alice123'],
        ['username' => 'bob', 'password' => 'bob123'],
        ['username' => 'charlie', 'password' => 'charlie123'],
    ];
    
    $user_ids = [];
    foreach ($test_users as $user) {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare('SELECT id FROM author WHERE name = :name');
        $stmt->execute([':name' => $user['username']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $user_ids[$user['username']] = $existing['id'];
            echo "✅ Utilisateur existant: " . htmlspecialchars($user['username']) . "<br>";
            $success_count++;
        } else {
            // Créer l'utilisateur
            $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO author (name, password) VALUES (:name, :password)');
            if ($stmt->execute([':name' => $user['username'], ':password' => $hashed_password])) {
                $user_ids[$user['username']] = $pdo->lastInsertId();
                echo "✅ Utilisateur créé: " . htmlspecialchars($user['username']) . " (motdepasse: " . $user['password'] . ")<br>";
                $success_count++;
            }
        }
    }
    
    // ===== 2. CRÉER DES PROJETS =====
    echo "<h2>📚 Création des projets de test</h2>";
    
    $projects_data = [
        [
            'author_id' => $user_ids['alice'],
            'title' => 'L\'Héritage de la Couronne',
            'description' => 'Epic fantasy sur la lutte pour le pouvoir dans un royaume en péril',
            'genre' => 'Fantasy',
            'target_word_count' => 100000,
        ],
        [
            'author_id' => $user_ids['bob'],
            'title' => 'Détective Mystère: Le Cas du Manoir Maudit',
            'description' => 'Thriller policier classique avec rebondissements',
            'genre' => 'Thriller',
            'target_word_count' => 80000,
        ],
        [
            'author_id' => $user_ids['charlie'],
            'title' => 'Amours à Prague',
            'description' => 'Romance contemporaine dans une belle ville historique',
            'genre' => 'Romance',
            'target_word_count' => 60000,
        ],
    ];
    
    $project_ids = [];
    foreach ($projects_data as $proj) {
        $stmt = $pdo->prepare('
            INSERT INTO project (author_id, title, description, genre, target_word_count, status)
            VALUES (:author_id, :title, :description, :genre, :target_word_count, :status)
        ');
        
        if ($stmt->execute([
            ':author_id' => $proj['author_id'],
            ':title' => $proj['title'],
            ':description' => $proj['description'],
            ':genre' => $proj['genre'],
            ':target_word_count' => $proj['target_word_count'],
            ':status' => 'in_progress'
        ])) {
            $project_id = $pdo->lastInsertId();
            $project_ids[$proj['title']] = $project_id;
            echo "✅ Projet créé: " . htmlspecialchars($proj['title']) . "<br>";
            $success_count++;
        }
    }
    
    // ===== 3. CRÉER DES INTRIGUES (CHAPITRES) =====
    echo "<h2>📖 Création des intrigues</h2>";
    
    $max_project_id = max($project_ids);
    $intrigue_data = [];
    
    // Pour le premier projet (Fantasy)
    $fantasy_project = $project_ids['L\'Héritage de la Couronne'];
    $intrigues_fantasy = [
        ['title' => 'Prologue: Le Crépuscule du Roi', 'description' => 'Le vieux roi est mourant'],
        ['title' => 'Chapitre 1: L\'Appel du Destin', 'description' => 'Les prétendants se lèvent'],
        ['title' => 'Chapitre 2: Alliances Dangereuses', 'description' => 'Des secrets viennent au jour'],
    ];
    
    foreach ($intrigues_fantasy as $index => $intrigue) {
        $stmt = $pdo->prepare('
            INSERT INTO intrigue (project_id, title, description, position, status)
            VALUES (:project_id, :title, :description, :position, :status)
        ');
        
        if ($stmt->execute([
            ':project_id' => $fantasy_project,
            ':title' => $intrigue['title'],
            ':description' => $intrigue['description'],
            ':position' => $index + 1,
            ':status' => 'first_read'
        ])) {
            $intrigue_id = $pdo->lastInsertId();
            $intrigue_data[] = ['project_id' => $fantasy_project, 'intrigue_id' => $intrigue_id];
            echo "✅ Intrigue créée: " . htmlspecialchars($intrigue['title']) . "<br>";
            $success_count++;
        }
    }
    
    // Pour le deuxième projet (Thriller)
    $thriller_project = $project_ids['Détective Mystère: Le Cas du Manoir Maudit'];
    $intrigues_thriller = [
        ['title' => 'Acte I: Découverte du Cadavre', 'description' => 'Le crime est découvert'],
        ['title' => 'Acte II: Interrogatoires et Secrets', 'description' => 'Chacun a quelque chose à cacher'],
        ['title' => 'Acte III: La Vérité Émerge', 'description' => 'Les pièces du puzzle s\'assemblent'],
    ];
    
    foreach ($intrigues_thriller as $index => $intrigue) {
        $stmt = $pdo->prepare('
            INSERT INTO intrigue (project_id, title, description, position, status)
            VALUES (:project_id, :title, :description, :position, :status)
        ');
        
        if ($stmt->execute([
            ':project_id' => $thriller_project,
            ':title' => $intrigue['title'],
            ':description' => $intrigue['description'],
            ':position' => $index + 1,
            ':status' => 'draft'
        ])) {
            $intrigue_id = $pdo->lastInsertId();
            $intrigue_data[] = ['project_id' => $thriller_project, 'intrigue_id' => $intrigue_id];
            echo "✅ Intrigue créée: " . htmlspecialchars($intrigue['title']) . "<br>";
            $success_count++;
        }
    }
    
    // ===== 4. CRÉER DES ÉLÉMENTS (SCÈNES) =====
    echo "<h2>🎬 Création des scènes</h2>";
    
    $scenes_content = [
        'fantasy_1' => '<p>Le roi repose sur son lit de mort. Les chandeliers vacillent, projetant des ombres dansantes sur les murs du château. Au loin, les cloches de la ville sonnent lentement...</p><p>Sa fille aînée, Elara, est agenouillée à son chevet. Ses yeux sont rouges de larmes, mais elle retient son chagrin. Elle doit être forte. Elle doit être reine.</p>',
        'fantasy_2' => '<p>Trois jours après la mort du roi, les prétendants commencent à arriver. Des cavaliers de provinces lointaines, des lords venant de leurs châteaux fortifiés. Tous veulent la couronne. Tous croient qu\'elle leur appartient de droit.</p><p>Elara les observe du haut de la tour du château. Elle voit leurs ambitions dans leurs yeux. Elle entend leurs promesses creuses.</p>',
        'thriller_1' => '<p>L\'inspecteur Marcus Ward se tient dans le hall du manoir, regardant le corps inerte du maître des lieux. Sir Edmund Blackwell. Connu dans la région pour sa richesse et ses secrets.</p><p>Maintenant, il ne sera plus jamais un secret.</p>',
        'thriller_2' => '<p>Le serviteur, James Cuthbert, jure ne rien avoir entendu la nuit du meurtre. Mais ses mains tremblent. L\'inspecteur Ward le remarque.</p><p>"Vous êtes certains?" demande-t-il, sa voix douce mais pénétrante.</p>',
    ];
    
    // Ajouter des scènes au premier projet
    $intrigue_count = 0;
    foreach ($intrigue_data as $data) {
        if ($data['project_id'] == $fantasy_project) {
            $stmt = $pdo->prepare('
                INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, location)
                VALUES (:intrigue_id, :type, :title, :description, :position, :content, :word_count, :status, :pov_character, :location)
            ');
            
            $position = $intrigue_count + 1;
            $content_key = 'fantasy_' . $position;
            $word_count = str_word_count(strip_tags($scenes_content[$content_key] ?? ''));
            
            if ($stmt->execute([
                ':intrigue_id' => $data['intrigue_id'],
                ':type' => 'scene',
                ':title' => 'Scène ' . $position,
                ':description' => 'Une scène importante',
                ':position' => 1,
                ':content' => $scenes_content[$content_key] ?? '',
                ':word_count' => $word_count,
                ':status' => 'finalized',
                ':pov_character' => $position == 1 ? 'Elara' : 'Elara',
                ':location' => 'Le Château Royal'
            ])) {
                echo "✅ Scène créée: Scène {$position} (Fantasy) - {$word_count} mots<br>";
                $success_count++;
                $intrigue_count++;
            }
        }
    }
    
    // Ajouter des scènes au projet thriller
    $intrigue_count = 0;
    foreach ($intrigue_data as $data) {
        if ($data['project_id'] == $thriller_project) {
            $stmt = $pdo->prepare('
                INSERT INTO element (intrigue_id, type, title, description, position, content, word_count, status, pov_character, location)
                VALUES (:intrigue_id, :type, :title, :description, :position, :content, :word_count, :status, :pov_character, :location)
            ');
            
            $position = $intrigue_count + 1;
            $content_key = 'thriller_' . $position;
            $word_count = str_word_count(strip_tags($scenes_content[$content_key] ?? ''));
            
            if ($stmt->execute([
                ':intrigue_id' => $data['intrigue_id'],
                ':type' => 'scene',
                ':title' => 'Scène ' . $position,
                ':description' => 'Une scène importante',
                ':position' => 1,
                ':content' => $scenes_content[$content_key] ?? '',
                ':word_count' => $word_count,
                ':status' => 'draft',
                ':pov_character' => $position == 1 ? 'Marcus Ward' : 'Marcus Ward',
                ':location' => 'Manoir Blackwell'
            ])) {
                echo "✅ Scène créée: Scène {$position} (Thriller) - {$word_count} mots<br>";
                $success_count++;
                $intrigue_count++;
            }
        }
    }
    
    // ===== 5. CRÉER DES PERSONNAGES =====
    echo "<h2>👥 Création des personnages</h2>";
    
    $characters_data = [
        [
            'project_id' => $fantasy_project,
            'name' => 'Elara Stormborn',
            'role' => 'main',
            'description' => 'Fille aînée du roi, héritière au trône. Intelligente, ambitieuse mais juste.',
            'physical_traits' => 'Cheveux blonds platine, yeux gris acier. Silhouette élégante.',
            'arc_notes' => 'De héritière hésitante à reine puissante',
        ],
        [
            'project_id' => $fantasy_project,
            'name' => 'Kael Blackthorn',
            'role' => 'main',
            'description' => 'Prétendant au trône du Sud. Guerrier redoutable et politicien retors.',
            'physical_traits' => 'Cheveux noirs, barbe taillée. Musculature impressionnante. Cicatrice au visage.',
            'arc_notes' => 'Ennemi juré qui pourrait devenir allié',
        ],
        [
            'project_id' => $thriller_project,
            'name' => 'Inspecteur Marcus Ward',
            'role' => 'main',
            'description' => 'Détective expérimenté. À la recherche de la vérité, peu importe le prix.',
            'physical_traits' => 'Cheveux gris, regard perçant. Environ 55 ans. Vêtements de couleur sombre.',
            'arc_notes' => 'Confronté à ses propres démons en résolvant le cas',
        ],
        [
            'project_id' => $thriller_project,
            'name' => 'Victoria Blackwell',
            'role' => 'secondary',
            'description' => 'Veuve du défunt Sir Edmund. Riche héritière avec des secrets.',
            'physical_traits' => 'Élégante, environ 45 ans. Toujours vêtue de noir.',
            'arc_notes' => 'Suspecte principale qui devient complice inattendue',
        ],
    ];
    
    foreach ($characters_data as $char) {
        $stmt = $pdo->prepare('
            INSERT INTO character (project_id, name, role, description, physical_traits, arc_notes, color_hex)
            VALUES (:project_id, :name, :role, :description, :physical_traits, :arc_notes, :color_hex)
        ');
        
        // Générer une couleur aléatoire
        $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#F39C12', '#9B59B6', '#E74C3C'];
        $color = $colors[array_rand($colors)];
        
        if ($stmt->execute([
            ':project_id' => $char['project_id'],
            ':name' => $char['name'],
            ':role' => $char['role'],
            ':description' => $char['description'],
            ':physical_traits' => $char['physical_traits'],
            ':arc_notes' => $char['arc_notes'],
            ':color_hex' => $color
        ])) {
            echo "✅ Personnage créé: " . htmlspecialchars($char['name']) . "<br>";
            $success_count++;
        }
    }
    
    // ===== 6. CRÉER DES NOTES D'ÉCRITURE =====
    echo "<h2>📝 Création des notes d'écriture</h2>";
    
    $notes_data = [
        [
            'project_id' => $fantasy_project,
            'note_type' => 'plot',
            'title' => 'Twist majeur - Acte 2',
            'content' => 'Révéler que Kael est en réalité le frère perdu d\'Elara!',
        ],
        [
            'project_id' => $fantasy_project,
            'note_type' => 'character',
            'title' => 'Développement d\'Elara',
            'content' => 'Elle doit apprendre à faire confiance à ses instincts politiques. Le pouvoir la change.',
        ],
        [
            'project_id' => $fantasy_project,
            'note_type' => 'world',
            'title' => 'Magie du monde',
            'content' => 'La magie est liée au sang royal. Seuls les descendants du premier roi peuvent la canaliser.',
        ],
        [
            'project_id' => $thriller_project,
            'note_type' => 'plot',
            'title' => 'Révélation finale',
            'content' => 'Le meurtrier n\'est pas qui on croit. C\'est un acte de vengeance personnelle.',
        ],
        [
            'project_id' => $thriller_project,
            'note_type' => 'idea',
            'title' => 'Scène bonus',
            'content' => 'Ajouter un flashback montrant la relation de Victoria avec le vrai coupable.',
        ],
    ];
    
    foreach ($notes_data as $note) {
        $stmt = $pdo->prepare('
            INSERT INTO writing_note (project_id, note_type, title, content, is_pinned)
            VALUES (:project_id, :note_type, :title, :content, :is_pinned)
        ');
        
        if ($stmt->execute([
            ':project_id' => $note['project_id'],
            ':note_type' => $note['note_type'],
            ':title' => $note['title'],
            ':content' => $note['content'],
            ':is_pinned' => 1
        ])) {
            echo "✅ Note créée: " . htmlspecialchars($note['title']) . "<br>";
            $success_count++;
        }
    }
    
    // ===== 7. CRÉER DES ÉVÉNEMENTS TIMELINE =====
    echo "<h2>📅 Création de la timeline</h2>";
    
    $timeline_data = [
        [
            'project_id' => $fantasy_project,
            'event_date' => '1200-01-01',
            'title' => 'Fondation du Royaume',
            'description' => 'Le premier roi unit les terres',
            'impact_notes' => 'Établit les bases de la sorcellerie royale'
        ],
        [
            'project_id' => $fantasy_project,
            'event_date' => '1500-06-15',
            'title' => 'Mort du Roi Ancien',
            'description' => 'Le roi meurt, laissant le trône sans héritier clair',
            'impact_notes' => 'Déclenche la crise dont parle l\'histoire'
        ],
    ];
    
    foreach ($timeline_data as $event) {
        $stmt = $pdo->prepare('
            INSERT INTO timeline_event (project_id, event_date, title, description, impact_notes)
            VALUES (:project_id, :event_date, :title, :description, :impact_notes)
        ');
        
        if ($stmt->execute([
            ':project_id' => $event['project_id'],
            ':event_date' => $event['event_date'],
            ':title' => $event['title'],
            ':description' => $event['description'],
            ':impact_notes' => $event['impact_notes']
        ])) {
            echo "✅ Événement timeline créé: " . htmlspecialchars($event['title']) . "<br>";
            $success_count++;
        }
    }
    
    // ===== RÉSUMÉ =====
    echo "<h2 style='color: green; margin-top: 30px;'>✅ SUCCÈS!</h2>";
    echo "<p style='font-size: 16px;'>";
    echo "<strong>$success_count</strong> éléments créés avec succès.<br>";
    echo "</p>";
    
    echo "<h3>📋 Identifiants de test:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo "Utilisateurs créés:\n";
    foreach ($test_users as $user) {
        echo "  👤 Utilisateur: " . htmlspecialchars($user['username']) . "   |   Mot de passe: " . htmlspecialchars($user['password']) . "\n";
    }
    echo "\nProjets créés:\n";
    foreach ($project_ids as $title => $id) {
        echo "  📚 $id - " . htmlspecialchars($title) . "\n";
    }
    echo "</pre>";
    
    echo "<h3>🧪 À tester:</h3>";
    echo "<ul>";
    echo "<li>✅ Connexion avec alice/alice123</li>";
    echo "<li>✅ Dashboard avec statistiques</li>";
    echo "<li>✅ Éditeur de scènes avec TinyMCE</li>";
    echo "<li>✅ Gestion des personnages</li>";
    echo "<li>✅ Notes d'écriture</li>";
    echo "<li>✅ Timeline d'événements</li>";
    echo "<li>✅ Export en DOCX</li>";
    echo "<li>✅ Export en PDF (Ctrl+P)</li>";
    echo "<li>✅ Comptage de mots en temps réel</li>";
    echo "<li>✅ Versioning des scènes</li>";
    echo "</ul>";
    
    echo "<div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;'>";
    echo "<strong>💡 Tip:</strong> Ouvrez les Outils Développeur (F12) → Console pour voir les erreurs potentielles.<br>";
    echo "<a href='dashboard.php' style='color: #1976d2; text-decoration: none; font-weight: bold;'>→ Aller au Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; background: #ffebee; border-radius: 5px;'>";
    echo "<strong>❌ Erreur:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    exit(1);
}

?>