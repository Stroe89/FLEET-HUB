<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie
require_once 'template/header.php'; // Includerea header-ului

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

// Funcție ajutătoare pentru a verifica existența unui tabel
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
    return $result && $result->num_rows > 0;
}

// --- Logică pentru calculul Datelor Financiare ---
$total_revenues = 0;
$total_expenses = 0;
$profit = 0;

$revenue_details = [];
$expense_details = []; // Detalii cheltuieli pe categorii

// 1. Venituri din facturi (status 'Platita')
if (tableExists($conn, 'facturi')) {
    // Aici folosim 'data_inregistrare' pentru că baza ta de date a indicat că această coloană există.
    $sql_revenues = "SELECT valoare_totala, data_inregistrare, numar_factura FROM facturi WHERE status = 'Platita' AND data_inregistrare BETWEEN ? AND ?";
    $stmt_revenues = $conn->prepare($sql_revenues);
    if ($stmt_revenues) {
        $stmt_revenues->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_revenues->execute()) {
            // Afișează o eroare explicită dacă interogarea eșuează
            $error_message .= "Eroare la executarea interogării pentru venituri: " . $stmt_revenues->error . "<br>SQL: " . htmlspecialchars($sql_revenues);
        } else {
            $result_revenues = $stmt_revenues->get_result();
            while ($row = $result_revenues->fetch_assoc()) {
                $total_revenues += $row['valoare_totala'];
                $revenue_details[] = ['type' => 'Factură Plătită', 'description' => 'Factura Nr. ' . $row['numar_factura'], 'amount' => $row['valoare_totala'], 'date' => $row['data_inregistrare']];
            }
        }
        $stmt_revenues->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru venituri: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_revenues);
    }
}

// 2. Cheltuieli Generale (din tabelul 'cheltuieli', dacă există)
if (tableExists($conn, 'cheltuieli')) {
    // Folosim 'data_cheltuiala' conform erorilor anterioare.
    $sql_general_expenses = "SELECT suma, data_cheltuiala, tip_cheltuiala, descriere FROM cheltuieli WHERE data_cheltuiala BETWEEN ? AND ?";
    $stmt_general_expenses = $conn->prepare($sql_general_expenses);
    if ($stmt_general_expenses) {
        $stmt_general_expenses->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_general_expenses->execute()) {
            $error_message .= "Eroare la executarea interogării pentru cheltuieli generale: " . $stmt_general_expenses->error . "<br>SQL: " . htmlspecialchars($sql_general_expenses);
        } else {
            $result_general_expenses = $stmt_general_expenses->get_result();
            while ($row = $result_general_expenses->fetch_assoc()) {
                $total_expenses += $row['suma'];
                $expense_details[] = ['category' => $row['tip_cheltuiala'], 'description' => $row['descriere'], 'amount' => $row['suma'], 'date' => $row['data_cheltuiala']];
            }
        }
        $stmt_general_expenses->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru cheltuieli generale: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_general_expenses);
    }
}

// 3. Cheltuieli Salariale (salarii, bonusuri, bonuri de masă)
if (tableExists($conn, 'salarii')) {
    $sql_salaries = "SELECT SUM(salariu_baza) as total_salarii FROM salarii WHERE data_inceput_salariu BETWEEN ? AND ?";
    $stmt_salaries = $conn->prepare($sql_salaries);
    if ($stmt_salaries) {
        $stmt_salaries->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_salaries->execute()) {
            $error_message .= "Eroare la executarea interogării pentru salarii: " . $stmt_salaries->error . "<br>SQL: " . htmlspecialchars($sql_salaries);
        } else {
            $result_salaries = $stmt_salaries->get_result()->fetch_assoc();
            $salarii_cost = $result_salaries['total_salarii'] ?? 0;
            $total_expenses += $salarii_cost;
            if ($salarii_cost > 0) $expense_details[] = ['category' => 'Salarii', 'description' => 'Costuri salariale de bază', 'amount' => $salarii_cost, 'date' => $end_date_str];
        }
        $stmt_salaries->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru salarii: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_salaries);
    }
}
if (tableExists($conn, 'bonusuri')) {
    $sql_bonuses = "SELECT SUM(valoare) as total_bonusuri FROM bonusuri WHERE data_acordare BETWEEN ? AND ?";
    $stmt_bonuses = $conn->prepare($sql_bonuses);
    if ($stmt_bonuses) {
        $stmt_bonuses->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_bonuses->execute()) {
            $error_message .= "Eroare la executarea interogării pentru bonusuri: " . $stmt_bonuses->error . "<br>SQL: " . htmlspecialchars($sql_bonuses);
        } else {
            $result_bonuses = $stmt_bonuses->get_result()->fetch_assoc();
            $bonusuri_cost = $result_bonuses['total_bonusuri'] ?? 0;
            $total_expenses += $bonusuri_cost;
            if ($bonusuri_cost > 0) $expense_details[] = ['category' => 'Bonusuri', 'description' => 'Bonusuri acordate angajaților', 'amount' => $bonusuri_cost, 'date' => $end_date_str];
        }
        $stmt_bonuses->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru bonusuri: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_bonuses);
    }
}
if (tableExists($conn, 'bonuri_masa')) {
    $sql_meal_vouchers = "SELECT SUM(numar_bonuri * valoare_bon_unitar) as total_bonuri_masa FROM bonuri_masa WHERE data_acordare BETWEEN ? AND ?";
    $stmt_meal_vouchers = $conn->prepare($sql_meal_vouchers);
    if ($stmt_meal_vouchers) {
        $stmt_meal_vouchers->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_meal_vouchers->execute()) {
            $error_message .= "Eroare la executarea interogării pentru bonuri de masă: " . $stmt_meal_vouchers->error . "<br>SQL: " . htmlspecialchars($sql_meal_vouchers);
        } else {
            $result_meal_vouchers = $stmt_meal_vouchers->get_result()->fetch_assoc();
            $bonuri_masa_cost = $result_meal_vouchers['total_bonuri_masa'] ?? 0;
            $total_expenses += $bonuri_masa_cost;
            if ($bonuri_masa_cost > 0) $expense_details[] = ['category' => 'Bonuri Masă', 'description' => 'Costuri cu bonuri de masă', 'amount' => $bonuri_masa_cost, 'date' => $end_date_str];
        }
        $stmt_meal_vouchers->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru bonuri de masă: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_meal_vouchers);
    }
}

// 4. Cheltuieli Mentenanță și Reparații
if (tableExists($conn, 'istoric_mentenanta')) {
    $sql_maintenance_cost = "SELECT SUM(cost_total) as total_cost FROM istoric_mentenanta WHERE data_intrare_service BETWEEN ? AND ?";
    $stmt_maintenance_cost = $conn->prepare($sql_maintenance_cost);
    if ($stmt_maintenance_cost) {
        $stmt_maintenance_cost->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_maintenance_cost->execute()) {
            $error_message .= "Eroare la executarea interogării pentru mentenanță: " . $stmt_maintenance_cost->error . "<br>SQL: " . htmlspecialchars($sql_maintenance_cost);
        } else {
            $result_maintenance_cost = $stmt_maintenance_cost->get_result()->fetch_assoc();
            $maintenance_cost = $result_maintenance_cost['total_cost'] ?? 0;
            $total_expenses += $maintenance_cost;
            if ($maintenance_cost > 0) $expense_details[] = ['category' => 'Mentenanță', 'description' => 'Costuri totale mentenanță vehicule', 'amount' => $maintenance_cost, 'date' => $end_date_str];
        }
        $stmt_maintenance_cost->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru mentenanță: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_maintenance_cost);
    }
}
if (tableExists($conn, 'istoric_reparatii')) {
    $sql_repair_cost = "SELECT SUM(cost_total) as total_cost FROM istoric_reparatii WHERE data_intrare_service BETWEEN ? AND ?";
    $stmt_repair_cost = $conn->prepare($sql_repair_cost);
    if ($stmt_repair_cost) {
        $stmt_repair_cost->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_repair_cost->execute()) {
            $error_message .= "Eroare la executarea interogării pentru reparații: " . $stmt_repair_cost->error . "<br>SQL: " . htmlspecialchars($sql_repair_cost);
        } else {
            $result_repair_cost = $stmt_repair_cost->get_result()->fetch_assoc();
            $repair_cost = $result_repair_cost['total_cost'] ?? 0;
            $total_expenses += $repair_cost;
            if ($repair_cost > 0) $expense_details[] = ['category' => 'Reparații', 'description' => 'Costuri totale reparații vehicule', 'amount' => $repair_cost, 'date' => $end_date_str];
        }
        $stmt_repair_cost->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru reparații: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_repair_cost);
    }
}

// 5. Cheltuieli Combustibil
if (tableExists($conn, 'consum_combustibil')) {
    $sql_fuel_cost = "SELECT SUM(cantitate_litri * pret_litru) as total_cost FROM consum_combustibil WHERE data_alimentare BETWEEN ? AND ?";
    $stmt_fuel_cost = $conn->prepare($sql_fuel_cost);
    if ($stmt_fuel_cost) {
        $stmt_fuel_cost->bind_param("ss", $start_date_str, $end_date_str);
        if (!$stmt_fuel_cost->execute()) {
            $error_message .= "Eroare la executarea interogării pentru combustibil: " . $stmt_fuel_cost->error . "<br>SQL: " . htmlspecialchars($sql_fuel_cost);
        } else {
            $result_fuel_cost = $stmt_fuel_cost->get_result()->fetch_assoc();
            $fuel_cost = $result_fuel_cost['total_cost'] ?? 0;
            $total_expenses += $fuel_cost;
            if ($fuel_cost > 0) $expense_details[] = ['category' => 'Combustibil', 'description' => 'Costuri totale combustibil', 'amount' => $fuel_cost, 'date' => $end_date_str];
        }
        $stmt_fuel_cost->close();
    } else {
        $error_message .= "Eroare la pregătirea interogării pentru combustibil: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql_fuel_cost);
    }
}

$profit = $total_revenues - $total_expenses;

// Sortăm detaliile cheltuielilor după dată
usort($expense_details, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

$conn->close(); // Închidem conexiunea la baza de date aici.
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Raport Financiar</title>

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

    /* Stiluri specifice pentru rapoarte financiare */
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
    /* Badge-uri pentru profit/pierdere */
    .badge-profit { background-color: #28a745; color: #fff; }
    .badge-loss { background-color: #dc3545; color: #fff; }

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
            <div class="breadcrumb-title pe-3">Rapoarte</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Raport Financiar</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Raport Financiar - Perioada: <?php echo htmlspecialchars($start_date_dt->format('d.m.Y')); ?> - <?php echo htmlspecialchars($end_date_dt->format('d.m.Y')); ?></h4>
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
                                <div class="card stat-card border-left-success">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Venituri Totale</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_revenues, 2, ',', '.')); ?> RON</h4></div>
                                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bx-trending-up"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-danger">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Cheltuieli Totale</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($total_expenses, 2, ',', '.')); ?> RON</h4></div>
                                        <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bx-trending-down"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card <?php echo ($profit >= 0) ? 'border-left-info' : 'border-left-warning'; ?>">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Profit Net</p><h4 class="my-1"><?php echo htmlspecialchars(number_format($profit, 2, ',', '.')); ?> RON</h4></div>
                                        <div class="widgets-icons <?php echo ($profit >= 0) ? 'bg-light-info text-info' : 'bg-light-warning text-warning'; ?> ms-auto"><i class="bx bx-wallet"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Detalii Venituri</h5>
                                        <hr>
                                        <?php if (empty($revenue_details)): ?>
                                            <div class="alert alert-info">Nu există venituri înregistrate pentru această perioadă.</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover" id="revenueTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Tip</th>
                                                            <th>Descriere</th>
                                                            <th>Dată</th>
                                                            <th>Valoare</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($revenue_details as $rev): ?>
                                                            <tr>
                                                                <td data-label="Tip:"><?php echo htmlspecialchars($rev['type']); ?></td>
                                                                <td data-label="Descriere:"><?php echo htmlspecialchars(mb_strimwidth($rev['description'], 0, 40, "...")); ?></td>
                                                                <td data-label="Dată:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($rev['date']))); ?></td>
                                                                <td data-label="Valoare:"><?php echo htmlspecialchars(number_format($rev['amount'], 2, ',', '.')) . ' RON'; ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Detalii Cheltuieli</h5>
                                        <hr>
                                        <?php if (empty($expense_details)): ?>
                                            <div class="alert alert-info">Nu există cheltuieli înregistrate pentru această perioadă.</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover" id="expenseTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Categorie</th>
                                                            <th>Descriere</th>
                                                            <th>Dată</th>
                                                            <th>Valoare</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($expense_details as $exp): ?>
                                                            <tr>
                                                                <td data-label="Categorie:"><?php echo htmlspecialchars($exp['category']); ?></td>
                                                                <td data-label="Descriere:"><?php echo htmlspecialchars(mb_strimwidth($exp['description'], 0, 40, "...")); ?></td>
                                                                <td data-label="Dată:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($exp['date']))); ?></td>
                                                                <td data-label="Valoare:"><?php echo htmlspecialchars(number_format($exp['amount'], 2, ',', '.')) . ' RON'; ?></td>
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

    // Functie pentru a reincarca pagina cu noul interval de date
    applyDateFilterBtn.addEventListener('click', function() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        window.location.href = `raport-financiar.php?start_date=${startDate}&end_date=${endDate}`;
    });

    // Functii de Export si Print
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4');
        doc.setFont('Noto Sans', 'normal');

        const title = `Raport Financiar - Perioada: ${startDateInput.value} - ${endDateInput.value}`;

        // Adăugăm statistici sumare
        const summaryHtml = `
            <h3 style="color:#333;">Sumar Financiar</h3>
            <p><strong>Venituri Totale:</strong> ${document.querySelector('.stat-card.border-left-success h4').textContent}</p>
            <p><strong>Cheltuieli Totale:</strong> ${document.querySelector('.stat-card.border-left-danger h4').textContent}</p>
            <p><strong>Profit Net:</strong> ${document.querySelector('.stat-card:nth-child(3) h4').textContent}</p>
            <br>
        `;

        doc.html(summaryHtml, {
            callback: function (doc) {
                // Adăugăm tabelul de venituri
                doc.text("Detalii Venituri", 40, doc.autoTable.previous.finalY + 30);
                doc.autoTable({
                    startY: doc.autoTable.previous.finalY + 50,
                    html: '#revenueTable',
                    theme: 'striped',
                    styles: { font: 'Noto Sans', fontSize: 8, cellPadding: 5, valign: 'middle', overflow: 'linebreak' },
                    headStyles: { fillColor: [59, 67, 90], textColor: [255, 255, 255], fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [42, 48, 66] },
                    bodyStyles: { textColor: [224, 224, 224] }
                });

                // Adăugăm tabelul de cheltuieli
                doc.text("Detalii Cheltuieli", 40, doc.autoTable.previous.finalY + 30);
                doc.autoTable({
                    startY: doc.autoTable.previous.finalY + 50,
                    html: '#expenseTable',
                    theme: 'striped',
                    styles: { font: 'Noto Sans', fontSize: 8, cellPadding: 5, valign: 'middle', overflow: 'linebreak' },
                    headStyles: { fillColor: [59, 67, 90], textColor: [255, 255, 255], fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [42, 48, 66] },
                    bodyStyles: { textColor: [224, 224, 224] }
                });

                doc.save(`Raport_Financiar_${startDateInput.value}_${endDateInput.value}.pdf`);
            },
            x: 10,
            y: 10,
            width: 580, // Lățimea paginii A4 în puncte (aprox 595 - 2*10 margini)
            windowWidth: 794 // Lățimea ferestrei pentru a simula randarea HTML
        });
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const wb = XLSX.utils.book_new();

        // Sheet 1: Sumar
        const summaryData = [
            ["Raport Financiar Sumar"],
            ["Perioada:", `${startDateInput.value} - ${endDateInput.value}`],
            [],
            ["Metrica", "Valoare"],
            ["Venituri Totale", document.querySelector('.stat-card.border-left-success h4').textContent],
            ["Cheltuieli Totale", document.querySelector('.stat-card.border-left-danger h4').textContent],
            ["Profit Net", document.querySelector('.stat-card:nth-child(3) h4').textContent]
        ];
        const ws_summary = XLSX.utils.aoa_to_sheet(summaryData);
        XLSX.utils.book_append_sheet(wb, ws_summary, "Sumar Financiar");

        // Sheet 2: Detalii Venituri
        const revenueTable = document.getElementById('revenueTable');
        if (revenueTable) {
            const ws_revenue = XLSX.utils.table_to_sheet(revenueTable);
            XLSX.utils.book_append_sheet(wb, ws_revenue, "Detalii Venituri");
        }

        // Sheet 3: Detalii Cheltuieli
        const expenseTable = document.getElementById('expenseTable');
        if (expenseTable) {
            const ws_expense = XLSX.utils.table_to_sheet(expenseTable);
            XLSX.utils.book_append_sheet(wb, ws_expense, "Detalii Cheltuieli");
        }

        XLSX.writeFile(wb, `Raport_Financiar_${startDateInput.value}_${endDateInput.value}.xlsx`);
    });

    document.getElementById('printReportBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const printContent = `
            <html>
            <head>
                <title>Raport Financiar - Perioada: ${startDateInput.value} - ${endDateInput.value}</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1, h3 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .stat-card { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
                    .stat-card p { margin-bottom: 5px; }
                    .stat-card h4 { margin-top: 0; color: #000; }
                    .text-secondary { color: #666; }
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
                    .badge-profit { background-color: #28a745; color: #fff; }
                    .badge-loss { background-color: #dc3545; color: #fff; }
                </style>
            </head>
            <body>
                <h1>Raport Financiar - Perioada: ${startDateInput.value} - ${endDateInput.value}</h1>
                <h3>Sumar Financiar</h3>
                <p><strong>Venituri Totale:</strong> ${document.querySelector('.stat-card.border-left-success h4').textContent}</p>
                <p><strong>Cheltuieli Totale:</strong> ${document.querySelector('.stat-card.border-left-danger h4').textContent}</p>
                <p><strong>Profit Net:</strong> ${document.querySelector('.stat-card:nth-child(3) h4').textContent}</p>
                <br>
                <h3>Detalii Venituri</h3>
                ${document.getElementById('revenueTable') ? document.getElementById('revenueTable').outerHTML : '<p>Nu există detalii de venituri.</p>'}
                <br>
                <h3>Detalii Cheltuieli</h3>
                ${document.getElementById('expenseTable') ? document.getElementById('expenseTable').outerHTML : '<p>Nu există detalii de cheltuieli.</p>'}
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