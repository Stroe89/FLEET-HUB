<?php
session_start(); // Porneste sesiunea la inceputul paginii

// Verifica daca utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    // Daca nu este autentificat, redirectioneaza catre pagina de login
    header("Location: login.php");
    exit(); // Opreste executia scriptului
}

// Daca utilizatorul este autentificat, continua cu incarcarea paginii
require_once 'db_connect.php'; // Conexiunea la baza de date (doar daca userul e autentificat)

// --- Logica pentru Preluarea Datelor Dashboard ---

// 1. Status Flotă (similar cu vehicule.php)
$fleet_status = [
    'Total' => 0,
    'Disponibil' => 0,
    'În cursă' => 0,
    'În service' => 0,
    'Indisponibil' => 0 // Adaugat pentru claritate
];
$sql_fleet_status = "SELECT status, COUNT(*) as count FROM vehicule GROUP BY status";
$result_fleet_status = $conn->query($sql_fleet_status);
if ($result_fleet_status) {
    while($row = $result_fleet_status->fetch_assoc()) {
        $fleet_status[$row['status']] = $row['count'];
    }
    $fleet_status['Total'] = array_sum($fleet_status);
}

// 2. Status Șoferi (presupunem un tabel 'angajati' cu 'functie' si 'status')
$driver_status = [
    'Total Soferi' => 0,
    'Disponibili' => 0,
    'În Cursă' => 0,
    'Indisponibili' => 0 // Include concediu, suspendat, inactiv
];
$sql_driver_status = "SELECT status, COUNT(*) as count FROM angajati WHERE functie = 'Sofer' GROUP BY status";
$result_driver_status = $conn->query($sql_driver_status);
if ($result_driver_status) {
    while($row = $result_driver_status->fetch_assoc()) {
        if ($row['status'] == 'Activ') $driver_status['Disponibili'] += $row['count'];
        else if ($row['status'] == 'În cursă') $driver_status['În Cursă'] += $row['count']; // Presupunem ca statusul 'In cursa' e in angajati
        else $driver_status['Indisponibili'] += $row['count'];
    }
    $driver_status['Total Soferi'] = array_sum($driver_status); // Recalculăm totalul
}

// 3. Alerte Flotă (Probleme, Expirări Documente)
$alerts = [
    'Documente Expirate' => 0,
    'Documente Expiră Curând' => 0,
    'Probleme Nerezolvate' => 0,
    'Revizii Programate' => 0
];
$expiring_docs_list = [];
$today = new DateTime();
$future_date_30_days = (clone $today)->modify('+30 days');

// Număr documente expirate
$sql_expired = "SELECT COUNT(*) as count FROM documente WHERE data_expirare < CURDATE()";
$result_expired = $conn->query($sql_expired);
if ($result_expired && $result_expired->num_rows > 0) {
    $alerts['Documente Expirate'] = $result_expired->fetch_assoc()['count'];
}

// Număr documente care expiră curând (și lista lor)
$sql_expiring_soon = "
    SELECT v.model, v.numar_inmatriculare, d.tip_document, d.data_expirare, d.nume_document_user
    FROM documente d
    JOIN vehicule v ON d.id_vehicul = v.id
    WHERE d.data_expirare BETWEEN ? AND ?
    ORDER BY d.data_expirare ASC
    LIMIT 5
";
$stmt_expiring_soon = $conn->prepare($sql_expiring_soon);
if ($stmt_expiring_soon) {
    $today_str = $today->format('Y-m-d');
    $future_date_str = $future_date_30_days->format('Y-m-d');
    $stmt_expiring_soon->bind_param("ss", $today_str, $future_date_str);
    $stmt_expiring_soon->execute();
    $result_expiring_soon = $stmt_expiring_soon->get_result();
    $alerts['Documente Expiră Curând'] = $result_expiring_soon->num_rows;
    while($row = $result_expiring_soon->fetch_assoc()) {
        $expiring_docs_list[] = $row;
    }
    $stmt_expiring_soon->close();
}

// Număr probleme nerezolvate
$sql_unresolved_problems = "SELECT COUNT(*) as count FROM probleme_raportate WHERE rezolvata = FALSE";
$result_unresolved_problems = $conn->query($sql_unresolved_problems);
if ($result_unresolved_problems && $result_unresolved_problems->num_rows > 0) {
    $alerts['Probleme Nerezolvate'] = $result_unresolved_problems->fetch_assoc()['count'];
}

// Număr revizii programate (viitoare)
$sql_scheduled_revizii = "SELECT COUNT(*) as count FROM plan_revizii WHERE data_programata >= CURDATE() AND status = 'Programata'";
$result_scheduled_revizii = $conn->query($sql_scheduled_revizii);
if ($result_scheduled_revizii && $result_scheduled_revizii->num_rows > 0) {
    $alerts['Revizii Programate'] = $result_scheduled_revizii->fetch_assoc()['count'];
}


// 4. Cash-flow rapid (simplificat, doar totaluri din facturi)
$cash_flow = [
    'Venituri Totale' => 0,
    'Cheltuieli Totale' => 0,
    'Profit Brut' => 0
];
$sql_revenues = "SELECT SUM(valoare_totala) as total_revenue FROM facturi WHERE status = 'Platita'";
$result_revenues = $conn->query($sql_revenues);
if ($result_revenues && $result_revenues->num_rows > 0) {
    $cash_flow['Venituri Totale'] = $result_revenues->fetch_assoc()['total_revenue'] ?? 0;
}

// Presupunem un tabel 'cheltuieli' pentru cheltuieli (dacă nu există, va fi 0)
$sql_expenses = "SELECT SUM(suma) as total_expenses FROM cheltuieli"; // Trebuie să creezi acest tabel
$result_expenses = $conn->query($sql_expenses);
if ($result_expenses && $result_expenses->num_rows > 0) {
    $cash_flow['Cheltuieli Totale'] = $result_expenses->fetch_assoc()['total_expenses'] ?? 0;
}
$cash_flow['Profit Brut'] = $cash_flow['Venituri Totale'] - $cash_flow['Cheltuieli Totale'];


// NU închide conexiunea aici! Va fi închisă automat la sfârșitul scriptului.
// $conn->close();
require_once 'template/header.php'; // Include header-ul după logica PHP
?>

<title>NTS TOUR | Panou de Administrare</title>

<style>
    /* Stiluri generale pentru a asigura vizibilitatea pe fundalul întunecat */
    body {
        background-color: #1a2035; /* Fundal întunecat consistent pentru întregul body */
        color: #e0e0e0; /* Culoare text deschisă implicită pentru tot body-ul */
    }

    .main-content {
        color: #ffffff !important; /* Asigură că tot textul din main-content este alb */
    }
    .main-content .text-muted, .main-content .text-secondary { /* Pentru textele mai puțin importante */
        color: #b0b0b0 !important; /* Un gri mai deschis, dar vizibil */
    }
    h1, h2, h3, h4, h5, h6 {
        color: #ffffff !important; /* Titlurile albe */
    }
    p, span, strong, label, small {
        color: #ffffff !important;
    }
    /* Asigură că numerele din cardurile de statistici sunt albe */
    .stat-card h4 {
        color: #ffffff !important;
    }

    /* Stiluri pentru carduri generale (folosite și în alte pagini, dar definite aici pentru index.php) */
    .card {
        background-color: #2a3042 !important; /* Fundal mai închis pentru carduri */
        color: #e0e0e0 !important; /* Text deschis pentru carduri */
        border: 1px solid rgba(255, 255, 255, 0.1) !important; /* Bordură subtilă */
        border-radius: 0.75rem !important; /* Colțuri rotunjite */
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2); /* Umbră pentru adâncime */
        height: 100%; /* Important: ensure all cards in a flex/grid row have same height */
        display: flex;
        flex-direction: column;
    }
    .card-body {
        flex-grow: 1; /* Allow card body to fill available space */
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .card-title {
        color: #ffffff !important; /* Titlurile cardurilor albe */
    }
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important; /* Linii separatoare albe */
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

    /* Stiluri suplimentare pentru cardurile de statistici */
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

    /* Stiluri pentru butoanele de acțiuni rapide - MODIFICATE */
    .d-grid .btn {
        font-weight: bold !important;
        padding: 0.8rem 1rem !important;
        border-radius: 0.5rem !important;
        transition: all 0.3s ease-in-out !important; /* Tranziție mai fluidă */
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
        position: relative;
        overflow: hidden; /* Ascunde efectele care ies din button */
        z-index: 1;
    }
    .d-grid .btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.15); /* Efect de "flare" */
        border-radius: 50%;
        transition: width 0.4s ease-in-out, height 0.4s ease-in-out;
        transform: translate(-50%, -50%);
        z-index: -1;
    }
    .d-grid .btn:hover {
        transform: translateY(-3px) scale(1.02) !important; /* Efect de ridicare și mărire subtilă */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
    }
    .d-grid .btn:hover::before {
        width: 200%;
        height: 200%;
    }

    /* Culorile butoanelor (adaptează la tema ta) */
    .d-grid .btn-primary { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; }
    .d-grid .btn-info { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: #fff !important; }
    .d-grid .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #343a40 !important; } /* Text închis pentru warning */
    .d-grid .btn-success { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .d-grid .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }

    /* Stiluri pentru badge-uri */
    .badge {
        padding: 0.4em 0.7em;
        border-radius: 0.3rem;
        font-size: 0.85em;
        font-weight: 600;
    }
    .badge.bg-warning { background-color: #ffc107 !important; color: #343a40 !important; } /* Text închis pentru badge-ul warning */
    .badge.bg-danger { background-color: #dc3545 !important; color: #fff !important; }
    .badge.bg-success { background-color: #28a745 !important; color: #fff !important; }
    .badge.bg-info { background-color: #17a2b8 !important; color: #fff !important; }

    /* Stiluri pentru listele din Alerte și Notificări Importante */
    .list-group-flush .list-group-item {
        background-color: #2a3042 !important; /* Fundal pentru elementele listei */
        color: #ffffff !important; /* Text alb pentru elementele listei */
        border-color: rgba(255, 255, 255, 0.1) !important; /* Bordură subtilă */
        padding: 0.75rem 1rem;
        transition: background-color 0.2s ease;
    }
    .list-group-flush .list-group-item:hover {
        background-color: #3b435a !important; /* Fundal la hover */
    }
    .list-group-flush .list-group-item.small {
        font-size: 0.9em;
        color: #e0e0e0 !important; /* Textul mic din listă (ex: detalii document) */
        padding-left: 2.5rem !important; /* Indentare pentru sub-itemi */
    }
    .list-group-flush .list-group-item i.bx {
        margin-right: 0.5rem;
        font-size: 1.1em;
    }
    .list-group-item-icon {
        color: #fff; /* Culoare implicită pentru iconițele din listă */
    }
    .list-group-item-icon.text-danger { color: #dc3545 !important; }
    .list-group-item-icon.text-warning { color: #ffc107 !important; }
    .list-group-item-icon.text-info { color: #17a2b8 !important; }

    /* --- LAYOUT REVISIONS FOR ORIGINAL DIMENSIONS & NEW ORDER --- */

    /* Ensure the main content area correctly uses Bootstrap's gutter system */
    .row.g-4 {
        --bs-gutter-x: 1.5rem; /* Standard Bootstrap gutter */
        --bs-gutter-y: 1.5rem;
    }

    /* Ensure cards stretch to fill height within their flex/grid containers */
    .card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .card-body {
        flex-grow: 1; /* Allow card body to fill available space */
    }

    /* Desktop Layout (Large screens and up) */
    @media (min-width: 992px) { /* Applies to large (lg) and up */
        .dashboard-layout-row {
            display: flex; /* Use flexbox for overall row layout */
            flex-wrap: wrap; /* Allow columns to wrap to the next line */
            align-items: stretch; /* Stretch items to equal height */
            gap: 1.5rem; /* Consistent gap between all columns/cards */
        }

        /* Order elements explicitly using flex 'order' property */
        .welcome-card-container { order: 1; width: 100%; } /* Already col-12, but ensures order */
        
        .alerts-actions-row { order: 2; width: 100%; display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: stretch; }
        .drivers-cashflow-row { order: 3; width: 100%; display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: stretch; }
        .stat-cards-bottom-row { order: 4; width: 100%; display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: stretch; }
        
        /* Ensure individual columns within these new rows take appropriate width */
        .alerts-actions-row > .col-lg-6,
        .drivers-cashflow-row > .col-lg-6 {
            flex: 1 1 calc(50% - 0.75rem); /* Half width minus half of the gap for perfect alignment */
            max-width: calc(50% - 0.75rem);
        }

        .stat-cards-bottom-row > .col-lg-3 {
            flex: 1 1 calc(25% - (1.5rem * 3 / 4)); /* Quarter width minus adjusted gap for perfect alignment */
            max-width: calc(25% - (1.5rem * 3 / 4));
        }
    }

    /* Responsive behavior for mobile/tablet */
    @media (max-width: 991.98px) { /* Applies to medium (md) and down */
        .dashboard-layout-row {
            flex-direction: column; /* Stack all major rows/groups vertically */
            gap: 1.5rem; /* Consistent gap between stacked rows */
        }

        /* Ensure all direct children (which are now `col-12` wrappers for rows) stack */
        .dashboard-layout-row > div {
            width: 100%;
            max-width: 100%;
            flex-basis: 100%;
        }

        /* Inner rows should also stack their children on mobile */
        .alerts-actions-row,
        .drivers-cashflow-row,
        .stat-cards-bottom-row {
            flex-direction: column; /* Stack children within these rows */
            gap: 1.5rem; /* Gap between individual cards when stacked */
        }

        /* Ensure individual cards within stacked rows take full width */
        .alerts-actions-row > div,
        .drivers-cashflow-row > div,
        .stat-cards-bottom-row > div {
            width: 100%;
            max-width: 100%;
            flex-basis: 100%;
        }

        /* Adjust stat card body for very small screens */
        @media (max-width: 575.98px) { /* Extra small devices (xs) */
            .stat-card .card-body {
                flex-direction: column;
                text-align: center;
            }
            .stat-card .widgets-icons {
                margin-bottom: 1rem;
                margin-left: 0 !important; /* Override ms-auto on smaller screens for centering */
            }
        }
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Panoul Principal</div>
            <div class="ps-3">
             
                </ol>
                </nav>
            </div>
        </div>
        
        <div class="row g-4 mb-4 dashboard-layout-row">
            
            <div class="col-12 welcome-card-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Bine ați venit în panoul de administrare NTS TOUR!</h4>
                        <p>Selectați o opțiune din meniul din stânga pentru a începe sau folosiți acțiunile rapide de mai sus.</p>
                    </div>
                </div>
            </div>

            <div class="col-12 alerts-actions-row">
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Alerte și Notificări Importante</h4>
                            <hr>
                            <?php if (array_sum($alerts) > 0 || !empty($expiring_docs_list)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php if ($alerts['Documente Expirate'] > 0): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <i class="bx bxs-file-blank list-group-item-icon text-danger"></i>Documente Expirate:
                                            <span class="badge bg-danger rounded-pill"><?php echo $alerts['Documente Expirate']; ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($alerts['Documente Expiră Curând'] > 0): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <i class="bx bxs-time list-group-item-icon text-warning"></i>Documente care expiră în curând (30 zile):
                                            <span class="badge bg-warning text-dark rounded-pill"><?php echo $alerts['Documente Expiră Curând']; ?></span>
                                        </li>
                                        <?php foreach ($expiring_docs_list as $doc): ?>
                                            <li class="list-group-item small text-muted ps-5">
                                                - <?php echo htmlspecialchars($doc['tip_document']); ?> (<?php echo htmlspecialchars($doc['nume_document_user']); ?>) pentru <?php echo htmlspecialchars($doc['model']); ?> (<?php echo htmlspecialchars($doc['numar_inmatriculare']); ?>) expiră la <?php echo (new DateTime($doc['data_expirare']))->format('d.m.Y'); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if ($alerts['Probleme Nerezolvate'] > 0): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <i class="bx bxs-error-alt list-group-item-icon text-danger"></i>Probleme raportate nerezolvate:
                                            <span class="badge bg-danger rounded-pill"><?php echo $alerts['Probleme Nerezolvate']; ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($alerts['Revizii Programate'] > 0): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <i class="bx bxs-wrench list-group-item-icon text-info"></i>Revizii Programate:
                                            <span class="badge bg-info rounded-pill"><?php echo $alerts['Revizii Programate']; ?></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">Nu există alerte sau notificări importante în acest moment.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Acțiuni Rapide</h4>
                            <hr>
                            <div class="d-grid gap-2">
                                <a href="adauga-vehicul.php" class="btn btn-primary"><i class="bx bx-plus me-2"></i>Adaugă Vehicul Nou</a>
                                <a href="adauga-document.php" class="btn btn-info"><i class="bx bx-file me-2"></i>Adaugă Document Nou</a>
                                <a href="raporteaza-problema.php" class="btn btn-warning text-dark"><i class="bx bx-error-alt me-2"></i>Raportează Problemă</a>
                                <a href="adauga-angajat.php" class="btn btn-success"><i class="bx bx-user-plus me-2"></i>Adaugă Angajat Nou</a>
                                <a href="calendar.php" class="btn btn-secondary"><i class="bx bx-calendar me-2"></i>Vezi Calendar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 drivers-cashflow-row">
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Status Șoferi</h4>
                            <hr>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Total Șoferi:
                                    <span class="badge bg-info rounded-pill"><?php echo $driver_status['Total Soferi']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Disponibili:
                                    <span class="badge bg-success rounded-pill"><?php echo $driver_status['Disponibili']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    În Cursă:
                                    <span class="badge bg-warning text-dark rounded-pill"><?php echo $driver_status['În Cursă']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Indisponibili:
                                    <span class="badge bg-danger rounded-pill"><?php echo $driver_status['Indisponibili']; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Cash-flow Rapid</h4>
                            <hr>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Venituri Totale:
                                    <span class="badge bg-success rounded-pill"><?php echo number_format($cash_flow['Venituri Totale'], 2, ',', '.'); ?> RON</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Cheltuieli Totale:
                                    <span class="badge bg-danger rounded-pill"><?php echo number_format($cash_flow['Cheltuieli Totale'], 2, ',', '.'); ?> RON</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Profit Brut:
                                    <span class="badge bg-info rounded-pill"><?php echo number_format($cash_flow['Profit Brut'], 2, ',', '.'); ?> RON</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 stat-cards-bottom-row">
                <div class="col-12 col-lg-3">
                    <div class="card stat-card border-left-info total-vehicles-card">
                        <div class="card-body">
                            <div><p class="mb-0 text-secondary">Total Vehicule</p><h4 class="my-1"><?php echo $fleet_status['Total']; ?></h4></div>
                            <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-collection"></i></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-lg-3">
                    <div class="card stat-card border-left-success available-vehicles-card">
                        <div class="card-body">
                            <div><p class="mb-0 text-secondary">Disponibile</p><h4 class="my-1"><?php echo $fleet_status['Disponibil']; ?></h4></div>
                            <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bxs-car"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-3">
                    <div class="card stat-card border-left-warning in-transit-vehicles-card">
                        <div class="card-body">
                            <div><p class="mb-0 text-secondary">În Cursă</p><h4 class="my-1"><?php echo $fleet_status['În cursă']; ?></h4></div>
                            <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bxs-stopwatch"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-3">
                    <div class="card stat-card border-left-danger in-service-vehicles-card">
                        <div class="card-body">
                            <div><p class="mb-0 text-secondary">În Service</p><h4 class="my-1"><?php echo $fleet_status['În service']; ?></h4></div>
                            <div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bxs-wrench"></i></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</main>
<?php require_once 'template/footer.php'; ?>