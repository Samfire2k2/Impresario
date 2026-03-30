<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sizing Dynamique - Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .test-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        .test-box {
            background: rgba(251, 245, 230, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid var(--bronze);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .size-indicator {
            font-size: 2em;
            font-weight: bold;
            color: var(--bronze);
            text-align: center;
            padding: 20px;
            background: rgba(201, 168, 124, 0.1);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .test-element {
            background: white;
            border-left: 4px solid var(--bronze);
            padding: 16px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .code-block {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 12px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.85em;
        }
        .instruction {
            background: rgba(107, 142, 35, 0.1);
            border-left: 4px solid #6B8E23;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
        }
        h1, h2 {
            color: var(--bronze);
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎯 Test du Système de Sizing Dynamique</h1>

        <div class="size-indicator">
            Échelle actuelle: <span id="current-scale">100%</span>
        </div>

        <div class="test-box">
            <h2>Instructions</h2>
            <div class="instruction">
                <strong>Utilisez les raccourcis clavier:</strong><br>
                • <code>Ctrl + +</code> pour augmenter<br>
                • <code>Ctrl + -</code> pour diminuer<br>
                • <code>Ctrl + 0</code> pour réinitialiser
            </div>
            <div class="instruction">
                <strong>Ou utilisez le panneau flottant:</strong><br>
                Cliquez sur les boutons en bas à droite (−, +, ↻)
            </div>
        </div>

        <div class="test-box">
            <h2>Tests d'Éléments</h2>
            
            <h3>Boutons</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="btn btn-primary">Bouton Principal</button>
                <button class="btn btn-secondary">Bouton Secondaire</button>
            </div>

            <h3 style="margin-top: 20px;">Formulaire</h3>
            <div class="form-group">
                <label>Texte d'exemple</label>
                <input type="text" placeholder="Essayez avec différentes tailles">
            </div>

            <h3 style="margin-top: 20px;">Cartes (Cards)</h3>
            <div class="test-grid">
                <div class="project-card">
                    <h3>Carte de Projet</h3>
                    <p>Les cartes se redimensionnent fluidement</p>
                </div>
                <div class="element-card">
                    <h4>Carte d'Élément</h4>
                    <p>Les éléments s'adaptent aussi</p>
                </div>
            </div>

            <h3 style="margin-top: 20px;">Tags et Badges</h3>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <span class="tag">Tag 1</span>
                <span class="tag">Tag 2</span>
                <span class="badge">Badge de test</span>
            </div>
        </div>

        <div class="test-box">
            <h2>Informations Techniques</h2>
            
            <h3>Variable CSS Actuelle</h3>
            <div class="code-block">
                --size-scale: <span id="size-value">1</span>
            </div>

            <h3>Variables de Sizing Disponibles</h3>
            <div class="test-grid">
                <div class="test-element">
                    <strong>--font-xs</strong><br>
                    Très petit (0.75rem)
                </div>
                <div class="test-element">
                    <strong>--font-sm</strong><br>
                    Petit (0.875rem)
                </div>
                <div class="test-element">
                    <strong>--font-base</strong><br>
                    Normal (1rem)
                </div>
                <div class="test-element">
                    <strong>--font-lg</strong><br>
                    Grand (1.125rem)
                </div>
                <div class="test-element">
                    <strong>--btn-padding</strong><br>
                    12px × size-scale
                </div>
                <div class="test-element">
                    <strong>--card-padding</strong><br>
                    24px × size-scale
                </div>
            </div>

            <h3>Plage de Redimensionnement</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: rgba(107, 142, 35, 0.1); padding: 10px; border-radius: 6px;">
                    <strong>Minimum:</strong> 75% (0.75x)
                </div>
                <div style="background: rgba(205, 133, 63, 0.1); padding: 10px; border-radius: 6px;">
                    <strong>Maximum:</strong> 150% (1.5x)
                </div>
            </div>
        </div>

        <div class="test-box">
            <h2>Test de Stockage</h2>
            <p>Votre préférence est sauvegardée dans <code>localStorage</code>:</p>
            <button class="btn btn-primary" onclick="showLocalStorage()">Afficher le stockage</button>
            <button class="btn btn-primary" onclick="clearLocalStorage()">Effacer le stockage</button>
            <div id="storage-info" style="margin-top: 10px; display: none; background: #f0f0f0; padding: 10px; border-radius: 6px;">
                <code id="storage-output"></code>
            </div>
        </div>

        <div class="test-box">
            <h2>Console JavaScript</h2>
            <p>Ouvrez la console (F12) et essayez:</p>
            <div class="code-block" style="background: black; color: lime; padding: 15px;">
                // Obtenir l'instance du sizer<br>
                window.sizer<br><br>
                
                // Obtenir l'échelle actuelle<br>
                window.sizer.getScale()<br><br>
                
                // Définir une échelle<br>
                window.sizer.setScale(1.2)<br><br>
                
                // Augmenter/diminuer<br>
                window.sizer.increase()<br>
                window.sizer.decrease()<br><br>
                
                // Réinitialiser<br>
                window.sizer.reset()
            </div>
        </div>

        <div class="test-box" style="background: rgba(107, 142, 35, 0.1); border-color: #6B8E23;">
            <h2 style="color: #6B8E23;">✅ Test Complet</h2>
            <p>Le système de sizing dynamique est <strong>correctement implémenté</strong> si:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>✓ Le panneau de contrôle apparaît en bas à droite</li>
                <li>✓ Les raccourcis clavier fonctionnent</li>
                <li>✓ Les éléments se redimensionnent fluidement</li>
                <li>✓ L'affichage change sans saccades</li>
                <li>✓ La taille est sauvegardée après rechargement</li>
                <li>✓ Toutes les transitions sont fluides</li>
            </ul>
        </div>
    </div>

    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/dynamic-sizer.js"></script>
    <script>
        // Update displays
        function updateDisplay() {
            const scale = window.sizer.getScale();
            document.getElementById('current-scale').textContent = (scale * 100).toFixed(0) + '%';
            document.getElementById('size-value').textContent = scale.toFixed(2);
        }

        // Update on size change
        window.addEventListener('sizeChanged', updateDisplay);
        
        // Initial update
        document.addEventListener('DOMContentLoaded', updateDisplay);

        // Show localStorage
        function showLocalStorage() {
            const value = localStorage.getItem('impresario-size-scale');
            const output = document.getElementById('storage-output');
            const info = document.getElementById('storage-info');
            
            if (value) {
                output.textContent = `impresario-size-scale: ${value}`;
            } else {
                output.textContent = 'Aucune valeur trouvée (utilisez la valeur par défaut)';
            }
            
            info.style.display = 'block';
        }

        // Clear localStorage
        function clearLocalStorage() {
            localStorage.removeItem('impresario-size-scale');
            const output = document.getElementById('storage-output');
            output.textContent = 'Stockage effacé. Rechargez la page pour réinitialiser.';
        }
    </script>
</body>
</html>
