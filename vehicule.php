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

// --- Logica pentru statistici ---
$stats = ['Disponibil' => 0, 'În cursă' => 0, 'În service' => 0, 'Total' => 0, 'Indisponibil' => 0];
$sql_stats = "SELECT status, COUNT(*) as count FROM vehicule GROUP BY status";
$result_stats = $conn->query($sql_stats);
if ($result_stats && $result_stats->num_rows > 0) {
    while($row_stat = $result_stats->fetch_assoc()) {
        if (isset($stats[$row_stat['status']])) {
            $stats[$row_stat['status']] = $row_stat['count'];
        }
    }
}
$stats['Total'] = array_sum($stats);

// --- Logica pentru a prelua vehiculele si actele lor ---
$sql = "
    SELECT 
        v.*,
        (SELECT d.data_expirare FROM documente d WHERE d.id_vehicul = v.id AND d.tip_document = 'ITP' ORDER BY d.data_expirare DESC LIMIT 1) as data_expirare_itp,
        (SELECT d.data_expirare FROM documente d WHERE d.id_vehicul = v.id AND d.tip_document = 'RCA' ORDER BY d.data_expirare DESC LIMIT 1) as data_expirare_rca,
        (SELECT d.data_expirare FROM documente d WHERE d.id_vehicul = v.id AND d.tip_document = 'Rovinieta' ORDER BY d.data_expirare DESC LIMIT 1) as data_expirare_rovinieta
    FROM 
        vehicule v
    ORDER BY 
        v.numar_inmatriculare ASC
";
$result = $conn->query($sql);

$vehicule = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vehicule[] = $row;
    }
}
// Conexiunea la baza de date este închisă automat la sfârșitul scriptului principal
// $conn->close(); // Nu închide conexiunea aici, PHP o va închide automat

// Preluăm tipurile de vehicule existente din baza de date pentru dropdown
$tipuri_vehicul_db = [];
$sql_tipuri_db = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
$result_tipuri_db = $conn->query($sql_tipuri_db);
if ($result_tipuri_db) {
    while ($row = $result_tipuri_db->fetch_assoc()) {
        $tipuri_vehicul_db[] = $row['tip'];
    }
}

// Tipuri de vehicule predefinite din domeniul transporturilor
$tipuri_vehicul_predefinite = [
    'Autocar', 'Microbuz', 'Minibus (8+1)', 'Camion (Rigid)', 'Camion (Articulat)', 
    'Autoutilitară', 'Furgonetă', 'Trailer (Semiremorcă)', 'Remorcă', 'Autoturism',
    'Mașină de Intervenție', 'Platformă Auto', 'Basculantă', 'Cisternă', 'Frigorifică',
    'Container', 'Duba', 'Altele'
];

// Combinăm tipurile din DB cu cele predefinite și eliminăm duplicatele
$tipuri_vehicul_finale = array_unique(array_merge($tipuri_vehicul_db, $tipuri_vehicul_predefinite));
sort($tipuri_vehicul_finale); // Sortează alfabetic

// Statusuri vehicul pentru filtrare
$statusuri_vehicul = ['Disponibil', 'În cursă', 'În service', 'Indisponibil'];

// Stocam datele vehiculelor intr-un array JS pentru a le accesa in modal (pentru editare/vizualizare)
// Aceasta este necesară pentru a popula dinamic detaliile în modaluri
$vehicles_data_for_js = [];
foreach ($vehicule as $veh) {
    $vehicles_data_for_js[$veh['id']] = $veh;
}
?>

<title>NTS TOUR | Vehicule</title>

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

    /* Stiluri specifice pentru cardurile de statistici */
    .stat-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
    }
    .stat-card .card-body {
        background-color: #2a3042 !important; /* Fundal pentru corpul cardului de statistică */
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem;
    }
    .stat-card .widgets-icons {
        font-size: 2.5rem !important; /* Mărește icoanele */
        opacity: 0.7 !important; /* Le face puțin transparente */
        padding: 10px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.08); /* Fundal subtil pentru iconițe */
    }
    /* Culorile bordurilor laterale pentru stat-card-uri */
    .stat-card.border-left-info { border-left-color: #007bff !important; }
    .stat-card.border-left-success { border-left-color: #28a745 !important; }
    .stat-card.border-left-warning { border-left-color: #ffc107 !important; }
    .stat-card.border-left-danger { border-left-color: #dc3545 !important; }

    /* Stiluri pentru tabelul de vehicule */
    .table {
        color: #e0e0e0 !important;
        background-color: #2a3042 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .table th, .table td {
        border-color: rgba(255, 255, 255, 0.1) !important;
        vertical-align: middle;
    }
    .table thead th {
        background-color: #3b435a !important;
        color: #ffffff !important;
        font-weight: bold;
    }
    .table tbody tr:hover {
        background-color: #3b435a !important;
    }
    .vehicle-card {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .vehicle-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
    }
    .vehicle-card .card-body {
        padding: 1rem;
    }
    .vehicle-card-img {
        max-height: 180px;
        object-fit: cover;
        border-radius: 0.5rem;
        margin-bottom: 10px;
        width: 100%;
    }
    .vehicle-card h5 {
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }
    .vehicle-card p {
        font-size: 0.9rem;
        color: #b0b0b0;
        margin-bottom: 0.25rem;
    }
    .vehicle-card .badge {
        font-size: 0.75em;
        padding: 0.3em 0.6em;
        border-radius: 0.25rem;
        font-weight: 600;
    }
    .badge-status-Disponibil { background-color: #28a745; color: #fff; }
    .badge-status-În_cursă { background-color: #0d6efd; color: #fff; }
    .badge-status-În_service { background-color: #ffc107; color: #343a40; }
    .badge-status-Indisponibil { background-color: #dc3545; color: #fff; }

    /* Responsive adjustments for cards and table */
    @media (max-width: 767.98px) {
        .vehicle-card {
            margin-bottom: 1rem;
        }
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
            <div class="breadcrumb-title pe-3">Vehicule</div>
            <div class="ps-3">
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Vehicule</h4>
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

                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
                            <div class="col">
                                <div class="card stat-card border-left-info">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Total Vehicule</p><h4 class="my-1"><?php echo $stats['Total']; ?></h4></div>
                                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-collection"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-success">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Disponibile</p><h4 class="my-1"><?php echo $stats['Disponibil']; ?></h4></div>
                                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bxs-car"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-warning">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">În Cursă</p><h4 class="my-1"><?php echo $stats['În cursă']; ?></h4></div>
                                        <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bxs-stopwatch"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-danger">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">În Service</p><h4 class="my-1"><?php echo $stats['În service']; ?></h4></div>
                                        <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bxs-wrench"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <a href="adauga-vehicul.php" class="btn btn-primary"><i class="bx bx-plus"></i> Adaugă Vehicul Nou</a>
                            <div class="search-filter-group d-flex align-items-center">
                                <label for="searchInput" class="form-label mb-0 me-2">Caută:</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Caută după număr sau model...">
                            </div>
                        </div>

                        <!-- Filtre adiționale -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="statusFilter" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="statusFilter" data-filter-group="status">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_vehicul as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="tipFilter" class="form-label">Filtrează după Tip Vehicul:</label>
                                <select class="form-select" id="tipFilter" data-filter-group="tip">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_vehicul_finale as $tip): // Folosim lista finală ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="documentStatusFilter" class="form-label">Filtrează după Status Documente:</label>
                                <select class="form-select" id="documentStatusFilter" data-filter-group="document_status">
                                    <option value="all">Toate</option>
                                    <option value="ITP_expirat">ITP Expirat</option>
                                    <option value="RCA_expirat">RCA Expirat</option>
                                    <option value="Rovinieta_expirata">Rovinietă Expirată</option>
                                    <option value="ITP_expira_curand">ITP Expiră Curând</option>
                                    <option value="RCA_expira_curand">RCA Expiră Curând</option>
                                    <option value="Rovinieta_expira_curand">Rovinietă Expiră Curând</option>
                                </select>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-xl-2 g-4" id="vehicle-grid">
                            <?php if (empty($vehicule)): ?>
                                <div class="col-12"><div class="alert alert-info">Nu există vehicule înregistrate.</div></div>
                            <?php else: ?>
                                <?php foreach ($vehicule as $vehicul): 
                                    $itp_status = 'Valabil'; $itp_class = 'text-success';
                                    if ($vehicul['data_expirare_itp'] && (new DateTime($vehicul['data_expirare_itp'])) < (new DateTime())) {
                                        $itp_status = 'Expirat'; $itp_class = 'text-danger';
                                    } elseif ($vehicul['data_expirare_itp'] && (new DateTime($vehicul['data_expirare_itp']))->diff(new DateTime())->days <= 30 && (new DateTime($vehicul['data_expirare_itp'])) >= (new DateTime())) {
                                        $itp_status = 'Expiră curând (' . (new DateTime($vehicul['data_expirare_itp']))->diff(new DateTime())->days . ' zile)'; $itp_class = 'text-warning';
                                    }

                                    $rca_status = 'Valabil'; $rca_class = 'text-success';
                                    if ($vehicul['data_expirare_rca'] && (new DateTime($vehicul['data_expirare_rca'])) < (new DateTime())) {
                                        $rca_status = 'Expirat'; $rca_class = 'text-danger';
                                    } elseif ($vehicul['data_expirare_rca'] && (new DateTime($vehicul['data_expirare_rca']))->diff(new DateTime())->days <= 30 && (new DateTime($vehicul['data_expirare_rca'])) >= (new DateTime())) {
                                        $rca_status = 'Expiră curând (' . (new DateTime($vehicul['data_expirare_rca']))->diff(new DateTime())->days . ' zile)'; $rca_class = 'text-warning';
                                    }

                                    $rovinieta_status = 'Valabilă'; $rovinieta_class = 'text-success';
                                    if ($vehicul['data_expirare_rovinieta'] && (new DateTime($vehicul['data_expirare_rovinieta'])) < (new DateTime())) {
                                        $rovinieta_status = 'Expirată'; $rovinieta_class = 'text-danger';
                                    } elseif ($vehicul['data_expirare_rovinieta'] && (new DateTime($vehicul['data_expirare_rovinieta']))->diff(new DateTime())->days <= 30 && (new DateTime($vehicul['data_expirare_rovinieta'])) >= (new DateTime())) {
                                        $rovinieta_status = 'Expiră curând (' . (new DateTime($vehicul['data_expirare_rovinieta']))->diff(new DateTime())->days . ' zile)'; $rovinieta_class = 'text-warning';
                                    }

                                    // Data attributes pentru filtrare
                                    $data_attributes = "data-status='" . htmlspecialchars($vehicul['status']) . "' ";
                                    $data_attributes .= "data-tip='" . htmlspecialchars($vehicul['tip']) . "' ";
                                    $data_attributes .= "data-itp-status='" . strtolower(str_replace([' ', '(', ')', 'ț'], ['-', '', '', 't'], $itp_status)) . "' ";
                                    $data_attributes .= "data-rca-status='" . strtolower(str_replace([' ', '(', ')', 'ț'], ['-', '', '', 't'], $rca_status)) . "' ";
                                    $data_attributes .= "data-rovinieta-status='" . strtolower(str_replace([' ', '(', ')', 'ț'], ['-', '', '', 't'], $rovinieta_status)) . "' ";
                                    $data_attributes .= "data-search='" . strtolower(htmlspecialchars($vehicul['numar_inmatriculare'] . ' ' . $vehicul['model'] . ' ' . $vehicul['tip'] . ' ' . $vehicul['status'])) . "' ";
                                ?>
                                    <div class="col vehicle-card-col" <?php echo $data_attributes; ?>>
                                        <div class="card vehicle-card h-100">
                                            <div class="card-body d-flex flex-column">
                                                <?php if (!empty($vehicul['imagine_path'])): ?>
                                                    <img src="<?php echo htmlspecialchars($vehicul['imagine_path']); ?>" class="vehicle-card-img mb-3" alt="Imagine Vehicul" onerror="this.onerror=null;this.src='https://placehold.co/400x200/2a3042/e0e0e0?text=Fara+Imagine';">
                                                <?php else: ?>
                                                    <img src="https://placehold.co/400x200/2a3042/e0e0e0?text=Fara+Imagine" class="vehicle-card-img mb-3" alt="Fără Imagine">
                                                <?php endif; ?>
                                                <h5 class="card-title"><?php echo htmlspecialchars($vehicul['model']); ?> (<?php echo htmlspecialchars($vehicul['numar_inmatriculare']); ?>)</h5>
                                                <p class="card-text mb-1">Tip: <strong><?php echo htmlspecialchars($vehicul['tip'] ?? 'N/A'); ?></strong></p>
                                                <p class="card-text mb-1">An Fabricație: <strong><?php echo htmlspecialchars($vehicul['an_fabricatie']); ?></strong></p>
                                                <p class="card-text mb-1">Kilometraj: <strong><?php echo htmlspecialchars(number_format($vehicul['kilometraj'], 0, ',', '.')) . ' km'; ?></strong></p>
                                                <p class="card-text mb-1">Status: <span class="badge badge-status-<?php echo str_replace(' ', '_', htmlspecialchars($vehicul['status'])); ?>"><?php echo htmlspecialchars($vehicul['status']); ?></span></p>
                                                <hr>
                                                <p class="card-text mb-1">ITP: <strong class="<?php echo $itp_class; ?>"><?php echo htmlspecialchars($itp_status); ?></strong></p>
                                                <p class="card-text mb-1">RCA: <strong class="<?php echo $rca_class; ?>"><?php echo htmlspecialchars($rca_status); ?></strong></p>
                                                <p class="card-text mb-1">Rovinietă: <strong class="<?php echo $rovinieta_class; ?>"><?php echo htmlspecialchars($rovinieta_status); ?></strong></p>
                                                <div class="mt-auto d-flex flex-column gap-2 pt-3">
                                                    <a href="editeaza-vehicul.php?id=<?php echo $vehicul['id']; ?>" class="btn btn-sm btn-outline-primary w-100">Editează</a>
                                                    <a href="documente-vehicule.php?id=<?php echo $vehicul['id']; ?>" class="btn btn-sm btn-outline-info w-100">Documente</a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-vehicle-btn w-100" data-id="<?php echo $vehicul['id']; ?>" data-numar-inmatriculare="<?php echo htmlspecialchars($vehicul['numar_inmatriculare']); ?>">Șterge</button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning report-problem-btn w-100" data-bs-toggle="modal" data-bs-target="#reportProblemModal" data-id="<?php echo $vehicul['id']; ?>" data-model="<?php echo htmlspecialchars($vehicul['model']); ?>" data-numar="<?php echo htmlspecialchars($vehicul['numar_inmatriculare']); ?>">Raportează</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div id="noResultsMessage" class="alert alert-warning mt-4" style="display: none;">
                            Nu s-au găsit vehicule care să corespundă filtrelor.
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal pentru Confirmare Ștergere -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmă Ștergerea Vehiculului</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi vehiculul cu numărul de înmatriculare <strong id="vehicleToDeleteNumar"></strong>? Această acțiune este ireversibilă și va șterge și toate documentele asociate!
                <input type="hidden" id="vehicleToDeleteId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru Raportare Problemă (Complex) -->
<div class="modal fade" id="reportProblemModal" tabindex="-1" aria-labelledby="reportProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportProblemModalLabel">Raportează o Problemă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reportProblemForm" action="report_problem.php" method="POST">
                    <div class="mb-3">
                        <label for="selectVehicleToReport" class="form-label">Selectează Vehiculul:</label>
                        <select class="form-select" id="selectVehicleToReport" name="id_vehicul" required>
                            <option value="">Alege un vehicul...</option>
                            <?php foreach ($vehicule as $veh): ?>
                                <option value="<?php echo htmlspecialchars($veh['id']); ?>" data-model="<?php echo htmlspecialchars($veh['model']); ?>" data-numar="<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>">
                                    <?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted" id="selectedVehicleInfo">
                            <!-- Informații despre vehiculul selectat vor apărea aici -->
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reporterName" class="form-label">Numele tău:</label>
                        <input type="text" class="form-control" id="reporterName" name="nume_raportor" required>
                    </div>

                    <div class="mb-3">
                        <label for="problemType" class="form-label">Tipul problemei:</label>
                        <select class="form-select" id="problemType" name="tip_problema" required>
                            <option value="">Selectează tipul problemei</option>
                            <option value="Mecanica">Mecanică</option>
                            <option value="Electrica">Electrică</option>
                            <option value="Estetica">Estetică</option>
                            <option value="Documente">Documente</option>
                            <option value="Anvelope">Anvelope</option>
                            <option value="Altele">Altele</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="problemDescription" class="form-label">Descrierea detaliată a problemei:</label>
                        <textarea class="form-control" id="problemDescription" name="descriere_problema" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="problemRating" class="form-label">Gravitatea problemei (1-5, unde 5 este foarte grav):</label>
                        <input type="number" class="form-control" id="problemRating" name="gravitate" min="1" max="5" value="3" required>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Trimite Raportul</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru Mesaje de Succes/Eroare (înlocuiește alert()) -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Notificare</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="messageModalBody">
                <!-- Conținutul mesajului aici -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>


<?php require_once 'template/footer.php'; ?>

<script>
// Datele vehiculelor, populat din PHP
const vehiclesData = <?php echo json_encode($vehicles_data_for_js); ?>;

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tipFilter = document.getElementById('tipFilter');
    const documentStatusFilter = document.getElementById('documentStatusFilter');
    const allCards = document.querySelectorAll('.vehicle-card-col');
    const noResultsMessage = document.getElementById('noResultsMessage');

    let filters = { status: 'all', tip: 'all', document_status: 'all', search: '' };

    function filterVehicles() {
        filters.search = searchInput.value.toLowerCase().trim();
        filters.status = statusFilter.value;
        filters.tip = tipFilter.value;
        filters.document_status = documentStatusFilter.value;

        let visibleCount = 0;
        allCards.forEach(card => {
            const statusMatch = (filters.status === 'all' || filters.status === card.dataset.status);
            const tipMatch = (filters.tip === 'all' || filters.tip === card.dataset.tip);
            const searchMatch = (card.dataset.search.includes(filters.search));
            
            let docStatusMatch = true;
            if (filters.document_status !== 'all') {
                const itpStatus = card.dataset.itpStatus;
                const rcaStatus = card.dataset.rcaStatus;
                const rovinietaStatus = card.dataset.rovinietaStatus;

                docStatusMatch = false; // Presupunem că nu se potrivește până nu găsim o potrivire
                switch (filters.document_status) {
                    case 'itp_expirat':
                        if (itpStatus === 'expirat') docStatusMatch = true;
                        break;
                    case 'rca_expirat':
                        if (rcaStatus === 'expirat') docStatusMatch = true;
                        break;
                    case 'rovinieta_expirata':
                        if (rovinietaStatus === 'expirata') docStatusMatch = true;
                        break;
                    case 'itp_expira_curand':
                        if (itpStatus.includes('expira-curand')) docStatusMatch = true;
                        break;
                    case 'rca_expira_curand':
                        if (rcaStatus.includes('expira-curand')) docStatusMatch = true;
                        break;
                    case 'rovinieta_expira_curand':
                        if (rovinietaStatus.includes('expira-curand')) docStatusMatch = true;
                        break;
                    default:
                        docStatusMatch = true; // Dacă "all" sau un status necunoscut, afișează
                }
            }

            if (statusMatch && tipMatch && searchMatch && docStatusMatch) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        // Afișează mesajul "Nu au fost găsite rezultate" dacă nu există carduri vizibile
        noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterVehicles);
    statusFilter.addEventListener('change', filterVehicles);
    tipFilter.addEventListener('change', filterVehicles);
    documentStatusFilter.addEventListener('change', filterVehicles);

    // Initial filter on page load
    filterVehicles();


    // Logica pentru modale
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const reportProblemModal = document.getElementById('reportProblemModal');
    const messageModal = document.getElementById('messageModal'); // Noua modală de mesaj
    const messageModalBody = document.getElementById('messageModalBody');

    // Funcție pentru a afișa mesaje non-blocante
    function showMessageModal(message, type = 'info') {
        messageModalBody.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        const modalInstance = new bootstrap.Modal(messageModal);
        modalInstance.show();
    }

    // Ascultam evenimentul de deschidere a modalului de stergere
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
            // Butonul care a declansat modalul
            const button = event.relatedTarget;
            // Extragem ID-ul din atributul data-id al butonului
            const vehicleId = button.getAttribute('data-id');
            const vehicleNumar = button.getAttribute('data-numar-inmatriculare'); // Preluăm și numărul
            // Setam ID-ul in campul ascuns din modal
            const deleteVehicleIdInput = confirmDeleteModal.querySelector('#vehicleToDeleteId');
            const vehicleToDeleteNumarSpan = confirmDeleteModal.querySelector('#vehicleToDeleteNumar');
            if (deleteVehicleIdInput) {
                deleteVehicleIdInput.value = vehicleId;
            }
            if (vehicleToDeleteNumarSpan) {
                vehicleToDeleteNumarSpan.textContent = vehicleNumar;
            }
        });

        // Logica pentru butonul de confirmare stergere
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                const vehicleIdToDelete = document.getElementById('vehicleToDeleteId').value;
                
                // Modificăm apelul fetch pentru a trimite ID-ul prin GET
                // URL-ul corect este 'sterge-vehicul.php', iar metoda este 'GET'
                fetch('sterge-vehicul.php?id=' + vehicleIdToDelete, { 
                    method: 'GET', 
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    }
                })
                .then(response => {
                    // Verificăm dacă răspunsul este OK (status 200)
                    if (response.ok) {
                        return response.text(); // Citim răspunsul ca text
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(data => {
                    console.log(data); // Poate conține mesajul de succes de la PHP
                    // Dupa stergere, ascunde modalul si reincarca pagina sau actualizeaza lista
                    const modalInstance = bootstrap.Modal.getInstance(confirmDeleteModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    if (data.includes("success")) {
                        showMessageModal('Vehiculul a fost șters cu succes!', 'success');
                    } else {
                        showMessageModal('Eroare la ștergerea vehiculului: ' + data, 'danger');
                    }
                    location.reload(); // Reincarca pagina pentru a vedea modificarile
                })
                .catch(error => {
                    console.error('Eroare la ștergerea vehiculului:', error);
                    showMessageModal('A apărut o eroare la ștergerea vehiculului. Verificați consola pentru detalii.', 'danger');
                });
            });
        }
    }

    // Ascultam evenimentul de deschidere a modalului de raportare problema
    if (reportProblemModal) {
        reportProblemModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Butonul "Raportează" care a declanșat modalul
            const vehicleId = button ? button.getAttribute('data-id') : null; // Poate fi null dacă modalul e deschis direct
            const vehicleModel = button ? button.getAttribute('data-model') : null;
            const vehicleNumar = button ? button.getAttribute('data-numar') : null;

            const selectVehicleToReport = reportProblemModal.querySelector('#selectVehicleToReport');
            const reportProblemModalLabel = reportProblemModal.querySelector('#reportProblemModalLabel');
            const selectedVehicleInfoSpan = reportProblemModal.querySelector('#selectedVehicleInfo');

            // Reseteaza campurile la fiecare deschidere
            reportProblemModal.querySelector('#reporterName').value = '';
            reportProblemModal.querySelector('#problemType').value = ''; 
            reportProblemModal.querySelector('#problemDescription').value = '';
            reportProblemModal.querySelector('#problemRating').value = '3'; 

            if (vehicleId) {
                // Pre-selectează vehiculul în dropdown
                selectVehicleToReport.value = vehicleId;
                // Actualizează titlul modalului și informațiile despre vehicul
                reportProblemModalLabel.textContent = `Raportează o Problemă pentru ${vehicleModel} (${vehicleNumar})`;
                selectedVehicleInfoSpan.textContent = `Vehicul selectat: ${vehicleModel} (${vehicleNumar})`;
                selectedVehicleInfoSpan.style.display = 'block';
            } else {
                // Dacă modalul e deschis fără un vehicul pre-selectat, afișează titlul generic
                reportProblemModalLabel.textContent = 'Raportează o Problemă';
                selectVehicleToReport.value = ''; // Asigură că nu e pre-selectat nimic
                selectedVehicleInfoSpan.style.display = 'none';
            }
        });

        // Ascultă schimbările în dropdown-ul de vehicule din modal
        const selectVehicleToReport = document.getElementById('selectVehicleToReport');
        const reportProblemModalLabel = document.getElementById('reportProblemModalLabel');
        const selectedVehicleInfoSpan = document.getElementById('selectedVehicleInfo');

        selectVehicleToReport.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const selectedVehicleId = this.value;

            if (selectedVehicleId) {
                const selectedVehicleModel = selectedOption.dataset.model;
                const selectedVehicleNumar = selectedOption.dataset.numar;
                reportProblemModalLabel.textContent = `Raportează o Problemă pentru ${selectedVehicleModel} (${selectedVehicleNumar})`;
                selectedVehicleInfoSpan.textContent = `Vehicul selectat: ${selectedVehicleModel} (${selectedVehicleNumar})`;
                selectedVehicleInfoSpan.style.display = 'block';
            } else {
                reportProblemModalLabel.textContent = 'Raportează o Problemă';
                selectedVehicleInfoSpan.style.display = 'none';
            }
        });


        // Logica pentru trimiterea raportului
        const reportProblemForm = document.getElementById('reportProblemForm');
        if (reportProblemForm) {
            reportProblemForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Opreste trimiterea implicita a formularului

                const vehicleIdToReport = document.getElementById('selectVehicleToReport').value; // Preluăm ID-ul din dropdown
                const reporterName = document.getElementById('reporterName').value;
                const problemType = document.getElementById('problemType').value;
                const problemDescription = document.getElementById('problemDescription').value;
                const problemRating = document.getElementById('problemRating').value;

                // Validare simplă
                if (!vehicleIdToReport || !reporterName || !problemType || !problemDescription || !problemRating) {
                    showMessageModal('Te rog să completezi toate câmpurile obligatorii!', 'warning');
                    return;
                }

                console.log('Raportează problemă pentru vehiculul ID:', vehicleIdToReport, 'Nume:', reporterName, 'Tip:', problemType, 'Descriere:', problemDescription, 'Nota:', problemRating);
                
                fetch('report_problem.php', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_vehicul=' + encodeURIComponent(vehicleIdToReport) +
                          '&nume_raportor=' + encodeURIComponent(reporterName) +
                          '&tip_problema=' + encodeURIComponent(problemType) +
                          '&descriere_problema=' + encodeURIComponent(problemDescription) +
                          '&gravitate=' + encodeURIComponent(problemRating)
                })
                .then(response => response.text()) // Așteptăm text, nu JSON
                .then(data => {
                    console.log(data); // Poate conține mesajul de succes/eroare de la PHP
                    const modalInstance = bootstrap.Modal.getInstance(reportProblemModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    if (data.includes("success")) { // Verificăm mesajul de succes de la PHP
                        showMessageModal('Problema a fost raportată cu succes!', 'success');
                        location.reload(); // Reîncarcă pagina pentru a vedea eventualele actualizări
                    } else {
                        showMessageModal('A apărut o eroare la raportarea problemei: ' + data, 'danger'); // Afișăm eroarea primită de la PHP
                    }
                })
                .catch(error => {
                    console.error('Eroare la raportarea problemei:', error);
                    showMessageModal('A apărut o eroare la raportarea problemei. Verificați consola pentru detalii.', 'danger');
                });
            });
        }
    }
});
</script>