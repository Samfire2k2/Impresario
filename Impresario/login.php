<?php
include 'includes/config.php';
include 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            $user = verifyLogin($pdo, $username, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Identifiants incorrects.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($username) || empty($password) || empty($password_confirm)) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (strlen($password) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif ($password !== $password_confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif (userExists($pdo, $username)) {
            $error = 'Ce nom d\'utilisateur existe déjà.';
        } else {
            if (createUser($pdo, $username, $password)) {
                $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
            } else {
                $error = 'Erreur lors de la création du compte.';
            }
        }
    }
}

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresario - Authentification</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <h1>Impresario</h1>
            <p class="subtitle">Organisez vos histoires</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="auth-tabs">
                <button class="tab-btn active" data-tab="login">Connexion</button>
                <button class="tab-btn" data-tab="register">Inscription</button>
            </div>
            
            <!-- Formulaire de connexion -->
            <form id="login-form" class="auth-form active" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login-username">Nom d'utilisateur</label>
                    <input type="text" id="login-username" name="username" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Mot de passe</label>
                    <input type="password" id="login-password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </form>
            
            <!-- Formulaire d'inscription -->
            <form id="register-form" class="auth-form" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="register-username">Nom d'utilisateur</label>
                    <input type="text" id="register-username" name="username" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Mot de passe</label>
                    <input type="password" id="register-password" name="password" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="register-password-confirm">Confirmer le mot de passe</label>
                    <input type="password" id="register-password-confirm" name="password_confirm" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-primary">Créer un compte</button>
            </form>
        </div>
    </div>
    
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/dynamic-sizer.js"></script>
    <script src="assets/js/auth.js"></script>
</body>
</html>
