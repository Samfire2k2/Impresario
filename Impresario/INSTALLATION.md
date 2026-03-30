# 📋 Guide d'installation d'Impresario

Ce guide vous explique comment installer et configurer Impresario sur votre serveur alwaysdata.

## 🎯 Résumé des étapes

1. **Initialiser la base de données**
2. **Déployer les fichiers PHP sur le serveur**
3. **Tester l'application**

---

## 1️⃣ Initialiser la base de données

### A. Accéder à phpPgAdmin (l'interface de gestion PostgreSQL d'alwaysdata)

1. Allez sur: https://phppgadmin.alwaysdata.net/
2. Connectez-vous avec vos identifiants alwaysdata

### B. Créer la base de données et les tables

1. Dans phpPgAdmin, créez une nouvelle base de données nommée `axolotl_impresario_bdd`
2. Si elle existe déjà, naviguez dedans
3. Ouvrez l'onglet **"SQL"** (en haut)
4. Copiez-collez TOUT le contenu du fichier `database.sql`
5. Cliquez sur **"Exécuter"** (Execute)

**OU via ligne de commande (si vous avez accès SSH):**

```bash
psql -h postgresql-axolotl.alwaysdata.net -U axolotl -d axolotl_impresario_bdd < database.sql
```

**Identifiants de la base de données:**
- Host: `postgresql-axolotl.alwaysdata.net`
- Port: `5432`
- Database: `axolotl_impresario_bdd`
- User: `axolotl`
- Password: `B690981DE5`

### C. Vérifier l'installation

Dans phpPgAdmin, vous devriez voir les tables créées:
- ✅ `author`
- ✅ `project`
- ✅ `intrigue`
- ✅ `tag`
- ✅ `element`
- ✅ `element_tag`
- ✅ `dependency`

Et des données de test dans chaque table.

---

## 2️⃣ Déployer les fichiers PHP

### A. Préparer la structure de répertoires

Sur votre serveur alwaysdata, créez cette structure dans `/home/votre_user/www/`:

```
Impresario/
├── includes/
│   ├── config.php
│   └── functions.php
├── api/
│   ├── tags.php
│   ├── elements.php
│   └── dependencies.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── main.js
│       └── auth.js
├── login.php
├── dashboard.php
├── project.php
├── intrigue.php
└── logout.php
```

### B. Upload via FTP ou le manager web alwaysdata

1. Connectez-vous au manager alwaysdata: https://admin.alwaysdata.com
2. Allez dans **"Sites"** → sélectionnez votre site
3. Cliquez sur **"Gérer"** → onglet **"FTP"**
4. Utilisez un client FTP (FileZilla, WinSCP, etc.) pour uploader les fichiers

**OU utilisez le gestionnaire de fichiers web:**

1. Allez dans **"Fichiers"** → `www/`
2. Créez les dossiers `Impresario`, `includes`, `api`, `assets/css`, `assets/js`
3. Uploadez les fichiers un par un

### C. Structure finale

Votre site devrait ressembler à ceci:
```
https://axolotl.alwaysdata.net/Impresario/login.php
https://axolotl.alwaysdata.net/Impresario/dashboard.php
https://axolotl.alwaysdata.net/Impresario/project.php
etc...
```

**OU** si vous mettez les fichiers à la racine du site:
```
https://axolotl.alwaysdata.net/login.php
https://axolotl.alwaysdata.net/dashboard.php
etc...
```

---

## 3️⃣ Tester l'application

### A. Accès initial

Allez à: `https://axolotl.alwaysdata.net/login.php`

### B. Compte de test

Utilisez les identifiants de test créés lors de l'initialisation:
- **Nom d'utilisateur:** `test_home`
- **Mot de passe:** `test_home`

### C. Première utilisation

1. ✅ Connectez-vous
2. ✅ Créez un nouveau projet
3. ✅ Ajoutez une intrigue
4. ✅ Ajoutez des scènes
5. ✅ Créez des tags
6. ✅ Assignez des tags aux scènes

---

## ⚠️ Dépannage

### "Erreur de connexion à la base de données"

**Solution:**
- Vérifiez les identifiants dans `includes/config.php`
- Assurez-vous que la base de données existe dans PostgreSQL
- Vérifiez que l'utilisateur `axolotl` a les permissions

### "Page blanche"

**Solution:**
- Vérifiez que PHP est activé sur votre serveur alwaysdata
- Activez l'affichage des erreurs en ajoutant en haut du fichier:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### "Les fichiers CSS/JS ne se chargent pas"

**Solution:**
- Vérifiez les chemins relatifs dans les fichiers HTML
- Assurez-vous que les fichiers sont uploadés dans le dossier `assets/`
- Vérifiez que les permissions du serveur permettent la lecture

### "Session ne fonctionne pas"

**Solution:**
- Vérifiez que le dossier `/tmp/` existe et a les permissions d'écriture
- Vérifiez les paramètres PHP de votre serveur alwaysdata

---

## 🔄 Étapes supplémentaires (optionnel)

### Remplacer le compte de test

1. Si vous ne voulez pas utiliser `test_home`, créez un nouveau compte en vous inscrivant
2. Supprimez le compte `test_home` depuis phpPgAdmin (table `author`)

### Modifier l'URL de base

Si vous ne voulez pas de chemins longs, demandez à alwaysdata de configurer un domaine racine pour votre application.

### Activer HTTPS

Allez dans les paramètres de votre site sur alwaysdata et activez HTTPS (let's encrypt gratuit).

---

## 📚 Fichiers importants

| Fichier | Rôle |
|---------|------|
| `includes/config.php` | Configuration de la BDD |
| `includes/functions.php` | Fonctions réutilisables |
| `login.php` | Authentification |
| `dashboard.php` | Liste des projets |
| `project.php` | Gestion des intrigues |
| `intrigue.php` | Gestion des scènes |
| `api/*.php` | API pour opérations AJAX |
| `database.sql` | Schéma de la BDD |

---

## ✅ Checklist final

- [ ] Base de données créée et remplie avec `database.sql`
- [ ] Tous les fichiers PHP uploadés
- [ ] Dossier `assets/` avec CSS et JS accessibles
- [ ] Accès possible à `login.php`
- [ ] Connexion avec `test_home/test_home` fonctionne
- [ ] Création d'un projet réussie
- [ ] Navigation entre pages fonctionne
- [ ] Tags s'affichent correctement

---

## 📞 Support

Si vous rencontrez des problèmes:
1. Consultez la documentation PHP sur php.net
2. Vérifiez les logs d'erreur de votre serveur alwaysdata
3. Testez en local d'abord si possible

**Bonne chance ! 🚀**
