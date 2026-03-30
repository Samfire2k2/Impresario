<!-- 
    WRITER MODE INTEGRATION SNIPPET
    
    Ajouter cet HTML dans project.php (après la navbar existante)
    pour afficher les liens rapides vers le Writer Mode
-->

<style>
    .writer-mode-toolbar {
        background: linear-gradient(135deg, var(--bronze) 0%, var(--bronze-dark) 100%);
        padding: calc(16px * var(--size-scale));
        margin-bottom: 20px;
        border-radius: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: 0 4px 15px rgba(201, 168, 124, 0.2);
    }

    .writer-toolbar-title {
        color: #FFFBF0;
        font-weight: 700;
        font-size: 1.1em;
    }

    .writer-toolbar-links {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .writer-toolbar-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        background: rgba(255, 251, 240, 0.2);
        border: 1px solid rgba(255, 251, 240, 0.4);
        border-radius: 8px;
        text-decoration: none;
        color: #FFFBF0;
        font-size: 0.95em;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .writer-toolbar-link:hover {
        background: rgba(255, 251, 240, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 768px) {
        .writer-mode-toolbar {
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .writer-toolbar-links {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php
// Check that Writer Mode is set up (optional - comment out if not needed)
// $checkWriterMode = $pdo->query("SELECT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_name = 'element' AND column_name = 'content')");
// $writerModeActive = $checkWriterMode->fetch(PDO::FETCH_NUM)[0];
$writerModeActive = true; // Assumes Writer Mode is activated
?>

<?php if ($writerModeActive): ?>
    <div class="writer-mode-toolbar">
        <span class="writer-toolbar-title">✍️ Mode Écrivain</span>
        <div class="writer-toolbar-links">
            <a href="writer-dashboard.php?id=<?php echo $projectId; ?>" class="writer-toolbar-link" title="Dashboard avec statistiques">
                📊 Dashboard
            </a>
            <a href="characters.php?id=<?php echo $projectId; ?>" class="writer-toolbar-link" title="Gérer les personnages">
                👥 Personnages
            </a>
            <a href="writing-notes.php?id=<?php echo $projectId; ?>" class="writer-toolbar-link" title="Notes d'écriture">
                📝 Notes
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- 
    LOCATION: project.php
    
    Insérez ce code:
    - APRÈS le navbar-container (les liens de navigation)
    - AVANT la section "<!-- Intrigue View -->"
    - À l'intérieur du container principal
    
    EXAMPLE:
    
    </div> <!-- Close navbar-container -->
    
    <div class="container">
        <!-- INSERT WRITER MODE TOOLBAR HERE -->
        
        <!-- Existing project content -->
        <div class="project-layout">
            ...
