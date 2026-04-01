<?php
/**
 * Setup Palettes - Création des tables et palettes prédéfinies
 */
include 'includes/config.php';

try {
    // 1. Create color_palette table
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS color_palette (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            colors JSON NOT NULL,
            is_colorblind_friendly BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');
    echo "✅ Table color_palette créée<br>";

    // 2. Add palette columns to project table
    $pdo->exec('ALTER TABLE project ADD COLUMN IF NOT EXISTS palette_id INTEGER REFERENCES color_palette(id)');
    $pdo->exec('ALTER TABLE project ADD COLUMN IF NOT EXISTS font_size INTEGER DEFAULT 16');
    $pdo->exec('ALTER TABLE project ADD COLUMN IF NOT EXISTS element_size INTEGER DEFAULT 100');
    echo "✅ Colonnes palette_id, font_size, element_size ajoutées à project<br><br>";

    // 3. Insert predefined palettes
    $palettes = [
        [
            'name' => 'Pastel Harmony',
            'description' => 'Couleurs douces et harmonieuses',
            'colors' => [
                'primary' => '#E8D4C2',
                'secondary' => '#F5E6D3',
                'accent1' => '#D4A5A5',
                'accent2' => '#A8D5BA',
                'accent3' => '#FFE5CC',
                'accent4' => '#D4E8F7'
            ],
            'is_colorblind_friendly' => false
        ],
        [
            'name' => 'Dark Elegance',
            'description' => 'Palette sombre et élégante',
            'colors' => [
                'primary' => '#2a2419',
                'secondary' => '#3a3228',
                'accent1' => '#8B4513',
                'accent2' => '#CD853F',
                'accent3' => '#D2B48C',
                'accent4' => '#F5DEB3'
            ],
            'is_colorblind_friendly' => false
        ],
        [
            'name' => 'Vibrant Colors',
            'description' => 'Couleurs vives et énergiques',
            'colors' => [
                'primary' => '#FF6B6B',
                'secondary' => '#4ECDC4',
                'accent1' => '#FFE66D',
                'accent2' => '#95E1D3',
                'accent3' => '#C7CEEA',
                'accent4' => '#FF8B94'
            ],
            'is_colorblind_friendly' => false
        ],
        [
            'name' => 'Colorblind Safe',
            'description' => 'Palette sûre pour daltoniens',
            'colors' => [
                'primary' => '#0173B2',
                'secondary' => '#DE8F05',
                'accent1' => '#CC78BC',
                'accent2' => '#CA9161',
                'accent3' => '#56B4E9',
                'accent4' => '#F0E442'
            ],
            'is_colorblind_friendly' => true
        ],
        [
            'name' => 'Soft Earth',
            'description' => 'Tons terreux et naturels',
            'colors' => [
                'primary' => '#9B7D4F',
                'secondary' => '#C9A87C',
                'accent1' => '#6B5344',
                'accent2' => '#8B6F47',
                'accent3' => '#D2B48C',
                'accent4' => '#E8DCC8'
            ],
            'is_colorblind_friendly' => false
        ],
        [
            'name' => 'Warm Sunset',
            'description' => 'Couchers de soleil chauds',
            'colors' => [
                'primary' => '#FF7F50',
                'secondary' => '#FFB347',
                'accent1' => '#FF6347',
                'accent2' => '#DC143C',
                'accent3' => '#FFA500',
                'accent4' => '#FFD700'
            ],
            'is_colorblind_friendly' => false
        ]
    ];

    foreach ($palettes as $palette) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO color_palette (name, description, colors, is_colorblind_friendly)
                VALUES (:name, :description, :colors, :colorblind)
            ');
            $stmt->execute([
                ':name' => $palette['name'],
                ':description' => $palette['description'],
                ':colors' => json_encode($palette['colors']),
                ':colorblind' => $palette['is_colorblind_friendly']
            ]);
            echo "✅ Palette créée: {$palette['name']}<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false) {
                echo "⏭️  Palette déjà existante: {$palette['name']}<br>";
            } else {
                throw $e;
            }
        }
    }

    echo "<br><strong>✨ Setup des palettes complété!</strong>";

} catch (Exception $e) {
    echo "❌ Erreur: " . htmlspecialchars($e->getMessage());
}
?>
