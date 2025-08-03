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

// Simulează rolul utilizatorului (pentru a testa interfața de administrator/mecanic)
// În aplicația reală, acest lucru ar veni din baza de date sau din sistemul de autentificare
$user_role = $_SESSION['user_role'] ?? 'Administrator'; // Poate fi 'Administrator' sau 'Mecanic'

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

// --- Preluare Date pentru Tabele și Dropdown-uri ---

// Preluăm lista de vehicule pentru dropdown-uri și afișare
$vehicule_list = [];
if (tableExists($conn, 'vehicule')) {
    $sql_vehicule = "SELECT id, model, numar_inmatriculare, tip FROM vehicule ORDER BY model ASC, numar_inmatriculare ASC";
    $result_vehicule = $conn->query($sql_vehicule);
    if ($result_vehicule) {
        while ($row = $result_vehicule->fetch_assoc()) {
            $vehicule_list[] = $row;
        }
    }
} else {
    $error_message .= "Tabelul 'vehicule' lipsește. ";
    $vehicule_list = [
        ['id' => 1, 'model' => 'Mercedes Actros', 'numar_inmatriculare' => 'B 10 ABC', 'tip' => 'Camion'],
        ['id' => 2, 'model' => 'Ford Transit', 'numar_inmatriculare' => 'B 20 DEF', 'tip' => 'Autoutilitară'],
    ];
}

// Preluăm lista de angajați (mecanici/responsabili)
$responsabili_list = [];
if (tableExists($conn, 'angajati')) {
    $sql_responsabili = "SELECT id, nume, prenume FROM angajati WHERE functie IN ('Mecanic', 'Administrator', 'Manager') ORDER BY nume ASC, prenume ASC";
    $result_responsabili = $conn->query($sql_responsabili);
    if ($result_responsabili) {
        while ($row = $result_responsabili->fetch_assoc()) {
            $responsabili_list[] = $row;
        }
    }
} else {
    $error_message .= "Tabelul 'angajati' lipsește. ";
    $responsabili_list = [
        ['id' => 1, 'nume' => 'Popescu', 'prenume' => 'Mihai'],
        ['id' => 2, 'nume' => 'Ionescu', 'prenume' => 'Andrei'],
    ];
}

// Preluăm planul de revizii
$plan_revizii = [];
$revizie_types = [ // Lista extinsă de tipuri de revizii/servicii
    'Revizie Generală', 'Schimb Ulei și Filtre', 'ITP', 'Verificare Frâne', 
    'Verificare Anvelope', 'Reparație Motor', 'Diagnosticare Electrică', 
    'Schimb Distribuție', 'Verificare Sistem Răcire', 'Verificare Suspensii',
    'Geometrie Roți', 'Încărcare Freon AC', 'Verificare Sistem Evacuare',
    'Inspecție Pre-Călătorie', 'Altele (Specificați în Observații)'
];
$revizie_statuses = ['Programată', 'În desfășurare', 'Finalizată', 'Anulată'];
$revizie_priorities = ['Normală', 'Urgentă'];

if (tableExists($conn, 'plan_revizii') &&
    columnExists($conn, 'plan_revizii', 'id_vehicul') &&
    columnExists($conn, 'plan_revizii', 'tip_revizie') &&
    columnExists($conn, 'plan_revizii', 'data_programata') &&
    columnExists($conn, 'plan_revizii', 'status')) {

    $sql_plan = "
        SELECT 
            pr.id, 
            pr.id_vehicul, 
            pr.tip_revizie, 
            pr.kilometraj_programat, 
            pr.data_programata, 
            pr.kilometraj_efectuat, 
            pr.data_efectuare, 
            pr.cost_estimat, 
            pr.cost_real, 
            pr.observatii, 
            pr.status, 
            pr.prioritate,
            pr.responsabil_id,
            v.numar_inmatriculare,
            v.model,
            v.tip as tip_vehicul_model, -- Adăugat pentru filtrare după tip vehicul
            a.nume as nume_responsabil,
            a.prenume as prenume_responsabil
        FROM 
            plan_revizii pr
        LEFT JOIN 
            vehicule v ON pr.id_vehicul = v.id
        LEFT JOIN
            angajati a ON pr.responsabil_id = a.id
        ORDER BY 
            pr.data_programata DESC
    ";
    $result_plan = $conn->query($sql_plan);
    if ($result_plan) {
        while ($row = $result_plan->fetch_assoc()) {
            $plan_revizii[] = $row;
        }
    } else {
        $error_message .= "Eroare la preluarea planului de revizii: " . $conn->error;
    }
} else {
    $error_message .= "Tabelul 'plan_revizii' sau una dintre coloanele necesare lipsește. ";
    // Date mock pentru plan_revizii
    $plan_revizii = [
        ['id' => 1, 'id_vehicul' => 1, 'tip_revizie' => 'Revizie Generală', 'kilometraj_programat' => 120000, 'data_programata' => '2025-08-15 09:00:00', 'kilometraj_efectuat' => null, 'data_efectuare' => null, 'cost_estimat' => 1500.00, 'cost_real' => null, 'observatii' => 'Verificare completă.', 'status' => 'Programată', 'prioritate' => 'Normală', 'responsabil_id' => 1, 'numar_inmatriculare' => 'B 10 ABC', 'model' => 'Mercedes Actros', 'tip_vehicul_model' => 'Camion', 'nume_responsabil' => 'Popescu', 'prenume_responsabil' => 'Mihai'],
        ['id' => 2, 'id_vehicul' => 2, 'tip_revizie' => 'Schimb Ulei și Filtre', 'kilometraj_programat' => 50000, 'data_programata' => '2025-07-05 14:00:00', 'kilometraj_efectuat' => 50100, 'data_efectuare' => '2025-07-05 16:30:00', 'cost_estimat' => 300.00, 'cost_real' => 320.50, 'observatii' => 'Ulei și filtre schimbate.', 'status' => 'Finalizată', 'prioritate' => 'Normală', 'responsabil_id' => 2, 'numar_inmatriculare' => 'B 20 DEF', 'model' => 'Ford Transit', 'tip_vehicul_model' => 'Autoutilitară', 'nume_responsabil' => 'Ionescu', 'prenume_responsabil' => 'Andrei'],
        ['id' => 3, 'id_vehicul' => 1, 'tip_revizie' => 'Reparație Motor', 'kilometraj_programat' => null, 'data_programata' => '2025-07-10 08:00:00', 'kilometraj_efectuat' => null, 'data_efectuare' => null, 'cost_estimat' => 5000.00, 'cost_real' => null, 'observatii' => 'Zgomot suspect la motor.', 'status' => 'În desfășurare', 'prioritate' => 'Urgentă', 'responsabil_id' => 1, 'numar_inmatriculare' => 'B 10 ABC', 'model' => 'Mercedes Actros', 'tip_vehicul_model' => 'Camion', 'nume_responsabil' => 'Popescu', 'prenume_responsabil' => 'Mihai'],
    ];
}

// Calcul statistici pentru dashboard
$total_programate = 0;
$total_in_desfasurare = 0;
$total_finalizate = 0;
$cost_total_estimat = 0;
$cost_total_real = 0;

foreach ($plan_revizii as $revizie) {
    if ($revizie['status'] == 'Programată') {
        $total_programate++;
    } elseif ($revizie['status'] == 'În desfășurare') {
        $total_in_desfasurare++;
    } elseif ($revizie['status'] == 'Finalizată') {
        $total_finalizate++;
    }
    $cost_total_estimat += $revizie['cost_estimat'] ?? 0;
    $cost_total_real += $revizie['cost_real'] ?? 0;
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Plan Revizii și Mentenanță</title>

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

    /* Stiluri specifice pentru tabele și butoane */
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
    /* Stiluri pentru butoanele de acțiune din tabel */
    .table .btn-sm {
        padding: 0.5rem 1rem !important; /* Mărește padding-ul pentru butoane mai mari */
        font-size: 0.9rem !important; /* Mărește font-ul */
        width: auto !important; /* Permite lățimii să se ajusteze conținutului */
        min-width: 80px; /* Lățime minimă pentru a nu fi prea mici */
        margin-right: 0.5rem; /* Spațiu între butoane */
        margin-bottom: 0.5rem; /* Spațiu sub butoane pe mobil */
    }
    /* Asigură că butoanele sunt pe o singură linie pe desktop și se înfășoară pe mobil */
    .table td:last-child {
        white-space: nowrap; /* Menține butoanele pe o singură linie pe desktop */
    }
    @media (max-width: 767.98px) {
        .table td:last-child {
            white-space: normal; /* Permite înfășurarea pe mobil */
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end; /* Aliniază la dreapta pe mobil */
            gap: 0.5rem; /* Spațiu între butoane pe mobil */
        }
        .table .btn-sm {
            flex-grow: 1; /* Permite butoanelor să ocupe spațiul disponibil */
            min-width: unset; /* Resetează min-width */
            margin-right: 0; /* Elimină marginea dreapta */
        }
    }

    .table .badge {
        padding: 0.4em 0.7em;
        border-radius: 0.3rem;
        font-size: 0.85em;
        font-weight: 600;
    }
    /* Culori specifice pentru statusuri și priorități */
    .badge-status-Programată { background-color: #007bff !important; color: #fff !important; }
    .badge-status-În_desfășurare { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-status-Finalizată { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-Anulată { background-color: #6c757d !important; color: #fff !important; }
    .badge-priority-Urgentă { background-color: #dc3545 !important; color: #fff !important; }
    .badge-priority-Normală { background-color: #17a2b8 !important; color: #fff !important; }

    /* Stiluri pentru cardurile de statistici */
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
    .stat-card.border-left-secondary { border-left-color: #6c757d !important; }

    /* Stiluri pentru ascunderea/afișarea câmpurilor în funcție de rol */
    .admin-only { display: none; }
    .mechanic-only { display: none; }

    <?php if ($user_role == 'Administrator'): ?>
        .admin-only { display: block; } /* Afișează pentru administratori */
        .admin-only.d-flex { display: flex !important; }
        .admin-only.d-inline-block { display: inline-block !important; }
        .admin-only.d-table-cell { display: table-cell !important; } /* Pentru <th> și <td> */
    <?php elseif ($user_role == 'Mecanic'): ?>
        .mechanic-only { display: block; } /* Afișează pentru mecanici */
        .mechanic-only.d-flex { display: flex !important; }
        .mechanic-only.d-inline-block { display: inline-block !important; }
        .mechanic-only.d-table-cell { display: table-cell !important; }
    <?php endif; ?>
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Mentenanță</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Plan Revizii</li>
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
                        <h4 class="card-title">Plan Revizii și Mentenanță</h4>
                        <p class="text-muted">Gestionează programările de service și starea mentenanței vehiculelor.</p>
                        <hr>

                        <!-- Statistici Mentenanță -->
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
                            <div class="col">
                                <div class="card stat-card border-left-info">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Revizii Programate</p><h4 class="my-1"><?php echo $total_programate; ?></h4></div>
                                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bx-calendar-check"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-warning">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">În Desfășurare</p><h4 class="my-1"><?php echo $total_in_desfasurare; ?></h4></div>
                                        <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bx-hourglass"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card stat-card border-left-success">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Revizii Finalizate</p><h4 class="my-1"><?php echo $total_finalizate; ?></h4></div>
                                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bx-check-circle"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col admin-only"> <!-- Vizibil doar pentru administratori -->
                                <div class="card stat-card border-left-secondary">
                                    <div class="card-body">
                                        <div><p class="mb-0 text-secondary">Costuri Totale Estimat</p><h4 class="my-1"><?php echo number_format($cost_total_estimat, 2, ',', '.'); ?> RON</h4></div>
                                        <div class="widgets-icons bg-light-secondary text-secondary ms-auto"><i class="bx bx-dollar"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formular Adăugare Revizie -->
                        <div class="admin-only"> <!-- Vizibil doar pentru administratori -->
                            <h5 class="mb-3">Adaugă Programare Revizie</h5>
                            <form id="addRevisionForm" class="row g-3 mb-4" action="process_revizii.php" method="POST">
                                <input type="hidden" name="action" value="add">
                                <div class="col-md-6">
                                    <label for="selectVehicle" class="form-label">Vehicul:</label>
                                    <select id="selectVehicle" name="id_vehicul" class="form-select" required>
                                        <option value="">Alege Vehicul...</option>
                                        <?php foreach ($vehicule_list as $veh): ?>
                                            <option value="<?php echo htmlspecialchars($veh['id']); ?>">
                                                <?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipRevizie" class="form-label">Tip Revizie:</label>
                                    <select id="tipRevizie" name="tip_revizie" class="form-select" required>
                                        <option value="">Alege Tip Revizie...</option>
                                        <?php foreach ($revizie_types as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="kilometrajProgramat" class="form-label">Kilometraj Programat (km):</label>
                                    <input type="number" class="form-control" id="kilometrajProgramat" name="kilometraj_programat" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="dataProgramata" class="form-label">Dată Programată:</label>
                                    <input type="datetime-local" class="form-control" id="dataProgramata" name="data_programata" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="costEstimat" class="form-label">Cost Estimat (RON):</label>
                                    <input type="number" step="0.01" class="form-control" id="costEstimat" name="cost_estimat" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="prioritate" class="form-label">Prioritate:</label>
                                    <select id="prioritate" name="prioritate" class="form-select">
                                        <?php foreach ($revizie_priorities as $priority): ?>
                                            <option value="<?php echo htmlspecialchars($priority); ?>"><?php echo htmlspecialchars($priority); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="responsabil" class="form-label">Responsabil (Mecanic/Manager):</label>
                                    <select id="responsabil" name="responsabil_id" class="form-select">
                                        <option value="">Alege Responsabil...</option>
                                        <?php foreach ($responsabili_list as $resp): ?>
                                            <option value="<?php echo htmlspecialchars($resp['id']); ?>">
                                                <?php echo htmlspecialchars($resp['nume']); ?> <?php echo htmlspecialchars($resp['prenume']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Adaugă Programare</button>
                                </div>
                            </form>
                        </div>

                        <hr class="mt-5 mb-4">
                        <h4 class="card-title">Listă Revizii</h4>
                        <p class="text-muted">Filtrează și gestionează programările de mentenanță.</p>
                        <hr>

                        <!-- Filtre pentru revizii existente -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <label for="filterVehicleRev" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicleRev">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo htmlspecialchars($veh['id']); ?>"><?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterStatusRev" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatusRev">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($revizie_statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterPriorityRev" class="form-label">Filtrează după Prioritate:</label>
                                <select class="form-select" id="filterPriorityRev">
                                    <option value="all">Toate Prioritățile</option>
                                    <?php foreach ($revizie_priorities as $priority): ?>
                                        <option value="<?php echo htmlspecialchars($priority); ?>"><?php echo htmlspecialchars($priority); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="searchRev" class="form-label">Căutare:</label>
                                <input type="text" class="form-control" id="searchRev" placeholder="Căutare după tip, observații...">
                            </div>
                        </div>

                        <!-- Butoane de Export -->
                        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel me-2"></i>Export Excel</button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="bx bxs-file-pdf me-2"></i>Export PDF</button>
                            <button type="button" class="btn btn-info" id="printListBtn"><i class="bx bx-printer me-2"></i>Printează</button>
                        </div>

                        <!-- Tabelul cu Plan Revizii -->
                        <?php if (empty($plan_revizii)): ?>
                            <div class="alert alert-info">Nu există programări de revizii înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="revisionsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Vehicul</th>
                                            <th>Tip Revizie</th>
                                            <th>Km Programat</th>
                                            <th>Dată Programată</th>
                                            <th>Km Efectuat</th>
                                            <th>Dată Efectuare</th>
                                            <th class="admin-only d-table-cell">Cost Estimat</th>
                                            <th class="admin-only d-table-cell">Cost Real</th>
                                            <th>Status</th>
                                            <th>Prioritate</th>
                                            <th>Responsabil</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="revisionsTableBody">
                                        <?php foreach ($plan_revizii as $revizie): 
                                            $revizie_status_class = 'badge-status-' . str_replace(' ', '_', $revizie['status']);
                                            $revizie_priority_class = 'badge-priority-' . str_replace(' ', '_', $revizie['prioritate']);
                                        ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($revizie['id']); ?>"
                                                data-id-vehicul="<?php echo htmlspecialchars($revizie['id_vehicul']); ?>"
                                                data-tip-revizie="<?php echo htmlspecialchars($revizie['tip_revizie']); ?>"
                                                data-kilometraj-programat="<?php echo htmlspecialchars($revizie['kilometraj_programat'] ?? ''); ?>"
                                                data-data-programata="<?php echo htmlspecialchars($revizie['data_programata']); ?>"
                                                data-kilometraj-efectuat="<?php echo htmlspecialchars($revizie['kilometraj_efectuat'] ?? ''); ?>"
                                                data-data-efectuare="<?php echo htmlspecialchars($revizie['data_efectuare'] ?? ''); ?>"
                                                data-cost-estimat="<?php echo htmlspecialchars($revizie['cost_estimat'] ?? ''); ?>"
                                                data-cost-real="<?php echo htmlspecialchars($revizie['cost_real'] ?? ''); ?>"
                                                data-observatii="<?php echo htmlspecialchars($revizie['observatii'] ?? ''); ?>"
                                                data-status="<?php echo htmlspecialchars($revizie['status']); ?>"
                                                data-prioritate="<?php echo htmlspecialchars($revizie['prioritate']); ?>"
                                                data-responsabil-id="<?php echo htmlspecialchars($revizie['responsabil_id'] ?? ''); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($revizie['numar_inmatriculare'] . ' ' . $revizie['model'] . ' ' . $revizie['tip_vehicul_model'] . ' ' . $revizie['tip_revizie'] . ' ' . $revizie['observatii'] . ' ' . $revizie['status'] . ' ' . $revizie['prioritate'] . ' ' . $revizie['nume_responsabil'] . ' ' . $revizie['prenume_responsabil'])); ?>"
                                            >
                                                <td data-label="ID:"><?php echo htmlspecialchars($revizie['id']); ?></td>
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($revizie['model'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($revizie['numar_inmatriculare'] ?? 'N/A'); ?>)</td>
                                                <td data-label="Tip Revizie:"><?php echo htmlspecialchars($revizie['tip_revizie']); ?></td>
                                                <td data-label="Km Programat:"><?php echo $revizie['kilometraj_programat'] ? number_format($revizie['kilometraj_programat'], 0, ',', '.') . ' km' : 'N/A'; ?></td>
                                                <td data-label="Dată Programată:"><?php echo (new DateTime($revizie['data_programata']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Km Efectuat:"><?php echo $revizie['kilometraj_efectuat'] ? number_format($revizie['kilometraj_efectuat'], 0, ',', '.') . ' km' : 'N/A'; ?></td>
                                                <td data-label="Dată Efectuare:"><?php echo $revizie['data_efectuare'] ? (new DateTime($revizie['data_efectuare']))->format('d.m.Y H:i') : 'N/A'; ?></td>
                                                <td data-label="Cost Estimat:" class="admin-only d-table-cell"><?php echo $revizie['cost_estimat'] ? number_format($revizie['cost_estimat'], 2, ',', '.') . ' RON' : 'N/A'; ?></td>
                                                <td data-label="Cost Real:" class="admin-only d-table-cell"><?php echo $revizie['cost_real'] ? number_format($revizie['cost_real'], 2, ',', '.') . ' RON' : 'N/A'; ?></td>
                                                <td data-label="Status:"><span class="badge <?php echo $revizie_status_class; ?>"><?php echo htmlspecialchars($revizie['status']); ?></span></td>
                                                <td data-label="Prioritate:"><span class="badge <?php echo $revizie_priority_class; ?>"><?php echo htmlspecialchars($revizie['prioritate']); ?></span></td>
                                                <td data-label="Responsabil:"><?php echo htmlspecialchars($revizie['nume_responsabil'] ?? 'N/A'); ?> <?php echo htmlspecialchars($revizie['prenume_responsabil'] ?? ''); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-2 view-edit-revision-btn" data-id="<?php echo $revizie['id']; ?>" data-bs-toggle="modal" data-bs-target="#revisionModal"><i class="bx bx-edit"></i> Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-success me-2 register-completion-btn mechanic-only" data-id="<?php echo $revizie['id']; ?>" data-bs-toggle="modal" data-bs-target="#revisionModal"><i class="bx bx-check-circle"></i> Înregistrează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-revision-btn admin-only" data-id="<?php echo $revizie['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
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

<!-- Modale -->

<!-- Modal Adaugă/Editează Revizie -->
<div class="modal fade" id="revisionModal" tabindex="-1" aria-labelledby="revisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revisionModalLabel">Adaugă Programare Revizie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="revisionForm" action="process_revizii.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="revisionAction" value="add">
                    <input type="hidden" name="id" id="revisionId">

                    <h6 class="mb-3">Detalii Programare (Doar pentru Administratori)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="modalSelectVehicle" class="form-label">Vehicul:</label>
                            <select id="modalSelectVehicle" name="id_vehicul" class="form-select" required <?php echo ($user_role == 'Mecanic' ? 'disabled' : ''); ?>>
                                <option value="">Alege Vehicul...</option>
                                <?php foreach ($vehicule_list as $veh): ?>
                                    <option value="<?php echo htmlspecialchars($veh['id']); ?>">
                                        <?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalTipRevizie" class="form-label">Tip Revizie:</label>
                            <select id="modalTipRevizie" name="tip_revizie" class="form-select" required <?php echo ($user_role == 'Mecanic' ? 'disabled' : ''); ?>>
                                <option value="">Alege Tip Revizie...</option>
                                <?php foreach ($revizie_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalKilometrajProgramat" class="form-label">Kilometraj Programat (km):</label>
                            <input type="number" class="form-control" id="modalKilometrajProgramat" name="kilometraj_programat" min="0" <?php echo ($user_role == 'Mecanic' ? 'disabled' : ''); ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataProgramata" class="form-label">Dată Programată:</label>
                            <input type="datetime-local" class="form-control" id="modalDataProgramata" name="data_programata" required <?php echo ($user_role == 'Mecanic' ? 'disabled' : ''); ?>>
                        </div>
                        <div class="col-md-6 admin-only"> <!-- Vizibil doar pentru administratori -->
                            <label for="modalCostEstimat" class="form-label">Cost Estimat (RON):</label>
                            <input type="number" step="0.01" class="form-control" id="modalCostEstimat" name="cost_estimat" min="0">
                        </div>
                        <div class="col-md-6 admin-only"> <!-- Vizibil doar pentru administratori -->
                            <label for="modalPrioritate" class="form-label">Prioritate:</label>
                            <select id="modalPrioritate" name="prioritate" class="form-select">
                                <?php foreach ($revizie_priorities as $priority): ?>
                                    <option value="<?php echo htmlspecialchars($priority); ?>"><?php echo htmlspecialchars($priority); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 admin-only"> <!-- Vizibil doar pentru administratori -->
                            <label for="modalResponsabil" class="form-label">Responsabil (Mecanic/Manager):</label>
                            <select id="modalResponsabil" name="responsabil_id" class="form-select">
                                <option value="">Alege Responsabil...</option>
                                <?php foreach ($responsabili_list as $resp): ?>
                                    <option value="<?php echo htmlspecialchars($resp['id']); ?>">
                                        <?php echo htmlspecialchars($resp['nume']); ?> <?php echo htmlspecialchars($resp['prenume']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="mb-3 mt-4">Actualizare Status și Finalizare (Pentru Mecanici și Admin)</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalStatus" class="form-label">Status:</label>
                            <select id="modalStatus" name="status" class="form-select" required>
                                <?php foreach ($revizie_statuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalKilometrajEfectuat" class="form-label">Kilometraj Efectuat (km):</label>
                            <input type="number" class="form-control" id="modalKilometrajEfectuat" name="kilometraj_efectuat" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataEfectuare" class="form-label">Dată Efectuare:</label>
                            <input type="datetime-local" class="form-control" id="modalDataEfectuare" name="data_efectuare">
                        </div>
                        <div class="col-md-6 admin-only"> <!-- Vizibil doar pentru administratori -->
                            <label for="modalCostReal" class="form-label">Cost Real (RON):</label>
                            <input type="number" step="0.01" class="form-control" id="modalCostReal" name="cost_real" min="0">
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații Mecanic/Admin:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmă Ștergere Revizie -->
<div class="modal fade" id="deleteRevisionModal" tabindex="-1" aria-labelledby="deleteRevisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRevisionModalLabel">Confirmă Ștergerea Reviziei</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi programarea de revizie pentru vehiculul <strong id="deleteRevisionVehicleDisplay"></strong> (<strong id="deleteRevisionTypeDisplay"></strong>)? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteRevisionIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRevisionBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Data PHP pentru JavaScript ---
    const allRevisionsData = <?php echo json_encode($plan_revizii); ?>;
    const revisionsMap = {};
    allRevisionsData.forEach(rev => {
        revisionsMap[rev.id] = rev;
    });

    const userRole = "<?php echo htmlspecialchars($user_role); ?>";

    // --- Elemente DOM pentru Filtrare ---
    const filterVehicleRev = document.getElementById('filterVehicleRev');
    const filterStatusRev = document.getElementById('filterStatusRev');
    const filterPriorityRev = document.getElementById('filterPriorityRev');
    const searchRev = document.getElementById('searchRev');
    const revisionsTableBody = document.getElementById('revisionsTableBody');

    function filterRevisionsTable() {
        const selectedVehicleId = filterVehicleRev.value;
        const selectedStatus = filterStatusRev.value;
        const selectedPriority = filterPriorityRev.value;
        const searchText = searchRev.value.toLowerCase().trim();

        document.querySelectorAll('#revisionsTableBody tr').forEach(row => {
            const rowVehicleId = row.getAttribute('data-id-vehicul');
            const rowStatus = row.getAttribute('data-status');
            const rowPriority = row.getAttribute('data-prioritate');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehicleMatch = (selectedVehicleId === 'all' || rowVehicleId === selectedVehicleId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const priorityMatch = (selectedPriority === 'all' || rowPriority === selectedPriority);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehicleMatch && statusMatch && priorityMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicleRev.addEventListener('change', filterRevisionsTable);
    filterStatusRev.addEventListener('change', filterRevisionsTable);
    filterPriorityRev.addEventListener('change', filterRevisionsTable);
    searchRev.addEventListener('input', filterRevisionsTable);
    filterRevisionsTable(); // Rulează la încărcarea paginii

    // --- Logică Modale (Adăugare / Editare / Ștergere) ---

    // Modal Adaugă/Editează Revizie
    const revisionModal = document.getElementById('revisionModal');
    const revisionForm = document.getElementById('revisionForm');
    const revisionActionInput = document.getElementById('revisionAction');
    const revisionIdInput = document.getElementById('revisionId');
    const revisionModalLabel = document.getElementById('revisionModalLabel');

    const modalSelectVehicle = document.getElementById('modalSelectVehicle');
    const modalTipRevizie = document.getElementById('modalTipRevizie');
    const modalKilometrajProgramat = document.getElementById('modalKilometrajProgramat');
    const modalDataProgramata = document.getElementById('modalDataProgramata');
    const modalCostEstimat = document.getElementById('modalCostEstimat');
    const modalPrioritate = document.getElementById('modalPrioritate');
    const modalResponsabil = document.getElementById('modalResponsabil');
    const modalStatus = document.getElementById('modalStatus');
    const modalKilometrajEfectuat = document.getElementById('modalKilometrajEfectuat');
    const modalDataEfectuare = document.getElementById('modalDataEfectuare');
    const modalCostReal = document.getElementById('modalCostReal');
    const modalObservatii = document.getElementById('modalObservatii');

    // Resetare formular la deschiderea modalului pentru adăugare (Admin)
    document.querySelector('.btn.btn-primary[data-bs-toggle="modal"][data-bs-target="#revisionModal"]').addEventListener('click', function() {
        revisionForm.reset();
        revisionActionInput.value = 'add';
        revisionIdInput.value = '';
        revisionModalLabel.textContent = 'Adaugă Programare Revizie';
        // Asigură că toate câmpurile sunt active pentru administrator la adăugare
        modalSelectVehicle.disabled = false;
        modalTipRevizie.disabled = false;
        modalKilometrajProgramat.disabled = false;
        modalDataProgramata.disabled = false;
        modalCostEstimat.disabled = false;
        modalPrioritate.disabled = false;
        modalResponsabil.disabled = false;
        modalKilometrajEfectuat.disabled = false;
        modalDataEfectuare.disabled = false;
        modalCostReal.disabled = false;
        modalObservatii.disabled = false;
    });


    // Populează modalul de editare la click pe butonul "Editează" (Admin)
    document.querySelectorAll('.view-edit-revision-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const revizie = revisionsMap[id];
            if (revizie) {
                revisionActionInput.value = 'edit';
                revisionIdInput.value = revizie.id;
                revisionModalLabel.textContent = 'Editează Programare Revizie';

                modalSelectVehicle.value = revizie.id_vehicul;
                modalTipRevizie.value = revizie.tip_revizie;
                modalKilometrajProgramat.value = revizie.kilometraj_programat || '';
                modalDataProgramata.value = revizie.data_programata ? new Date(revizie.data_programata).toISOString().slice(0, 16) : '';
                modalCostEstimat.value = revizie.cost_estimat || '';
                modalPrioritate.value = revizie.prioritate;
                modalResponsabil.value = revizie.responsabil_id || '';
                modalStatus.value = revizie.status;
                modalKilometrajEfectuat.value = revizie.kilometraj_efectuat || '';
                modalDataEfectuare.value = revizie.data_efectuare ? new Date(revizie.data_efectuare).toISOString().slice(0, 16) : '';
                modalCostReal.value = revizie.cost_real || '';
                modalObservatii.value = revizie.observatii || '';

                // Logica de activare/dezactivare câmpuri în funcție de rol
                if (userRole === 'Mecanic') {
                    modalSelectVehicle.disabled = true;
                    modalTipRevizie.disabled = true;
                    modalKilometrajProgramat.disabled = true;
                    modalDataProgramata.disabled = true;
                    modalCostEstimat.disabled = true; // Doar vizibil, nu editabil
                    modalPrioritate.disabled = true;
                    modalResponsabil.disabled = true;
                    // Câmpurile de finalizare sunt editabile pentru mecanici
                    modalStatus.disabled = false;
                    modalKilometrajEfectuat.disabled = false;
                    modalDataEfectuare.disabled = false;
                    modalCostReal.disabled = true; // Doar vizibil, nu editabil
                    modalObservatii.disabled = false;
                } else { // Administrator
                    modalSelectVehicle.disabled = false;
                    modalTipRevizie.disabled = false;
                    modalKilometrajProgramat.disabled = false;
                    modalDataProgramata.disabled = false;
                    modalCostEstimat.disabled = false;
                    modalPrioritate.disabled = false;
                    modalResponsabil.disabled = false;
                    modalStatus.disabled = false;
                    modalKilometrajEfectuat.disabled = false;
                    modalDataEfectuare.disabled = false;
                    modalCostReal.disabled = false;
                    modalObservatii.disabled = false;
                }
                new bootstrap.Modal(revisionModal).show();
            }
        });
    });

    // Butonul "Înregistrează Revizie Efectuată" (pentru mecanici)
    document.querySelectorAll('.register-completion-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const revizie = revisionsMap[id];
            if (revizie) {
                revisionActionInput.value = 'edit'; // Este tot o acțiune de editare
                revisionIdInput.value = revizie.id;
                revisionModalLabel.textContent = 'Înregistrează Finalizare Revizie';

                // Pre-populează câmpurile existente
                modalSelectVehicle.value = revizie.id_vehicul;
                modalTipRevizie.value = revizie.tip_revizie;
                modalKilometrajProgramat.value = revizie.kilometraj_programat || '';
                modalDataProgramata.value = revizie.data_programata ? new Date(revizie.data_programata).toISOString().slice(0, 16) : '';
                modalCostEstimat.value = revizie.cost_estimat || '';
                modalPrioritate.value = revizie.prioritate;
                modalResponsabil.value = revizie.responsabil_id || '';
                modalObservatii.value = revizie.observatii || '';

                // Setează statusul la "În desfășurare" sau "Finalizată" implicit, dacă nu e deja
                if (revizie.status === 'Programată' || revizie.status === 'Nouă') {
                    modalStatus.value = 'În desfășurare';
                } else {
                    modalStatus.value = revizie.status;
                }
                
                // Pre-populează câmpurile de finalizare cu valori curente dacă există
                modalKilometrajEfectuat.value = revizie.kilometraj_efectuat || '';
                modalDataEfectuare.value = revizie.data_efectuare ? new Date(revizie.data_efectuare).toISOString().slice(0, 16) : new Date().toISOString().slice(0, 16); // Data curentă
                modalCostReal.value = revizie.cost_real || '';

                // Dezactivează câmpurile de planificare pentru mecanici
                modalSelectVehicle.disabled = true;
                modalTipRevizie.disabled = true;
                modalKilometrajProgramat.disabled = true;
                modalDataProgramata.disabled = true;
                modalCostEstimat.disabled = true;
                modalPrioritate.disabled = true;
                modalResponsabil.disabled = true;
                
                // Activează câmpurile de finalizare pentru mecanici
                modalStatus.disabled = false;
                modalKilometrajEfectuat.disabled = false;
                modalDataEfectuare.disabled = false;
                modalCostReal.disabled = true; // Costul real este editabil doar de admin
                modalObservatii.disabled = false;

                new bootstrap.Modal(revisionModal).show();
            }
        });
    });


    // Trimiterea formularului de adăugare/editare revizie
    revisionForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(revisionForm);

        // Re-activează câmpurile disabled înainte de trimitere, altfel valorile nu vor fi incluse
        // Această parte este crucială pentru a trimite datele câmpurilor disabled
        if (userRole === 'Mecanic') {
            modalSelectVehicle.disabled = false;
            modalTipRevizie.disabled = false;
            modalKilometrajProgramat.disabled = false;
            modalDataProgramata.disabled = false;
            modalCostEstimat.disabled = false;
            modalPrioritate.disabled = false;
            modalResponsabil.disabled = false;
            modalCostReal.disabled = false;
        }

        fetch('process_revizii.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) // Așteptăm text, nu JSON
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(revisionModal);
            if (modalInstance) { modalInstance.hide(); }
            if (data.includes("success")) {
                alert('Programarea a fost salvată cu succes!');
                location.reload(); 
            } else {
                alert('Eroare la salvarea programării: ' + data);
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea programării:', error);
            alert('A apărut o eroare la salvarea programării.');
        });
    });

    // Modal Confirmă Ștergere Revizie
    const deleteRevisionModal = document.getElementById('deleteRevisionModal');
    const deleteRevisionIdConfirm = document.getElementById('deleteRevisionIdConfirm');
    const deleteRevisionVehicleDisplay = document.getElementById('deleteRevisionVehicleDisplay');
    const deleteRevisionTypeDisplay = document.getElementById('deleteRevisionTypeDisplay');
    const confirmDeleteRevisionBtn = document.getElementById('confirmDeleteRevisionBtn');

    document.querySelectorAll('.delete-revision-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const revizie = revisionsMap[id];
            if (revizie) {
                deleteRevisionIdConfirm.value = revizie.id;
                deleteRevisionVehicleDisplay.textContent = `${revizie.model} (${revizie.numar_inmatriculare})`;
                deleteRevisionTypeDisplay.textContent = revizie.tip_revizie;
                new bootstrap.Modal(deleteRevisionModal).show();
            }
        });
    });

    confirmDeleteRevisionBtn.addEventListener('click', function() {
        const revisionIdToDelete = document.getElementById('deleteRevisionIdConfirm').value;
        if (revisionIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', revisionIdToDelete);

            fetch('process_revizii.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data);
                const modalInstance = bootstrap.Modal.getInstance(deleteRevisionModal);
                if (modalInstance) { modalInstance.hide(); }
                if (data.includes("success")) {
                    alert('Programarea a fost ștearsă cu succes!');
                    location.reload(); 
                } else {
                    alert('Eroare la ștergerea programării: ' + data);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea programării:', error);
                alert('A apărut o eroare la ștergerea programării.');
            });
        }
    });

    // --- Funcționalitate Export (PDF, Excel, Print) ---
    function exportTableToPDF(tableId, title) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const headers = [];
        // Colectează headerele vizibile în funcție de rol
        document.querySelectorAll(`#${tableId} thead th`).forEach(th => {
            // Exclude coloanele ascunse de CSS (admin-only)
            if (th.offsetParent !== null && !th.classList.contains('admin-only')) {
                headers.push(th.textContent);
            }
        });

        const data = [];
        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
            if (row.style.display !== 'none') { // Doar rândurile vizibile
                const rowData = [];
                // Colectează celulele vizibile în funcție de rol și exclude ultima coloană (Acțiuni)
                row.querySelectorAll('td:not(:last-child)').forEach(td => {
                    if (td.offsetParent !== null && !td.classList.contains('admin-only')) {
                        const badgeSpan = td.querySelector('.badge');
                        if (badgeSpan) {
                            rowData.push(badgeSpan.textContent);
                        } else {
                            rowData.push(td.textContent);
                        }
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

        doc.save(`${title.replace(/ /g, '_')}.pdf`);
    }

    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        exportTableToPDF('revisionsTable', 'Plan Revizii');
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('revisionsTable');
        const clonedTable = table.cloneNode(true);
        const tbody = clonedTable.querySelector('tbody');
        Array.from(tbody.children).forEach(row => {
            if (row.style.display === 'none') {
                tbody.removeChild(row);
            }
        });
        // Elimină coloanele "Acțiuni" și cele ascunse de rol din clona tabelului înainte de export
        clonedTable.querySelectorAll('th:last-child, td:last-child, .admin-only').forEach(el => el.remove());

        const ws = XLSX.utils.table_to_sheet(clonedTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Plan Revizii");
        XLSX.writeFile(wb, `Plan_Revizii.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('revisionsTable').cloneNode(true);
        // Elimină coloanele "Acțiuni" și cele ascunse de rol din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child, .admin-only').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Plan Revizii și Mentenanță</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Plan Revizii și Mentenanță</h1>
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
            // Elimină orice backdrop-uri reziduale
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
        });
    });
});
</script>
