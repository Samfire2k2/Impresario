# Impresario - Gestionnaire d'histoires avec intrigues parallèles

Un outil web professionnel pour écrivains permettant d'organiser et de planifier vos histoires avec des intrigues parallèles, des personnages, des notes d'écriture, et un système de gestion de versions complet.

## 🎯 Fonctionnalités principales

### Gestion de projets
- ✅ **Système d'authentification sécurisé**: Comptes utilisateur isolés
- ✅ **Projets complets**: Genre, synopsis, cibles de mot-clés, statut (draft/in_progress/completed/published)
- ✅ **Palettes de couleurs**: 5 palettes prédéfinies (Pastel, Dark, Vibrant, Minimal, Accessible)
- ✅ **Thèmes clair/sombre**: Interface adaptée à vos préférences

### Structuration narrative
- ✅ **Intrigues parallèles**: Organisez vos storylines qui se déroulent en parallèle
- ✅ **Scènes et marqueurs**: Différentes types d'éléments pour structurer votre récit
- ✅ **Vue d'ensemble (Vue Globale)**: Voyez TOUTES les intrigues simultanément dans une grille responsive
- ✅ **Vue détail (Vue Détail)**: Travaillez sur une intrigue spécifique avec tous les détails
- ✅ **Drag-and-drop multi-intrigue**: Déplacez les scènes entre intrigues facilement

### Gestion avancée
- ✅ **Personnages**: Roster complet avec traits physiques, arc narratif, couleur d'identification
- ✅ **Relations scène-personnage**: Suivez qui apparaît dans chaque scène (rôle principal/secondaire/mentionné)
- ✅ **Tags et catégories**: Système de taggage hiérarchique coloré par intrigue
- ✅ **Dépendances**: Empêchez les événements de venir dans le mauvais ordre
- ✅ **Timeline d'événements**: Chronologie des événements clés avec personnages impliqués

### Écriture et révision
- ✅ **Contenu des scènes**: Écrire directement dans l'application avec compteur de mots
- ✅ **Statuts**: draft → first_read → finalized
- ✅ **Métadonnées scènes**: POV, date, location, contenu complet
- ✅ **Notes d'écriture**: notes épinglées pour vos idées (plot, character, world, timeline, idea)
- ✅ **Historique des versions**: Suivez l'évolution de chaque scène avec résumés des changements
- ✅ **Compteurs et statistiques**: Progression vers votre cible de mot-clés en temps réel

### Visual polish
- ✅ **Effets de luminescence bronze**: Glowing effects sur les éléments interactifs
- ✅ **Contraste optimisé**: Descriptions en noir pur pour la lisibilité maximale
- ✅ **Responsive design**: Fonctionne sur desktop, tablet, mobile
- ✅ **Animations fluides**: Transitions smooth et drag-drop intuitif

## 📋 Structure du projet

```
Impresario/
├── includes/
│   ├── config.php              # Configuration BD PostgreSQL
│   ├── functions.php           # Fonctions utilitaires
│   ├── head.php                # Template <head> commun
│   ├── palette-functions.php   # Gestion des palettes de couleurs
│   └── writer-functions.php    # Fonctions du mode écrivain
├── api/
│   ├── elements.php            # CRUD scènes/marqueurs, drag-drop
│   ├── intrigue.php            # CRUD intrigues
│   ├── palette.php             # API palettes
│   ├── tags.php                # API tags
│   ├── export.php              # Export projets
│   ├── character.php           # CRUD personnages
│   ├── positions.php           # Mise à jour positions drag-drop
│   └── dependencies.php        # Gestion dépendances
├── assets/
│   ├── css/
│   │   └── style.css           # 4700+ lignes, thèmes clair/sombre
│   └── js/
│       ├── main.js             # JS principal
│       ├── auth.js             # Authentification
│       ├── drag-drop.js        # Drag-drop multi-intrigue
│       ├── dynamic-sizer.js    # Redimensionnement dynamique
│       └── theme-manager.js    # Gestion des thèmes
├── pages/
│   └── [pages dynamiques futures]
├── login.php                   # Authentification
├── dashboard.php               # Liste des projets
├── project.php                 # Vue projet (intrigues)
├── planner.php                 # Planificateur détail (intrigue unique)
├── planner-overview.php        # VUE GLOBALS (toutes intrigues)
├── character-editor.php        # Gestion personnages
├── characters.php              # Liste personnages
├── writing-notes.php           # Notes d'écrivain
├── scene-editor.php            # Éditeur de scène avancé
├── logout.php                  # Déconnexion
├── database.sql                # Schéma + données test
└── README.md
```

## 🚀 Installation

### 1. Configuration de la base de données

Connectez-vous à PostgreSQL et exécutez le script `database.sql`:

```bash
psql -h [postgresql-host] -U [user] -d [database] < database.sql
```

Le script crée:
- 18 tables avec contraintes CHECK et foreign keys
- Indexes pour performance
- Vue `project_statistics`
- 3 utilisateurs de test avec projets complets

### 2. Configuration PHP

Modifiez `includes/config.php` avec vos paramètres PostgreSQL:

```php
$db_host = 'your-host';
$db_name = 'your-database';
$db_user = 'your-user';
$db_pass = 'your-password';
$db_port = 5432;
```

### 3. Déploiement

Déployez tous les fichiers sur votre serveur web. Assurez-vous que:
- PHP 8.0+ est installé
- Extension PDO PostgreSQL est activée
- Les répertoires `assets/` et `includes/` sont accessibles

## 🔐 Utilisateurs de test

Trois comptes préconfigurés avec données complètes:

| Nom | Mot de passe | Thème | Palette | Projet |
|-----|-------------|-------|---------|--------|
| `test_home` | `test_home` | Light | Pastel (bronze) | Fantasy - Le Cœur de l'Ombre |
| `alice` | `alice123` | Dark | Dark (bleu) | Sci-fi - Étoile Perdue |
| `bob` | `bob456` | Light | Vibrant | Mystery - Les Secrets de Willowbrook |

Chaque projet inclut:
- 2-3 intrigues complètes
- 4-7 scènes avec contenu réel
- 3-4 personnages détaillés
- Relations scène-personnage
- Tags colorés
- Timeline d'événements
- Notes d'écriture épinglées
- Historique des versions

## 📝 Utilisation

### Flux de travail principal

**1. Connexion & Dashboard**
```
login.php → dashboard.php (liste des projets)
```

**2. Gestion du projet**
```
project.php (vue projet)
├─ "Vue Globale" → planner-overview.php (TOUS les intrigues en grille)
├─ "Vue Détail" → planner.php (intrigue unique)
├─ "Personnages" → characters.php (roster du projet)
└─ "Notes" → writing-notes.php (notes épinglées)
```

**3. Planification dans Vue Globale**
- Toutes les intrigues visibles comme colonnes
- Drag-drop entre colonnes pour déplacer les scènes
- Buttons "Ajouter scène" et "Voir détail" par intrigue
- Clic sur "Vue Détail" pour focus une intrigue

**4. Travail en détail**
- Cliquez sur une scène pour l'éditer
- Ajoutez contenu, POV, date, location
- Marquez le statut (draft/first_read/finalized)
- Consultez l'historique des versions
- Gérez les relations personnages

### Clés de la productivité

- **Vue Globale**: Pour voir la structure narrative complète d'un projet
- **Drag-drop**: Déplacez les scènes entre intrigues en un clic
- **Tags colorés**: Visualisez rapidement la répartition des thèmes
- **Notes épinglées**: Gardez vos idées clés à portée de main
- **Compteurs**: Suivez votre progression vers vos cibles

## 🎨 Palettes de couleurs

### Pastel (défaut)
Couleurs chaudes et relaxantes: beige, bronze, marron

### Dark
Gris sombres avec accent bleu électrique - idéal pour travail de nuit

### Vibrant
Rose, orange, violet, bleu pâle - couleurs vives et énergiques

### Minimal
Blanc, noir, gris - design épuré et professionnel

### Accessible (Colorblind-friendly)
Jaune/bleu/orange avec bon contraste - adapté à la daltonisme

## 🌐 Fonctionnalités API

Toutes les opérations utilisent des endpoints REST sécurisés:

- `api/elements.php`: CRUD scènes, drag-drop, positions
- `api/intrigue.php`: Créer/modifier intrigues
- `api/character.php`: CRUD personnages
- `api/tags.php`: Gestion tags
- `api/export.php`: Export projets

## 📊 Données de test

Les objets test incluent:

- **12 scènes écrites** (800-1800 mots chacune)
- **9 intrigues** complètes avec statuts
- **9 personnages** avec descriptions détaillées
- **10 relations** scène-personnage
- **9 tags** catégorisés
- **6 notes d'écriture** avec pinning
- **5 événements timeline**
- **6 versions** d'éléments avec historique
- **3 dépendances** (ordering constraints)

## 🔧 Technologies

- **Backend**: PHP 8.x vanilla (pas de framework)
- **Base de données**: PostgreSQL 12+
- **Frontend**: JavaScript vanilla, HTML5, CSS3
- **Drag-Drop**: HTML5 native API
- **Authentification**: Sessions PHP sécurisées

## 📈 Statistiques du projet

- 18 tables PostgreSQL
- 4700+ lignes de CSS (including dark mode)
- 1000+ lignes de JavaScript (multi-intrigue drag-drop)
- 2000+ lignes de PHP (API endpoints)
- Support complet des métadonnées d'écriture

## 📱 Affichage

L'application affiche les intrigues comme des lignes horizontales, avec les scènes représentées par des carrés le long de ces lignes. Les couleurs des scènes correspondent aux tags assignés.

## 🛠️ Technologie

- **Backend:** PHP 7.4+
- **Base de données:** PostgreSQL
- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Authentification:** Sessions PHP avec mot de passe hashé (bcrypt)

## 🔒 Sécurité

- Tous les mots de passe sont hashés avec bcrypt
- Validation et sanitization de toutes les entrées utilisateur
- Vérification des droits d'accès pour chaque opération
- Utilisation de prepared statements pour prévenir les injections SQL

## 📝 Notes de développement

- Les positions des intrigues et scènes sont gérées via un champ `position` pour un tri efficace
- Les dépendances ne peuvent créer que des restrictions (un élément empêche certains autres de venir après)
- Le système de couleurs peut être étendu pour supporter plus de catégories

## 🐛 Problèmes connus

- (**RÉSOLU A IMPLEMENTER**) L'affichage en grille des scènes peut être optimisé pour les écrans très larges
- (**TODO**) Interface de drag-and-drop pour réordonner les éléments
- (**TODO**) Mode d'édition pour les scènes existantes

## 🔄 Fonctionnalités futures

- [ ] Drag-and-drop pour réordonner les scènes
- [ ] Export en Markdown ou PDF
- [ ] Partage de projets avec d'autres utilisateurs
- [ ] Historique des modifications
- [ ] Chercher et remplacer du texte
- [ ] Templates de projets
- [ ] Affichage en timeline verticale
- [ ] Collaboration en temps réel

## 📞 Support

Pour toute question, consultez la documentation ou contactez le développeur.

---

**Version:** 1.0
**Dernière mise à jour:** Mars 2026
