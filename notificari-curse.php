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

// Preluăm lista de șoferi pentru dropdown-uri de filtrare
$soferi_list = [];
$stmt_soferi = $conn->prepare("SELECT id, nume, prenume, telefon, email FROM angajati WHERE functie = 'Sofer' ORDER BY nume ASC, prenume ASC");
if ($stmt_soferi) {
    $stmt_soferi->execute();
    $result_soferi = $stmt_soferi->get_result();
    while ($row = $result_soferi->fetch_assoc()) {
        $soferi_list[] = $row;
    }
    $stmt_soferi->close();
}

// Preluăm toate cursele active/programate pentru a genera alerte
$curse_alerte_list = [];
$sql_curse_alerte = "
    SELECT 
        ca.*, v.model, v.numar_inmatriculare, v.tip as tip_vehicul,
        a.nume as sofer_nume, a.prenume as sofer_prenume, a.telefon as sofer_telefon, a.email as sofer_email
    FROM 
        curse_active ca
    JOIN 
        vehicule v ON ca.id_vehicul = v.id
    LEFT JOIN 
        angajati a ON ca.id_sofer = a.id
    WHERE 
        ca.status IN ('Programată', 'În cursă', 'Pauză')
    ORDER BY 
        ca.data_inceput ASC
";
$result_curse_alerte = $conn->query($sql_curse_alerte);
$now_dt = new DateTime();

if ($result_curse_alerte) {
    while ($cursa = $result_curse_alerte->fetch_assoc()) {
        $alert_type = '';
        $alert_details = '';
        $alert_status_class = ''; // Pentru badge-ul de alertă

        $data_inceput_dt = new DateTime($cursa['data_inceput']);
        $data_estimata_sfarsit_dt = $cursa['data_estimata_sfarsit'] ? new DateTime($cursa['data_estimata_sfarsit']) : null;

        // Alerte pentru curse programate
        if ($cursa['status'] == 'Programată') {
            if ($data_inceput_dt < $now_dt) {
                $alert_type = 'Cursă Întârziată';
                $alert_details = 'Cursă programată care ar fi trebuit să înceapă la ' . $data_inceput_dt->format('d.m.Y H:i') . ' dar nu a început.';
                $alert_status_class = 'badge-alert-întârziată';
            } else {
                $interval_start = $now_dt->diff($data_inceput_dt);
                $hours_to_start = $interval_start->h + ($interval_start->days * 24);
                if ($hours_to_start <= 24 && $hours_to_start > 0) {
                    $alert_type = 'Cursă Începe Curând';
                    $alert_details = 'Începe în ' . $hours_to_start . ' ore. Locație: ' . htmlspecialchars($cursa['locatie_plecare']) . ' - ' . htmlspecialchars($cursa['locatie_destinatie']);
                    $alert_status_class = 'badge-alert-curând';
                }
            }
            if (empty($cursa['id_sofer'])) {
                $alert_type = 'Fără Șofer Alocat';
                $alert_details = 'Cursă programată fără șofer alocat.';
                $alert_status_class = 'badge-alert-fără_șofer';
            }
            if (empty($cursa['id_vehicul'])) {
                $alert_type = 'Fără Vehicul Alocat';
                $alert_details = 'Cursă programată fără vehicul alocat.';
                $alert_status_class = 'badge-alert-fără_vehicul';
            }
        } 
        // Alerte pentru curse în desfășurare
        elseif ($cursa['status'] == 'În cursă' || $cursa['status'] == 'Pauză') {
            if ($data_estimata_sfarsit_dt && $data_estimata_sfarsit_dt < $now_dt) {
                $alert_type = 'Cursă Depășită';
                $alert_details = 'Cursă în desfășurare care a depășit ora estimată de finalizare (' . $data_estimata_sfarsit_dt->format('d.m.Y H:i') . ').';
                $alert_status_class = 'badge-alert-depășită';
            }
        }

        if (!empty($alert_type)) {
            $curse_alerte_list[] = [
                'id' => $cursa['id'],
                'id_vehicul' => $cursa['id_vehicul'],
                'model' => $cursa['model'],
                'numar_inmatriculare' => $cursa['numar_inmatriculare'],
                'tip_vehicul' => $cursa['tip_vehicul'],
                'id_sofer' => $cursa['id_sofer'],
                'sofer_nume' => $cursa['sofer_nume'],
                'sofer_prenume' => $cursa['sofer_prenume'],
                'sofer_telefon' => $cursa['sofer_telefon'],
                'sofer_email' => $cursa['sofer_email'],
                'data_inceput' => $cursa['data_inceput'],
                'data_estimata_sfarsit' => $cursa['data_estimata_sfarsit'],
                'locatie_plecare' => $cursa['locatie_plecare'],
                'locatie_destinatie' => $cursa['locatie_destinatie'],
                'status_cursă_curent' => $cursa['status'], // Statusul real al cursei
                'km_parcursi' => $cursa['km_parcursi'],
                'observatii_cursă' => $cursa['observatii'],
                'alert_type' => $alert_type,
                'alert_details' => $alert_details,
                'alert_status_class' => $alert_status_class
            ];
        }
    }
}
$conn->close();

// Tipuri de alerte pentru filtrare
$tipuri_alerte_filter = [
    'Cursă Întârziată', 'Cursă Începe Curând', 'Fără Șofer Alocat', 
    'Fără Vehicul Alocat', 'Cursă Depășită'
];

// Statusuri angajați pentru filtrare (pentru a filtra șoferii)
$statusuri_angajati_filter = ['Activ', 'Inactiv', 'Concediu', 'Suspendat', 'Demisionat'];

// Tipuri de vehicule pentru filtrare (extrage din lista de vehicule pentru a fi dinamice)
$tipuri_vehicul_list = array_unique(array_column($vehicule_list, 'tip'));
sort($tipuri_vehicul_list);
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Alerte Curse</title>

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

    /* Stiluri specifice pentru tabelul de alerte curse */
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
    /* Badge-uri pentru statusul alertelor */
    .badge-alert-întârziată { background-color: #dc3545 !important; color: #fff !important; }
    .badge-alert-curând { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-alert-fără_șofer { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-alert-fără_vehicul { background-color: #6c757d !important; color: #fff !important; }
    .badge-alert-depășită { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-alert-în_cursă { background-color: #0d6efd !important; color: #fff !important; }
    .badge-alert-valabil { background-color: #28a745 !important; color: #fff !important; }

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
            <div class="breadcrumb-title pe-3">Notificări</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Alerte Curse</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Alerte Curse</h4>
                        <p class="text-muted">Alerte legate de cursele programate sau în desfășurare care necesită atenție.</p>
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
                                    <?php foreach ($tipuri_vehicul_list as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterSofer" class="form-label">Filtrează după Șofer:</label>
                                <select class="form-select" id="filterSofer">
                                    <option value="all">Toți Șoferii</option>
                                    <?php foreach ($angajati_list as $angajat): ?>
                                        <option value="<?php echo $angajat['id']; ?>"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterTipAlerta" class="form-label">Filtrează după Tip Alertă:</label>
                                <select class="form-select" id="filterTipAlerta">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_alerte_filter as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterStatusCursa" class="form-label">Filtrează după Status Cursă:</label>
                                <select class="form-select" id="filterStatusCursa">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach (['Programată', 'În cursă', 'Pauză'] as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta locație, observații...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Alertelor Curse -->
                        <?php if (empty($curse_alerte_list)): ?>
                            <div class="alert alert-info">Nu există alerte de curse în acest moment.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cursă</th>
                                            <th>Vehicul</th>
                                            <th>Șofer</th>
                                            <th>Tip Alertă</th>
                                            <th>Detalii Alertă</th>
                                            <th>Status Cursă</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="curseAlerteTableBody">
                                        <?php foreach ($curse_alerte_list as $alert): ?>
                                            <tr 
                                                data-id="<?php echo $alert['id']; ?>"
                                                data-id-vehicul="<?php echo $alert['id_vehicul']; ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($alert['tip_vehicul'] ?? ''); ?>"
                                                data-id-sofer="<?php echo htmlspecialchars($alert['id_sofer'] ?? ''); ?>"
                                                data-sofer-telefon="<?php echo htmlspecialchars($alert['sofer_telefon'] ?? ''); ?>"
                                                data-sofer-email="<?php echo htmlspecialchars($alert['sofer_email'] ?? ''); ?>"
                                                data-data-inceput="<?php echo htmlspecialchars($alert['data_inceput']); ?>"
                                                data-data-estimata-sfarsit="<?php echo htmlspecialchars($alert['data_estimata_sfarsit'] ?? ''); ?>"
                                                data-locatie-plecare="<?php echo htmlspecialchars($alert['locatie_plecare']); ?>"
                                                data-locatie-destinatie="<?php echo htmlspecialchars($alert['locatie_destinatie']); ?>"
                                                data-status-cursă-curent="<?php echo htmlspecialchars($alert['status_cursă_curent']); ?>"
                                                data-km-parcursi="<?php echo htmlspecialchars($alert['km_parcursi']); ?>"
                                                data-observatii-cursă="<?php echo htmlspecialchars($alert['observatii_cursă']); ?>"
                                                data-alert-type="<?php echo htmlspecialchars($alert['alert_type']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars(
                                                    $alert['model'] . ' ' . $alert['numar_inmatriculare'] . ' ' . $alert['tip_vehicul'] . ' ' .
                                                    ($alert['sofer_nume'] . ' ' . $alert['sofer_prenume']) . ' ' . $alert['locatie_plecare'] . ' ' . $alert['locatie_destinatie'] . ' ' .
                                                    $alert['alert_type'] . ' ' . $alert['alert_details'] . ' ' . $alert['status_cursă_curent']
                                                )); ?>"
                                            >
                                                <td data-label="Cursă:"><?php echo htmlspecialchars($alert['locatie_plecare'] . ' - ' . $alert['locatie_destinatie']); ?></td>
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($alert['model'] . ' (' . $alert['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Șofer:"><?php echo htmlspecialchars($alert['sofer_nume'] ? $alert['sofer_nume'] . ' ' . $alert['sofer_prenume'] : 'N/A'); ?></td>
                                                <td data-label="Tip Alertă:"><span class="badge <?php echo htmlspecialchars($alert['alert_status_class']); ?>"><?php echo htmlspecialchars($alert['alert_type']); ?></span></td>
                                                <td data-label="Detalii Alertă:"><?php echo htmlspecialchars(mb_strimwidth($alert['alert_details'], 0, 50, "...")); ?></td>
                                                <td data-label="Status Cursă:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $alert['status_cursă_curent'])); ?>"><?php echo htmlspecialchars($alert['status_cursă_curent']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#viewCursaDetailsModal">Detalii Cursă</button>
                                                    <button type="button" class="btn btn-sm btn-outline-success send-whatsapp-btn mb-1 w-100" data-cursă-id="<?php echo $alert['id']; ?>">Trimite WhatsApp</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary send-email-btn w-100" data-cursă-id="<?php echo $alert['id']; ?>">Trimite Email</button>
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

<!-- Modal Vizualizare Detalii Cursă (Read-only) -->
<div class="modal fade" id="viewCursaDetailsModal" tabindex="-1" aria-labelledby="viewCursaDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCursaDetailsModalLabel">Detalii Cursă</h5>
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
                        <label class="form-label">Șofer:</label>
                        <p class="form-control-plaintext text-white" id="detailSofer"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefon Șofer:</label>
                        <p class="form-control-plaintext text-white" id="detailSoferTelefon"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Șofer:</label>
                        <p class="form-control-plaintext text-white" id="detailSoferEmail"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Locație Plecare:</label>
                        <p class="form-control-plaintext text-white" id="detailLocatiePlecare"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Locație Destinație:</label>
                        <p class="form-control-plaintext text-white" id="detailLocatieDestinatie"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dată/Ora Început:</label>
                        <p class="form-control-plaintext text-white" id="detailDataInceput"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dată/Ora Estimată Sfârșit:</label>
                        <p class="form-control-plaintext text-white" id="detailDataEstimataSfarsit"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Km Parcurși:</label>
                        <p class="form-control-plaintext text-white" id="detailKmParcursi"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status Cursă:</label>
                        <p class="form-control-plaintext text-white" id="detailStatusCursa"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observații Cursă:</label>
                        <p class="form-control-plaintext text-white" id="detailObservatiiCursa"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tip Alertă:</label>
                        <p class="form-control-plaintext text-white" id="detailTipAlerta"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Detalii Alertă:</label>
                        <p class="form-control-plaintext text-white" id="detailDetaliiAlerta"></p>
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
    const curseAlerteTableBody = document.getElementById('curseAlerteTableBody');
    const viewCursaDetailsModal = document.getElementById('viewCursaDetailsModal');

    // Date alerte curse pentru JavaScript (populat din PHP)
    const curseAlerteData = <?php echo json_encode($curse_alerte_list); ?>;
    const curseMap = {};
    curseAlerteData.forEach(alert => {
        curseMap[alert.id] = alert;
    });

    // Filtrare Tabel
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipVehicul = document.getElementById('filterTipVehicul');
    const filterSofer = document.getElementById('filterSofer');
    const filterTipAlerta = document.getElementById('filterTipAlerta');
    const filterStatusCursa = document.getElementById('filterStatusCursa');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipVehicul = filterTipVehicul.value;
        const selectedSoferId = filterSofer.value;
        const selectedTipAlerta = filterTipAlerta.value;
        const selectedStatusCursa = filterStatusCursa.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#curseAlerteTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipVehicul = row.getAttribute('data-tip-vehicul');
            const rowSoferId = row.getAttribute('data-id-sofer');
            const rowTipAlerta = row.getAttribute('data-alert-type');
            const rowStatusCursa = row.getAttribute('data-status-cursă-curent');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipVehiculMatch = (selectedTipVehicul === 'all' || rowTipVehicul === selectedTipVehicul);
            const soferMatch = (selectedSoferId === 'all' || rowSoferId === selectedSoferId);
            const tipAlertaMatch = (selectedTipAlerta === 'all' || rowTipAlerta === selectedTipAlerta);
            const statusCursaMatch = (selectedStatusCursa === 'all' || rowStatusCursa === selectedStatusCursa);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipVehiculMatch && soferMatch && tipAlertaMatch && statusCursaMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipVehicul.addEventListener('change', filterTable);
    filterSofer.addEventListener('change', filterTable);
    filterTipAlerta.addEventListener('change', filterTable);
    filterStatusCursa.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterTable(); // Rulează la încărcarea paginii


    // Deschide modalul pentru vizualizarea detaliilor
    curseAlerteTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-details-btn')) {
            const row = e.target.closest('tr');
            const cursaId = row.getAttribute('data-id');
            const cursa = curseMap[cursaId]; // Preluăm obiectul complet din mapă

            if (cursa) {
                document.getElementById('detailVehicul').textContent = `${cursa.model} (${cursa.numar_inmatriculare})`;
                document.getElementById('detailTipVehicul').textContent = cursa.tip_vehicul || 'N/A';
                document.getElementById('detailSofer').textContent = cursa.sofer_nume ? `${cursa.sofer_nume} ${cursa.sofer_prenume}` : 'N/A';
                document.getElementById('detailSoferTelefon').textContent = cursa.sofer_telefon || 'N/A';
                document.getElementById('detailSoferEmail').textContent = cursa.sofer_email || 'N/A';
                document.getElementById('detailLocatiePlecare').textContent = cursa.locatie_plecare;
                document.getElementById('detailLocatieDestinatie').textContent = cursa.locatie_destinatie;
                document.getElementById('detailDataInceput').textContent = new Date(cursa.data_inceput).toLocaleString('ro-RO');
                document.getElementById('detailDataEstimataSfarsit').textContent = cursa.data_estimata_sfarsit ? new Date(cursa.data_estimata_sfarsit).toLocaleString('ro-RO') : 'N/A';
                document.getElementById('detailKmParcursi').textContent = parseFloat(cursa.km_parcursi).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' km';
                document.getElementById('detailStatusCursa').textContent = cursa.status_cursă_curent;
                document.getElementById('detailObservatiiCursa').textContent = cursa.observatii_cursă || 'N/A';
                document.getElementById('detailTipAlerta').textContent = cursa.alert_type;
                document.getElementById('detailDetaliiAlerta').textContent = cursa.alert_details;

                const viewCursaDetailsModalInstance = new bootstrap.Modal(viewCursaDetailsModal);
                viewCursaDetailsModalInstance.show();
            }
        }
    });

    // Logica pentru butoanele de notificare (WhatsApp și Email)
    curseAlerteTableBody.addEventListener('click', function(e) {
        const targetBtn = e.target.closest('.send-whatsapp-btn, .send-email-btn');
        if (!targetBtn) return;

        const row = targetBtn.closest('tr');
        const cursaId = row.getAttribute('data-id');
        const cursa = curseMap[cursaId];

        if (!cursa) {
            console.error("Course data not found in map for ID:", cursaId);
            alert("Eroare: Detaliile cursei nu au putut fi preluate pentru notificare.");
            return;
        }

        const vehicleInfo = `${cursa.model} (${cursa.numar_inmatriculare})`;
        const soferInfo = cursa.sofer_nume ? `${cursa.sofer_nume} ${cursa.sofer_prenume}` : 'N/A';
        const tipAlerta = cursa.alert_type;
        const detaliiAlerta = cursa.alert_details;
        const locatiePlecare = cursa.locatie_plecare;
        const locatieDestinatie = cursa.locatie_destinatie;
        const dataInceput = new Date(cursa.data_inceput).toLocaleString('ro-RO');
        const statusCursaCurent = cursa.status_cursă_curent;

        // Contacte (prioritar șoferul alocat, altfel un contact implicit)
        const recipientPhone = cursa.sofer_telefon || '407xxxxxxxx'; // Număr implicit
        const recipientEmail = cursa.sofer_email || 'dispecer@companie.com'; // Email implicit

        const message = `Salut! Ai o alertă pentru cursa ${locatiePlecare} - ${locatieDestinatie} (Vehicul: ${vehicleInfo}, Șofer: ${soferInfo}). Tip alertă: ${tipAlerta}. Detalii: ${detaliiAlerta}. Status Cursă: ${statusCursaCurent}. Te rog să verifici.`;
        const emailSubject = `Alertă Cursă: ${tipAlerta} pentru ${vehicleInfo}`;

        if (targetBtn.classList.contains('send-whatsapp-btn')) {
            if (recipientPhone && recipientPhone !== 'N/A') {
                const whatsappUrl = `https://wa.me/${recipientPhone}?text=${encodeURIComponent(message)}`;
                window.open(whatsappUrl, '_blank');
            } else {
                alert('Numărul de telefon al șoferului/destinatarului nu este disponibil pentru WhatsApp.');
            }
        } else if (targetBtn.classList.contains('send-email-btn')) {
            if (recipientEmail && recipientEmail !== 'N/A') {
                const emailUrl = `mailto:${recipientEmail}?subject=${encodeURIComponent(emailSubject)}&body=${encodeURIComponent(message)}`;
                window.open(emailUrl, '_blank');
            } else {
                alert('Adresa de email a șoferului/destinatarului nu este disponibilă.');
            }
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
