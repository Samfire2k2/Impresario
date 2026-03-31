<?php
/**
 * Button Showcase - Démonstration de tous les styles de boutons disponibles
 */
include 'includes/config.php';
include 'includes/functions.php';

requireLogin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Showcase - Boutons | Impresario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="logo-link">← Impresario</a>
            <h1 class="page-title">Showcase des Boutons</h1>
            <div class="navbar-user">
                <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
            </div>
        </div>
    </header>

    <div class="showcase-container">
        <!-- Primary Buttons -->
        <div class="showcase-section">
            <h2>Boutons Primaires</h2>
            <div class="showcase-inline">
                <button class="btn btn-primary">Bouton Primaire</button>
                <button class="btn btn-primary btn-small">Petit</button>
                <button class="btn btn-primary btn-large">Grand</button>
                <button class="btn btn-primary" disabled>Désactivé</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-primary"&gt;Cliquez-moi&lt;/button&gt;</div>
        </div>

        <!-- Secondary Buttons -->
        <div class="showcase-section">
            <h2>Boutons Secondaires</h2>
            <div class="showcase-inline">
                <button class="btn btn-secondary">Bouton Secondaire</button>
                <button class="btn btn-secondary btn-small">Petit</button>
                <button class="btn btn-secondary btn-large">Grand</button>
                <button class="btn btn-secondary" disabled>Désactivé</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-secondary"&gt;Annuler&lt;/button&gt;</div>
        </div>

        <!-- Danger Buttons -->
        <div class="showcase-section">
            <h2>Boutons Danger</h2>
            <div class="showcase-inline">
                <button class="btn btn-danger">Danger</button>
                <button class="btn btn-danger btn-small">Petit</button>
                <button class="btn btn-danger btn-large">Grand</button>
                <button class="btn btn-danger" disabled>Désactivé</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-danger"&gt;Supprimer&lt;/button&gt;</div>
        </div>

        <!-- Success Buttons -->
        <div class="showcase-section">
            <h2>Boutons Succès</h2>
            <div class="showcase-inline">
                <button class="btn btn-success">Succès</button>
                <button class="btn btn-success btn-small">Petit</button>
                <button class="btn btn-success btn-large">Grand</button>
                <button class="btn btn-success" disabled>Désactivé</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-success"&gt;Valider&lt;/button&gt;</div>
        </div>

        <!-- Warning Buttons -->
        <div class="showcase-section">
            <h2>Boutons Avertissement</h2>
            <div class="showcase-inline">
                <button class="btn btn-warning">Avertissement</button>
                <button class="btn btn-warning btn-small">Petit</button>
                <button class="btn btn-warning btn-large">Grand</button>
                <button class="btn btn-warning" disabled>Désactivé</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-warning"&gt;Attention&lt;/button&gt;</div>
        </div>

        <!-- Ghost Buttons -->
        <div class="showcase-section">
            <h2>Boutons Fantôme (Transparent)</h2>
            <div class="showcase-inline">
                <button class="btn btn-ghost">Fantôme</button>
                <button class="btn btn-ghost btn-small">Petit</button>
                <button class="btn btn-ghost btn-large">Grand</button>
                <button class="btn btn-ghost" disabled>Désactivé</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-ghost"&gt;Optionnel&lt;/button&gt;</div>
        </div>

        <!-- Link Buttons -->
        <div class="showcase-section">
            <h2>Boutons Lien</h2>
            <div class="showcase-inline">
                <button class="btn btn-link">Lien Simple</button>
                <button class="btn btn-link btn-small">Petit</button>
                <button class="btn btn-link" disabled>Désactivé</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-link"&gt;Plus d'infos&lt;/button&gt;</div>
        </div>

        <!-- Icon Buttons -->
        <div class="showcase-section">
            <h2>Boutons Icônes</h2>
            <div class="showcase-inline">
                <button class="btn btn-primary btn-icon"><i class="fas fa-plus"></i></button>
                <button class="btn btn-primary btn-icon"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-icon"><i class="fas fa-trash"></i></button>
                <button class="btn btn-success btn-icon"><i class="fas fa-check"></i></button>
                <button class="btn btn-ghost btn-icon"><i class="fas fa-heart"></i></button>
            </div>
            <div class="code-block">&lt;button class="btn btn-primary btn-icon"&gt;&lt;i class="fas fa-plus"&gt;&lt;/i&gt;&lt;/button&gt;</div>
            
            <div style="margin-top: 20px;">
                <h3>Tailles d'icônes</h3>
                <div class="showcase-inline">
                    <button class="btn btn-primary btn-icon btn-icon-small"><i class="fas fa-plus"></i></button>
                    <button class="btn btn-primary btn-icon"><i class="fas fa-plus"></i></button>
                    <button class="btn btn-primary btn-icon btn-icon-large"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        </div>

        <!-- Rounded Buttons -->
        <div class="showcase-section">
            <h2>Boutons Arrondis</h2>
            <div class="showcase-inline">
                <button class="btn btn-primary btn-rounded">Arrondi</button>
                <button class="btn btn-success btn-rounded btn-small">Petit</button>
                <button class="btn btn-danger btn-rounded btn-large">Grand</button>
            </div>
            <div class="code-block">&lt;button class="btn btn-primary btn-rounded"&gt;Moderne&lt;/button&gt;</div>
        </div>

        <!-- Combination Examples -->
        <div class="showcase-section">
            <h2>Combinaisons Recommandées</h2>
            <div class="showcase-grid">
                <div class="showcase-item">
                    <h3>Form Action</h3>
                    <div class="showcase-buttons">
                        <button class="btn btn-primary">Enregistrer</button>
                        <button class="btn btn-secondary">Annuler</button>
                    </div>
                </div>
                
                <div class="showcase-item">
                    <h3>Delete Confirmation</h3>
                    <div class="showcase-buttons">
                        <button class="btn btn-danger">Supprimer</button>
                        <button class="btn btn-ghost">Garder</button>
                    </div>
                </div>
                
                <div class="showcase-item">
                    <h3>Navigation</h3>
                    <div class="showcase-buttons">
                        <button class="btn btn-ghost btn-small"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button class="btn btn-ghost btn-small">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <div class="showcase-item">
                    <h3>Quick Actions</h3>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary btn-icon btn-icon-small"><i class="fas fa-plus"></i></button>
                        <button class="btn btn-ghost btn-icon btn-icon-small"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-icon btn-icon-small"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Guidelines -->
        <div class="showcase-section">
            <h2>Guide d'Utilisation</h2>
            <div class="showcase-item">
                <h3>Classes CSS Disponibles</h3>
                <pre class="code-block" style="white-space: pre-wrap;">
/* Main types */
.btn-primary       /* Action principale */
.btn-secondary     /* Action secondaire */
.btn-danger        /* Action destructive */
.btn-success       /* Action confirmée */
.btn-warning       /* Avertissement */
.btn-ghost         /* Optionnel/Transparent */
.btn-link          /* Lien simple */

/* Sizes */
.btn-small         /* 32px HEIGHT */
.btn-large         /* 48px height */

/* Shapes */
.btn-icon          /* Carré 40x40px */
.btn-icon-small    /* Carré 32x32px */
.btn-icon-large    /* Carré 48x48px */
.btn-rounded       /* Border-radius: 50px */

/* States */
:disabled          /* Désactivé automatiquement */
.loading           /* Animation de chargement */
                </pre>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-manager.js"></script>
</body>
</html>
