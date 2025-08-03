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

// Preluăm lista de vehicule pentru dropdown-uri de filtrare și adăugare
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare FROM vehicule ORDER BY model ASC, numar_inmatriculare ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

// Preluăm lista de șoferi pentru dropdown-uri de filtrare și adăugare
$soferi_list = [];
$stmt_soferi = $conn->prepare("SELECT id, nume, prenume FROM angajati WHERE functie = 'Sofer' ORDER BY nume ASC, prenume ASC");
if ($stmt_soferi) {
    $stmt_soferi->execute();
    $result_soferi = $stmt_soferi->get_result();
    while ($row = $result_soferi->fetch_assoc()) {
        $soferi_list[] = $row;
    }
    $stmt_soferi->close();
}

// Preluăm lista de rute planificate
$rute_list = [];
$sql_rute = "
    SELECT pr.*, v.model, v.numar_inmatriculare, a.nume as sofer_nume, a.prenume as sofer_prenume
    FROM planificare_rute pr
    JOIN vehicule v ON pr.id_vehicul = v.id
    LEFT JOIN angajati a ON pr.id_sofer = a.id
    ORDER BY pr.data_plecare_estimata DESC
";
$result_rute = $conn->query($sql_rute);
if ($result_rute) {
    while ($row = $result_rute->fetch_assoc()) {
        $rute_list[] = $row;
    }
}
// Conexiunea la baza de date este închisă automat la sfârșitul scriptului principal
// $conn->close(); // Nu închide conexiunea aici, PHP o va închide automat

// Statusuri pentru filtrare
$statusuri_ruta = ['Planificată', 'În desfășurare', 'Finalizată', 'Anulată'];
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Planificare Rute</title>

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

    /* Stiluri specifice pentru tabelul de rute */
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
    /* Badge-uri pentru statusul rutei */
    .badge-status-planificată { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-status-în_desfășurare { background-color: #0d6efd !important; color: #fff !important; }
    .badge-status-finalizată { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-anulată { background-color: #dc3545 !important; color: #fff !important; }

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
            <div class="breadcrumb-title pe-3">Planificare Rute</div>
            <div class="ps-3">
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Rute Planificate</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditRutaModal" id="addRutaBtn">
                            <i class="bx bx-plus"></i> Adaugă Rută Nouă
                        </button>

                        <!-- Secțiunea de Filtrare -->
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
                                <label for="filterSofer" class="form-label">Filtrează după Șofer:</label>
                                <select class="form-select" id="filterSofer">
                                    <option value="all">Toți Șoferii</option>
                                    <?php foreach ($soferi_list as $sofer): ?>
                                        <option value="<?php echo $sofer['id']; ?>"><?php echo htmlspecialchars($sofer['nume'] . ' ' . $sofer['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_ruta as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume rută, locații, observații...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Rutelor -->
                        <?php if (empty($rute_list)): ?>
                            <div class="alert alert-info">Nu există rute planificate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nume Rută</th>
                                            <th>Vehicul</th>
                                            <th>Șofer</th>
                                            <th>Plecare</th>
                                            <th>Sosire</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ruteTableBody">
                                        <?php foreach ($rute_list as $ruta): ?>
                                            <tr 
                                                data-id="<?php echo $ruta['id']; ?>"
                                                data-id-vehicul="<?php echo $ruta['id_vehicul']; ?>"
                                                data-id-sofer="<?php echo htmlspecialchars($ruta['id_sofer'] ?? ''); ?>"
                                                data-nume-ruta="<?php echo htmlspecialchars($ruta['nume_ruta']); ?>"
                                                data-locatie-start="<?php echo htmlspecialchars($ruta['locatie_start']); ?>"
                                                data-locatie-final="<?php echo htmlspecialchars($ruta['locatie_final']); ?>"
                                                data-data-plecare-estimata="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($ruta['data_plecare_estimata']))); ?>"
                                                data-data-sosire-estimata="<?php echo htmlspecialchars($ruta['data_sosire_estimata'] ? date('Y-m-d\TH:i', strtotime($ruta['data_sosire_estimata'])) : ''); ?>"
                                                data-distanta-estimata-km="<?php echo htmlspecialchars($ruta['distanta_estimata_km']); ?>"
                                                data-timp-estimat-ore="<?php echo htmlspecialchars($ruta['timp_estimat_ore']); ?>"
                                                data-status="<?php echo htmlspecialchars($ruta['status']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($ruta['observatii']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($ruta['nume_ruta'] . ' ' . $ruta['locatie_start'] . ' ' . $ruta['locatie_final'] . ' ' . $ruta['model'] . ' ' . $ruta['numar_inmatriculare'] . ' ' . $ruta['sofer_nume'] . ' ' . $ruta['sofer_prenume'] . ' ' . $ruta['observatii'])); ?>"
                                            >
                                                <td data-label="Nume Rută:"><?php echo htmlspecialchars($ruta['nume_ruta']); ?></td>
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($ruta['model'] . ' (' . $ruta['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Șofer:"><?php echo htmlspecialchars($ruta['sofer_nume'] ? $ruta['sofer_nume'] . ' ' . $ruta['sofer_prenume'] : 'N/A'); ?></td>
                                                <td data-label="Plecare:"><?php echo htmlspecialchars(mb_strimwidth($ruta['locatie_start'], 0, 20, "...")); ?></td>
                                                <td data-label="Sosire:"><?php echo htmlspecialchars(mb_strimwidth($ruta['locatie_final'], 0, 20, "...")); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $ruta['status'])); ?>"><?php echo htmlspecialchars($ruta['status']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-ruta-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#addEditRutaModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-ruta-btn w-100" data-id="<?php echo $ruta['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Rută -->
<div class="modal fade" id="addEditRutaModal" tabindex="-1" aria-labelledby="addEditRutaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditRutaModalLabel">Adaugă Rută Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rutaForm" action="process_planificare_rute.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="rutaAction" name="action" value="add">
                    <input type="hidden" id="rutaId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalNumeRuta" class="form-label">Nume Rută:</label>
                            <input type="text" class="form-control" id="modalNumeRuta" name="nume_ruta" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalSelectVehicul" class="form-label">Vehicul:</label>
                            <select class="form-select" id="modalSelectVehicul" name="id_vehicul" required>
                                <option value="">Alege un vehicul</option>
                                <?php foreach ($vehicule_list as $veh): ?>
                                    <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalSelectSofer" class="form-label">Șofer (opțional):</label>
                            <select class="form-select" id="modalSelectSofer" name="id_sofer">
                                <option value="">Fără șofer</option>
                                <?php foreach ($soferi_list as $sofer): ?>
                                    <option value="<?php echo $sofer['id']; ?>"><?php echo htmlspecialchars($sofer['nume'] . ' ' . $sofer['prenume']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalLocatiePlecare" class="form-label">Locație Plecare:</label>
                            <input type="text" class="form-control" id="modalLocatiePlecare" name="locatie_plecare" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalLocatieDestinatie" class="form-label">Locație Destinație:</label>
                            <input type="text" class="form-control" id="modalLocatieDestinatie" name="locatie_final" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataPlecareEstimata" class="form-label">Dată/Ora Plecare Estimată:</label>
                            <input type="datetime-local" class="form-control" id="modalDataPlecareEstimata" name="data_plecare_estimata" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataSosireEstimata" class="form-label">Dată/Ora Sosire Estimată (opțional):</label>
                            <input type="datetime-local" class="form-control" id="modalDataSosireEstimata" name="data_sosire_estimata">
                        </div>
                        <div class="col-md-6">
                            <label for="modalDistantaEstimata" class="form-label">Distanță Estimată (km):</label>
                            <input type="number" step="0.01" class="form-control" id="modalDistantaEstimata" name="distanta_estimata_km">
                        </div>
                        <div class="col-md-6">
                            <label for="modalTimpEstimata" class="form-label">Timp Estimat (ore):</label>
                            <input type="number" step="0.01" class="form-control" id="modalTimpEstimata" name="timp_estimat_ore">
                        </div>
                        <div class="col-12">
                            <label for="modalStatus" class="form-label">Status Rută:</label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_ruta as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Rută</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Rută -->
<div class="modal fade" id="deleteRutaModal" tabindex="-1" aria-labelledby="deleteRutaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRutaModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi ruta <strong id="deleteRutaName"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteRutaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRutaBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditRutaModal = document.getElementById('addEditRutaModal');
    const rutaForm = document.getElementById('rutaForm');
    const addRutaBtn = document.getElementById('addRutaBtn');
    const deleteRutaModal = document.getElementById('deleteRutaModal');
    const confirmDeleteRutaBtn = document.getElementById('confirmDeleteRutaBtn');
    const ruteTableBody = document.getElementById('ruteTableBody');

    // Filtrare
    const filterVehicul = document.getElementById('filterVehicul');
    const filterSofer = document.getElementById('filterSofer');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedSoferId = filterSofer.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#ruteTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowSoferId = row.getAttribute('data-id-sofer');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const soferMatch = (selectedSoferId === 'all' || rowSoferId === selectedSoferId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && soferMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterSofer.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addRutaBtn.addEventListener('click', function() {
        rutaForm.reset();
        document.getElementById('rutaAction').value = 'add';
        document.getElementById('rutaId').value = '';
        document.getElementById('addEditRutaModalLabel').textContent = 'Adaugă Rută Nouă';
        
        // Setează data/ora de plecare la momentul curent
        const now = new Date();
        const formattedNow = now.toISOString().substring(0, 16);
        document.getElementById('modalDataPlecareEstimata').value = formattedNow;
        document.getElementById('modalDataSosireEstimata').value = ''; // Golește estimarea
        document.getElementById('modalDistantaEstimata').value = '';
        document.getElementById('modalTimpEstimata').value = '';
        document.getElementById('modalStatus').value = 'Planificată'; // Status implicit
    });

    // Deschide modalul pentru editare
    ruteTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-ruta-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('rutaAction').value = 'edit';
            document.getElementById('rutaId').value = row.getAttribute('data-id');
            document.getElementById('addEditRutaModalLabel').textContent = 'Editează Rută';

            document.getElementById('modalNumeRuta').value = row.getAttribute('data-nume-ruta');
            document.getElementById('modalSelectVehicul').value = row.getAttribute('data-id-vehicul');
            document.getElementById('modalSelectSofer').value = row.getAttribute('data-id-sofer');
            document.getElementById('modalLocatiePlecare').value = row.getAttribute('data-locatie-plecare');
            document.getElementById('modalLocatieDestinatie').value = row.getAttribute('data-locatie-final');
            document.getElementById('modalDataPlecareEstimata').value = row.getAttribute('data-data-plecare-estimata');
            document.getElementById('modalDataSosireEstimata').value = row.getAttribute('data-data-sosire-estimata');
            document.getElementById('modalDistantaEstimata').value = row.getAttribute('data-distanta-estimata-km');
            document.getElementById('modalTimpEstimata').value = row.getAttribute('data-timp-estimat-ore');
            document.getElementById('modalStatus').value = row.getAttribute('data-status');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    rutaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(rutaForm);

        fetch('process_planificare_rute.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditRutaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea rutei:', error);
            alert('A apărut o eroare la salvarea rutei.');
        });
    });

    // Ștergerea rutei
    ruteTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-ruta-btn')) {
            const rutaIdToDelete = e.target.getAttribute('data-id');
            const rutaName = e.target.closest('tr').getAttribute('data-nume-ruta');
            document.getElementById('deleteRutaId').value = rutaIdToDelete;
            document.getElementById('deleteRutaName').textContent = rutaName;
            const deleteModalInstance = new bootstrap.Modal(deleteRutaModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteRutaBtn.addEventListener('click', function() {
        const rutaIdToDelete = document.getElementById('deleteRutaId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', rutaIdToDelete);

        fetch('process_planificare_rute.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteRutaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea rutei:', error);
            alert('A apărut o eroare la ștergerea rutei.');
        });
    });
});
</script>