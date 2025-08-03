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

// --- Preluare Date pentru Filtre și Tabel ---

// Preluăm lista de vehicule pentru dropdown-uri
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

// Preluăm lista de șoferi pentru dropdown-uri
$soferi_list = [];
if (tableExists($conn, 'angajati')) {
    $sql_soferi = "SELECT id, nume, prenume FROM angajati WHERE functie = 'Sofer' ORDER BY nume ASC, prenume ASC";
    $result_soferi = $conn->query($sql_soferi);
    if ($result_soferi) {
        while ($row = $result_soferi->fetch_assoc()) {
            $soferi_list[] = $row;
        }
    }
} else {
    $error_message .= "Tabelul 'angajati' lipsește. ";
    $soferi_list = [
        ['id' => 1, 'nume' => 'Popescu', 'prenume' => 'Ion'],
        ['id' => 2, 'nume' => 'Georgescu', 'prenume' => 'Maria'],
    ];
}

// Preluăm toate cursele (active, programate, finalizate, anulate)
$curse_all = [];
$cursa_statuses = ['În desfășurare', 'Programată', 'Finalizată', 'Anulată']; 

if (tableExists($conn, 'curse')) {
    $sql_curse = "
        SELECT 
            c.id, 
            c.id_vehicul, 
            c.id_sofer, 
            c.data_inceput, 
            c.data_sfarsit, 
            c.locatie_plecare, 
            c.locatie_destinatie, 
            c.kilometraj_parcurs, 
            c.observatii, 
            c.status,
            v.numar_inmatriculare, 
            v.model, 
            v.tip as tip_vehicul,
            a.nume as nume_sofer, 
            a.prenume as prenume_sofer
        FROM 
            curse c
        LEFT JOIN 
            vehicule v ON c.id_vehicul = v.id
        LEFT JOIN 
            angajati a ON c.id_sofer = a.id
        ORDER BY 
            c.data_inceput ASC
    ";
    $result_curse = $conn->query($sql_curse);
    if ($result_curse) {
        while ($row = $result_curse->fetch_assoc()) {
            $curse_all[] = $row;
        }
    } else {
        $error_message .= "Eroare la preluarea curselor: " . $conn->error;
    }
} else {
    $error_message .= "Tabelul 'curse' lipsește. ";
    // Date mock pentru curse
    $curse_all = [
        ['id' => 1, 'id_vehicul' => 1, 'id_sofer' => 1, 'data_inceput' => '2025-07-08 08:00:00', 'data_sfarsit' => '2025-07-08 18:00:00', 'locatie_plecare' => 'București', 'locatie_destinatie' => 'Brașov', 'kilometraj_parcurs' => 0, 'observatii' => 'Cursa de test 1.', 'status' => 'În desfășurare', 'numar_inmatriculare' => 'B 10 ABC', 'model' => 'Mercedes Actros', 'tip_vehicul' => 'Camion', 'nume_sofer' => 'Popescu', 'prenume_sofer' => 'Ion'],
        ['id' => 2, 'id_vehicul' => 2, 'id_sofer' => 2, 'data_inceput' => '2025-07-10 09:00:00', 'data_sfarsit' => '2025-07-10 12:00:00', 'locatie_plecare' => 'Sibiu', 'locatie_destinatie' => 'Alba Iulia', 'kilometraj_parcurs' => 0, 'observatii' => 'Cursa programată 1.', 'status' => 'Programată', 'numar_inmatriculare' => 'B 20 DEF', 'model' => 'Ford Transit', 'tip_vehicul' => 'Autoutilitară', 'nume_sofer' => 'Georgescu', 'prenume_sofer' => 'Maria'],
        ['id' => 3, 'id_vehicul' => 1, 'id_sofer' => 1, 'data_inceput' => '2025-07-01 09:00:00', 'data_sfarsit' => '2025-07-01 17:00:00', 'locatie_plecare' => 'Timișoara', 'locatie_destinatie' => 'Arad', 'kilometraj_parcurs' => 100, 'observatii' => 'Cursa finalizată de test.', 'status' => 'Finalizată', 'numar_inmatriculare' => 'B 10 ABC', 'model' => 'Mercedes Actros', 'tip_vehicul' => 'Camion', 'nume_sofer' => 'Popescu', 'prenume_sofer' => 'Ion'],
        ['id' => 4, 'id_vehicul' => 2, 'id_sofer' => 2, 'data_inceput' => '2025-06-25 10:00:00', 'data_sfarsit' => '2025-06-25 10:00:00', 'locatie_plecare' => 'Constanța', 'locatie_destinatie' => 'București', 'kilometraj_parcurs' => 0, 'observatii' => 'Cursa anulată din motive personale.', 'status' => 'Anulată', 'numar_inmatriculare' => 'B 20 DEF', 'model' => 'Ford Transit', 'tip_vehicul' => 'Autoutilitară', 'nume_sofer' => 'Georgescu', 'prenume_sofer' => 'Maria'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Curse Active</title>

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

    /* Stiluri specifice pentru tabele */
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
    /* Culori specifice pentru statusurile curselor */
    .badge-status-În_desfășurare { background-color: #007bff !important; color: #fff !important; }
    .badge-status-Programată { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-status-Finalizată { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-Anulată { background-color: #dc3545 !important; color: #fff !important; }

    /* Stiluri pentru indicatorii de status lângă ID */
    .status-indicator {
        display: inline-block;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        margin-right: 8px;
        vertical-align: middle;
        border: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .status-indicator.status-finalizată { background-color: #28a745; } /* Verde */
    .status-indicator.status-anulată { background-color: #dc3545; } /* Roșu */
    .status-indicator.status-în_desfășurare { background-color: #007bff; } /* Albastru */
    .status-indicator.status-programată { background-color: #ffc107; } /* Galben */

    /* Legendă */
    .legend-item {
        display: flex;
        align-items: center;
        margin-right: 15px;
        font-size: 0.9em;
        color: #e0e0e0;
    }
    .legend-color-box {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        margin-right: 8px;
        border: 1px solid rgba(255,255,255,0.2);
    }
    /* Culori pentru casuțele din legendă */
    .legend-color-box.status-în_desfășurare { background-color: #007bff; }
    .legend-color-box.status-programată { background-color: #ffc107; }
    .legend-color-box.status-finalizată { background-color: #28a745; }
    .legend-color-box.status-anulată { background-color: #dc3545; }


</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Curse Active</div>
            <div class="ps-3">
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
                        <h4 class="card-title">Gestionare Curse Active și Programate</h4>
                        <p class="text-muted">Vizualizează, adaugă și gestionează cursele în desfășurare și cele programate.</p>
                        <hr>

                        <!-- Buton Adaugă Cursă Nouă -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEditCursaModal" id="addNewCursaBtn"><i class="bx bx-plus"></i> Adaugă Cursă Nouă</button>
                            <div class="search-filter-group d-flex align-items-center">
                                <label for="searchInput" class="form-label mb-0 me-2">Caută:</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Caută locație, observații...">
                            </div>
                        </div>

                        <!-- Filtre -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <label for="filterVehicle" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicle">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo htmlspecialchars($veh['id']); ?>"><?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterTipVehicul" class="form-label">Filtrează după Tip Vehicul:</label>
                                <select class="form-select" id="filterTipVehicul">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach (array_unique(array_column($vehicule_list, 'tip')) as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterSofer" class="form-label">Filtrează după Șofer:</label>
                                <select class="form-select" id="filterSofer">
                                    <option value="all">Toți Șoferii</option>
                                    <?php foreach ($soferi_list as $sofer): ?>
                                        <option value="<?php echo htmlspecialchars($sofer['id']); ?>"><?php echo htmlspecialchars($sofer['nume']); ?> <?php echo htmlspecialchars($sofer['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status Cursă:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($cursa_statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterDateStart" class="form-label">Dată Început (de la):</label>
                                <input type="date" class="form-control" id="filterDateStart">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterDateEnd" class="form-label">Dată Sfârșit (până la):</label>
                                <input type="date" class="form-control" id="filterDateEnd">
                            </div>
                        </div>

                        <!-- Butoane de Export -->
                        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel me-2"></i>Export Excel</button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="bx bxs-file-pdf me-2"></i>Export PDF</button>
                            <button type="button" class="btn btn-info" id="printListBtn"><i class="bx bx-printer me-2"></i>Printează</button>
                        </div>

                        <!-- Tabelul cu Curse Active și Programate -->
                        <?php if (empty($curse_all)): // Acum afișăm toate statusurile ?>
                            <div class="alert alert-info">Nu există curse înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="activeCurseTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Vehicul</th>
                                            <th>Tip Vehicul</th>
                                            <th>Șofer</th>
                                            <th>Dată Început</th>
                                            <th>Dată Sfârșit (Est.)</th>
                                            <th>Locație Plecare</th>
                                            <th>Locație Destinație</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activeCurseTableBody">
                                        <?php foreach ($curse_all as $cursa): 
                                            $cursa_status_class = 'badge-status-' . str_replace(' ', '_', $cursa['status']);
                                            $indicator_class = '';
                                            if ($cursa['status'] == 'Finalizată') $indicator_class = 'status-finalizată';
                                            else if ($cursa['status'] == 'Anulată') $indicator_class = 'status-anulată';
                                            else if ($cursa['status'] == 'În desfășurare') $indicator_class = 'status-în_desfășurare';
                                            else if ($cursa['status'] == 'Programată') $indicator_class = 'status-programată';
                                        ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($cursa['id']); ?>"
                                                data-id-vehicul="<?php echo htmlspecialchars($cursa['id_vehicul']); ?>"
                                                data-id-sofer="<?php echo htmlspecialchars($cursa['id_sofer']); ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($cursa['tip_vehicul']); ?>"
                                                data-status="<?php echo htmlspecialchars($cursa['status']); ?>"
                                                data-data-inceput="<?php echo htmlspecialchars($cursa['data_inceput']); ?>"
                                                data-data-sfarsit="<?php echo htmlspecialchars($cursa['data_sfarsit'] ?? ''); ?>"
                                                data-locatie-plecare="<?php echo htmlspecialchars($cursa['locatie_plecare'] ?? ''); ?>"
                                                data-locatie-destinatie="<?php echo htmlspecialchars($cursa['locatie_destinatie'] ?? ''); ?>"
                                                data-kilometraj-parcurs="<?php echo htmlspecialchars($cursa['kilometraj_parcurs'] ?? ''); ?>"
                                                data-observatii="<?php echo htmlspecialchars($cursa['observatii'] ?? ''); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($cursa['numar_inmatriculare'] . ' ' . $cursa['model'] . ' ' . $cursa['tip_vehicul'] . ' ' . $cursa['nume_sofer'] . ' ' . $cursa['prenume_sofer'] . ' ' . $cursa['locatie_plecare'] . ' ' . $cursa['locatie_destinatie'] . ' ' . $cursa['observatii'] . ' ' . $cursa['status'])); ?>"
                                            >
                                                <td data-label="ID:">
                                                    <span class="status-indicator <?php echo $indicator_class; ?>"></span><?php echo htmlspecialchars($cursa['id']); ?>
                                                </td>
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($cursa['model'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($cursa['numar_inmatriculare'] ?? 'N/A'); ?>)</td>
                                                <td data-label="Tip Vehicul:"><?php echo htmlspecialchars($cursa['tip_vehicul'] ?? 'N/A'); ?></td>
                                                <td data-label="Șofer:"><?php echo htmlspecialchars($cursa['nume_sofer'] ?? 'N/A'); ?> <?php echo htmlspecialchars($cursa['prenume_sofer'] ?? ''); ?></td>
                                                <td data-label="Dată Început:"><?php echo (new DateTime($cursa['data_inceput']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Dată Sfârșit:"><?php echo $cursa['data_sfarsit'] ? (new DateTime($cursa['data_sfarsit']))->format('d.m.Y H:i') : 'N/A'; ?></td>
                                                <td data-label="Locație Plecare:"><?php echo htmlspecialchars($cursa['locatie_plecare'] ?? 'N/A'); ?></td>
                                                <td data-label="Locație Destinație:"><?php echo htmlspecialchars($cursa['locatie_destinatie'] ?? 'N/A'); ?></td>
                                                <td data-label="Status:"><span class="badge <?php echo $cursa_status_class; ?>"><?php echo htmlspecialchars($cursa['status']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info me-2 view-details-btn" data-id="<?php echo $cursa['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewDetailsModal"><i class="bx bx-show"></i> Detalii</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-2 edit-cursa-btn" data-id="<?php echo $cursa['id']; ?>" data-bs-toggle="modal" data-bs-target="#addEditCursaModal"><i class="bx bx-edit"></i> Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-cursa-btn" data-id="<?php echo $cursa['id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteCursaModal"><i class="bx bx-trash"></i> Șterge</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4 d-flex flex-wrap align-items-center justify-content-end">
                            <span class="text-muted me-3">Legendă Statusuri:</span>
                            <div class="legend-item"><span class="legend-color-box status-în_desfășurare"></span> În desfășurare</div>
                            <div class="legend-item"><span class="legend-color-box status-programată"></span> Programată</div>
                            <div class="legend-item"><span class="legend-color-box status-finalizată"></span> Finalizată</div>
                            <div class="legend-item"><span class="legend-color-box status-anulată"></span> Anulată</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modale -->

<!-- Modal Adaugă/Editează Cursă -->
<div class="modal fade" id="addEditCursaModal" tabindex="-1" aria-labelledby="addEditCursaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditCursaModalLabel">Adaugă Cursă Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addEditCursaForm" action="process_curse_active.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="cursaAction" value="add">
                    <input type="hidden" name="id" id="cursaId">
                    
                    <div class="mb-3">
                        <label for="idVehicul" class="form-label">Vehicul:</label>
                        <select id="idVehicul" name="id_vehicul" class="form-select" required>
                            <option value="">Alege Vehicul...</option>
                            <?php foreach ($vehicule_list as $veh): ?>
                                <option value="<?php echo htmlspecialchars($veh['id']); ?>"><?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="idSofer" class="form-label">Șofer:</label>
                        <select id="idSofer" name="id_sofer" class="form-select" required>
                            <option value="">Alege Șofer...</option>
                            <?php foreach ($soferi_list as $sofer): ?>
                                <option value="<?php echo htmlspecialchars($sofer['id']); ?>"><?php echo htmlspecialchars($sofer['nume']); ?> <?php echo htmlspecialchars($sofer['prenume']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dataInceput" class="form-label">Dată Început:</label>
                        <input type="datetime-local" class="form-control" id="dataInceput" name="data_inceput" required>
                    </div>
                    <div class="mb-3">
                        <label for="dataSfarsit" class="form-label">Dată Sfârșit (Estimat/Final):</label>
                        <input type="datetime-local" class="form-control" id="dataSfarsit" name="data_sfarsit">
                    </div>
                    <div class="mb-3">
                        <label for="locatiePlecare" class="form-label">Locație Plecare:</label>
                        <input type="text" class="form-control" id="locatiePlecare" name="locatie_plecare" placeholder="Ex: București" required>
                    </div>
                    <div class="mb-3">
                        <label for="locatieDestinatie" class="form-label">Locație Destinație:</label>
                        <input type="text" class="form-control" id="locatieDestinatie" name="locatie_destinatie" placeholder="Ex: Cluj-Napoca" required>
                    </div>
                    <div class="mb-3" id="kilometrajParcursGroup">
                        <label for="kilometrajParcurs" class="form-label">Kilometraj Parcurs (la finalizare):</label>
                        <input type="number" class="form-control" id="kilometrajParcurs" name="kilometraj_parcurs" min="0" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label for="observatii" class="form-label">Observații:</label>
                        <textarea class="form-control" id="observatii" name="observatii" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="statusCursa" class="form-label">Status Cursă:</label>
                        <select id="statusCursa" name="status" class="form-select" required>
                            <option value="În desfășurare">În desfășurare</option>
                            <option value="Programată">Programată</option>
                            <option value="Finalizată">Finalizată</option>
                            <option value="Anulată">Anulată</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Cursă</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Vizualizare Detalii Cursă -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDetailsModalLabel">Detalii Cursă: <span id="viewCursaIdDisplay"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Vehicul:</strong> <span id="viewCursaVehicle"></span></p>
                <p><strong>Șofer:</strong> <span id="viewCursaSofer"></span></p>
                <p><strong>Dată Început:</strong> <span id="viewCursaDataInceput"></span></p>
                <p><strong>Dată Sfârșit:</strong> <span id="viewCursaDataSfarsit"></span></p>
                <p><strong>Locație Plecare:</strong> <span id="viewCursaLocatiePlecare"></span></p>
                <p><strong>Locație Destinație:</strong> <span id="viewCursaLocatieDestinatie"></span></p>
                <p><strong>Kilometraj Parcurs:</strong> <span id="viewCursaKmParcurs"></span></p>
                <p><strong>Status:</strong> <span id="viewCursaStatus" class="badge"></span></p>
                <hr>
                <h6>Observații:</h6>
                <p id="viewCursaObservatii"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Finalizare Cursă (Acum se declanșează din modalul de editare status) -->
<div class="modal fade" id="completeCursaModal" tabindex="-1" aria-labelledby="completeCursaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeCursaModalLabel">Finalizează Cursa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeCursaForm" action="process_curse_active.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="id" id="completeCursaId">
                    <p>Ești sigur că vrei să finalizezi cursa pentru vehiculul <strong id="completeCursaVehicul"></strong>?</p>
                    <div class="mb-3">
                        <label for="finalKm" class="form-label">Kilometraj Final:</label>
                        <input type="number" class="form-control" id="finalKm" name="kilometraj_parcurs" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="finalDate" class="form-label">Dată și Oră Finalizare:</label>
                        <input type="datetime-local" class="form-control" id="finalDate" name="data_sfarsit" required>
                    </div>
                    <div class="mb-3">
                        <label for="finalObservatii" class="form-label">Observații Finale (Opțional):</label>
                        <textarea class="form-control" id="finalObservatii" name="observatii" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success">Finalizează Cursa</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Confirmă Anulare Cursă (Acum se declanșează din modalul de editare status) -->
<div class="modal fade" id="cancelCursaModal" tabindex="-1" aria-labelledby="cancelCursaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelCursaModalLabel">Confirmă Anularea Cursei</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să anulezi cursa pentru vehiculul <strong id="cancelCursaVehicul"></strong>? Această acțiune va schimba statusul cursei la "Anulată".
                <input type="hidden" id="cancelCursaIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-warning" id="confirmCancelCursaBtn">Anulează Cursa</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Confirmă Ștergere Cursă -->
<div class="modal fade" id="deleteCursaModal" tabindex="-1" aria-labelledby="deleteCursaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCursaModalLabel">Confirmă Ștergerea Cursei</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi cursa pentru vehiculul <strong id="deleteCursaVehicul"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteCursaIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCursaBtn">Șterge</button>
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
    const allCurseData = <?php echo json_encode($curse_all); ?>; 
    const curseMap = {};
    allCurseData.forEach(cursa => {
        curseMap[cursa.id] = cursa;
    });

    // --- Elemente DOM pentru Filtrare ---
    const searchInput = document.getElementById('searchInput');
    const filterVehicle = document.getElementById('filterVehicle');
    const filterTipVehicul = document.getElementById('filterTipVehicul');
    const filterSofer = document.getElementById('filterSofer');
    const filterStatus = document.getElementById('filterStatus');
    const filterDateStart = document.getElementById('filterDateStart');
    const filterDateEnd = document.getElementById('filterDateEnd');
    const activeCurseTableBody = document.getElementById('activeCurseTableBody');

    function filterCurseTable() {
        const searchKeywords = searchInput.value.toLowerCase().trim();
        const selectedVehicleId = filterVehicle.value;
        const selectedTipVehicul = filterTipVehicul.value;
        const selectedSoferId = filterSofer.value;
        const selectedStatus = filterStatus.value;
        const startDate = filterDateStart.value ? new Date(filterDateStart.value) : null;
        const endDate = filterDateEnd.value ? new Date(filterDateEnd.value) : null;

        document.querySelectorAll('#activeCurseTableBody tr').forEach(row => {
            const cursaId = row.getAttribute('data-id');
            const cursa = curseMap[cursaId]; 
            if (!cursa) return;

            const vehicleMatch = (selectedVehicleId === 'all' || cursa.id_vehicul == selectedVehicleId);
            const tipVehiculMatch = (selectedTipVehicul === 'all' || cursa.tip_vehicul === selectedTipVehicul);
            const soferMatch = (selectedSoferId === 'all' || cursa.id_sofer == selectedSoferId);
            const statusMatch = (selectedStatus === 'all' || cursa.status === selectedStatus);
            
            const cursaStartDate = new Date(cursa.data_inceput);
            const cursaEndDate = cursa.data_sfarsit ? new Date(cursa.data_sfarsit) : null;

            const dateStartMatch = (!startDate || cursaStartDate >= startDate);
            const dateEndMatch = (!endDate || (cursaEndDate && cursaEndDate <= endDate));

            const textSearchMatch = (searchKeywords === '' || cursa.locatie_plecare.toLowerCase().includes(searchKeywords) ||
                                     cursa.locatie_destinatie.toLowerCase().includes(searchKeywords) ||
                                     (cursa.observatii && cursa.observatii.toLowerCase().includes(searchKeywords)) ||
                                     cursa.numar_inmatriculare.toLowerCase().includes(searchKeywords) ||
                                     cursa.model.toLowerCase().includes(searchKeywords) ||
                                     cursa.nume_sofer.toLowerCase().includes(searchKeywords) ||
                                     cursa.prenume_sofer.toLowerCase().includes(searchKeywords));

            if (vehicleMatch && tipVehiculMatch && soferMatch && statusMatch && dateStartMatch && dateEndMatch && textSearchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterCurseTable);
    filterVehicle.addEventListener('change', filterCurseTable);
    filterTipVehicul.addEventListener('change', filterCurseTable);
    filterSofer.addEventListener('change', filterCurseTable);
    filterStatus.addEventListener('change', filterCurseTable);
    filterDateStart.addEventListener('change', filterDateStart);
    filterDateEnd.addEventListener('change', filterDateEnd);
    filterCurseTable(); // Rulează la încărcarea paginii

    // --- Logică Modale (Adăugare / Editare / Vizualizare / Finalizare / Anulare / Ștergere) ---

    // Modal Adaugă/Editează Cursă
    const addEditCursaModal = document.getElementById('addEditCursaModal');
    const addEditCursaForm = document.getElementById('addEditCursaForm');
    const cursaActionInput = document.getElementById('cursaAction');
    const cursaIdInput = document.getElementById('cursaId');
    const addEditCursaModalLabel = document.getElementById('addEditCursaModalLabel');

    const idVehiculInput = document.getElementById('idVehicul');
    const idSoferInput = document.getElementById('idSofer');
    const dataInceputInput = document.getElementById('dataInceput');
    const dataSfarsitInput = document.getElementById('dataSfarsit');
    const locatiePlecareInput = document.getElementById('locatiePlecare');
    const locatieDestinatieInput = document.getElementById('locatieDestinatie');
    const kilometrajParcursGroup = document.getElementById('kilometrajParcursGroup'); // Grup pentru a ascunde/afișa
    const kilometrajParcursInput = document.getElementById('kilometrajParcurs');
    const observatiiInput = document.getElementById('observatii');
    const statusCursaInput = document.getElementById('statusCursa');

    // Deschide modalul pentru adăugare cursă nouă
    document.getElementById('addNewCursaBtn').addEventListener('click', function() {
        addEditCursaForm.reset();
        cursaActionInput.value = 'add';
        cursaIdInput.value = '';
        addEditCursaModalLabel.textContent = 'Adaugă Cursă Nouă';
        // Setează data de început implicit la data/ora curentă
        dataInceputInput.value = new Date().toISOString().slice(0, 16);
        // Ascunde kilometrajul parcurs și setează la 0
        kilometrajParcursGroup.style.display = 'none';
        kilometrajParcursInput.value = '0';
        // Setează statusul implicit la "În desfășurare"
        statusCursaInput.value = 'În desfășurare';
        statusCursaInput.disabled = false; // Asigură că este activ la adăugare
    });

    // Deschide modalul pentru editare cursă
    document.querySelectorAll('.edit-cursa-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const cursa = curseMap[id];
            if (cursa) {
                cursaActionInput.value = 'edit';
                cursaIdInput.value = cursa.id;
                addEditCursaModalLabel.textContent = `Editează Cursă #${cursa.id}`;

                idVehiculInput.value = cursa.id_vehicul;
                idSoferInput.value = cursa.id_sofer;
                dataInceputInput.value = cursa.data_inceput ? new Date(cursa.data_inceput).toISOString().slice(0, 16) : '';
                dataSfarsitInput.value = cursa.data_sfarsit ? new Date(cursa.data_sfarsit).toISOString().slice(0, 16) : '';
                locatiePlecareInput.value = cursa.locatie_plecare || '';
                locatieDestinatieInput.value = cursa.locatie_destinatie || '';
                kilometrajParcursInput.value = cursa.kilometraj_parcurs || '0';
                observatiiInput.value = cursa.observatii || '';
                statusCursaInput.value = cursa.status;

                // Afișează kilometrajul parcurs la editare
                kilometrajParcursGroup.style.display = 'block';
                statusCursaInput.disabled = false; // Poate fi modificat la editare
                
                new bootstrap.Modal(addEditCursaModal).show();
            }
        });
    });

    // Trimiterea formularului Adaugă/Editează Cursă
    addEditCursaForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Opreste trimiterea implicita a formularului
        // Dezactivează butonul de submit pentru a preveni multiple trimiteri
        const submitButton = event.submitter;
        submitButton.disabled = true;
        submitButton.textContent = 'Se salvează...';

        const formData = new FormData(addEditCursaForm);

        fetch('process_curse_active.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) // Așteptăm text, nu JSON
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(addEditCursaModal);
            if (modalInstance) { modalInstance.hide(); }
            if (data.includes("success")) {
                alert('Cursa a fost salvată cu succes!');
                location.reload(); 
            } else {
                alert('Eroare la salvarea cursei: ' + data);
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea cursei:', error);
            alert('A apărut o eroare la salvarea cursei.');
        })
        .finally(() => {
            // Re-activează butonul de submit
            submitButton.disabled = false;
            submitButton.textContent = 'Salvează Cursă';
        });
    });

    // Modal Vizualizare Detalii Cursă
    const viewDetailsModal = document.getElementById('viewDetailsModal');
    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const cursa = curseMap[id];
            if (cursa) {
                document.getElementById('viewCursaIdDisplay').textContent = cursa.id;
                document.getElementById('viewCursaVehicle').textContent = `${cursa.model || 'N/A'} (${cursa.numar_inmatriculare || 'N/A'})`;
                document.getElementById('viewCursaSofer').textContent = `${cursa.nume_sofer || 'N/A'} ${cursa.prenume_sofer || ''}`;
                document.getElementById('viewCursaDataInceput').textContent = new Date(cursa.data_inceput).toLocaleString('ro-RO');
                document.getElementById('viewCursaDataSfarsit').textContent = cursa.data_sfarsit ? new Date(cursa.data_sfarsit).toLocaleString('ro-RO') : 'N/A (Activă)';
                document.getElementById('viewCursaLocatiePlecare').textContent = cursa.locatie_plecare || 'N/A';
                document.getElementById('viewCursaLocatieDestinatie').textContent = cursa.locatie_destinatie || 'N/A';
                document.getElementById('viewCursaKmParcurs').textContent = `${cursa.kilometraj_parcurs} km`;
                
                const statusBadge = document.getElementById('viewCursaStatus');
                statusBadge.textContent = cursa.status;
                statusBadge.className = `badge badge-status-${cursa.status.replace(/ /g, '_')}`;

                document.getElementById('viewCursaObservatii').textContent = cursa.observatii || 'Nu există observații.';
                new bootstrap.Modal(viewDetailsModal).show();
            }
        });
    });

    // Modal Finalizare Cursă (Acum se declanșează din modalul de editare status)
    const completeCursaModal = document.getElementById('completeCursaModal');
    const completeCursaForm = document.getElementById('completeCursaForm');
    const completeCursaIdInput = document.getElementById('completeCursaId');
    const completeCursaVehiculDisplay = document.getElementById('completeCursaVehicul');
    const finalKmInput = document.getElementById('finalKm');
    const finalDateInput = document.getElementById('finalDate');
    const finalObservatiiInput = document.getElementById('finalObservatii');

    // Butonul Finalizează nu mai este în tabel, dar logica modalului rămâne
    // pentru a fi apelată din editarea statusului.
    // document.querySelectorAll('.complete-cursa-btn').forEach(button => { /* ... */ });

    completeCursaForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(completeCursaForm);
        formData.append('action', 'complete'); // La finalizare, statusul va fi "Finalizată"
        
        // Asigură că data_inceput, locatie_plecare, locatie_destinatie sunt trimise
        // Preluăm datele originale ale cursei
        const cursaOriginala = curseMap[formData.get('id')];
        if (cursaOriginala) {
            formData.append('id_vehicul', cursaOriginala.id_vehicul);
            formData.append('id_sofer', cursaOriginala.id_sofer);
            formData.append('data_inceput', cursaOriginala.data_inceput);
            formData.append('locatie_plecare', cursaOriginala.locatie_plecare);
            formData.append('locatie_destinatie', cursaOriginala.locatie_destinatie);
        }

        fetch('process_curse_active.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(completeCursaModal);
            if (modalInstance) { modalInstance.hide(); }
            if (data.includes("success")) {
                alert('Cursa a fost finalizată cu succes!');
                location.reload(); 
            } else {
                alert('Eroare la finalizarea cursei: ' + data);
            }
        })
        .catch(error => {
            console.error('Eroare la finalizarea cursei:', error);
            alert('A apărut o eroare la finalizarea cursei.');
        });
    });

    // Buton Anulează Cursă (Acum se declanșează din modalul de editare status)
    const cancelCursaModal = document.getElementById('cancelCursaModal');
    const cancelCursaIdConfirm = document.getElementById('cancelCursaIdConfirm');
    const cancelCursaVehiculDisplay = document.getElementById('cancelCursaVehicul');
    const confirmCancelCursaBtn = document.getElementById('confirmCancelCursaBtn');

    // Butonul Anulează nu mai este în tabel, dar logica modalului rămâne
    // pentru a fi apelată din editarea statusului.
    // document.querySelectorAll('.cancel-cursa-btn').forEach(button => { /* ... */ });

    confirmCancelCursaBtn.addEventListener('click', function() {
        const id = document.getElementById('cancelCursaIdConfirm').value;
        const cursa = curseMap[id]; // Preluăm datele originale
        if (cursa) {
            const formData = new FormData();
            formData.append('action', 'cancel'); // Acțiune de anulare
            formData.append('id', id);
            // Trimitem toate datele originale pentru a face un UPDATE valid
            formData.append('id_vehicul', cursa.id_vehicul);
            formData.append('id_sofer', cursa.id_sofer);
            formData.append('data_inceput', cursa.data_inceput);
            formData.append('data_sfarsit', cursa.data_sfarsit || ''); // Trimite gol dacă e null
            formData.append('locatie_plecare', cursa.locatie_plecare);
            formData.append('locatie_destinatie', cursa.locatie_destinatie);
            formData.append('kilometraj_parcurs', cursa.kilometraj_parcurs);
            formData.append('observatii', cursa.observatii || '');
            formData.append('status', 'Anulată'); // Setează statusul la Anulată

            fetch('process_curse_active.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data);
                const modalInstance = bootstrap.Modal.getInstance(cancelCursaModal);
                if (modalInstance) { modalInstance.hide(); }
                if (data.includes("success")) {
                    alert('Cursa a fost anulată cu succes!');
                    location.reload(); 
                } else {
                    alert('Eroare la anularea cursei: ' + data);
                }
            })
            .catch(error => {
                console.error('Eroare la anularea cursei:', error);
                alert('A apărut o eroare la anularea cursei.');
            });
        }
    });


    // Modal Confirmă Ștergere Cursă (Păstrăm pentru consistență, chiar dacă butonul e scos din UI)
    const deleteCursaModal = document.getElementById('deleteCursaModal');
    const deleteCursaIdConfirm = document.getElementById('deleteCursaIdConfirm');
    const deleteCursaVehiculDisplay = document.getElementById('deleteCursaVehicul');
    const deleteCursaSoferDisplay = document.getElementById('deleteCursaSofer'); 
    const confirmDeleteCursaBtn = document.getElementById('confirmDeleteCursaBtn');

    // Acest selector va găsi butoane în UI (dacă sunt adăugate manual)
    document.querySelectorAll('.delete-cursa-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const cursa = curseMap[id];
            if (cursa) {
                deleteCursaIdConfirm.value = cursa.id;
                deleteCursaVehiculDisplay.textContent = `${cursa.model} (${cursa.numar_inmatriculare})`;
                deleteCursaSoferDisplay.textContent = `${cursa.nume_sofer} ${cursa.prenume_sofer}`;
                new bootstrap.Modal(deleteCursaModal).show();
            }
        });
    });

    confirmDeleteCursaBtn.addEventListener('click', function() {
        const cursaIdToDelete = document.getElementById('deleteCursaIdConfirm').value;
        if (cursaIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_cursa'); // Acțiune explicită pentru ștergere cursă
            formData.append('id', cursaIdToDelete);

            fetch('process_curse_active.php', { // Presupunem că acest script gestionează ștergerea
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) // Așteptăm text, nu JSON
            .then(data => {
                console.log(data);
                const modalInstance = bootstrap.Modal.getInstance(deleteCursaModal);
                if (modalInstance) { modalInstance.hide(); }
                if (data.includes("success")) {
                    alert('Cursa a fost ștearsă cu succes!');
                    location.reload(); 
                } else {
                    alert('Eroare la ștergerea cursei: ' + data);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea cursei:', error);
                alert('A apărut o eroare la ștergerea cursei.');
            });
        }
    });

    // --- Funcționalitate Export (PDF, Excel, Print) ---
    function exportTableToPDF(tableId, title) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const headers = [];
        document.querySelectorAll(`#${tableId} thead th`).forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
            if (row.style.display !== 'none') { // Doar rândurile vizibile
                const rowData = [];
                // Excludem coloana de Acțiuni (ultima coloană)
                row.querySelectorAll('td:not(:last-child)').forEach(td => {
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

        doc.save(`${title.replace(/ /g, '_')}.pdf`);
    }

    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        exportTableToPDF('activeCurseTable', 'Curse Active si Programate');
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('activeCurseTable');
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
        XLSX.utils.book_append_sheet(wb, ws, "Curse Active si Programate");
        XLSX.writeFile(wb, `Curse_Active_si_Programate.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('activeCurseTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Curse Active și Programate</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Curse Active și Programate</h1>
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
            // Elimină manual backdrop-ul dacă persistă
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.parentNode.removeChild(backdrop);
            }
        });
    });
});
</script>
