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

// --- Logică pentru Preluarea Datelor Dashboard Marketing (Simulate/Mock) ---
$marketing_stats = [
    'Total Postări' => 0,
    'Postări Publicate' => 0,
    'Postări Programate' => 0,
    'Interacțiuni Totale' => 0,
    'Rată Angajament Medie' => 0
];
$scheduled_posts = [];
$published_posts = [];

// Date simulate dacă tabelul 'marketing_posts' nu există
if (tableExists($conn, 'marketing_posts')) {
    // Total Postări
    $sql_total_posts = "SELECT COUNT(*) as count FROM marketing_posts";
    $result_total_posts = $conn->query($sql_total_posts);
    if ($result_total_posts) {
        $marketing_stats['Total Postări'] = $result_total_posts->fetch_assoc()['count'] ?? 0;
    }

    // Postări Publicate
    $sql_published_posts_count = "SELECT COUNT(*) as count FROM marketing_posts WHERE status = 'publicata'";
    $result_published_posts_count = $conn->query($sql_published_posts_count);
    if ($result_published_posts_count) {
        $marketing_stats['Postări Publicate'] = $result_published_posts_count->fetch_assoc()['count'] ?? 0;
    }

    // Postări Programate
    $sql_scheduled_posts_count = "SELECT COUNT(*) as count FROM marketing_posts WHERE status = 'programata'";
    $result_scheduled_posts_count = $conn->query($sql_scheduled_posts_count);
    if ($result_scheduled_posts_count) {
        $marketing_stats['Postări Programate'] = $result_scheduled_posts_count->fetch_assoc()['count'] ?? 0;
    }
    
    // Preluare postări programate
    $sql_scheduled = "SELECT id, titlu_postare, platforme_selectate, data_programare, status FROM marketing_posts WHERE status = 'programata' ORDER BY data_programare ASC LIMIT 5";
    $result_scheduled = $conn->query($sql_scheduled);
    if ($result_scheduled) {
        while($row = $result_scheduled->fetch_assoc()) {
            $scheduled_posts[] = $row;
        }
    }

    // Preluare postări publicate
    $sql_published = "SELECT id, titlu_postare, platforme_selectate, data_programare, status FROM marketing_posts WHERE status = 'publicata' ORDER BY data_programare DESC LIMIT 10";
    $result_published = $conn->query($sql_published);
    if ($result_published) {
        while($row = $result_published->fetch_assoc()) {
            $published_posts[] = $row;
        }
    }

    // Interacțiuni Totale (mock pentru că nu avem date reale de interacțiune)
    $marketing_stats['Interacțiuni Totale'] = 5000;
    $marketing_stats['Rată Angajament Medie'] = 5.2;

} else {
    // Date mock complete dacă tabelul nu există
    $marketing_stats = [
        'Total Postări' => 75,
        'Postări Publicate' => 60,
        'Postări Programate' => 15,
        'Interacțiuni Totale' => 5000,
        'Rată Angajament Medie' => 5.2
    ];
    $scheduled_posts = [
        ['id' => 1, 'titlu_postare' => 'Promoția de Toamnă', 'platforme_selectate' => '["facebook", "instagram"]', 'data_programare' => '2025-09-01 10:00:00', 'status' => 'programata'],
        ['id' => 2, 'titlu_postare' => 'Noutăți Flotă Septembrie', 'platforme_selectate' => '["facebook", "tiktok"]', 'data_programare' => '2025-09-05 14:00:00', 'status' => 'programata'],
    ];
    $published_posts = [
        ['id' => 10, 'titlu_postare' => 'Oferte de Vară', 'platforme_selectate' => '["facebook", "instagram"]', 'data_programare' => '2025-07-20 11:00:00', 'status' => 'publicata'],
        ['id' => 11, 'titlu_postare' => 'Sfaturi Mentenanță', 'platforme_selectate' => '["facebook", "tiktok"]', 'data_programare' => '2025-07-25 15:00:00', 'status' => 'publicata'],
        ['id' => 12, 'titlu_postare' => 'Recrutăm Șoferi', 'platforme_selectate' => '["facebook", "instagram", "tiktok"]', 'data_programare' => '2025-07-30 09:00:00', 'status' => 'publicata'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Dashboard Marketing</title>

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

    /* Stiluri specifice pentru dashboard marketing */
    .stat-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
    }
    .stat-card .card-body {
        background-color: #2a3042 !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem;
    }
    .stat-card .widgets-icons {
        font-size: 2.5rem !important;
        opacity: 0.7 !important;
        padding: 10px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.08);
    }
    .stat-card.border-left-info { border-left-color: #007bff !important; }
    .stat-card.border-left-success { border-left-color: #28a745 !important; }
    .stat-card.border-left-warning { border-left-color: #ffc107 !important; }
    .stat-card.border-left-danger { border-left-color: #dc3545 !important; }
    .stat-card.border-left-primary { border-left-color: #0d6efd !important; }

    /* Stiluri pentru butoanele de acțiuni rapide */
    .d-grid .btn {
        font-weight: bold !important;
        padding: 0.8rem 1rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out !important, box-shadow 0.2s ease-in-out !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .d-grid .btn:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
    }
    .d-grid .btn-primary { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; }
    .d-grid .btn-info { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: #fff !important; }
    .d-grid .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #343a40 !important; }
    .d-grid .btn-success { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .d-grid .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }

    /* Stiluri pentru badge-uri */
    .badge {
        padding: 0.4em 0.7em;
        border-radius: 0.3rem;
        font-size: 0.85em;
        font-weight: 600;
    }
    .badge.bg-warning { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge.bg-danger { background-color: #dc3545 !important; color: #fff !important; }
    .badge.bg-success { background-color: #28a745 !important; color: #fff !important; }
    .badge.bg-info { background-color: #17a2b8 !important; color: #fff !important; }

    /* Stiluri pentru listele din Campanii Recente */
    .list-group-flush .list-group-item {
        background-color: #2a3042 !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        padding: 0.75rem 1rem;
        transition: background-color 0.2s ease;
    }
    .list-group-flush .list-group-item:hover {
        background-color: #3b435a !important;
    }
    .list-group-item-icon {
        margin-right: 0.5rem;
        font-size: 1.1em;
    }
    .list-group-item-icon.text-success { color: #28a745 !important; }
    .list-group-item-icon.text-warning { color: #ffc107 !important; }
    .list-group-item-icon.text-info { color: #17a2b8 !important; }
    .list-group-item-icon.text-danger { color: #dc3545 !important; }

    /* Stiluri pentru tab-uri */
    .nav-tabs .nav-link {
        color: #e0e0e0;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-bottom-color: transparent;
        background-color: #3b435a;
        margin-right: 5px;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .nav-tabs .nav-link.active {
        color: #ffffff;
        background-color: #2a3042;
        border-color: rgba(255, 255, 255, 0.1);
        border-bottom-color: #2a3042;
    }
    .nav-tabs .nav-link:hover {
        border-color: rgba(255, 255, 255, 0.2);
    }
    .tab-content {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-top: none;
        border-radius: 0 0 0.75rem 0.75rem;
        padding: 1.5rem;
    }

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
    .platform-selector .form-check-label .bx.bxl-tiktok { color: #000; } /* TikTok are logo negru/alb */
    .platform-selector .form-check-label .bx.bxl-whatsapp { color: #25D366; }
    .platform-selector .form-check-label .bx.bxl-telegram { color: #0088CC; }
    .platform-selector .form-check-input:checked + .form-check-label .bx { color: #fff; } /* Iconițe albe când sunt selectate */


    /* Responsive adjustments for table */
    @media (max-width: 767.98px) {
        .table-responsive {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
        }
        .table {
            width: 100%;
            margin-bottom: 0;
        }
        .table thead {
            display: none;
        }
        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 0.75rem;
        }
        .table tbody td {
            display: block;
            text-align: right;
            padding-left: 50% !important;
            position: relative;
            border: none;
        }
        .table tbody td::before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            width: 50%;
            padding-left: 1rem;
            font-weight: bold;
            text-align: left;
            color: #b0b0b0;
        }
        .table tbody td:last-child {
            border-bottom: none;
        }
        .table .btn {
            width: 100%;
            margin-top: 0.5rem;
        }
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Marketing (Social Manager)</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard Marketing</li>
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

        <!-- Statistici Cheie Marketing -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-5 g-4 mb-4">
            <div class="col">
                <div class="card stat-card border-left-info">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Total Postări</p><h4 class="my-1"><?php echo number_format($marketing_stats['Total Postări'], 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-bar-chart-alt-2"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-success">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Postări Publicate</p><h4 class="my-1"><?php echo number_format($marketing_stats['Postări Publicate'], 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bxs-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-warning">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Postări Programate</p><h4 class="my-1"><?php echo number_format($marketing_stats['Postări Programate'], 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bxs-time"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-danger">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Interacțiuni Totale</p><h4 class="my-1"><?php echo number_format($marketing_stats['Interacțiuni Totale'], 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bx-heart"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-primary">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Rată Angajament Medie</p><h4 class="my-1"><?php echo number_format($marketing_stats['Rată Angajament Medie'], 2, ',', '.'); ?>%</h4></div>
                        <div class="widgets-icons bg-light-primary text-primary ms-auto"><i class="bx bx-trending-up"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Creare Postare Nouă și Liste Postări -->
        <div class="row g-4 mb-4">
            <!-- Creare Postare Nouă -->
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Creează Postare Nouă</h4>
                        <p class="text-muted">Compune și programează postări pentru platformele tale de social media.</p>
                        <hr>
                        <form id="createPostForm" action="process_marketing.php" method="POST">
                            <input type="hidden" name="action" value="create_post">
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

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="reset" class="btn btn-secondary">Resetează</button>
                                <button type="submit" class="btn btn-primary">Salvează ca Draft</button>
                                <button type="submit" class="btn btn-success" name="action" value="publish_now">Publică Acum</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab-uri Postări Programate și Publicate -->
            <div class="col-12">
                <ul class="nav nav-tabs mb-3" id="marketingPostsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="scheduled-tab" data-bs-toggle="tab" data-bs-target="#scheduledPosts" type="button" role="tab" aria-controls="scheduledPosts" aria-selected="true">Postări Programate</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="published-tab" data-bs-toggle="tab" data-bs-target="#publishedPosts" type="button" role="tab" aria-controls="publishedPosts" aria-selected="false">Postări Publicate</button>
                    </li>
                </ul>
                <div class="tab-content" id="marketingPostsTabsContent">
                    <!-- Tab Postări Programate -->
                    <div class="tab-pane fade show active" id="scheduledPosts" role="tabpanel" aria-labelledby="scheduled-tab">
                        <h5 class="card-title mb-3">Postări Programate</h5>
                        <?php if (empty($scheduled_posts)): ?>
                            <div class="alert alert-info">Nu există postări programate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="scheduledPostsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Titlu</th>
                                            <th>Platforme</th>
                                            <th>Dată Programare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($scheduled_posts as $post): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($post['id']); ?></td>
                                                <td><?php echo htmlspecialchars($post['titlu_postare']); ?></td>
                                                <td>
                                                    <?php 
                                                        $platforms = json_decode($post['platforme_selectate'], true);
                                                        if (!empty($platforms)) {
                                                            foreach ($platforms as $platform) {
                                                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(ucfirst($platform)) . '</span>';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                    ?>
                                                </td>
                                                <td><?php echo (new DateTime($post['data_programare']))->format('d.m.Y H:i'); ?></td>
                                                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars(ucfirst($post['status'])); ?></span></td>
                                                <td>
                                                    <a href="dashboard-marketing.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary me-2"><i class="bx bx-edit"></i> Editează</a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-post-btn" data-id="<?php echo $post['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab Postări Publicate -->
                    <div class="tab-pane fade" id="publishedPosts" role="tabpanel" aria-labelledby="published-tab">
                        <h5 class="card-title mb-3">Postări Publicate</h5>
                        <?php if (empty($published_posts)): ?>
                            <div class="alert alert-info">Nu există postări publicate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="publishedPostsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Titlu</th>
                                            <th>Platforme</th>
                                            <th>Dată Publicare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($published_posts as $post): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($post['id']); ?></td>
                                                <td><?php echo htmlspecialchars($post['titlu_postare']); ?></td>
                                                <td>
                                                    <?php 
                                                        $platforms = json_decode($post['platforme_selectate'], true);
                                                        if (!empty($platforms)) {
                                                            foreach ($platforms as $platform) {
                                                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(ucfirst($platform)) . '</span>';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                    ?>
                                                </td>
                                                <td><?php echo (new DateTime($post['data_programare']))->format('d.m.Y H:i'); ?></td>
                                                <td><span class="badge bg-success"><?php echo htmlspecialchars(ucfirst($post['status'])); ?></span></td>
                                                <td>
                                                    <a href="dashboard-marketing.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary me-2"><i class="bx bx-show"></i> Vezi</a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-post-btn" data-id="<?php echo $post['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Elemente DOM ---
    const createPostForm = document.getElementById('createPostForm');
    const sendOptionSelect = document.getElementById('sendOption');
    const scheduleDateTimeGroup = document.getElementById('scheduleDateTimeGroup');
    const scheduleDateTimeInput = document.getElementById('scheduleDateTime');
    const postIdInput = document.getElementById('postId');

    const scheduledPostsTable = document.getElementById('scheduledPostsTable');
    const publishedPostsTable = document.getElementById('publishedPostsTable');

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

    // --- Logică pentru Editarea Postării (dacă există campaign_id în URL) ---
    const campaignData = <?php echo json_encode($campaign_data); ?>; // Preluat din PHP
    if (campaignData) {
        document.getElementById('postTitle').value = campaignData.titlu_postare || '';
        document.getElementById('postContentText').value = campaignData.continut_text || '';
        document.getElementById('postContentHtml').value = campaignData.continut_html || '';
        postIdInput.value = campaignData.id;

        // Selectează platformele
        const selectedPlatforms = JSON.parse(campaignData.platforme_selectate || '[]');
        selectedPlatforms.forEach(platform => {
            const checkbox = document.getElementById(`platform${platform.charAt(0).toUpperCase() + platform.slice(1)}`);
            if (checkbox) checkbox.checked = true;
        });

        // Setează opțiunea de trimitere și data
        if (campaignData.status === 'programata') {
            sendOptionSelect.value = 'schedule';
            scheduleDateTimeInput.value = campaignData.data_programare ? new Date(campaignData.data_programare).toISOString().slice(0, 16) : '';
        } else {
            sendOptionSelect.value = 'now';
        }
        sendOptionSelect.dispatchEvent(new Event('change')); // Activează logica de afișare/ascundere a datei

        // Schimbă textul butonului de salvare
        createPostForm.querySelector('button[type="submit"][value="create_post"]').textContent = "Salvează Modificările";
        createPostForm.querySelector('button[type="submit"][value="create_post"]').value = "save_post"; // Actualizează valoarea acțiunii
    }


    // --- Logică pentru Butoanele de Ștergere Postare ---
    document.querySelectorAll('.delete-post-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postIdToDelete = this.dataset.id;
            if (confirm('Ești sigur că vrei să ștergi această postare?')) {
                const formData = new FormData();
                formData.append('action', 'delete_post');
                formData.append('id', postIdToDelete);

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
                });
            }
        });
    });

    // --- Funcționalitate Export (PDF, Excel, Print) ---
    function exportTableToPDF(tableId, title) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const headers = [];
        document.querySelectorAll(`#${tableId} thead th`).forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
            const rowData = [];
            row.querySelectorAll('td:not(:last-child)').forEach(td => { // Exclude ultima coloană (Acțiuni)
                const badgeSpan = td.querySelector('.badge');
                if (badgeSpan) {
                    rowData.push(badgeSpan.textContent);
                } else {
                    rowData.push(td.textContent);
                }
            });
            data.push(rowData);
        });

        doc.text(title, 40, 40); 
        doc.autoTable({
            startY: 60,
            head: [headers],
            body: data,
            theme: 'striped',
            styles: {
                font: 'Noto Sans',
                fontSize: 8,
                cellPadding: 5,
                valign: 'middle',
                overflow: 'linebreak'
            },
            headStyles: {
                fillColor: [59, 67, 90],
                textColor: [255, 255, 255],
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [42, 48, 66]
            },
            bodyStyles: {
                textColor: [224, 224, 224]
            },
            didParseCell: function(data) {
                if (data.section === 'head') {
                    data.cell.styles.textColor = [255, 255, 255];
                }
            }
        });

        doc.save(`${title.replace(/ /g, '_')}.pdf`);
    }

    function exportTableToExcel(tableId, fileName) {
        const table = document.getElementById(tableId);
        const clonedTable = table.cloneNode(true);
        const tbody = clonedTable.querySelector('tbody');
        Array.from(tbody.children).forEach(row => {
            // Elimină coloana "Acțiuni" din clona tabelului înainte de export
            row.querySelector('td:last-child').remove();
            row.querySelector('th:last-child').remove(); // Elimină header-ul Acțiuni
        });

        const ws = XLSX.utils.table_to_sheet(clonedTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
        XLSX.writeFile(wb, `${fileName}.xlsx`);
    }

    function printTable(tableId, title) {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById(tableId).cloneNode(true);
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>${title}</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>${title}</h1>
                ${tableToPrint.outerHTML}
            </body>
            </html>
        `;
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const activeTabContent = document.querySelector('.tab-pane.fade.show.active');
        if (activeTabContent.id === 'scheduledPosts') {
            exportTableToPDF('scheduledPostsTable', 'Postări Programate');
        } else if (activeTabContent.id === 'publishedPosts') {
            exportTableToPDF('publishedPostsTable', 'Postări Publicate');
        }
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const activeTabContent = document.querySelector('.tab-pane.fade.show.active');
        if (activeTabContent.id === 'scheduledPosts') {
            exportTableToExcel('scheduledPostsTable', 'Postari_Programate');
        } else if (activeTabContent.id === 'publishedPosts') {
            exportTableToExcel('publishedPostsTable', 'Postari_Publicate');
        }
    });

    document.getElementById('printReportBtn').addEventListener('click', function() {
        const activeTabContent = document.querySelector('.tab-pane.fade.show.active');
        if (activeTabContent.id === 'scheduledPosts') {
            printTable('scheduledPostsTable', 'Postări Programate');
        } else if (activeTabContent.id === 'publishedPosts') {
            printTable('publishedPostsTable', 'Postări Publicate');
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
