# 🚀 DÉMARRAGE RAPIDE - CERTIFICATS

**5 minutes pour un système de certificats en production.**

---

## ⚡ Étape 1: Migrer la base de données (2 min)

Choisissez votre base de données:

### MySQL
```bash
php assets/database/init_mysql_db.php
```

### SQLite
```bash
php assets/database/init_db.php
```

**Résultat attendu:**
```
✅ Base de données initialisée avec succès.
```

---

## ⚡ Étape 2: Vérifier l'installation (1 min)

```bash
php verify-integration.php
```

**Résultat attendu:**
```
🎉 INTÉGRATION RÉUSSIE!
Status: ✅ PRODUCTION-READY
```

---

## ⚡ Étape 3: Intégrer le JavaScript (1 min)

Dans votre fichier HTML (n'importe quelle page avec utilisateurs):

```html
<!-- En bas du body -->
<script src="assets/js/certificate-manager.js"></script>
```

**C'est tout! Le système s'initialise automatiquement.**

---

## ⚡ Étape 4: Tester (1 min)

Accédez à:
```
http://localhost/formation_bamba/certificate-demo.html
```

---

## ✅ C'est fait!

Les certificats se créent automatiquement quand un utilisateur atteint **98%** de progression.

---

## 🎯 Flux utilisateur

```
1. Utilisateur apprend      → Progression augmente
2. À 98%+                   → Certificat créé automatiquement
3. Bouton apparaît          → "Télécharger certificat"
4. Utilisateur clique       → HTML s'ouvre
5. Utilisateur imprime      → Ou convertit en PDF
```

---

## 🔍 Vérifier un certificat

**URL:** `http://localhost/formation_bamba/certificate-demo.html`

**Entrez un ID:** `CERT-1778807074-C5E68A`

---

## 📚 Documentation

Besoin de plus de détails?

- **DEPLOYMENT_GUIDE.md** - Guide complet
- **CERTIFICATES_GUIDE.md** - Tous les endpoints
- **certificate-demo.html** - Tester l'interface

---

## 🆘 Problèmes?

### Table manquante
```bash
php assets/database/init_mysql_db.php
```

### Logo invisible
Vérifier: `assets/images/logos/certif_logo.png`

### Tests complets
```bash
php test-certificates.php
```

---

## 📊 Vérification finale

Tous les fichiers créés:
```
✅ 5 fichiers PHP (endpoints + librairie)
✅ 3 fichiers BD (table + migrations)
✅ 1 librairie JavaScript
✅ 5 fichiers documentation
✅ 2 fichiers test/démo
```

---

## 🎓 Ce que fait le système

| Feature | Détail |
|---|---|
| **Automatique** | Création à 98%+ |
| **ID unique** | CERT-xxx-xxxxxx |
| **Téléchargement** | HTML imprimable |
| **Vérification** | ID validable |
| **Sécurisé** | Lié à user_id |
| **Permanent** | Ne disparaît pas |

---

## ✨ Résultat final

```
Certificat généré automatiquement
         ↓
ID unique créé (CERT-xxx)
         ↓
Stocké en base de données
         ↓
Téléchargeable en HTML/PDF
         ↓
Vérifiable par ID
```

---

**Statut**: ✅ Production-ready

**Suivant?** Voir DEPLOYMENT_GUIDE.md pour configuration avancée.
