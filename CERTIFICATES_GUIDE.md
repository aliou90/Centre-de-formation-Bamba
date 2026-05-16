# 🎓 Intégration des Certificats - Guide Complet

## 📋 Vue d'ensemble

Système de certificats automatique pour les apprenants qui complètent un cours à 98-100%.

### Caractéristiques:
- ✅ Création automatique de certificat à 98%+
- ✅ ID de certificat unique (CERT-timestamp-random)
- ✅ Téléchargement en HTML (format imprimable/convertible PDF)
- ✅ Génération PDF avancée avec TCPDF (optionnel)
- ✅ Vérification d'ID de certificat
- ✅ Logo en haut du certificat
- ✅ Informations: nom apprenant, cours (latin + arabe), date, ID certificat

---

## 🗄️ Base de données

### Table `certificates`

```sql
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    certificate_id VARCHAR(50) NOT NULL UNIQUE,
    completion_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    progression INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cert (user_id, book_title)
) ENGINE=InnoDB;
```

---

## 🔌 Endpoints PHP

### 1. **Vérifier certificats disponibles**
```
GET /rqt_certificate_check.php
```

**Réponse JSON:**
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

### 2. **Vérifier/Valider un ID de certificat**
```
POST /rqt_certificate_verify.php
Content-Type: application/json

{
    "certificate_id": "CERT-1715779200-A7K9M2"
}
```

**Réponse JSON (valide):**
```json
{
    "status": "valid",
    "message": "Certificat valide",
    "certificate_id": "CERT-1715779200-A7K9M2",
    "user_name": "Jean Dupont",
    "book_title": "Quran",
    "completion_date": "2024-05-15 14:00:00",
    "progression": 100
}
```

**Réponse JSON (invalide):**
```json
{
    "status": "invalid",
    "message": "ID de certificat invalide ou inexistant"
}
```

### 3. **Télécharger certificat (HTML/Imprimable)**
```
GET /rqt_certificate_download.php?certificate_id=CERT-xxx&mode=html
```

- Retourne un HTML formaté
- Prêt pour impression ou conversion PDF
- Inclut le logo depuis `assets/images/logos/certif_logo.png`
- Style CSS portrait adapté

### 4. **Télécharger certificat (PDF avec TCPDF)**
```
GET /rqt_certificate_generate_pdf.php?certificate_id=CERT-xxx
```

- Génère un PDF direct si TCPDF est installé
- Sinon, redirige vers la version HTML

---

## 📁 Fichiers PHP créés

| Fichier | Description |
|---------|-------------|
| `lib_certificate.php` | Classe utilitaire `CertificateManager` |
| `rqt_certificate_check.php` | Vérifier les certificats disponibles |
| `rqt_certificate_verify.php` | Valider un ID de certificat |
| `rqt_certificate_download.php` | Télécharger certificat en HTML |
| `rqt_certificate_generate_pdf.php` | Générer PDF avec TCPDF (optionnel) |

---

## 🔄 Flux d'automatisation

1. Utilisateur progresse dans un cours
2. À 98%+, appel à `rqt_user_book_progression_update.php`
3. Création automatique d'un certificat
4. Réponse inclut `certificate` object avec ID et message
5. Interface affiche bouton "Télécharger certificat"

### Exemple réponse progression:
```json
{
    "status": "ok",
    "message": "Progression mise à jour (98%) !",
    "congrat": "🎉 90% ! Tu touches au but !",
    "certificate": {
        "status": "created",
        "certificate_id": "CERT-1715779200-A7K9M2",
        "message": "🎓 Certificat créé ! Vous pouvez maintenant le télécharger."
    }
}
```

---

## 🚀 Installation & Configuration

### Prérequis
- PHP 7.0+
- Base de données (MySQL/SQLite)
- Logo du certificat: `assets/images/logos/certif_logo.png` ✓

### Installation des migrations BD

```bash
# Pour MySQL
php assets/database/init_mysql_db.php

# Pour SQLite
php assets/database/init_db.php
```

### Installation TCPDF (optionnel, pour PDFs avancés)

```bash
# Avec Composer
composer require tecnickcom/tcpdf

# Ou manuellement: 
# Télécharger depuis https://tcpdf.org/
# Décompresser dans assets/TCPDF/
```

---

## 💻 Intégration Frontend

### Exemple JavaScript

```javascript
// Vérifier les certificats disponibles
async function checkCertificates() {
    const response = await fetch('/rqt_certificate_check.php');
    const data = await response.json();
    
    if (data.status === 'ok') {
        data.certificates.forEach(cert => {
            if (cert.has_certificate) {
                addCertificateButton(cert.book_title, cert.certificate_id);
            }
        });
    }
}

// Télécharger le certificat
async function downloadCertificate(certificateId) {
    const url = `/rqt_certificate_download.php?certificate_id=${certificateId}&mode=html`;
    window.open(url, '_blank');
}

// Vérifier un ID de certificat
async function verifyCertificateId(certId) {
    const response = await fetch('/rqt_certificate_verify.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ certificate_id: certId })
    });
    
    const data = await response.json();
    if (data.status === 'valid') {
        console.log(`Certificat valide pour ${data.user_name}`);
    } else {
        console.log('Certificat invalide');
    }
}
```

---

## 🎨 Design du certificat

Le certificat inclut:
- ✅ Logo en haut (assets/images/logos/certif_logo.png)
- ✅ Titre "CERTIFICAT" + "DE RÉUSSITE"
- ✅ Message "Félicitations! Vous avez terminé le cours"
- ✅ Nom du cours en latin
- ✅ Nom du cours en arabe
- ✅ Date de réussite (bottom-left)
- ✅ ID du certificat (bottom-right)
- ✅ Design professionnel A4 portrait

---

## 📊 Métriques

- Progression minimum pour certificat: **98%**
- Format ID certificat: `CERT-{timestamp}-{random}`
- Exemple: `CERT-1715779200-A7K9M2`

---

## ⚙️ Configuration avancée

### Modifier le seuil de certificat

Dans `rqt_user_book_progression_update.php`, ligne avec:
```php
if ($oldProg < 98 && $progression >= 98 && !CertificateManager::hasCertificate($db, $user_id, $title)) {
```

Remplacer `98` par le seuil souhaité (ex: 100 pour 100% uniquement).

### Personnaliser le design

Éditer le HTML/CSS dans `rqt_certificate_download.php` ou `rqt_certificate_generate_pdf.php`.

---

## 🔐 Sécurité

- ✅ Certificats liés à l'utilisateur (user_id)
- ✅ Vérification de session sur chaque endpoint
- ✅ ID de certificat unique et aléatoire
- ✅ Impossible de générer deux certificats pour le même cours

---

## 📝 Notes

- Les certificats sont générés **automatiquement** à 98%+
- L'utilisateur ne peut pas créer de certificat manuellement
- Un utilisateur ne peut avoir qu'**un seul** certificat par cours
- Les certificats restent **permanents** même si la progression baisse

---

## 🆘 Dépannage

### Problème: "Certificat non trouvé"
- Vérifier que progression >= 98%
- Vérifier que l'utilisateur est connecté
- Vérifier l'ID du certificat

### Problème: Logo ne s'affiche pas
- Vérifier que `assets/images/logos/certif_logo.png` existe
- Vérifier les permissions du fichier

### Problème: PDF ne génère pas
- TCPDF n'est pas requis
- Utiliser `mode=html` pour imprimer en PDF depuis le navigateur
- Installer TCPDF pour génération native si souhaité

---

**Version**: 1.0  
**Dernière mise à jour**: 2024-05-15
