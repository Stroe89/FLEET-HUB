<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie

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

// Preluăm luna și anul selectate sau luna/anul curente
$selected_month_year = $_GET['month'] ?? date('Y-m'); // Format YYYY-MM
$display_date_month = new DateTime($selected_month_year . '-01'); // Pentru afișare

$start_date_str = $display_date_month->format('Y-m-01');
$end_date_str = $display_date_month->format('Y-m-t'); // Ultima zi a lunii

// Preluăm lista de vehicule pentru dropdown-uri de filtrare
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare, tip FROM vehicule ORDER BY model ASC, numar_inmatriculare ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

// Preluăm tipurile de vehicule existente din baza de date pentru dropdown
$tipuri_vehicul_db = [];
$sql_tipuri_db = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
$result_tipuri_db = $conn->query($sql_tipuri_db);
if ($result_tipuri_db) {
    while ($row = $result_tipuri_db->fetch_assoc()) {
        $tipuri_vehicul_db[] = $row['tip'];
    }
}

// Tipuri de vehicule predefinite din domeniul transporturilor (lista extinsă)
$tipuri_vehicul_predefined = [
    'Autocar', 'Microbuz', 'Minibus (8+1)', 'Camion (Rigid)', 'Camion (Articulat)', 
    'Autoutilitară', 'Furgonetă', 'Trailer (Semiremorcă)', 'Remorcă', 'Autoturism',
    'Mașină de Intervenție', 'Platformă Auto', 'Basculantă', 'Cisternă', 'Frigorifică',
    'Container', 'Duba', 'Altele', 'Autotren', 'Cap Tractor', 'Semiremorcă Frigorifică',
    'Semiremorcă Prelată', 'Semiremorcă Cisternă', 'Semiremorcă Basculantă', 'Autospecială',
    'Vehicul Electric', 'Vehicul Hibrid'
];

// Combinăm tipurile din DB cu cele predefinite și eliminăm duplicatele
$tipuri_vehicul_finale = array_unique(array_merge($tipuri_vehicul_db, $tipuri_vehicul_predefined));
sort($tipuri_vehicul_finale); // Sortează alfabetic

// Statusuri vehicul pentru filtrare
$statusuri_vehicul = ['Disponibil', 'În cursă', 'În service', 'Indisponibil'];


// --- Logică pentru calculul Raportului Lunar Flotă ---
$fleet_report_data_monthly = [];
$total_fleet_km_monthly = 0;
$total_fleet_in_service_days = 0;
$total_fleet_in_course_days = 0;

foreach ($vehicule_list as $vehicul) {
    $vehicle_id = $vehicul['id'];
    $km_parcursi_vehicul = 0;
    $days_in_service_vehicul = 0;
    $days_in_course_vehicul = 0;

    // 1. Kilometri parcurși din curse_active în luna selectată
    $sql_km = "SELECT SUM(km_parcursi) as total_km FROM curse_active WHERE id_vehicul = ? AND data_inceput BETWEEN ? AND ?";
    $stmt_km = $conn->prepare($sql_km);
    if ($stmt_km) {
        $stmt_km->bind_param("iss", $vehicle_id, $start_date_str, $end_date_str);
        $stmt_km->execute();
        $result_km = $stmt_km->get_result()->fetch_assoc();
        $km_parcursi_vehicul = $result_km['total_km'] ?? 0;
        $stmt_km->close();
    }

    // 2. Zile în service / în cursă (simplificat pentru raport lunar)
    // Aceasta este o abordare simplificată. Pentru precizie, ar trebui să iterezi prin fiecare zi din lună
    // și să verifici statusul vehiculului în acea zi, sau să ai un tabel de log-uri de status.
    // Aici, vom verifica doar înregistrările de mentenanță și curse care se suprapun cu luna.

    // Zile în service
    if ($conn->query("SHOW TABLES LIKE 'istoric_mentenanta'")->num_rows > 0) {
        $sql_service_days = "SELECT data_intrare_service, data_iesire_service, status FROM istoric_mentenanta WHERE id_vehicul = ? AND status IN ('În Așteptare', 'În Desfășurare', 'Finalizată') AND ((data_intrare_service BETWEEN ? AND ?) OR (data_iesire_service BETWEEN ? AND ?) OR (data_intrare_service <= ? AND data_iesire_service >= ?))";
        $stmt_service_days = $conn->prepare($sql_service_days);
        if ($stmt_service_days) {
            $stmt_service_days->bind_param("sssssss", $vehicle_id, $start_date_str, $end_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str);
            $stmt_service_days->execute();
            $result_service_days = $stmt_service_days->get_result();
            while ($row_service = $result_service_days->fetch_assoc()) {
                $entry_date = new DateTime($row_service['data_intrare_service']);
                $exit_date = $row_service['data_iesire_service'] ? new DateTime($row_service['data_iesire_service']) : new DateTime(); // Dacă nu e ieșită, considerăm până azi

                $overlap_start = max($entry_date, $display_date_month);
                $overlap_end = min($exit_date, (clone $display_date_month)->modify('last day of this month'));

                if ($overlap_start <= $overlap_end) {
                    $days_in_service_vehicul += $overlap_start->diff($overlap_end)->days + 1;
                }
            }
            $stmt_service_days->close();
        }
    }

    // Zile în cursă (similar, simplificat)
    $sql_course_days = "SELECT data_inceput, data_estimata_sfarsit FROM curse_active WHERE id_vehicul = ? AND status IN ('În cursă', 'Pauză', 'Finalizată') AND ((data_inceput BETWEEN ? AND ?) OR (data_estimata_sfarsit BETWEEN ? AND ?) OR (data_inceput <= ? AND data_estimata_sfarsit >= ?))";
    $stmt_course_days = $conn->prepare($sql_course_days);
    if ($stmt_course_days) {
        $stmt_course_days->bind_param("sssssss", $vehicle_id, $start_date_str, $end_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str);
        $stmt_course_days->execute();
        $result_course_days = $stmt_course_days->get_result();
        while ($row_course = $result_course_days->fetch_assoc()) {
            $start_course = new DateTime($row_course['data_inceput']);
            $end_course = $row_course['data_estimata_sfarsit'] ? new DateTime($row_course['data_estimata_sfarsit']) : new DateTime();

            $overlap_start = max($start_course, $display_date_month);
            $overlap_end = min($end_course, (clone $display_date_month)->modify('last day of this month'));

            if ($overlap_start <= $overlap_end) {
                $days_in_course_vehicul += $overlap_start->diff($overlap_end)->days + 1;
            }
        }
        $stmt_course_days->close();
    }


    $fleet_report_data_monthly[] = [
        'id' => $vehicul['id'],
        'model' => $vehicul['model'],
        'numar_inmatriculare' => $vehicul['numar_inmatriculare'],
        'tip_vehicul' => $vehicul['tip'],
        'km_parcursi_lunar' => $km_parcursi_vehicul,
        'zile_in_service_lunar' => $days_in_service_vehicul,
        'zile_in_cursa_lunar' => $days_in_course_vehicul,
        'procent_disponibilitate' => 100 - (($days_in_service_vehicul + $days_in_course_vehicul) / $display_date_month->format('t')) * 100 // Calcul simplificat
    ];

    $total_fleet_km_monthly += $km_parcursi_vehicul;
    $total_fleet_in_service_days += $days_in_service_vehicul;
    $total_fleet_in_course_days += $days_in_course_vehicul;
}

$total_days_in_month = (int)$display_date_month->format('t');
$avg_disponibility_fleet = ($total_days_in_month > 0) ? (100 - (($total_fleet_in_service_days + $total_fleet_in_course_days) / ($total_fleet_km_monthly > 0 ? $total_fleet_km_monthly : 1) ) * 100) : 0; // Ajustare pentru diviziune la zero

$conn->close(); // Închidem conexiunea la baza de date aici.

// --- Logică pentru Export (triggered by URL parameter) ---
$export_format = $_GET['export_format'] ?? null;

if ($export_format) {
    ob_start(); // Începe buffering-ul de output
    ?>
    <h1>Raport Flotă Lunar - Perioada: <?php echo htmlspecialchars($display_date_month->format('F Y')); ?></h1>
    <h3>Sumar Flotă Lunară</h3>
    <p>Total Vehicule: <?php echo count($fleet_report_data_monthly); ?></p>
    <p>Total KM Parcurși: <?php echo htmlspecialchars(number_format($total_fleet_km_monthly, 0, ',', '.')); ?> km</p>
    <p>Zile în Service: <?php echo htmlspecialchars(number_format($total_fleet_in_service_days, 0, ',', '.')); ?> zile</p>
    <p>Zile în Cursă: <?php echo htmlspecialchars(number_format($total_fleet_in_course_days, 0, ',', '.')); ?> zile</p>
    <br>
    <h3>Detalii Flotă Lunară</h3>
    <table border="1" cellpadding="5" cellspacing="0" id="reportTable">
        <thead>
            <tr>
                <th>Vehicul</th>
                <th>Tip Vehicul</th>
                <th>Număr Înmatriculare</th>
                <th>KM Parcurși (Lună)</th>
                <th>Zile în Service (Lună)</th>
                <th>Zile în Cursă (Lună)</th>
                <th>Procent Disponibilitate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fleet_report_data_monthly as $veh_data): ?>
                <tr>
                    <td><?php echo htmlspecialchars($veh_data['model']); ?></td>
                    <td><?php echo htmlspecialchars($veh_data['tip_vehicul'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($veh_data['numar_inmatriculare']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($veh_data['km_parcursi_lunar'], 0, ',', '.')) . ' km'; ?></td>
                    <td><?php echo htmlspecialchars(number_format($veh_data['zile_in_service_lunar'], 0, ',', '.')) . ' zile'; ?></td>
                    <td><?php echo htmlspecialchars(number_format($veh_data['zile_in_cursa_lunar'], 0, ',', '.')) . ' zile'; ?></td>
                    <td><?php echo htmlspecialchars(number_format($veh_data['procent_disponibilitate'], 2, ',', '.')) . ' %'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    $report_html_content = ob_get_clean(); // Preluăm conținutul HTML și oprim buffering-ul

    switch ($export_format) {
        case 'pdf':
            echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'></script>";
            echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js'></script>";
            echo "<script>";
            echo "document.addEventListener('DOMContentLoaded', function() {";
            echo "    const { jsPDF } = window.jspdf;";
            echo "    const doc = new jsPDF('p', 'pt', 'a4');";
            echo "    doc.setFont('Noto Sans', 'normal');";
            echo "    doc.html(document.body.innerHTML, {";
            echo "        callback: function (doc) {";
            echo "            doc.save('Raport_Flota_Lunar_" . $selected_month_year . ".pdf');";
            echo "            window.close();";
            echo "        },";
            echo "        x: 10, y: 10, width: 580, windowWidth: 794";
            echo "    });";
            echo "});";
            echo "</script>";
            echo $report_html_content;
            exit();

        case 'excel':
            echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js'></script>";
            echo "<script>";
            echo "document.addEventListener('DOMContentLoaded', function() {";
            echo "    const table = document.getElementById('reportTable');";
            echo "    const ws = XLSX.utils.table_to_sheet(table);";
            echo "    const wb = XLSX.utils.book_new();";
            echo "    XLSX.utils.book_append_sheet(wb, ws, 'Raport Flota Lunar');";
            echo "    XLSX.writeFile(wb, 'Raport_Flota_Lunar_" . $selected_month_year . ".xlsx');";
            echo "    window.close();";
            echo "});";
            echo "</script>";
            echo $report_html_content;
            exit();

        case 'print':
            echo "<script>";
            echo "document.addEventListener('DOMContentLoaded', function() {";
            echo "    window.print();";
            echo "    window.onafterprint = function() { window.close(); };";
            echo "});";
            echo "</script>";
            echo $report_html_content;
            exit();
    }
}

require_once 'template/header.php'; // Header-ul paginii normale
?>

<title>NTS TOUR | Raport Flotă Lunar</title>

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

    /* Stiluri specifice pentru rapoarte */
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
    /* Badge-uri pentru statusuri */
    .badge-status-ok { background-color: #28a745; color: #fff; }
    .badge-status-warning { background-color: #ffc107; color: #343a40; }
    .badge-status-danger { background-color: #dc3545; color: #fff; }

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
            <div class="breadcrumb-title pe-3">Rapoarte</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Raport Flotă Lunar</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Raport Flotă Lunar - Perioada: <?php echo htmlspecialchars($display_date_month->format('F Y')); ?></h4>
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

                        <!-- Selector de lună/an și butoane de export -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="reportMonth" class="form-label">Selectează Luna:</label>
                                <input type="month" class="form-control" id="reportMonth" value="<?php echo htmlspecialchars($selected_month_year); ?>">
                            </div>
                            <div class="col-md-8 mb-3 d-flex align-items-end justify-content-end">
                                <!-- Butoanele de export vor fi gestionate de pagina export-rapoarte.php -->
                                <!-- Aici le lăsăm doar ca placeholder vizual, funcționalitatea e în altă parte -->
                                <button type="button" class="btn btn-primary me-2 disabled"><i class="bx bxs-file-pdf"></i> Export PDF</button>
                                <button type="button" class="btn btn-success me-2 disabled"><i class="bx bxs-file-excel"></i> Export Excel</button>
                                <button type="button" class="btn btn-info disabled"><i class="bx bx-printer"></i> Printează</button>
                            </div>
                        </div>

                        <!-- Statistici Sumare Lunare -->
                        <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
                            <div class="col">
                                <div class="card stat-card border-left-info">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Total Vehicule</p><h4 class="my-1"><?php echo count($fleet_report_data_monthly); ?></h4></div>
                                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-collection"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-success">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Total KM Parcurși</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_fleet_km_monthly, 0, ',', '.')); ?></h4></div>
                                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bx-trip"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-warning">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Zile în Service</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_fleet_in_service_days, 0, ',', '.')); ?></h4></div>
                                        <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bxs-wrench"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-danger">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Zile în Cursă</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_fleet_in_course_days, 0, ',', '.')); ?></h4></div>
                                        <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bxs-stopwatch"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Secțiunea de Filtrare a Tabelului Detaliat -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterVehicul" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicul">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterTipVehicul" class="form-label">Filtrează după Tip Vehicul:</label>
                                <select class="form-select" id="filterTipVehicul">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_vehicul_finale as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta model, număr înmatriculare...">
                                </div>
                            </div>
                        </div>

                        <!-- Tabelul cu Detalii Flotă Lunară -->
                        <?php if (empty($fleet_report_data_monthly)): ?>
                            <div class="alert alert-info">Nu există date de flotă pentru luna selectată.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="reportTable">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Vehicul</th>
                                            <th>Număr Înmatriculare</th>
                                            <th>KM Parcurși (Lună)</th>
                                            <th>Zile în Service (Lună)</th>
                                            <th>Zile în Cursă (Lună)</th>
                                            <th>Procent Disponibilitate</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportTableBody">
                                        <?php foreach ($fleet_report_data_monthly as $veh_data): ?>
                                            <tr 
                                                data-id-vehicul="<?php echo $veh_data['id']; ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($veh_data['tip_vehicul'] ?? ''); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($veh_data['model'] . ' ' . $veh_data['numar_inmatriculare'] . ' ' . $veh_data['tip_vehicul'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($veh_data['model']); ?></td>
                                                <td data-label="Tip Vehicul:"><?php echo htmlspecialchars($veh_data['tip_vehicul'] ?? 'N/A'); ?></td>
                                                <td data-label="Număr Înmatriculare:"><?php echo htmlspecialchars($veh_data['numar_inmatriculare']); ?></td>
                                                <td data-label="KM Parcurși (Lună):"><?php echo htmlspecialchars(number_format($veh_data['km_parcursi_lunar'], 0, ',', '.')) . ' km'; ?></td>
                                                <td data-label="Zile în Service (Lună):"><?php echo htmlspecialchars(number_format($veh_data['zile_in_service_lunar'], 0, ',', '.')) . ' zile'; ?></td>
                                                <td data-label="Zile în Cursă (Lună):"><?php echo htmlspecialchars(number_format($veh_data['zile_in_cursa_lunar'], 0, ',', '.')) . ' zile'; ?></td>
                                                <td data-label="Procent Disponibilitate:">
                                                    <?php 
                                                        $disponibility_badge_class = 'badge-status-ok';
                                                        if ($veh_data['procent_disponibilitate'] < 80 && $veh_data['procent_disponibilitate'] >= 50) { 
                                                            $disponibility_badge_class = 'badge-status-warning';
                                                        } elseif ($veh_data['procent_disponibilitate'] < 50) { 
                                                            $disponibility_badge_class = 'badge-status-danger';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $disponibility_badge_class; ?>">
                                                        <?php echo htmlspecialchars(number_format($veh_data['procent_disponibilitate'], 2, ',', '.')) . ' %'; ?>
                                                    </span>
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
    const reportMonthInput = document.getElementById('reportMonth');
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipVehicul = document.getElementById('filterTipVehicul');
    const filterSearch = document.getElementById('filterSearch');
    const reportTableBody = document.getElementById('reportTableBody');

    // Functie pentru a reincarca pagina cu luna selectata
    reportMonthInput.addEventListener('change', function() {
        const selectedMonth = this.value;
        window.location.href = `raport-flota-lunar.php?month=${selectedMonth}`;
    });

    // Functie de filtrare pentru tabel
    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipVehicul = filterTipVehicul.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#reportTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipVehicul = row.getAttribute('data-tip-vehicul');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipVehiculMatch = (selectedTipVehicul === 'all' || rowTipVehicul === selectedTipVehicul);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipVehiculMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipVehicul.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterTable(); // Rulează la încărcarea paginii


    // Functii de Export si Print (triggered by URL parameter in PHP)
    // Acestea sunt acum gestionate de export-rapoarte.php care redirecționează aici cu un parametru
    // Nu mai avem butoane de export direct pe această pagină, ele sunt în export-rapoarte.php
});
</script>
