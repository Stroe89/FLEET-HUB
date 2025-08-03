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

// Funcție pentru a verifica existența unei coloane într-un tabel
function columnExists($conn, $tableName, $columnName) {
    if (!tableExists($conn, $tableName)) {
        return false;
    }
    $result = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($tableName) . "` LIKE '" . $conn->real_escape_string($columnName) . "'");
    return $result && $result->num_rows > 0;
}

// --- Logică pentru Preluarea Datelor Dashboard Newsletter ---
$newsletter_stats = [
    'Total Abonati' => 0,
    'Campanii Trimise' => 0,
    'Rata Deschidere Medie' => 0,
    'Rata Click Medie' => 0,
    'Abonati Noi Luna Curenta' => 0
];
$recent_campaigns = [];

try {
    // Total Abonati
    if (tableExists($conn, 'abonati_newsletter') && columnExists($conn, 'abonati_newsletter', 'status')) {
        $sql_total_subscribers = "SELECT COUNT(*) as count FROM abonati_newsletter WHERE status = 'activ'";
        $result_total_subscribers = $conn->query($sql_total_subscribers);
        if ($result_total_subscribers) {
            $newsletter_stats['Total Abonati'] = $result_total_subscribers->fetch_assoc()['count'] ?? 0;
        } else {
            error_log("SQL Error (Total Abonati): " . $conn->error);
            $error_message .= "Eroare la preluarea totalului de abonați. ";
        }

        // Abonati Noi Luna Curenta
        $start_of_month = date('Y-m-01');
        $end_of_month = date('Y-m-t');
        if (columnExists($conn, 'abonati_newsletter', 'data_abonare')) {
            $sql_new_subscribers = "SELECT COUNT(*) as count FROM abonati_newsletter WHERE data_abonare BETWEEN ? AND ?";
            $stmt_new_subscribers = $conn->prepare($sql_new_subscribers);
            if ($stmt_new_subscribers) {
                $stmt_new_subscribers->bind_param("ss", $start_of_month, $end_of_month);
                $stmt_new_subscribers->execute();
                $result_new_subscribers = $stmt_new_subscribers->get_result()->fetch_assoc();
                $newsletter_stats['Abonati Noi Luna Curenta'] = $result_new_subscribers['count'] ?? 0;
                $stmt_new_subscribers->close();
            } else {
                error_log("SQL Error (Abonati Noi): " . $conn->error);
                $error_message .= "Eroare la preluarea abonaților noi. ";
            }
        } else {
            $error_message .= "Coloana 'data_abonare' lipsește din 'abonati_newsletter'. ";
        }
    } else {
        $error_message .= "Tabelul 'abonati_newsletter' sau coloana 'status' lipsește. ";
        // Fallback dacă tabelul nu există
        $newsletter_stats['Total Abonati'] = 1250;
        $newsletter_stats['Abonati Noi Luna Curenta'] = 35;
    }


    // Campanii Trimise și Campanii Recente
    if (tableExists($conn, 'campanii_newsletter') && 
        columnExists($conn, 'campanii_newsletter', 'status') &&
        columnExists($conn, 'campanii_newsletter', 'nume_campanie') &&
        columnExists($conn, 'campanii_newsletter', 'subiect') &&
        columnExists($conn, 'campanii_newsletter', 'data_trimitere') &&
        columnExists($conn, 'campanii_newsletter', 'numar_destinatari') &&
        columnExists($conn, 'campanii_newsletter', 'numar_deschideri') &&
        columnExists($conn, 'campanii_newsletter', 'numar_clickuri')) {

        $sql_campaigns_sent = "SELECT COUNT(*) as count FROM campanii_newsletter WHERE status = 'trimisa'";
        $result_campaigns_sent = $conn->query($sql_campaigns_sent);
        if ($result_campaigns_sent) {
            $newsletter_stats['Campanii Trimise'] = $result_campaigns_sent->fetch_assoc()['count'] ?? 0;
        } else {
            error_log("SQL Error (Campanii Trimise): " . $conn->error);
            $error_message .= "Eroare la preluarea campaniilor trimise. ";
        }

        $sql_recent_campaigns = "SELECT id, nume_campanie, subiect, data_trimitere, status, numar_destinatari, numar_deschideri, numar_clickuri FROM campanii_newsletter ORDER BY data_trimitere DESC LIMIT 10";
        $result_recent_campaigns = $conn->query($sql_recent_campaigns);
        if ($result_recent_campaigns) {
            while($row = $result_recent_campaigns->fetch_assoc()) {
                $recent_campaigns[] = $row;
            }
        } else {
            error_log("SQL Error (Campanii Recente): " . $conn->error);
            $error_message .= "Eroare la preluarea campaniilor recente. ";
        }

        // Rata Deschidere Medie & Rata Click Medie
        $sql_open_click_rates = "SELECT AVG(numar_deschideri / numar_destinatari) * 100 as avg_open, AVG(numar_clickuri / numar_destinatari) * 100 as avg_click FROM campanii_newsletter WHERE status = 'trimisa' AND numar_destinatari > 0";
        $result_rates = $conn->query($sql_open_click_rates);
        if ($result_rates) {
            $rates = $result_rates->fetch_assoc();
            $newsletter_stats['Rata Deschidere Medie'] = $rates['avg_open'] ?? 0;
            $newsletter_stats['Rata Click Medie'] = $rates['avg_click'] ?? 0;
        } else {
            error_log("SQL Error (Rate Deschidere/Click): " . $conn->error);
            $error_message .= "Eroare la calcularea ratelor de deschidere/click. ";
        }

    } else {
        $error_message .= "Tabelul 'campanii_newsletter' sau una dintre coloanele necesare lipsește. ";
        // Fallback dacă tabelul nu există
        $newsletter_stats['Campanii Trimise'] = 45;
        $newsletter_stats['Rata Deschidere Medie'] = 22.5;
        $newsletter_stats['Rata Click Medie'] = 3.8;
        $recent_campaigns = [
            ['id' => 1, 'nume_campanie' => 'Promoția de Vară 2025 (Mock)', 'subiect' => 'Super Oferte!', 'data_trimitere' => '2025-06-20 10:00:00', 'status' => 'trimisa', 'numar_destinatari' => 1000, 'numar_deschideri' => 250, 'numar_clickuri' => 50],
            ['id' => 2, 'nume_campanie' => 'Noutăți Iulie (Mock)', 'subiect' => 'Ultimele Noutăți', 'data_trimitere' => '2025-07-01 11:30:00', 'status' => 'trimisa', 'numar_destinatari' => 1200, 'numar_deschideri' => 300, 'numar_clickuri' => 70],
        ];
    }

} catch (Exception $e) {
    $error_message .= "Eroare generală la preluarea datelor: " . $e->getMessage();
    error_log("General Error in dashboard-newsletter.php: " . $e->getMessage());
} finally {
    if(isset($conn)) { $conn->close(); } // Închidem conexiunea la baza de date
}

?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Dashboard Newsletter</title>

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

    /* Stiluri specifice pentru dashboard newsletter */
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
    .stat-card.border-left-primary { border-left-color: #0d6efd !important; } /* Adăugat pentru al 5-lea card */

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

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .stat-card .card-body {
            flex-direction: column;
            text-align: center;
        }
        .stat-card .widgets-icons {
            margin-bottom: 1rem;
        }
        .stat-card .ms-auto {
            margin-left: 0 !important;
        }
        .d-grid .btn {
            width: 100%;
        }
    }
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
        border-bottom-color: #2a3042; /* Se suprapune peste bordura cardului */
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
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Newsletter</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard Newsletter</li>
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

        <!-- Statistici Cheie Newsletter -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-5 g-4 mb-4">
            <div class="col">
                <div class="card stat-card border-left-info">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Total Abonați</p><h4 class="my-1"><?php echo number_format($newsletter_stats['Total Abonati'], 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-user-plus"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-success">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Campanii Trimise</p><h4 class="my-1"><?php echo number_format($newsletter_stats['Campanii Trimise'], 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bxs-paper-plane"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-warning">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Rata Deschidere Medie</p><h4 class="my-1"><?php echo number_format($newsletter_stats['Rata Deschidere Medie'], 2, ',', '.'); ?>%</h4></div>
                        <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bx-envelope-open"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-danger">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Rata Click Medie</p><h4 class="my-1"><?php echo number_format($newsletter_stats['Rata Click Medie'], 2, ',', '.'); ?>%</h4></div>
                        <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bx-mouse"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-primary">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Abonați Noi (Luna)</p><h4 class="my-1"><?php echo number_format($newsletter_stats['Abonati Noi Luna Curenta'], 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-primary text-primary ms-auto"><i class="bx bx-user-plus"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acțiuni Rapide și Campanii Recente -->
        <div class="row g-4 mb-4">
            <!-- Acțiuni Rapide -->
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Acțiuni Rapide Newsletter</h4>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="creeaza-newsletter.php" class="btn btn-primary"><i class="bx bx-plus me-2"></i>Creează Newsletter Nou</a>
                            <a href="lista-abonati.php" class="btn btn-info"><i class="bx bx-list-ul me-2"></i>Gestionează Abonați</a>
                            <a href="campanii-trimise.php" class="btn btn-success"><i class="bx bx-send me-2"></i>Vezi Campanii Trimise</a>
                            <a href="template-uri-newsletter.php" class="btn btn-secondary"><i class="bx bx-layout me-2"></i>Gestionează Template-uri</a>
                            <a href="setari-newsletter.php" class="btn btn-warning text-dark"><i class="bx bx-cog me-2"></i>Setări Newsletter</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campanii Recente -->
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Campanii Newsletter Recente</h4>
                        <hr>
                        <?php if (empty($recent_campaigns)): ?>
                            <div class="alert alert-info">Nu există campanii recente de afișat.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="recentCampaignsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nume Campanie</th>
                                            <th>Subiect</th>
                                            <th>Dată Trimitere</th>
                                            <th>Status</th>
                                            <th>Destinatari</th>
                                            <th>Deschideri</th>
                                            <th>Clickuri</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_campaigns as $campaign): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($campaign['id']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['nume_campanie']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['subiect']); ?></td>
                                                <td><?php echo $campaign['data_trimitere'] ? (new DateTime($campaign['data_trimitere']))->format('d.m.Y H:i') : 'N/A'; ?></td>
                                                <td>
                                                    <?php 
                                                        $status_class = 'bg-info';
                                                        if ($campaign['status'] == 'trimisa') $status_class = 'bg-success';
                                                        else if ($campaign['status'] == 'programata') $status_class = 'bg-warning text-dark';
                                                        else if ($campaign['status'] == 'esuaa' || $campaign['status'] == 'anulata') $status_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($campaign['status']); ?></span>
                                                </td>
                                                <td><?php echo number_format($campaign['numar_destinatari'], 0, ',', '.'); ?></td>
                                                <td><?php echo number_format($campaign['numar_deschideri'], 0, ',', '.'); ?></td>
                                                <td><?php echo number_format($campaign['numar_clickuri'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <a href="creeaza-newsletter.php?id=<?php echo $campaign['id'] ?? ''; ?>" class="btn btn-sm btn-outline-primary me-2"><i class="bx bx-edit"></i> Editează</a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-campaign-btn" data-id="<?php echo $campaign['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary me-2" id="exportPdfBtn"><i class="bx bxs-file-pdf"></i> Export PDF</button>
                            <button type="button" class="btn btn-success me-2" id="exportExcelBtn"><i class="bx bxs-file-excel"></i> Export Excel</button>
                            <button type="button" class="btn btn-info" id="printReportBtn"><i class="bx bx-printer"></i> Printează</button>
                        </div>
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
    // --- Data PHP pentru JavaScript ---
    const allTemplatesData = <?php echo json_encode($all_templates); ?>;

    // --- Elemente DOM pentru Modale ---
    const addTemplateModal = document.getElementById('addTemplateModal');
    const editTemplateModal = document.getElementById('editTemplateModal');
    const deleteTemplateModal = document.getElementById('deleteTemplateModal');
    const previewTemplateModal = document.getElementById('previewTemplateModal');

    const editTemplateForm = document.getElementById('editTemplateForm');
    const deleteTemplateIdConfirm = document.getElementById('deleteTemplateIdConfirm');
    const deleteTemplateNameDisplay = document.getElementById('deleteTemplateNameDisplay');
    const confirmDeleteTemplateBtn = document.getElementById('confirmDeleteTemplateBtn');
    const templatePreviewIframe = document.getElementById('templatePreviewIframe');
    const previewTemplateName = document.getElementById('previewTemplateName');


    // --- Funcționalitate Filtrare Tabel Principal Șabloane ---
    const searchTemplate = document.getElementById('searchTemplate'); // Adaugă un input de căutare în HTML dacă vrei
    const templatesTableBody = document.getElementById('templatesTableBody');

    function filterTemplatesTable() {
        const searchText = (searchTemplate ? searchTemplate.value.toLowerCase().trim() : ''); // Verifică existența elementului
        
        document.querySelectorAll('#templatesTableBody tr').forEach(row => {
            const rowNume = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const rowSubiect = row.querySelector('td:nth-child(3)').textContent.toLowerCase();

            if (searchText === '' || rowNume.includes(searchText) || rowSubiect.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    // Adaugă event listener doar dacă elementul searchTemplate există
    if (searchTemplate) { 
        searchTemplate.addEventListener('input', filterTemplatesTable);
    }
    filterTemplatesTable(); // Rulează la încărcarea paginii

    // --- Logică Modale Șablon (Editare / Ștergere / Preview) ---

    // Populează modalul de editare șablon
    document.querySelectorAll('.edit-template-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const template = allTemplatesData.find(t => t.id == id);
            if (template) {
                document.getElementById('editTemplateId').value = template.id;
                document.getElementById('editTemplateName').value = template.nume_template;
                document.getElementById('editTemplateSubject').value = template.subiect_implicit || '';
                document.getElementById('editTemplateContent').value = template.continut_html || '';
            }
        });
    });

    // Deschide modalul de confirmare ștergere șablon
    document.querySelectorAll('.delete-template-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentTemplateIdToDelete = this.dataset.id;
            const templateName = this.closest('tr').querySelector('td[data-label="Nume Șablon:"]').textContent;
            deleteTemplateNameDisplay.textContent = templateName;
            new bootstrap.Modal(deleteTemplateModal).show();
        });
    });

    // Confirmă ștergerea șablonului
    confirmDeleteTemplateBtn.addEventListener('click', function() {
        if (currentTemplateIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_template');
            formData.append('id', currentTemplateIdToDelete);

            fetch('process_newsletter.php', {
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
                console.error('Eroare la ștergerea șablonului:', error);
                alert('A apărut o eroare la ștergerea șablonului.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(deleteTemplateModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
    });

    // Populează și afișează modalul de preview șablon
    document.querySelectorAll('.preview-template-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const template = allTemplatesData.find(t => t.id == id);
            if (template) {
                previewTemplateName.textContent = template.nume_template;
                // Încarcă conținutul HTML în iframe
                const iframeDoc = templatePreviewIframe.contentWindow.document;
                iframeDoc.open();
                iframeDoc.write(template.continut_html);
                iframeDoc.close();
                new bootstrap.Modal(previewTemplateModal).show();
            }
        });
    });


    // --- Funcționalitate Export (PDF, Excel, Print) ---
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const title = `Listă Șabloane Newsletter`;
        const headers = [];
        document.querySelectorAll('#templatesTable thead th').forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll('#templatesTableBody tr').forEach(row => {
            if (row.style.display !== 'none') { // Doar rândurile vizibile
                const rowData = [];
                // Excludem coloana de Acțiuni din export (ultima coloană)
                row.querySelectorAll('td:not(:last-child)').forEach(td => {
                    rowData.push(td.textContent);
                });
                data.push(rowData);
            }
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

        doc.save(`Lista_Sabloane_Newsletter.pdf`);
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('templatesTable');
        const clonedTable = table.cloneNode(true);
        const tbody = clonedTable.querySelector('tbody');
        Array.from(tbody.children).forEach(row => {
            if (row.style.display === 'none') {
                tbody.removeChild(row);
            }
        });
        // Elimină coloana "Acțiuni" din clona tabelului înainte de export
        clonedTable.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const ws = XLSX.utils.table_to_sheet(clonedTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Sabloane Newsletter");
        XLSX.writeFile(wb, `Lista_Sabloane_Newsletter.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('templatesTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Șabloane Newsletter</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Șabloane Newsletter</h1>
                ${tableToPrint.outerHTML}
            </body>
            </html>
        `;
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
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
