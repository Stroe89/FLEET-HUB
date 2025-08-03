<?php
session_start();
require_once 'db_connect.php';
// require_once 'template/header.php'; // Inclus mai jos

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

// Preluăm intervalul de date selectat sau setăm implicit pe luna curentă
$start_date_str = $_GET['start_date'] ?? date('Y-m-01');
$end_date_str = $_GET['end_date'] ?? date('Y-m-t');

$start_date_dt = new DateTime($start_date_str);
$end_date_dt = new DateTime($end_date_str);

// Preluăm lista de vehicule pentru dropdown-uri de filtrare și pentru bucla principală
// Includem tip_combustibil aici, presupunând că există în tabela vehicule
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare, tip, tip_combustibil FROM vehicule ORDER BY model ASC, numar_inmatriculare ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
} else {
    $error_message .= "Eroare la pregătirea interogării vehiculelor: " . $conn->error . "<br>";
}

// Preluăm tipurile de vehicule existente din baza de date pentru dropdown
$tipuri_vehicul_db = [];
$sql_tipuri_db = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
$result_tipuri_db = $conn->query($sql_tipuri_db);
if ($result_tipuri_db) {
    while ($row = $result_tipuri_db->fetch_assoc()) {
        $tipuri_vehicul_db[] = $row['tip'];
    }
} else {
    $error_message .= "Eroare la preluarea tipurilor de vehicule din DB: " . $conn->error . "<br>";
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
sort($tipuri_vehicul_finale);

// Preluăm tipurile de combustibil existente din tabela consum_combustibil pentru dropdown
$tipuri_combustibil_db = [];
$sql_tipuri_combustibil_db = "SELECT DISTINCT tip_combustibil FROM consum_combustibil WHERE tip_combustibil IS NOT NULL AND tip_combustibil != '' ORDER BY tip_combustibil ASC";
$result_tipuri_combustibil_db = $conn->query($sql_tipuri_combustibil_db);
if ($result_tipuri_combustibil_db) {
    while ($row = $result_tipuri_combustibil_db->fetch_assoc()) {
        $tipuri_combustibil_db[] = $row['tip_combustibil'];
    }
}
$tipuri_combustibil_predefined = ['Diesel', 'Benzină', 'GPL', 'Electric', 'Altele'];
$tipuri_combustibil_finale = array_unique(array_merge($tipuri_combustibil_db, $tipuri_combustibil_predefined));
sort($tipuri_combustibil_finale);


// --- Logică pentru calculul Consumului de Combustibil ---
$report_data = [];
$total_fleet_liters = 0;
$total_fleet_cost = 0;
$total_fleet_km_calculated = 0;

$table_exists_consum = $conn->query("SHOW TABLES LIKE 'consum_combustibil'")->num_rows > 0;
$table_exists_curse = $conn->query("SHOW TABLES LIKE 'curse_active'")->num_rows > 0;


foreach ($vehicule_list as $vehicul) {
    $vehicle_id = $vehicul['id'];
    $litri_consumati_vehicul = 0;
    $cost_combustibil_vehicul = 0;
    $min_km_reading = null; // Prima citire de KM în perioada selectată
    $max_km_reading = null; // Ultima citire de KM în perioada selectată
    $km_parcursi_din_alimentari = 0; // Kilometri calculați doar din diferența de odometru la alimentări

    if ($table_exists_consum) {
        $sql_consum = "SELECT cantitate_litri, pret_litru, kilometraj_curent, cost_total, data_alimentare FROM consum_combustibil WHERE id_vehicul = ? AND data_alimentare BETWEEN ? AND ? ORDER BY data_alimentare ASC";
        $stmt_consum = $conn->prepare($sql_consum);
        if ($stmt_consum) {
            $stmt_consum->bind_param("iss", $vehicle_id, $start_date_str, $end_date_str);
            if (!$stmt_consum->execute()) {
                $error_message .= "Eroare la executarea interogării de consum pentru vehicul " . htmlspecialchars($vehicul['numar_inmatriculare']) . ": " . $stmt_consum->error . "<br>";
            } else {
                $result_consum = $stmt_consum->get_result();
                $all_fuelings_for_vehicle = [];
                while ($row = $result_consum->fetch_assoc()) {
                    $all_fuelings_for_vehicle[] = $row;
                    $litri_consumati_vehicul += $row['cantitate_litri'];
                    $cost_combustibil_vehicul += ($row['cost_total'] ?? ($row['cantitate_litri'] * $row['pret_litru']));
                }
                $stmt_consum->close();

                // Calculează KM parcurși pe baza primului și ultimului kilometraj înregistrat pentru acest vehicul în intervalul de date.
                if (!empty($all_fuelings_for_vehicle)) {
                    // Sortează array-ul după data alimentării pentru a găsi citirea min/max de km în interval
                    usort($all_fuelings_for_vehicle, function($a, $b) {
                        return strtotime($a['data_alimentare']) - strtotime($b['data_alimentare']);
                    });

                    // Asigură-te că ambele citiri (min și max) sunt valide și numerice
                    if (isset($all_fuelings_for_vehicle[0]['kilometraj_curent']) && is_numeric($all_fuelings_for_vehicle[0]['kilometraj_curent']) &&
                        isset($all_fuelings_for_vehicle[count($all_fuelings_for_vehicle) - 1]['kilometraj_curent']) && is_numeric($all_fuelings_for_vehicle[count($all_fuelings_for_vehicle) - 1]['kilometraj_curent'])) {
                        $min_km_reading = (int)$all_fuelings_for_vehicle[0]['kilometraj_curent'];
                        $max_km_reading = (int)$all_fuelings_for_vehicle[count($all_fuelings_for_vehicle) - 1]['kilometraj_curent'];
                        
                        if ($max_km_reading >= $min_km_reading) {
                            $km_parcursi_din_alimentari = $max_km_reading - $min_km_reading;
                        }
                    }
                }
            }
        } else {
            $error_message .= "Eroare la pregătirea interogării de consum pentru vehicul " . htmlspecialchars($vehicul['numar_inmatriculare']) . ": " . $conn->error . "<br>";
        }
    }

    $km_parcursi_calculati_vehicul = $km_parcursi_din_alimentari; // Inițial, folosim KM din alimentări

    // Adăugăm KM din curse_active ca o sursă suplimentară de KM parcurși (fallback sau completare)
    if ($table_exists_curse) {
        $sql_km_curse = "SELECT SUM(km_parcursi) as total_km_curse FROM curse_active WHERE id_vehicul = ? AND data_inceput BETWEEN ? AND ?";
        $stmt_km_curse = $conn->prepare($sql_km_curse);
        if ($stmt_km_curse) {
            $stmt_km_curse->bind_param("iss", $vehicle_id, $start_date_str, $end_date_str);
            if (!$stmt_km_curse->execute()) {
                $error_message .= "Eroare la executarea interogării KM curse pentru vehicul " . htmlspecialchars($vehicul['numar_inmatriculare']) . ": " . $stmt_km_curse->error . "<br>";
            } else {
                $result_km_curse = $stmt_km_curse->get_result()->fetch_assoc();
                $km_din_curse = $result_km_curse['total_km_curse'] ?? 0;
                
                // Logica pentru a decide ce KM folosim:
                // Dacă nu avem KM calculați din alimentări (adică $km_parcursi_din_alimentari e 0),
                // dar avem KM din curse, îi folosim pe aceia. Aceasta este o logică de fallback.
                if ($km_parcursi_din_alimentari == 0 && $km_din_curse > 0) {
                     $km_parcursi_calculati_vehicul = $km_din_curse;
                }
                // Dacă vrei să aduni KM din ambele surse: $km_parcursi_calculati_vehicul += $km_din_curse;
            }
            $stmt_km_curse->close();
        } else {
            $error_message .= "Eroare la pregătirea interogării KM curse pentru vehicul " . htmlspecialchars($vehicul['numar_inmatriculare']) . ": " . $conn->error . "<br>";
        }
    }
    
    // Calcule finale pentru fiecare vehicul
    $consum_mediu = 0;
    $cost_per_km = 0;
    // Calculele se fac doar dacă s-au consumat litri ȘI s-au parcurs KM
    if ($litri_consumati_vehicul > 0 && $km_parcursi_calculati_vehicul > 0) {
        $consum_mediu = ($litri_consumati_vehicul / $km_parcursi_calculati_vehicul) * 100;
        $cost_per_km = ($cost_combustibil_vehicul / $km_parcursi_calculati_vehicul);
    }
    
    $report_data[] = [
        'id' => $vehicul['id'],
        'model' => $vehicul['model'],
        'numar_inmatriculare' => $vehicul['numar_inmatriculare'],
        'tip_vehicul' => $vehicul['tip'],
        'tip_combustibil' => $vehicul['tip_combustibil'] ?? 'N/A', // Asigură că tip_combustibil există în vehicule
        'litri_consumati' => $litri_consumati_vehicul,
        'cost_combustibil' => $cost_combustibil_vehicul,
        'km_parcursi_calculati' => $km_parcursi_calculati_vehicul,
        'consum_mediu' => $consum_mediu,
        'cost_per_km' => $cost_per_km
    ];

    $total_fleet_liters += $litri_consumati_vehicul;
    $total_fleet_cost += $cost_combustibil_vehicul;
    $total_fleet_km_calculated += $km_parcursi_calculati_vehicul;
}

$total_fleet_consum_mediu = ($total_fleet_km_calculated > 0) ? ($total_fleet_liters / $total_fleet_km_calculated) * 100 : 0;
$total_fleet_cost_per_km = ($total_fleet_km_calculated > 0) ? ($total_fleet_cost / $total_fleet_km_calculated) : 0;

$conn->close(); // Închidem conexiunea la baza de date aici.
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Raport Consum Combustibil</title>

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
                        <li class="breadcrumb-item active" aria-current="page">Raport Consum Combustibil</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Raport Consum Combustibil - Perioada: <?php echo htmlspecialchars($start_date_dt->format('d.m.Y')); ?> - <?php echo htmlspecialchars($end_date_dt->format('d.m.Y')); ?></h4>
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

                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="startDate" class="form-label">Dată Început:</label>
                                <input type="date" class="form-control" id="startDate" value="<?php echo htmlspecialchars($start_date_str); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="endDate" class="form-label">Dată Sfârșit:</label>
                                <input type="date" class="form-control" id="endDate" value="<?php echo htmlspecialchars($end_date_str); ?>">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100 me-2" id="applyDateFilterBtn">Aplică Filtru Dată</button>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-primary me-2" id="exportPdfBtn"><i class="bx bxs-file-pdf"></i> Export PDF</button>
                                <button type="button" class="btn btn-success me-2" id="exportExcelBtn"><i class="bx bxs-file-excel"></i> Export Excel</button>
                                <button type="button" class="btn btn-info" id="printReportBtn"><i class="bx bx-printer"></i> Printează</button>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                            <div class="col">
                                <div class="card stat-card border-left-info">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Total Litri Consumați</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_fleet_liters, 2, ',', '.')); ?> L</h4></div>
                                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bx-gas-pump"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-danger">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Cost Total Combustibil</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_fleet_cost, 2, ',', '.')); ?> RON</h4></div>
                                        <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bx-dollar"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-success">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Consum Mediu Flotă</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_fleet_consum_mediu, 2, ',', '.')); ?> L/100KM</h4></div>
                                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bx-line-chart"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                <label for="filterTipCombustibil" class="form-label">Filtrează după Tip Combustibil:</label>
                                <select class="form-select" id="filterTipCombustibil">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_combustibil_finale as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta model, număr înmatriculare...">
                                </div>
                            </div>
                        </div>

                        <?php if (empty($report_data)): ?>
                            <div class="alert alert-info">Nu există date de consum combustibil pentru perioada selectată.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="consumCombustibilTable">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Vehicul</th>
                                            <th>Număr Înmatriculare</th>
                                            <th>Litri Consumați</th>
                                            <th>Cost Combustibil</th>
                                            <th>KM Parcurși (Calculat)</th>
                                            <th>Consum Mediu (L/100KM)</th>
                                            <th>Cost/KM Combustibil</th>
                                        </tr>
                                    </thead>
                                    <tbody id="consumCombustibilTableBody">
                                        <?php foreach ($report_data as $veh_data): ?>
                                            <tr
                                                data-id-vehicul="<?php echo $veh_data['id']; ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($veh_data['tip_vehicul'] ?? ''); ?>"
                                                data-tip-combustibil="<?php echo htmlspecialchars($veh_data['tip_combustibil'] ?? 'N/A'); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($veh_data['model'] . ' ' . $veh_data['numar_inmatriculare'] . ' ' . $veh_data['tip_vehicul'] . ' ' . ($veh_data['tip_combustibil'] ?? ''))); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($veh_data['model']); ?></td>
                                                <td data-label="Tip Vehicul:"><?php echo htmlspecialchars($veh_data['tip_vehicul'] ?? 'N/A'); ?></td>
                                                <td data-label="Număr Înmatriculare:"><?php echo htmlspecialchars($veh_data['numar_inmatriculare']); ?></td>
                                                <td data-label="Litri Consumați:"><?php echo htmlspecialchars(number_format($veh_data['litri_consumati'], 2, ',', '.')) . ' L'; ?></td>
                                                <td data-label="Cost Combustibil:"><?php echo htmlspecialchars(number_format($veh_data['cost_combustibil'], 2, ',', '.')) . ' RON'; ?></td>
                                                <td data-label="KM Parcurși (Calculat):"><?php echo htmlspecialchars(number_format($veh_data['km_parcursi_calculati'], 0, ',', '.')) . ' km'; ?></td>
                                                <td data-label="Consum Mediu (L/100KM):">
                                                    <?php
                                                        $consum_badge_class = 'badge-status-info'; // Default if no data or cannot calculate
                                                        if ($veh_data['litri_consumati'] > 0 && $veh_data['km_parcursi_calculati'] > 0) {
                                                            if (($veh_data['tip_vehicul'] == 'Camion' || $veh_data['tip_vehicul'] == 'Autocar')) {
                                                                if ($veh_data['consum_mediu'] > 45) { $consum_badge_class = 'badge-status-danger'; }
                                                                elseif ($veh_data['consum_mediu'] > 35) { $consum_badge_class = 'badge-status-warning'; }
                                                                else { $consum_badge_class = 'badge-status-ok'; }
                                                            } else { // Autoturisme, microbuze etc.
                                                                if ($veh_data['consum_mediu'] > 15) { $consum_badge_class = 'badge-status-danger'; }
                                                                elseif ($veh_data['consum_mediu'] > 10) { $consum_badge_class = 'badge-status-warning'; }
                                                                else { $consum_badge_class = 'badge-status-ok'; }
                                                            }
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $consum_badge_class; ?>">
                                                        <?php echo htmlspecialchars(number_format($veh_data['consum_mediu'], 2, ',', '.')) . ' L'; ?>
                                                    </span>
                                                </td>
                                                <td data-label="Cost/KM Combustibil:">
                                                    <?php
                                                        $cost_km_badge_class = 'badge-status-info'; // Default if no data or cannot calculate
                                                        if ($veh_data['cost_combustibil'] > 0 && $veh_data['km_parcursi_calculati'] > 0) {
                                                            if ($veh_data['cost_per_km'] > 1.0) { $cost_km_badge_class = 'badge-status-danger'; }
                                                            elseif ($veh_data['cost_per_km'] > 0.5) { $cost_km_badge_class = 'badge-status-warning'; }
                                                            else { $cost_km_badge_class = 'badge-status-ok'; }
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $cost_km_badge_class; ?>">
                                                        <?php echo htmlspecialchars(number_format($veh_data['cost_per_km'], 2, ',', '.')) . ' RON'; ?>
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
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const applyDateFilterBtn = document.getElementById('applyDateFilterBtn');
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipVehicul = document.getElementById('filterTipVehicul');
    const filterTipCombustibil = document.getElementById('filterTipCombustibil');
    const filterSearch = document.getElementById('filterSearch');
    const consumCombustibilTableBody = document.getElementById('consumCombustibilTableBody');

    // Functie pentru a aplica filtrele de tabel
    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipVehicul = filterTipVehicul.value;
        const selectedTipCombustibil = filterTipCombustibil.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#consumCombustibilTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipVehicul = row.getAttribute('data-tip-vehicul');
            const rowTipCombustibil = row.getAttribute('data-tip-combustibil');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipVehiculMatch = (selectedTipVehicul === 'all' || rowTipVehicul === selectedTipVehicul);
            const tipCombustibilMatch = (selectedTipCombustibil === 'all' || rowTipCombustibil === selectedTipCombustibil);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipVehiculMatch && tipCombustibilMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipVehicul.addEventListener('change', filterTable);
    filterTipCombustibil.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterTable(); // Rulează la încărcarea paginii

    // Functie pentru a reincarca pagina cu noul interval de date
    applyDateFilterBtn.addEventListener('click', function() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        window.location.href = `raport-consum-combustibil.php?start_date=${startDate}&end_date=${endDate}`;
    });

    // Functii de Export si Print
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const title = `Raport Consum Combustibil - Perioada: ${startDateInput.value} - ${endDateInput.value}`;
        const headers = [];
        document.querySelectorAll('#consumCombustibilTable thead th').forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll('#consumCombustibilTableBody tr').forEach(row => {
            // Doar rândurile vizibile
            if (row.style.display !== 'none') {
                const rowData = [];
                row.querySelectorAll('td').forEach(td => {
                    const badgeSpan = td.querySelector('.badge');
                    if (badgeSpan) {
                        rowData.push(badgeSpan.textContent);
                    } else {
                        rowData.push(td.textContent);
                    }
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

        doc.save(`Raport_Consum_Combustibil_${startDateInput.value}_${endDateInput.value}.pdf`);
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('consumCombustibilTable');
        // Clonăm tabelul pentru a procesa doar rândurile vizibile
        const clonedTable = table.cloneNode(true);
        const tbody = clonedTable.querySelector('tbody');
        Array.from(tbody.children).forEach(row => {
            if (row.style.display === 'none') {
                tbody.removeChild(row);
            }
        });

        const ws = XLSX.utils.table_to_sheet(clonedTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Raport Consum Combustibil");
        XLSX.writeFile(wb, `Raport_Consum_Combustibil_${startDateInput.value}_${endDateInput.value}.xlsx`);
    });

    document.getElementById('printReportBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const printContent = `
            <html>
            <head>
                <title>Raport Consum Combustibil - Perioada: ${startDateInput.value} - ${endDateInput.value}</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .badge { 
                        display: inline-block; 
                        padding: 0.25em 0.4em; 
                        font-size: 75%; 
                        font-weight: 700; 
                        line-height: 1; 
                        text-align: center; 
                        white-space: nowrap; 
                        vertical-align: baseline; 
                        border-radius: 0.25rem; 
                    }
                    .badge-status-ok { background-color: #28a745; color: #fff; }
                    .badge-status-warning { background-color: #ffc107; color: #343a40; }
                    .badge-status-danger { background-color: #dc3545; color: #fff; }
                    .badge-status-info { background-color: #17a2b8; color: #fff; } /* Added for 'N/A' or insufficient data */
                </style>
            </head>
            <body>
                <h1>Raport Consum Combustibil - Perioada: ${startDateInput.value} - ${endDateInput.value}</h1>
                ${document.getElementById('consumCombustibilTable').outerHTML}
            </body>
            </html>
        `;
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    });
});
</script>