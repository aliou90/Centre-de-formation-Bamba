<?php
session_start();
define('APP_NAME', 'Plateforme de Formation Bamba');
define('BASE_URL', 'http://localhost/formation_bamba/');
$adminPassword = '#Radmin';

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if (hash_equals($adminPassword, (string) $_POST['admin_password'])) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: admin.php');
        exit;
    }

    $adminAuthError = 'Mot de passe incorrect.';
}

if (empty($_SESSION['admin_authenticated'])):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Accès Admin</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
            font-family: sans-serif;
            color: #102033;
        }

        .admin-lock-card {
            width: min(420px, calc(100vw - 32px));
            background: rgba(255, 255, 255, 0.96);
            border-radius: 14px;
            padding: 28px 24px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.22);
        }

        .admin-lock-card h1 {
            margin: 0 0 10px;
            font-size: 1.35rem;
        }

        .admin-lock-card p {
            margin: 0 0 18px;
            color: #4b5563;
        }

        .admin-lock-card label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .admin-lock-card input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 1rem;
            margin-bottom: 14px;
        }

        .admin-lock-card button {
            width: 100%;
            border: none;
            border-radius: 10px;
            background: #1d4ed8;
            color: white;
            padding: 12px 14px;
            font-size: 1rem;
            cursor: pointer;
        }

        .admin-lock-error {
            margin-bottom: 12px;
            color: #b91c1c;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <form class="admin-lock-card" method="post" action="admin.php">
        <h1>Accès Administration</h1>
        <p>Entrez le mot de passe pour ouvrir `admin.php`.</p>
        <?php if (!empty($adminAuthError)): ?>
            <div class="admin-lock-error"><?= htmlspecialchars($adminAuthError) ?></div>
        <?php endif; ?>
        <label for="admin_password">Mot de passe</label>
        <input type="password" id="admin_password" name="admin_password" required autofocus>
        <button type="submit">Entrer</button>
    </form>
</body>
</html>
<?php
exit;
endif;

$bookDir = __DIR__.'/assets/books/';
$books = [];

if (is_dir($bookDir)) {
    foreach (scandir($bookDir) as $book) {
        if ($book !== '.' && $book !== '..' && is_dir($bookDir . $book)) {
            $books[] = $book;
        }
    }
}
?>
<?php
if (!empty($_GET['selected_book'])) {
    // Si un livre est sélectionné, on charge ses informations
    $selectedBook = $_GET['selected_book'];
    $configPath = "$bookDir$selectedBook/config/chapitres.json";
    $configPath = "$bookDir$selectedBook/config/config.json";

    $chapitres = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
    $lang = file_exists($configPath) ? json_decode(file_get_contents($configPath), true)['lang'] ?? '' : '';
    $trans = file_exists($configPath) ? json_decode(file_get_contents($configPath), true)['trans'] ?? '' : '';
    $type = file_exists($configPath) ? json_decode(file_get_contents($configPath), true)['type'] ?? '' : '';
    $arabicName = file_exists($configPath) ? json_decode(file_get_contents($configPath), true)['nomArabe'] ?? '' : '';
    $author = file_exists($configPath) ? json_decode(file_get_contents($configPath), true)['auteur'] ?? '' : '';
    $translator = file_exists($configPath) ? json_decode(file_get_contents($configPath), true)['traducteur'] ?? '' : '';
    $narrator = file_exists($configPath) ? json_decode(file_get_contents($configPath), true)['voix'] ?? '' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Admin - Configuration des Livres</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f7f7f7; }
        select, input, textarea, button { margin: 5px 0; padding: 8px; font-size: 1rem; }
        .config-area { background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .chapter-block { border-left: 2px solid #ccc; margin-left: 20px; padding-left: 10px; margin-top: 10px; }
        .sub-block { margin-left: 20px; }
        label { font-weight: bold; display: block; margin-top: 10px; }

        /* Conteneur horizontal pour les éléments de formulaire */
        .floating-inline {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        /* Groupe flottant input + label */
        .form-group {
            position: relative;
            flex: 0 1 320px;
            box-sizing: border-box;
        }

        /* Style des champs input et select */
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 10px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            box-sizing: border-box;
        }

        /* Label flottant */
        .form-group label {
            position: absolute;
            top: 35%;
            left: 12px;
            transform: translateY(-50%);
            background: white;
            padding: 0 4px;
            font-size: 1rem;
            color: #777;
            pointer-events: none;
            transition: all 0.2s ease;
        }

        /* Activation du label flottant */
        .form-group input:focus + label,
        .form-group select:focus + label,
        .form-group input:not(:placeholder-shown) + label,
        .form-group select:valid + label {
            top: -8px;
            left: 8px;
            font-size: 0.75rem;
            color: #333;
        }

        /* Masquer visuellement le placeholder (nécessaire pour :placeholder-shown) */
        .form-group input::placeholder {
            color: transparent;
        }

        /* Style des boutons dans tous les formulaires */
        form button {
            padding: 10px 15px;
            font-size: 0.95rem;
            border: none;
            background: #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        form button:hover {
            background: #bbb;
        }


        .form-group.small {
            position: relative;
            display: inline-block;
            margin: 5px 10px 15px 0;
            vertical-align: top;
            box-sizing: border-box;
        }

        .form-group.small input {
            padding: 10px 8px;
            font-size: 0.95rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            box-sizing: border-box;
        }

        .form-group.small label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            background: white;
            padding: 0 4px;
            color: #777;
            font-size: 0.9rem;
            pointer-events: none;
            transition: all 0.2s ease;
        }

        .form-group.small input:focus + label,
        .form-group.small input:not(:placeholder-shown) + label,
        .form-group.small input:not([value=""]) + label {
            top: -8px;
            left: 8px;
            font-size: 0.75rem;
            color: #333;
        }

        .form-group.small input::placeholder {
            color: transparent;
        }


        body {
            position: relative;
        }

        .logout-btn {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 1.5rem;
            text-decoration: none;
            color: #333;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            opacity: 0.7;
        }

    </style>
</head>
<body>

<a href="admin.php?logout=1" class="logout-btn" title="Se déconnecter">🔚</a>

<h2>📚 Interface d'administration des livres</h2>

<!-- Formulaire de création d'un nouveau livre -->
<!-- Formulaire de création d'un nouveau livre -->
<form id="newBookForm" onsubmit="event.preventDefault(); createNewBook();" style="display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap;">

    <div class="form-group">
        <input type="text" id="newBookName" name="newBookName" placeholder=" " required oninput="adjustSearchDirection(this)">
        <label for="newBookName">➕ Ajouter un nouveau livre :</label>
    </div>

    <button type="submit">Créer</button>
    <span id="newBookMsg" style="margin-left: 10px;"></span>

</form>


<!-- Formulaire de sélection d'un livre existant -->
<div class="select-area">
    <h3>📖 Configuration des livres existants</h3>
    <p>Choisissez un livre pour le configurer :</p>
    <form id="bookForm" method="get" class="floating-inline">
        <div class="form-group">
            <select name="selected_book" id="selected_book" onchange="this.form.submit()" required>
                <option value="" disabled <?= empty($_GET['selected_book']) ? 'selected' : '' ?>>-- Choisir un livre --</option>
                <?php foreach ($books as $book): ?>
                    <option value="<?= htmlspecialchars($book) ?>" <?= isset($_GET['selected_book']) && $_GET['selected_book'] === $book ? 'selected' : '' ?>>
                        <?= htmlspecialchars($book) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="selected_book">📚 Livre *</label>
        </div>

        <div class="form-group">
            <input type="text" id="book_title" name="book_title" value="<?= isset($_GET['selected_book']) ? htmlspecialchars($_GET['selected_book']) : '' ?>" required oninput="adjustSearchDirection(this)">
            <label for="book_title">✏️ Nouveau Titre *</label>
        </div>

        <button type="button" onclick="renameBook()">✏️ Renommer</button>
        <button type="button" onclick="deleteBook()">🗑️ Supprimer</button>

        <span id="bookActionMsg" style="margin-left: 10px;"></span>
                <!-- Sous section d'information sur la méthode de renommage visible pour les admins -->
                <em>Note : Le titre du livre est renommé en ajoutant automatiquement la langue d'écriture et de traduction entre parenthèses. Par exemple : "Livre de Test (fr-ar)".</em>
    </form>
</div>

<!-- Formulaire d'importation de fichiers -->
<?php if (isset($_GET['selected_book']) && is_dir(__DIR__ . "/assets/books/" . $_GET['selected_book'])): ?>
    <div class="config-area">
        <h3>📂 Importer des fichiers pour le livre : <em><?= htmlspecialchars($_GET['selected_book']) ?></em></h3>
        <form id="uploadForm" action="admin_upload_files.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="book" value="<?= htmlspecialchars($_GET['selected_book']) ?>">

            <label for="images">📷 Images :</label>
            <input type="file" name="images[]" multiple accept="image/*"><br>

            <label for="audios">🎵 Audios :</label>
            <input type="file" name="audios[]" multiple accept="audio/*"><br>

            <button type="submit">⬆️ Importer</button>
        </form>
        <span id="uploadMsg" style="margin-left: 10px;"></span>
    </div>
<?php endif; ?>

<?php
if (!empty($_GET['selected_book'])) {
    // Variable utiles définies un peu plus en haut (Voir avant le Doctype)
    ?>

    <div class="config-area">
        <h3>📖 Configuration du livre : <em><?= htmlspecialchars($selectedBook) ?></em></h3>

    <!-- Choix de la langue -->
    <div class="lang-section">
        <label for="lang">Langues du livre </label>
        <div class="floating-inline">
            <div class="form-group">
                <select id="lang" required>
                    <option value="" disabled selected hidden></option>
                    <option value="fr" <?= $lang === 'fr' ? 'selected' : '' ?>>Français</option>
                    <option value="ar" <?= $lang === 'ar' ? 'selected' : '' ?>>Arabe</option>
                    <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>Anglais</option>
                    <option value="wo" <?= $lang === 'wo' ? 'selected' : '' ?>>Wolof</option>
                </select>
                <label for="lang">Langue source</label>
            </div>

            <div class="form-group">
                <select id="trans" required>
                    <option value="" disabled selected hidden></option>
                    <option value="fr" <?= $trans === 'fr' ? 'selected' : '' ?>>Français</option>
                    <option value="ar" <?= $trans === 'ar' ? 'selected' : '' ?>>Arabe</option>
                    <option value="en" <?= $trans === 'en' ? 'selected' : '' ?>>Anglais</option>
                    <option value="wo" <?= $trans === 'wo' ? 'selected' : '' ?>>Wolof</option>
                </select>
                <label for="trans">Langue de traduction</label>
            </div>

            <button onclick="saveLang()">💾 Sauvegarder la langue</button>
            <span id="saveLangMsg" style="margin-left: 10px;"></span>
        </div>

    </div>

    <!-- Choix du type xs/xm/qr -->
    <div class="type-section">
        <label for="lang">Catégorie du livre </label>
        <div class="floating-inline">
            <div class="form-group">
                <select id="type" required>
                    <option value="" disabled selected hidden></option>
                    <option value="xm" <?= $type === 'xm' ? 'selected' : '' ?>>Xam Xam</option>
                    <option value="xs" <?= $type === 'xs' ? 'selected' : '' ?>>Xassida</option>
                    <option value="qr" <?= $type === 'qr' ? 'selected' : '' ?>>Al Quran</option>
                </select>
                <label for="type">Catégorie</label>
            </div>

            <button onclick="saveType()">💾 Sauvegarder</button>
            <span id="saveTypeMsg" style="margin-left: 10px;"></span>
        </div>
    </div>

    <!-- Nom Arabe du livre -->
    <div class="arabic-name-section">
        <label for="arabic_book_name">Nom en arabe du livre </label>
        <div class="floating-inline">
            <div class="form-group">
                <input type="text" id="book_arabic_title" name="book_arabic_title" value="<?= htmlspecialchars($arabicName) ?>" oninput="adjustSearchDirection(this)">
                <label for="book_title">✏️ Titre en Arabe</label>
            </div>

            <button type="button" onclick="saveArabicName()">💾 Sauvegarder</button>
            <span id="bookArabicNameMsg" style="margin-left: 10px;"></span>
        </div>
    </div>

    <!-- Nom de l'auteur -->
    <div class="author-name-section">
        <label for="author_name">Nom de l'auteur du livre</label>
        <div class="floating-inline">
            <div class="form-group">
                <input type="text" id="author_name" name="author_name" value="<?= htmlspecialchars($author) ?>" oninput="adjustSearchDirection(this)">
                <label for="book_author">Auteur du Livre</label>
            </div>

            <button type="button" onclick="saveAuthorName()">💾 Sauvegarder</button>
            <span id="bookAuthorMsg" style="margin-left: 10px;"></span>
        </div>
    </div>

    <!-- Nom du traducteur -->
    <div class="translator-name-section">
        <label for="translator_name">Nom du traducteur du livre</label>
        <div class="floating-inline">
            <div class="form-group">
                <input type="text" id="translator_name" name="translator_name" value="<?= htmlspecialchars($translator) ?>" oninput="adjustSearchDirection(this)">
                <label for="book_translator">Traducteur du Livre</label>
            </div>

            <button type="button" onclick="saveTranslatorName()">💾 Sauvegarder</button>
            <span id="bookTranslatorMsg" style="margin-left: 10px;"></span>
        </div>
    </div>

    <!-- Nom du Narrateur -->
    <div class="narrator-name-section">
        <label for="narrator_name">Nom du narrateur (Voix)</label>
        <div class="floating-inline">
            <div class="form-group">
                <input type="text" id="narrator_name" name="narrator_name" value="<?= htmlspecialchars($narrator) ?>" oninput="adjustSearchDirection(this)">
                <label for="book_narrator">Narrateur du Livre</label>
            </div>

            <button type="button" onclick="saveNarratorName()">💾 Sauvegarder</button>
            <span id="bookNarratorMsg" style="margin-left: 10px;"></span>
        </div>
    </div>

<!-- Éditeur visuel des chapitres -->
<div id="chapters_editor">
    <h4>🧩 Gestion des chapitres et sous-chapitres</h4>
    <div id="chapters_container"></div>
    <button type="button" onclick="addChapter()">➕ Ajouter un chapitre</button>
    <br><br>
    <div>
        <input type="hidden" name="book" value="<?= htmlspecialchars($selectedBook) ?>">
        <input type="hidden" name="chapitres_json" id="chapitres_json">
        <!-- Bouton de sauvegarde dynamique -->
        <div>
            <button type="button" onclick="saveChapitres()">💾 Sauvegarder les chapitres</button>
            <span id="saveChaptersMsg" style="margin-left: 10px;"></span>
        </div>
    </div>
</div>

<script>
    const initialData = <?= json_encode($chapitres, JSON_UNESCAPED_UNICODE) ?>;

    function createInput(placeholder, value, className, type = "text", width = "100%") {
        const wrapper = document.createElement('div');
        wrapper.className = 'form-group small';

        const input = document.createElement('input');
        input.className = className;
        input.placeholder = placeholder;
        input.value = value ?? '';
        input.type = type;
        input.required = false;
        input.style.width = width;
        input.oninput = (e) => {
            adjustSearchDirection(e.target);
        };

        const label = document.createElement('label');
        label.textContent = placeholder;

        wrapper.appendChild(input);
        wrapper.appendChild(label);
        return wrapper;
    }

    function formatTime(value) {
        if (!value.includes(":")) return "00:00";

        let [min, sec] = value.split(":");

        // Si trop long, on ne garde que les deux premiers chiffres
        if (min.length > 4) min = min.slice(0, 2);
        if (sec.length > 4) sec = sec.slice(0, 2);

        let mm = parseInt(min, 10);
        let ss = parseInt(sec, 10);

        // Si ce n'est pas un nombre, retourner "00:00"
        if (isNaN(mm) || isNaN(ss)) return "00:00";

        // Si >= 60, on remet à 0
        if (mm >= 60) mm = 0;
        if (ss >= 60) ss = 0;

        return `${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
    }

    // Fonction pour créer un bloc de chapitre
    function createBlock(chapitre, depth = 0) {
        const container = document.createElement('div');
        container.className = 'chapter-block';
        container.style.marginLeft = `${depth * 20}px`;

        const chapitreInput = createInput("Numéro du chapitre", chapitre.chapitre, "chapter-field", "text", "100px");
        const titreInput = createInput("Titre", chapitre.titre, "title-field", "text", "200px");
        const pageInput = createInput("Page", chapitre.page, "page-field", "number", "80px");
        const debutInput = createInput("Début (mm:ss)", chapitre.debut, "debut-field", "text", "100px");

        // Écouteur pour formater le temps
        debutInput.querySelector('input').addEventListener("blur", (e) => {
            e.target.value = formatTime(e.target.value.trim());
        });


        // Bouton pour supprimer le chapitre
        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = "🗑️ Supprimer";
        deleteBtn.type = "button";
        deleteBtn.onclick = () => container.remove();

        // Bouton pour ajouter un sous-chapitre
        const addSubBtn = document.createElement('button');
        addSubBtn.textContent = "➕ Sous-chapitre";
        addSubBtn.type = "button";
        addSubBtn.onclick = () => {
            const parentNum = chapitreInput.querySelector('input').value.trim();

            if (!parentNum) {
                alert("⚠️ Le chapitre parent doit avoir un numéro avant d’ajouter un sous-chapitre.");
                return;
            }

            // Chercher tous les sous-chapitres déjà existants dans ce bloc
            const existingSubs = Array.from(container.querySelectorAll(':scope > .sub-block .chapter-field'))
                .map(input => input.value.trim())
                .filter(val => val.startsWith(parentNum + '.'));

            // Extraire les suffixes numériques (ex: de 3.2 → 2)
            const subNums = existingSubs.map(val => {
                const parts = val.split('.');
                return parts.length > 1 ? parseInt(parts[1]) : 0;
            }).filter(n => !isNaN(n));

            // Trouver le prochain numéro
            const nextSub = subNums.length ? Math.max(...subNums) + 1 : 1;

            const subNum = `${parentNum}.${nextSub}`;
            const sub = createBlock({ chapitre: subNum }, depth + 1);
            sub.classList.add('sub-block');
            container.appendChild(sub);
        };

        container.appendChild(chapitreInput);
        container.appendChild(titreInput);
        container.appendChild(pageInput);
        container.appendChild(debutInput);

        container.appendChild(deleteBtn);
        container.appendChild(addSubBtn);

        // Ajout récursif des sous-chapitres
        if (chapitre.sousChapitres) {
            chapitre.sousChapitres.forEach(sub => {
                const subBlock = createBlock(sub, depth + 1);
                subBlock.classList.add('sub-block');
                container.appendChild(subBlock);
            });
        }

        return container;
    }

    // Fonction pour ajouter un chapitre principal ou un sous-chapitre
    function addChapter() {
        const container = document.getElementById('chapters_container');
        const chapterFields = container.querySelectorAll('.chapter-field');

        // Récupère les numéros existants de chapitres principaux (sans ".")
        let maxNum = 0;
        chapterFields.forEach(input => {
            const val = input.value.trim();
            if (val && !val.includes('.')) {
                const num = parseInt(val);
                if (!isNaN(num) && num > maxNum) {
                    maxNum = num;
                }
            }
        });

        // Crée un nouveau numéro de chapitre
        const newChapNum = maxNum + 1;

        // Crée un nouveau bloc de chapitre
        const block = createBlock({ chapitre: String(newChapNum) });
        container.appendChild(block);
    }

    // Fonction pour sauvegarder les chapitres
    function saveChapitres() {
        const chapters = [];
        const chapterNumbers = new Set();
        const container = document.getElementById('chapters_container');
        const errorMsg = document.getElementById('saveChaptersMsg');

        function parseBlock(block, parentNum = null) {
            const chapitreVal = block.querySelector('.chapter-field').value.trim();
            const titreVal = block.querySelector('.title-field').value.trim();
            const pageVal = parseInt(block.querySelector('.page-field').value) || 1;
            const debutVal = block.querySelector('.debut-field').value.trim() || "00:00";

            // Validation : numéro non vide
            if (!chapitreVal) {
                throw new Error("❌ Un chapitre a un numéro vide.");
            }

            // Validation : doublons
            if (chapterNumbers.has(chapitreVal)) {
                throw new Error(`❌ Le chapitre "${chapitreVal}" est en double.`);
            }

            // Validation : bon référencement des sous-chapitres
            if (parentNum && !chapitreVal.startsWith(parentNum + ".")) {
                throw new Error(`❌ Le sous-chapitre "${chapitreVal}" n'appartient pas correctement à son chapitre parent "${parentNum}".`);
            }

            chapterNumbers.add(chapitreVal);

            const children = Array.from(block.children).filter(child =>
                child.classList.contains('chapter-block') || child.classList.contains('sub-block')
            );

            const obj = {
                chapitre: chapitreVal,
                titre: titreVal,
                page: pageVal,
                debut: debutVal
            };

            const subChaps = [];
            children.forEach(child => {
                subChaps.push(parseBlock(child, chapitreVal)); // passer le numéro du parent
            });
            if (subChaps.length) obj.sousChapitres = subChaps;

            return obj;
        }

        try {
            container.querySelectorAll(':scope > .chapter-block').forEach(block => {
                chapters.push(parseBlock(block));
            });

            const data = {
                book: "<?= addslashes($selectedBook) ?>",
                chapitres: chapters
            };

            fetch('admin_save_chapitres_json.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.ok ? res.json() : Promise.reject('Erreur serveur'))
            .then(res => {
                errorMsg.textContent = "✅ Sauvegardé !";
                setTimeout(() => errorMsg.textContent = "", 2000);
            })
            .catch(err => {
                errorMsg.textContent = "❌ Erreur lors de la sauvegarde.";
                setTimeout(() => errorMsg.textContent = "", 2000);
                console.error(err);
            });

        } catch (err) {
            errorMsg.textContent = err.message;
            errorMsg.style.color = "red";
            setTimeout(() => {
                errorMsg.textContent = "";
                errorMsg.style.color = "";
            }, 3000);
        }
    }


    // Initialisation
    window.onload = () => {
        initialData.forEach(chap => {
            const block = createBlock(chap);
            document.getElementById('chapters_container').appendChild(block);
        });
    };
</script>

    </div>
<?php } ?>

<!-- Script pour sauvegarder la langue -->
<script>
    function saveLang() {
        const lang = document.getElementById('lang').value;
        const trans = document.getElementById('trans').value;
        const book = "<?= addslashes($selectedBook) ?>";

        fetch('admin_save_lang_json.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book, lang, trans })
        })
        .then(res => res.ok ? res.text() : Promise.reject('Erreur serveur'))
        .then(msg => {
            document.getElementById('saveLangMsg').textContent = "✅ Langue enregistrée";
            setTimeout(() => document.getElementById('saveLangMsg').textContent = "", 2000);
        })
        .catch(err => {
            document.getElementById('saveLangMsg').textContent = "❌ Échec";
            setTimeout(() => document.getElementById('saveLangMsg').textContent = "", 2000);
            console.error(err);
        });
    }
</script>

<!-- Script pour sauvegarder la catégorie -->
<script>
    function saveType() {
        const type = document.getElementById('type').value;
        const book = "<?= addslashes($selectedBook) ?>";

        fetch('admin_save_type_json.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book, type })
        })
        .then(res => res.ok ? res.text() : Promise.reject('Erreur serveur'))
        .then(msg => {
            document.getElementById('saveTypeMsg').textContent = "✅ Langue enregistrée";
            setTimeout(() => document.getElementById('saveTypeMsg').textContent = "", 2000);
        })
        .catch(err => {
            document.getElementById('saveTypeMsg').textContent = "❌ Échec";
            setTimeout(() => document.getElementById('saveTypeMsg').textContent = "", 2000);
            console.error(err);
        });
    }
</script>

<!-- Script pour sauvegarder la nom arabe du livre -->
<script>
    function saveArabicName() {
        const arabicName = document.getElementById('book_arabic_title').value;
        const book = "<?= addslashes($selectedBook) ?>";

        fetch('admin_save_arabic_name_json.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book, arabicName })
        })
        .then(res => res.ok ? res.text() : Promise.reject('Erreur serveur'))
        .then(msg => {
            document.getElementById('bookArabicNameMsg').textContent = "✅ Langue arabe enregistrée";
            setTimeout(() => document.getElementById('bookArabicNameMsg').textContent = "", 2000);
        })
        .catch(err => {
            document.getElementById('bookArabicNameMsg').textContent = "❌ Échec";
            setTimeout(() => document.getElementById('bookArabicNameMsg').textContent = "", 2000);
            console.error(err);
        });
    }
</script>

<!-- Script pour sauvegarder la nom de l'auteur livre -->
<script>
    function saveAuthorName() {
        const authorName = document.getElementById('author_name').value;
        const book = "<?= addslashes($selectedBook) ?>";

        fetch('admin_save_author_name_json.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book, authorName })
        })
        .then(res => res.ok ? res.text() : Promise.reject('Erreur serveur'))
        .then(msg => {
            document.getElementById('bookAuthorMsg').textContent = "✅ Nom de l'auteur enregistré";
            setTimeout(() => document.getElementById('bookAuthorMsg').textContent = "", 2000);
        })
        .catch(err => {
            document.getElementById('bookAuthorMsg').textContent = "❌ Échec";
            setTimeout(() => document.getElementById('bookAuthorMsg').textContent = "", 2000);
            console.error(err);
        });
    }
</script>

<!-- Script pour sauvegarder la nom du traducteur livre -->
<script>
    function saveTranslatorName() {
        const translatorName = document.getElementById('translator_name').value;
        const book = "<?= addslashes($selectedBook) ?>";

        fetch('admin_save_translator_name_json.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book, translatorName })
        })
        .then(res => res.ok ? res.text() : Promise.reject('Erreur serveur'))
        .then(msg => {
            document.getElementById('bookTranslatorMsg').textContent = "✅ Nom du traducteur enregistré";
            setTimeout(() => document.getElementById('bookTranslatorMsg').textContent = "", 2000);
        })
        .catch(err => {
            document.getElementById('bookTranslatorMsg').textContent = "❌ Échec";
            setTimeout(() => document.getElementById('bookTranslatorMsg').textContent = "", 2000);
            console.error(err);
        });
    }
</script>

<!-- Script pour sauvegarder la nom du narrateur livre -->
<script>
    function saveNarratorName() {
        const narratorName = document.getElementById('narrator_name').value;
        const book = "<?= addslashes($selectedBook) ?>";

        fetch('admin_save_narrator_name_json.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book, narratorName })
        })
        .then(res => res.ok ? res.text() : Promise.reject('Erreur serveur'))
        .then(msg => {
            document.getElementById('bookNarratorMsg').textContent = "✅ Nom du narrateur enregistré";
            setTimeout(() => document.getElementById('bookNarratorMsg').textContent = "", 2000);
        })
        .catch(err => {
            document.getElementById('bookNarratorMsg').textContent = "❌ Échec";
            setTimeout(() => document.getElementById('bookNarratorMsg').textContent = "", 2000);
            console.error(err);
        });
    }
</script>

<!-- Script pour créer un nouveau livre -->
<script>
function createNewBook() {
    const name = document.getElementById('newBookName').value.trim();
    const msg = document.getElementById('newBookMsg');

    if (!name) {
        msg.textContent = "❌ Nom requis.";
        return;
    }

    fetch('admin_create_book.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name })
    })
    .then(res => res.ok ? res.json() : Promise.reject('Erreur serveur'))
    .then(response => {
        if (response.success) {
            msg.textContent = "✅ Livre créé.";
            setTimeout(() => msg.textContent = "", 1000);
            // Recharge avec le livre sélectionné
            const url = new URL(window.location.href);
            url.searchParams.set('selected_book', name);
            window.location.href = url.toString();
        } else {
            msg.textContent = "❌ " + (response.message || "Erreur à la création.");
            setTimeout(() => msg.textContent = "", 1000);
        }
    })
    .catch(err => {
        msg.textContent = "❌ " + err;
        setTimeout(() => msg.textContent = "", 2000);
        console.error(err);
    });
}
</script>

<!-- Script pour renommer ou supprimer un livre  -->
<script>
function renameBook() {
    const selected = document.getElementById('selected_book').value;
    const newTitle = document.getElementById('book_title').value.trim();
    const msg = document.getElementById('bookActionMsg');

    if (!selected || !newTitle) {
        msg.textContent = "❌ Sélectionnez un livre et entrez un nouveau nom.";
        return;
    }

    fetch('admin_rename_book.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ oldName: selected, newName: newTitle })
    })
    .then(res => res.ok ? res.json() : Promise.reject('Erreur serveur'))
    .then(response => {
        if (response.success) {
            msg.textContent = "✅ Livre renommé.";
            setTimeout(() => msg.textContent = "", 1000);
            // Rediriger avec le nouveau nom dans l'URL
            const url = new URL(window.location.href);
            url.searchParams.set('selected_book', response.newName);
            window.location.href = url.toString();
        } else {
            msg.textContent = "❌ " + (response.message || "Erreur lors du renommage.");
            setTimeout(() => msg.textContent = "", 1000);
        }
    })
    .catch(err => {
        msg.textContent = "❌ " + err;
        setTimeout(() => msg.textContent = "", 2000);
        console.error(err);
    });
}

// Fonction pour supprimer un livre
function deleteBook() {
    const selected = document.getElementById('selected_book').value;
    const msg = document.getElementById('bookActionMsg');

    if (!selected) {
        msg.textContent = "❌ Aucun livre sélectionné.";
        return;
    }

    if (!confirm(`Supprimer le livre "${selected}" ?`)) return;

    fetch('admin_delete_book.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ book: selected })
    })
    .then(res => res.ok ? res.json() : Promise.reject('Erreur serveur'))
    .then(response => {
        if (response.success) {
            msg.textContent = "✅ Livre supprimé.";
            setTimeout(() => msg.textContent = "", 1000);
            window.location.href = window.location.pathname; // recharge sans le paramètre sélectionné
        } else {
            msg.textContent = "❌ " + (response.message || "Erreur lors de la suppression.");
            setTimeout(() => msg.textContent = "", 1000);
        }
    })
    .catch(err => {
        msg.textContent = "❌ " + err;
        setTimeout(() => msg.textContent = "", 1000);
        console.error(err);
    });
}
</script>

<script>
// Auto positionnement du texte dans le champ de recherche
function adjustSearchDirection(input) {
    const text = input.value.trim();
    // Détection très simple : si le texte commence par un caractère arabe, on passe en RTL
    const isArabic = /^[\u0600-\u06FF]/.test(text);
    input.style.direction = isArabic ? 'rtl' : 'ltr';
    input.style.textAlign = isArabic ? 'right' : 'left';
}
</script>
</body>
</html>
