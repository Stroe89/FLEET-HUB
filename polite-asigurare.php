<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

$today_dt = new DateTime();
$today_str = $today_dt->format('Y-m-d');

// Preluăm setările de notificare (ex: câte zile înainte de expirare) - relevant pentru calcul status
$notifica_expirare_documente_zile = 30; // Valoare implicită, poate fi preluată din DB
$table_exists_notif = $conn->query("SHOW TABLES LIKE 'setari_notificari'")->num_rows > 0;
if ($table_exists_notif) {
    $sql_notif_settings = "SELECT notifica_expirare_documente_zile FROM setari_notificari WHERE id = 1";
    $result_notif_settings = $conn->query($sql_notif_settings);
    if ($result_notif_settings && $result_notif_settings->num_rows > 0) {
        $notifica_expirare_documente_zile = $result_notif_settings->fetch_assoc()['notifica_expirare_documente_zile'];
    }
}


// Preluăm lista de vehicule pentru dropdown-uri de filtrare
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare FROM vehicule ORDER BY model ASC, numar_inmatriculare ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

// Preluăm TOATE documentele active (is_deleted = FALSE)
// Am scos filtrarea pe tip_document pentru a include toate documentele
$all_documents_list = [];
$sql_documents = "
    SELECT 
        d.id, d.id_vehicul, d.tip_document, d.data_expirare, d.nume_document_user, d.cale_fisier, d.important,
        d.observatii, d.numar_referinta,
        v.model, v.numar_inmatriculare
        -- Dacă ai coloana 'tip' în tabela 'vehicule', o poți adăuga aici:
        -- , v.tip as tip_vehicul 
    FROM 
        documente d
    JOIN 
        vehicule v ON d.id_vehicul = v.id
    WHERE 
        d.is_deleted = FALSE 
    ORDER BY d.data_expirare ASC
";
$stmt_documents = $conn->prepare($sql_documents);
if ($stmt_documents) {
    $stmt_documents->execute();
    $result_documents = $stmt_documents->get_result();
    while ($row = $result_documents->fetch_assoc()) {
        // Calculăm statusul și zilele rămase aici, pentru a le pasa la JS
        $data_expirare_dt_obj = new DateTime($row['data_expirare']);
        $interval = $today_dt->diff($data_expirare_dt_obj);
        $days_left = (int)$interval->format('%r%a');

        $status_text = '';
        $status_class = '';
        if ($days_left < 0) {
            $status_text = 'Expirat acum ' . abs($days_left) . ' zile';
            $status_class = 'status-expired';
        } elseif ($days_left <= $notifica_expirare_documente_zile && $days_left >= 0) {
            $status_text = 'Expiră în ' . $days_left . ' zile';
            if ($days_left == 0) $status_text = 'Expiră Astăzi!';
            $status_class = 'status-expiring-soon';
        } else {
            $status_text = 'Valabil';
            $status_class = 'status-valid';
        }
        $row['calculated_status_text'] = $status_text;
        $row['calculated_status_class'] = $status_class;
        $row['calculated_days_remaining'] = $days_left;

        // Infer tip_vehicul based on model for client-side filtering (simple inference)
        $inferred_tip_vehicul = 'Altele'; // Default
        $model_lower = strtolower($row['model']);
        if (str_contains($model_lower, 'autocar')) {
            $inferred_tip_vehicul = 'Autocar';
        } elseif (str_contains($model_lower, 'microbuz')) {
            $inferred_tip_vehicul = 'Microbuz';
        } elseif (str_contains($model_lower, 'sprinter') || str_contains($model_lower, 'transit')) {
            $inferred_tip_vehicul = 'Autoutilitară';
        } elseif (str_contains($model_lower, 'camion') || str_contains($model_lower, 'truck') || str_contains($model_lower, 'daf') || str_contains($model_lower, 'scania')) {
            $inferred_tip_vehicul = 'Camion (Articulat)';
        } elseif (str_contains($model_lower, 'autoturism') || str_contains($model_lower, 'passat') || str_contains($model_lower, 'golf')) {
            $inferred_tip_vehicul = 'Autoturism';
        }
        $row['tip_vehicul'] = $inferred_tip_vehicul; // Pass inferred type to JS

        // Extracting Company from nume_document_user or observatii if not a dedicated field
        // For 'RCA' or 'Asigurare Casco', try to extract company name
        $extracted_company = 'N/A';
        if (in_array($row['tip_document'], ['RCA', 'Asigurare Casco'])) {
            // Simple logic: check if any predefined company name is in doc name or observatii
            foreach (['Allianz-Țiriac', 'Groupama', 'Omniasig', 'Generali', 'Asirom', 'Euroins', 'Grawe', 'Uniqqa', 'Signal Iduna', 'Netcity Asigurări'] as $company_name) {
                if (str_contains(strtolower($row['nume_document_user']), strtolower($company_name)) || str_contains(strtolower($row['observatii']), strtolower($company_name))) {
                    $extracted_company = $company_name;
                    break;
                }
            }
        }
        $row['extracted_company'] = $extracted_company;


        $all_documents_list[] = $row;
    }
    $stmt_documents->close();
}
$conn->close();


// Toate tipurile de documente
$tipuri_documente = ['ITP', 'RCA', 'Rovinieta', 'Asigurare Casco', 'Licenta Transport', 'Altele'];
sort($tipuri_documente); // Sortează alfabetic

// Toate companiile de asigurare (listă extinsă pentru filtrare)
$companii_asigurare = [
    'Allianz-Țiriac', 'Groupama', 'Omniasig', 'Generali', 'Asirom',
    'Euroins', 'Grawe', 'Uniqqa', 'Signal Iduna', 'Netcity Asigurări',
    // Adaugă aici alte companii dacă este cazul
];
sort($companii_asigurare);

// Tipurile de vehicule predefinite (pentru filtrare)
$tipuri_vehicul_predefined = [
    'Autocar', 'Microbuz', 'Minibus (8+1)', 'Camion (Rigid)', 'Camion (Articulat)', 
    'Autoutilitară', 'Furgonetă', 'Trailer (Semiremorcă)', 'Remorcă', 'Autoturism',
    'Mașină de Intervenție', 'Platformă Auto', 'Basculantă', 'Cisternă', 'Frigorifică',
    'Container', 'Duba', 'Altele'
];
sort($tipuri_vehicul_predefined);

?>

<title>NTS TOUR | Gestionare Documente Vehicule</title>

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
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .modal-content {
        background-color: #2a3042 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
    }
    .modal-title {
        color: #ffffff !important;
    }
    .btn-close {
        filter: invert(1);
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
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        border-radius: 0.5rem !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: bold !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover, .btn-outline-primary:hover, .btn-outline-danger:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
    }
    .alert {
        color: #ffffff !important;
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

    /* Stiluri specifice pentru CARDURILE de documente */
    .document-card {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.5rem 1.2rem rgba(0, 0, 0, 0.25);
        transition: all 0.3s ease-in-out;
        height: 100%; /* Ensure cards in a row have same height */
        display: flex;
        flex-direction: column;
    }
    .document-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.35);
    }

    .document-card.status-expired {
        border-left: 5px solid #dc3545;
    }
    .document-card.status-expiring-soon {
        border-left: 5px solid #ffc107;
    }
    .document-card.status-valid {
        border-left: 5px solid #28a745;
    }

    .document-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
    }
    .document-card-header .doc-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #ffffff;
        line-height: 1.2;
    }
    .document-card-header .doc-important-icon {
        color: #ffc107;
        font-size: 1.4rem;
        margin-left: 0.5rem;
    }

    .document-card-body {
        font-size: 0.95rem;
        color: #c0c0c0;
        flex-grow: 1;
    }
    .document-card-body p {
        margin-bottom: 0.5rem;
        color: #e0e0e0 !important;
    }
    .document-card-body strong {
        color: #ffffff !important;
    }
    .document-card-body i {
        margin-right: 0.4rem;
        color: #909090;
    }

    .document-card-footer {
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        margin-top: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    /* Status Badges */
    .badge-status {
        font-size: 0.8em;
        padding: 0.5em 0.8em;
        border-radius: 0.4rem;
        font-weight: 700;
        vertical-align: middle;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .badge-status-expired {
        background-color: #f44336 !important;
        color: #fff !important;
    }
    .badge-status-expiring-soon {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }
    .badge-status-valid {
        background-color: #4caf50 !important;
        color: #fff !important;
    }

    /* Action Buttons within cards */
    .document-card-footer .btn {
        padding: 0.5rem 0.8rem;
        font-size: 0.85rem;
        font-weight: 500;
        min-width: 80px;
    }
    .btn-outline-info {
        color: #0dcaf0 !important;
        border-color: #0dcaf0 !important;
    }
    .btn-outline-info:hover {
        background-color: #0dcaf0 !important;
        color: #212529 !important;
    }
    .btn-outline-warning {
        color: #ffc107 !important;
        border-color: #ffc107 !important;
    }
    .btn-outline-warning:hover {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }
    .btn-outline-danger {
        color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    .btn-outline-danger:hover {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
    .btn-outline-secondary { /* Filter reset button */
        color: #6c757d;
        border-color: #6c757d;
        background-color: transparent;
    }
    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(108, 117, 125, 0.2);
    }

    /* Filter Section Styling */
    .filter-section {
        background-color: #1f2538;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.6rem;
        padding: 1.5rem;
        margin-bottom: 2.5rem;
    }
    .filter-section .form-label {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    .filter-section .form-control, .filter-section .form-select {
        height: calc(2.25rem + 2px);
        padding: 0.375rem 0.75rem;
    }
    .filter-section .input-group-text {
        background-color: #1a2035;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #b0b0b0;
        border-right: none;
    }
    .filter-section .input-group .form-control {
        border-left: none;
    }
    .filter-section .btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }
    /* No results message */
    .no-results-message {
        background-color: #2a3042;
        border: 1px dashed rgba(255, 255, 255, 0.15);
        border-radius: 0.75rem;
        padding: 3rem;
        text-align: center;
        color: #c0c0c0;
        font-size: 1.2rem;
        margin-top: 2rem;
    }
    .no-results-message i {
        font-size: 3rem;
        color: #6a90f1;
        margin-bottom: 1.5rem;
        display: block;
    }
    /* Grid adjustments for responsiveness */
    .row-cols-lg-4 > * {
        flex: 0 0 auto;
        width: 25%;
    }
    @media (max-width: 991.98px) {
        .row-cols-md-3 > * {
            flex: 0 0 auto;
            width: 33.33333333%;
        }
    }
    @media (max-width: 767.98px) {
        .row-cols-sm-2 > * {
            flex: 0 0 auto;
            width: 50%;
        }
    }
    @media (max-width: 575.98px) {
        .row-cols-1 > * {
            flex: 0 0 auto;
            width: 100%;
        }
    }
    /* General site-wide styling (from template/header.php or custom.css) */
    .page-breadcrumb .breadcrumb-item a {
        color: #a0a0a0;
        font-size: 0.95rem;
    }
    .page-breadcrumb .breadcrumb-item a:hover {
        color: #ffffff;
    }
    .page-breadcrumb .breadcrumb-item.active {
        color: #e0e0e0;
        font-weight: 500;
        font-size: 0.95rem;
    }
    .bx {
        vertical-align: middle;
        margin-right: 4px;
        font-size: 1.1em;
    }
</style>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">


<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4">
            <div class="breadcrumb-title pe-3">Gestionare Documente Vehicule</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Centralizator Documente Vehicule</h4>
                        <p class="text-muted mb-4">Vizualizează și gestionează toate documentele asociate vehiculelor din flotă.</p>
                        <hr class="mb-4">

                        <?php if ($success_message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                <div><?php echo $success_message; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                <div><?php echo $error_message; ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-5 filter-section">
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterVehicul" class="form-label"><i class="bx bx-car me-1"></i> Vehicul:</label>
                                    <select class="form-select" id="filterVehicul">
                                        <option value="">Toate Vehiculele</option>
                                        <?php foreach ($vehicule_list as $veh): ?>
                                            <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterTipVehicul" class="form-label"><i class="bx bx-truck me-1"></i> Tip Vehicul (predefinit):</label>
                                    <select class="form-select" id="filterTipVehicul">
                                        <option value="">Toate Tipurile</option>
                                        <?php foreach ($tipuri_vehicul_predefined as $tip): ?>
                                            <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterTipDocument" class="form-label"><i class="bx bx-category me-1"></i> Tip Document:</label>
                                    <select class="form-select" id="filterTipDocument">
                                        <option value="">Toate Tipurile</option>
                                        <?php foreach ($tipuri_documente as $tip): ?>
                                            <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterStatusExpirare" class="form-label"><i class="bx bx-info-circle me-1"></i> Stare Expirare:</label>
                                    <select class="form-select" id="filterStatusExpirare">
                                        <option value="">Toate Stările</option>
                                        <option value="expirat">Expirate</option>
                                        <option value="expira_curand">Expiră Curând</option>
                                        <option value="valabil">Valabile</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterStartDate" class="form-label"><i class="bx bx-calendar-event me-1"></i> Expiră de la:</label>
                                    <input type="date" class="form-control" id="filterStartDate">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterEndDate" class="form-label"><i class="bx bx-calendar-event me-1"></i> Expiră până la:</label>
                                    <input type="date" class="form-control" id="filterEndDate">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="sortBy" class="form-label"><i class="bx bx-sort-alt-2 me-1"></i> Sortează după:</label>
                                    <select class="form-select" id="sortBy">
                                        <option value="data_expirare">Dată Expirare</option>
                                        <option value="nume_document_user">Nume Document</option>
                                        <option value="tip_document">Tip Document</option>
                                        <option value="model">Model Vehicul</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="sortOrder" class="form-label"><i class="bx bx-sort-down me-1"></i> Ordine:</label>
                                    <select class="form-select" id="sortOrder">
                                        <option value="ASC">Ascendent</option>
                                        <option value="DESC">Descendent</option>
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" class="btn btn-primary" id="applyFiltersBtn"><i class="bx bx-filter-alt me-1"></i> Aplică Filtre</button>
                                    <button type="button" class="btn btn-secondary" id="resetFiltersBtn"><i class="bx bx-reset me-1"></i> Resetează Filtre</button>
                                </div>
                            </div>
                        </div>

                        <?php if (empty($all_documents_list)): ?>
                            <div class="no-results-message">
                                <i class="bx bx-info-circle"></i>
                                <p>Nu s-au găsit documente în baza de date.</p>
                                <p>Poți adăuga documente noi din secțiunea "Adaugă Document Nou".</p>
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="documentsCardsContainer">
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="viewDocumentDetailsModal" tabindex="-1" aria-labelledby="viewDocumentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDocumentDetailsModalLabel"><i class="bx bx-detail me-2"></i> Detalii Complete Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-car me-1"></i> Vehicul:</label>
                        <p class="form-control-plaintext text-white" id="detailVehicul"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-truck me-1"></i> Tip Vehicul:</label>
                        <p class="form-control-plaintext text-white" id="detailTipVehicul"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-tag me-1"></i> Tip Document:</label>
                        <p class="form-control-plaintext text-white" id="detailTipDocument"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-file-text me-1"></i> Nume Document:</label>
                        <p class="form-control-plaintext text-white" id="detailNumeDocument"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-calendar-alt me-1"></i> Dată Expirare:</label>
                        <p class="form-control-plaintext text-white" id="detailDataExpirare"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-check-shield me-1"></i> Status Expirare:</label>
                        <p class="form-control-plaintext text-white" id="detailStatusExpirare"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-star me-1"></i> Important:</label>
                        <p class="form-control-plaintext text-white" id="detailImportant"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-barcode me-1"></i> Număr Referință Internă:</label>
                        <p class="form-control-plaintext text-white" id="detailNumarReferinta"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bx bx-comment-detail me-1"></i> Observații:</label>
                        <p class="form-control-plaintext text-white" id="detailObservatii"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bx bx-file-blank me-1"></i> Fișier Atașat:</label>
                        <div id="detailFileContainer"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bx bx-x me-1"></i> Închide</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bx bx-trash me-2"></i> Confirmă Ștergerea</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi acest document? Această acțiune îl va marca ca inactiv și nu poate fi anulată direct din interfață.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <form id="deleteDocumentForm" action="process_document.php" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="document_id" id="modalDocumentId">
                    <input type="hidden" name="id_vehicul" id="modalVehiculId">
                    <button type="submit" class="btn btn-danger"><i class="bx bx-trash-alt me-1"></i> Șterge Documentul</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentsCardsContainer = document.getElementById('documentsCardsContainer');
    const viewDocumentDetailsModal = document.getElementById('viewDocumentDetailsModal');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    const modalDocumentId = document.getElementById('modalDocumentId');
    const modalVehiculId = document.getElementById('modalVehiculId');

    // Date documente complete, calculate și pregătite de PHP
    const allDocuments = <?php echo json_encode($all_documents_list); ?>;
    const documentsMap = {};
    allDocuments.forEach(doc => {
        documentsMap[doc.id] = doc;
    });

    const tipuriVehiculPredefined = <?php echo json_encode($tipuri_vehicul_predefined); ?>;
    const notificaExpirareZile = <?php echo $notifica_expirare_documente_zile; ?>;

    // Funcție pentru a genera HTML-ul unui card de document
    const generateDocumentCardHTML = (doc) => {
        const dataExpirareFormatted = doc.data_expirare ? new Date(doc.data_expirare).toLocaleDateString('ro-RO') : 'N/A';
        const isImportant = doc.important == 1;

        // Calculăm statusul și clasa badge-ului direct în JS pentru coerență cu filtrarea
        let statusText = '';
        let statusClass = '';
        const today = new Date();
        const expirationDate = new Date(doc.data_expirare);
        const diffTime = expirationDate.getTime() - today.getTime();
        const daysLeft = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (daysLeft < 0) {
            statusText = `Expirat acum ${Math.abs(daysLeft)} zile`;
            statusClass = 'status-expired';
        } else if (daysLeft <= notificaExpirareZile && daysLeft >= 0) {
            statusText = `Expiră în ${daysLeft} zile`;
            if (daysLeft === 0) statusText = 'Expiră Astăzi!';
            statusClass = 'status-expiring-soon';
        } else {
            statusText = 'Valabil';
            statusClass = 'status-valid';
        }
        
        // Ensure doc object has these for proper filtering (if not coming from PHP pre-calculated)
        doc.calculated_status_text = statusText;
        doc.calculated_status_class = statusClass;
        doc.calculated_days_remaining = daysLeft;

        // Display company for insurance policies if extracted
        let companyDisplay = '';
        if (doc.tip_document === 'RCA' || doc.tip_document === 'Asigurare Casco') {
            companyDisplay = doc.extracted_company ? `<p><i class="bx bx-building-house"></i> Companie: <strong>${doc.extracted_company}</strong></p>` : '';
        }


        return `
            <div class="col"
                 data-id="${doc.id}"
                 data-id-vehicul="${doc.id_vehicul}"
                 data-tip-document="${doc.tip_document}"
                 data-nume-document="${doc.nume_document_user}"
                 data-data-expirare="${doc.data_expirare}"
                 data-cale-fisier="${doc.cale_fisier || ''}"
                 data-important="${doc.important}"
                 data-model="${doc.model}"
                 data-numar-inmatriculare="${doc.numar_inmatriculare}"
                 data-observatii="${doc.observatii || ''}"
                 data-numar-referinta="${doc.numar_referinta || ''}"
                 data-calculated-status-text="${statusText}"
                 data-calculated-days-remaining="${daysLeft}"
                 data-tip-vehicul="${doc.tip_vehicul || 'N/A'}"
                 data-extracted-company="${doc.extracted_company || 'N/A'}"
            >
                <div class="document-card ${statusClass}">
                    <div class="document-card-header">
                        <div class="doc-title">
                            ${doc.nume_document_user}
                            ${isImportant ? '<i class="bx bxs-star doc-important-icon" title="Document Important"></i>' : ''}
                        </div>
                        <span class="badge badge-status badge-${statusClass}">${statusText}</span>
                    </div>
                    <div class="document-card-body">
                        <p><i class="bx bx-car"></i> Vehicul: <strong>${doc.model} (${doc.numar_inmatriculare})</strong></p>
                        <p><i class="bx bx-tag"></i> Tip Document: <strong>${doc.tip_document}</strong></p>
                        ${companyDisplay}
                        <p><i class="bx bx-calendar-alt"></i> Expiră la: <strong>${dataExpirareFormatted}</strong></p>
                        ${doc.numar_referinta ? `<p><i class="bx bx-barcode"></i> Ref. Internă: <strong>${doc.numar_referinta}</strong></p>` : ''}
                        ${doc.observatii ? `<p><i class="bx bx-comment-detail"></i> Obs: ${doc.observatii.substring(0, 50)}${doc.observatii.length > 50 ? '...' : ''}</p>` : ''}
                    </div>
                    <div class="document-card-footer">
                        ${doc.cale_fisier ? `<a href="${doc.cale_fisier}" target="_blank" class="btn btn-sm btn-outline-info" title="Vezi Document"><i class="bx bx-file"></i> Vezi</a>` : ''}
                        <a href="edit-document.php?id=${doc.id}&vehicul_id=${doc.id_vehicul}" class="btn btn-sm btn-outline-warning" title="Editează Document"><i class="bx bx-edit"></i> Editează</a>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" data-doc-id="${doc.id}" data-vehicul-id="${doc.id_vehicul}" title="Șterge Document"><i class="bx bx-trash"></i> Șterge</button>
                        
                        ${daysLeft <= 7 && daysLeft >= 0 ? `
                            <button type="button" class="btn btn-sm btn-outline-success send-whatsapp-btn" data-document-id="${doc.id}"><i class="bx bxl-whatsapp"></i> WhatsApp</button>
                            <button type="button" class="btn btn-sm btn-outline-primary send-email-btn" data-document-id="${doc.id}"><i class="bx bx-envelope"></i> Email</button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    };

    // Funcție pentru a randa documentele filtrate/sortate
    function renderDocuments(documentsToRender) {
        documentsCardsContainer.innerHTML = '';
        if (documentsToRender.length === 0) {
            documentsCardsContainer.innerHTML = `
                <div class="col-12">
                    <div class="no-results-message">
                        <i class="bx bx-info-circle"></i>
                        <p>Nu s-au găsit documente care să corespundă criteriilor de căutare/filtrare.</p>
                        <p>Totul este în regulă! Fără documente care necesită atenție.</p>
                    </div>
                </div>
            `;
        } else {
            documentsToRender.forEach(doc => {
                documentsCardsContainer.innerHTML += generateDocumentCardHTML(doc);
            });
        }
    }

    // Logica de filtrare și sortare
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipVehicul = document.getElementById('filterTipVehicul');
    const filterTipDocument = document.getElementById('filterTipDocument'); // Changed from filterTipPolita
    const filterStatusExpirare = document.getElementById('filterStatusExpirare');
    const filterStartDate = document.getElementById('filterStartDate');
    const filterEndDate = document.getElementById('filterEndDate');
    const sortBy = document.getElementById('sortBy');
    const sortOrder = document.getElementById('sortOrder');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const filterSearchText = document.getElementById('filterSearchText'); // Add an ID to your general search input if you want it to be separate

    // New filter: Company (for insurance types)
    const filterCompanieAsigurare = document.getElementById('filterCompanieAsigurare'); // Assuming this element exists


    function applyFiltersAndSort() {
        let filteredDocuments = [...allDocuments];

        const selectedVehiculId = filterVehicul.value;
        const selectedTipVehicul = filterTipVehicul.value;
        const selectedTipDocument = filterTipDocument.value;
        const selectedStatusExpirare = filterStatusExpirare.value;
        const selectedCompanieAsigurare = filterCompanieAsigurare ? filterCompanieAsigurare.value : ''; // Check if element exists
        const startDate = filterStartDate.value ? new Date(filterStartDate.value).getTime() : null;
        const endDate = filterEndDate.value ? new Date(filterEndDate.value).getTime() : null;
        const searchText = filterSearchText ? filterSearchText.value.toLowerCase().trim() : '';

        filteredDocuments = filteredDocuments.filter(doc => {
            let match = true;

            // Filter by Vehicle
            if (selectedVehiculId && doc.id_vehicul != selectedVehiculId) {
                match = false;
            }
            
            // Filter by Tip Vehicul (now using inferred type from PHP)
            if (selectedTipVehicul && doc.tip_vehicul !== selectedTipVehicul) {
                 match = false; 
            }
            
            // Filter by Document Type
            if (selectedTipDocument && doc.tip_document !== selectedTipDocument) {
                match = false;
            }

            // Filter by Company (only if the filter element exists and is selected)
            if (selectedCompanieAsigurare && doc.extracted_company !== selectedCompanieAsigurare) {
                match = false;
            }

            // Filter by Expiration Status
            if (selectedStatusExpirare) {
                const docCurrentStatusText = doc.calculated_status_text.toLowerCase();
                
                let isStatusMatch = false;
                if (selectedStatusExpirare === 'expirat' && docCurrentStatusText.includes('expirat')) {
                    isStatusMatch = true;
                } else if (selectedStatusExpirare === 'expira_curand' && (docCurrentStatusText.includes('expiră') || docCurrentStatusText.includes('expira'))) {
                    isStatusMatch = true;
                } else if (selectedStatusExpirare === 'valabil' && docCurrentStatusText.includes('valabil')) {
                    isStatusMatch = true;
                }
                if (!isStatusMatch) match = false;
            }
            
            // Filter by Search Text
            if (searchText) {
                const searchableText = `${doc.nume_document_user} ${doc.model} ${doc.numar_inmatriculare} ${doc.tip_document} ${doc.observatii} ${doc.numar_referinta} ${doc.tip_vehicul} ${doc.extracted_company || ''}`.toLowerCase(); // Added extracted_company
                if (!searchableText.includes(searchText)) {
                    match = false;
                }
            }
            
            // Filter by Date Range
            if (startDate) {
                const docExpDateMs = new Date(doc.data_expirare).getTime();
                if (docExpDateMs < startDate) {
                    match = false;
                }
            }
            if (endDate) {
                const docExpDateMs = new Date(doc.data_expirare).getTime();
                if (docExpDateMs > endDate) {
                    match = false;
                }
            }
            return match;
        });

        // Sorting
        const currentSortBy = sortBy.value;
        const currentSortOrder = sortOrder.value;

        filteredDocuments.sort((a, b) => {
            let valA, valB;

            switch (currentSortBy) {
                case 'data_expirare':
                    valA = new Date(a.data_expirare).getTime();
                    valB = new Date(b.data_expirare).getTime();
                    break;
                case 'nume_document_user':
                    valA = a.nume_document_user.toLowerCase();
                    valB = b.nume_document_user.toLowerCase();
                    break;
                case 'tip_document':
                    valA = a.tip_document.toLowerCase();
                    valB = b.tip_document.toLowerCase();
                    break;
                case 'model':
                    valA = a.model.toLowerCase();
                    valB = b.model.toLowerCase();
                    break;
                default: // Default to data_expirare
                    valA = new Date(a.data_expirare).getTime();
                    valB = new Date(b.data_expirare).getTime();
            }

            if (typeof valA === 'string') {
                return currentSortOrder === 'ASC' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            } else { // numeric comparison
                return currentSortOrder === 'ASC' ? valA - valB : valB - valA;
            }
        });

        renderDocuments(filteredDocuments);
    }

    function resetFilters() {
        // Reset all filter inputs to their default "empty" state
        filterVehicul.value = '';
        filterTipVehicul.value = ''; 
        filterTipDocument.value = '';
        filterStatusExpirare.value = '';
        if (filterCompanieAsigurare) filterCompanieAsigurare.value = ''; // Reset if exists
        filterStartDate.value = '';
        filterEndDate.value = '';
        sortBy.value = 'data_expirare'; // Default sort order
        sortOrder.value = 'ASC'; // Default sort direction
        if (filterSearchText) filterSearchText.value = ''; // Reset if exists

        renderDocuments(allDocuments); // Render all original documents
    }

    // Attach event listeners to filter controls and buttons
    applyFiltersBtn.addEventListener('click', applyFiltersAndSort);
    resetFiltersBtn.addEventListener('click', resetFilters);

    // Dynamic filtering on input/change
    document.querySelectorAll('#filterVehicul, #filterTipVehicul, #filterTipDocument, #filterStatusExpirare, #filterStartDate, #filterEndDate, #sortBy, #sortOrder').forEach(el => {
        el.addEventListener('change', applyFiltersAndSort);
    });
    // Attach listener for company filter if it exists
    if (filterCompanieAsigurare) {
        filterCompanieAsigurare.addEventListener('change', applyFiltersAndSort);
    }
    if (filterSearchText) {
        filterSearchText.addEventListener('input', applyFiltersAndSort); // Real-time search as user types
    }


    // Initial render of all documents when page loads
    renderDocuments(allDocuments);


    // Populate View Details Modal (Event delegation for dynamically added cards)
    documentsCardsContainer.addEventListener('click', function(e) {
        const viewDetailsBtn = e.target.closest('.btn-outline-info');
        if (viewDetailsBtn) {
            const cardElement = viewDetailsBtn.closest('.col'); // Get the parent column element
            const docId = cardElement.dataset.id;
            const doc = documentsMap[docId]; // Get full data from the map

            if (doc) {
                document.getElementById('detailVehicul').textContent = `${doc.model} (${doc.numar_inmatriculare})`;
                document.getElementById('detailTipVehicul').textContent = doc.tip_vehicul || 'N/A'; 
                document.getElementById('detailTipDocument').textContent = doc.tip_document;
                document.getElementById('detailNumeDocument').textContent = doc.nume_document_user; // Changed from detailNumePolita
                
                const dataExpirareDt = new Date(doc.data_expirare);
                document.getElementById('detailDataExpirare').textContent = dataExpirareDt.toLocaleDateString('ro-RO');
                document.getElementById('detailStatusExpirare').textContent = doc.calculated_status_text; // Changed from detailStatus
                
                // Populate company for details modal only if it's an insurance policy
                const detailCompanieAsigurare = document.getElementById('detailCompanieAsigurare');
                if (detailCompanieAsigurare) {
                    if (doc.tip_document === 'RCA' || doc.tip_document === 'Asigurare Casco') {
                        detailCompanieAsigurare.closest('.col-md-6').style.display = ''; // Show container
                        detailCompanieAsigurare.textContent = doc.extracted_company || 'N/A';
                    } else {
                        detailCompanieAsigurare.closest('.col-md-6').style.display = 'none'; // Hide container
                    }
                }

                document.getElementById('detailImportant').textContent = doc.important == 1 ? 'Da' : 'Nu';
                document.getElementById('detailNumarReferinta').textContent = doc.numar_referinta || 'N/A';
                document.getElementById('detailObservatii').textContent = doc.observatii || 'N/A';

                const detailFileContainer = document.getElementById('detailFileContainer');
                detailFileContainer.innerHTML = ''; 

                if (doc.cale_fisier) {
                    const fileExtension = doc.cale_fisier.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
                    const isPdf = fileExtension === 'pdf';

                    if (isPdf) {
                        detailFileContainer.innerHTML = `<iframe src="${doc.cale_fisier}" width="100%" height="400px" style="border: none; border-radius: 0.5rem; background-color: #3b435a;"></iframe>`;
                    } else if (isImage) {
                        detailFileContainer.innerHTML = `<img src="${doc.cale_fisier}" alt="Previzualizare Document" style="max-width: 100%; height: auto; display: block; margin: 0 auto; border-radius: 0.5rem;">`;
                    } else {
                        detailFileContainer.innerHTML = `<div class="alert alert-info text-center"><i class="bx bx-file" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>Previzualizare indisponibilă pentru acest tip de fișier.</div>`;
                    }
                    detailFileContainer.innerHTML += `<a href="${doc.cale_fisier}" target="_blank" class="btn btn-sm btn-info mt-2"><i class="bx bx-download"></i> Descarcă Fișier Original</a>`;
                } else {
                    detailFileContainer.innerHTML = `<div class="alert alert-warning">Nu există fișier atașat pentru acest document.</div>`;
                }

                const viewDocumentDetailsModalInstance = new bootstrap.Modal(viewDocumentDetailsModal);
                viewDocumentDetailsModalInstance.show();
            }
        }
    });

    // Delete Confirmation Modal (Event delegation)
    documentsCardsContainer.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-doc-btn');
        if (deleteBtn) {
            const cardElement = deleteBtn.closest('.col');
            const docId = cardElement.dataset.id;
            const vehiculId = cardElement.dataset.idVehicul;

            modalDocumentId.value = docId;
            modalVehiculId.value = vehiculId;
            deleteModal.show();
        }
    });

    // Logic for Notification Buttons (WhatsApp and Email) - assuming direct contact for policies too
    documentsCardsContainer.addEventListener('click', function(e) {
        const whatsappBtn = e.target.closest('.send-whatsapp-btn');
        const emailBtn = e.target.closest('.send-email-btn');

        let targetButton = null;
        if (whatsappBtn) targetButton = whatsappBtn;
        else if (emailBtn) targetButton = emailBtn;

        if (targetButton) {
            const cardElement = targetButton.closest('.col');
            const docId = cardElement.dataset.id;
            const doc = documentsMap[docId]; // Get data from the map for full details

            if (!doc) {
                console.error("Document not found in map for ID:", docId);
                alert("Eroare: Detaliile documentului nu au putut fi preluate pentru notificare.");
                return;
            }

            const vehicleModel = doc.model;
            const vehicleNumar = doc.numar_inmatriculare;
            const docType = doc.tip_document;
            const docName = doc.nume_document_user;
            const docExpirationDate = new Date(doc.data_expirare).toLocaleDateString('ro-RO');
            const expirationStatus = doc.calculated_status_text;

            // Placeholder for contacts (replace with real logic to fetch contacts from DB, e.g., driver's phone/email for the vehicle)
            const contactPhone = '407xxxxxxxx'; 
            const contactEmail = 'responsabil@companie.com'; 

            const message = `Salut! Documentul ${docType} (${docName}) pentru vehiculul ${vehicleModel} (${vehicleNumar}) expiră la data de ${docExpirationDate}. Status: ${expirationStatus}. Te rog să iei măsuri.`;
            const emailSubject = `Alertă Expirare Document Vehicul: ${docType} pentru ${vehicleNumar}`;

            if (targetButton === whatsappBtn) {
                const whatsappUrl = `https://wa.me/${contactPhone}?text=${encodeURIComponent(message)}`;
                window.open(whatsappUrl, '_blank');
            } else if (targetButton === emailBtn) {
                const emailUrl = `mailto:${contactEmail}?subject=${encodeURIComponent(emailSubject)}&body=${encodeURIComponent(message)}`;
                window.open(emailUrl, '_blank');
            }
        }
    });

    // Fix for page blocking after closing modals (generic)
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