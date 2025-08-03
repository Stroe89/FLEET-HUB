<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php'; // Header-ul va fi inclus aici, dar stilurile vor fi preluate din DB

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mesaje de succes sau eroare din sesiune
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Preluăm setările de temă existente (ar trebui să fie un singur rând cu id=1)
$setari_tema = [
    'theme_mode' => 'blue-theme',
    'accent_color' => '#0d6efd',
    'font_family' => 'Noto Sans',
    'hidden_elements' => '[]' // Valoare implicită pentru noua coloană
];

$sql_settings = "SELECT * FROM setari_tema WHERE id = 1";
$result_settings = $conn->query($sql_settings);
if ($result_settings && $result_settings->num_rows > 0) {
    $fetched_settings = $result_settings->fetch_assoc();
    $setari_tema = array_merge($setari_tema, $fetched_settings);
    // Asigură că hidden_elements este un array PHP
    $setari_tema['hidden_elements'] = json_decode($setari_tema['hidden_elements'] ?? '[]', true);
    if (!is_array($setari_tema['hidden_elements'])) {
        $setari_tema['hidden_elements'] = [];
    }
}
$conn->close();

// Opțiuni disponibile pentru temă
$theme_modes = [
    'blue-theme' => 'Tema Albastră (Implicită)',
    'light' => 'Mod Luminos',
    'dark' => 'Mod Întunecat',
    'semi-dark' => 'Mod Semi-Întunecat',
    'bordered-theme' => 'Temă cu Borduri'
];

$accent_colors = [
    '#0d6efd' => 'Albastru (Implicit)',
    '#28a745' => 'Verde',
    '#ffc107' => 'Galben',
    '#dc3545' => 'Roșu',
    '#6f42c1' => 'Violet',
    '#17a2b8' => 'Turcoaz',
    '#fd7e14' => 'Portocaliu'
];

$font_families = [
    'Noto Sans' => 'Noto Sans (Implicit)',
    'Inter' => 'Inter',
    'Roboto' => 'Roboto',
    'Open Sans' => 'Open Sans',
    'Montserrat' => 'Montserrat',
    'Lato' => 'Lato',
    'Oswald' => 'Oswald'
];

// Elemente UI care pot fi ascunse/afișate
$ui_elements = [
    'search_bar' => ['label' => 'Bară de căutare (Desktop)', 'icon' => 'bx-search'],
    'lang_selector' => ['label' => 'Selector Limbă', 'icon' => 'bx-globe'],
    'widget_menu' => ['label' => 'Meniu Widget-uri', 'icon' => 'bx-grid-alt'],
    'notifications' => ['label' => 'Notificări', 'icon' => 'bx-bell'],
    'user_profile' => ['label' => 'Profil Utilizator', 'icon' => 'bx-user-circle'],
    'sidebar_logo' => ['label' => 'Logo Sidebar', 'icon' => 'bx-image'],
    'sidebar_search' => ['label' => 'Căutare Sidebar (Mobil)', 'icon' => 'bx-search-alt'],
    // Adaugă alte elemente aici dacă vrei să le controlezi vizibilitatea
];
?>

<title>NTS TOUR | Setări Temă</title>

<style>
    /* Stiluri generale preluate din tema */
    body, html, .main-content {
        color: #ffffff !important;
    }
    .text-muted, .text-secondary {
        color: #e0e0e0 !important;
    }
    h1, h2, h3, h4, h5, h6 {
        color: #ffffff !important;
    }
    p, span, strong, label, small {
        color: #ffffff !important;
    }
    .card {
        background-color: #2a3042 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .card-title {
        color: #ffffff !important;
    }
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .form-label {
        color: #e0e0e0 !important;
    }
    .form-control, .form-select, .form-check-label {
        background-color: #1a2035 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 0.5rem !important;
    }
    .form-control::placeholder {
        color: #b0b0b0 !important;
        opacity: 0.7 !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #6a90f1 !important;
        box-shadow: 0 0 0 0.25rem rgba(106, 144, 241, 0.25) !important;
    }
    .form-check-input:checked {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    .alert {
        color: #ffffff !important;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .alert-info {
        background-color: #203354 !important;
        border-color: #4285f4 !important;
    }
    .alert-success {
        background-color: #2c5234 !important;
        border-color: #4caf50 !important;
    }
    .alert-danger {
        background-color: #5c2c31 !important;
        border-color: #f44336 !important;
    }
    .alert-warning {
        background-color: #6a5300 !important;
        border-color: #ffc107 !important;
    }

    /* Stiluri specifice pentru selectorii de temă */
    .theme-option-group, .color-palette-group, .font-select-group {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 1rem;
    }
    .theme-option-box, .color-swatch {
        flex: 1 1 auto; /* Permite elementelor să crească și să se micșoreze */
        min-width: 120px; /* Lățime minimă pentru a nu deveni prea mici */
        max-width: 200px; /* Lățime maximă pentru a nu fi prea late */
        border: 2px solid transparent;
        border-radius: 0.75rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        text-align: center;
        font-weight: bold;
        color: #ffffff; /* Culoare text implicită */
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .theme-option-box.selected, .color-swatch.selected {
        border-color: #0d6efd; /* Culoare bordură pentru selecție */
        box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.4);
        transform: translateY(-2px);
    }
    .theme-option-box:hover, .color-swatch:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
    }

    /* Fundaluri specifice pentru modurile de temă */
    .theme-option-box[data-theme-mode="blue-theme"] { background-color: #1a2035; }
    .theme-option-box[data-theme-mode="light"] { background-color: #f8f9fa; color: #212529; }
    .theme-option-box[data-theme-mode="dark"] { background-color: #212529; }
    .theme-option-box[data-theme-mode="semi-dark"] { background-color: #2a3042; }
    .theme-option-box[data-theme-mode="bordered-theme"] { background-color: #3b435a; }

    .color-swatch {
        height: 50px;
        width: 50px;
        min-width: 50px;
        border-radius: 50%; /* Cerc pentru culori */
        margin-bottom: 0.5rem;
        box-shadow: inset 0 0 5px rgba(0,0,0,0.3);
    }
    .color-swatch.selected {
        border-width: 3px;
    }

    .font-select-group .form-select {
        flex: 1 1 100%; /* Ocupă toată lățimea */
        max-width: 100%;
    }

    .theme-preview-text {
        margin-top: 1.5rem;
        padding: 1rem;
        border: 1px dashed rgba(255, 255, 255, 0.3);
        border-radius: 0.5rem;
        font-size: 1.1rem;
        text-align: center;
        transition: all 0.3s ease;
    }

    /* Stiluri pentru ascunderea/afișarea pictogramelor */
    .icon-visibility-group .form-check-label {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        background-color: #1a2035;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .icon-visibility-group .form-check-label:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .icon-visibility-group .form-check-input:checked + .form-check-label {
        background-color: rgba(13, 110, 253, 0.2); /* O nuanță mai deschisă de accent */
        border-color: var(--primary-accent-color);
    }
    .icon-visibility-group .form-check-label i {
        font-size: 1.5rem;
        color: var(--primary-accent-color); /* Icoanele vor avea culoarea de accent */
    }
    .icon-visibility-group .form-check-label span {
        font-weight: 500;
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Setări</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Setări Temă</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Personalizare Temă Interfață</h4>
                        <p class="text-muted">Ajustează aspectul vizual al platformei pentru o experiență personalizată.</p>
                        <hr>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form id="themeSettingsForm" action="process_setari_tema.php" method="POST">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="1"> <!-- ID-ul este întotdeauna 1 pentru acest tabel -->
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Mod Temă:</label>
                                    <div class="theme-option-group">
                                        <?php foreach ($theme_modes as $mode_value => $mode_label): ?>
                                            <div class="theme-option-box" data-theme-mode="<?php echo htmlspecialchars($mode_value); ?>">
                                                <input type="radio" name="theme_mode" value="<?php echo htmlspecialchars($mode_value); ?>" class="d-none" <?php echo ($setari_tema['theme_mode'] == $mode_value) ? 'checked' : ''; ?>>
                                                <span><?php echo htmlspecialchars($mode_label); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <label class="form-label">Culoare de Accent:</label>
                                    <div class="color-palette-group">
                                        <?php foreach ($accent_colors as $color_hex => $color_label): ?>
                                            <div class="color-swatch" style="background-color: <?php echo htmlspecialchars($color_hex); ?>;" data-accent-color="<?php echo htmlspecialchars($color_hex); ?>">
                                                <input type="radio" name="accent_color" value="<?php echo htmlspecialchars($color_hex); ?>" class="d-none" <?php echo ($setari_tema['accent_color'] == $color_hex) ? 'checked' : ''; ?>>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <label for="fontFamily" class="form-label">Familie Font:</label>
                                    <div class="font-select-group">
                                        <select class="form-select" id="fontFamily" name="font_family">
                                            <?php foreach ($font_families as $font_value => $font_label): ?>
                                                <option value="<?php echo htmlspecialchars($font_value); ?>" <?php echo ($setari_tema['font_family'] == $font_value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($font_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <p class="theme-preview-text" style="font-family: '<?php echo htmlspecialchars($setari_tema['font_family']); ?>'; color: <?php echo htmlspecialchars($setari_tema['accent_color']); ?>;">
                                        Acesta este un text de previzualizare pentru font și culoare.
                                    </p>
                                </div>

                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Vizibilitate Elemente Interfață</h5>
                                    <p class="text-muted">Alege ce elemente din antet și sidebar dorești să fie vizibile.</p>
                                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 icon-visibility-group">
                                        <?php foreach ($ui_elements as $element_key => $element_info): ?>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="hidden_elements[]" value="<?php echo htmlspecialchars($element_key); ?>" id="hide_<?php echo htmlspecialchars($element_key); ?>" 
                                                        <?php echo in_array($element_key, $setari_tema['hidden_elements']) ? '' : 'checked'; ?>>
                                                    <label class="form-check-label" for="hide_<?php echo htmlspecialchars($element_key); ?>">
                                                        <i class="bx <?php echo htmlspecialchars($element_info['icon']); ?>"></i>
                                                        <span><?php echo htmlspecialchars($element_info['label']); ?></span>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="form-text text-muted">Elementele bifate vor fi vizibile. Debifează pentru a le ascunde.</small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Salvează Setări Temă</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const htmlElement = document.documentElement;
    const themeSettingsForm = document.getElementById('themeSettingsForm');
    const themeOptionBoxes = document.querySelectorAll('.theme-option-box');
    const colorSwatches = document.querySelectorAll('.color-palette-group .color-swatch');
    const fontFamilySelect = document.getElementById('fontFamily');
    const themePreviewText = document.querySelector('.theme-preview-text');
    const uiVisibilityCheckboxes = document.querySelectorAll('.icon-visibility-group input[type="checkbox"]');

    // Funcție pentru a aplica tema vizual
    function applyThemePreview(mode, accentColor, fontFamily) {
        // Aplică modul temei pe elementul <html>
        htmlElement.setAttribute('data-bs-theme', mode);

        // Aplică culoarea de accent (aceasta ar necesita CSS dinamic sau variabile CSS)
        // Pentru simplitate, vom schimba culoarea textului de previzualizare
        themePreviewText.style.color = accentColor;
        // Setăm variabila CSS globală pentru culoarea de accent
        htmlElement.style.setProperty('--primary-accent-color', accentColor);
        // Convertim hex în RGB pentru a actualiza variabila --primary-accent-color-rgb
        function hexToRgb(hex) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `${r}, ${g}, ${b}`;
        }
        htmlElement.style.setProperty('--primary-accent-color-rgb', hexToRgb(accentColor));


        // Aplică font-family
        themePreviewText.style.fontFamily = `'${fontFamily}', sans-serif`;
        document.body.style.fontFamily = `'${fontFamily}', sans-serif`; // Aplică și pe body

        // Actualizează clasa 'selected' pentru modurile de temă
        themeOptionBoxes.forEach(box => {
            if (box.dataset.themeMode === mode) {
                box.classList.add('selected');
                box.querySelector('input[name="theme_mode"]').checked = true;
            } else {
                box.classList.remove('selected');
                box.querySelector('input[name="theme_mode"]').checked = false;
            }
        });

        // Actualizează clasa 'selected' pentru culorile de accent
        colorSwatches.forEach(swatch => {
            if (swatch.dataset.accentColor === accentColor) {
                swatch.classList.add('selected');
                swatch.querySelector('input[name="accent_color"]').checked = true;
            } else {
                swatch.classList.remove('selected');
                swatch.querySelector('input[name="accent_color"]').checked = false;
            }
        });

        // Actualizează selecția în dropdown-ul de font
        fontFamilySelect.value = fontFamily;
    }

    // Funcție pentru a aplica vizibilitatea elementelor UI
    function applyUIVisibility() {
        uiVisibilityCheckboxes.forEach(checkbox => {
            const elementKey = checkbox.value;
            const isVisible = checkbox.checked;
            const targetElement = document.querySelector(`[data-ui-element="${elementKey}"]`);
            if (targetElement) {
                if (isVisible) {
                    targetElement.style.display = ''; // Afișează
                } else {
                    targetElement.style.display = 'none'; // Ascunde
                }
            }
            // Pentru bara de căutare principală, care are o structură diferită
            if (elementKey === 'search_bar') {
                const searchBar = document.querySelector('.top-header .search-bar');
                if (searchBar) {
                    searchBar.style.display = isVisible ? 'block' : 'none';
                }
            }
            // Pentru căutarea din sidebar (doar pe mobil)
            if (elementKey === 'sidebar_search') {
                const sidebarSearchInput = document.querySelector('.sidebar-search-input.d-lg-none');
                if (sidebarSearchInput) {
                    sidebarSearchInput.style.display = isVisible ? 'block' : 'none';
                }
            }
            // Pentru logo-ul din sidebar
            if (elementKey === 'sidebar_logo') {
                const sidebarLogo = document.querySelector('.sidebar-header .logo-icon');
                if (sidebarLogo) {
                    sidebarLogo.style.display = isVisible ? 'flex' : 'none'; // Logo-ul este flex pentru centrare
                }
            }
        });
    }


    // Preluare setări inițiale din PHP
    const initialThemeMode = "<?php echo htmlspecialchars($setari_tema['theme_mode']); ?>";
    const initialAccentColor = "<?php echo htmlspecialchars($setari_tema['accent_color']); ?>";
    const initialFontFamily = "<?php echo htmlspecialchars($setari_tema['font_family']); ?>";
    const initialHiddenElements = <?php echo json_encode($setari_tema['hidden_elements']); ?>;

    // Aplică setările inițiale la încărcarea paginii
    applyThemePreview(initialThemeMode, initialAccentColor, initialFontFamily);
    
    // Setează starea inițială a checkbox-urilor de vizibilitate
    uiVisibilityCheckboxes.forEach(checkbox => {
        const elementKey = checkbox.value;
        // Dacă elementKey NU este în array-ul initialHiddenElements, atunci e bifat (vizibil)
        checkbox.checked = !initialHiddenElements.includes(elementKey);
    });
    applyUIVisibility(); // Aplică vizibilitatea inițială

    // Event listeners pentru schimbările vizuale
    themeOptionBoxes.forEach(box => {
        box.addEventListener('click', function() {
            const selectedMode = this.dataset.themeMode;
            const currentAccentColor = document.querySelector('.color-swatch.selected')?.dataset.accentColor || initialAccentColor;
            const currentFontFamily = fontFamilySelect.value;
            applyThemePreview(selectedMode, currentAccentColor, currentFontFamily);
        });
    });

    colorSwatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            const selectedAccentColor = this.dataset.accentColor;
            const currentThemeMode = document.querySelector('.theme-option-box.selected')?.dataset.themeMode || initialThemeMode;
            const currentFontFamily = fontFamilySelect.value;
            applyThemePreview(currentThemeMode, selectedAccentColor, currentFontFamily);
        });
    });

    fontFamilySelect.addEventListener('change', function() {
        const selectedFontFamily = this.value;
        const currentThemeMode = document.querySelector('.theme-option-box.selected')?.dataset.themeMode || initialThemeMode;
        const currentAccentColor = document.querySelector('.color-swatch.selected')?.dataset.accentColor || initialAccentColor;
        applyThemePreview(currentThemeMode, currentAccentColor, selectedFontFamily);
    });

    uiVisibilityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', applyUIVisibility);
    });

    // Funcție pentru a genera dinamic stilurile pentru fonturi (dacă nu sunt deja incluse global)
    function loadGoogleFont(fontName) {
        if (!document.querySelector(`link[href*="font-family=${encodeURIComponent(fontName)}"]`)) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(fontName.replace(/ /g, '+'))}:wght@300;400;500;600;700&display=swap`;
            document.head.appendChild(link);
        }
    }

    // Încarcă fonturile la inițializare
    <?php foreach (array_keys($font_families) as $font): ?>
        loadGoogleFont("<?php echo htmlspecialchars($font); ?>");
    <?php endforeach; ?>

    // Trimiterea formularului
    themeSettingsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(themeSettingsForm);

        // Colectează elementele ascunse
        const elementsToHide = [];
        uiVisibilityCheckboxes.forEach(checkbox => {
            if (!checkbox.checked) { // Dacă este debifat, înseamnă că vrem să-l ascundem
                elementsToHide.push(checkbox.value);
            }
        });
        formData.append('hidden_elements', JSON.stringify(elementsToHide));

        fetch('process_setari_tema.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Răspunsul este text, nu JSON, deci ar putea fi un mesaj de succes sau eroare
            if (data.includes("succes")) { // Caută un cuvânt cheie în mesaj
                 alert('Setările de temă au fost salvate cu succes!');
            } else {
                 alert('Eroare la salvarea setărilor de temă: ' + data);
            }
            console.log(data); // Pentru depanare
            location.reload(); // Reîncarcă pagina pentru a aplica complet tema după salvare
        })
        .catch(error => {
            console.error('Eroare la salvarea setărilor de temă:', error);
            alert('A apărut o eroare la salvarea setărilor de temă.');
        });
    });
});
</script>