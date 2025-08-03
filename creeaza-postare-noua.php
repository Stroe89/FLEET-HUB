<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie
require_once 'template/header.php';

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

// Funcție ajutătoare pentru a verifica existența unui tabel
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
    return $result && $result->num_rows > 0;
}

// --- Preluare Date pentru Populare Formular (dacă se editează o postare) ---
$post_id = $_GET['id'] ?? null;
$post_data = null;

if ($post_id && is_numeric($post_id)) {
    if (tableExists($conn, 'marketing_posts')) {
        $stmt_post = $conn->prepare("SELECT * FROM marketing_posts WHERE id = ?");
        if ($stmt_post) {
            $stmt_post->bind_param("i", $post_id);
            $stmt_post->execute();
            $result_post = $stmt_post->get_result();
            $post_data = $result_post->fetch_assoc();
            $stmt_post->close();
        }
    }
}

$conn->close();

// Liste pentru selectoare
$platforms_available = ['facebook', 'instagram', 'tiktok', 'whatsapp', 'telegram'];
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | <?php echo $post_data ? 'Editează' : 'Creează'; ?> Postare</title>

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

    /* Stiluri specifice butoane */
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        font-weight: bold !important;
        padding: 0.75rem 1.5rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover, .btn-outline-primary:hover, .btn-outline-danger:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
    }
    .btn-primary { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; }
    .btn-info { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: #fff !important; }
    .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #343a40 !important; }
    .btn-success { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }

    /* Stiluri pentru selectorul de platforme */
    .platform-selector .form-check-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease;
        background-color: #1a2035;
        color: #e0e0e0;
        height: 100px; /* Înălțime fixă pentru butoane */
    }
    .platform-selector .form-check-input:checked + .form-check-label {
        background-color: var(--primary-accent-color);
        border-color: var(--primary-accent-color);
        color: #fff;
    }
    .platform-selector .form-check-label i {
        font-size: 2.5rem;
        margin-bottom: 5px;
    }
    .platform-selector .form-check-label span {
        font-size: 0.9em;
        font-weight: bold;
    }
    /* Iconițe specifice platformelor */
    .platform-selector .form-check-label .bx.bxl-facebook-square { color: #1877F2; }
    .platform-selector .form-check-label .bx.bxl-instagram { color: #E4405F; }
    .platform-selector .form-check-label .bx.bxl-tiktok { color: #000; filter: invert(1); } /* TikTok are logo negru/alb */
    .platform-selector .form-check-label .bx.bxl-whatsapp { color: #25D366; }
    .platform-selector .form-check-label .bx.bxl-telegram { color: #0088CC; }
    .platform-selector .form-check-input:checked + .form-check-label .bx { color: #fff; } /* Iconițe albe când sunt selectate */
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Marketing (Social Manager)</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="dashboard-marketing.php">Dashboard Marketing</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $post_data ? 'Editează' : 'Creează'; ?> Postare</li>
                </ol>
                </nav>
            </div>
        </div>
        
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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title"><?php echo $post_data ? 'Editează Postare Social Media' : 'Creează Postare Social Media Nouă'; ?></h4>
                        <p class="text-muted">Compune și programează postări pentru platformele tale de social media.</p>
                        <hr>

                        <form id="createPostForm" action="process_marketing.php" method="POST">
                            <input type="hidden" name="action" id="postAction" value="<?php echo $post_data ? 'edit_post' : 'create_post'; ?>">
                            <?php if ($post_data): ?>
                                <input type="hidden" name="post_id" id="postId" value="<?php echo htmlspecialchars($post_data['id']); ?>">
                            <?php else: ?>
                                <input type="hidden" name="post_id" id="postId" value="">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="postTitle" class="form-label">Titlu Postare:</label>
                                <input type="text" class="form-control" id="postTitle" name="titlu_postare" placeholder="Ex: Promoția de Vară NTS TOUR" value="<?php echo htmlspecialchars($post_data['titlu_postare'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="postContentText" class="form-label">Conținut Text:</label>
                                <textarea class="form-control" id="postContentText" name="continut_text" rows="5" placeholder="Scrie textul postării aici..." required><?php echo htmlspecialchars($post_data['continut_text'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="postContentHtml" class="form-label">Conținut HTML (Opțional - pentru editor avansat):</label>
                                <textarea class="form-control" id="postContentHtml" name="continut_html" rows="5" placeholder="Conținut HTML pentru platforme care suportă (ex: email, anumite bloguri)."><?php echo htmlspecialchars($post_data['continut_html'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">Pentru un editor WYSIWYG avansat (ex: TinyMCE, CKEditor), va fi necesară integrare suplimentară.</small>
                            </div>

                            <h5 class="mb-3 mt-4">Selectează Platforme</h5>
                            <div class="row row-cols-2 row-cols-md-3 g-3 mb-4 platform-selector">
                                <?php foreach ($platforms_available as $platform): 
                                    $checked = false;
                                    if ($post_data && isset($post_data['platforme_selectate'])) {
                                        $selected_platforms = json_decode($post_data['platforme_selectate'], true);
                                        if (is_array($selected_platforms) && in_array($platform, $selected_platforms)) {
                                            $checked = true;
                                        }
                                    }
                                    $icon_class = '';
                                    switch ($platform) {
                                        case 'facebook': $icon_class = 'bxl-facebook-square'; break;
                                        case 'instagram': $icon_class = 'bxl-instagram'; break;
                                        case 'tiktok': $icon_class = 'bxl-tiktok'; break;
                                        case 'whatsapp': $icon_class = 'bxl-whatsapp'; break;
                                        case 'telegram': $icon_class = 'bxl-telegram'; break;
                                    }
                                ?>
                                <div class="col">
                                    <input class="form-check-input" type="checkbox" id="platform<?php echo htmlspecialchars(ucfirst($platform)); ?>" name="platforms[]" value="<?php echo htmlspecialchars($platform); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="platform<?php echo htmlspecialchars(ucfirst($platform)); ?>">
                                        <i class="bx <?php echo htmlspecialchars($icon_class); ?>"></i>
                                        <span><?php echo htmlspecialchars(ucfirst($platform)); ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <h5 class="mb-3 mt-4">Programare Trimitere</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="sendOption" class="form-label">Opțiune Trimitere:</label>
                                    <select class="form-select" id="sendOption" name="send_option">
                                        <option value="now" <?php echo ($post_data && $post_data['status'] != 'programata') ? 'selected' : ''; ?>>Trimite Acum</option>
                                        <option value="schedule" <?php echo ($post_data && $post_data['status'] == 'programata') ? 'selected' : ''; ?>>Programează Trimitere</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="scheduleDateTimeGroup" style="<?php echo ($post_data && $post_data['status'] == 'programata') ? '' : 'display: none;'; ?>">
                                    <label for="scheduleDateTime" class="form-label">Dată și Oră Programare:</label>
                                    <input type="datetime-local" class="form-control" id="scheduleDateTime" name="data_programare" value="<?php echo $post_data && $post_data['data_programare'] ? (new DateTime($post_data['data_programare']))->format('Y-m-d\TH:i') : ''; ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="dashboard-marketing.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary" name="submit_type" value="save_draft">Salvează ca Draft</button>
                                <button type="submit" class="btn btn-success" name="submit_type" value="publish_now">Publică Acum</button>
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
    // --- Data PHP pentru JavaScript ---
    const postData = <?php echo json_encode($post_data); ?>;

    // --- Elemente DOM ---
    const createPostForm = document.getElementById('createPostForm');
    const postActionInput = document.getElementById('postAction');
    const postIdInput = document.getElementById('postId');
    const postTitleInput = document.getElementById('postTitle');
    const postContentTextInput = document.getElementById('postContentText');
    const postContentHtmlInput = document.getElementById('postContentHtml');
    const platformCheckboxes = document.querySelectorAll('input[name="platforms[]"]');
    const sendOptionSelect = document.getElementById('sendOption');
    const scheduleDateTimeGroup = document.getElementById('scheduleDateTimeGroup');
    const scheduleDateTimeInput = document.getElementById('scheduleDateTime');

    // --- Funcționalitate Programare Trimitere ---
    sendOptionSelect.addEventListener('change', function() {
        if (this.value === 'schedule') {
            scheduleDateTimeGroup.style.display = 'block';
            scheduleDateTimeInput.setAttribute('required', 'required');
        } else {
            scheduleDateTimeGroup.style.display = 'none';
            scheduleDateTimeInput.removeAttribute('required');
        }
    });
    // Asigură starea corectă la încărcarea paginii
    sendOptionSelect.dispatchEvent(new Event('change'));

    // --- Logică pentru Popularea Formularului la Editare ---
    if (postData) {
        // Câmpurile de bază sunt deja populate de PHP
        // Asigură că platformele sunt bifate corect
        const selectedPlatforms = JSON.parse(postData.platforme_selectate || '[]');
        platformCheckboxes.forEach(cb => {
            if (selectedPlatforms.includes(cb.value)) {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
        });
        // Setează opțiunea de trimitere și data programată
        if (postData.status === 'programata') {
            sendOptionSelect.value = 'schedule';
            // Formatul datetime-local este YYYY-MM-DDTHH:MM
            scheduleDateTimeInput.value = postData.data_programare ? new Date(postData.data_programare).toISOString().slice(0, 16) : '';
        } else {
            sendOptionSelect.value = 'now';
            scheduleDateTimeInput.value = '';
        }
        sendOptionSelect.dispatchEvent(new Event('change')); // Activează logica de afișare/ascundere a datei
    }

    // --- Trimiterea Formularului ---
    createPostForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(createPostForm);

        // Asigură că acțiunea corectă este trimisă (din butonul de submit)
        const submitType = e.submitter.value; 
        formData.set('submit_type', submitType); // Adăugăm submit_type pentru a ști ce buton a fost apăsat

        // Asigură că platformele sunt un array JSON
        const selectedPlatforms = [];
        platformCheckboxes.forEach(cb => {
            if (cb.checked) {
                selectedPlatforms.push(cb.value);
            }
        });
        formData.set('platforms', JSON.stringify(selectedPlatforms));

        // Asigură că data_programare este setată corect sau null
        if (sendOptionSelect.value === 'now' || submitType === 'publish_now') {
            formData.set('data_programare', new Date().toISOString().slice(0, 19).replace('T', ' '));
            formData.set('status', 'publicata'); // Forțează statusul la publicat dacă se trimite acum
        } else if (sendOptionSelect.value === 'schedule') {
            if (!scheduleDateTimeInput.value) {
                alert('Te rog să specifici o dată și oră pentru programare.');
                return;
            }
            formData.set('data_programare', scheduleDateTimeInput.value.replace('T', ' '));
            formData.set('status', 'programata');
        } else { // Cazul "Salvează ca Draft"
            formData.set('data_programare', ''); // Golim data programare dacă e draft
            formData.set('status', 'draft');
        }

        // Setează acțiunea corectă pentru process_marketing.php
        if (postData) { // Dacă edităm
            formData.set('action', 'edit_post');
        } else { // Dacă creăm
            formData.set('action', 'create_post');
        }

        fetch('process_marketing.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                // Redirecționează la dashboard sau la lista de postări programate/publicate
                window.location.href = 'dashboard-marketing.php'; 
            } else {
                alert('Eroare: ' + data.message);
                console.error('Eroare la salvarea postării:', data.message);
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea postării:', error);
            alert('A apărut o eroare la salvarea postării.');
        });
    });

    // Fix pentru blocarea paginii după închiderea modalurilor (generic)
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    });
});
</script>