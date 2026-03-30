# 🚀 Impresario - Démarrage rapide

Vous avez reçu Impresario complètement développé. Voici comment le mettre en ligne en 5 minutes.

## ⚡ TL;DR (Très Rapide)

1. **Initialiser BDD:** Copier `database.sql` → phpPgAdmin → Exécuter
2. **Uploader:** Via FTP, mettre tous les fichiers dans `/www/Impresario/`
3. **Accéder:** `https://axolotl.alwaysdata.net/Impresario/`
4. **Login:** `test_home / test_home`
5. **C'est prêt!** Créez un projet pour tester

---

## 📋 Checklist de déploiement (15 min)

### ✅ 1. Initialiser la base de données (5 min)

**Via phpPgAdmin (le plus simple):**

```
1. https://phppgadmin.alwaysdata.net/
2. Login avec identifiants alwaysdata
3. Base de données → New → axolotl_impresario_bdd
4. Sélectionner la BD → Tab "SQL"
5. COPIER TOUT LE CONTENU DE: database.sql
6. COLLER dans la fenêtre SQL
7. EXÉCUTER (Execute)
8. ✓ Attendre confirmation "SUCCESS"
```

**OU via Terminal (SSH):**
```bash
psql -h postgresql-axolotl.alwaysdata.net -U axolotl -d axolotl_impresario_bdd < database.sql
```

### ✅ 2. Uploader les fichiers (5 min)

**Via le gestionnaire de fichiers alwaysdata:**

```
1. https://admin.alwaysdata.com
2. Fichiers → www/
3. Créer dossier: Impresario
4. Uploader tous les fichiers SAUF database.sql
5. Structure finale:
   www/Impresario/
   ├── login.php
   ├── dashboard.php
   ├── project.php
   ├── intrigue.php
   ├── includes/ (dossier)
   ├── api/ (dossier)
   ├── assets/ (dossier)
   └── [autres fichiers]
```

**OU via FTP (FileZilla, WinSCP):**
```
1. Ouvrir FTP client
2. Host: sftp-axolotl.alwaysdata.net
3. Username/Password: identifiants alwaysdata
4. Port: 22
5. Naviguer: /home/xxx/www/
6. Créer dossier: Impresario
7. Déposer tous les fichiers (SAUF database.sql)
```

### ✅ 3. Tester (5 min)

```
1. Navigateur: https://axolotl.alwaysdata.net/Impresario/
2. Login: test_home
3. Password: test_home
4. ✓ Dashboard appelle → Vous êtes connecté!
5. Créer nouveau projet pour vérifier
6. Félicitations! C'est prêt!
```

---

## 🆘 Ça ne fonctionne pas?

### "Page blanche ou erreur database"

**Vérifier:**
1. Est-ce que database.sql a bien été exécuté? (Check dans phpPgAdmin)
2. Les identifiants PostgreSQL sont corrects dans `includes/config.php`:
   ```php
   define('DB_HOST', 'postgresql-axolotl.alwaysdata.net');
   define('DB_PORT', '5432');
   define('DB_NAME', 'axolotl_impresario_bdd');
   define('DB_USER', 'axolotl');
   define('DB_PASS', 'B690981DE5');
   ```

### "Fichiers CSS/JS ne se chargent pas"

**Vérifier:**
1. Structure correcte: `Impresario/assets/css/style.css`
2. Chemins des `<link>` et `<script>` sont relatifs (pas absolus)
3. Permissions: 644 pour les fichiers

### "Impossible de se connecter"

**Vérifier:**
1. Identifiants: `test_home / test_home` (exact)
2. Table `author` existe dans la BDD
3. Espace blanc en début de `login.php` ou autres fichiers?

### "500 Internal Server Error"

1. Vérifier les logs d'erreur: Fichiers → tmp/
2. Ou activer debug mode:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

---

## 📚 Après le déploiement

1. **Pour vous (admin/développeur):**
   - Lire `README.md` pour comprendre l'architecture
   - Lire `PROJECT_COMPLETION.md` pour les détails
   - Garder `database.sql` en backup

2. **Pour l'amie (utilisateur):**
   - Lire `FEATURES_AVANCEES.md` pour les casutilisation
   - Créer un nouveau compte (nouveau login)
   - Commencer par créer un projet test
   - Expérimenter avec les tags et dépendances

---

## 🎯 Premiers pas pour l'amie

**Créer sa première histoire:**

1. Login avec son compte (ou test_home)
2. Cliquer **"+ Nouveau projet"**
3. Donner un titre: "Ma première histoire"
4. Description: (optionnel)
5. Créer
6. Cliquer **"Ouvrir"**
7. Cliquer **"+ Ajouter"** (dans Intrigues)
8. Créer "Intrigue 1: L'aventure"
9. Ouvrir l'intrigue
10. Cliquer **"+ Ajouter un élément"**
11. Créer première scène: "Le héros quitte le village"
12. Ajouter 2-3 autres scènes
13. Cliquer **"Gérer les tags"** → Ajouter tags colorés
14. Assigner tags aux scènes
15. Admirer le résultat! 🎨

---

## 💡 Astuces

- **Sauvegarder régulièrement** votre structure (screenshots)
- **Tester l'ordre** des scènes avant de commettre une version finale
- **Utiliser les descriptions** pour les notes qu'on oublie facilement
- **Les marqueurs** pour les points clés (ex: "Point de non-retour")
- **Les dépendances** pour prévenir les erreurs logiques

---

## 📞 Besoin d'aide?

- **Dépannage général:** Voir `INSTALLATION.md` (section Dépannage)
- **Comment utiliser les features:** Voir `FEATURES_AVANCEES.md`
- **Détails techniques:** Voir `PROJECT_COMPLETION.md`
- **Erreur PHP:** Vérifier les logs de PHP dans Fichiers → tmp/

---

## 🎉 Bonus: Améliorations futures possibles

Si vous voulez ajouter plus tard:
- Drag & drop pour réordonner
- Export en PDF/Markdown
- Partage de projets avec autres utilisateurs
- Dark mode
- Undo/Redo

Voir `PROJECT_COMPLETION.md` pour liste complète.

---

**Bon planung! L'application est prête à l'emploi!** 🚀✨

Pour toute question, reportez-vous aux fichiers de documentation.
