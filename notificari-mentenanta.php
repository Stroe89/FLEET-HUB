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

// Preluăm lista de mecanici pentru dropdown-uri de filtrare
$mecanici_list = [];
$stmt_mecanici = $conn->prepare("SELECT id, nume, prenume FROM angajati WHERE functie = 'Mecanic' ORDER BY nume ASC, prenume ASC");
if ($stmt_mecanici) {
    $stmt_mecanici->execute();
    $result_mecanici = $stmt_mecanici->get_result();
    while ($row = $result_mecanici->fetch_assoc()) {
        $mecanici_list[] = $row;
    }
    $stmt_mecanici->close();
}

// Preluăm înregistrările de mentenanță care sunt în așteptare sau în desfășurare
$mentenanta_alerte_list = [];
$sql_alerte_mentenanta = "
    SELECT im.*, v.model, v.numar_inmatriculare, v.tip as tip_vehicul,
           a.nume as mecanic_nume, a.prenume as mecanic_prenume
    FROM istoric_mentenanta im
    JOIN vehicule v ON im.id_vehicul = v.id
    LEFT JOIN angajati a ON im.id_mecanic = a.id
    WHERE im.status IN ('În Așteptare', 'În Desfășurare')
    ORDER BY im.data_intrare_service DESC
";
$result_alerte_mentenanta = $conn->query($sql_alerte_mentenanta);
if ($result_alerte_mentenanta) {
    while ($row = $result_alerte_mentenanta->fetch_assoc()) {
        $mentenanta_alerte_list[] = $row;
    }
}
$conn->close();

// Tipuri de mentenanță pentru filtrare
$tipuri_mentenanta = ['Revizie Periodică', 'Reparație Majoră', 'Reparație Minoră', 'Inspecție Tehnică', 'Schimb Anvelope', 'Altele'];

// Statusuri mentenanță pentru filtrare (doar cele relevante pentru alerte)
$statusuri_mentenanta_alerte = ['În Așteptare', 'În Desfășurare'];

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
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Alerte Mentenanță</title>

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

    /* Stiluri specifice pentru tabelul de alerte mentenanță */
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
    /* Badge-uri pentru statusul mentenanței */
    .badge-status-în_așteptare { background-color: #ffc107 !important; color: #343a40 !important; }
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
            <div class="breadcrumb-title pe-3">Notificări</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Alerte Mentenanță</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Alerte Mentenanță Vehicule</h4>
                        <p class="text-muted">Înregistrări de mentenanță care necesită atenție (în așteptare sau în desfășurare).</p>
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

                        <!-- Secțiunea de Filtrare Avansată -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-3 mb-3">
                                <label for="filterVehicul" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicul">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterTipVehicul" class="form-label">Filtrează după Tip Vehicul:</label>
                                <select class="form-select" id="filterTipVehicul">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_vehicul_finale as $tip): // Folosim lista finală ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterTipMentenanta" class="form-label">Filtrează după Tip Mentenanță:</label>
                                <select class="form-select" id="filterTipMentenanta">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_mentenanta as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_mentenanta_alerte as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterMecanic" class="form-label">Filtrează după Mecanic:</label>
                                <select class="form-select" id="filterMecanic">
                                    <option value="all">Toți Mecanicii</option>
                                    <?php foreach ($mecanici_list as $mecanic): ?>
                                        <option value="<?php echo $mecanic['id']; ?>"><?php echo htmlspecialchars($mecanic['nume'] . ' ' . $mecanic['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta descriere, observații, factură...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Alertelor Mentenanță -->
                        <?php if (empty($mentenanta_alerte_list)): ?>
                            <div class="alert alert-info">Nu există alerte de mentenanță în acest moment.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Vehicul</th>
                                            <th>Tip Mentenanță</th>
                                            <th>Descriere Problemă</th>
                                            <th>Dată Intrare</th>
                                            <th>Status</th>
                                            <th>Mecanic</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mentenantaAlerteTableBody">
                                        <?php foreach ($mentenanta_alerte_list as $record): ?>
                                            <tr 
                                                data-id="<?php echo $record['id']; ?>"
                                                data-id-vehicul="<?php echo $record['id_vehicul']; ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($record['tip_vehicul'] ?? ''); ?>"
                                                data-tip-mentenanta="<?php echo htmlspecialchars($record['tip_mentenanta']); ?>"
                                                data-descriere-problema="<?php echo htmlspecialchars($record['descriere_problema']); ?>"
                                                data-descriere-lucrari="<?php echo htmlspecialchars($record['descriere_lucrari']); ?>"
                                                data-data-intrare-service="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($record['data_intrare_service']))); ?>"
                                                data-data-iesire-service="<?php echo htmlspecialchars($record['data_iesire_service'] ? date('Y-m-d\TH:i', strtotime($record['data_iesire_service'])) : ''); ?>"
                                                data-cost-total="<?php echo htmlspecialchars($record['cost_total']); ?>"
                                                data-factura-serie="<?php echo htmlspecialchars($record['factura_serie']); ?>"
                                                data-factura-numar="<?php echo htmlspecialchars($record['factura_numar']); ?>"
                                                data-status="<?php echo htmlspecialchars($record['status']); ?>"
                                                data-id-mecanic="<?php echo htmlspecialchars($record['id_mecanic'] ?? ''); ?>"
                                                data-observatii="<?php echo htmlspecialchars($record['observatii']); ?>"
                                                data-km-la-intrare="<?php echo htmlspecialchars($record['km_la_intrare']); ?>"
                                                data-km-la-iesire="<?php echo htmlspecialchars($record['km_la_iesire']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($record['model'] . ' ' . $record['numar_inmatriculare'] . ' ' . $record['tip_vehicul'] . ' ' . $record['tip_mentenanta'] . ' ' . $record['descriere_problema'] . ' ' . $record['descriere_lucrari'] . ' ' . $record['factura_serie'] . ' ' . $record['factura_numar'] . ' ' . ($record['mecanic_nume'] ?? '') . ' ' . ($record['mecanic_prenume'] ?? '') . ' ' . $record['observatii'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($record['model'] . ' (' . $record['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Tip Vehicul:"><?php echo htmlspecialchars($record['tip_vehicul'] ?? 'N/A'); ?></td>
                                                <td data-label="Tip Mentenanță:"><?php echo htmlspecialchars($record['tip_mentenanta']); ?></td>
                                                <td data-label="Descriere Problemă:"><?php echo htmlspecialchars(mb_strimwidth($record['descriere_problema'], 0, 30, "...")); ?></td>
                                                <td data-label="Dată Intrare:"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($record['data_intrare_service']))); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                                <td data-label="Mecanic:"><?php echo htmlspecialchars($record['mecanic_nume'] ? $record['mecanic_nume'] . ' ' . $record['mecanic_prenume'] : 'N/A'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#viewMentenantaDetailsModal">Detalii</button>
                                                    <button type="button" class="btn btn-sm btn-outline-success send-whatsapp-btn mb-1 w-100" data-record-id="<?php echo $record['id']; ?>">Trimite WhatsApp</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary send-email-btn w-100" data-record-id="<?php echo $record['id']; ?>">Trimite Email</button>
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

<!-- Modal Vizualizare Detalii Mentenanță (Read-only) -->
<div class="modal fade" id="viewMentenantaDetailsModal" tabindex="-1" aria-labelledby="viewMentenantaDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMentenantaDetailsModalLabel">Detalii Înregistrare Mentenanță</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Vehicul:</label>
                        <p class="form-control-plaintext text-white" id="detailVehicul"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tip Vehicul:</label>
                        <p class="form-control-plaintext text-white" id="detailTipVehicul"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tip Mentenanță:</label>
                        <p class="form-control-plaintext text-white" id="detailTipMentenanta"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mecanic:</label>
                        <p class="form-control-plaintext text-white" id="detailMecanic"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descriere Problemă:</label>
                        <p class="form-control-plaintext text-white" id="detailDescriereProblema"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descriere Lucrări Efectuate:</label>
                        <p class="form-control-plaintext text-white" id="detailDescriereLucrari"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dată Intrare Service:</label>
                        <p class="form-control-plaintext text-white" id="detailDataIntrareService"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dată Ieșire Service:</label>
                        <p class="form-control-plaintext text-white" id="detailDataIesireService"></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost Total:</label>
                        <p class="form-control-plaintext text-white" id="detailCostTotal"></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Serie Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailFacturaSerie"></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Număr Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailFacturaNumar"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status:</label>
                        <p class="form-control-plaintext text-white" id="detailStatus"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Km la Intrare:</label>
                        <p class="form-control-plaintext text-white" id="detailKmLaIntrare"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Km la Ieșire:</label>
                        <p class="form-control-plaintext text-white" id="detailKmLaIesire"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observații:</label>
                        <p class="form-control-plaintext text-white" id="detailObservatii"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mentenantaAlerteTableBody = document.getElementById('mentenantaAlerteTableBody');
    const viewMentenantaDetailsModal = document.getElementById('viewMentenantaDetailsModal');

    // Date mentenanta pentru JavaScript (populat din PHP)
    const mentenantaAlerteData = <?php echo json_encode($mentenanta_alerte_list); ?>;
    const mentenantaMap = {};
    mentenantaAlerteData.forEach(record => {
        mentenantaMap[record.id] = record;
    });

    // Filtrare Tabel
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipVehicul = document.getElementById('filterTipVehicul');
    const filterTipMentenanta = document.getElementById('filterTipMentenanta');
    const filterMecanic = document.getElementById('filterMecanic');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipVehicul = filterTipVehicul.value;
        const selectedTipMentenanta = filterTipMentenanta.value;
        const selectedMecanicId = filterMecanic.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#mentenantaAlerteTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipVehicul = row.getAttribute('data-tip-vehicul');
            const rowTipMentenanta = row.getAttribute('data-tip-mentenanta');
            const rowMecanicId = row.getAttribute('data-id-mecanic');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipVehiculMatch = (selectedTipVehicul === 'all' || rowTipVehicul === selectedTipVehicul);
            const tipMentenantaMatch = (selectedTipMentenanta === 'all' || rowTipMentenanta === selectedTipMentenanta);
            const mecanicMatch = (selectedMecanicId === 'all' || rowMecanicId === selectedMecanicId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipVehiculMatch && tipMentenantaMatch && mecanicMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipVehicul.addEventListener('change', filterTable);
    filterTipMentenanta.addEventListener('change', filterTable);
    filterMecanic.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterTable(); // Rulează la încărcarea paginii


    // Deschide modalul pentru vizualizarea detaliilor
    mentenantaAlerteTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-details-btn')) {
            const row = e.target.closest('tr');
            const recordId = row.getAttribute('data-id');
            const record = mentenantaMap[recordId];

            if (record) {
                document.getElementById('detailVehicul').textContent = `${record.model} (${record.numar_inmatriculare})`;
                document.getElementById('detailTipVehicul').textContent = record.tip_vehicul || 'N/A';
                document.getElementById('detailTipMentenanta').textContent = record.tip_mentenanta;
                document.getElementById('detailMecanic').textContent = record.mecanic_nume ? `${record.mecanic_nume} ${record.mecanic_prenume}` : 'N/A';
                document.getElementById('detailDescriereProblema').textContent = record.descriere_problema || 'N/A';
                document.getElementById('detailDescriereLucrari').textContent = record.descriere_lucrari;
                document.getElementById('detailDataIntrareService').textContent = new Date(record.data_intrare_service).toLocaleString('ro-RO');
                document.getElementById('detailDataIesireService').textContent = record.data_iesire_service ? new Date(record.data_iesire_service).toLocaleString('ro-RO') : 'N/A';
                document.getElementById('detailCostTotal').textContent = parseFloat(record.cost_total).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RON';
                document.getElementById('detailFacturaSerie').textContent = record.factura_serie || 'N/A';
                document.getElementById('detailFacturaNumar').textContent = record.factura_numar || 'N/A';
                document.getElementById('detailStatus').textContent = record.status;
                document.getElementById('detailKmLaIntrare').textContent = (record.km_la_intrare || 'N/A') + ' km';
                document.getElementById('detailKmLaIesire').textContent = (record.km_la_iesire || 'N/A') + ' km';
                document.getElementById('detailObservatii').textContent = record.observatii || 'N/A';

                const viewMentenantaDetailsModalInstance = new bootstrap.Modal(viewMentenantaDetailsModal);
                viewMentenantaDetailsModalInstance.show();
            }
        }
    });

    // Logica pentru butoanele de notificare (WhatsApp și Email)
    mentenantaAlerteTableBody.addEventListener('click', function(e) {
        const row = e.target.closest('tr');
        if (!row) return;

        const recordId = row.getAttribute('data-id');
        const record = mentenantaMap[recordId];

        if (!record) {
            console.error("Record not found in map for ID:", recordId);
            alert("Eroare: Detaliile înregistrării nu au putut fi preluate.");
            return;
        }

        const vehicleInfo = `${record.model} (${record.numar_inmatriculare})`;
        const tipMentenanta = record.tip_mentenanta;
        const dataIntrare = new Date(record.data_intrare_service).toLocaleDateString('ro-RO');
        const statusMentenanta = record.status;
        const mecanic = record.mecanic_nume ? `${record.mecanic_nume} ${record.mecanic_prenume}` : 'N/A';
        const descriereProblema = record.descriere_problema || 'N/A';

        // Placeholder pentru contacte (înlocuiește cu logică reală de preluare a contactelor)
        // Ideal, acestea ar veni din baza de date (ex: telefonul/emailul mecanicului alocat)
        const contactPhone = '407xxxxxxxx'; // Exemplu: numărul de telefon al mecanicului/responsabilului
        const contactEmail = 'manager.service@companie.com'; // Exemplu: emailul responsabilului

        const message = `Salut! O înregistrare de mentenanță pentru vehiculul ${vehicleInfo} (Tip: ${tipMentenanta}) are statusul "${statusMentenanta}". Dată intrare: ${dataIntrare}. Problemă: ${descriereProblema}. Mecanic: ${mecanic}. Te rog să verifici.`;
        const emailSubject = `Alertă Mentenanță Vehicul: ${tipMentenanta} pentru ${vehicleInfo}`;

        if (e.target.classList.contains('send-whatsapp-btn')) {
            const whatsappUrl = `https://wa.me/${contactPhone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        } else if (e.target.classList.contains('send-email-btn')) {
            const emailUrl = `mailto:${contactEmail}?subject=${encodeURIComponent(emailSubject)}&body=${encodeURIComponent(message)}`;
            window.open(emailUrl, '_blank');
        }
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
