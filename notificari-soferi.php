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

// Preluăm setările de notificare (ex: câte zile înainte de expirare documente șoferi)
$notifica_expirare_sofer_documente_zile = 30; // Valoare implicită, poate fi configurată în DB

$today_dt = new DateTime();
$future_date_dt = (clone $today_dt)->modify('+' . $notifica_expirare_sofer_documente_zile . ' days');
$today_str = $today_dt->format('Y-m-d');
$future_date_str = $future_date_dt->format('Y-m-d');


// Preluăm lista de angajați (șoferi) pentru dropdown-uri de filtrare
$angajati_list = [];
$stmt_angajati = $conn->prepare("SELECT id, nume, prenume, functie, telefon, email FROM angajati ORDER BY nume ASC, prenume ASC");
if ($stmt_angajati) {
    $stmt_angajati->execute();
    $result_angajati = $stmt_angajati->get_result();
    while ($row = $result_angajati->fetch_assoc()) {
        $angajati_list[] = $row;
    }
    $stmt_angajati->close();
}

// Preluăm toate alertele relevante pentru șoferi
$soferi_alerte_list = [];

// Alerte pentru documente șoferi (Permis, Fișă medicală, Aviz psihologic)
$sql_driver_docs_alerts = "
    SELECT 
        a.id, a.nume, a.prenume, a.functie, a.status, a.telefon, a.email,
        a.data_expirare_permis, a.data_valabilitate_fisa_medicala, a.data_valabilitate_aviz_psihologic
    FROM 
        angajati a
    WHERE 
        a.functie = 'Sofer' AND (
            a.data_expirare_permis <= ? OR (a.data_expirare_permis BETWEEN ? AND ?) OR
            a.data_valabilitate_fisa_medicala <= ? OR (a.data_valabilitate_fisa_medicala BETWEEN ? AND ?) OR
            a.data_valabilitate_aviz_psihologic <= ? OR (a.data_valabilitate_aviz_psihologic BETWEEN ? AND ?)
        )
    ORDER BY 
        a.nume ASC, a.prenume ASC
";
$stmt_driver_docs_alerts = $conn->prepare($sql_driver_docs_alerts);
if ($stmt_driver_docs_alerts) {
    $stmt_driver_docs_alerts->bind_param("sssssssss", 
        $today_str, $today_str, $future_date_str,
        $today_str, $today_str, $future_date_str,
        $today_str, $today_str, $future_date_str
    );
    $stmt_driver_docs_alerts->execute();
    $result_driver_docs_alerts = $stmt_driver_docs_alerts->get_result();
    while ($row = $result_driver_docs_alerts->fetch_assoc()) {
        // Pentru fiecare șofer, identificăm ce documente expiră
        $driver_alerts = [
            'id' => $row['id'],
            'nume' => $row['nume'],
            'prenume' => $row['prenume'],
            'functie' => $row['functie'],
            'telefon' => $row['telefon'],
            'email' => $row['email'],
            'status_angajat' => $row['status'],
            'document_alerts' => []
        ];

        // Permis de conducere
        if (!empty($row['data_expirare_permis'])) {
            $permis_dt = new DateTime($row['data_expirare_permis']);
            $interval = $today_dt->diff($permis_dt);
            $days_remaining = (int)$interval->format('%r%a');
            if ($days_remaining < 0 || ($days_remaining <= $notifica_expirare_sofer_documente_zile && $days_remaining >= 0)) {
                $driver_alerts['document_alerts'][] = [
                    'type' => 'Permis de Conducere',
                    'expiration_date' => $row['data_expirare_permis'],
                    'days_remaining' => $days_remaining
                ];
            }
        }
        // Fișă medicală
        if (!empty($row['data_valabilitate_fisa_medicala'])) {
            $fisa_dt = new DateTime($row['data_valabilitate_fisa_medicala']);
            $interval = $today_dt->diff($fisa_dt);
            $days_remaining = (int)$interval->format('%r%a');
            if ($days_remaining < 0 || ($days_remaining <= $notifica_expirare_sofer_documente_zile && $days_remaining >= 0)) {
                $driver_alerts['document_alerts'][] = [
                    'type' => 'Fișă Medicală',
                    'expiration_date' => $row['data_valabilitate_fisa_medicala'],
                    'days_remaining' => $days_remaining
                ];
            }
        }
        // Aviz psihologic
        if (!empty($row['data_valabilitate_aviz_psihologic'])) {
            $aviz_dt = new DateTime($row['data_valabilitate_aviz_psihologic']);
            $interval = $today_dt->diff($aviz_dt);
            $days_remaining = (int)$interval->format('%r%a');
            if ($days_remaining < 0 || ($days_remaining <= $notifica_expirare_sofer_documente_zile && $days_remaining >= 0)) {
                $driver_alerts['document_alerts'][] = [
                    'type' => 'Aviz Psihologic',
                    'expiration_date' => $row['data_valabilitate_aviz_psihologic'],
                    'days_remaining' => $days_remaining
                ];
            }
        }

        if (!empty($driver_alerts['document_alerts'])) {
            $soferi_alerte_list[] = $driver_alerts;
        }
    }
    $stmt_driver_docs_alerts->close();
}

// Alerte pentru șoferi în concediu (dacă tabelul concedii există)
if ($conn->query("SHOW TABLES LIKE 'concedii'")->num_rows > 0) {
    $sql_concedii_alerts = "
        SELECT 
            c.id_angajat, c.tip_concediu, c.data_inceput, c.data_sfarsit, c.status,
            a.nume, a.prenume, a.functie, a.telefon, a.email
        FROM 
            concedii c
        JOIN
            angajati a ON c.id_angajat = a.id
        WHERE 
            c.status = 'Aprobat' AND (c.data_inceput <= ? AND c.data_sfarsit >= ?)
        ORDER BY 
            c.data_inceput ASC
    ";
    $stmt_concedii_alerts = $conn->prepare($sql_concedii_alerts);
    if ($stmt_concedii_alerts) {
        $stmt_concedii_alerts->bind_param("ss", $today_str, $today_str);
        $stmt_concedii_alerts->execute();
        $result_concedii_alerts = $stmt_concedii_alerts->get_result();
        while ($row = $result_concedii_alerts->fetch_assoc()) {
            // Adăugăm o alertă de tip "Concediu"
            $soferi_alerte_list[] = [
                'id' => $row['id_angajat'],
                'nume' => $row['nume'],
                'prenume' => $row['prenume'],
                'functie' => $row['functie'],
                'telefon' => $row['telefon'],
                'email' => $row['email'],
                'status_angajat' => $row['status'], // Statusul angajatului
                'concediu_info' => [
                    'type' => $row['tip_concediu'],
                    'start_date' => $row['data_inceput'],
                    'end_date' => $row['data_sfarsit']
                ]
            ];
        }
        $stmt_concedii_alerts->close();
    }
}

$conn->close();

// Tipuri de alerte pentru filtrare
$tipuri_alerte = ['Document Expirat', 'Document Expiră Curând', 'În Concediu', 'În Cursă']; // "În Cursă" va fi din statusul angajatului

// Statusuri angajați pentru filtrare
$statusuri_angajati_filter = ['Activ', 'Inactiv', 'Concediu', 'Suspendat', 'Demisionat'];

// Tipuri de vehicule pentru filtrare (extrage din lista de vehicule pentru a fi dinamice)
$tipuri_vehicul_list = array_unique(array_column($vehicule_list, 'tip'));
sort($tipuri_vehicul_list);
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Alerte Șoferi</title>

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

    /* Stiluri specifice pentru tabelul de alerte șoferi */
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
    .badge-alert-expirat { background-color: #dc3545 !important; color: #fff !important; }
    .badge-alert-expiră_curând { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-alert-în_concediu { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-alert-în_cursă { background-color: #0d6efd !important; color: #fff !important; }
    .badge-alert-valabil { background-color: #28a745 !important; color: #fff !important; } /* Pentru statusuri "ok" */

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
                        <li class="breadcrumb-item active" aria-current="page">Alerte Șoferi</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Alerte Șoferi</h4>
                        <p class="text-muted">Alerte legate de documente expirate/care expiră curând, concedii sau alte statusuri relevante pentru șoferi.</p>
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
                            <div class="col-md-4 mb-3">
                                <label for="filterAngajat" class="form-label">Filtrează după Angajat:</label>
                                <select class="form-select" id="filterAngajat">
                                    <option value="all">Toți Angajații</option>
                                    <?php foreach ($angajati_list as $angajat): ?>
                                        <option value="<?php echo $angajat['id']; ?>"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterTipAlerta" class="form-label">Filtrează după Tip Alertă:</label>
                                <select class="form-select" id="filterTipAlerta">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_alerte as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatusAngajat" class="form-label">Filtrează după Status Angajat:</label>
                                <select class="form-select" id="filterStatusAngajat">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_angajati_filter as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume, telefon, email, tip alertă...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Alertelor Șoferi -->
                        <?php if (empty($soferi_alerte_list)): ?>
                            <div class="alert alert-info">Nu există alerte active pentru șoferi în acest moment.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Șofer</th>
                                            <th>Funcție</th>
                                            <th>Status Angajat</th>
                                            <th>Tip Alertă</th>
                                            <th>Detalii Alertă</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="soferiAlerteTableBody">
                                        <?php foreach ($soferi_alerte_list as $alert): 
                                            $alert_type_display = 'Necunoscut';
                                            $alert_details_display = '';
                                            $alert_status_class = 'badge-alert-valabil'; // Default

                                            if (isset($alert['document_alerts'])) {
                                                foreach ($alert['document_alerts'] as $doc_alert) {
                                                    $alert_type_display = $doc_alert['type'];
                                                    $exp_date_dt = new DateTime($doc_alert['expiration_date']);
                                                    $alert_details_display = "Expiră la: " . $exp_date_dt->format('d.m.Y');
                                                    if ($doc_alert['days_remaining'] < 0) {
                                                        $alert_type_display .= ' (Expirat)';
                                                        $alert_status_class = 'badge-alert-expirat';
                                                    } elseif ($doc_alert['days_remaining'] <= $notifica_expirare_sofer_documente_zile) {
                                                        $alert_type_display .= ' (Expiră curând)';
                                                        $alert_status_class = 'badge-alert-expiră_curând';
                                                    }
                                                    // Adăugăm fiecare alertă de document ca rând separat pentru claritate
                                                    // Sau le concatenăm dacă vrem un singur rând per șofer
                                                    // Aici le vom concatena pentru a menține un rând per șofer, dar cu detalii multiple
                                                    $alert_details_display .= " | Status: " . $alert_type_display;
                                                }
                                                $alert_type_display = "Documente"; // Categoria generală
                                            } elseif (isset($alert['concediu_info'])) {
                                                $alert_type_display = 'În Concediu';
                                                $start_dt = new DateTime($alert['concediu_info']['start_date']);
                                                $end_dt = new DateTime($alert['concediu_info']['end_date']);
                                                $alert_details_display = "Tip: " . htmlspecialchars($alert['concediu_info']['type']) . " (" . $start_dt->format('d.m.Y') . " - " . $end_dt->format('d.m.Y') . ")";
                                                $alert_status_class = 'badge-alert-în_concediu';
                                            }
                                            // Adaugă alte tipuri de alerte aici (ex: în cursă, probleme)
                                            // Pentru "În Cursă" vom folosi direct statusul angajatului
                                            if ($alert['status_angajat'] == 'În cursă') {
                                                $alert_type_display = 'În Cursă';
                                                $alert_details_display = 'Șoferul este în prezent într-o cursă.';
                                                $alert_status_class = 'badge-alert-în_cursă';
                                            }

                                            // Search text pentru filtrare
                                            $search_text = strtolower(htmlspecialchars(
                                                $alert['nume'] . ' ' . $alert['prenume'] . ' ' . 
                                                $alert['functie'] . ' ' . $alert['telefon'] . ' ' . $alert['email'] . ' ' . 
                                                $alert_type_display . ' ' . $alert_details_display . ' ' . $alert['status_angajat']
                                            ));
                                        ?>
                                            <tr 
                                                data-id="<?php echo $alert['id']; ?>"
                                                data-nume="<?php echo htmlspecialchars($alert['nume']); ?>"
                                                data-prenume="<?php echo htmlspecialchars($alert['prenume']); ?>"
                                                data-functie="<?php echo htmlspecialchars($alert['functie']); ?>"
                                                data-telefon="<?php echo htmlspecialchars($alert['telefon'] ?? ''); ?>"
                                                data-email="<?php echo htmlspecialchars($alert['email'] ?? ''); ?>"
                                                data-status-angajat="<?php echo htmlspecialchars($alert['status_angajat']); ?>"
                                                data-tip-alerta="<?php echo htmlspecialchars($alert_type_display); ?>"
                                                data-search-text="<?php echo $search_text; ?>"
                                            >
                                                <td data-label="Șofer:"><?php echo htmlspecialchars($alert['nume'] . ' ' . $alert['prenume']); ?></td>
                                                <td data-label="Funcție:"><?php echo htmlspecialchars($alert['functie']); ?></td>
                                                <td data-label="Status Angajat:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $alert['status_angajat'])); ?>"><?php echo htmlspecialchars($alert['status_angajat']); ?></span></td>
                                                <td data-label="Tip Alertă:"><span class="badge <?php echo $alert_status_class; ?>"><?php echo htmlspecialchars($alert_type_display); ?></span></td>
                                                <td data-label="Detalii Alertă:"><?php echo htmlspecialchars($alert_details_display); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#viewSoferDetailsModal">Detalii</button>
                                                    <button type="button" class="btn btn-sm btn-outline-success send-whatsapp-btn mb-1 w-100" data-angajat-id="<?php echo $alert['id']; ?>">Trimite WhatsApp</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary send-email-btn w-100" data-angajat-id="<?php echo $alert['id']; ?>">Trimite Email</button>
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

<!-- Modal Vizualizare Detalii Șofer (Read-only) -->
<div class="modal fade" id="viewSoferDetailsModal" tabindex="-1" aria-labelledby="viewSoferDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSoferDetailsModalLabel">Detalii Șofer: <span id="detailSoferNume"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Funcție:</label>
                        <p class="form-control-plaintext text-white" id="detailSoferFunctie"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefon:</label>
                        <p class="form-control-plaintext text-white" id="detailSoferTelefon"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email:</label>
                        <p class="form-control-plaintext text-white" id="detailSoferEmail"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status Angajat:</label>
                        <p class="form-control-plaintext text-white" id="detailSoferStatus"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alerte Documente:</label>
                        <ul class="list-unstyled text-white" id="detailSoferDocumentAlerts"></ul>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Informații Concediu:</label>
                        <p class="form-control-plaintext text-white" id="detailSoferConcediuInfo"></p>
                    </div>
                    <!-- Poți adăuga mai multe detalii aici dacă vrei să le preiei din baza de date -->
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
    const soferiAlerteTableBody = document.getElementById('soferiAlerteTableBody');
    const viewSoferDetailsModal = document.getElementById('viewSoferDetailsModal');

    // Date alerte șoferi pentru JavaScript (populat din PHP)
    const soferiAlerteData = <?php echo json_encode($soferi_alerte_list); ?>;
    const angajatiMap = {}; // Mapăm angajații după ID pentru acces rapid
    soferiAlerteData.forEach(alert => {
        angajatiMap[alert.id] = alert;
    });

    // Filtrare Tabel
    const filterAngajat = document.getElementById('filterAngajat');
    const filterTipAlerta = document.getElementById('filterTipAlerta');
    const filterStatusAngajat = document.getElementById('filterStatusAngajat');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedAngajatId = filterAngajat.value;
        const selectedTipAlerta = filterTipAlerta.value;
        const selectedStatusAngajat = filterStatusAngajat.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#soferiAlerteTableBody tr').forEach(row => {
            const rowAngajatId = row.getAttribute('data-id');
            const rowTipAlerta = row.getAttribute('data-tip-alerta');
            const rowStatusAngajat = row.getAttribute('data-status-angajat');
            const rowSearchText = row.getAttribute('data-search-text');

            const angajatMatch = (selectedAngajatId === 'all' || rowAngajatId === selectedAngajatId);
            const tipAlertaMatch = (selectedTipAlerta === 'all' || rowTipAlerta === selectedTipAlerta);
            const statusAngajatMatch = (selectedStatusAngajat === 'all' || rowStatusAngajat === selectedStatusAngajat);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (angajatMatch && tipAlertaMatch && statusAngajatMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterAngajat.addEventListener('change', filterTable);
    filterTipAlerta.addEventListener('change', filterTable);
    filterStatusAngajat.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterTable(); // Rulează la încărcarea paginii


    // Deschide modalul pentru vizualizarea detaliilor
    soferiAlerteTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-details-btn')) {
            const row = e.target.closest('tr');
            const angajatId = row.getAttribute('data-id');
            const alertData = angajatiMap[angajatId]; // Preluăm obiectul complet din mapă

            if (alertData) {
                document.getElementById('detailSoferNume').textContent = `${alertData.nume} ${alertData.prenume}`;
                document.getElementById('detailSoferFunctie').textContent = alertData.functie;
                document.getElementById('detailSoferTelefon').textContent = alertData.telefon || 'N/A';
                document.getElementById('detailSoferEmail').textContent = alertData.email || 'N/A';
                document.getElementById('detailSoferStatus').textContent = alertData.status_angajat;

                const docAlertsList = document.getElementById('detailSoferDocumentAlerts');
                docAlertsList.innerHTML = ''; // Curățăm lista

                if (alertData.document_alerts && alertData.document_alerts.length > 0) {
                    alertData.document_alerts.forEach(doc_alert => {
                        const li = document.createElement('li');
                        let statusText = '';
                        if (doc_alert.days_remaining < 0) {
                            statusText = `Expirat`;
                        } else if (doc_alert.days_remaining <= <?php echo $notifica_expirare_sofer_documente_zile; ?>) {
                            statusText = `Expiră curând (${doc_alert.days_remaining} zile)`;
                        }
                        li.textContent = `${doc_alert.type}: ${new Date(doc_alert.expiration_date).toLocaleDateString('ro-RO')} (${statusText})`;
                        docAlertsList.appendChild(li);
                    });
                } else {
                    const li = document.createElement('li');
                    li.textContent = 'Niciun document cu alertă.';
                    docAlertsList.appendChild(li);
                }

                document.getElementById('detailSoferConcediuInfo').textContent = alertData.concediu_info ? 
                    `Tip: ${alertData.concediu_info.type} (${new Date(alertData.concediu_info.start_date).toLocaleDateString('ro-RO')} - ${new Date(alertData.concediu_info.end_date).toLocaleDateString('ro-RO')})` : 'N/A';

                const viewSoferDetailsModalInstance = new bootstrap.Modal(viewSoferDetailsModal);
                viewSoferDetailsModalInstance.show();
            }
        }
    });

    // Logica pentru butoanele de notificare (WhatsApp și Email)
    soferiAlerteTableBody.addEventListener('click', function(e) {
        const targetBtn = e.target.closest('.send-whatsapp-btn, .send-email-btn');
        if (!targetBtn) return;

        const row = targetBtn.closest('tr');
        const angajatId = row.getAttribute('data-id');
        const alertData = angajatiMap[angajatId];

        if (!alertData) {
            console.error("Alert data not found for ID:", angajatId);
            alert("Eroare: Detaliile șoferului nu au putut fi preluate pentru notificare.");
            return;
        }

        const soferNumeComplet = `${alertData.nume} ${alertData.prenume}`;
        const soferTelefon = alertData.telefon;
        const soferEmail = alertData.email;
        const tipAlerta = row.getAttribute('data-tip-alerta');
        const detaliiAlerta = row.querySelector('td[data-label="Detalii Alertă:"]').textContent;

        const message = `Salut, ${soferNumeComplet}! Ai o alertă importantă: ${tipAlerta}. Detalii: ${detaliiAlerta}. Te rugăm să iei măsuri.`;
        const emailSubject = `Alertă Importantă: ${tipAlerta} pentru ${soferNumeComplet}`;

        if (targetBtn.classList.contains('send-whatsapp-btn')) {
            if (soferTelefon) {
                const whatsappUrl = `https://wa.me/${soferTelefon}?text=${encodeURIComponent(message)}`;
                window.open(whatsappUrl, '_blank');
            } else {
                alert('Numărul de telefon al șoferului nu este disponibil pentru WhatsApp.');
            }
        } else if (targetBtn.classList.contains('send-email-btn')) {
            if (soferEmail) {
                const emailUrl = `mailto:${soferEmail}?subject=${encodeURIComponent(emailSubject)}&body=${encodeURIComponent(message)}`;
                window.open(emailUrl, '_blank');
            } else {
                alert('Adresa de email a șoferului nu este disponibilă.');
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
