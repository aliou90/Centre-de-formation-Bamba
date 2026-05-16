# 🎓 GUIDE DE DÉPLOIEMENT - Système de Certificats

## 📋 Prérequis

- PHP 7.0+
- MySQL ou SQLite
- Logo du certificat: `assets/images/logos/certif_logo.png` ✅

---

## 🚀 Étapes d'installation

### 1️⃣ Migrer la base de données

#### Avec MySQL:
```bash
php assets/database/init_mysql_db.php
```

#### Avec SQLite:
```bash
php assets/database/init_db.php
```

**Résultat attendu:**
```
✅ Base de données initialisée avec succès.
```

**Ou si vous avez accès à PHPMyAdmin:**
- Exécuter le SQL contenu dans `init_mysql_db.php` pour créer la table `certificates`

---

### 2️⃣ Vérifier l'installation (optionnel)

```bash
php test-certificates.php
```

Ce script teste:
- ✅ Connexion à la base de données
- ✅ Présence de toutes les tables
- ✅ Classe `CertificateManager`
- ✅ Génération d'IDs de certificat
- ✅ Création de certificat
- ✅ Récupération de certificat
- ✅ Vérification d'ID
- ✅ Nettoyage des données

---

### 3️⃣ Intégrer le JavaScript frontend

#### Option A: Charger depuis la page HTML
```html
<!DOCTYPE html>
<html>
<head>
    <title>Bamba Formation</title>
</head>
<body>
    <!-- Votre contenu -->
    
    <!-- Charger le gestionnaire de certificats -->
    <script src="assets/js/certificate-manager.js"></script>
</body>
</html>
```

#### Option B: Utilisation manuelle
```javascript
// Créer une instance
const certManager = new CertificateManager();

// Initialiser
await certManager.initializeCertificates();

// Afficher le modal de vérification
certManager.showVerificationModal();
```

---

### 4️⃣ (Optionnel) Installer TCPDF pour PDFs natifs

Si vous voulez générer des PDFs directs au lieu d'HTML imprimables:

#### Avec Composer:
```bash
composer require tecnickcom/tcpdf
```

#### Manuellement:
1. Télécharger depuis: https://tcpdf.org/
2. Décompresser dans: `assets/TCPDF/`
3. Vérifier que le fichier existe: `assets/TCPDF/include/tcpdf.php`

**Si TCPDF n'est pas installé:** Le système utilise par défaut le mode HTML imprimable ✅

---

## 🧪 Tests

### Test complet du système
```bash
php test-certificates.php
```

### Vérifier une page manuelle
Accédez à: `http://localhost/formation_bamba/certificate-demo.html`

Cette page inclut:
- ✅ Information sur le système
- ✅ Tests des endpoints
- ✅ Modal de vérification d'ID
- ✅ Exemples de code

---

## 📱 Utilisation

### Flux utilisateur

1. **Utilisateur apprend un cours**
   - Navigue dans les pages
   - Écoute le contenu
   - La progression augmente

2. **À 98% de progression**
   - Certificat créé automatiquement
   - Notification affichée
   - ID généré: `CERT-{timestamp}-{random}`

3. **Utilisateur télécharge**
   - Clique "Télécharger certificat"
   - HTML s'ouvre dans nouvel onglet
   - Imprime ou convertit en PDF

4. **Vérification du certificat**
   - Quelqu'un vérifie l'ID
   - Clic sur "Vérifier certificat"
   - Affiche les infos du certificat

---

## 📊 Endpoints API

### 1. Vérifier certificats
```bash
curl http://localhost/formation_bamba/rqt_certificate_check.php
```

**Réponse:**
```json
{
    "status": "ok",
    "certificates": [
        {
            "book_title": "Quran",
            "progression": 100,
            "has_certificate": true,
            "certificate_id": "CERT-1715779200-A7K9M2",
            "completion_date": "2024-05-15 14:00:00"
        }
    ]
}
```

### 2. Vérifier un ID
```bash
curl -X POST http://localhost/formation_bamba/rqt_certificate_verify.php \
  -H "Content-Type: application/json" \
  -d '{"certificate_id": "CERT-1715779200-A7K9M2"}'
```

**Réponse:**
```json
{
    "status": "valid",
    "user_name": "Jean Dupont",
    "book_title": "Quran",
    "completion_date": "2024-05-15 14:00:00"
}
```

### 3. Télécharger (HTML)
```
http://localhost/formation_bamba/rqt_certificate_download.php?certificate_id=CERT-xxx&mode=html
```

### 4. Générer PDF (TCPDF)
```
http://localhost/formation_bamba/rqt_certificate_generate_pdf.php?certificate_id=CERT-xxx
```

---

## 🎨 Personnalisation

### Modifier le seuil de certificat

Fichier: `rqt_user_book_progression_update.php` (ligne ~75)

**Avant (98%+):**
```php
if ($oldProg < 98 && $progression >= 98 && !CertificateManager::hasCertificate($db, $user_id, $title)) {
```

**Pour 100% uniquement:**
```php
if ($oldProg < 100 && $progression >= 100 && !CertificateManager::hasCertificate($db, $user_id, $title)) {
```

### Personnaliser le design

Fichier: `rqt_certificate_download.php` ou `rqt_certificate_generate_pdf.php`

Modifier les styles CSS ou le texte des certificats.

---

## 🐛 Dépannage

### Problème: Table certificates manquante
**Solution:** Exécuter `php assets/database/init_mysql_db.php` ou `init_db.php`

### Problème: Logo ne s'affiche pas
**Solution:** Vérifier que `assets/images/logos/certif_logo.png` existe

### Problème: Certificat ne se crée pas
**Solution:** 
- Vérifier que progression >= 98%
- Vérifier que l'utilisateur est connecté
- Vérifier les logs PHP

### Problème: PDF génération lente
**Solution:** 
- TCPDF peut être lent la première fois
- Utiliser mode HTML et imprimer en PDF depuis navigateur
- Ou installer TCPDF via Composer

---

## 📂 Structure des fichiers

```
bamba_formation/
├── 📁 assets/
│   ├── 📁 images/
│   │   └── 📁 logos/
│   │       └── certif_logo.png ✅
│   ├── 📁 js/
│   │   └── certificate-manager.js ✅
│   ├── 📁 database/
│   │   ├── init_mysql_db.php ✅
│   │   └── init_db.php ✅
│   └── 📁 TCPDF/ (optionnel)
├── lib_certificate.php ✅
├── rqt_certificate_check.php ✅
├── rqt_certificate_verify.php ✅
├── rqt_certificate_download.php ✅
├── rqt_certificate_generate_pdf.php ✅
├── rqt_user_book_progression_update.php ✅
├── test-certificates.php ✅
├── certificate-demo.html ✅
├── CERTIFICATES_GUIDE.md
├── INTEGRATION_SUMMARY.md
└── README.md (ce fichier)
```

---

## 🔒 Sécurité

- ✅ Session user requise sur chaque endpoint
- ✅ Certificats liés à user_id
- ✅ ID unique et aléatoire
- ✅ Contrainte unique (user_id, book_title) en BD
- ✅ Validation systématique des IDs

---

## 📈 Statistiques

Une fois en production:

```sql
-- Nombre total de certificats émis
SELECT COUNT(*) FROM certificates;

-- Certificats par utilisateur
SELECT user_id, COUNT(*) as nb_certificats FROM certificates GROUP BY user_id;

-- Certificats par cours
SELECT book_title, COUNT(*) as nb_certificats FROM certificates GROUP BY book_title;

-- Taux de réussite (98%+)
SELECT book_title, COUNT(*) as nb_certified FROM certificates GROUP BY book_title ORDER BY nb_certified DESC;
```

---

## ✨ Fonctionnalités futures (optionnel)

- [ ] Afficher "Mes certificats" (page dédiée)
- [ ] Partage social de certificats
- [ ] QR code sur certificat
- [ ] Certificat numérique signé
- [ ] Template certificat personnalisé
- [ ] Analytics certificats
- [ ] Notification email de certificat
- [ ] Badge dans le profil

---

## 📞 Support

Pour des questions sur l'implémentation:
- Voir `CERTIFICATES_GUIDE.md` - Documentation complète
- Voir `INTEGRATION_SUMMARY.md` - Résumé technique
- Exécuter `test-certificates.php` - Tests diagnostic
- Accéder à `certificate-demo.html` - Interface de test

---

## 🎯 Résumé rapide

| Étape | Commande | Résultat |
|---|---|---|
| 1 | `php assets/database/init_mysql_db.php` | Tables créées |
| 2 | Ajouter `<script src="assets/js/certificate-manager.js"></script>` | JS chargé |
| 3 | Utilisateur progresse à 98% | Certificat créé |
| 4 | Utilisateur télécharge | PDF/HTML téléchargé |

---

**Installation terminée! 🎉**

Le système est maintenant prêt à fonctionner.

Consultez `certificate-demo.html` pour tester les fonctionnalités.

---

**Version**: 1.0  
**Dernière mise à jour**: 15 mai 2024  
**Status**: ✅ Production-ready
