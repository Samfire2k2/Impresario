# Impresario - Gestionnaire d'histoires avec intrigues parallèles

Un outil web pour organiser vos histoires avec des intrigues parallèles, des dépendances d'événements, et un système de tags colorés.

## 🎯 Fonctionnalités

- ✅ **Système d'authentification**: Créez un compte et ne voyez que vos propres projets
- ✅ **Gestion de projets**: Créez plusieurs histoires (projets)
- ✅ **Intrigues parallèles**: Divisez chaque histoire en plusieurs intrigues qui se déroulent en parallèle
- ✅ **Scènes**: Ajoutez des scènes le long de chaque intrigue
- ✅ **Dépendances**: Empêchez certains événements de survenir après d'autres
- ✅ **Tags et couleurs**: Catégorisez vos scènes avec des tags colorés pour visualiser rapidement la répartition du contenu

## 📋 Structure du projet

```
Axo/
├── includes/
│   ├── config.php          # Configuration de la base de données
│   └── functions.php       # Fonctions utilitaires
├── api/
│   ├── tags.php           # API pour gérer les tags
│   ├── elements.php       # API pour gérer les éléments
│   └── dependencies.php   # API pour gérer les dépendances
├── assets/
│   ├── css/
│   │   └── style.css      # Feuille de style
│   └── js/
│       ├── main.js        # JavaScript principal
│       └── auth.js        # JavaScript pour l'authentification
├── login.php              # Page de connexion/inscription
├── dashboard.php          # Tableau de bord des projets
├── project.php            # Vue d'un projet (gestion des intrigues)
├── intrigue.php           # Vue d'une intrigue (gestion des scènes)
├── logout.php             # Déconnexion
├── database.sql           # Script d'initialisation de la base de données
├── .gitignore
└── README.md
```

## 🚀 Installation

### 1. Configuration de la base de données

Connectez-vous à votre serveur PostgreSQL et exécutez le script `database.sql`:

```bash
psql -h postgresql-axolotl.alwaysdata.net -U axolotl -d axolotl_impresario_bdd < database.sql
```

**Identifiants PostgreSQL:**
- Host: `postgresql-axolotl.alwaysdata.net`
- Port: `5432`
- Database: `axolotl_impresario_bdd`
- User: `axolotl`
- Password: `B690981DE5`

### 2. Déploiement du code

Déployez tous les fichiers PHP sur votre serveur gratuit (alwaysdata.net ou similar).

### 3. Structure des répertoires sur le serveur

Assurez-vous que la structure suivante existe sur votre serveur:
```
/public_html/Impresario/
├── includes/
├── api/
├── assets/
├── *.php files
└── database.sql
```

## 🔐 Compte de test

Après l'initialisation de la base de données, un compte de test est créé:
- **Nom d'utilisateur:** `test_home`
- **Mot de passe:** `test_home`

## 📝 Utilisation

### 1. Connexion
Accédez à `https://axolotl.alwaysdata.net/` et connectez-vous ou créez un compte

### 2. Créer un projet
Cliquez sur "Nouveau projet" dans le tableau de bord

### 3. Ajouter des intrigues
Dans votre projet, ajoutez une ou plusieurs intrigues (storylines parallèles)

### 4. Ajouter des scènes
Ouvrez une intrigue et ajoutez des scènes le long de la ligne

### 5. Gérer les tags
Créez des tags colorés pour catégoriser vos scènes (ex: "Romance", "Action", "Développement de personnage")

### 6. Configurer les dépendances
Pour empêcher certains événements de venir après d'autres:
- Ouvrez l'élément qui impose la contrainte
- Ajoutez les éléments qui ne doivent pas venir après dans la liste des dépendances

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
