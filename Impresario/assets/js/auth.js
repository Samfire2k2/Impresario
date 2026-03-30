// Gestion des onglets d'authentification
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const authForms = document.querySelectorAll('.auth-form');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets et formulaires
            tabBtns.forEach(b => b.classList.remove('active'));
            authForms.forEach(form => form.classList.remove('active'));
            
            // Activer l'onglet et le formulaire cliqués
            this.classList.add('active');
            const form = document.getElementById(tabName + '-form');
            if (form) {
                form.classList.add('active');
            }
        });
    });
});
