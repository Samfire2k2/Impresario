<?php
// Redirection automatique vers la page de connexion ou dashboard
include 'includes/config.php';
include 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
