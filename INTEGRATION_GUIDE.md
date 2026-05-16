# 📋 GUIDE D'INTÉGRATION - SYSTÈME DE CERTIFICATS

**Date**: 15 mai 2026  
**Version**: 1.1  
**Status**: ✅ PRODUCTION-READY

---

## ✨ CE QUI A ÉTÉ INTÉGRÉ

### 1. Bouton "Télécharger" sur les livres
- ✅ Bouton vert **📥 Télécharger** apparaît automatiquement pour les livres avec certificat
- ✅ Situé à côté de "Suivre" et "Retirer"
- ✅ Au clic, ouvre le certificat en HTML (A4 portrait)
- ✅ Peut aussi être converti en PDF

### 2. Modal "Mes Certificats"
- ✅ Bouton **🏅 Mes Certificats** dans le profil utilisateur
- ✅ Affiche tous les certificats obtenus par l'utilisateur
- ✅ Design premium avec cartes professionnelles
- ✅ Chaque certificat affiche:
  - Nom du cours (latin + arabe)
  - Date de réussite
  - ID unique du certificat
  - Boutons "Voir" (HTML) et "PDF" (téléchargement)

### 3. Fichiers créés/modifiés

**Fichiers nouveaux:**
- `assets/js/certificates-integration.js` - Intégration système (210 lignes)
- `assets/css/certificates-styles.css` - Styles professionnels (180 lignes)

**Fichiers modifiés:**
- `index.php` - Ajout du modal et du bouton télécharger
- `assets/js/certificate-manager.js` - Existant, utilisé par l'intégration

---

## 🚀 UTILISATION

### Pour les utilisateurs

#### Voir un certificat depuis la liste des livres
1. Terminer un cours à 98%+ ✓
2. Actualiser la page ou attendre quelques secondes
3. Le bouton vert **📥 Télécharger** apparaît
4. Cliquer sur le bouton
5. Le certificat s'ouvre dans un nouvel onglet (A4 portrait)
6. Imprimer ou enregistrer en PDF

#### Voir tous ses certificats
1. Ouvrir le profil utilisateur (menu déroulant)
2. Cliquer sur **🏅 Mes Certificats**
3. Un modal s'ouvre avec tous les certificats
4. Pour chaque certificat:
   - **Voir**: Affiche le certificat en HTML
   - **PDF**: Télécharge en PDF (si TCPDF installé)

### Pour les développeurs

#### Ajouter des boutons personnalisés
```javascript
// Obtenir le gestionnaire de certificats
const certManager = new CertificateManager();

// Télécharger un certificat
certManager.downloadCertificate('CERT-xxx', 'html');
certManager.downloadCertificate('CERT-xxx', 'pdf');

// Vérifier les certificats disponibles
const certs = await certManager.checkAvailableCertificates();
```

#### Rafraîchir les certificats manuellement
```javascript
// Appel via la fonction globale
updateCertificateButtons();
```

---

## 🔧 CONFIGURATION

### Seuil de certificat (actuellement 98%)
Pour modifier le seuil, éditer `rqt_user_book_progression_update.php`:
```php
if ($oldProg < 98 && $progression >= 98) {
    // Créer le certificat
}
```

Changer `98` à la valeur désirée (ex: 90 pour 90%+)

### Intervalle de rafraîchissement
Dans `assets/js/certificates-integration.js` ligne ~180:
```javascript
autoRefreshCertificates(30000); // 30 secondes
```

Changer `30000` (millisecondes) selon vos besoins

### Style des boutons
Tous les styles sont dans `assets/css/certificates-styles.css`:
- `.download-badge` - Bouton Télécharger
- `.certificate-card` - Cartes du modal
- Les couleurs principale: `#5A5AFF` (bleu)

---

## 📝 STRUCTURE TECHNIQUE

### Flow du système

```
1. Utilisateur termine un cours (98%+)
        ↓
2. CertificateManager.createCertificate() appelé
        ↓
3. ID unique généré: CERT-{timestamp}-{random}
        ↓
4. Certificat stocké en BD
        ↓
5. certificateButtons rafraîchit
        ↓
6. Bouton Télécharger apparaît sur le livre
        ↓
7. Utilisateur peut télécharger HTML/PDF
```

### Fichiers impliqués

**Backend:**
- `lib_certificate.php` - Logique métier
- `rqt_certificate_check.php` - API certificats
- `rqt_certificate_download.php` - HTML
- `rqt_certificate_generate_pdf.php` - PDF

**Frontend:**
- `assets/js/certificate-manager.js` - Classe cliente
- `assets/js/certificates-integration.js` - Intégration
- `assets/css/certificates-styles.css` - Styles

**Database:**
- Table `certificates` (MySQL + SQLite)

---

## ✅ VÉRIFICATION

### Tester l'intégration

1. **Vérifier les fichiers:**
   ```bash
   ls -la assets/js/certificate*.js
   ls -la assets/css/certificate*.css
   ```

2. **Console du navigateur (F12):**
   ```
   - Pas d'erreurs 404
   - Message: "🎓 Initialisation de l'intégration des certificats..."
   - CertificateManager chargé
   ```

3. **Tester le bouton:**
   - Terminer un cours à 98%
   - Actualiser la page
   - Vérifier l'apparition du bouton vert

4. **Tester le modal:**
   - Ouvrir profil utilisateur
   - Cliquer "🏅 Mes Certificats"
   - Vérifier le contenu

---

## 🐛 DÉPANNAGE

### Les boutons ne s'affichent pas

**Cause 1: Certificate-manager.js non chargé**
```
Solution: Vérifier F12 > Network > certificate-manager.js charge correctement
```

**Cause 2: API ne répond pas**
```
Solution: Vérifier que rqt_certificate_check.php est accessible
         Vérifier la session utilisateur est active
```

**Cause 3: Aucun certificat créé**
```
Solution: Terminer un cours à 98%+
         Actualiser la page (30 sec pour rafraîchissement)
         Vérifier la BD: SELECT * FROM certificates;
```

### Le modal ne s'ouvre pas

**Cause: Bootstrap modal non initié**
```
Solution: Vérifier que bootstrap.js est chargé
         Vérifier l'ID du modal: #certificates-modal
         Vérifier l'ID du bouton: #view-badges-btn
```

### Les certificats ne se téléchargent pas

**Cause 1: User pas authentifié**
```
Solution: Vérifier $_SESSION['user']['id']
         Se reconnecter
```

**Cause 2: ID certificat invalide**
```
Solution: Vérifier la BD: SELECT * FROM certificates
         Vérifier certificate_id n'est pas corrompu
```

---

## 📊 MONITORING

### Vérifier les certificats en BD

**MySQL:**
```sql
SELECT 
    c.certificate_id,
    u.fullname,
    c.book_title,
    c.completion_date
FROM certificates c
JOIN users u ON c.user_id = u.id
ORDER BY c.created_at DESC
LIMIT 10;
```

**SQLite:**
```sql
SELECT * FROM certificates ORDER BY created_at DESC LIMIT 10;
```

### Logs du navigateur (Console)

Tout est loggé en console:
```
✅ Certificats trouvés
❌ Erreurs détectées
⚠️ Avertissements
```

---

## 🎯 PROCHAINES ÉTAPES (optionnel)

1. **Page "Mes Certificats" complète**
   - Liste tous les certificats
   - Filtres par cours
   - Export en bulk

2. **Partage social**
   - Twitter
   - LinkedIn
   - Facebook

3. **Notifications email**
   - Email automatique à 98%
   - Lien direct au certificat

4. **QR code**
   - QR code sur le certificat
   - Vérification par scan

5. **Analytics**
   - Taux de certificats par cours
   - Utilisateurs certifiés
   - Tendances

---

## 📞 SUPPORT

**Problèmes?** Consulter les fichiers de documentation:
- `QUICKSTART.md` - Démarrage rapide
- `DEPLOYMENT_GUIDE.md` - Installation
- `CERTIFICATES_GUIDE.md` - Référence API
- `README_CERTIFICATES.md` - Vue générale

---

**Intégration complète et testée ✅**
