/**
 * Intégration des Certificats avec la plateforme Bamba
 * Gère l'affichage des boutons télécharger et le modal des certificats
 */

// Attendre que le CertificateManager soit disponible
if (typeof CertificateManager === 'undefined') {
    console.warn('CertificateManager non trouvé. Assurez-vous que certificate-manager.js est chargé');
} else {
    const certManager = new CertificateManager();
    window.certManager = certManager;

    function ensureDownloadButton(listItem) {
        let downloadBtn = listItem.querySelector('.download-badge');
        if (downloadBtn) {
            return downloadBtn;
        }

        const controlGroup = listItem.querySelector('.book-control-group');
        const removeBadge = controlGroup ? controlGroup.querySelector('.remove-badge') : null;

        if (!controlGroup) {
            return null;
        }

        downloadBtn = document.createElement('button');
        downloadBtn.type = 'button';
        downloadBtn.className = 'book-badge download-badge';
        downloadBtn.title = 'Voir le certificat';
        downloadBtn.textContent = 'Certificat';
        downloadBtn.style.display = 'none';
        downloadBtn.onclick = function(e) {
            e.stopPropagation();
        };

        if (removeBadge) {
            controlGroup.insertBefore(downloadBtn, removeBadge);
        } else {
            controlGroup.appendChild(downloadBtn);
        }

        return downloadBtn;
    }
    
    /**
     * Afficher le bouton de téléchargement pour un livre spécifique
     * @param {string} bookTitle - Titre du livre
     * @param {string} certificateId - ID du certificat
     */
    function showDownloadButton(bookTitle, certificateId) {
        console.log(`🎓 Affichage du bouton pour: ${bookTitle} (${certificateId})`);
        
        const bookItems = document.querySelectorAll('.book-item');
        if (bookItems.length === 0) {
            console.warn(`⚠️ Aucun livre visible pour afficher le bouton`);
            return;
        }
        
        bookItems.forEach(bookItem => {
            const currentBookTitle = bookItem.getAttribute('data-latin');
            
            if (currentBookTitle === bookTitle) {
                const listItem = bookItem.closest('li');
                if (listItem) {
                    const downloadBtn = ensureDownloadButton(listItem);
                    if (downloadBtn) {
                        // Afficher le bouton
                        downloadBtn.style.display = 'inline-block';
                        downloadBtn.setAttribute('data-cert-id', certificateId);
                        downloadBtn.setAttribute('data-book-title', bookTitle);
                        
                        // Ajouter l'event listener
                        downloadBtn.onclick = function(e) {
                            e.stopPropagation();
                            const certId = this.getAttribute('data-cert-id');
                            console.log(`📥 Téléchargement du certificat: ${certId}`);
                            certManager.downloadCertificate(certId, 'html');
                        };
                        
                        // Animation
                        downloadBtn.style.animation = 'fadeIn 0.5s ease-in-out';
                        console.log(`✅ Bouton affiché pour "${bookTitle}"`);
                    }
                } else {
                    console.warn(`⚠️ Élément <li> non trouvé pour ${bookTitle}`);
                }
            }
        });
    }
    
    /**
     * Mettre à jour les boutons Télécharger pour les livres avec certificat
     */
    function updateCertificateButtons() {
        certManager.checkAvailableCertificates()
            .then(certificates => {
                // Créer une map certificat par titre du livre
                const certMap = {};
                certificates.forEach(cert => {
                    if (cert.book_title) {
                        certMap[cert.book_title] = {
                            id: cert.certificate_id,
                            date: cert.completion_date,
                            arabic: cert.course_name_arabic || ''
                        };
                    }
                });
                
                // Mettre à jour les badges de chaque livre
                const bookItems = document.querySelectorAll('.book-item');
                if (bookItems.length === 0) {
                    console.log('ℹ️ Aucun livre visible pour le moment');
                    return;
                }
                
                bookItems.forEach(bookItem => {
                    const bookTitle = bookItem.getAttribute('data-latin');
                    const listItem = bookItem.closest('li');
                    if (!listItem) return;
                    
                    if (certMap[bookTitle]) {
                        const downloadBtn = ensureDownloadButton(listItem);
                        if (!downloadBtn) {
                            return;
                        }

                        // Afficher le bouton et ajouter l'event listener
                        downloadBtn.style.display = 'inline-block';
                        downloadBtn.setAttribute('data-cert-id', certMap[bookTitle].id);
                        downloadBtn.setAttribute('data-book-title', bookTitle);
                        
                        // Définir l'action du bouton
                        downloadBtn.onclick = function(e) {
                            e.stopPropagation();
                            const certId = this.getAttribute('data-cert-id');
                            const bookTitle = this.getAttribute('data-book-title');
                            console.log(`📥 Téléchargement du certificat: ${certId} (${bookTitle})`);
                            certManager.downloadCertificate(certId, 'html');
                        };
                    } else {
                        const downloadBtn = listItem.querySelector('.download-badge');
                        if (!downloadBtn) {
                            return;
                        }

                        // Masquer le bouton s'il n'y a pas de certificat
                        downloadBtn.style.display = 'none';
                        downloadBtn.removeAttribute('data-cert-id');
                        downloadBtn.removeAttribute('data-book-title');
                        downloadBtn.onclick = null;
                    }
                });
                
                console.log(`✅ ${Object.keys(certMap).length} certificat(s) trouvé(s) et affiché(s)`);
            })
            .catch(err => {
                console.error('❌ Erreur lors de la récupération des certificats:', err);
            });
    }
    
    /**
     * Charger et afficher les certificats dans le modal
     */
    async function loadCertificatesModal() {
        const loadingDiv = document.getElementById('certificates-loading');
        const listDiv = document.getElementById('certificates-list');
        const emptyDiv = document.getElementById('certificates-empty');
        const errorDiv = document.getElementById('certificates-error');
        
        if (!loadingDiv || !listDiv || !emptyDiv || !errorDiv) {
            console.error('❌ Éléments du modal non trouvés');
            return;
        }
        
        // Réinitialiser l'affichage
        loadingDiv.style.display = 'block';
        listDiv.style.display = 'none';
        emptyDiv.style.display = 'none';
        errorDiv.style.display = 'none';
        errorDiv.innerHTML = '';
        
        try {
            // Charger les certificats
            const certificates = await certManager.checkAvailableCertificates();
            
            if (!certificates || certificates.length === 0) {
                loadingDiv.style.display = 'none';
                emptyDiv.style.display = 'block';
                return;
            }
            
            // Créer le HTML pour les certificats
            let certificatesHTML = '<div class="row g-3">';
            
            certificates.forEach(cert => {
                const courseNameLatin = cert.book_title || 'Cours inconnu';
                const courseNameArabic = cert.course_name_arabic || '';
                const completionDate = new Date(cert.completion_date).toLocaleDateString('fr-FR');
                const certId = cert.certificate_id;
                
                certificatesHTML += `
                    <div class="col-md-6">
                        <div class="card h-100" style="border-left: 4px solid #5A5AFF; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s;">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #5A5AFF; margin-bottom: 8px;">
                                    <i class="fas fa-certificate" style="color: #FFD700;"></i> ${escapeHtml(courseNameLatin)}
                                </h6>
                                ${courseNameArabic ? `
                                    <p class="card-text" style="font-size: 0.9rem; direction: rtl; text-align: right; color: #333; margin: 8px 0;">
                                        ${escapeHtml(courseNameArabic)}
                                    </p>
                                ` : ''}
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-calendar"></i> ${completionDate}
                                </small>
                                <small class="text-secondary d-block mb-3" style="word-break: break-all; font-family: monospace; font-size: 0.75rem;">
                                    <strong>ID:</strong> ${certId}
                                </small>
                                <div class="d-flex gap-2" style="flex-wrap: wrap;">
                                    <button class="btn btn-sm btn-outline-primary" onclick="certManager.downloadCertificate('${certId}', 'html')" title="Voir le certificat en HTML">
                                        <i class="fas fa-eye"></i> Voir
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="certManager.downloadCertificate('${certId}', 'pdf')" title="Télécharger en PDF">
                                        <i class="fas fa-download"></i> PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            certificatesHTML += '</div>';
            
            loadingDiv.style.display = 'none';
            listDiv.innerHTML = certificatesHTML;
            listDiv.style.display = 'block';
            
            console.log(`✅ ${certificates.length} certificat(s) affiché(s) dans le modal`);
        } catch (error) {
            console.error('❌ Erreur lors du chargement des certificats:', error);
            loadingDiv.style.display = 'none';
            errorDiv.style.display = 'block';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i> <strong>Erreur:</strong> 
                ${error.message || 'Impossible de charger les certificats. Veuillez réessayer.'}
            `;
        }
    }
    
    /**
     * Échapper les caractères HTML pour éviter les injections XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    /**
     * Initialiser les event listeners
     */
    function initEventListeners() {
        const viewBadgesBtn = document.getElementById('view-badges-btn');
        
        if (viewBadgesBtn) {
            viewBadgesBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Ouverture du modal des certificats...');
                loadCertificatesModal();
            });
        } else {
            console.warn('⚠️ Bouton "view-badges-btn" non trouvé');
        }
    }
    
    /**
     * Rafraîchir les certificats à intervalle régulier
     */
    function autoRefreshCertificates(intervalMs = 30000) {
        // Rafraîchir au chargement initial
        updateCertificateButtons();
        
        // Puis rafraîchir tous les 30 secondes
        setInterval(updateCertificateButtons, intervalMs);
    }
    
    /**
     * Point d'entrée principal
     */
    function initializeCertificatesIntegration() {
        console.log('🎓 Initialisation de l\'intégration des certificats...');
        
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    initEventListeners();
                    autoRefreshCertificates();
                }, 500);
            });
        } else {
            setTimeout(() => {
                initEventListeners();
                autoRefreshCertificates();
            }, 500);
        }
    }
    
    // Initialiser au chargement du script
    initializeCertificatesIntegration();
    
    // Exposer les fonctions globalement pour les boutons inline
    window.updateCertificateButtons = updateCertificateButtons;
    window.loadCertificatesModal = loadCertificatesModal;
}
