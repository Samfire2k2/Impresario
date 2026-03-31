<?php
/**
 * Script pour appliquer les migrations de base de données manquantes
 */
include 'includes/config.php';
include 'includes/functions.php';

requireLogin();

echo "=== Vérification et application des migrations ===\n\n";

$migrations = [
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS orientation VARCHAR(20) DEFAULT 'vertical'",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS bg_color_1 VARCHAR(7) DEFAULT '#fef5e7'",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS bg_color_2 VARCHAR(7) DEFAULT '#f8e8d8'",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS bg_color_3 VARCHAR(7) DEFAULT NULL",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS bg_color_4 VARCHAR(7) DEFAULT NULL",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS button_color VARCHAR(7) DEFAULT '#d4a574'",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS title_color VARCHAR(7) DEFAULT '#5a4a3a'",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS scene_default_color VARCHAR(7) DEFAULT '#fef5e7'",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS palette_id INTEGER DEFAULT NULL",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS font_size INTEGER DEFAULT 16",
    "ALTER TABLE project ADD COLUMN IF NOT EXISTS element_size INTEGER DEFAULT 100"
];

try {
    foreach ($migrations as $index => $migration) {
        echo "Exécution migration " . ($index + 1) . "...\n";
        $pdo->exec($migration);
        echo "✓ Migration " . ($index + 1) . " completée\n";
    }
    
    echo "\n✅ Toutes les migrations ont été appliquées avec succès!\n";
    
    // Insert default palettes if not exists
    echo "\nInitialisation des palettes de couleurs...\n";
    $palettes = [
        [
            'name' => 'Pastel Harmony',
            'code' => 'pastel',
            'description' => 'Couleurs douces et harmonieuses',
            'colors' => ['bg_1' => '#E8D4C2', 'bg_2' => '#F5E6D3', 'button' => '#D4A5A5', 'title' => '#6B5344', 'scene_default' => '#FFE5CC'],
            'colorblind' => 0
        ],
        [
            'name' => 'Dark Elegance',
            'code' => 'dark',
            'description' => 'Palette sombre et élégante',
            'colors' => ['bg_1' => '#2a2419', 'bg_2' => '#3a3228', 'button' => '#8B4513', 'title' => '#F5DEB3', 'scene_default' => '#CD853F'],
            'colorblind' => 0
        ],
        [
            'name' => 'Vibrant Colors',
            'code' => 'vibrant',
            'description' => 'Couleurs vives et énergiques',
            'colors' => ['bg_1' => '#FF6B6B', 'bg_2' => '#4ECDC4', 'button' => '#FFE66D', 'title' => '#2C3E50', 'scene_default' => '#95E1D3'],
            'colorblind' => 0
        ],
        [
            'name' => 'Colorblind Safe',
            'code' => 'colorblind',
            'description' => 'Palette sûre pour daltoniens',
            'colors' => ['bg_1' => '#0173B2', 'bg_2' => '#DE8F05', 'button' => '#CC78BC', 'title' => '#FFFFFF', 'scene_default' => '#56B4E9'],
            'colorblind' => 1
        ],
        [
            'name' => 'Soft Earth',
            'code' => 'earth',
            'description' => 'Tons terreux et naturels',
            'colors' => ['bg_1' => '#9B7D4F', 'bg_2' => '#C9A87C', 'button' => '#6B5344', 'title' => '#3D3026', 'scene_default' => '#D2B48C'],
            'colorblind' => 0
        ],
        [
            'name' => 'Warm Sunset',
            'code' => 'sunset',
            'description' => 'Couchers de soleil chauds',
            'colors' => ['bg_1' => '#FF7F50', 'bg_2' => '#FFB347', 'button' => '#FF6347', 'title' => '#8B0000', 'scene_default' => '#FFA500'],
            'colorblind' => 0
        ]
    ];
    
    foreach ($palettes as $palette) {
        try {
            $stmt = $pdo->prepare('INSERT INTO color_palette (name, code, description) VALUES (:name, :code, :desc)');
            $stmt->execute([':name' => $palette['name'], ':code' => $palette['code'], ':desc' => $palette['description']]);
            $paletteId = $pdo->lastInsertId();
            
            // Insert colors for this palette
            foreach ($palette['colors'] as $role => $color) {
                $stmt = $pdo->prepare('INSERT INTO palette_color (palette_id, role, hex_color) VALUES (:pid, :role, :color)');
                $stmt->execute([':pid' => $paletteId, ':role' => $role, ':color' => $color]);
            }
            
            echo "✓ Palette créée: {$palette['name']}\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false) {
                echo "⏭️  Palette déjà existante: {$palette['name']}\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✅ Palettes initialisées!\n";
    echo "\nRedirection vers le planner...\n";
    
    // Rediriger vers le planner après succès
    header('Location: planner.php?project=' . $_GET['project'] ?? '1');
    exit;
    
} catch (Exception $e) {
    echo "\n❌ Erreur lors de l'application des migrations:\n";
    echo $e->getMessage() . "\n";
    echo "\nVeuillez vérifier votre base de données et réessayer.\n";
}
?>
