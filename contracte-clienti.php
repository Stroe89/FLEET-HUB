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

// Preluăm setările de notificare pentru expirarea contractelor (dacă există)
$notifica_expirare_contracte_zile = 60; // Valoare implicită
// Ideal, am avea o tabelă `setari_notificari` pentru contracte similar cu documente

// Preluăm lista de clienți pentru dropdown-uri de filtrare
$clienti_list = [];
$stmt_clienti = $conn->prepare("SELECT id, nume_companie FROM clienti ORDER BY nume_companie ASC");
if ($stmt_clienti) {
    $stmt_clienti->execute();
    $result_clienti = $stmt_clienti->get_result();
    while ($row = $result_clienti->fetch_assoc()) {
        $clienti_list[] = $row;
    }
    $stmt_clienti->close();
}

// Preluăm TOATE contractele active
$all_contracts_list = [];
$sql_contracts = "
    SELECT 
        c.id, c.id_client, c.nume_contract, c.tip_contract, c.numar_contract, c.data_semnare, c.data_inceput, c.data_expirare, 
        c.valoare_contract, c.moneda, c.status_contract, c.termeni_plata, c.persoana_contact_client, c.email_contact_client, c.telefon_contact_client,
        c.cale_fisier, c.nume_original_fisier, c.observatii, c.is_deleted,
        cl.nume_companie, cl.cui as cod_fiscal, cl.nr_reg_com as reg_comertului, cl.email_contact as email_contact_principal, cl.telefon as telefon_contact_principal
    FROM 
        contracte c
    JOIN 
        clienti cl ON c.id_client = cl.id
    WHERE 
        c.is_deleted = FALSE 
    ORDER BY c.data_expirare ASC
";
$stmt_contracts = $conn->prepare($sql_contracts);
if ($stmt_contracts) {
    $stmt_contracts->execute();
    $result_contracts = $stmt_contracts->get_result();
    while ($row = $result_contracts->fetch_assoc()) {
        // Calculăm statusul de expirare și zilele rămase
        $contract_exp_dt_obj = new DateTime($row['data_expirare']);
        $interval = $today_dt->diff($contract_exp_dt_obj);
        $days_left = (int)$interval->format('%r%a');

        $status_text_exp = '';
        $status_class_exp = '';
        if ($days_left < 0) {
            $status_text_exp = 'Expirat acum ' . abs($days_left) . ' zile';
            $status_class_exp = 'status-expired';
        } elseif ($days_left <= $notifica_expirare_contracte_zile && $days_left >= 0) {
            $status_text_exp = 'Expiră în ' . $days_left . ' zile';
            if ($days_left == 0) $status_text_exp = 'Expiră Astăzi!';
            $status_class_exp = 'status-expiring-soon';
        } else {
            $status_text_exp = 'Valabil';
            $status_class_exp = 'status-valid';
        }
        $row['calculated_status_text'] = $status_text_exp;
        $row['calculated_status_class'] = $status_class_exp;
        $row['calculated_days_remaining'] = $days_left;

        $all_contracts_list[] = $row;
    }
    $stmt_contracts->close();
}
$conn->close();

// Liste pentru filtre
$tipuri_contracte = ['Transport Marfă', 'Închiriere Autocar', 'Logistice', 'Mentenanță', 'Altele'];
sort($tipuri_contracte);

$statusuri_contracte = ['Activ', 'Expirat', 'Suspendat', 'În Negociere', 'Anulat'];
sort($statusuri_contracte);

?>

<title>NTS TOUR | Contracte Clienți</title>

<style>
    /* Stiluri generale preluate din tema (similar cu ce am folosit la documente.php) */
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

    /* Stiluri specifice pentru CARDURILE de contracte */
    .contract-card {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.5rem 1.2rem rgba(0, 0, 0, 0.25);
        transition: all 0.3s ease-in-out;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .contract-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.35);
    }

    .contract-card.status-expired {
        border-left: 5px solid #dc3545;
    }
    .contract-card.status-expiring-soon {
        border-left: 5px solid #ffc107;
    }
    .contract-card.status-valid {
        border-left: 5px solid #28a745;
    }
    .contract-card.status-in-negociere, .contract-card.status-anulat, .contract-card.status-suspendat {
        border-left: 5px solid #6c757d; /* Grey for other statuses */
    }


    .contract-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
    }
    .contract-card-header .contract-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #ffffff;
        line-height: 1.2;
    }

    .contract-card-body {
        font-size: 0.95rem;
        color: #c0c0c0;
        flex-grow: 1;
    }
    .contract-card-body p {
        margin-bottom: 0.5rem;
        color: #e0e0e0 !important;
    }
    .contract-card-body strong {
        color: #ffffff !important;
    }
    .contract-card-body i {
        margin-right: 0.4rem;
        color: #909090;
    }

    .contract-card-footer {
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
    .badge-status-valid { /* For contracts, 'Valid' for expiry date, 'Activ' for contract status */
        background-color: #4caf50 !important;
        color: #fff !important;
    }
    .badge-status-activ { /* Specific for contract status = Activ */
        background-color: #198754 !important;
        color: #fff !important;
    }
    .badge-status-in-negociere {
        background-color: #0dcaf0 !important;
        color: #212529 !important;
    }
    .badge-status-suspendat, .badge-status-anulat {
        background-color: #6c757d !important;
        color: #fff !important;
    }

    /* Action Buttons within cards */
    .contract-card-footer .btn {
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
    .btn-outline-secondary {
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
            <div class="breadcrumb-title pe-3">Gestionare Contracte</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Centralizator Contracte Clienți</h4>
                        <p class="text-muted mb-4">Vizualizează și gestionează toate contractele active sau istorice cu clienții.</p>
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
                                    <label for="filterClient" class="form-label"><i class="bx bx-user me-1"></i> Client:</label>
                                    <select class="form-select" id="filterClient">
                                        <option value="">Toți Clienții</option>
                                        <?php foreach ($clienti_list as $client): ?>
                                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['nume_companie']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterTipContract" class="form-label"><i class="bx bx-category me-1"></i> Tip Contract:</label>
                                    <select class="form-select" id="filterTipContract">
                                        <option value="">Toate Tipurile</option>
                                        <?php foreach ($tipuri_contracte as $tip): ?>
                                            <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterStatusContract" class="form-label"><i class="bx bx-info-circle me-1"></i> Status Contract:</label>
                                    <select class="form-select" id="filterStatusContract">
                                        <option value="">Toate Statusurile</option>
                                        <?php foreach ($statusuri_contracte as $status): ?>
                                            <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filterSearchText" class="form-label"><i class="bx bx-search me-1"></i> Căutare rapidă:</label>
                                    <input type="text" class="form-control" id="filterSearchText" placeholder="Nume, Număr contract, Client...">
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
                                        <option value="nume_contract">Nume Contract</option>
                                        <option value="nume_companie">Client</option>
                                        <option value="status_contract">Status Contract</option>
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
                                    <a href="adauga-contract.php" class="btn btn-success"><i class="bx bx-plus-circle me-1"></i> Adaugă Contract Rapid</a>
                                </div>
                            </div>
                        </div>

                        <?php if (empty($all_contracts_list)): ?>
                            <div class="no-results-message">
                                <i class="bx bx-info-circle"></i>
                                <p>Nu s-au găsit contracte în baza de date.</p>
                                <p>Asigură-te că ai adăugat clienți și contracte.</p>
                                <a href="adauga-contract.php" class="btn btn-primary mt-3"><i class="bx bx-plus-circle me-1"></i> Adaugă Contract Nou</a>
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="contractsCardsContainer">
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="viewContractDetailsModal" tabindex="-1" aria-labelledby="viewContractDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewContractDetailsModalLabel"><i class="bx bx-detail me-2"></i> Detalii Complete Contract</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-user me-1"></i> Client:</label>
                        <p class="form-control-plaintext text-white" id="detailClientName"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-hash me-1"></i> Număr Contract:</label>
                        <p class="form-control-plaintext text-white" id="detailNumarContract"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-file-text me-1"></i> Nume Contract:</label>
                        <p class="form-control-plaintext text-white" id="detailNumeContract"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-category me-1"></i> Tip Contract:</label>
                        <p class="form-control-plaintext text-white" id="detailTipContract"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-calendar-check me-1"></i> Dată Semnare:</label>
                        <p class="form-control-plaintext text-white" id="detailDataSemnare"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-calendar-plus me-1"></i> Dată Început:</label>
                        <p class="form-control-plaintext text-white" id="detailDataInceput"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-calendar-x me-1"></i> Dată Expirare:</label>
                        <p class="form-control-plaintext text-white" id="detailDataExpirare"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-check-shield me-1"></i> Status Contract:</label>
                        <p class="form-control-plaintext text-white" id="detailStatusContract"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-dollar me-1"></i> Valoare Contract:</label>
                        <p class="form-control-plaintext text-white" id="detailValoareContract"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-wallet-alt me-1"></i> Termeni Plată:</label>
                        <p class="form-control-plaintext text-white" id="detailTermeniPlata"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-phone me-1"></i> Contact Client (Nume):</label>
                        <p class="form-control-plaintext text-white" id="detailContactClientNume"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-envelope me-1"></i> Contact Client (Email):</label>
                        <p class="form-control-plaintext text-white" id="detailContactClientEmail"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-phone-call me-1"></i> Contact Client (Telefon):</label>
                        <p class="form-control-plaintext text-white" id="detailContactClientTelefon"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bx bx-comment-detail me-1"></i> Observații:</label>
                        <p class="form-control-plaintext text-white" id="detailObservatii"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bx bx-file-blank me-1"></i> Fișier Contract:</label>
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
                Ești sigur că vrei să ștergi acest contract? Această acțiune îl va marca ca inactiv și nu poate fi anulată direct din interfață.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <form id="deleteContractForm" action="process_contract.php" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="contract_id" id="modalContractId">
                    <button type="submit" class="btn btn-danger"><i class="bx bx-trash-alt me-1"></i> Șterge Contractul</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contractsCardsContainer = document.getElementById('contractsCardsContainer');
    const viewContractDetailsModal = document.getElementById('viewContractDetailsModal');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    const modalContractId = document.getElementById('modalContractId');

    // Date contracte complete, calculate și pregătite de PHP
    const allContracts = <?php echo json_encode($all_contracts_list); ?>;
    const contractsMap = {};
    allContracts.forEach(contract => {
        contractsMap[contract.id] = contract;
    });

    const notificaExpirareContracteZile = 60; // Hardcoded, ideal ar veni din PHP setari

    // Funcție pentru a genera HTML-ul unui card de contract
    const generateContractCardHTML = (contract) => {
        const dataExprareFormatted = contract.data_expirare ? new Date(contract.data_expirare).toLocaleDateString('ro-RO') : 'N/A';
        const dataSemnareFormatted = contract.data_semnare ? new Date(contract.data_semnare).toLocaleDateString('ro-RO') : 'N/A';
        const dataInceputFormatted = contract.data_inceput ? new Date(contract.data_inceput).toLocaleDateString('ro-RO') : 'N/A';

        // Calculăm statusul de expirare și clasa badge-ului direct în JS
        let expirationStatusText = '';
        let expirationStatusClass = '';
        const today = new Date();
        const expirationDate = new Date(contract.data_expirare);
        const diffTime = expirationDate.getTime() - today.getTime();
        const daysLeft = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (daysLeft < 0) {
            expirationStatusText = `Expirat acum ${Math.abs(daysLeft)} zile`;
            expirationStatusClass = 'status-expired';
        } else if (daysLeft <= notificaExpirareContracteZile && daysLeft >= 0) {
            expirationStatusText = `Expiră în ${daysLeft} zile`;
            if (daysLeft === 0) expirationStatusText = 'Expiră Astăzi!';
            expirationStatusClass = 'status-expiring-soon';
        } else {
            expirationStatusText = 'Valabil';
            expirationStatusClass = 'status-valid';
        }

        // Clasa și textul pentru statusul general al contractului
        let contractStatusText = contract.status_contract;
        let contractStatusClass = 'badge-status-activ'; // Default for "Activ"
        if (contract.status_contract === 'Expirat') {
            contractStatusClass = 'badge-status-expired';
        } else if (contract.status_contract === 'În Negociere') {
            contractStatusClass = 'badge-status-in-negociere';
        } else if (contract.status_contract === 'Suspendat') {
            contractStatusClass = 'badge-status-suspendat';
        } else if (contract.status_contract === 'Anulat') {
            contractStatusClass = 'badge-status-anulat';
        }


        return `
            <div class="col"
                 data-id="${contract.id}"
                 data-id-client="${contract.id_client}"
                 data-nume-client="${contract.nume_companie}"
                 data-nume-contract="${contract.nume_contract}"
                 data-tip-contract="${contract.tip_contract}"
                 data-numar-contract="${contract.numar_contract}"
                 data-data-semnare="${contract.data_semnare}"
                 data-data-inceput="${contract.data_inceput}"
                 data-data-expirare="${contract.data_expirare}"
                 data-valoare-contract="${contract.valoare_contract}"
                 data-moneda="${contract.moneda}"
                 data-status-contract="${contract.status_contract}"
                 data-termeni-plata="${contract.termeni_plata || ''}"
                 data-persoana-contact-client="${contract.persoana_contact_client || ''}"
                 data-email-contact-client="${contract.email_contact_client || ''}"
                 data-telefon-contact-client="${contract.telefon_contact_client || ''}"
                 data-cale-fisier="${contract.cale_fisier || ''}"
                 data-nume-original-fisier="${contract.nume_original_fisier || ''}"
                 data-observatii="${contract.observatii || ''}"
                 data-calculated-expiration-status-text="${expirationStatusText}"
                 data-calculated-expiration-days-remaining="${daysLeft}"
            >
                <div class="contract-card ${expirationStatusClass}">
                    <div class="contract-card-header">
                        <div class="contract-title">
                            ${contract.nume_contract}
                        </div>
                        <span class="badge badge-status ${contractStatusClass}">${contractStatusText}</span>
                    </div>
                    <div class="contract-card-body">
                        <p><i class="bx bx-user"></i> Client: <strong>${contract.nume_companie}</strong></p>
                        <p><i class="bx bx-hash"></i> Nr. Contract: <strong>${contract.numar_contract}</strong></p>
                        <p><i class="bx bx-category"></i> Tip Contract: <strong>${contract.tip_contract}</strong></p>
                        <p><i class="bx bx-calendar-x"></i> Expiră la: <strong>${dataExprareFormatted}</strong></p>
                        <p><i class="bx bx-info-circle"></i> Status Expirare: <strong class="text-white">${expirationStatusText}</strong></p>
                        ${contract.valoare_contract ? `<p><i class="bx bx-dollar"></i> Valoare: <strong>${parseFloat(contract.valoare_contract).toFixed(2)} ${contract.moneda}</strong></p>` : ''}
                        ${contract.observatii ? `<p><i class="bx bx-comment-detail"></i> Obs: ${contract.observatii.substring(0, 50)}${contract.observatii.length > 50 ? '...' : ''}</p>` : ''}
                    </div>
                    <div class="contract-card-footer">
                        ${contract.cale_fisier ? `<a href="${contract.cale_fisier}" target="_blank" class="btn btn-sm btn-outline-info" title="Vezi Contract"><i class="bx bx-file"></i> Vezi</a>` : ''}
                        <a href="edit-contract.php?id=${contract.id}" class="btn btn-sm btn-outline-warning" title="Editează Contract"><i class="bx bx-edit"></i> Editează</a>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-contract-btn" data-contract-id="${contract.id}" title="Șterge Contract"><i class="bx bx-trash"></i> Șterge</button>
                    </div>
                </div>
            </div>
        `;
    };

    // Funcție pentru a randa contractele filtrate/sortate
    function renderContracts(contractsToRender) {
        contractsCardsContainer.innerHTML = '';
        if (contractsToRender.length === 0) {
            contractsCardsContainer.innerHTML = `
                <div class="col-12">
                    <div class="no-results-message">
                        <i class="bx bx-info-circle"></i>
                        <p>Nu s-au găsit contracte care să corespundă criteriilor de căutare/filtrare.</p>
                        <p>Poți ajusta filtrele sau adăuga contracte noi.</p>
                        <a href="adauga-contract.php" class="btn btn-primary mt-3"><i class="bx bx-plus-circle me-1"></i> Adaugă Contract Nou</a>
                    </div>
                </div>
            `;
        } else {
            contractsToRender.forEach(contract => {
                contractsCardsContainer.innerHTML += generateContractCardHTML(contract);
            });
        }
    }

    // Logica de filtrare și sortare
    const filterClient = document.getElementById('filterClient');
    const filterTipContract = document.getElementById('filterTipContract');
    const filterStatusContract = document.getElementById('filterStatusContract');
    const filterSearchText = document.getElementById('filterSearchText');
    const filterStartDate = document.getElementById('filterStartDate');
    const filterEndDate = document.getElementById('filterEndDate');
    const sortBy = document.getElementById('sortBy');
    const sortOrder = document.getElementById('sortOrder');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');

    function applyFiltersAndSort() {
        let filteredContracts = [...allContracts];

        const selectedClientId = filterClient.value;
        const selectedTipContract = filterTipContract.value;
        const selectedStatusContract = filterStatusContract.value;
        const searchText = filterSearchText.value.toLowerCase().trim();
        const startDate = filterStartDate.value ? new Date(filterStartDate.value).getTime() : null;
        const endDate = filterEndDate.value ? new Date(filterEndDate.value).getTime() : null;

        filteredContracts = filteredContracts.filter(contract => {
            let match = true;

            // Filter by Client
            if (selectedClientId && contract.id_client != selectedClientId) {
                match = false;
            }
            
            // Filter by Contract Type
            if (selectedTipContract && contract.tip_contract !== selectedTipContract) {
                match = false;
            }

            // Filter by Contract Status
            if (selectedStatusContract && contract.status_contract !== selectedStatusContract) {
                match = false;
            }
            
            // Filter by Search Text
            if (searchText) {
                const searchableText = `${contract.nume_contract} ${contract.numar_contract} ${contract.nume_companie} ${contract.tip_contract} ${contract.observatii} ${contract.persoana_contact_client} ${contract.email_contact_client} ${contract.telefon_contact_client}`.toLowerCase();
                if (!searchableText.includes(searchText)) {
                    match = false;
                }
            }
            
            // Filter by Date Range (Contract Expiration Date)
            if (startDate) {
                const contractExpDateMs = new Date(contract.data_expirare).getTime();
                if (contractExpDateMs < startDate) {
                    match = false;
                }
            }
            if (endDate) {
                const contractExpDateMs = new Date(contract.data_expirare).getTime();
                if (contractExpDateMs > endDate) {
                    match = false;
                }
            }
            return match;
        });

        // Sorting
        const currentSortBy = sortBy.value;
        const currentSortOrder = sortOrder.value;

        filteredContracts.sort((a, b) => {
            let valA, valB;

            switch (currentSortBy) {
                case 'data_expirare':
                    valA = new Date(a.data_expirare).getTime();
                    valB = new Date(b.data_expirare).getTime();
                    break;
                case 'nume_contract':
                    valA = a.nume_contract.toLowerCase();
                    valB = b.nume_contract.toLowerCase();
                    break;
                case 'nume_companie':
                    valA = a.nume_companie.toLowerCase();
                    valB = b.nume_companie.toLowerCase();
                    break;
                case 'status_contract':
                    valA = a.status_contract.toLowerCase();
                    valB = b.status_contract.toLowerCase();
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

        renderContracts(filteredContracts);
    }

    function resetFilters() {
        // Reset all filter inputs to their default "empty" state
        filterClient.value = '';
        filterTipContract.value = '';
        filterStatusContract.value = '';
        filterSearchText.value = '';
        filterStartDate.value = '';
        filterEndDate.value = '';
        sortBy.value = 'data_expirare';
        sortOrder.value = 'ASC';

        renderContracts(allContracts); // Render all original contracts
    }

    // Attach event listeners to filter controls and buttons
    applyFiltersBtn.addEventListener('click', applyFiltersAndSort);
    resetFiltersBtn.addEventListener('click', resetFilters);

    document.querySelectorAll('#filterClient, #filterTipContract, #filterStatusContract, #filterStartDate, #filterEndDate, #sortBy, #sortOrder').forEach(el => {
        el.addEventListener('change', applyFiltersAndSort);
    });
    filterSearchText.addEventListener('input', applyFiltersAndSort);


    // Initial render of all contracts when page loads
    renderContracts(allContracts);


    // Populate View Details Modal (Event delegation)
    contractsCardsContainer.addEventListener('click', function(e) {
        const viewDetailsBtn = e.target.closest('.btn-outline-info');
        if (viewDetailsBtn) {
            const cardElement = viewDetailsBtn.closest('.col');
            const contractId = cardElement.dataset.id;
            const contract = contractsMap[contractId];

            if (contract) {
                document.getElementById('detailClientName').textContent = contract.nume_companie;
                document.getElementById('detailNumarContract').textContent = contract.numar_contract;
                document.getElementById('detailNumeContract').textContent = contract.nume_contract;
                document.getElementById('detailTipContract').textContent = contract.tip_contract;
                document.getElementById('detailDataSemnare').textContent = contract.data_semnare ? new Date(contract.data_semnare).toLocaleDateString('ro-RO') : 'N/A';
                document.getElementById('detailDataInceput').textContent = contract.data_inceput ? new Date(contract.data_inceput).toLocaleDateString('ro-RO') : 'N/A';
                document.getElementById('detailDataExpirare').textContent = contract.data_expirare ? new Date(contract.data_expirare).toLocaleDateString('ro-RO') : 'N/A';
                document.getElementById('detailStatusContract').textContent = contract.status_contract;
                document.getElementById('detailValoareContract').textContent = contract.valoare_contract ? `${parseFloat(contract.valoare_contract).toFixed(2)} ${contract.moneda}` : 'N/A';
                document.getElementById('detailTermeniPlata').textContent = contract.termeni_plata || 'N/A';
                document.getElementById('detailContactClientNume').textContent = contract.persoana_contact_client || 'N/A';
                document.getElementById('detailContactClientEmail').textContent = contract.email_contact_client || 'N/A';
                document.getElementById('detailContactClientTelefon').textContent = contract.telefon_contact_client || 'N/A';
                document.getElementById('detailObservatii').textContent = contract.observatii || 'N/A';

                const detailFileContainer = document.getElementById('detailFileContainer');
                detailFileContainer.innerHTML = ''; 

                if (contract.cale_fisier) {
                    const fileExtension = contract.cale_fisier.split('.').pop().toLowerCase();
                    const isPdf = fileExtension === 'pdf'; // Contracts are typically PDFs

                    if (isPdf) {
                        detailFileContainer.innerHTML = `<iframe src="${contract.cale_fisier}" width="100%" height="400px" style="border: none; border-radius: 0.5rem; background-color: #3b435a;"></iframe>`;
                    } else {
                        detailFileContainer.innerHTML = `<div class="alert alert-info text-center"><i class="bx bx-file" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>Previzualizare indisponibilă pentru acest tip de fișier.</div>`;
                    }
                    detailFileContainer.innerHTML += `<a href="${contract.cale_fisier}" target="_blank" class="btn btn-sm btn-info mt-2"><i class="bx bx-download"></i> Descarcă Fișier Original</a>`;
                } else {
                    detailFileContainer.innerHTML = `<div class="alert alert-warning">Nu există fișier atașat pentru acest contract.</div>`;
                }

                const viewContractDetailsModalInstance = new bootstrap.Modal(viewContractDetailsModal);
                viewContractDetailsModalInstance.show();
            }
        }
    });

    // Delete Confirmation Modal (Event delegation)
    contractsCardsContainer.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-contract-btn');
        if (deleteBtn) {
            const cardElement = deleteBtn.closest('.col');
            const contractId = cardElement.dataset.id;

            modalContractId.value = contractId;
            deleteModal.show();
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