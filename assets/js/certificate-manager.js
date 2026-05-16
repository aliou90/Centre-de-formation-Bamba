/**
 * certificate-manager.js
 * Gestion des certificats côté client
 */

class CertificateManager {
    constructor() {
        this.apiBaseUrl = '/';
    }

    /**
     * Vérifier les certificats disponibles pour l'utilisateur
     */
    async checkAvailableCertificates() {
        try {
            const response = await fetch(this.apiBaseUrl + 'rqt_certificate_check.php');
            const data = await response.json();
            
            if (data.status === 'ok') {
                return data.certificates;
            } else {
                console.error('Erreur lors de la vérification des certificats:', data.message);
                return [];
            }
        } catch (error) {
            console.error('Erreur réseau:', error);
            return [];
        }
    }

    /**
     * Télécharger un certificat
     */
    downloadCertificate(certificateId, format = 'html') {
        if (format === 'html') {
            const url = `${this.apiBaseUrl}rqt_certificate_download.php?certificate_id=${encodeURIComponent(certificateId)}&mode=html`;
            // Ouvrir dans un nouvel onglet pour impression/PDF
            window.open(url, '_blank');
        } else if (format === 'pdf') {
            const url = `${this.apiBaseUrl}rqt_certificate_generate_pdf.php?certificate_id=${encodeURIComponent(certificateId)}`;
            const link = document.createElement('a');
            link.href = url;
            link.download = `certificat_${certificateId}.pdf`;
            link.click();
        }
    }

    /**
     * Générer un PDF avec TCPDF (si disponible)
     */
    generatePdf(certificateId) {
        const url = `${this.apiBaseUrl}rqt_certificate_generate_pdf.php?certificate_id=${encodeURIComponent(certificateId)}`;
        const link = document.createElement('a');
        link.href = url;
        link.download = `certificat_${certificateId}.pdf`;
        link.click();
    }

    /**
     * Vérifier/valider un ID de certificat
     */
    async verifyCertificateId(certificateId) {
        try {
            const response = await fetch(this.apiBaseUrl + 'rqt_certificate_verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    certificate_id: certificateId
                })
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors de la vérification:', error);
            return { status: 'error', message: error.message };
        }
    }

    /**
     * Afficher un modal de vérification d'ID de certificat
     */
    showVerificationModal() {
        const modal = document.createElement('div');
        modal.className = 'certificate-verification-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>🔍 Vérifier un certificat</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Entrez l'ID du certificat à vérifier:</p>
                    <input type="text" id="cert-id-input" placeholder="Ex: CERT-1715779200-A7K9M2" class="cert-id-input">
                    <button id="verify-btn" class="btn btn-primary">Vérifier</button>
                    <div id="verify-result" class="verify-result"></div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Event listeners
        const closeBtn = modal.querySelector('.close-btn');
        const verifyBtn = modal.querySelector('#verify-btn');
        const certIdInput = modal.querySelector('#cert-id-input');

        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        verifyBtn.addEventListener('click', async () => {
            const certId = certIdInput.value.trim();
            if (!certId) {
                alert('Veuillez entrer un ID de certificat');
                return;
            }

            const result = await this.verifyCertificateId(certId);
            const resultDiv = modal.querySelector('#verify-result');

            if (result.status === 'valid') {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <strong>✅ Certificat valide!</strong><br>
                        <strong>Apprenant:</strong> ${result.user_name}<br>
                        <strong>Cours:</strong> ${result.book_title}<br>
                        <strong>Date:</strong> ${new Date(result.completion_date).toLocaleDateString('fr-FR')}<br>
                        <strong>Progression:</strong> ${result.progression}%
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-error">
                        <strong>❌ ${result.message}</strong>
                    </div>
                `;
            }
        });

        // Écouter Entrée sur l'input
        certIdInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                verifyBtn.click();
            }
        });

        // Focus sur l'input
        certIdInput.focus();
    }

    /**
     * Ajouter un bouton de certificat à un élément de cours
     */
    addCertificateButton(container, bookTitle, certificateId) {
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'certificate-button-container';
        buttonContainer.innerHTML = `
            <button class="btn btn-success certificate-btn" title="Télécharger votre certificat">
                🎓 Télécharger certificat
            </button>
        `;

        const button = buttonContainer.querySelector('.certificate-btn');
        button.addEventListener('click', () => {
            this.downloadCertificate(certificateId, 'html');
        });

        container.appendChild(buttonContainer);
    }

    /**
     * Initialiser les certificats pour tous les cours complétés
     */
    async initializeCertificates() {
        const certificates = await this.checkAvailableCertificates();
        
        if (certificates.length === 0) {
            return;
        }

        certificates.forEach(cert => {
            if (cert.has_certificate && cert.progression >= 100) {
                // Ajouter le bouton au bon endroit dans l'interface
                const bookElement = document.querySelector(`[data-book="${cert.book_title}"]`);
                if (bookElement) {
                    this.addCertificateButton(bookElement, cert.book_title, cert.certificate_id);
                }
            }
        });
    }
}

// Exporter pour utilisation
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CertificateManager;
}

window.CertificateManager = CertificateManager;

// Styles CSS pour les modales et boutons
const styles = `
<style>
    .certificate-button-container {
        margin-top: 10px;
    }

    .certificate-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .certificate-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .certificate-btn:active {
        transform: translateY(0);
    }

    .certificate-verification-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .modal-content {
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        width: 90%;
        max-width: 500px;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 28px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close-btn:hover {
        opacity: 0.8;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-body p {
        margin: 0 0 15px 0;
        color: #333;
    }

    .cert-id-input {
        width: 100%;
        padding: 10px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        margin-bottom: 10px;
        box-sizing: border-box;
        font-family: monospace;
    }

    .cert-id-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    #verify-btn {
        width: 100%;
        padding: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    #verify-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .verify-result {
        margin-top: 15px;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        line-height: 1.6;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert strong {
        display: block;
        margin-bottom: 10px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        border: none;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-success {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
    }
</style>
`;

// Injecter les styles dans la page
document.head.insertAdjacentHTML('beforeend', styles);

// Initialiser automatiquement si l'utilisateur est connecté
document.addEventListener('DOMContentLoaded', () => {
    const certManager = new CertificateManager();
    certManager.initializeCertificates();
    
    // Exposer globally pour utilisation manuelle
    window.certManager = certManager;
});
