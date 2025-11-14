// Formulaire connexion / Inscription / Mot de passe oublié
document.addEventListener('DOMContentLoaded', () => {
    // Mise en évidence de noms sacrés
    highlightText();
});

// Vérification et Enregistrement du Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').then(function(registration) {
      console.log('Service Worker enregistré avec succès:', registration);
    }).catch(function(error) {
      console.log('Échec de l\'enregistrement du Service Worker:', error);
    });
}

// Fonction pour afficher un message flottant
function showFloatingMessage(message, type = 'info') {
    const validTypes = ['success', 'info', 'warning', 'danger'];
    const alertClass = validTypes.includes(type) ? `alert alert-${type}` : 'alert alert-info';

    // Définir les émojis selon le type
    const emojis = {
        success: '✅',
        info: 'ℹ️',
        warning: '⚠️',
        danger: '❌'
    };
    const emoji = emojis[type] || emojis['info'];

    const duration = 3000;
    const floatingMessage = document.getElementById('floating-message');

    // Remplacer \n par <br> et ajouter l’émoji au début
    floatingMessage.innerHTML = `${emoji} ${message.replace(/\n/g, '<br>')}`;

    // Supprimer d'anciennes classes alert-* et ajouter la nouvelle
    floatingMessage.className = ''; // reset
    floatingMessage.classList.add(...alertClass.split(' '));

    floatingMessage.style.display = 'block';

    // Forcer une légère pause pour permettre la transition
    setTimeout(() => {
        floatingMessage.classList.add('show');
    }, 10);

    // Cacher après délai
    setTimeout(() => {
        floatingMessage.classList.remove('show');
        setTimeout(() => {
            floatingMessage.style.display = 'none';
        }, 500); // temps de transition
    }, duration);
}

// Afficher/cacher le menu des livres
document.getElementById('toggle-book-menu-btn').addEventListener('click', () => {
    const allBookList = document.getElementById('book-list');
    allBookList.classList.toggle('open');
});

// Fonction ouvrir/fermer le lecteur audio
const toggleBtn = document.getElementById('toggle-audio-panel');
const audioContainer = document.querySelector('.audio-navigation-container');
const arrowIcon = toggleBtn.querySelector('span');

toggleBtn.addEventListener('click', () => {
    audioContainer.classList.toggle('open');
    // Change l’icône
    arrowIcon.innerHTML = audioContainer.classList.contains('open') ? '&#9660;' : '&#9650;';
    // ↓ si ouvert, ↑ si fermé
});

// Afficher/Cacher le haeder
let lastScrollTop = 0;
const header = document.querySelector('.page-header');

window.addEventListener('scroll', () => {
    let currentScroll = window.pageYOffset || document.documentElement.scrollTop;

    if (currentScroll > lastScrollTop) {
        // Scrolling vers le bas → cacher header
        header.classList.add('hide');
    } else {
        // Scrolling vers le haut → afficher header
        header.classList.remove('hide');
    }

    lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // éviter valeurs négatives
});

// Contrôle et fermeture du menu livre au clic extérieur et non sur le lecteur
document.addEventListener('click', function (event) {
    const bookMenuContainer = document.getElementById('book-list');
    const toggleBookMenuButton = document.getElementById('toggle-book-menu-btn');
    const audioContainer = document.querySelector('.audio-navigation-container');
    const toggleAudioButton = document.getElementById('toggle-audio-panel');
    const arrowIcon = toggleAudioButton.querySelector('span');
    const btnClose = document.querySelector('.btn-close');

    const isClickedInsideMenu = bookMenuContainer.contains(event.target);
    const isClickedToggleButton = toggleBookMenuButton.contains(event.target);
    const isClickedAudioContainer = audioContainer.contains(event.target);
    const isClickedAudioToggle = toggleAudioButton.contains(event.target);
    const isClickedCloseButton = btnClose.contains(event.target);

    // Fermer le menu livre si clic en dehors du menu et pas sur le bouton fermer 
    // ni le bouton ouvrir le lecteur 
    if (!isClickedInsideMenu && !isClickedToggleButton && !isClickedCloseButton) {
        bookMenuContainer.classList.remove('open');
    }

    if (!isClickedAudioContainer && !isClickedAudioToggle) {
        audioContainer.classList.remove('open');
        arrowIcon.innerHTML = '&#9650;'; // flèche vers le haut (fermé)
    }
});

// Basculement Mode portrait/paysage
document.getElementById('toggle-orientation').addEventListener('click', () => {
    const carousel = document.getElementById('carouselExample');
    carousel.classList.toggle('portrait-mode');
});

let currentBookTitle = null;
let getedImages;
let images = null;
let audios = null;
let config = null;
let chapitres = null;
let menuActiveIndex = 0; // Indice de départ pour la langue arabe
let skipProgressionUpdate = false;
let fromMenuClic = false; // Signal de clic depuis menu page
let wavesurfer; // Référence globale à l'instance de WaveSurfer
let globalTimeInSecForChapter = null; // Variable pour stocker le temps en secondes
let carouselElement = null;
let carouselInner = null;

function loadBook(bookTitle) {
    // Livre actuel
    currentBookTitle = bookTitle; // ✅ Suivi du livre en cours

    // Initialiser manuellement le carousel s'il n'existe pas encore
    carouselElement = document.querySelector('#carouselExample');
    if (!bootstrap.Carousel.getInstance(carouselElement)) {
        new bootstrap.Carousel(carouselElement);
    }

    carouselInner = document.getElementById('carousel-images');
    carouselInner.innerHTML = '';

    if (!books[currentBookTitle]) return;

    config = books[currentBookTitle].config; // Configurations du livre depuis config/config.json
    getedImages = books[currentBookTitle].images;
    images = [...getedImages];
    if (config.lang === 'ar') {
        images.sort((a, b) => { // Tri
            const numA = parseInt(a.match(/(\d+)/)[0]);
            const numB = parseInt(b.match(/(\d+)/)[0]);
            return numA - numB;
        });
        images.reverse(); // Renversement
    }
    audios = books[currentBookTitle].audios;
    chapitres = books[currentBookTitle].chapitres; // Chapitres du livre depuis config/chapitres.json
    menuActiveIndex = 0; // Indice de départ pour la langue arabe
    skipProgressionUpdate = false;
    globalTimeInSecForChapter = null; // Variable pour stocker le temps en secondes
    fromMenuClic = false; // Signal de clic depuis menu page

    // Titre du livre
    // carouselInner.innerHTML += `
    //     <div class="carousel-item-title text-center text-bg-light">
    //         <h4 class="">${currentBookTitle}</h4>
    //         <h4 class="pb-2 mb-1">${config.nomArabe ? config.nomArabe : ''}</h4>
    //     </div>
    // `;

    // Faire défiler jusqu'au titre une fois qu'il est rendu
    setTimeout(() => {
        const titleElement = document.querySelector('.carousel-item-title');
        if (titleElement) {
            titleElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100); // Délai court pour s'assurer que l'élément est dans le DOM

    // Récupérer le nom du ficher sans extension
    const getFileNameWithoutExtension = (filePath) =>
        filePath.split('/').pop().split('.').slice(0, -1).join('.');

    // VÉRIFICATION DE CONNEXION ET DONNÉES DE L'UTILISATEUR
    async function getUserBookAndStartIndex(currentBookTitle, images, config) {
        try {
            const sessionRes = await fetch('rqt_session_check.php');
            const sessionData = await sessionRes.json();
            if (sessionData.status !== 'ok') throw new Error('Non connecté');
    
            const booksRes = await fetch('rqt_user_books_get.php');
            const userBooksData = await booksRes.json();
    
            if (userBooksData.status === 'ok') {
                const book = userBooksData.books.find(b => b.title === currentBookTitle);
                if (book && typeof book.last_page === 'number') {
                    const startIndex = (config.lang === 'ar')
                        ? images.length - book.last_page
                        : book.last_page - 1;
    
                    return { startIndex, book };
                }
            }
            // Aucun livre ou erreur → retour défaut
            const firstIndex = (config.lang === 'ar')
                ? images.length - 1 
                : 0;
            return { startIndex: firstIndex, book: null };
        } catch (err) {
            console.warn("Erreur session/livre :", err);
            const firstIndex = (config.lang === 'ar')
                ? images.length - 1 
                : 0;
            return { startIndex: firstIndex, book: null };
        }
    }

    // INSTANCIATION
    getUserBookAndStartIndex(currentBookTitle, images, config).then(({ startIndex, book }) => {
        buildCarousel(startIndex);
    });    

    // CHARGEMENT DES IMAGES ET DE L'AUDIO DE LA PAGE AFFICHÉE 
    function buildCarousel(startIndex) {
        images.forEach((img, index) => {
            // Obtenir l'index de la la page selon la langue
            const realIndex = config.lang === 'ar'
                ? images.length - 1 - index
                : index;

            // Activer la page correspondante à l'index
            const activeClass = (index === startIndex) ? 'active' : '';

            carouselInner.innerHTML += `
                <div class="carousel-item ${activeClass}">
                    <img src="${images[index]}" class="d-block w-100" alt="Page ${realIndex + 1}">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Page ${realIndex + 1}</h5>
                    </div>
                </div>
            `;
        });

        document.getElementById('pageLabel').textContent =
            `Page ${config.lang === 'ar' ? images.length - startIndex : startIndex + 1}`;

        menuActiveIndex = config.lang === 'ar' ? images.length - startIndex - 1 : startIndex;

        // S'assurer que les actions de s'effectuent après ça
        onCarouselReady(); // exécute autre logique ici

        // Calcul de l'index selon la langue (Pour recherche dans les images)
        // Pour éviter une erreur si le tableau images a été renversé
        const realIndex = config.lang === 'ar'
            ? images.length - 1 - startIndex
            : startIndex;

        // ✅ Utilisation directe de startIndex
        const firstImage = images[startIndex];

        // Recherche audio correspondant
        const firstAudio = audios.find(audio =>
            getFileNameWithoutExtension(audio) === getFileNameWithoutExtension(firstImage)
        );

        loadFirstAudio(firstAudio);

    }
    
    // Lire le premier audio ou l'audio de la page correspondante
    function loadFirstAudio(firstAudio){
        try {
            if (wavesurfer) wavesurfer.destroy();
        } catch (error) {
            console.warn("Erreur lors de la destruction de WaveSurfer :", error);
        }

        document.getElementById('current-time').textContent = '0:00';
        document.getElementById('total-time').textContent = '0:00';

        if (firstAudio) {
            initWaveSurfer(firstAudio, chapTimeInSec = null, bookConfig = config);
            globalTimeInSecForChapter = null;
        }
    }

    // Fonction : mise à jour de progression pour la première page
    function updateUserFirstProgression(book, currentBookTitle, imagesLength) {
        const lastProgression = book?.progression || 0;
        const firstProgression = Math.round((1 * 100) / imagesLength);

        if (firstProgression > lastProgression) {
            const currentPage = 1; 
            fetch('rqt_user_book_progression_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: currentBookTitle,
                    page: currentPage,
                    progression: firstProgression
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'ok') {
                    console.warn("Erreur MAJ progression:", data.message);
                } else {
                    console.log(data.message);
                    showFloatingMessage(`Bienvenue au cours ${currentBookTitle}. \n Bon apprentissage !`, "success");
                    refreshAllBookList(); // mettre à jour la liste si nécessaire
                }
            })
            .catch(err => console.error("Erreur réseau MAJ progression:", err));
        }
    }
    
    getUserBookAndStartIndex(currentBookTitle, images, config).then(({ startIndex, book }) => {
        // Appel de la fonction pour potentielle mise à jour
        updateUserFirstProgression(book, currentBookTitle, images.length);
    });    

    // FONCTION DE MISE À JOUR DE PROGRESSION
    function updateUserProgression({ currentBookTitle, pageIndex, images, skipProgressionUpdate, fromMenu }) {
        if (skipProgressionUpdate) return;
    
        fetch('rqt_session_check.php')
            .then(res => res.json())
            .then(sessionData => {
                if (sessionData.status === 'ok') {
                    return fetch('rqt_user_books_get.php');
                } else {
                    throw new Error('Utilisateur non connecté');
                }
            })
            .then(res => res.json())
            .then(userBooksData => {
                if (userBooksData.status === 'ok') {
                    const userBooks = userBooksData.books;
                    const matchedBook = userBooks.find(book => book.title === currentBookTitle);
    
                    if (matchedBook) {
                        const currentPage = pageIndex + 1; // index 0 → page 1
                        const userLastPage = matchedBook.last_page || 1;
                        const userLastProgression = matchedBook.progression || 0;
                        const currentProgression = Math.round((currentPage * 100) / images.length);
    
                        const stepProgression = Math.round(100 / images.length);
                        let nextProgression = userLastProgression + stepProgression;
                        if (currentPage === images.length && nextProgression >= 99) {
                            nextProgression = 100;
                        }

                        // Si clic du menu page, doit être impérativement page suivante pour MAJ
                        if (fromMenu && currentPage !== userLastPage + 1)  return;
    
                        if ((currentPage > userLastPage && currentProgression > userLastProgression) || (currentPage >= userLastPage && nextProgression === 100) || (userLastPage === images.length && nextProgression <= 99)) {
                            fetch('rqt_user_book_progression_update.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    title: currentBookTitle,
                                    page: currentPage,
                                    progression: nextProgression
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status !== 'ok') {
                                    console.warn("Erreur MAJ progression:", data.message);
                                } else {
                                    console.log(data.message);
                                    refreshAllBookList();
                                    if (data.congrat) {
                                        showFloatingMessage(data.congrat, "success");
                                    }
                                }
                            })
                            .catch(err => console.error("Erreur réseau MAJ progression:", err));
                        }
                    }
                }
            })
            .catch(err => console.warn('Progression non mise à jour :', err.message));
    }    

    // Afficher les infos de l'auteur, traducteur, etc.
    // document.querySelector('.book-meta').style.border = '#5A5AFF 2px solid'; 
    // document.querySelector('.book-meta').style.marginBottom = "10px";
    // document.getElementById('book-author').innerHTML = `Auteur : ${config.auteur ? config.auteur : 'inconnu'}`; // Auteur
    // document.getElementById('book-translator').innerHTML = `Traducteur : ${ config.traducteur ? config.traducteur : 'inconnu'}`; // Traducteur
    // document.getElementById('book-narrator').innerHTML = `Narrateur: ${config.voix ? config.voix : 'inconnu' }`; // Voix

    // Au lancement du livre, montrer le lecteur audio
    const audioContainer = document.querySelector('.audio-navigation-container');
    audioContainer.classList.add('open');
    // Change l’icône
    arrowIcon.innerHTML = audioContainer.classList.contains('open') ? '&#9660;' : '&#9650;';
    // ↓ si ouvert, ↑ si fermé
    // Sur changement de slide → changer l’audio

    function onCarouselReady() {
        // Générer le menu de page
        const pageList = document.getElementById('page-list');
        pageList.innerHTML = '';

        // Ajout des pages au menu des Pages
        images.forEach((img, index) => {
            const li = document.createElement('li');
            li.textContent = `Page ${index + 1}`;
            
            li.onclick = (e) => {
                e.stopPropagation(); // ✅ Empêche la fermeture auto en dehors

                // Signal de clic depuis menu
                fromMenuClic = true;

                const carousel = bootstrap.Carousel.getInstance(document.querySelector('#carouselExample'));
                const slideIndex = config.lang === 'ar' ? images.length - 1 - index : index;
                carousel.to(slideIndex);

                // Fermer automatiquement le menu Page après sélection
                //document.getElementById('page-sidebar').classList.remove('open');
            };
            pageList.appendChild(li);
            if (index === menuActiveIndex) li.classList.add('active'); // Mise en évidence du premier li créé
            pageList.appendChild(li);
        });

        // Générer le menu de chapitres récursivement
        const chapitreList = document.getElementById('chapter-list');
        chapitreList.innerHTML = '';

        // Fonction utilitaire récursive
        function createChapterItem(chap, level = 0) {
            if (chap && typeof chap.page === 'number' && typeof chap.debut === 'string') {
                const li = document.createElement('li');

                // Label du chapitre avec indentation visuelle
                let label = '';
                if (chap.titre) {
                    label = chap.titre;
                } else if (chap.chapitre !== undefined) {
                    label = `Chapitre ${chap.chapitre}`;
                } else {
                    label = `Chapitre sans nom`;
                }

                li.textContent = `${label}`; // indentation simple
                li.classList.add('chapter-item', `page-${chap.page}`, `level-${level}`);

                // Appliquer l’orientation selon la langue
                li.dir = isArabic(label) ? 'rtl' : 'ltr';
                li.style.textAlign = isArabic(label) ? 'right' : 'left';

                const index = config.lang === 'ar' ? images.length - chap.page : chap.page - 1; // Index selon la langue
                const debut = chap.debut;

                li.onclick = (e) => {
                    e.stopPropagation(); // ✅ Empêche la fermeture auto en dehors

                    // Ne pas mettre à jour la progression
                    skipProgressionUpdate = true;
                    
                    globalTimeInSecForChapter = timeToSeconds(debut); // Variable globale pour le temps mise à jour
                    console.log("Sync Time in seconds:", globalTimeInSecForChapter);

                    const carousel = bootstrap.Carousel.getInstance(document.querySelector('#carouselExample'));
                    const currentPageIndex = Array.from(document.querySelectorAll('.carousel-item')).findIndex(item => item.classList.contains('active'));

                    const targetIndex = config.lang === 'ar' ? images.length - chap.page : chap.page - 1;

                    if (currentPageIndex === targetIndex) {
                        // Même page : on avance ou recule le wavesurfer
                        if (wavesurfer && wavesurfer.isReady) {
                            wavesurfer.seekTo(globalTimeInSecForChapter / wavesurfer.getDuration());
                        } else {
                            console.warn("wavesurfer non prêt");
                        }
                    } else {
                        // Changement de page, le temps sera pris en compte via initWaveSurfer
                        carousel.to(targetIndex);
                    }

                    // Fermer automatiquement le menu chapitre après sélection
                    // document.getElementById('chapter-sidebar').classList.remove('open');
                };

                chapitreList.appendChild(li);
            }

            // Appel récursif pour les sous-chapitres
            if (Array.isArray(chap.sousChapitres)) {
                chap.sousChapitres.forEach((subChap) => createChapterItem(subChap, level + 1));
            }
        }

        // Vérifie si des chapitres sont définis
        if (Array.isArray(chapitres)) {
            chapitres.forEach((chap) => createChapterItem(chap));
        }


        // AU CHANGEMENT DE PAGE
        $('#carouselExample').on('slid.bs.carousel', function () {
            // Faire défiler jusqu'au titre une fois qu'il est rendu
            setTimeout(() => {
                const activeImage = $('.carousel-item.active img').attr('src');
                const activeImageName = getFileNameWithoutExtension(activeImage);
                const matchingAudio = audios.find(audio => getFileNameWithoutExtension(audio) === activeImageName);
        
                // Tenter de détruire l’instance précédente en attrapant les erreurs
                try {
                    if (wavesurfer) wavesurfer.destroy();
                } catch (error) {
                    console.warn("Erreur lors de la destruction de WaveSurfer :", error);
                }
                // Initialiser compteurs de temps
                document.getElementById('current-time').textContent = '0:00';
                document.getElementById('total-time').textContent = '0:00';
        
                // Mettre à jour le menu des pages
                const carouselItems = document.querySelectorAll('.carousel-item');
                const activeIndex = [...carouselItems].findIndex(item => item.classList.contains('active'));
        
                // Calcul de l’index logique de page selon langue
                const pageIndex = config.lang === 'ar'
                    ? images.length - 1 - activeIndex
                    : activeIndex;
        
                // Supprimer l'ancienne classe "active" dans le menu
                document.querySelectorAll('#page-list li').forEach((li, i) => {
                    li.classList.toggle('active', i === pageIndex);
                });
        
                // Ajouter le numéro de page au Label du l'icône page
                document.getElementById('pageLabel').textContent = `Page ${pageIndex + 1}`;
        
                // Vérifier si l'audio correspondant existe avant de l'initialiser et de le charger
                if (matchingAudio) {
                    initWaveSurfer(matchingAudio, globalTimeInSecForChapter, config);
                    globalTimeInSecForChapter = null; // Réinitialiser le temps après l'initialisation
                }

                const fromMenu = fromMenuClic === true ? fromMenuClic : false;
        
                updateUserProgression({
                    currentBookTitle: currentBookTitle,              // Titre du livre
                    pageIndex: pageIndex,                           // index de la page actuelle (commence à 0)
                    images: images,                                 // tableau d’images/pages
                    skipProgressionUpdate: skipProgressionUpdate,    // Mettre à jour ou non
                    fromMenu: fromMenu                              // Clic depuis menu page
                });            

                fromMenuClic = false; // Réinitialiser

            }, 100); // Délai court pour s'assurer que l'élément est dans le DOM
                
        });

        // Ouvrir/fermer la sidebar Page avec le bouton 📖
        document.getElementById('page-menu-toggle').onclick = (e) => {
            // e.stopPropagation(); // 👉 Empêche la fermeture immédiate
            document.getElementById('page-sidebar').classList.toggle('open');
        };

        // Ouvrir/fermer la sidebar Chapitre avec le bouton 📖
        document.getElementById('chapter-menu-toggle').onclick = (e) => {
            // e.stopPropagation(); // 👉 Empêche la fermeture immédiate
            document.getElementById('chapter-sidebar').classList.toggle('open');
        };

        // Fermer les menu (page et chapitre) si clic en dehors
        document.addEventListener('click', function (event) {
            const sidebar1 = document.getElementById('page-sidebar');
            const toggleButton1 = document.getElementById('page-menu-toggle');

            // Contrôle et fermeture du sidebar Chapitre
            const isClickInsideSidebar1 = sidebar1.contains(event.target);
            const isClickOnToggle1 = toggleButton1.contains(event.target);

            if (!isClickInsideSidebar1 && !isClickOnToggle1) {
                sidebar1.classList.remove('open');
            }

            const sidebar2 = document.getElementById('chapter-sidebar');
            const toggleButton2 = document.getElementById('chapter-menu-toggle');

            // Contrôle et fermeture du sidebar Chapitre
            const isClickInsideSidebar2 = sidebar2.contains(event.target);
            const isClickOnToggle2 = toggleButton2.contains(event.target);

            if (!isClickInsideSidebar2 && !isClickOnToggle2) {
                sidebar2.classList.remove('open');
            }

            // Contrôle et fermeture du sidebar Chapitre Menu livre
            const bookMenuContainer = document.getElementById('book-list');
            const isClickedBookMenuContainer = bookMenuContainer.contains(event.target);

            const toogleBookMenuButton = document.getElementById('toggle-book-menu-btn');
            const isClickedToogleBookMenuButton = toogleBookMenuButton.contains(event.target);

            // Fermer le lecteur audio si clic en dehors et dehors les menus des livres, des pages et des chapitres
            const audioContainer = document.querySelector('.audio-navigation-container');
            const isClickedAudioContainer = audioContainer.contains(event.target);

            if (!isClickedAudioContainer && !isClickedBookMenuContainer && !isClickedToogleBookMenuButton && !isClickInsideSidebar2 && !isClickOnToggle2 && !isClickInsideSidebar1 && !isClickOnToggle1) {
                audioContainer.classList.remove('open');
                // Change l’icône
                arrowIcon.innerHTML = audioContainer.classList.contains('open') ? '&#9660;' : '&#9650;';
                // ↓ si ouvert, ↑ si fermé
            }

        });

    }

}

// Lancement du Lecteur Audio
/**
 * 
 * @param {"Lien de l'audio*} audioUrl 
 * @param {*Debut de Chapitre, si appelé*} chapTimeInSec 
 * @param {*Les configurations du livre} bookConfig 
 */
function initWaveSurfer(audioUrl, chapTimeInSec = null, bookConfig = null) {
    if (wavesurfer) wavesurfer.destroy(); // Détruire l'instance précédente si elle existe

    const isArabic = bookConfig && bookConfig.lang === 'ar'; // Vérifier si la langue est arabe

    const container = document.getElementById('waveform-container');
    container.innerHTML = ''; // Vider le contenu
    container.style.direction = isArabic ? 'rtl' : 'ltr';
    container.style.transform = isArabic ? 'scaleX(-1)' : 'scaleX(1)';

    // Créer une nouvelle instance de WaveSurfer
    wavesurfer = WaveSurfer.create({
        container: '#waveform-container',
        waveColor: '#ced4da',
        progressColor: '#0d6efd',
        cursorColor: '#000',
        barWidth: 2,
        height: 20,
        responsive: true,
        rtl: isArabic, // Activer le mode RTL si la langue est arabe
    });

    // Charger l'audio
    wavesurfer.load(audioUrl);

    // Événement pour le bouton "Lecture/Pause"
    document.getElementById('play-pause').onclick = () => {
        wavesurfer.playPause();
    };

    document.getElementById('playback-rate').onchange = (e) => {
        wavesurfer.setPlaybackRate(parseFloat(e.target.value));
    };

    // Afficher les textes de rewind et forward
    document.getElementById('rewindLabel').textContent = isArabic ? '+5s' : '-5s';
    document.getElementById('forwardLabel').textContent = isArabic ? '-5s' : '+5s';
    const autoPlayIcon = document.getElementById('autoPlayIcon');
    if (autoPlayIcon) {
        // Par défaut (non arabe) → lecture suivante
        autoPlayIcon.textContent = '⏭️';

        // Si arabe → lecture précédente (visuellement "inverse")
        if (bookConfig && bookConfig.lang === 'ar') {
            autoPlayIcon.textContent = '⏮️';
        }
    }


    // Rewind (reculer sauf si arabe → avancer)
    document.getElementById('rewind').onclick = () => {
        let time = wavesurfer.getCurrentTime();
        const offset = 5;
        let newTime = isArabic
            ? Math.min(wavesurfer.getDuration(), time + offset) // avancer si arabe
            : Math.max(0, time - offset);                       // sinon reculer
        wavesurfer.seekTo(newTime / wavesurfer.getDuration());
    };

    // Forward (avancer sauf si arabe → reculer)
    document.getElementById('forward').onclick = () => {
        let time = wavesurfer.getCurrentTime();
        const offset = 5;
        let newTime = isArabic
            ? Math.max(0, time - offset)                        // reculer si arabe
            : Math.min(wavesurfer.getDuration(), time + offset); // sinon avancer
        wavesurfer.seekTo(newTime / wavesurfer.getDuration());
    };

    // Événement pour mettre à jour le temps actuel
    wavesurfer.on('audioprocess', () => {
        document.getElementById('current-time').textContent = formatTime(wavesurfer.getCurrentTime());
    });

    wavesurfer.on('seek', () => {
        document.getElementById('current-time').textContent = formatTime(wavesurfer.getCurrentTime());
    });

    // Synchronisation du temps et vitesse de lecture
    wavesurfer.on('ready', () => {
        // Affichage des temps
        document.getElementById('total-time').textContent = formatTime(wavesurfer.getDuration());

        // Récupérer la vitesse sélectionnée
        const rate = parseFloat(document.getElementById('playback-rate').value);
        wavesurfer.setPlaybackRate(rate);

        // Si chapTimeInSec a une valeur, synchroniser le temps
        if (chapTimeInSec) {
            console.log("Synchronisation du chapitre:", chapTimeInSec);
            wavesurfer.seekTo(chapTimeInSec / wavesurfer.getDuration());
        }
        // Jouer l'audio automatiquement
        wavesurfer.play();
    });

    // Lecture automatique du slide suivant à la fin de l'audio
    wavesurfer.on('finish', () => {
        const autoPlayEnabled = document.getElementById('autoPlayBtn').checked;
        if (!autoPlayEnabled) return;

        const carousel = bootstrap.Carousel.getInstance(document.querySelector('#carouselExample'));
        const carouselItems = document.querySelectorAll('.carousel-item');
        const activeIndex = [...carouselItems].findIndex(item => item.classList.contains('active'));

        const isArabic = bookConfig && bookConfig.lang === 'ar';

        let nextIndex;
        if (isArabic) {
            // Aller au slide précédent visuellement (mais logique suivant en arabe)
            nextIndex = activeIndex - 1;
        } else {
            // Aller au slide suivant
            nextIndex = activeIndex + 1;
        }

        // Vérifier si l'index est valide
        if (nextIndex >= 0 && nextIndex < carouselItems.length) {
            carousel.to(nextIndex);
        }
    });

}

// Fonction pour formater le temps en "mm:ss"
function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

// Convertir "mm:ss" en secondes
function timeToSeconds(timeStr) {
    const parts = timeStr.split(':').map(Number);

    if (parts.length === 1) {
        return parts[0]; // "ss"
    } else if (parts.length === 2) {
        return parts[0] * 60 + parts[1]; // "mm:ss"
    } else if (parts.length === 3) {
        return parts[0] * 3600 + parts[1] * 60 + parts[2]; // "hh:mm:ss"
    } else {
        return 0; // Format invalide
    }
}

// Fonction utilitaire pour détecter si le texte contient des caractères arabes
function isArabic(text) {
    return /[\u0600-\u06FF]/.test(text);
}

// Fonction pour normaliser le texte (enlever les accents, les harakats, etc.)
function normalizeText(text) {
    return text
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "") // Accents latins
        .replace(/[\u064B-\u065F]/g, "") // Harakats arabes
        .replace(/[^\w\u0600-\u06FF]/g, "") // Supprime ponctuations sauf lettres arabes
        .toLowerCase()
        .trim();
}

// Fonction principale : met en évidence les mots exacts Allah, Muhammad, Khadim
function highlightText() {
    $('.bookName').each(function () {
        const element = $(this);
        const text = element.text();

        // Divise le texte en mots en gardant la ponctuation séparée
        const words = text.split(/(\s+|[.,;!?()])/); // garde les séparateurs

        // Reconstruit le texte avec mise en évidence
        const highlightedText = words.map(word => {
            const normalized = normalizeText(word);

            if (["الله", "allah", "allahu", "بالله", "والله", "تالله", "لله"].includes(normalized)) {
                return `<span style="color:red; font-weight:bold">${word}</span>`;
            }

            if ([
                "محمد", "mouhammad", "mouhammadun", "mouhammadoun", "mouhammadan", "mouhammadin",
                "muhammadu", "muhammadan", "muhammadin", "muhammadun", "mouhamad", "mahomet", "mohammed", 
                "mohamed", "mohammad", "muhammad", "muhamad",
            ].includes(normalized)) {
                return `<span style="color:blue; font-weight:bold">${word}</span>`;
            }

            if (["خديم", "khadim", "khadimu", "khadimou", "khadimi", "xadim", "xadimu", "xadimou", "xadimi", "الخديم"].includes(normalized)) {
                return `<span style="color:green; font-weight:bold">${word}</span>`;
            }

            return word; // mot non ciblé
        }).join('');

        // Injecte le texte HTML mis en forme dans le DOM
        element.html(highlightedText);
    });
}

// Écouteur pour la recherche dans le menu des livres
document.getElementById('bookSearch').addEventListener('input', function () {
    const query = normalizeText(this.value.trim());
    const originalQuery = this.value.trim();
    // Sélectionner toutes les listes de livres visibles
    const bookLists = Array.from(document.querySelectorAll('.book-list-content'))
        .filter(el => getComputedStyle(el).display !== 'none');

    let totalFound = 0;
    let globalPhase = 'title';
    let globalMatch = '';

    // Vérification de filtrage par langue et type
    const searchFilterBtnsGroup = document.getElementById('searchFilterBtnsGroup');
    const selectedLangs = Array.from(searchFilterBtnsGroup.querySelectorAll('input[name="lang[]"]:checked')).map(cb => cb.value);
    const selectedTypes = Array.from(searchFilterBtnsGroup.querySelectorAll('input[name="type[]"]:checked')).map(cb => cb.value);

    function formatList(arr, labelFunc = (v) => v) {
        return arr.length > 0 ? arr.map(labelFunc).join(', ') : '';
    }    

    const langPart = selectedLangs.length > 0 
    ? ` selon ${selectedLangs.length > 1 ? 'les' : 'la'} langue${selectedLangs.length > 1 ? 's' : ''} : 
        <strong>${formatList(selectedLangs, getLangLabel)}</strong>` 
    : '';

    const typePart = selectedTypes.length > 0 
        ? ` ${selectedLangs.length > 0 ? 'et/ou' : ''}
            selon le${selectedTypes.length > 1 ? 's' : '' } type${selectedTypes.length > 1 ? 's' : '' } : 
            <strong>${formatList(selectedTypes, getTypeLabel)}</strong>` 
        : '';

    bookLists.forEach(list => {
        const items = list.querySelectorAll('.book-item');
        let found = 0;
        let phase = 'title';
        let matchedText = '';
    
        // CAS SPÉCIAL : aucun texte de recherche => on applique uniquement les filtres
        if (query === '') {
            items.forEach(item => {
                const itemLang = item.dataset.lang || '';
                const itemType = item.dataset.type || '';
    
                const langMatch = selectedLangs.length === 0 || selectedLangs.includes(itemLang);
                const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(itemType);
    
                const li = item.closest('li');
                if (li) li.style.display = (langMatch && typeMatch) ? '' : 'none';
    
                if (langMatch && typeMatch) found++;
            });
    
            if (found > 0) totalFound += found;
            return;
        }
    
        // CACHER TOUS LES ITEMS AVANT FILTRAGE
        items.forEach(item => {
            const li = item.closest('li');
            if (li) li.style.display = 'none';
        });
    
        // PHASE 1 : Titre (avec filtres)
        items.forEach(item => {
            const latin = normalizeText(item.dataset.latin);
            const arabic = normalizeText(item.dataset.arabic);
    
            const itemLang = item.dataset.lang || '';
            const itemType = item.dataset.type || '';
    
            const langMatch = selectedLangs.length === 0 || selectedLangs.includes(itemLang);
            const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(itemType);
    
            if ((latin.includes(query) || arabic.includes(query)) && langMatch && typeMatch) {
                const li = item.closest('li');
                if (li) li.style.display = '';
                found++;
            }
        });
    
        // PHASES suivantes : auteur, traducteur, narrateur
        if (found === 0) {
            const phases = ['author', 'translator', 'narrator'];
            for (let p of phases) {
                phase = p;
                items.forEach(item => {
                    const raw = item.dataset[p] || '';
                    if (normalizeText(raw).includes(query)) {
                        const li = item.closest('li');
                        if (li) li.style.display = '';
                        found++;
                        matchedText = raw;
                    }
                });
                if (found > 0) break;
            }
        }
    
        if (found > 0) {
            totalFound += found;
            globalPhase = phase;
            if (phase !== 'title') globalMatch = matchedText;
        }
    });
    // Masquer ou afficher les groupes .book-list-content-group selon s'ils contiennent des <li> visibles
    document.querySelectorAll('.book-list-content-group').forEach(group => {
        const listItems = group.querySelectorAll('li');
        const anyVisible = Array.from(listItems).some(li => getComputedStyle(li).display !== 'none');
        group.style.display = anyVisible ? '' : 'none';
    });

    // Construction de l'entête de résultat (globale pour tous et user)
    let headerHTML = '';
    if (totalFound > 0 && globalPhase === 'title') {
        headerHTML = `
            <div class="found-msg">
                <strong><i>${totalFound} </i></strong>
                Livre${totalFound > 1 ? 's' : ''} trouvé${totalFound > 1 ? 's' : ''} pour : <b>« ${originalQuery} »</b>
                ${langPart ? '<br>' : ''}${langPart} 
                ${typePart ? '<br>' : ''}${typePart}
            </div>
        `;
    } else if (totalFound > 0) {
        const label = globalPhase === 'author' ? "l’auteur"
                    : globalPhase === 'translator' ? "le traducteur"
                    : "l’interprète";

        headerHTML = `
            <div class="not-found-msg">
                <em><small>Aucun livre trouvé pour : « ${originalQuery} »</small>
                ${langPart ? '<br>' : ''}${langPart} 
                ${typePart ? '<br>' : ''}${typePart}
                </em>
            </div>
            <hr width="100%" class="my-1">
            <div class="found-alt">
                <i>${totalFound} </i>
                Résultat selon <b>${label}</b> : <b class="text-success">« ${globalMatch} »</b>
            </div>
        `;                
    } else {
        headerHTML = `
            <div class="not-found-msg">
                <em><small>Aucun résultat pour : « ${originalQuery} »</small>
                ${langPart ? '<br>' : ''}${langPart} 
                ${typePart ? '<br>' : ''}${typePart}
                </em>
            </div>
        `;
    }

    // Injecter l'entête dans la liste de livres (s'il y a recherche => query)
    injectBookHeader(headerHTML, query.trim() !== '');
});

// Fonction pour injecter l'entête dans la liste de livres
function injectBookHeader(htmlContent, shouldInject) {
    // Supprimer tous les anciens en-têtes
    document.querySelectorAll('.book-result-header').forEach(header => header.remove());

    if (!shouldInject) return; // Ne rien faire si pas de recherche

    const header = document.createElement('div');
    header.className = 'book-result-header';
    header.innerHTML = htmlContent;

    document.querySelectorAll('.all-book-list-content').forEach(list => {
        list.parentNode.insertBefore(header.cloneNode(true), list);
    });
}

// Fonction pour convertir les valeurs de filtre
// Convertir les référence langues en langue
function getLangLabel(code) {
    const map = {
        ar: 'Arabe',
        fr: 'Français',
        en: 'Anglais',
        wo: 'Wolof'
    };
    return map[code] || code;
}

// Fonction pour convertir les références types en type
function getTypeLabel(code) {
    const map = {
        qr: 'Quran',
        xs: 'Xassida',
        xm: 'Xam Xam'
    };
    return map[code] || code;
}


// Filtrage des livres
document.addEventListener('DOMContentLoaded', () => {
    const searchFilterBtnsGroup = document.getElementById('searchFilterBtnsGroup');
    const langCheckboxes = searchFilterBtnsGroup.querySelectorAll('input[name="lang[]"]');
    const typeCheckboxes = searchFilterBtnsGroup.querySelectorAll('input[name="type[]"]');

    function filterBooks() {
        const selectedLangs = Array.from(langCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        const selectedTypes = Array.from(typeCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        const allBookLists = document.querySelectorAll('.list-books');

        allBookLists.forEach(bookList => {
            const items = bookList.querySelectorAll('li');

            items.forEach(item => {
                const itemLang = item.dataset.lang || '';
                const itemType = item.dataset.type || '';

                const langMatch = selectedLangs.length === 0 || selectedLangs.includes(itemLang);
                const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(itemType);

                item.style.display = (langMatch && typeMatch) ? '' : 'none';
            });
        });
    }

    // 🔓 Exposer dans la fenêtre globale
    window.filterBooks = filterBooks;

    // Écoute des checkbox
    [...langCheckboxes, ...typeCheckboxes].forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const searchInput = document.getElementById('bookSearch');
            if (searchInput) {
                // Déclenchement programmé de l'événement "input"
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });


    // Appel initial 
    const searchInput = document.getElementById('bookSearch');
    if (searchInput) {
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
    }


    // Réinitialiser les boutons filtre
    function resetFilters() {
        const searchFilterBtnsGroup = document.getElementById('searchFilterBtnsGroup');
        const checkboxes = searchFilterBtnsGroup.querySelectorAll('input[name="lang[]"], input[name="type[]"]');

        checkboxes.forEach(cb => {
            cb.checked = false;
    
            // Si tu utilises des styles custom comme `.active` sur les labels :
            if (cb.parentElement.classList.contains('active')) {
                cb.parentElement.classList.remove('active');
            }
        });
    
        if (typeof filterBooks === 'function') {
            filterBooks();
        }
    }
    
    // 🔓 Exposer globalement aussi si besoin :
    window.resetFilters = resetFilters;
    
});

// Auto positionnement du texte dans le champ de recherche
function adjustSearchDirection(input) {
    const text = input.value.trim();
    // Détection très simple : si le texte commence par un caractère arabe, on passe en RTL
    const isArabic = /^[\u0600-\u06FF]/.test(text);
    input.style.direction = isArabic ? 'rtl' : 'ltr';
    input.style.textAlign = isArabic ? 'right' : 'left';
}

// Affichage sous menu utilisateur
document.addEventListener('DOMContentLoaded', function () {
    const userIcon = document.getElementById('userIcon');
    const dropdown = document.getElementById('user-dropdown');

    userIcon.addEventListener('click', function (e) {
        e.stopPropagation(); // Empêche la fermeture immédiate

        // Raffraichir le sous menu utilisateur
        refreshUserSubMenu();

        // Afficher/Cacher le menu déroulant
        dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
    });

    document.addEventListener('click', function () {
        dropdown.style.display = 'none';
    });
});

// Fonction pour rafraîchir le sous-menu utilisateur
function refreshUserSubMenu() {
    // Masquer tous les éléments par défaut
    document.getElementById('user-profile-link').style.display = 'none';
    document.getElementById('user-book-link').style.display = 'none';
    document.getElementById('user-logout-link').style.display = 'none';
    document.getElementById('user-login-link').style.display = 'none';

    fetch('rqt_session_check.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                const user = data.user;
                document.getElementById('user-profile-link').style.display = 'flex';
                document.getElementById('user-book-link').style.display = 'flex';
                document.getElementById('user-logout-link').style.display = 'flex';
                document.getElementById('userName').textContent = user.fullname || 'Inconnu';
            } else {
                document.getElementById('user-login-link').style.display = 'flex';
                document.getElementById('userName').textContent = 'Visiteur';
            }
        })
        .catch(error => {
            console.error('Erreur vérification de session :', error);
            showFloatingMessage("Erreur lors de la vérification de session.", "danger");
        });
}
// Initialiser le sous-menu et les infos utilisateur
refreshUserSubMenu();

// Basculer entre tous les livres, mes livres et profil
document.addEventListener('DOMContentLoaded', function () {
    const userProfileLink = document.getElementById('user-profile-link');
    const userBookLink = document.getElementById('user-book-link');
    const userLogoutLink = document.getElementById('user-logout-link');
    const userLoginLink = document.getElementById('user-login-link');
    const allBookIcon = document.getElementById('show-all-book');
    const menuTitle = document.getElementById('bookMenuTitle');
    const bookSearchForm = document.getElementById('bookSearchForm');
    const allBookList = document.querySelector('.all-book-list-content');
    const userBookList = document.querySelector('.user-book-list-content');
    const profileForm = document.querySelector('.user-profile-form');
    const authForm = document.querySelector('.user-auth-section');

    // Clic sur l’icône "Livres"
    allBookIcon.addEventListener('click', function () {
        menuTitle.textContent = "Livres disponibles";
        bookSearchForm.style.display = "block";
        // Acctualiser la liste des livres et les boutons de contrôle
        refreshAllBookList();
        allBookList.style.display = "block";
        userBookList.style.display = "none";
        profileForm.style.display = "none";
        authForm.style.display = "none"; // Cacher le formulaire de connexion

        // Raffraichir le filtre de la liste des livres
        filterBooks();

        // Simuler la recherche uniquement si le champ n’est pas vide
        const bookSearchInput = document.getElementById('bookSearch');
        if (bookSearchInput.value.trim() !== '') {
            // Déclencher manuellement l'événement input
            bookSearchInput.dispatchEvent(new Event('input'));
        }

    });

    // Clic sur "Mes Livres"
    userBookLink.addEventListener('click', function (e) {
        e.preventDefault();
        menuTitle.textContent = "Mes livres";
        bookSearchForm.style.display = "block";
        allBookList.style.display = "none";
        userBookList.style.display = "block";
        profileForm.style.display = "none";
        authForm.style.display = "none"; // Cacher le formulaire de connexion

        // Fermer le menu utilisateur s’il est affiché
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) dropdown.style.display = 'none';

        // Afficher les livres de l'utilisateur connecté 
        showUserBookList(); // Le raffraichissement est fait dans cette fonction
    });
    
    // Clic sur "Profil"
    userProfileLink.addEventListener('click', function (e) {
        e.preventDefault();

        menuTitle.textContent = "Mon Compte";
        bookSearchForm.style.display = "none";
        allBookList.style.display = "none";
        userBookList.style.display = "none";
        profileForm.style.display = "block";
        authForm.style.display = "none";

        document.getElementById("account-message").style.display = "none";
        document.getElementById("account-message").textContent = "";

        const form = document.getElementById("profile-data-form");
        const infoCard = document.getElementById("profile-info-card");
        form.reset(); // réinitialise les champs
        infoCard.style.display = "none"; // au début

        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) dropdown.style.display = 'none';

        fetch('rqt_session_check.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    const user = data.user;
                    form.fullname.value = user.fullname || '';
                    form.email.value = user.email || '';
                    form.phone.value = user.phone || '';
                    form.userOldPassword.value = '';
                    form.userNewPassword.value = '';

                    // Mettre à jour la carte visible
                    document.getElementById("profile-fullname").textContent = user.fullname || 'Nom inconnu';
                    document.getElementById("profile-email").textContent = "Email: " + (user.email || 'Email inconnu');
                    document.getElementById("profile-phone").textContent = "Téléphone: " + (user.phone || 'Téléphone inconnu');

                    infoCard.style.display = "block";

                } else {
                    document.getElementById("account-message").textContent = data.message;
                    document.getElementById("account-message").classList.add('text-danger');
                    document.getElementById("account-message").style.display = "block";
                    infoCard.style.display = "none";
                }
            })
            .catch(error => {
                console.error('Erreur profil :', error);
                document.getElementById("account-message").textContent = "Erreur lors du chargement.";
                document.getElementById("account-message").style.display = "block";
                document.getElementById("account-message").classList.add('text-danger');
                infoCard.style.display = "none";
            });
    });

    // Clic sur "Modifier mon compte" pour ouvrir le modal
    document.getElementById('edit-profile-btn').addEventListener('click', function (e) {
        e.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('profile-edit-modal'));
        modal.show();
    });


    // Clic Connexion
    userLoginLink.addEventListener('click', function (e) {
        e.preventDefault();
        menuTitle.textContent = "Authentification";
        bookSearchForm.style.display = "none";
        allBookList.style.display = "none";
        userBookList.style.display = "none";
        profileForm.style.display = "none";
        authForm.style.display = "block"; // afficher le formulaire de connexion

        // Fermer le menu utilisateur s’il est affiché
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) dropdown.style.display = 'none';
    });

    // Clic sur "Déconnexion"
    userLogoutLink.addEventListener('click', function (e) {
        e.preventDefault();
        menuTitle.textContent = "Livres disponibles";
        bookSearchForm.style.display = "block";
        allBookList.style.display = "block";
        userBookList.style.display = "none";
        profileForm.style.display = "none";
        authForm.style.display = "none"; // Cacher le formulaire de connexion

        // Fermer le menu utilisateur s’il est affiché
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) dropdown.style.display = 'none';

        // Appel AJAX pour déconnexion
        fetch('rqt_user_logout.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Raffraichir le sous menu utilisateur
                    refreshUserSubMenu();

                    // Actualiser la liste des livres
                    refreshAllBookList();

                    // Afficher une info de déconnexion
                    showFloatingMessage("Déconnexion réussie !", "success");
                    // Réactualiser la page
                    window.location.reload();
                } else {
                    showFloatingMessage("Erreur lors de la déconnexion.", "danger");
                }
            })
            .catch(error => {
                console.error('Erreur déconnexion :', error);
                showFloatingMessage("Erreur réseau lors de la déconnexion.", "danger");
            });
    });

});

document.addEventListener('DOMContentLoaded', () => {
    const forgotPasswordLink = document.getElementById('forgotPassword');
    const forgotTab = document.getElementById('forgot-password-tab');
    const authTabs = document.getElementById('auth-tabs');

    const loginNav = document.getElementById('login-nav');
    const registerNav = document.getElementById('register-nav');

    if (forgotPasswordLink && forgotTab && authTabs && loginNav && registerNav) {
        // 🎯 Mot de passe oublié
        forgotPasswordLink.addEventListener('click', (event) => {
            event.preventDefault();

            // Garde les entêtes visibles
            authTabs.style.display = 'flex'; // ou 'block', selon ton style
            // Lbérer les entêtes
            document.querySelectorAll('.nav-link.active').forEach(navLink => {
                navLink.classList.remove('active');
            });

            // Masquer tous les autres onglets
            document.querySelectorAll('.tab-pane').forEach(tab => {
                tab.classList.remove('show', 'active');
            });

            // Activer "mot de passe oublié"
            forgotTab.classList.add('show', 'active');

            // Pré-remplir l'email
            const loginEmail = document.getElementById('login').value.trim();
            document.getElementById("recover-email").value = isValidEmail(loginEmail) ? loginEmail : '';
        });

        // 🎯 Revenir sur login
        loginNav.addEventListener('click', () => {
            authTabs.style.display = 'flex';
            switchTab('login-tab');
        });

        // 🎯 Revenir sur inscription
        registerNav.addEventListener('click', () => {
            authTabs.style.display = 'flex';
            switchTab('register-tab');
        });
    }
});

// 🔁 Change d'onglet
function switchTab(activeId) {
    document.querySelectorAll('.tab-pane').forEach(tab => {
        tab.classList.remove('show', 'active');
    });
    const activeTab = document.getElementById(activeId);
    if (activeTab) {
        activeTab.classList.add('show', 'active');
    }
}

// ✅ Vérification email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Fonction pour rafraîchir la liste complète des livres sans tout recréer
function refreshAllBookList() {
    fetch('rqt_user_books_get.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                const userBooks = data.books;
                const userBooksMap = {}; // Map rapide pour chercher par titre
                userBooks.forEach(book => {
                    userBooksMap[book.title] = book;
                });

                // Parcourt tous les livres affichés
                document.querySelectorAll('.list-books li').forEach(listItem => {
                    const title = listItem.querySelector('.book-item')?.dataset.latin;
                    const progressDiv = listItem.querySelector('.progress');
                    const controlGroup = listItem.querySelector('.book-control-group');

                    const bookData = userBooksMap[title];

                    if (bookData) {
                        // Livre suivi

                        if (progressDiv) {
                            const progressBar = progressDiv.querySelector('.progress-bar');
                            if (progressBar) {
                                progressBar.style.width = `${bookData.progression}%`;
                                progressBar.setAttribute('aria-valuenow', bookData.progression);
                                progressBar.innerText = `${bookData.progression}%`;
                            }
                        } else if (controlGroup) {
                            const newProgressDiv = document.createElement('div');
                            newProgressDiv.className = 'progress mt-2';
                            const progressBarClass = bookData.progression === 100 ? 'progress-bar bg-success' : 'progress-bar';
                            newProgressDiv.innerHTML = `
                                <div class="${progressBarClass}" role="progressbar" 
                                    style="width: ${bookData.progression}%;" 
                                    aria-valuenow="${bookData.progression}" aria-valuemin="0" aria-valuemax="100">
                                    ${bookData.progression}%
                                </div>`;
                            listItem.insertBefore(newProgressDiv, controlGroup);
                        }

                        if (controlGroup) {
                            controlGroup.innerHTML = `
                                ${
                                    bookData.progression < 100
                                    ? '<span class="book-badge static-badge in-progress-badge">En cours</span>'
                                    : '<span class="book-badge static-badge finished-badge">Terminé</span>'
                                }
                                <span class="book-badge remove-badge" title="Retirer ce livre">Retirer</span>
                            `;
                        }

                    } else {
                        // Livre non suivi
                        if (progressDiv) progressDiv.remove();
                        if (controlGroup) {
                            controlGroup.innerHTML = `
                                <span class="book-badge follow-badge" title="Suivre ce livre">Suivre</span>
                            `;
                        }
                    }
                });

                resetFilters(); // Facultatif

            } else {
                displayBooksWithoutControls();
            }
        })
        .catch(err => {
            console.error("Erreur :", err);
            displayBooksWithoutControls();
        });
}

// Fonction qui enlève la barre et les boutons
function displayBooksWithoutControls() {
    document.querySelectorAll('.list-books li').forEach(listItem => {
        const progressDiv = listItem.querySelector('.progress');
        if (progressDiv) progressDiv.remove();
        
        const controlGroup = listItem.querySelector('.book-control-group');
        if (controlGroup) controlGroup.innerHTML = '';
    });
}


// Recharger la liste de tous les livres
refreshAllBookList();

// Fonction pour afficher la liste des livres de l'utilisateur connecté
function showUserBookList() {
    fetch('rqt_user_books_get.php')
        .then(response => response.json())
        .then(data => {
            console.log(data);
            if (data.status === 'ok') {
                const books = data.books;

                // Grouper les livres par langue
                const groups = {
                    ar: { label: '📚 Livres en Arabe', books: [] },
                    fr: { label: '📖 Livres en Français', books: [] },
                    en: { label: '📘 Livres en Anglais', books: [] },
                    wo: { label: '📝 Livres en Wolof', books: [] },
                    autres: { label: '📁 Autres Livres', books: [] },
                };

                books.forEach(book => {
                    const lang = book.lang || 'autres';
                    (groups[lang] || groups.autres).books.push(book);
                });

                const userBookList = document.querySelector('.user-book-list-content');
                userBookList.innerHTML = ''; // Reset

                for (const [lang, group] of Object.entries(groups)) {
                    if (group.books.length === 0) continue;


                    // Créer un conteneur pour la liste
                    const groupContainer = document.createElement('div');
                    groupContainer.classList.add('book-list-content-group');
                    groupContainer.style.marginBottom = '1rem';

                    // Titre du groupe
                    const groupTitle = document.createElement('h5');
                    groupTitle.textContent = group.label;
                    groupTitle.style.marginTop = '1rem';
                    groupTitle.classList.add('book-language-header');

                    // Ajouter le titre au conteneur de groupe
                    groupContainer.appendChild(groupTitle);

                    // Liste des livres
                    const listContainer = document.createElement('ul');
                    listContainer.classList.add('list-group', 'list-books');
                    listContainer.style.margin = '0';

                    group.books.forEach(book => {
                        const listItem = document.createElement('li');
                        listItem.className = 'list-group-item';
                        listItem.style.position = 'relative';

                        const listItemDiv = document.createElement('div');
                        listItemDiv.className = 'book-item';
                        listItemDiv.setAttribute('onclick', `loadBook('${book.title.replace(/'/g, "\\'")}')`);
                        listItemDiv.dataset.latin = book.title;
                        listItemDiv.dataset.arabic = book.nomArabe || '';
                        listItemDiv.dataset.lang = book.lang || '';
                        listItemDiv.dataset.trans = book.trans || '';
                        listItemDiv.dataset.type = book.type || '';
                        listItemDiv.dataset.author = book.auteur || '';
                        listItemDiv.dataset.translator = book.traducteur || '';
                        listItemDiv.dataset.narrator = book.voix || '';

                        const progressBarClass = book.progression === 100 ? 'progress-bar bg-success' : 'progress-bar';

                        listItemDiv.innerHTML = `
                            ${book.title}
                            ${book.nomArabe ? `
                                <div dir="rtl" style="direction: rtl; text-align: right; font-size: 0.8rem; color: #555;">
                                    ${book.nomArabe}
                                </div>` : ''}
                            <div class="progress mt-2">
                                <div class="${progressBarClass}" role="progressbar" style="width: ${book.progression}%;" aria-valuenow="${book.progression}" aria-valuemin="0" aria-valuemax="100">
                                    ${book.progression}%
                                </div>
                            </div>
                        `;

                        listItem.appendChild(listItemDiv);

                        const listItemControlDiv = document.createElement('div');
                        listItemControlDiv.className = 'book-control-group';
                        listItemControlDiv.style.position = 'absolute';
                        listItemControlDiv.style.bottom = '5px';
                        listItemControlDiv.style.left = '15px';
                        listItemControlDiv.innerHTML = `
                            ${book.progression < 100
                                ? '<span class="book-badge static-badge in-progress-badge">En cours</span>'
                                : '<span class="book-badge static-badge finished-badge">Terminé</span>'
                            }
                            <span class="book-badge remove-badge" title="Retirer ce livre">Retirer</span>
                        `;
                        listItem.appendChild(listItemControlDiv);
                        listContainer.appendChild(listItem);
                    });

                    // Ajouter la liste au conteneur du groupe
                    groupContainer.appendChild(listContainer);
                    userBookList.appendChild(groupContainer);
                }

                highlightText();
                resetFilters();

            } else {
                document.querySelector('.user-book-list-content').innerHTML = `
                    <div class="alert alert-warning">${data.message}</div>`;
            }
        })
        .catch(err => {
            console.error("Erreur :", err);
            document.querySelector('.user-book-list-content').innerHTML = `
                <div class="alert alert-danger">Erreur lors du chargement.</div>`;
        });
}

// Écouteur global pour les boutons Suivre et Retirer
document.addEventListener('click', function(event) {
    const badge = event.target.closest('.follow-badge, .remove-badge');
    if (badge) {
        event.stopPropagation(); // Bloque le clic qui remonterait au li parent

        const listItem = badge.closest('li');
        if (badge.classList.contains('follow-badge')) {
            addBookToUserSelection(listItem); // Fonction pour suivre le livre
        } else if (badge.classList.contains('remove-badge')) {
            deleteBookFromUserSelection(listItem); // Fonction pour retirer le livre
        }
    }
});

// Fonction pour suivre un livre
function addBookToUserSelection(listItem) {
    const title = listItem.querySelector('.book-item').dataset.latin;

    fetch('rqt_user_books_add.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ title })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            // ✅ Mettre à jour immédiatement l'UI (progression 0%)
            const controlGroup = listItem.querySelector('.book-control-group');
            if (controlGroup) {
                // Supprimer ancienne barre si existe
                const oldProgress = listItem.querySelector('.progress');
                if (oldProgress) oldProgress.remove();

                // Ajouter une barre de progression initiale
                const newProgressDiv = document.createElement('div');
                newProgressDiv.className = 'progress mt-2';
                newProgressDiv.innerHTML = `
                    <div class="progress-bar" role="progressbar" 
                        style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>`;
                listItem.insertBefore(newProgressDiv, controlGroup);

                // Mettre à jour les badges
                controlGroup.innerHTML = `
                    <span class="book-badge static-badge in-progress-badge">En cours</span>
                    <span class="book-badge remove-badge" title="Retirer ce livre">Retirer</span>
                `;
            }

            showFloatingMessage("Vous suivez maintenant ce livre !", 'success');
        } else {
            showFloatingMessage(data.message, 'danger');
        }
    })
    .catch(err => {
        console.error("Erreur ajout :", err);
    });
}

// Fonction pour retirer un livre
function deleteBookFromUserSelection(listItem) {
    const title = listItem.querySelector('.book-item').dataset.latin;

    if (!confirm("Êtes-vous sûr de vouloir retirer ce livre ?")) return;

    fetch('rqt_user_books_remove.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ title })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            // ✅ Supprimer immédiatement la barre et remettre "Suivre"
            const progressDiv = listItem.querySelector('.progress');
            if (progressDiv) progressDiv.remove();

            const controlGroup = listItem.querySelector('.book-control-group');
            if (controlGroup) {
                controlGroup.innerHTML = `<span class="book-badge follow-badge" title="Suivre ce livre">Suivre</span>`;
            }

            showFloatingMessage("Livre retiré avec succès !", 'success');
        } else {
            showFloatingMessage(data.message, 'danger');
        }
    })
    .catch(err => {
        console.error("Erreur suppression :", err);
        showFloatingMessage("Erreur lors de la suppression du livre.", 'danger');
    });
}

// Formulaire connexion / Inscription / Mot de passe oublié
document.addEventListener('DOMContentLoaded', () => {
    // Formulaire connexion
    const loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // empêche le rechargement

        const login = document.getElementById('login').value.trim();
        const password = document.getElementById('login_password').value;

        try {
            const response = await fetch('rqt_user_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ login, password })
            });

            const result = await response.json();

            if (result.success) {
                // Raffraichir le sous menu utilisateur
                refreshUserSubMenu();

                // Actualiser la liste des livres
                refreshAllBookList();
                
                showFloatingMessage("Connexion réussie !", "success");
                // Reactualiser la page
                // window.location.reload();

                // Vider les trois formulaires
                document.getElementById('registerForm').reset();
                document.getElementById('loginForm').reset();
                document.getElementById('forgotPasswordForm').reset();

                // Afficher le menu livre de l'utilisateur connecté
                document.getElementById('bookMenuTitle').textContent = "Mes livres";
                document.getElementById('bookSearchForm').style.display = "block";
                document.querySelector('.all-book-list-content').style.display = "none";
                document.querySelector('.user-book-list-content').style.display = "block";
                document.querySelector('.user-profile-form').style.display = "none";
                document.querySelector('.user-auth-section').style.display = "none"; // Cacher le formulaire de connexion

                // Afficher les livres de l'utilisateur connecté avec un délai pour que la session soit bien prise en compte
                setTimeout(() => {
                    showUserBookList();
                }, 500); // 500ms suffit dans la plupart des cas


            } else {
                const messageDiv = document.getElementById("user-auth-message");
                messageDiv.classList.add("text", "text-danger");
                messageDiv.textContent = result.message || "Identifiants incorrects."
                messageDiv.style.display = "block";
            }
        } catch (error) {
            console.error('Erreur réseau :', error);
            const messageDiv = document.getElementById("user-auth-message");
            messageDiv.classList.add("text", "text-danger");
            messageDiv.textContent = "Erreur de connexion au serveur."
            messageDiv.style.display = "block";
        }
    });

    // Formulaire d'inscription
    const registerForm = document.getElementById('registerForm');

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // empêche le rechargement

        const formData = new FormData(registerForm);
        const data = {};
        formData.forEach((value, key) => data[key] = value);

        try {
            const response = await fetch('rqt_user_register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                // Raffraichir le sous menu utilisateur
                refreshUserSubMenu();

                // Actualiser la liste des livres
                refreshAllBookList();
                
                showFloatingMessage("Inscription réussie !", "success");

                // Vider les trois formulaires
                document.getElementById('registerForm').reset();
                document.getElementById('loginForm').reset();
                document.getElementById('forgotPasswordForm').reset();

                // Afficher la section connectée ou autre action
                document.getElementById('bookMenuTitle').textContent = "Mes livres";
                document.getElementById('bookSearchForm').style.display = "block";
                document.querySelector('.all-book-list-content').style.display = "none";
                document.querySelector('.user-book-list-content').style.display = "block";
                document.querySelector('.user-profile-form').style.display = "none";
                document.querySelector('.user-auth-section').style.display = "none";

                // Afficher les livres de l'utilisateur connecté avec un délai pour que la session soit bien prise en compte
                setTimeout(() => {
                    showUserBookList();
                }, 500); // 500ms suffit dans la plupart des cas


            } else {
                const messageDiv = document.getElementById("user-auth-message");
                messageDiv.classList.add("text", "text-danger");
                messageDiv.textContent = result.message || "Erreur lors de l'inscription.";
                messageDiv.style.display = "block";
            }
        } catch (error) {
            console.error('Erreur réseau :', error);
            const messageDiv = document.getElementById("user-auth-message");
            messageDiv.classList.add("text", "text-danger");
            messageDiv.textContent = "Erreur de communication avec le serveur.";
            messageDiv.style.display = "block";
        }
    });

    // Formulaire mot de passe oublié
    const forgotForm = document.getElementById('forgotPasswordForm');

    forgotForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(forgotForm);
        const email = formData.get('email');

        try {
            const response = await fetch('rqt_password_reset.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            const messageDiv = document.getElementById("user-auth-message");

            if (result.success) {
                messageDiv.classList.remove("text-danger");
                messageDiv.classList.add("text", "text-success");

                forgotForm.reset(); // vider les champs de form mot de passe oublié

                switchTab('login-tab'); // Afficher le formulaire de connexion
                document.getElementById('login-nav').classList.add('show', 'active');
            } else {
                messageDiv.classList.remove("text-success");
                messageDiv.classList.add("text", "text-danger");
            }

            messageDiv.textContent = result.message;
            messageDiv.style.display = "block";
        } catch (error) {
            console.error("Erreur réseau :", error);
            const messageDiv = document.getElementById("user-auth-message");
            messageDiv.classList.add("text", "text-danger");
            messageDiv.textContent = "Erreur réseau. Veuillez réessayer plus tard.";
            messageDiv.style.display = "block";
        }
    });

});

// Vérifier une demande de réinitialisation de mot de passe
const urlParams = new URLSearchParams(window.location.search);
const userId = urlParams.get('id');
const userState = urlParams.get('state');
const activationCode = urlParams.get('activation_code');

if (userId && userState === '0' && activationCode) {
    // Appel AJAX à notre nouveau fichier
    fetch(`rqt_password_reset_demand_check.php?id=${userId}&activation_code=${activationCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Affiche le bloc de réinitialisation
                document.getElementById('content').style.display = 'none';
                document.getElementById('pageResetPassword').style.display = 'block';
            } else {
                showFloatingMessage(data.message, "danger");
                location.href = '/';
            }
        })
        .catch(error => {
            console.error('Erreur de vérification :', error);
            showFloatingMessage("Erreur réseau lors de la vérification.", "danger");
            location.href = '/';
        });
}

// Formulaire de réinitialisation de mot de passe - Gestion
document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('id');

    if (newPassword !== confirmPassword) {
        showFloatingMessage("Les mots de passe ne correspondent pas.", "danger");
        return;
    }

    fetch('rqt_password_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            id: userId,
            new_password: newPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFloatingMessage("Mot de passe réinitialisé avec succès !", "success");
            location.href = '/'; // Redirection après succès
        } else {
            showFloatingMessage(data.message, "danger");
        }
    })
    .catch(error => console.error('Erreur lors de la mise à jour :', error));
});



// Formulaire Mise à jour des informations utilisateur
// Ciblage du formulaire
const profileFormElement = document.getElementById("profile-data-form");

profileFormElement.addEventListener('submit', function (e) {
    e.preventDefault(); // Empêcher l'envoi classique

    // Récupération des champs
    const formData = new FormData(profileFormElement);

    // Cacher les messages précédents
    const errorDiv = document.getElementById("profile-edit-message");
    errorDiv.style.display = "none";
    errorDiv.textContent = "";

    // Envoyer les données
    fetch('rqt_user_info_update.php', {
        method: 'POST',
        body: formData
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.status === 'ok') {
            const successDiv = document.getElementById("profile-edit-message");
            successDiv.classList.remove("text-danger", "text-success");
            successDiv.classList.add("text-success");
            successDiv.textContent = "Profil mis à jour avec succès.";
            successDiv.style.display = "block";

        } else {
            errorDiv.textContent = data.message || "Une erreur s'est produite.";
            errorDiv.classList.remove("text-danger", "text-success");
            errorDiv.classList.add("text-danger");
            errorDiv.style.display = "block";
        }
    })
    .catch(error => {
        console.error("Erreur lors de la mise à jour du profil :", error);
        errorDiv.textContent = "Erreur lors de l'envoi des données.";
        errorDiv.classList.remove("text-danger", "text-success");
        errorDiv.classList.add("text-danger");
        errorDiv.style.display = "block";
    });
});


// Afficher/cacher mot de passe
// Fonctionnalité d'affichage/masquage du mot de passe
document.querySelectorAll('.toggle-password').forEach(item => {
    item.addEventListener('click', function() {
        const target = document.getElementById(this.dataset.target);
        if (target.type === "password") {
            target.type = "text";
            this.textContent = "🙈"; // Icône pour caché
        } else {
            target.type = "password";
            this.textContent = "👁️"; // Icône pour visible
        }
    });
});