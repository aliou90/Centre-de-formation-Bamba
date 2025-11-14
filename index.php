<?php 
// Activation de la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- SCRIPT PHP RÉCUPÉRER LES LIVRES ET PAGES(IMAGES) -->
<?php require_once __DIR__.'/books_get.php'; ?>
<?php 
// VARIABLES GLOBALES
define('APP_NAME', 'Plateforme de Formation Bamba');
define('BASE_URL', 'http://localhost/formation_bamba/');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    
    <!-- JQUERY -->
    <script src="assets/js/jquery.min.js"></script>

    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <script src="assets/js/all.min.js"></script>

    <!-- INTRO.JS -->
    <link rel="stylesheet" href="assets/css/introjs.min.css">
    <script src="assets/js/introjs.min.js"></script>

    <!-- WaveSurfer lecteur audio personnalisé -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/6.6.3/wavesurfer.min.js"></script> -->
    <script src="./assets/js/wavesurfer.min.js"></script>

    <!-- STYLES PERSONNALISÉS -->
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- FICHIER MANIFEST POUR PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#000000">

</head>
<body>
    <header class="page-header col-10 d-flex flex-column align-items-center justify-content-center">
        <img src="./assets/images/logos/logo2.png" class="header-logo" alt="website icon">
        <h1 class="text-white"><?= APP_NAME ?></h1>
    </header>


    <button id="toggle-book-menu-btn">☰</button> <!-- Bouton pour afficher/cacher le menu -->

    <!-- Menu flottant - Liste des livres -->
    <aside id="book-list" style="margin-left: 0; margin-right: 0; padding-left: 0; padding-right: 0;">
        
        <div id="user-menu" class="d-flex justify-content-between align-items-center" style="padding: 10px; background-color: #001f3d; color: white;">
            <?php $fullname = $_SESSION['user']['fullname'] ?? 'Visiteur'; ?>
            <span id="userName" style="margin-left: 20px;"><?= htmlspecialchars($fullname); ?></span>
            <span>
                <!-- Ouvrir sous-menu utilisateur -->
                <span id="userIcon" class="" style="cursor:pointer; margin: 3px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                    </svg>
                </span>
                <!-- Sous Menu utilisateur -->
                <div id="user-dropdown" class="dropdown-menu show shadow user-dropdown-submenu" style="display: none; position: absolute; right: 10px; top: 60px; background-color: white; border-radius: 8px; overflow: hidden; z-index: 1000;">
                    <!-- Si l'utilisateur est connecté -->
                    <a id="user-profile-link" class="dropdown-item user-dropdown-submenu-item" style="cursor: pointer;">Mon Compte</a>
                    <a id="user-book-link" class="dropdown-item user-dropdown-submenu-item" style="cursor: pointer;">Mes livres</a>
                    <a id="user-logout-link" class="dropdown-item text-danger user-dropdown-submenu-item" style="cursor: pointer;">Se déconnecter</a>
                    <!-- Si l'utilisateur n'est pas connecté -->
                    <a id="user-login-link" class="dropdown-item text-success user-dropdown-submenu-item" style="cursor: pointer;">Se connecter</a>
                </div>

                <!-- Afficher tous les livres -->
                <span id="show-all-book" class="" style="cursor:pointer; margin: 3px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
                    <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                    </svg>
                </span>
            </span>
        </div>

        <h3 id="bookMenuTitle" style="padding-left: 10px;">Livres disponibles</h3>
        <!-- Barre de recherche -->
        <div id="bookSearchForm" class="position-relative" style="margin-bottom: 10px;">
            <div class="search-input-group">
                <input
                    type="text"
                    id="bookSearch"
                    class="form-control"
                    placeholder="Rechercher un livre..."
                    style="
                        border-radius: 8px;
                        padding-inline-start: 2.5rem;
                        padding-inline-end: 1.2rem;
                        padding-block: 0.75rem; /* ↑↓ */
                        font-size: 1rem;
                        text-align: start;
                        border:#5A5AFF 1px solid;
                        margin-bottom: 10px;
                    "
                    oninput="adjustSearchDirection(this)"
                >
                <span
                    class="search-icon position-absolute"
                    style="
                        inset-inline-start: 15px;
                        top: 20%;
                        transform: translateY(-50%);
                        color: #aaa;
                        font-size: 1rem;
                    "
                >🔍</span>
            </div>

            <!-- Filtres Langues et Types -->
            <div id="searchFilterBtnsGroup" class="d-flex flex-wrap gap-2 mb-3">
                <!-- Langues -->
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <label class="filter-tag">
                        <input type="checkbox" name="lang[]" value="ar" hidden>
                        Arabe
                    </label>
                    <label class="filter-tag">
                        <input type="checkbox" name="lang[]" value="en" hidden>
                        Anglais
                    </label>
                    <label class="filter-tag">
                        <input type="checkbox" name="lang[]" value="fr" hidden>
                        Français
                    </label>
                    <label class="filter-tag">
                        <input type="checkbox" name="lang[]" value="wo" hidden>
                        Wolof
                    </label>
                </div>

                <!-- Types -->
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <label class="filter-tag">
                        <input type="checkbox" name="type[]" value="qr" hidden>
                        Quran
                    </label>
                    <label class="filter-tag">
                        <input type="checkbox" name="type[]" value="xs" hidden>
                        Xassida
                    </label>
                    <label class="filter-tag">
                        <input type="checkbox" name="type[]" value="xm" hidden>
                        Xam xam
                    </label>
                </div>
            </div> <!-- Fin Filtres Langues et Types -->
        </div> <!-- Fin Barre de recherche -->

        <!-- Liste de tous les livres -->
        <section class="all-book-list-content book-list-content">
            <?php foreach ($langGroups as $lang => $group): ?>
                <?php if (!empty($group['books'])): ?>
                    <div class="book-list-content-group">
                        <h5 class="book-language-header">
                            <?= $group['label'] ?>
                        </h5>
                        <ul class="list-group list-books" style="margin: 0;">
                            <?php foreach ($group['books'] as $title => $data): ?>
                                <li class="list-group-item" style="position: relative;">
                                    <div class="book-item" 
                                        data-latin="<?= htmlspecialchars($title) ?>" 
                                        data-arabic="<?= htmlspecialchars($data['config']['nomArabe'] ?? '') ?>"
                                        data-lang="<?= htmlspecialchars($data['config']['lang'] ?? '') ?>" 
                                        data-trans="<?= htmlspecialchars($data['config']['trans'] ?? '') ?>"
                                        data-type="<?= htmlspecialchars($data['config']['type'] ?? '') ?>" 
                                        data-author="<?= htmlspecialchars($data['config']['auteur'] ?? '') ?>" 
                                        data-translator="<?= htmlspecialchars($data['config']['traducteur'] ?? '') ?>"
                                        data-narrator="<?= htmlspecialchars($data['config']['voix'] ?? '') ?>" 
                                        onclick="loadBook('<?= addslashes($title) ?>')"
                                    > 
                                        <?= htmlspecialchars($title) ?>
                                        <?php if (!empty($data['config']['nomArabe'])): ?>
                                            <div class="bookName bookNameArabic" dir="rtl" style="direction: rtl; text-align: right; font-size: 0.8rem; color: #555;">
                                                <?= htmlspecialchars($data['config']['nomArabe']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-control-group" style="position: absolute; bottom: 5px; left: 15px;">
                                        <span class="book-badge follow-badge" title="Suivre ce livre">Suivre</span>
                                        <span class="book-badge static-badge in-progress-badge">En cours</span>
                                        <span class="book-badge static-badge finished-badge">Terminé</span>
                                        <span class="book-badge remove-badge" title="Retirer ce livre">Retirer</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>

        <!-- Liste des livres de l'utilisateur connecté -->
        <section class="user-book-list-content book-list-content" style="display: none;"></section>

        <!-- Section Profil utilisateur -->
        <section class="user-profile-form" id="profile-form" style="display: none; padding: 10px;">
        <div id="account-message" class="text-center" style="display: none;"></div>

            <!-- Carte d'informations utilisateur -->
            <div id="profile-info-card" class="card" style="max-width: 500px; margin: 20px auto;">
                <div class="card-body text-center">
                    <!-- Image de profil -->
                    <img src="./assets/images/logos/logo.png" class="rounded-circle" alt="Image de profil" id="profile-image" style="width: 100px; height: 100px; object-fit: cover;">
                    <!-- Informations utilisateur -->
                    <h5 class="card-title" id="profile-fullname">Nom</h5>
                    <!-- Informations de contact alignées à gauche avec bootstrap -->
                    <div class="text-start">
                        <p id="profile-email" class="mb-1"><strong>Email:</strong> <span id="profile-email">email@example.com</span></p>
                        <p id="profile-phone" class="mb-1"><strong>Téléphone:</strong> <span id="profile-phone">+123456789</span></p>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <a href="#" id="edit-profile-btn" class="btn btn-outline-primary me-2">Modifier mon compte</a>
                        <a href="#" id="view-badges-btn" class="btn btn-outline-success">Voir mes Badges</a>
                    </div>
                </div>
            </div>
            <!-- Le Formulaire de modification du profil est en bas (en dehors du sidebar) -->
        </section> <!-- Fin Section Profil utilisateur -->

        <!-- Section d'athentification -->
        <section class="user-auth-section" style="display: none;"> 
            <div id="user-auth-message" class="text-center" style="display: none;"></div>
            <div id="auth-section" style="color: white; background-color: #130a4d; padding: 0 auto; max-width: 400px; margin: 20px auto;">
                <ul class="nav nav-tabs" id="auth-tabs">
                    <li class="nav-item flex-grow-1">
                        <a class="nav-link active" id="login-nav" data-bs-toggle="tab" href="#login-tab">Se connecter</a>
                    </li>
                    <li class="nav-item flex-grow-1">
                        <a class="nav-link" id="register-nav" data-bs-toggle="tab" href="#register-tab">S'inscrire</a>
                    </li>
                </ul>

                <div class="tab-content" style="background-color: #222; padding: 5px 0; border-radius: 5px;">
                    <!-- Formulaire de connexion -->
                    <div id="login-tab" class="tab-pane fade show active">
                        <form id="loginForm">
                            <input type="text" class="allFormInput" name="login" id="login" placeholder="Email ou Téléphone" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">

                            <div style="position: relative;">
                                <input type="password" class="allFormInput" name="password" id="login_password" placeholder="Mot de passe" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">
                                <span class="toggle-password" data-target="login_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
                            </div>

                            <button type="submit" style="width: 100%; padding: 10px; border-radius:20px; background-color: #5A5AFF; color: white; border: none; cursor: pointer;">Se connecter</button>
                            <a href="#" id="forgotPassword" style="display: block; margin: 10px; color: #5A5AFF; text-decoration:none; ">🎯 Mot de passe oublié ?</a>
                        </form>
                    </div>

                    <!-- Formulaire d'inscription -->
                    <div id="register-tab" class="tab-pane fade">
                        <form id="registerForm">
                            <input type="text" class="allFormInput" name="fullname" placeholder="Nom Complet" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">
                            <input type="email" class="allFormInput" name="email" placeholder="Email" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">
                            <input type="text" class="allFormInput" name="phone" placeholder="Téléphone" style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">

                            <div style="position: relative;">
                                <input type="password" class="allFormInput" name="password" id="password" placeholder="Mot de passe" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">
                                <span class="toggle-password" data-target="password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
                            </div>

                            <div style="position: relative;">
                                <input type="password" class="allFormInput" name="confirm_password" id="confirm_password" placeholder="Confirmer le mot de passe" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">
                                <span class="toggle-password" data-target="confirm_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
                            </div>

                            <button type="submit" style="width: 100%; padding: 10px; border-radius:20px; background-color: #5A5AFF; color: white; border: none; cursor: pointer;">S'inscrire</button>
                        </form>
                    </div>

                    <!-- Formulaire mot de passe oublié -->
                    <div id="forgot-password-tab" class="tab-pane fade">
                        <form id="forgotPasswordForm">
                            <p style="color: white; margin-bottom: 10px;">Entrez votre adresse email pour réinitialiser votre mot de passe.</p>
                            <input type="email" class="allFormInput" name="email" id="recover-email" placeholder="Email" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: none;">

                            <button type="submit" style="width: 100%; padding: 10px; border-radius:20px; background-color: #5A5AFF; color: white; border: none; cursor: pointer;">Envoyer</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        
    </aside> <!-- Fin Menu flottant - Liste des livres -->
    <main id="content"> <!-- Debut Contenu principal -->
        <!-- Contenu principal -->
        <article id="book-content">
            <section id="carouselExample" class="carousel slide" data-bs-ride="false" data-bs-interval="false" data-bs-wrap="false" data-bs-pause="true">
                <!-- Contenu Intérieur du carousel -->
                <div class="carousel-inner d-block w-100" id="carousel-images">
                    <!-- Les images des livres seront chargées ici -->

                        <!-- Image par défaut -->
                    <div class="carousel-item active">
                        <img src="./assets/images/covers/cover1.png" class="d-block w-100" alt="Page par défaut">
                    </div>
                </div>     
                
                <!-- Boutons Contrôles du carrousel (transparents sur l'images) -->
                <!-- <a class="carousel-control-prev" role="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" role="button" data-bs-target="#carouselExample" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>            -->
            </section>
            <section>
                <!-- <div class="position-relative">  style="min-height: 80px;"
                    <div class="book-meta text-start text-white small p-3" 
                    style="line-height: 1.2; background: rgba(0, 0, 0, 0.5); border-radius: 8px; overflow: hidden; padding: 6px 10px;">
                        <p id="book-author" class="mb-1"></p>
                        <p id="book-translator" class="mb-1"></p>
                        <p id="book-narrator" class="mb-0"></p>
                    </div>
                </div> -->
            </section>
        </article>
    </main> <!-- Fin Contenu principal -->

    <!-- Message flottant dédié à  l'affichage des messages d'erreur ou de succès -->
    <div id="floating-message" style="display: none;"></div>

    <!-- Modal pour édition du profil -->
    <div class="modal fade" id="profile-edit-modal" tabindex="-1" aria-labelledby="profileEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="profileEditModalLabel">Modifier mon Profil</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
        <div id="profile-edit-message" class="text-center" style="display: none;"></div>
            <form id="profile-data-form">
                <div class="mb-2">
                    <label>Nom complet *</label>
                    <input type="text" name="fullname" class="form-control" value="">
                </div>

                <div class="mb-2">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" value="" disabled>
                </div>

                <div class="mb-2">
                    <label>Téléphone *</label>
                    <input type="text" name="phone" class="form-control" value="">
                </div>

                <div class="mb-2" style="position: relative;">
                    <label>Mot de Passe *</label>
                    <input type="password" name="userOldPassword" id="userOldPassword" class="form-control" required>
                    <span class="toggle-password" data-target="userOldPassword" style="position: absolute; right: 10px; top: 66%; transform: translateY(-50%); cursor: pointer;">👁️</span>
                </div>

                <div class="mb-2" style="position: relative;">
                    <label>Changer mot de Passe</label>
                    <input type="password" name="userNewPassword" id="userNewPassword" class="form-control">
                    <span class="toggle-password" data-target="userNewPassword" style="position: absolute; right: 10px; top: 66%; transform: translateY(-50%); cursor: pointer;">👁️</span>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">Mettre à jour</button>
            </form>
        </div>
        </div>
    </div>
    </div>
    <!-- Fin Modal pour édition du profil -->
    <!-- Navigation + Audio -->
    <article class="audio-navigation-container">
        <button id="toggle-audio-panel" class="toggle-arrow">
            <span>&#9650;</span> <!-- ↑ -->
        </button>

        <div class="waveform-wrapper">
            <div class="waveform-bar-wrapper">
                <!-- Barre audio -->
                <div id="waveform-container" class="waveform position-relative"></div>

                <!-- Comteur de temps -->
                <div class="waveform-times" dir="ltr">
                    <span id="current-time">0:00</span> / <span id="total-time">0:00</span>
                </div>
            </div>

            <!-- Ligne boutons navigation/lecture -->
            <div class="waveform-controls d-flex justify-content-between align-items-center flex-wrap">
                <!-- Page précédente -->
                <button class="btn btn-outline-secondary btn-sm sm-carousel-control-prev" title="Page Précédente" data-bs-target="#carouselExample" data-bs-slide="prev">
                    ◀
                </button>

                <!-- Contrôles audio -->
                <div class="d-flex align-items-end gap-2">
                    <div class="d-flex flex-column align-items-center">
                        <button id="rewind" class="btn btn-light btn-sm" title="Reculer de 5s">⏪</button>
                        <small id="rewindLabel" style="font-size: 0.5rem; line-height: 1; margin-top: 2px;">-5s</small>
                    </div>
                    
                    <div class="d-flex flex-column align-items-center">
                        <button id="play-pause" class="btn btn-primary btn-sm" title="Lecture/Pause">⏯️</button>
                    </div>
                    
                    <div class="d-flex flex-column align-items-center">
                        <button id="forward" class="btn btn-light btn-sm" title="Avancer de 5s">⏩</button>
                        <small id="forwardLabel" style="font-size: 0.5rem; line-height: 1; margin-top: 2px;">+5s</small>
                    </div>
                </div>

                <!-- Page suivante -->
                <button class="btn btn-outline-secondary btn-sm sm-carousel-control-next" title="Page Suivante" data-bs-target="#carouselExample" data-bs-slide="next">
                    ▶
                </button>
            </div>

            <!-- Ligne  Vitesse et Menu -->
            <div class="waveform-speed d-flex justify-content-between align-items-center mt-2 px-2">
                <!-- Vitesse -->
                <div class="d-flex flex-column align-items-center" style="width: 60px;">
                    <select id="playback-rate" class="form-select form-select-sm w-auto text-center">
<option value="0.75">0.75x</option>
                        <option value="0.8">0.8x</option>
                        <option value="0.9">0.9x</option>
                        <option value="1" selected>1x</option>
                        <option value="1.10">1.10x</option>
                        <option value="1.20">1.20x</option>
                        <option value="1.25">1.25x</option>
<option value="1.50">1.50x</option>
                    </select>
                    <small style="font-size: 0.6rem; line-height: 1; margin-top: 2px;">Vitesse</small>
                </div>
                
                <!-- Bouton d'activation de lecture auto avec icône -->
                <div class="btn-group" title="Lecture automatique" style="width: 50px;">
                    <label class="btn btn-primary" id="autoPlayLabel">
                        <input type="checkbox" name="auto-play" id="autoPlayBtn" autocomplete="off" hidden>
                        <span id="autoPlayIcon" class="autoplay-icon">⏭️</span>
                        <small style="font-size: 0.6rem; display: block; line-height: 1;">Auto</small>
                    </label>
                </div>

                <!-- Bouton Basculement Paysage/Portrait (caché en petit écran) -->
                <button id="toggle-orientation" class="btn btn-sm btn-outline-secondary text-center p-1 d-none d-md-inline-block" title="Bascule Portrait/Paysage" style="width: 50px;">
                    <div>
                        <div style="font-size: 1.2rem;">🖼️</div>
                        <small style="font-size: 0.6rem; display: block; line-height: 1;">Vue</small>
                    </div>
                </button>

                <!-- Bouton Menu Chapitre -->
                <button id="chapter-menu-toggle" class="btn btn-sm btn-outline-secondary text-center p-1" title="Liste des chapitres" style="width: 50px;">
                <div>
                    <div style="font-size: 1.2rem;">🗂️</div>
                    <small id="chapterLabel" style="font-size: 0.6rem; display: block; line-height: 1;">Chap</small>
                </div>
                </button>

                <!-- Bouton Menu Page -->
                <button id="page-menu-toggle" class="btn btn-sm btn-outline-secondary text-center p-1" title="Liste des pages" style="width: 50px;">
                <div>
                    <div style="font-size: 1.2rem;">📖</div>
                    <small id="pageLabel" style="font-size: 0.6rem; display: block; line-height: 1;">Page</small>
                </div>
                </button>
            </div>
        </div>
    </article>
    <!-- Sidebar Pages -->
    <div id="page-sidebar" class="page-sidebar">
        <div class="page-sidebar-content p-3">
            <h5 class="text-center mb-3">Pages du livre</h5>
            <ul id="page-list" class="list-unstyled"></ul>
        </div>
    </div>

    <!-- Sidebar Chapitres -->
    <div id="chapter-sidebar" class="chapter-sidebar">
        <div class="chapter-sidebar-content p-3">
            <h5 class="text-center mb-3">Chapitres du livre</h5>
            <ul id="chapter-list" class="list-unstyled"></ul>
        </div>
    </div>

    <!-- Page Réinitialisation de mot de passe - Si demandée et vérifiée -->
    <!-- PAGE DE RÉINITIALISATION -->
    <div id="pageResetPassword" style="display: none; color: white; background-color: #130a4d; padding: 20px; padding-top: 250px; max-width: 400px; margin: 20px auto;">
        <h3>Réinitialisation du mot de passe</h3>
        <p class="text-info text-center">Saisissez et confirmez votre noveau mot de passe s'il vous plaît</p>
        <form id="resetPasswordForm">
        <!-- Mot de passe avec visibilité -->
        <div style="position: relative;">
            <input type="password" class="allFormInput" name="password" id="newPassword" placeholder="Nouveau mot de passe" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: 1px solid #444;">
            <span class="toggle-password" data-target="newPassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
        </div>

        <!-- Confirmation du mot de passe avec visibilité -->
        <div style="position: relative;">
            <input type="password" class="allFormInput" name="confirm_password" id="confirmPassword" placeholder="Confirmer le mot de passe" required style="width: 100%; padding: 10px; margin: 5px 0; background-color: #333; color: white; border: 1px solid #444;">
            <span class="toggle-password" data-target="confirmPassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
        </div>

        <button type="submit" style="width: 100%; padding: 10px; background-color: #5A5AFF; color: white; border: none; cursor: pointer;">Réinitialiser</button>
        </form>
    </div>
    <!-- FIN PAGE DE RÉINITIALISATION -->
        
    <!-- Footer avec infos développeur et contact -->
    <footer class="footer text-black text-center text-lg-start">
        <div class="text-left p-3 shadow">
            Copyright(&copy;): Aliou Mbengue - TECH-JAMM<br>
            Contact: 
            <span class=" d-inline-block">
                <img src="assets/images/icons/icon-whatsapp.png" alt="whatsapp" class="icon rounded-circle pb-1" width="18">
                <a href="https://api.whatsapp.com/send?phone=221764550358" class="text-success text-decoration-none">+221764550358</a>
            </span>
            -
            <span class=" d-inline-block">
                <img src="assets/images/icons/icon-mail.png" alt="mail" class="icon rounded-circle pb-1" width="18">
                <a href="mailto:tech.jamm.corp@gmail.com" class=" text-primary text-decoration-none">tech.jamm.corp@gmail.com</a>
            </span>

            <div class="d-block">
            Soutenir le projet par 
            -
            <span class=" d-inline-block">
                <img src="assets/images/icons/wave.png" alt="Wave Mobile" class="icon rounded-circle pb-1" width="18">
                <img src="assets/images//icons/om.png" alt="Orange Money" class="icon rounded-circle pb-1" width="18">
                <a href="tel:+221776647080" class="text-primary text-decoration-none">+221-77-664-70-80</a>
                </span>
            </div>
        </div>
    </footer>

<script>
    let books = <?= json_encode($books) ?>; 
</script>

<!-- SCRIPT GESTION LECTURE DE PAGE (AUDIO) -->
<script src="./assets/js/script.js"></script>

<!-- Inclure Font Awesome si ce n'est pas déjà fait -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Références du Service Worker -->
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
        .then((registration) => {
            console.log('Service Worker enregistré avec succès:', registration);
        })
        .catch((error) => {
            console.error('Erreur lors de l\'enregistrement du Service Worker:', error);
        });
}
</script>
</body>
</html>
