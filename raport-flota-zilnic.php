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
    // CORECȚIE: Era $_SESSION['session_message'], ar trebui să fie $_SESSION['success_message']
    $success_message = $_SESSION['success_message']; 
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Preluăm data selectată sau data curentă
$selected_date = $_GET['date'] ?? date('Y-m-d');
$display_date = new DateTime($selected_date);

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

// Preluăm toate vehiculele cu statusul lor actual și ultimele curse/mentenanțe pentru ziua selectată
$fleet_report_data = [];
$sql_report = "
    SELECT 
        v.id, v.model, v.numar_inmatriculare, v.tip, v.status, v.kilometraj,
        (SELECT ca.locatie_destinatie FROM curse_active ca WHERE ca.id_vehicul = v.id AND DATE(ca.data_inceput) = ? ORDER BY ca.data_inceput DESC LIMIT 1) as ultima_destinatie_azi,
        (SELECT ca.status FROM curse_active ca WHERE ca.id_vehicul = v.id AND ca.status IN ('În cursă', 'Pauză') AND DATE(ca.data_inceput) = ? ORDER BY ca.data_inceput DESC LIMIT 1) as status_cursa_activa,
        (SELECT pr.tip_problema FROM probleme_raportate pr WHERE pr.id_vehicul = v.id AND pr.status != 'Rezolvată' ORDER BY pr.data_raportare DESC LIMIT 1) as ultima_problema_nerezolvata
    FROM 
        vehicule v
    ORDER BY 
        v.numar_inmatriculare ASC
";
$stmt_report = $conn->prepare($sql_report);
if ($stmt_report) {
    $stmt_report->bind_param("ss", $selected_date, $selected_date); // Două bind-uri pentru cele două ?
    $stmt_report->execute();
    $result_report = $stmt_report->get_result();
    while ($row = $result_report->fetch_assoc()) {
        $fleet_report_data[] = $row;
    }
    $stmt_report->close();
}

// Calculăm statistici sumare pentru ziua selectată
$stats_daily = [
    'Total Vehicule' => count($fleet_report_data),
    'Vehicule în Cursă Azi' => 0,
    'Vehicule în Service Azi' => 0,
    'Probleme Nerezolvate' => 0,
    'Vehicule Disponibile' => 0
];

foreach ($fleet_report_data as $veh) {
    if ($veh['status_cursa_activa'] == 'În cursă' || $veh['status_cursa_activa'] == 'Pauză') {
        $stats_daily['Vehicule în Cursă Azi']++;
    } elseif ($veh['status'] == 'În service') {
        $stats_daily['Vehicule în Service Azi']++;
    }
    if (!empty($veh['ultima_problema_nerezolvata'])) {
        $stats_daily['Probleme Nerezolvate']++;
    }
}
$stats_daily['Vehicule Disponibile'] = $stats_daily['Total Vehicule'] - $stats_daily['Vehicule în Cursă Azi'] - $stats_daily['Vehicule în Service Azi'];

$conn->close(); // Închidem conexiunea la baza de date aici.

// Tipuri de vehicule pentru filtrare (extrage din lista de vehicule pentru a fi dinamice)
$tipuri_vehicul_list = array_unique(array_column($vehicule_list, 'tip'));
sort($tipuri_vehicul_list);

// Statusuri vehicul pentru filtrare
$statusuri_vehicul = ['Disponibil', 'În cursă', 'În service', 'Indisponibil'];

// --- Logică pentru Export (triggered by URL parameter) ---
$export_format = $_GET['export_format'] ?? null;

if ($export_format) {
    // Aici vom genera conținutul raportului pentru export
    // Pentru simplitate, vom genera HTML-ul tabelului și îl vom folosi pentru export
    ob_start(); // Începe buffering-ul de output
    ?>
    <h1>Raport Flotă Zilnic - <?php echo htmlspecialchars($display_date->format('d.m.Y')); ?></h1>
    <h3>Sumar Flotă</h3>
    <p>Total Vehicule: <?php echo htmlspecialchars($stats_daily['Total Vehicule']); ?></p>
    <p>Vehicule Disponibile: <?php echo htmlspecialchars($stats_daily['Vehicule Disponibile']); ?></p>
    <p>Vehicule în Cursă Azi: <?php echo htmlspecialchars($stats_daily['Vehicule în Cursă Azi']); ?></p>
    <p>Vehicule în Service Azi: <?php echo htmlspecialchars($stats_daily['Vehicule în Service Azi']); ?></p>
    <p>Probleme Nerezolvate: <?php echo htmlspecialchars($stats_daily['Probleme Nerezolvate']); ?></p>
    <br>
    <h3>Detalii Flotă</h3>
    <table border="1" cellpadding="5" cellspacing="0" id="reportTable"> <thead>
            <tr>
                <th>Vehicul</th>
                <th>Tip Vehicul</th>
                <th>Număr Înmatriculare</th>
                <th>Status Curent</th>
                <th>Ultima Destinație (Azi)</th>
                <th>Kilometraj Curent</th>
                <th>Probleme Nerezolvate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fleet_report_data as $veh): ?>
                <tr>
                    <td><?php echo htmlspecialchars($veh['model']); ?></td>
                    <td><?php echo htmlspecialchars($veh['tip'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($veh['numar_inmatriculare']); ?></td>
                    <td><?php echo htmlspecialchars($veh['status']); ?></td>
                    <td><?php echo htmlspecialchars($veh['ultima_destinatie_azi'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars(number_format($veh['kilometraj'], 0, ',', '.')) . ' km'; ?></td>
                    <td><?php echo !empty($veh['ultima_problema_nerezolvata']) ? 'Da (' . htmlspecialchars($veh['ultima_problema_nerezolvata']) . ')' : 'Nu'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    $report_html_content = ob_get_clean(); // Preluăm conținutul HTML și oprim buffering-ul

    switch ($export_format) {
        case 'pdf':
            // Pentru PDF, vom folosi jspdf și jspdf-autotable pe client-side
            // Redirecționăm cu un script care declanșează exportul
            echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'></script>";
            echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js'></script>";
            echo "<script>";
            echo "document.addEventListener('DOMContentLoaded', function() {";
            echo "    const { jsPDF } = window.jspdf;";
            echo "    const doc = new jsPDF('p', 'pt', 'a4');";
            echo "    doc.setFont('Noto Sans', 'normal');"; // Asigură fontul pentru caractere românești
            echo "    doc.html(document.body.innerHTML, {"; // Exportă întregul body (care conține doar raportul acum)
            echo "        callback: function (doc) {";
            echo "            doc.save('Raport_Flota_Zilnic_" . $selected_date . ".pdf');";
            echo "            window.close(); // Închide fereastra după export";
            echo "        },";
            echo "        x: 10, y: 10, width: 580, windowWidth: 794";
            echo "    });";
            echo "});";
            echo "</script>";
            echo $report_html_content; // Afișăm conținutul pentru ca jspdf să-l poată prelua
            exit();

        case 'excel':
            // Pentru Excel, vom folosi xlsx.full.min.js pe client-side
            echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js'></script>";
            echo "<script>";
            echo "document.addEventListener('DOMContentLoaded', function() {";
            echo "    const table = document.getElementById('reportTable');"; // Asigură că tabelul are ID-ul 'reportTable'
            echo "    const ws = XLSX.utils.table_to_sheet(table);";
            echo "    const wb = XLSX.utils.book_new();";
            echo "    XLSX.utils.book_append_sheet(wb, ws, 'Raport Flota Zilnic');";
            echo "    XLSX.writeFile(wb, 'Raport_Flota_Zilnic_" . $selected_date . ".xlsx');";
            echo "    window.close(); // Închide fereastra după export";
            echo "});";
            echo "</script>";
            echo $report_html_content; // Afișăm conținutul pentru ca xlsx să-l poată prelua
            exit();

        case 'print':
            echo "<script>";
            echo "document.addEventListener('DOMContentLoaded', function() {";
            echo "    window.print();";
            echo "    window.onafterprint = function() { window.close(); }; // Închide fereastra după print";
            echo "});";
            echo "</script>";
            echo $report_html_content; // Afișăm conținutul pentru print
            exit();
    }
}

require_once 'template/header.php'; // Header-ul paginii normale
?>

<title>NTS TOUR | Raport Flotă Zilnic</title>

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
                        <li class="breadcrumb-item active" aria-current="page">Raport Flotă Zilnic</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Raport Flotă Zilnic - <?php echo htmlspecialchars($display_date->format('d.m.Y')); ?></h4>
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
                                <label for="reportDate" class="form-label">Selectează Data:</label>
                                <input type="date" class="form-control" id="reportDate" value="<?php echo htmlspecialchars($selected_date); ?>">
                            </div>
                            <div class="col-md-8 mb-3 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-primary me-2 disabled"><i class="bx bxs-file-pdf"></i> Export PDF</button>
                                <button type="button" class="btn btn-success me-2 disabled"><i class="bx bxs-file-excel"></i> Export Excel</button>
                                <button type="button" class="btn btn-info disabled"><i class="bx bx-printer"></i> Printează</button>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
                            <div class="col">
                                <div class="card stat-card border-left-info">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Total Vehicule</p><h4 class="my-1"><?php echo htmlspecialchars($stats_daily['Total Vehicule']); ?></h4></div>
                                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-collection"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-success">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Disponibile</p><h4 class="my-1"><?php echo htmlspecialchars($stats_daily['Vehicule Disponibile']); ?></h4></div>
                                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bxs-car"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-warning">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">În Cursă Azi</p><h4 class="my-1"><?php echo htmlspecialchars($stats_daily['Vehicule în Cursă Azi']); ?></h4></div>
                                        <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bxs-stopwatch"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-danger">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">În Service Azi</p><h4 class="my-1"><?php echo htmlspecialchars($stats_daily['Vehicule în Service Azi']); ?></h4></div>
                                        <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bxs-wrench"></i></div>
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
                                    <?php foreach ($tipuri_vehicul_list as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status Curent:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_vehicul as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta model, număr înmatriculare, destinație...">
                                </div>
                            </div>
                        </div>

                        <?php if (empty($fleet_report_data)): ?>
                            <div class="alert alert-info">Nu există date de flotă pentru data selectată.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="reportTable">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Vehicul</th>
                                            <th>Număr Înmatriculare</th>
                                            <th>Status Curent</th>
                                            <th>Ultima Destinație (Azi)</th>
                                            <th>Kilometraj Curent</th>
                                            <th>Probleme Nerezolvate</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportTableBody">
                                        <?php foreach ($fleet_report_data as $veh): ?>
                                            <tr 
                                                data-id-vehicul="<?php echo $veh['id']; ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($veh['tip'] ?? ''); ?>"
                                                data-status="<?php echo htmlspecialchars($veh['status']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($veh['model'] . ' ' . $veh['numar_inmatriculare'] . ' ' . $veh['tip'] . ' ' . $veh['ultima_destinatie_azi'] . ' ' . $veh['ultima_problema_nerezolvata'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($veh['model']); ?></td>
                                                <td data-label="Tip Vehicul:"><?php echo htmlspecialchars($veh['tip'] ?? 'N/A'); ?></td>
                                                <td data-label="Număr Înmatriculare:"><?php echo htmlspecialchars($veh['numar_inmatriculare']); ?></td>
                                                <td data-label="Status Curent:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $veh['status'])); ?>"><?php echo htmlspecialchars($veh['status']); ?></span></td>
                                                <td data-label="Ultima Destinație (Azi):"><?php echo htmlspecialchars($veh['ultima_destinatie_azi'] ?? 'N/A'); ?></td>
                                                <td data-label="Kilometraj Curent:"><?php echo htmlspecialchars(number_format($veh['kilometraj'], 0, ',', '.')) . ' km'; ?></td>
                                                <td data-label="Probleme Nerezolvate:"><?php echo !empty($veh['ultima_problema_nerezolvata']) ? '<span class="badge bg-danger">Da (' . htmlspecialchars($veh['ultima_problema_nerezolvata']) . ')</span>' : '<span class="badge bg-success">Nu</span>'; ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportDateInput = document.getElementById('reportDate');
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipVehicul = document.getElementById('filterTipVehicul');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');
    const reportTableBody = document.getElementById('reportTableBody');

    // Functie pentru a reincarca pagina cu data selectata
    reportDateInput.addEventListener('change', function() {
        const selectedDate = this.value;
        window.location.href = `raport-flota-zilnic.php?date=${selectedDate}`;
    });

    // Functie de filtrare pentru tabel
    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipVehicul = filterTipVehicul.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#reportTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipVehicul = row.getAttribute('data-tip-vehicul');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipVehiculMatch = (selectedTipVehicul === 'all' || rowTipVehicul === selectedTipVehicul);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipVehiculMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipVehicul.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);

    // Initial filter on page load
    filterTable();


    // Functii de Export si Print (triggered by URL parameter in PHP)
    // Acestea sunt acum gestionate de export-rapoarte.php care redirecționează aici cu un parametru
    // Nu mai avem butoane de export direct pe această pagină, ele sunt în export-rapoarte.php
});
</script>