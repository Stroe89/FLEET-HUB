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
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare, tip FROM vehicule ORDER BY model ASC, numar_inmatriculare ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

// Preluăm lista de mecanici pentru dropdown-uri de filtrare și adăugare
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

// Preluăm toate înregistrările de reparații
$reparatii_list = [];
$sql_reparatii = "
    SELECT ir.*, v.model, v.numar_inmatriculare, v.tip as tip_vehicul,
           a.nume as mecanic_nume, a.prenume as mecanic_prenume
    FROM istoric_reparatii ir
    JOIN vehicule v ON ir.id_vehicul = v.id
    LEFT JOIN angajati a ON ir.id_mecanic = a.id
    ORDER BY ir.data_intrare_service DESC
";
$result_reparatii = $conn->query($sql_reparatii);
if ($result_reparatii) {
    while ($row = $result_reparatii->fetch_assoc()) {
        $reparatii_list[] = $row;
    }
}
// Conexiunea la baza de date este închisă automat la sfârșitul scriptului principal
// $conn->close();

// Tipuri de reparații pentru filtrare
$tipuri_reparatie = ['Motor', 'Transmisie', 'Frâne', 'Suspensie', 'Electrică', 'Caroserie', 'Anvelope', 'Revizie', 'Altele'];

// Statusuri reparații pentru filtrare
$statusuri_reparatie = ['În Așteptare', 'În Desfășurare', 'Finalizată', 'Anulată'];

// Tipuri de vehicule pentru filtrare (extrage din lista de vehicule pentru a fi dinamice)
$tipuri_vehicul_list = array_unique(array_column($vehicule_list, 'tip'));
sort($tipuri_vehicul_list);
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Istoric Reparații</title>

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

    /* Stiluri specifice pentru tabelul de reparații */
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
    /* Badge-uri pentru statusul reparației */
    .badge-status-în_așteptare { background-color: #17a2b8 !important; color: #fff !important; }
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
            <div class="breadcrumb-title pe-3">Mentenanță</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Istoric Reparații</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Istoric Reparații Vehicule</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditReparatieModal" id="addReparatieBtn">
                            <i class="bx bx-plus"></i> Adaugă Înregistrare Reparație
                        </button>

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
                                <label for="filterTipReparatie" class="form-label">Filtrează după Tip Reparație:</label>
                                <select class="form-select" id="filterTipReparatie">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_reparatie as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterMecanic" class="form-label">Filtrează după Mecanic:</label>
                                <select class="form-select" id="filterMecanic">
                                    <option value="all">Toți Mecanicii</option>
                                    <?php foreach ($mecanici_list as $mecanic): ?>
                                        <option value="<?php echo $mecanic['id']; ?>"><?php echo htmlspecialchars($mecanic['nume'] . ' ' . $mecanic['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_reparatie as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStartDate" class="form-label">Dată Intrare (de la):</label>
                                <input type="date" class="form-control" id="filterStartDate">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterEndDate" class="form-label">Dată Ieșire (până la):</label>
                                <input type="date" class="form-control" id="filterEndDate">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta descriere, observații, factură...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Înregistrărilor de Reparații -->
                        <?php if (empty($reparatii_list)): ?>
                            <div class="alert alert-info">Nu există înregistrări de reparații.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Reparație</th>
                                            <th>Dată Intrare</th>
                                            <th>Dată Ieșire</th>
                                            <th>Cost Total</th>
                                            <th>Status</th>
                                            <th>Mecanic</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reparatiiTableBody">
                                        <?php foreach ($reparatii_list as $record): ?>
                                            <tr 
                                                data-id="<?php echo $record['id']; ?>"
                                                data-id-vehicul="<?php echo $record['id_vehicul']; ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($record['tip_vehicul'] ?? ''); ?>"
                                                data-tip-reparatie="<?php echo htmlspecialchars($record['tip_reparatie']); ?>"
                                                data-descriere-problema="<?php echo htmlspecialchars($record['descriere_problema']); ?>"
                                                data-descriere-lucrari-efectuate="<?php echo htmlspecialchars($record['descriere_lucrari_efectuate']); ?>"
                                                data-data-intrare-service="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($record['data_intrare_service']))); ?>"
                                                data-data-iesire-service="<?php echo htmlspecialchars($record['data_iesire_service'] ? date('Y-m-d\TH:i', strtotime($record['data_iesire_service'])) : ''); ?>"
                                                data-cost-piese="<?php echo htmlspecialchars($record['cost_piese']); ?>"
                                                data-cost-manopera="<?php echo htmlspecialchars($record['cost_manopera']); ?>"
                                                data-cost-total="<?php echo htmlspecialchars($record['cost_total']); ?>"
                                                data-factura-serie="<?php echo htmlspecialchars($record['factura_serie']); ?>"
                                                data-factura-numar="<?php echo htmlspecialchars($record['factura_numar']); ?>"
                                                data-status="<?php echo htmlspecialchars($record['status']); ?>"
                                                data-id-mecanic="<?php echo htmlspecialchars($record['id_mecanic'] ?? ''); ?>"
                                                data-observatii="<?php echo htmlspecialchars($record['observatii']); ?>"
                                                data-km-la-reparatie="<?php echo htmlspecialchars($record['km_la_reparatie']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($record['model'] . ' ' . $record['numar_inmatriculare'] . ' ' . $record['tip_reparatie'] . ' ' . $record['descriere_problema'] . ' ' . $record['descriere_lucrari_efectuate'] . ' ' . $record['factura_serie'] . ' ' . $record['factura_numar'] . ' ' . ($record['mecanic_nume'] ?? '') . ' ' . ($record['mecanic_prenume'] ?? '') . ' ' . $record['observatii'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($record['model'] . ' (' . $record['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Tip Reparație:"><?php echo htmlspecialchars($record['tip_reparatie']); ?></td>
                                                <td data-label="Dată Intrare:"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($record['data_intrare_service']))); ?></td>
                                                <td data-label="Dată Ieșire:"><?php echo htmlspecialchars($record['data_iesire_service'] ? date('d.m.Y H:i', strtotime($record['data_iesire_service'])) : 'N/A'); ?></td>
                                                <td data-label="Cost Total:"><?php echo htmlspecialchars(number_format($record['cost_total'], 2, ',', '.')) . ' RON'; ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                                <td data-label="Mecanic:"><?php echo htmlspecialchars($record['mecanic_nume'] ? $record['mecanic_nume'] . ' ' . $record['mecanic_prenume'] : 'N/A'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#viewReparatieDetailsModal">Detalii</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-reparatie-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#addEditReparatieModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-reparatie-btn w-100" data-id="<?php echo $record['id']; ?>">Șterge</button>
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

<!-- Modal Vizualizare Detalii Reparație (Read-only) -->
<div class="modal fade" id="viewReparatieDetailsModal" tabindex="-1" aria-labelledby="viewReparatieDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewReparatieDetailsModalLabel">Detalii Înregistrare Reparație</h5>
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
                        <label class="form-label">Tip Reparație:</label>
                        <p class="form-control-plaintext text-white" id="detailTipReparatie"></p>
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
                        <p class="form-control-plaintext text-white" id="detailDescriereLucrariEfectuate"></p>
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
                        <label class="form-label">Cost Piese:</label>
                        <p class="form-control-plaintext text-white" id="detailCostPiese"></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost Manoperă:</label>
                        <p class="form-control-plaintext text-white" id="detailCostManopera"></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost Total:</label>
                        <p class="form-control-plaintext text-white" id="detailCostTotal"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status:</label>
                        <p class="form-control-plaintext text-white" id="detailStatus"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Km la Reparație:</label>
                        <p class="form-control-plaintext text-white" id="detailKmLaReparatie"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serie Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailFacturaSerie"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Număr Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailFacturaNumar"></p>
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

<!-- Modal Adaugă/Editează Reparație -->
<div class="modal fade" id="addEditReparatieModal" tabindex="-1" aria-labelledby="addEditReparatieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditReparatieModalLabel">Adaugă Înregistrare Reparație</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reparatieForm" action="process_reparatii.php" method="POST">
                <input type="hidden" id="reparatieAction" name="action" value="add">
                <input type="hidden" id="reparatieId" name="id">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalSelectVehicul" class="form-label">Vehicul:</label>
                            <select class="form-select" id="modalSelectVehicul" name="id_vehicul" required>
                                <option value="">Alege un vehicul</option>
                                <?php foreach ($vehicule_list as $veh): ?>
                                    <option value="<?php echo $veh['id']; ?>" data-tip-vehicul="<?php echo htmlspecialchars($veh['tip'] ?? ''); ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalTipVehicul" class="form-label">Tip Vehicul:</label>
                            <input type="text" class="form-control" id="modalTipVehicul" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="modalTipReparatie" class="form-label">Tip Reparație:</label>
                            <select class="form-select" id="modalTipReparatie" name="tip_reparatie" required>
                                <option value="">Selectează tipul</option>
                                <?php foreach ($tipuri_reparatie as $tip): ?>
                                    <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalSelectMecanic" class="form-label">Mecanic (opțional):</label>
                            <select class="form-select" id="modalSelectMecanic" name="id_mecanic">
                                <option value="">Fără mecanic alocat</option>
                                <?php foreach ($mecanici_list as $mecanic): ?>
                                    <option value="<?php echo $mecanic['id']; ?>"><?php echo htmlspecialchars($mecanic['nume'] . ' ' . $mecanic['prenume']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataIntrareService" class="form-label">Dată Intrare Service:</label>
                            <input type="datetime-local" class="form-control" id="modalDataIntrareService" name="data_intrare_service" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataIesireService" class="form-label">Dată Ieșire Service (opțional):</label>
                            <input type="datetime-local" class="form-control" id="modalDataIesireService" name="data_iesire_service">
                        </div>
                        <div class="col-md-6">
                            <label for="modalKmLaReparatie" class="form-label">Km la Reparație:</label>
                            <input type="number" class="form-control" id="modalKmLaReparatie" name="km_la_reparatie" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="modalCostPiese" class="form-label">Cost Piese (RON):</label>
                            <input type="number" step="0.01" class="form-control" id="modalCostPiese" name="cost_piese" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="modalCostManopera" class="form-label">Cost Manoperă (RON):</label>
                            <input type="number" step="0.01" class="form-control" id="modalCostManopera" name="cost_manopera" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="modalCostTotal" class="form-label">Cost Total (RON):</label>
                            <input type="number" step="0.01" class="form-control" id="modalCostTotal" name="cost_total" value="0.00" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="modalStatus" class="form-label">Status:</label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_reparatie as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalFacturaSerie" class="form-label">Serie Factură (opțional):</label>
                            <input type="text" class="form-control" id="modalFacturaSerie" name="factura_serie">
                        </div>
                        <div class="col-md-6">
                            <label for="modalFacturaNumar" class="form-label">Număr Factură (opțional):</label>
                            <input type="text" class="form-control" id="modalFacturaNumar" name="factura_numar">
                        </div>
                        <div class="col-12">
                            <label for="modalDescriereProblema" class="form-label">Descriere Problemă (opțional):</label>
                            <textarea class="form-control" id="modalDescriereProblema" name="descriere_problema" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="modalDescriereLucrariEfectuate" class="form-label">Descriere Lucrări Efectuate:</label>
                            <textarea class="form-control" id="modalDescriereLucrariEfectuate" name="descriere_lucrari_efectuate" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații Suplimentare:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Înregistrare</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Reparație -->
<div class="modal fade" id="deleteReparatieModal" tabindex="-1" aria-labelledby="deleteReparatieModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteReparatieModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi înregistrarea de reparație pentru vehiculul <strong id="deleteReparatieVehicul"></strong> (<strong id="deleteReparatieTip"></strong>)? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteReparatieId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteReparatieBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reparatiiTableBody = document.getElementById('reparatiiTableBody');
    const viewReparatieDetailsModal = document.getElementById('viewReparatieDetailsModal');
    const addEditReparatieModal = document.getElementById('addEditReparatieModal');
    const reparatieForm = document.getElementById('reparatieForm');
    const deleteReparatieModal = document.getElementById('deleteReparatieModal');
    const confirmDeleteReparatieBtn = document.getElementById('confirmDeleteReparatieBtn');

    // Câmpuri din modalul de editare/adăugare
    const modalSelectVehicul = document.getElementById('modalSelectVehicul');
    const modalTipVehicul = document.getElementById('modalTipVehicul');
    const modalSelectMecanic = document.getElementById('modalSelectMecanic');
    const modalCostPiese = document.getElementById('modalCostPiese');
    const modalCostManopera = document.getElementById('modalCostManopera');
    const modalCostTotal = document.getElementById('modalCostTotal');


    // Date pre-încărcate pentru vehicule și mecanici (pentru a popula câmpurile readonly)
    const vehiculeData = <?php echo json_encode($vehicule_list); ?>;
    const mecaniciData = <?php echo json_encode($mecanici_list); ?>;

    // Mapări pentru acces rapid
    const vehiculeMap = {};
    vehiculeData.forEach(veh => {
        vehiculeMap[veh.id] = veh;
    });

    const mecaniciMap = {};
    mecaniciData.forEach(mecanic => {
        mecaniciMap[mecanic.id] = mecanic;
    });


    // Funcții pentru actualizarea câmpurilor readonly în modal
    function updateModalVehiculDetails() {
        const selectedVehiculId = modalSelectVehicul.value;
        if (selectedVehiculId && vehiculeMap[selectedVehiculId]) {
            modalTipVehicul.value = vehiculeMap[selectedVehiculId].tip || 'N/A';
        } else {
            modalTipVehicul.value = '';
        }
    }
    modalSelectVehicul.addEventListener('change', updateModalVehiculDetails);

    // Calculează Cost Total automat
    function calculateCostTotal() {
        const costPiese = parseFloat(modalCostPiese.value) || 0;
        const costManopera = parseFloat(modalCostManopera.value) || 0;
        modalCostTotal.value = (costPiese + costManopera).toFixed(2);
    }
    modalCostPiese.addEventListener('input', calculateCostTotal);
    modalCostManopera.addEventListener('input', calculateCostTotal);


    // Filtrare Tabel
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipReparatie = document.getElementById('filterTipReparatie');
    const filterMecanic = document.getElementById('filterMecanic');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');
    const filterStartDate = document.getElementById('filterStartDate');
    const filterEndDate = document.getElementById('filterEndDate');

    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipReparatie = filterTipReparatie.value;
        const selectedMecanicId = filterMecanic.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#reparatiiTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipReparatie = row.getAttribute('data-tip-reparatie');
            const rowMecanicId = row.getAttribute('data-id-mecanic');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');
            const rowDataIntrare = new Date(row.getAttribute('data-data-intrare-service')).getTime();
            const rowDataIesire = row.getAttribute('data-data-iesire-service') ? new Date(row.getAttribute('data-data-iesire-service')).getTime() : Infinity;

            let dateMatch = true;
            if (filterStartDate.value) {
                const startFilterTime = new Date(filterStartDate.value).getTime();
                if (rowDataIntrare < startFilterTime) {
                    dateMatch = false;
                }
            }
            if (filterEndDate.value) {
                const endFilterTime = new Date(filterEndDate.value).getTime();
                if (rowDataIntrare > endFilterTime) { // Filtrează pe data de intrare
                    dateMatch = false;
                }
            }

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipReparatieMatch = (selectedTipReparatie === 'all' || rowTipReparatie === selectedTipReparatie);
            const mecanicMatch = (selectedMecanicId === 'all' || rowMecanicId === selectedMecanicId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipReparatieMatch && mecanicMatch && statusMatch && searchMatch && dateMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipReparatie.addEventListener('change', filterTable);
    filterMecanic.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterStartDate.addEventListener('change', filterTable);
    filterEndDate.addEventListener('change', filterTable);


    // Deschide modalul pentru adăugare (butonul din pagină)
    document.getElementById('addReparatieBtn').addEventListener('click', function(e) {
        reparatieForm.reset();
        document.getElementById('reparatieAction').value = 'add';
        document.getElementById('reparatieId').value = '';
        document.getElementById('addEditReparatieModalLabel').textContent = 'Adaugă Înregistrare Reparație';
        document.getElementById('modalDataIntrareService').value = new Date().toISOString().substring(0, 16);
        document.getElementById('modalDataIesireService').value = '';
        document.getElementById('modalCostPiese').value = '0.00';
        document.getElementById('modalCostManopera').value = '0.00';
        document.getElementById('modalCostTotal').value = '0.00';
        document.getElementById('modalStatus').value = 'În Așteptare';
        document.getElementById('modalKmLaReparatie').value = '';
        document.getElementById('modalKmLaIesire').value = '';
        updateModalVehiculDetails(); // Resetează și detaliile vehiculului
    });


    // Deschide modalul pentru vizualizarea detaliilor
    reparatiiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-details-btn')) {
            const row = e.target.closest('tr');
            
            document.getElementById('detailVehicul').textContent = row.querySelector('td[data-label="Vehicul:"]').textContent;
            document.getElementById('detailTipVehicul').textContent = row.getAttribute('data-tip-vehicul') || 'N/A';
            document.getElementById('detailTipReparatie').textContent = row.getAttribute('data-tip-reparatie');
            document.getElementById('detailMecanic').textContent = row.querySelector('td[data-label="Mecanic:"]').textContent;
            document.getElementById('detailDescriereProblema').textContent = row.getAttribute('data-descriere-problema') || 'N/A';
            document.getElementById('detailDescriereLucrariEfectuate').textContent = row.getAttribute('data-descriere-lucrari-efectuate');
            document.getElementById('detailDataIntrareService').textContent = new Date(row.getAttribute('data-data-intrare-service')).toLocaleString('ro-RO');
            document.getElementById('detailDataIesireService').textContent = row.getAttribute('data-data-iesire-service') ? new Date(row.getAttribute('data-data-iesire-service')).toLocaleString('ro-RO') : 'N/A';
            document.getElementById('detailCostPiese').textContent = parseFloat(row.getAttribute('data-cost-piese')).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RON';
            document.getElementById('detailCostManopera').textContent = parseFloat(row.getAttribute('data-cost-manopera')).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RON';
            document.getElementById('detailCostTotal').textContent = parseFloat(row.getAttribute('data-cost-total')).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RON';
            document.getElementById('detailFacturaSerie').textContent = row.getAttribute('data-factura-serie') || 'N/A';
            document.getElementById('detailFacturaNumar').textContent = row.getAttribute('data-factura-numar') || 'N/A';
            document.getElementById('detailStatus').textContent = row.querySelector('td[data-label="Status:"] span').textContent;
            document.getElementById('detailKmLaReparatie').textContent = (row.getAttribute('data-km-la-reparatie') || 'N/A') + ' km';
            document.getElementById('detailObservatii').textContent = row.getAttribute('data-observatii') || 'N/A';

            const viewReparatieDetailsModalInstance = new bootstrap.Modal(viewReparatieDetailsModal);
            viewReparatieDetailsModalInstance.show();
        }
    });

    // Deschide modalul pentru editare
    reparatiiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-reparatie-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('reparatieAction').value = 'edit';
            document.getElementById('reparatieId').value = row.getAttribute('data-id');
            document.getElementById('addEditReparatieModalLabel').textContent = 'Editează Înregistrare Reparație';

            // Populează câmpurile din formularul de editare
            document.getElementById('modalSelectVehicul').value = row.getAttribute('data-id-vehicul');
            document.getElementById('modalTipReparatie').value = row.getAttribute('data-tip-reparatie');
            document.getElementById('modalSelectMecanic').value = row.getAttribute('data-id-mecanic');
            document.getElementById('modalDataIntrareService').value = row.getAttribute('data-data-intrare-service');
            document.getElementById('modalDataIesireService').value = row.getAttribute('data-data-iesire-service');
            document.getElementById('modalKmLaReparatie').value = row.getAttribute('data-km-la-reparatie');
            document.getElementById('modalKmLaIesire').value = row.getAttribute('data-km-la-iesire');
            document.getElementById('modalCostPiese').value = parseFloat(row.getAttribute('data-cost-piese')).toFixed(2);
            document.getElementById('modalCostManopera').value = parseFloat(row.getAttribute('data-cost-manopera')).toFixed(2);
            document.getElementById('modalCostTotal').value = parseFloat(row.getAttribute('data-cost-total')).toFixed(2);
            document.getElementById('modalStatus').value = row.getAttribute('data-status');
            document.getElementById('modalFacturaSerie').value = row.getAttribute('data-factura-serie');
            document.getElementById('modalFacturaNumar').value = row.getAttribute('data-factura-numar');
            document.getElementById('modalDescriereProblema').value = row.getAttribute('data-descriere-problema');
            document.getElementById('modalDescriereLucrariEfectuate').value = row.getAttribute('data-descriere-lucrari-efectuate');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
            
            updateModalVehiculDetails(); // Asigură că tipul vehiculului este afișat

            const addEditReparatieModalInstance = new bootstrap.Modal(addEditReparatieModal);
            addEditReparatieModalInstance.show();
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    reparatieForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(reparatieForm);

        fetch('process_reparatii.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditReparatieModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea înregistrării de reparație:', error);
            alert('A apărut o eroare la salvarea înregistrării de reparație.');
        });
    });

    // Ștergerea înregistrării de reparație
    reparatiiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-reparatie-btn')) {
            const reparatieIdToDelete = e.target.getAttribute('data-id');
            const reparatieVehicul = e.target.closest('tr').querySelector('td[data-label="Vehicul:"]').textContent;
            const reparatieTip = e.target.closest('tr').querySelector('td[data-label="Tip Reparație:"]').textContent;
            document.getElementById('deleteReparatieId').value = reparatieIdToDelete;
            document.getElementById('deleteReparatieVehicul').textContent = reparatieVehicul;
            document.getElementById('deleteReparatieTip').textContent = reparatieTip;
            const deleteReparatieModalInstance = new bootstrap.Modal(deleteReparatieModal);
            deleteReparatieModalInstance.show();
        }
    });

    confirmDeleteReparatieBtn.addEventListener('click', function() {
        const reparatieIdToDelete = document.getElementById('deleteReparatieId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', reparatieIdToDelete);

        fetch('process_reparatii.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteReparatieModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea înregistrării de reparație:', error);
            alert('A apărut o eroare la ștergerea înregistrării de reparație.');
        });
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
