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

// Funcție pentru a verifica existența unei coloane într-un tabel
function columnExists($conn, $tableName, $columnName) {
    if (!tableExists($conn, $tableName)) {
        return false;
    }
    $result = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($tableName) . "` LIKE '" . $conn->real_escape_string($columnName) . "'");
    return $result && $result->num_rows > 0;
}

// --- Preluare Date pentru Dropdown-uri și Tabele ---

// Preluăm tipurile de vehicule existente din baza de date pentru dropdown
$tipuri_vehicul_db = [];
if (tableExists($conn, 'vehicule')) {
    $sql_tipuri_db = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
    $result_tipuri_db = $conn->query($sql_tipuri_db);
    if ($result_tipuri_db) {
        while ($row = $result_tipuri_db->fetch_assoc()) {
            $tipuri_vehicul_db[] = $row['tip'];
        }
    }
}

// Tipuri de vehicule predefinite din domeniul transporturilor (lista extinsă)
$tipuri_vehicul_predefinite = [
    'Autoturism', 'Autoutilitară', 'Camion', 'Autocar', 'Microbuz', 'Minibus (8+1)',
    'Camion (Rigid)', 'Camion (Articulat)', 'Furgonetă', 'Trailer (Semiremorcă)',
    'Remorcă', 'Mașină de Intervenție', 'Platformă Auto', 'Basculantă', 'Cisternă',
    'Frigorifică', 'Container', 'Duba', 'Autotren', 'Cap Tractor',
    'Semiremorcă Frigorifică', 'Semiremorcă Prelată', 'Semiremorcă Cisternă',
    'Semiremorcă Basculantă', 'Autospecială', 'Vehicul Electric', 'Vehicul Hibrid', 'Altele'
];

// Combinăm tipurile din DB cu cele predefinite și eliminăm duplicatele
$tipuri_vehicul_finale = array_unique(array_merge($tipuri_vehicul_db, $tipuri_vehicul_predefinite));
sort($tipuri_vehicul_finale); // Sortează alfabetic


// Preluăm lista de vehicule pentru dropdown-uri de filtrare și adăugare
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
    // Date mock pentru vehicule
    $vehicule_list = [
        ['id' => 1, 'model' => 'Mercedes Actros', 'numar_inmatriculare' => 'B 10 ABC', 'tip' => 'Camion'],
        ['id' => 2, 'model' => 'Ford Transit', 'numar_inmatriculare' => 'B 20 DEF', 'tip' => 'Autoutilitară'],
        ['id' => 3, 'model' => 'Irisbus Magelys', 'numar_inmatriculare' => 'B 30 GHI', 'tip' => 'Autocar'],
        ['id' => 4, 'model' => 'Mercedes Sprinter', 'numar_inmatriculare' => 'B 40 JKL', 'tip' => 'Microbuz'],
    ];
}

// Preluăm lista de șoferi pentru dropdown-uri
$soferi_list = [];
if (tableExists($conn, 'angajati') && columnExists($conn, 'angajati', 'nume') && columnExists($conn, 'angajati', 'prenume')) {
    $sql_soferi = "SELECT id, nume, prenume FROM angajati WHERE functie = 'Sofer' ORDER BY nume ASC, prenume ASC";
    $result_soferi = $conn->query($sql_soferi);
    if ($result_soferi) {
        while ($row = $result_soferi->fetch_assoc()) {
            $soferi_list[] = $row;
        }
    }
} else {
    // Date mock pentru șoferi
    $soferi_list = [
        ['id' => 1, 'nume' => 'Popescu', 'prenume' => 'Ion'],
        ['id' => 2, 'nume' => 'Georgescu', 'prenume' => 'Maria'],
        ['id' => 3, 'nume' => 'Vasilescu', 'prenume' => 'Andrei'],
    ];
}

// Preluăm alocările existente
$alocari_list = [];
if (tableExists($conn, 'alocari_vehicule_soferi') && 
    columnExists($conn, 'alocari_vehicule_soferi', 'id_vehicul') &&
    columnExists($conn, 'alocari_vehicule_soferi', 'id_sofer') &&
    columnExists($conn, 'alocari_vehicule_soferi', 'data_inceput') &&
    columnExists($conn, 'alocari_vehicule_soferi', 'data_sfarsit')) { // Verifică și data_sfarsit

    $sql_alocari = "
        SELECT 
            avs.id, 
            avs.id_vehicul, 
            avs.id_sofer, 
            avs.data_inceput, 
            avs.data_sfarsit, 
            v.numar_inmatriculare, 
            v.model, 
            v.tip as tip_vehicul,
            a.nume as nume_sofer, 
            a.prenume as prenume_sofer
        FROM 
            alocari_vehicule_soferi avs
        LEFT JOIN 
            vehicule v ON avs.id_vehicul = v.id
        LEFT JOIN 
            angajati a ON avs.id_sofer = a.id
        ORDER BY 
            avs.data_inceput DESC
    ";
    $result_alocari = $conn->query($sql_alocari);
    if ($result_alocari) {
        while ($row = $result_alocari->fetch_assoc()) {
            $alocari_list[] = $row;
        }
    } else {
        $error_message .= "Eroare la preluarea alocărilor: " . $conn->error;
    }
} else {
    $error_message .= "Tabelul 'alocari_vehicule_soferi' sau una dintre coloanele necesare (id_vehicul, id_sofer, data_inceput, data_sfarsit) lipsește. ";
    // Date mock pentru alocări
    $alocari_list = [
        ['id' => 1, 'id_vehicul' => 1, 'id_sofer' => 1, 'data_inceput' => '2025-07-01 08:00:00', 'data_sfarsit' => '2025-07-05 17:00:00', 'numar_inmatriculare' => 'B 10 ABC', 'model' => 'Mercedes Actros', 'tip_vehicul' => 'Camion', 'nume_sofer' => 'Popescu', 'prenume_sofer' => 'Ion'],
        ['id' => 2, 'id_vehicul' => 2, 'id_sofer' => 2, 'data_inceput' => '2025-07-03 09:00:00', 'data_sfarsit' => null, 'numar_inmatriculare' => 'B 20 DEF', 'model' => 'Ford Transit', 'tip_vehicul' => 'Autoutilitară', 'nume_sofer' => 'Georgescu', 'prenume_sofer' => 'Maria'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Alocare Vehicul Șofer</title>

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
        padding: 0.3rem 0.6rem !important;
        font-size: 0.8rem !important;
        width: auto !important;
    }
    .table .badge {
        padding: 0.4em 0.7em;
        border-radius: 0.3rem;
        font-size: 0.85em;
        font-weight: 600;
    }
    .badge-status-Activa { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-Finalizata { background-color: #6c757d !important; color: #fff !important; }
    .badge-status-Anulata { background-color: #dc3545 !important; color: #fff !important; }
    .badge-status-Programata { background-color: #007bff !important; color: #fff !important; } /* Nou status */

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
            <div class="breadcrumb-title pe-3">Alocare Vehicul Șofer</div>
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
                        <h4 class="card-title">Alocare Vehicul către Șofer</h4>
                        <p class="text-muted">Alocă vehicule șoferilor și gestionează alocările existente.</p>
                        <hr>

                        <!-- Formular Adaugă Alocare -->
                        <h5 class="mb-3">Adaugă Alocare Nouă</h5>
                        <form id="addAlocareForm" class="row g-3 mb-4" action="process_alocari.php" method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="col-md-6">
                                <label for="selectVehicle" class="form-label">Vehicul:</label>
                                <select id="selectVehicle" name="id_vehicul" class="form-select" required>
                                    <option value="">Alege Vehicul...</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo htmlspecialchars($veh['id']); ?>" data-model="<?php echo htmlspecialchars($veh['model']); ?>" data-numar="<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>">
                                            <?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="selectSofer" class="form-label">Șofer:</label>
                                <select id="selectSofer" name="id_sofer" class="form-select" required>
                                    <option value="">Alege Șofer...</option>
                                    <?php foreach ($soferi_list as $sofer): ?>
                                        <option value="<?php echo htmlspecialchars($sofer['id']); ?>" data-nume="<?php echo htmlspecialchars($sofer['nume']); ?>" data-prenume="<?php echo htmlspecialchars($sofer['prenume']); ?>">
                                            <?php echo htmlspecialchars($sofer['nume']); ?> <?php echo htmlspecialchars($sofer['prenume']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="dataStart" class="form-label">Dată Început:</label>
                                <input type="datetime-local" class="form-control" id="dataStart" name="data_inceput" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dataEnd" class="form-label">Dată Sfârșit (Opțional):</label>
                                <input type="datetime-local" class="form-control" id="dataEnd" name="data_sfarsit">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="permanentAllocation">
                                    <label class="form-check-label" for="permanentAllocation">
                                        Alocare Permanentă (fără dată de sfârșit)
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Adaugă Alocare</button>
                            </div>
                        </form>

                        <hr class="mt-5 mb-4">
                        <h4 class="card-title">Alocări Existente</h4>
                        <p class="text-muted">Filtrează și gestionează alocările curente și istorice.</p>
                        <hr>

                        <!-- Filtre pentru alocări existente -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="filterVehicleAlloc" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicleAlloc">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo htmlspecialchars($veh['id']); ?>"><?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterDriverAlloc" class="form-label">Filtrează după Șofer:</label>
                                <select class="form-select" id="filterDriverAlloc">
                                    <option value="all">Toți Șoferii</option>
                                    <?php foreach ($soferi_list as $sofer): ?>
                                        <option value="<?php echo htmlspecialchars($sofer['id']); ?>"><?php echo htmlspecialchars($sofer['nume']); ?> <?php echo htmlspecialchars($sofer['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatusAlloc" class="form-label">Filtrează după Status Alocare:</label>
                                <select class="form-select" id="filterStatusAlloc">
                                    <option value="all">Toate Statusurile</option>
                                    <option value="Activa">Activă</option>
                                    <option value="Finalizata">Finalizată</option>
                                    <option value="Anulata">Anulată</option>
                                    <option value="Programata">Programată</option>
                                </select>
                            </div>
                        </div>

                        <!-- Butoane de Export -->
                        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel me-2"></i>Export Excel</button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="bx bxs-file-pdf me-2"></i>Export PDF</button>
                            <button type="button" class="btn btn-info" id="printListBtn"><i class="bx bx-printer me-2"></i>Printează</button>
                        </div>

                        <!-- Tabelul cu Alocări -->
                        <?php if (empty($alocari_list)): ?>
                            <div class="alert alert-info">Nu există alocări vehicul-șofer înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="alocariTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Vehicul</th>
                                            <th>Șofer</th>
                                            <th>Dată Început</th>
                                            <th>Dată Sfârșit</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="alocariTableBody">
                                        <?php foreach ($alocari_list as $alocare): 
                                            $alocare_status = 'Activa';
                                            $current_datetime = new DateTime();
                                            $data_inceput_dt = new DateTime($alocare['data_inceput']);
                                            $data_sfarsit_dt = $alocare['data_sfarsit'] ? new DateTime($alocare['data_sfarsit']) : null;

                                            if ($data_sfarsit_dt && $data_sfarsit_dt < $current_datetime) {
                                                $alocare_status = 'Finalizata';
                                            } elseif ($data_inceput_dt > $current_datetime) {
                                                $alocare_status = 'Programata';
                                            } else {
                                                $alocare_status = 'Activa';
                                            }
                                            // Puteți adăuga un câmp 'status' în tabelul alocari_vehicule_soferi pentru a stoca 'Anulata'
                                            // Pentru moment, folosim doar Activa/Finalizata/Programata
                                            $alocare_status_class = 'badge-status-' . str_replace(' ', '_', $alocare_status);
                                        ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($alocare['id']); ?>"
                                                data-id-vehicul="<?php echo htmlspecialchars($alocare['id_vehicul']); ?>"
                                                data-id-sofer="<?php echo htmlspecialchars($alocare['id_sofer']); ?>"
                                                data-status="<?php echo htmlspecialchars($alocare_status); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($alocare['numar_inmatriculare'] . ' ' . $alocare['model'] . ' ' . $alocare['nume_sofer'] . ' ' . $alocare['prenume_sofer'] . ' ' . $alocare_status)); ?>"
                                            >
                                                <td data-label="ID:"><?php echo htmlspecialchars($alocare['id']); ?></td>
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($alocare['model'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($alocare['numar_inmatriculare'] ?? 'N/A'); ?>)</td>
                                                <td data-label="Șofer:"><?php echo htmlspecialchars($alocare['nume_sofer'] ?? 'N/A'); ?> <?php echo htmlspecialchars($alocare['prenume_sofer'] ?? ''); ?></td>
                                                <td data-label="Dată Început:"><?php echo (new DateTime($alocare['data_inceput']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Dată Sfârșit:"><?php echo $alocare['data_sfarsit'] ? (new DateTime($alocare['data_sfarsit']))->format('d.m.Y H:i') : 'Permanentă'; ?></td>
                                                <td data-label="Status:"><span class="badge <?php echo $alocare_status_class; ?>"><?php echo htmlspecialchars($alocare_status); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-2 edit-alocare-btn" data-id="<?php echo $alocare['id']; ?>" data-bs-toggle="modal" data-bs-target="#editAlocareModal"><i class="bx bx-edit"></i> Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-alocare-btn" data-id="<?php echo $alocare['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
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

<!-- Modal Adaugă/Editează Alocare (Reutilizăm pentru editare) -->
<div class="modal fade" id="editAlocareModal" tabindex="-1" aria-labelledby="editAlocareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAlocareModalLabel">Editează Alocare Vehicul-Șofer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAlocareForm" action="process_alocari.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editAlocareId">
                    <div class="mb-3">
                        <label for="editSelectVehicle" class="form-label">Vehicul:</label>
                        <select id="editSelectVehicle" name="id_vehicul" class="form-select" required>
                            <option value="">Alege Vehicul...</option>
                            <?php foreach ($vehicule_list as $veh): ?>
                                <option value="<?php echo htmlspecialchars($veh['id']); ?>"><?php echo htmlspecialchars($veh['model']); ?> (<?php echo htmlspecialchars($veh['numar_inmatriculare']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editSelectSofer" class="form-label">Șofer:</label>
                        <select id="editSelectSofer" name="id_sofer" class="form-select" required>
                            <option value="">Alege Șofer...</option>
                            <?php foreach ($soferi_list as $sofer): ?>
                                <option value="<?php echo htmlspecialchars($sofer['id']); ?>"><?php echo htmlspecialchars($sofer['nume']); ?> <?php echo htmlspecialchars($sofer['prenume']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editDataStart" class="form-label">Dată Început:</label>
                        <input type="datetime-local" class="form-control" id="editDataStart" name="data_inceput" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDataEnd" class="form-label">Dată Sfârșit (Opțional):</label>
                        <input type="datetime-local" class="form-control" id="editDataEnd" name="data_sfarsit">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="editPermanentAllocation">
                            <label class="form-check-label" for="editPermanentAllocation">
                                Alocare Permanentă (fără dată de sfârșit)
                            </label>
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

<!-- Modal Confirmă Ștergere Alocare -->
<div class="modal fade" id="deleteAlocareModal" tabindex="-1" aria-labelledby="deleteAlocareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAlocareModalLabel">Confirmă Ștergerea Alocării</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi alocarea pentru vehiculul <strong id="deleteAlocareVehicul"></strong> și șoferul <strong id="deleteAlocareSofer"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteAlocareIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAlocareBtn">Șterge</button>
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
    // Datele alocărilor, vehiculelor și șoferilor, populate din PHP
    const allAlocariData = <?php echo json_encode($alocari_list); ?>;
    const allVehiclesData = <?php echo json_encode($vehicule_list); ?>;
    const allSoferiData = <?php echo json_encode($soferi_list); ?>;

    const alocariMap = {};
    allAlocariData.forEach(alocare => {
        alocariMap[alocare.id] = alocare;
    });

    // --- Elemente DOM pentru Filtrare ---
    const filterVehicleAlloc = document.getElementById('filterVehicleAlloc');
    const filterDriverAlloc = document.getElementById('filterDriverAlloc');
    const filterStatusAlloc = document.getElementById('filterStatusAlloc');
    const alocariTableBody = document.getElementById('alocariTableBody');

    function filterAlocariTable() {
        const selectedVehicleId = filterVehicleAlloc.value;
        const selectedDriverId = filterDriverAlloc.value;
        const selectedStatus = filterStatusAlloc.value;

        document.querySelectorAll('#alocariTableBody tr').forEach(row => {
            const rowVehicleId = row.getAttribute('data-id-vehicul');
            const rowDriverId = row.getAttribute('data-id-sofer');
            const rowStatus = row.getAttribute('data-status');
            
            const vehicleMatch = (selectedVehicleId === 'all' || rowVehicleId === selectedVehicleId);
            const driverMatch = (selectedDriverId === 'all' || rowDriverId === selectedDriverId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);

            if (vehicleMatch && driverMatch && statusMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicleAlloc.addEventListener('change', filterAlocariTable);
    filterDriverAlloc.addEventListener('change', filterAlocariTable);
    filterStatusAlloc.addEventListener('change', filterAlocariTable);
    filterAlocariTable(); // Rulează la încărcarea paginii

    // --- Logică Modale (Adăugare / Editare / Ștergere) ---

    // Adaugă Alocare Nouă - Permanent Allocation Checkbox
    const permanentAllocationCheckbox = document.getElementById('permanentAllocation');
    const dataEndInput = document.getElementById('dataEnd');

    permanentAllocationCheckbox.addEventListener('change', function() {
        if (this.checked) {
            dataEndInput.value = ''; // Golim câmpul de dată
            dataEndInput.setAttribute('disabled', 'disabled');
            dataEndInput.removeAttribute('required'); // Eliminăm required
        } else {
            dataEndInput.removeAttribute('disabled');
            dataEndInput.setAttribute('required', 'required'); // Adăugăm required înapoi
        }
    });

    // Modal Editare Alocare - Permanent Allocation Checkbox
    const editPermanentAllocationCheckbox = document.getElementById('editPermanentAllocation');
    const editDataEndInput = document.getElementById('editDataEnd');

    editPermanentAllocationCheckbox.addEventListener('change', function() {
        if (this.checked) {
            editDataEndInput.value = ''; // Golim câmpul de dată
            editDataEndInput.setAttribute('disabled', 'disabled');
            editDataEndInput.removeAttribute('required'); // Eliminăm required
        } else {
            editDataEndInput.removeAttribute('disabled');
            editDataEndInput.setAttribute('required', 'required'); // Adăugăm required înapoi
        }
    });

    // Populează modalul de editare alocare
    const editAlocareModal = document.getElementById('editAlocareModal');
    const editAlocareForm = document.getElementById('editAlocareForm');
    const editAlocareIdInput = document.getElementById('editAlocareId');
    const editSelectVehicle = document.getElementById('editSelectVehicle');
    const editSelectSofer = document.getElementById('editSelectSofer');
    const editDataStart = document.getElementById('editDataStart');
    
    document.querySelectorAll('.edit-alocare-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const alocare = alocariMap[id];
            if (alocare) {
                editAlocareIdInput.value = alocare.id;
                editSelectVehicle.value = alocare.id_vehicul;
                editSelectSofer.value = alocare.id_sofer;
                // Formatarea datelor pentru input type="datetime-local"
                editDataStart.value = alocare.data_inceput ? new Date(alocare.data_inceput).toISOString().slice(0, 16) : '';
                
                if (alocare.data_sfarsit) {
                    editDataEndInput.value = new Date(alocare.data_sfarsit).toISOString().slice(0, 16);
                    editPermanentAllocationCheckbox.checked = false;
                    editDataEndInput.removeAttribute('disabled');
                    editDataEndInput.setAttribute('required', 'required'); // Asigură că e required
                } else {
                    editDataEndInput.value = '';
                    editPermanentAllocationCheckbox.checked = true;
                    editDataEndInput.setAttribute('disabled', 'disabled');
                    editDataEndInput.removeAttribute('required'); // Elimină required
                }
                new bootstrap.Modal(editAlocareModal).show();
            }
        });
    });

    editAlocareForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(editAlocareForm);

        // Dacă alocarea este permanentă, asigură-te că data_sfarsit este null
        if (editPermanentAllocationCheckbox.checked) {
            formData.set('data_sfarsit', ''); // Trimite un string gol, PHP o va converti în NULL
        }

        fetch('process_alocari.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(editAlocareModal);
            if (modalInstance) { modalInstance.hide(); }
            if (data.includes("success")) {
                alert('Alocarea a fost actualizată cu succes!');
                location.reload(); 
            } else {
                alert('Eroare la actualizarea alocării: ' + data);
            }
        })
        .catch(error => {
            console.error('Eroare la actualizarea alocării:', error);
            alert('A apărut o eroare la actualizarea alocării.');
        });
    });

    // Modal Confirmă Ștergere Alocare
    const deleteAlocareModal = document.getElementById('deleteAlocareModal');
    const deleteAlocareIdConfirm = document.getElementById('deleteAlocareIdConfirm');
    const deleteAlocareVehiculDisplay = document.getElementById('deleteAlocareVehicul');
    const deleteAlocareSoferDisplay = document.getElementById('deleteAlocareSofer');
    const confirmDeleteAlocareBtn = document.getElementById('confirmDeleteAlocareBtn');

    document.querySelectorAll('.delete-alocare-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const alocare = alocariMap[id];
            if (alocare) {
                deleteAlocareIdConfirm.value = alocare.id;
                deleteAlocareVehiculDisplay.textContent = `${alocare.model} (${alocare.numar_inmatriculare})`;
                deleteAlocareSoferDisplay.textContent = `${alocare.nume_sofer} ${alocare.prenume_sofer}`;
                new bootstrap.Modal(deleteAlocareModal).show();
            }
        });
    });

    confirmDeleteAlocareBtn.addEventListener('click', function() {
        const alocareIdToDelete = document.getElementById('deleteAlocareIdConfirm').value;
        if (alocareIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', alocareIdToDelete);

            fetch('process_alocari.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data);
                const modalInstance = bootstrap.Modal.getInstance(deleteAlocareModal);
                if (modalInstance) { modalInstance.hide(); }
                if (data.includes("success")) {
                    alert('Alocarea a fost ștearsă cu succes!');
                    location.reload(); 
                } else {
                    alert('Eroare la ștergerea alocării: ' + data);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea alocării:', error);
                alert('A apărut o eroare la ștergerea alocării.');
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
        exportTableToPDF('alocariTable', 'Lista Alocari Vehicule Soferi');
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('alocariTable');
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
        XLSX.utils.book_append_sheet(wb, ws, "Alocari Vehicule Soferi");
        XLSX.writeFile(wb, `Alocari_Vehicule_Soferi.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('alocariTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Alocări Vehicule Șoferi</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Alocări Vehicule Șoferi</h1>
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
        });
    });
});
</script>
