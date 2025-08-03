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

// Preluăm toate postările de marketing pentru a le pre-încărca în JavaScript
$all_marketing_posts = [];
if (tableExists($conn, 'marketing_posts')) {
    $sql_posts = "SELECT id, titlu_postare, continut_text, continut_html, platforme_selectate, data_programare, status FROM marketing_posts ORDER BY data_programare DESC";
    $result_posts = $conn->query($sql_posts);
    if ($result_posts) {
        while ($row = $result_posts->fetch_assoc()) {
            $all_marketing_posts[] = $row;
        }
    }
} else {
    // Date mock dacă tabelul nu există
    $all_marketing_posts = [
        ['id' => 1, 'titlu_postare' => 'Promoție de Vară', 'continut_text' => 'Descriere scurtă...', 'continut_html' => '<p>Conținut HTML</p>', 'platforme_selectate' => '["facebook", "instagram"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('+2 days')), 'status' => 'programata'],
        ['id' => 2, 'titlu_postare' => 'Noutăți Flotă', 'continut_text' => 'Descriere scurtă...', 'continut_html' => '<p>Conținut HTML</p>', 'platforme_selectate' => '["tiktok"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('-5 days')), 'status' => 'publicata'],
    ];
}

$conn->close();

// Liste pentru filtre
$post_statuses = ['draft', 'programata', 'publicata', 'esuaa'];
$platforms_available = ['facebook', 'instagram', 'tiktok', 'whatsapp', 'telegram'];
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Calendar Postări Marketing</title>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

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

    /* Stiluri specifice FullCalendar */
    .fc {
        font-family: '<?php echo $current_font_family; ?>', sans-serif !important;
    }
    .fc .fc-toolbar-title {
        color: #ffffff;
    }
    .fc .fc-button {
        background-color: #3b435a;
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.1);
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .fc .fc-button:hover {
        background-color: #4a546e;
        border-color: rgba(255, 255, 255, 0.2);
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background-color: var(--primary-accent-color);
        border-color: var(--primary-accent-color);
        color: #fff;
    }
    .fc .fc-daygrid-day-number {
        color: #e0e0e0;
    }
    .fc .fc-col-header-cell-cushion {
        color: #ffffff;
    }
    .fc .fc-daygrid-day.fc-day-today {
        background-color: rgba(13, 110, 253, 0.15); /* Light blue for today */
    }
    .fc-theme-standard .fc-scrollgrid {
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .fc-event {
        background-color: #0d6efd; /* Default event color */
        border-color: #0d6efd;
        color: #fff;
        border-radius: 0.3rem;
        font-size: 0.85em;
        padding: 2px 5px;
        margin-bottom: 2px;
        cursor: pointer;
    }
    /* Culori specifice pentru statusuri postări */
    .fc-event-draft { background-color: #6c757d; border-color: #6c757d; } /* Grey */
    .fc-event-programata { background-color: #ffc107; border-color: #ffc107; color: #343a40; } /* Yellow */
    .fc-event-publicata { background-color: #28a745; border-color: #28a745; } /* Green */
    .fc-event-esuaa { background-color: #dc3545; border-color: #dc3545; } /* Red */

    /* Stil pentru iconițe pe evenimente */
    .fc-event-main-frame .bx {
        font-size: 1.1em;
        vertical-align: middle;
        margin-right: 5px;
    }
    /* Stiluri pentru selectorul de platforme în modal */
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
                    <li class="breadcrumb-item active" aria-current="page">Calendar Postări</li>
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
                        <h4 class="card-title">Calendar Postări Marketing</h4>
                        <p class="text-muted">Planifică, programează și gestionează postările tale de social media.</p>
                        <hr>

                        <!-- Filtre (Opțional, dacă vrei să filtrezi evenimentele afișate în calendar) -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($post_statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterPlatform" class="form-label">Filtrează după Platformă:</label>
                                <select class="form-select" id="filterPlatform">
                                    <option value="all">Toate Platformele</option>
                                    <?php foreach ($platforms_available as $platform): ?>
                                        <option value="<?php echo htmlspecialchars($platform); ?>"><?php echo htmlspecialchars(ucfirst($platform)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal Adaugă/Editează Postare -->
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postModalLabel">Adaugă Postare Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="postForm" action="process_marketing.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="postAction" value="create_post">
                    <input type="hidden" name="post_id" id="postId">

                    <div class="mb-3">
                        <label for="postTitle" class="form-label">Titlu Postare:</label>
                        <input type="text" class="form-control" id="postTitle" name="titlu_postare" placeholder="Ex: Promoția de Vară NTS TOUR" required>
                    </div>
                    <div class="mb-3">
                        <label for="postContentText" class="form-label">Conținut Text:</label>
                        <textarea class="form-control" id="postContentText" name="continut_text" rows="5" placeholder="Scrie textul postării aici..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="postContentHtml" class="form-label">Conținut HTML (Opțional - pentru editor avansat):</label>
                        <textarea class="form-control" id="postContentHtml" name="continut_html" rows="5" placeholder="Conținut HTML pentru platforme care suportă (ex: email, anumite bloguri)."></textarea>
                        <small class="form-text text-muted">Pentru un editor WYSIWYG avansat (ex: TinyMCE, CKEditor), va fi necesară integrare suplimentară.</small>
                    </div>

                    <h5 class="mb-3 mt-4">Selectează Platforme</h5>
                    <div class="row row-cols-2 row-cols-md-3 g-3 mb-4 platform-selector">
                        <div class="col">
                            <input class="form-check-input" type="checkbox" id="platformFacebook" name="platforms[]" value="facebook">
                            <label class="form-check-label" for="platformFacebook">
                                <i class="bx bxl-facebook-square"></i>
                                <span>Facebook</span>
                            </label>
                        </div>
                        <div class="col">
                            <input class="form-check-input" type="checkbox" id="platformInstagram" name="platforms[]" value="instagram">
                            <label class="form-check-label" for="platformInstagram">
                                <i class="bx bxl-instagram"></i>
                                <span>Instagram</span>
                            </label>
                        </div>
                        <div class="col">
                            <input class="form-check-input" type="checkbox" id="platformTikTok" name="platforms[]" value="tiktok">
                            <label class="form-check-label" for="platformTikTok">
                                <i class="bx bxl-tiktok"></i>
                                <span>TikTok</span>
                            </label>
                        </div>
                        <div class="col">
                            <input class="form-check-input" type="checkbox" id="platformWhatsApp" name="platforms[]" value="whatsapp">
                            <label class="form-check-label" for="platformWhatsApp">
                                <i class="bx bxl-whatsapp"></i>
                                <span>WhatsApp</span>
                            </label>
                        </div>
                        <div class="col">
                            <input class="form-check-input" type="checkbox" id="platformTelegram" name="platforms[]" value="telegram">
                            <label class="form-check-label" for="platformTelegram">
                                <i class="bx bxl-telegram"></i>
                                <span>Telegram</span>
                            </label>
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">Programare Trimitere</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="sendOption" class="form-label">Opțiune Trimitere:</label>
                            <select class="form-select" id="sendOption" name="send_option">
                                <option value="now">Trimite Acum</option>
                                <option value="schedule">Programează Trimitere</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="scheduleDateTimeGroup" style="display: none;">
                            <label for="scheduleDateTime" class="form-label">Dată și Oră Programare:</label>
                            <input type="datetime-local" class="form-control" id="scheduleDateTime" name="data_programare">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary" name="submit_type" value="save_draft">Salvează ca Draft</button>
                    <button type="submit" class="btn btn-success" name="submit_type" value="publish_now">Publică Acum</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmă Ștergere Postare -->
<div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePostModalLabel">Confirmă Ștergerea Postării</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi postarea <strong id="deletePostTitleDisplay"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deletePostIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePostBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/ro.js'></script> <!-- Limba română -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const postModal = document.getElementById('postModal');
    const postForm = document.getElementById('postForm');
    const deletePostModal = document.getElementById('deletePostModal');
    const confirmDeletePostBtn = document.getElementById('confirmDeletePostBtn');

    // Câmpuri din modal
    const postActionInput = document.getElementById('postAction');
    const postIdInput = document.getElementById('postId');
    const postTitleInput = document.getElementById('postTitle');
    const postContentTextInput = document.getElementById('postContentText');
    const postContentHtmlInput = document.getElementById('postContentHtml');
    const platformCheckboxes = document.querySelectorAll('input[name="platforms[]"]');
    const sendOptionSelect = document.getElementById('sendOption');
    const scheduleDateTimeGroup = document.getElementById('scheduleDateTimeGroup');
    const scheduleDateTimeInput = document.getElementById('scheduleDateTime');
    const postModalLabel = document.getElementById('postModalLabel');

    // Date PHP pentru JavaScript
    const allMarketingPostsData = <?php echo json_encode($all_marketing_posts); ?>;
    const postsMap = {};
    allMarketingPostsData.forEach(post => {
        postsMap[post.id] = post;
    });

    // Funcție pentru a obține iconița platformei
    function getPlatformIcon(platform) {
        switch (platform) {
            case 'facebook': return 'bxl-facebook-square';
            case 'instagram': return 'bxl-instagram';
            case 'tiktok': return 'bxl-tiktok';
            case 'whatsapp': return 'bxl-whatsapp';
            case 'telegram': return 'bxl-telegram';
            default: return 'bx-globe';
        }
    }

    // Inițializare FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'ro', // Setează limba română
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        editable: true, // Permite drag-and-drop și resize
        selectable: true, // Permite selectarea intervalelor de timp
        eventStartEditable: true,
        eventDurationEditable: true,
        navLinks: true, // Permite navigarea la zi/săptămână la click pe numerele zilelor
        
        // Sursa de evenimente (va apela process_marketing.php cu action=fetch_calendar_posts)
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('process_marketing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=fetch_calendar_posts&start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    successCallback(data.events);
                } else {
                    console.error('Eroare la preluarea evenimentelor:', data.message);
                    failureCallback(data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la fetch evenimente:', error);
                failureCallback(error);
            });
        },

        // Deschide modalul pentru a adăuga o postare nouă la click pe o dată
        dateClick: function(info) {
            postForm.reset();
            postActionInput.value = 'create_post';
            postIdInput.value = '';
            postModalLabel.textContent = 'Adaugă Postare Nouă';
            postTitleInput.value = '';
            postContentTextInput.value = '';
            postContentHtmlInput.value = '';
            platformCheckboxes.forEach(cb => cb.checked = false);
            sendOptionSelect.value = 'now';
            scheduleDateTimeInput.value = info.dateStr.substring(0, 16); // Pre-populează data și ora
            sendOptionSelect.dispatchEvent(new Event('change')); // Asigură vizibilitatea câmpului de programare
            
            new bootstrap.Modal(postModal).show();
        },

        // Deschide modalul pentru a edita o postare existentă la click pe eveniment
        eventClick: function(info) {
            const postId = info.event.id;
            const post = postsMap[postId]; // Folosim harta locală pentru a prelua datele complete

            if (post) {
                postActionInput.value = 'edit_post';
                postIdInput.value = post.id;
                postModalLabel.textContent = 'Editează Postare';

                postTitleInput.value = post.titlu_postare || '';
                postContentTextInput.value = post.continut_text || '';
                postContentHtmlInput.value = post.continut_html || '';

                // Resetare și selectare platforme
                platformCheckboxes.forEach(cb => cb.checked = false);
                const selectedPlatforms = JSON.parse(post.platforme_selectate || '[]');
                selectedPlatforms.forEach(platform => {
                    const checkbox = document.getElementById(`platform${platform.charAt(0).toUpperCase() + platform.slice(1)}`);
                    if (checkbox) checkbox.checked = true;
                });

                // Setează opțiunea de trimitere și data
                if (post.status === 'programata') {
                    sendOptionSelect.value = 'schedule';
                    scheduleDateTimeInput.value = post.data_programare ? new Date(post.data_programare).toISOString().slice(0, 16) : '';
                } else {
                    sendOptionSelect.value = 'now';
                    scheduleDateTimeInput.value = ''; // Golim dacă e publicată/draft
                }
                sendOptionSelect.dispatchEvent(new Event('change')); // Activează logica de afișare/ascundere a datei

                new bootstrap.Modal(postModal).show();
            } else {
                alert('Detaliile postării nu au putut fi preluate.');
            }
        },

        // Tratează drag-and-drop pentru evenimente
        eventDrop: function(info) {
            const postId = info.event.id;
            const newStart = info.event.start.toISOString().slice(0, 19).replace('T', ' ');
            const newEnd = info.event.end ? info.event.end.toISOString().slice(0, 19).replace('T', ' ') : newStart;

            // Trimite actualizarea către server
            const formData = new FormData();
            formData.append('action', 'update_post_schedule'); // Nouă acțiune în process_marketing.php
            formData.append('id', postId);
            formData.append('data_programare', newStart);
            // Dacă vrei să actualizezi și statusul la 'programata' automat la drag, poți adăuga:
            // formData.append('status', 'programata'); 

            fetch('process_marketing.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // alert('Postare actualizată cu succes!');
                    calendar.refetchEvents(); // Reîncarcă evenimentele
                } else {
                    alert('Eroare la actualizarea postării: ' + data.message);
                    info.revert(); // Anulează mișcarea în calendar
                }
            })
            .catch(error => {
                console.error('Eroare la drag-and-drop:', error);
                alert('A apărut o eroare la actualizarea postării.');
                info.revert();
            });
        },

        // Tratează redimensionarea evenimentelor
        eventResize: function(info) {
            const postId = info.event.id;
            const newStart = info.event.start.toISOString().slice(0, 19).replace('T', ' ');
            const newEnd = info.event.end ? info.event.end.toISOString().slice(0, 19).replace('T', ' ') : newStart;

            const formData = new FormData();
            formData.append('action', 'update_post_schedule'); // Aceeași acțiune ca la drag
            formData.append('id', postId);
            formData.append('data_programare', newStart);
            // formData.append('data_sfarsit_programare', newEnd); // Dacă ai o coloană separată pentru sfârșit

            fetch('process_marketing.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // alert('Postare redimensionată cu succes!');
                    calendar.refetchEvents();
                } else {
                    alert('Eroare la redimensionarea postării: ' + data.message);
                    info.revert();
                }
            })
            .catch(error => {
                console.error('Eroare la redimensionare:', error);
                alert('A apărut o eroare la redimensionarea postării.');
                info.revert();
            });
        },

        // Render custom content for events (for icons)
        eventContent: function(arg) {
            let iconHtml = '';
            const platforms = JSON.parse(arg.event.extendedProps.platforme_selectate || '[]');
            platforms.forEach(platform => {
                iconHtml += `<i class='bx ${getPlatformIcon(platform)} me-1'></i>`;
            });
            
            let statusClass = `fc-event-${arg.event.extendedProps.status}`;

            return { html: `<div class="${statusClass}">${iconHtml} ${arg.event.title}</div>` };
        }
    });

    calendar.render();

    // --- Funcționalitate Filtrare Calendar ---
    const filterStatus = document.getElementById('filterStatus');
    const filterPlatform = document.getElementById('filterPlatform');

    function applyCalendarFilters() {
        calendar.refetchEvents(); // Forțează reîncărcarea evenimentelor cu noile filtre

        // FullCalendar are o funcționalitate de filtrare internă, dar putem filtra și vizual
        // Aici, vom filtra evenimentele după ce sunt preluate de FullCalendar
        const selectedStatus = filterStatus.value;
        const selectedPlatform = filterPlatform.value;

        calendar.getEvents().forEach(event => {
            const eventStatus = event.extendedProps.status;
            const eventPlatforms = JSON.parse(event.extendedProps.platforme_selectate || '[]');

            let statusMatch = (selectedStatus === 'all' || eventStatus === selectedStatus);
            let platformMatch = (selectedPlatform === 'all' || eventPlatforms.includes(selectedPlatform));

            if (statusMatch && platformMatch) {
                event.setProp('display', 'auto'); // Afișează evenimentul
            } else {
                event.setProp('display', 'none'); // Ascunde evenimentul
            }
        });
    }

    filterStatus.addEventListener('change', applyCalendarFilters);
    filterPlatform.addEventListener('change', applyCalendarFilters);
    // applyCalendarFilters(); // Rulează la încărcarea paginii inițial (după ce calendarul e gata)


    // --- Trimiterea Formularului (Creare/Editare Postare) ---
    postForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(postForm);

        // Asigură că acțiunea corectă este trimisă
        const submitType = e.submitter.value; // Valoarea butonului care a declanșat submit-ul
        if (submitType === 'save_draft') {
            formData.set('action', postIdInput.value ? 'edit_post' : 'create_post');
            formData.set('status', 'draft');
            formData.set('send_option', 'manual'); // Nu se trimite automat
            formData.set('data_programare', ''); // Golim data programare dacă e draft
        } else if (submitType === 'publish_now') {
            formData.set('action', postIdInput.value ? 'edit_post' : 'create_post'); // Poate fi edit sau create
            formData.set('status', 'publicata');
            formData.set('send_option', 'now');
            formData.set('data_programare', new Date().toISOString().slice(0, 19).replace('T', ' ')); // Setează data curentă
        } else if (submitType === 'save_and_schedule') { // Dacă am avea un buton separat pentru programare
            formData.set('action', postIdInput.value ? 'edit_post' : 'create_post');
            formData.set('status', 'programata');
            formData.set('send_option', 'schedule');
        }

        // Asigură că platformele sunt un array JSON
        const selectedPlatforms = [];
        platformCheckboxes.forEach(cb => {
            if (cb.checked) {
                selectedPlatforms.push(cb.value);
            }
        });
        formData.set('platforms', JSON.stringify(selectedPlatforms));


        fetch('process_marketing.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                const modalInstance = bootstrap.Modal.getInstance(postModal);
                if (modalInstance) { modalInstance.hide(); }
                calendar.refetchEvents(); // Reîncarcă evenimentele din calendar
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

    // --- Logică Buton Ștergere Postare (din modal) ---
    let currentPostIdToDelete = null;
    document.querySelectorAll('.delete-post-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentPostIdToDelete = this.dataset.id;
            const postTitle = this.closest('tr').querySelector('td:nth-child(2)').textContent;
            document.getElementById('deletePostTitleDisplay').textContent = postTitle;
            new bootstrap.Modal(deletePostModal).show();
        });
    });

    confirmDeletePostBtn.addEventListener('click', function() {
        if (currentPostIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_post');
            formData.append('id', currentPostIdToDelete);

            fetch('process_marketing.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); 
                } else {
                    alert('Eroare: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea postării:', error);
                alert('A apărut o eroare la ștergerea postării.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(deletePostModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
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
