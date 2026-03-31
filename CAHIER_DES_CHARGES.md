# Cahier des Charges - Impresario

## Vue d'ensemble

**Impresario** est une application web de gestion narrative permettant de planifier et organiser des histoires complexes avec intrigues parallèles, dépendances d'événements et système de tags colorés.

L'application comprend trois pages principales :
- **Page de Connexion** - Authentification utilisateur
- **Page d'Accueil** - Gestion des projets (histoires)
- **Page Planner** - Gestion détaillée d'une histoire (page principale et plus complexe)

---

## PAGE DE CONNEXION

### Fonctionnalités

- ✅ Possibilité de s'**inscrire** avec :
  - Un ID unique (non existant en base de données)
  - Un mot de passe
- ✅ Possibilité de se **connecter** directement avec ID + mot de passe

### Charte graphique

- Design minimaliste et épuré
- Thème cohérent avec l'identité Impresario (warm, timeless aesthetic)
- Supportant le mode clair et mode sombre

---

## PAGE D'ACCUEIL (Dashboard)

### Fonctionnalités

- ✅ Afficher la **liste des histoires** existantes de l'utilisateur
  - Accès à chaque histoire en 1 clic
  - Les couleurs affichées reflètent celles choisies dans le Planner de chaque histoire
  
- ✅ Bouton **"Créer une nouvelle histoire"**
  - Permet de démarrer une nouvelle narration

- ✅ Bouton **"Supprimer une histoire"**
  - Avec demande de confirmation avant suppression

- ✅ Fonction **"Export des données"**
  - Permet à l'utilisateur de sauvegarder l'intégralité de son contenu
  - Formats : JSON et SQL

- ✅ Fonction **"Déconnexion"**
  - Retour au formulaire de connexion

- ✅ Options de **personnalisation** :
  - Choix de couleurs / palettes de couleurs
  - Mode nuit disponible

### Charte graphique

- Présentation en grille ou liste des projets
- Chaque carte de projet affiche les couleurs de la palette choisie
- Palette prédéfinie visible et modifiable
- Support du mode clair / sombre

---

## PAGE PLANNER (Gestion d'une histoire)

La page Planner est le cœur de l'application. C'est ici que l'utilisateur planifie et organise chaque histoire en détail.

### FONCTIONNALITÉS

#### Navigation et Contrôle global

- ✅ **Quitter le Planner** - Retour à la page d'accueil
- ✅ **Déconnexion** - Quitter Impresario complètement

#### Gestion des Intrigues

- ✅ **Créer une intrigue** - Ajouter une nouvelle ligne narrative
- ✅ **Éditer une intrigue** :
  - Nom/titre
  - Description et notes
  - Couleur associée
- ✅ **Déplacer une intrigue** - Changer l'ordre des intrigues les unes par rapport aux autres
- ✅ **Supprimer une intrigue** - Avec confirmation

#### Gestion des Scènes

- ✅ **Créer une scène** - Ajouter un événement à une intrigue
- ✅ **Éditer une scène** :
  - Titre
  - Description de détail
  - Tags associés
  - Couleur personnalisée
- ✅ **Supprimer une scène** - Retrait de l'intrigue
- ✅ **Drag'n Drop une scène** :
  - Le long d'une même intrigue (réordonner)
  - **Sur une autre intrigue** (déplacer entre intrigues)
- ✅ **Ranger / sortir une scène de la Poche** :
  - La Poche est la réserve de scènes non encore affectées à une intrigue
  - Permet de créer des scènes "brouillon" avant assignation

#### Gestion des Marqueurs

- ✅ **Créer un marqueur** :
  - Note brève pouvant se placer entre deux scènes
  - Cas d'usage : mentionner une ellipse, changement d'arc, changement de chapitre, etc.
  - **Important** : Un seul marqueur "changement de chapitre" segmente toutes les intrigues simultanément

#### Gestion des Tags et Couleurs

- ✅ **Créer un tag** :
  - Définir le label du tag
  - Choisir la couleur du tag
  - Les scènes portant ce tag adopteront la couleur du tag
  
- ✅ **Modifier les couleurs du Planner** :
  - Couleur d'arrière-plan
  - Couleur des boutons
  - Couleur par défaut des scènes
  - Choix d'une palette prédéfinie pour garantir des couleurs harmonieuses
  - **Palettes prédéfinies disponibles** :
    - Palette Pastel (couleurs douces harmonieuses)
    - Palette Dark (élégante et sombre)
    - Palette Vibrant (couleurs éclatantes)
    - **Palette Daltonienne** (accessible pour personnes daltoniennes)
    - Palette Soft Earth (teintes naturelles)
    - Palette Warm Sunset (teintes chaudes)

#### Gestion des Dépendances

- ✅ **Créer une dépendance** entre deux scènes :
  - "A jamais placé avant B"
  - "B toujours placé après C"
  - etc.
- ✅ **Vérifier l'absence de boucles** :
  - Pas de sac de nœud cyclique (A avant B avant C avant A)
  - Validation logique des dépendances
- ✅ **Dépendances indépendantes des intrigues** :
  - A et B peuvent être de deux intrigues différentes
  - Les dépendances s'appliquent sur le global

#### Paramètres d'Affichage

- ✅ **Changer la direction de défilement** :
  - Vertical (par défaut)
  - Horizontal
  
- ✅ **Taille et format des éléments** :
  - Changer la taille des scènes
  - Choisir la taille de police générale
  - Contrôles accessibles depuis les paramètres
  
- ✅ **Affichage des descriptions** :
  - Afficher / masquer toutes les descriptions simultanément
  - Afficher / masquer chaque description manuellement
  - Les fonctions d'édition ne sont **pas visibles** par défaut
  - La scène est un bloc épuré montrant avant tout le titre
  - Symboles discrets pour signifier les dépendances

---

### CHARTE GRAPHIQUE

#### Layout et Navigation

- ✅ **Ruban de boutons latéral** :
  - Visible sur le côté gauche de l'écran
  - Boutons pour les fonctions principales :
    - Ouvrir la Poche
    - Créer une scène
    - Créer une intrigue
    - Gérer les tags
    - Accéder aux dépendances
    - Paramètres (couleurs, taille, etc.)
    - Déconnexion

- ✅ **Titre de l'histoire** :
  - Affiché en haut à gauche
  - Fond semi-transparent
  - Visible/intégré au design principal

#### Couleurs et Gradients

- ✅ **Arrière-plan selon direction de défilement** :
  - Dégradé de couleurs (2, 3 ou 4 couleurs selon configuration)
  - Direction : vertical ou horizontal selon le sens de défilement
  - Les couleurs d'arrière-plan définissent également les couleurs de la carte projet en page d'accueil

- ✅ **Palettes prédéfinies** :
  - L'utilisateur choisit une palette
  - Au sein de la palette, assignation des couleurs :
    - Couleur pour les scènes en mode défaut
    - Couleur pour les boutons et titre
    - Couleur(s) pour l'arrière-plan

#### Marqueurs

- ✅ **Design des marqueurs** :
  - Apparence de marque-page (bookmark)
  - Forme allongée terminée par une **encoche** (clip-path polygon ✂️)
  - Gradient de couleur distinctive
  - Différenciation claire avec les scènes

#### Scènes et Éléments

- ✅ **Bloc scène épuré** :
  - Affichage principal : titre uniquement
  - Symboles discrets pour dépendances :
    - Icône ou petit badge indicateur
    - Ne cluttère pas le visuel
  - Descriptions masquées par défaut
  - Édition accessible au survol ou clic dédié

#### Modes visuels

- ✅ Support du **mode clair** (par défaut)
- ✅ Support du **mode sombre** (cohérent avec identité Impresario)
- ✅ **Transitions douces** entre les modes
- ✅ Respect du choix utilisateur en session

---

## SPÉCIFICATIONS GRAPHIQUES GLOBALES

### Identité visuelle

- **Thème** : "Modern Writer's Aesthetic"
- **Inspirations** : Glassmorphism, design minimaliste, esthétique chaleureuse
- **Palette primaire** : Tons chauds (crème, bronze, brun)
- **Accessibilité** : Contraste suffisant, palette daltonienne disponible, cursor personnalisé (crayon)

### Police

- Police principale : Georgia / Segoe UI (serif)
- Fallback : système

### Responsivité

- Support desktop principal
- Interface adaptée pour grand écran (résolution >= 1920px)

---

## NOTES IMPORTANTES

1. **Palettes Harmonieuses** : Anticiper les chocs visuels en proposant des ensembles de couleurs cohérentes
2. **Accessibilité Daltonienne** : Une palette spéciale incluant des contrastes appropriés
3. **Marqueurs Globaux** : Les marqueurs "chapitres" affectent l'affichage sur toutes les intrigues
4. **Absence de Boucle** : Validation stricte des dépendances pour éviter les incohérences logiques
5. **Export Utilisateur** : Capacité pour chaque utilisateur de télécharger toutes ses données

