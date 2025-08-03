<?php
session_start();
require_once 'db_connect.php';
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

// Preluăm lista de clienți pentru dropdown-uri de filtrare și adăugare
$clienti_list = [];
$stmt_clienti = $conn->prepare("SELECT id, nume_companie, persoana_contact FROM clienti WHERE is_deleted = FALSE ORDER BY nume_companie ASC");
if ($stmt_clienti) {
    $stmt_clienti->execute();
    $result_clienti = $stmt_clienti->get_result();
    while ($row = $result_clienti->fetch_assoc()) {
        $clienti_list[] = $row;
    }
    $stmt_clienti->close();
}

// Preluăm suma totală plătită pentru fiecare factură (din plati_clienti)
$suma_platita_per_factura = [];
$sql_sum_plati = "SELECT id_factura, SUM(suma_platita) as total_platit FROM plati_clienti GROUP BY id_factura";
$result_sum_plati = $conn->query($sql_sum_plati);
if ($result_sum_plati) {
    while ($row = $result_sum_plati->fetch_assoc()) {
        $suma_platita_per_factura[$row['id_factura']] = (float)$row['total_platit'];
    }
}

// Preluăm lista de facturi CU DETALII FINANCIARE
$facturi_list = [];
$total_valoare_facturi = 0;
$total_valoare_tva = 0;
$total_cost_asociat = 0;
$total_profit_estimat = 0;
$total_suma_incasata = 0; // From payments
$total_suma_restanta = 0;

$sql_facturi = "
    SELECT f.*, c.nume_companie, c.persoana_contact
    FROM facturi f
    JOIN clienti c ON f.id_client = c.id
    ORDER BY f.data_emiterii DESC
";
$result_facturi = $conn->query($sql_facturi);
if ($result_facturi) {
    while ($factura = $result_facturi->fetch_assoc()) {
        $factura_id = $factura['id'];
        $valoare_totala = (float)($factura['valoare_totala'] ?? 0);
        $valoare_tva = (float)($factura['valoare_tva'] ?? 0);
        $cost_asociat = (float)($factura['cost_asociat'] ?? 0);
        $moneda = $factura['moneda'] ?? 'RON';

        // Calcul suma incasata si ramasa
        $suma_incasata_curenta = $suma_platita_per_factura[$factura_id] ?? 0;
        $suma_ramasa_de_plata = $valoare_totala - $suma_incasata_curenta;
        if ($suma_ramasa_de_plata < 0) $suma_ramasa_de_plata = 0; // Prevent negative remaining

        // Update factura status based on payment (dynamic status override)
        if ($suma_incasata_curenta >= $valoare_totala && $valoare_totala > 0) {
            $factura['status'] = 'Platita';
        } elseif ($suma_incasata_curenta > 0 && $suma_incasata_curenta < $valoare_totala) {
            $factura['status'] = 'Partial Platita'; // New status for clarity
        } elseif ($valoare_totala > 0 && strtotime($factura['data_scadenta']) < time() && $suma_incasata_curenta == 0) {
            $factura['status'] = 'Restanta';
        }

        // Calcul profit estimat
        $valoare_fara_tva = $valoare_totala - $valoare_tva;
        $profit_estimat = $valoare_fara_tva - $cost_asociat;

        $factura['suma_incasata_curenta'] = $suma_incasata_curenta;
        $factura['suma_ramasa_de_plata'] = $suma_ramasa_de_plata;
        $factura['profit_estimat'] = $profit_estimat;

        $facturi_list[] = $factura;

        // Sumar totaluri
        $total_valoare_facturi += $valoare_totala;
        $total_valoare_tva += $valoare_tva;
        $total_cost_asociat += $cost_asociat;
        $total_profit_estimat += $profit_estimat;
        $total_suma_incasata += $suma_incasata_curenta;
        $total_suma_restanta += $suma_ramasa_de_plata;
    }
}
$conn->close();

// Statusuri pentru filtrare (actualizat cu Partial Platita)
$statusuri_factura = ['Emisa', 'Partial Platita', 'Platita', 'Restanta', 'Anulata'];
?>

<title>NTS TOUR | Facturare Transport</title>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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

    /* Stiluri specifice pentru tabelul de facturi */
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
    .badge-status-emisa { background-color: #0d6efd !important; color: #fff !important; }
    .badge-status-platita { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-partial_platita { background-color: #6c757d !important; color: #fff !important; } /* New status color */
    .badge-status-restanta { background-color: #fd7e14 !important; color: #fff !important; }
    .badge-status-anulata { background-color: #dc3545 !important; color: #fff !important; }

    /* Stiluri pentru cardurile de sumar */
    .summary-card {
        background-color: #1f2538;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: 0 0.5rem 1.2rem rgba(0, 0, 0, 0.2);
    }
    .summary-card .summary-icon {
        font-size: 3rem;
        color: #6a90f1;
        margin-bottom: 0.5rem;
    }
    .summary-card .summary-value {
        font-size: 2rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 0.25rem;
    }
    .summary-card .summary-label {
        font-size: 0.9rem;
        color: #c0c0c0;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }


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
            <div class="breadcrumb-title pe-3">CRM</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i> Acasă</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><i class="bx bx-receipt"></i> Facturare Transport</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Facturi Transport</h4>
                        <p class="text-muted mb-4">Emite, vizualizează și gestionează facturile de transport către clienți.</p>
                        <hr class="mb-4">

                        <?php if ($success_message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                <div><?php echo $success_message; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                <div><?php echo $error_message; ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="row row-cols-1 row-cols-md-4 g-4 mb-5">
                            <div class="col">
                                <div class="summary-card">
                                    <i class="bx bx-money summary-icon text-primary"></i>
                                    <div class="summary-value"><?php echo number_format($total_valoare_facturi, 2, ',', '.') . ' RON'; ?></div>
                                    <div class="summary-label">Total Valoare Facturi</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="summary-card">
                                    <i class="bx bx-credit-card-alt summary-icon text-success"></i>
                                    <div class="summary-value"><?php echo number_format($total_suma_incasata, 2, ',', '.') . ' RON'; ?></div>
                                    <div class="summary-label">Total Sumă Încasată</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="summary-card">
                                    <i class="bx bx-time summary-icon text-warning"></i>
                                    <div class="summary-value"><?php echo number_format($total_suma_restanta, 2, ',', '.') . ' RON'; ?></div>
                                    <div class="summary-label">Total Sumă Restantă</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="summary-card">
                                    <i class="bx bx-line-chart summary-icon text-info"></i>
                                    <div class="summary-value"><?php echo number_format($total_profit_estimat, 2, ',', '.') . ' RON'; ?></div>
                                    <div class="summary-label">Profit Estimat Total</div>
                                </div>
                            </div>
                        </div>


                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditFacturaModal" id="addFacturaBtn">
                            <i class="bx bx-plus-circle me-1"></i> Adaugă Factură Nouă
                        </button>

                        <div class="row mb-4 filter-section card p-3">
                            <h5 class="card-title mb-3">Filtrează Facturile</h5>
                            <div class="col-md-3 mb-3">
                                <label for="filterClient" class="form-label"><i class="bx bx-user me-1"></i> Filtrează după Client:</label>
                                <select class="form-select" id="filterClient">
                                    <option value="all">Toți Clienții</option>
                                    <?php foreach ($clienti_list as $client): ?>
                                        <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['nume_companie'] . ' (' . $client['persoana_contact'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterStatus" class="form-label"><i class="bx bx-info-circle me-1"></i> Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_factura as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-3 mb-3">
                                <label for="filterStartDate" class="form-label"><i class="bx bx-calendar-event me-1"></i> Dată Emitere de la:</label>
                                <input type="date" class="form-control" id="filterStartDate">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterEndDate" class="form-label"><i class="bx bx-calendar-event me-1"></i> Dată Emitere până la:</label>
                                <input type="date" class="form-control" id="filterEndDate">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label"><i class="bx bx-search me-1"></i> Caută:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta număr factură, client...">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-secondary" id="resetFiltersBtn"><i class="bx bx-reset me-1"></i> Resetează Filtre</button>
                            </div>
                        </div>

                        <?php if (empty($facturi_list)): ?>
                            <div class="alert alert-info text-center py-4">
                                <i class="bx bx-info-circle" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                Nu există facturi înregistrate.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Nr. Factură</th>
                                            <th>Dată Emitere</th>
                                            <th>Dată Scadență</th>
                                            <th>Valoare Totală</th>
                                            <th>Valoare TVA</th>
                                            <th>Cost Asociat</th>
                                            <th>Profit Estimat</th>
                                            <th>Plătit</th>
                                            <th>Rămas de Plată</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="facturiTableBody">
                                        <?php foreach ($facturi_list as $factura): ?>
                                            <tr 
                                                data-id="<?php echo $factura['id']; ?>"
                                                data-id-client="<?php echo $factura['id_client']; ?>"
                                                data-numar-factura="<?php echo htmlspecialchars($factura['numar_factura']); ?>"
                                                data-data-emiterii="<?php echo htmlspecialchars($factura['data_emiterii']); ?>"
                                                data-data-scadenta="<?php echo htmlspecialchars($factura['data_scadenta']); ?>"
                                                data-valoare-totala="<?php echo htmlspecialchars($factura['valoare_totala']); ?>"
                                                data-valoare-tva="<?php echo htmlspecialchars($factura['valoare_tva']); ?>"
                                                data-cost-asociat="<?php echo htmlspecialchars($factura['cost_asociat']); ?>"
                                                data-moneda="<?php echo htmlspecialchars($factura['moneda']); ?>"
                                                data-status="<?php echo htmlspecialchars($factura['status']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($factura['observatii_factura'] ?? ''); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($factura['numar_factura'] . ' ' . $factura['nume_companie'] . ' ' . $factura['persoana_contact'])); ?>"
                                                data-suma-incasata-curenta="<?php echo htmlspecialchars($factura['suma_incasata_curenta']); ?>"
                                                data-suma-ramasa-de-plata="<?php echo htmlspecialchars($factura['suma_ramasa_de_plata']); ?>"
                                                data-profit-estimat="<?php echo htmlspecialchars($factura['profit_estimat']); ?>"
                                            >
                                                <td data-label="Client:"><?php echo htmlspecialchars($factura['nume_companie'] . ' (' . $factura['persoana_contact'] . ')'); ?></td>
                                                <td data-label="Nr. Factură:"><?php echo htmlspecialchars($factura['numar_factura']); ?></td>
                                                <td data-label="Dată Emitere:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($factura['data_emiterii']))); ?></td>
                                                <td data-label="Dată Scadență:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($factura['data_scadenta']))); ?></td>
                                                <td data-label="Valoare Totală:"><?php echo number_format($factura['valoare_totala'], 2, ',', '.') . ' ' . htmlspecialchars($factura['moneda']); ?></td>
                                                <td data-label="Valoare TVA:"><?php echo number_format($factura['valoare_tva'], 2, ',', '.') . ' ' . htmlspecialchars($factura['moneda']); ?></td>
                                                <td data-label="Cost Asociat:"><?php echo number_format($factura['cost_asociat'], 2, ',', '.') . ' ' . htmlspecialchars($factura['moneda']); ?></td>
                                                <td data-label="Profit Estimat:"><?php echo number_format($factura['profit_estimat'], 2, ',', '.') . ' ' . htmlspecialchars($factura['moneda']); ?></td>
                                                <td data-label="Plătit:"><?php echo number_format($factura['suma_incasata_curenta'], 2, ',', '.') . ' ' . htmlspecialchars($factura['moneda']); ?></td>
                                                <td data-label="Rămas de Plată:"><?php echo number_format($factura['suma_ramasa_de_plata'], 2, ',', '.') . ' ' . htmlspecialchars($factura['moneda']); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $factura['status'])); ?>"><?php echo htmlspecialchars($factura['status']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details-factura-btn" title="Vezi detalii"><i class="bx bx-show"></i></button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-factura-btn" data-bs-toggle="modal" data-bs-target="#addEditFacturaModal" title="Editează"><i class="bx bx-edit"></i></button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-factura-btn" data-id="<?php echo $factura['id']; ?>" title="Șterge"><i class="bx bx-trash"></i></button>
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

<div class="modal fade" id="addEditFacturaModal" tabindex="-1" aria-labelledby="addEditFacturaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditFacturaModalLabel"><i class="bx bx-plus-circle me-2"></i> Adaugă Factură Nouă</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="facturaForm" action="process_facturi.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="facturaAction" name="action" value="add">
                    <input type="hidden" id="facturaId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalSelectClient" class="form-label">Client:</label>
                            <select class="form-select" id="modalSelectClient" name="id_client" required>
                                <option value="">Alege un client</option>
                                <?php foreach ($clienti_list as $client): ?>
                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['nume_companie'] . ' (' . $client['persoana_contact'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Te rog selectează un client.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalNumarFactura" class="form-label">Număr Factură: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalNumarFactura" name="numar_factura" required>
                            <div class="invalid-feedback">Te rog introdu numărul facturii.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataEmiterii" class="form-label">Dată Emitere: <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modalDataEmiterii" name="data_emiterii" required>
                            <div class="invalid-feedback">Te rog introdu data emiterii.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataScadenta" class="form-label">Dată Scadență: <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modalDataScadenta" name="data_scadenta" required>
                            <div class="invalid-feedback">Te rog introdu data scadenței.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalValoareTotala" class="form-label">Valoare Totală (cu TVA): <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="modalValoareTotala" name="valoare_totala" required min="0.01">
                            <div class="invalid-feedback">Te rog introdu valoarea totală.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalMoneda" class="form-label">Monedă: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalMoneda" name="moneda" value="RON" required>
                            <div class="invalid-feedback">Te rog introdu moneda.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalValoareTVA" class="form-label">Valoare TVA:</label>
                            <input type="number" step="0.01" class="form-control" id="modalValoareTVA" name="valoare_tva" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="modalCostAsociat" class="form-label">Cost Asociat:</label>
                            <input type="number" step="0.01" class="form-control" id="modalCostAsociat" name="cost_asociat" value="0.00">
                        </div>
                        <div class="col-12">
                            <label for="modalStatus" class="form-label">Status: <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_factura as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Te rog selectează un status.</div>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatiiFactura" class="form-label">Observații Factură:</label>
                            <textarea class="form-control" id="modalObservatiiFactura" name="observatii_factura" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bx bx-x me-1"></i> Anulează</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Salvează Factura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteFacturaModal" tabindex="-1" aria-labelledby="deleteFacturaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteFacturaModalLabel"><i class="bx bx-trash me-2"></i> Confirmă Ștergerea</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi această factură? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteFacturaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteFacturaBtn"><i class="bx bx-trash-alt me-1"></i> Șterge</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewFacturaDetailsModal" tabindex="-1" aria-labelledby="viewFacturaDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewFacturaDetailsModalLabel"><i class="bx bx-info-circle me-2"></i> Detalii Complete Factură</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Client:</label>
                        <p class="form-control-plaintext text-white" id="detailClientName"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Număr Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailNumarFactura"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dată Emitere:</label>
                        <p class="form-control-plaintext text-white" id="detailDataEmiterii"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dată Scadență:</label>
                        <p class="form-control-plaintext text-white" id="detailDataScadenta"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valoare Totală Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailValoareTotala"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valoare TVA:</label>
                        <p class="form-control-plaintext text-white" id="detailValoareTVA"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cost Asociat:</label>
                        <p class="form-control-plaintext text-white" id="detailCostAsociat"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profit Estimat (Factură):</label>
                        <p class="form-control-plaintext text-white" id="detailProfitEstimat"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sumă Plătită Până Acum:</label>
                        <p class="form-control-plaintext text-white" id="detailSumaPlatitaCurenta"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sumă Rămasă de Plată:</label>
                        <p class="form-control-plaintext text-success" id="detailSumaRamasaDePlata"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailStatus"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observații:</label>
                        <p class="form-control-plaintext text-white" id="detailObservatiiFactura"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bx bx-x me-1"></i> Închide</button>
            </div>
        </div>
    </div>
</div>


<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditFacturaModal = document.getElementById('addEditFacturaModal');
    const facturaForm = document.getElementById('facturaForm');
    const addFacturaBtn = document.getElementById('addFacturaBtn');
    const deleteFacturaModal = document.getElementById('deleteFacturaModal');
    const confirmDeleteFacturaBtn = document.getElementById('confirmDeleteFacturaBtn');
    const facturiTableBody = document.getElementById('facturiTableBody');
    const viewFacturaDetailsModal = document.getElementById('viewFacturaDetailsModal');

    // Filtrare
    const filterClient = document.getElementById('filterClient');
    const filterStatus = document.getElementById('filterStatus');
    const filterStartDate = document.getElementById('filterStartDate');
    const filterEndDate = document.getElementById('filterEndDate');
    const filterSearch = document.getElementById('filterSearch');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');


    function filterTable() {
        const selectedClientId = filterClient.value;
        const selectedStatus = filterStatus.value;
        const startDate = filterStartDate.value ? new Date(filterStartDate.value) : null;
        const endDate = filterEndDate.value ? new Date(filterEndDate.value) : null;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#facturiTableBody tr').forEach(row => {
            const rowClientId = row.getAttribute('data-id-client');
            const rowStatus = row.getAttribute('data-status');
            const rowDataEmiterii = new Date(row.getAttribute('data-data-emiterii'));
            const rowSearchText = row.getAttribute('data-search-text');

            const clientMatch = (selectedClientId === 'all' || rowClientId === selectedClientId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const dateMatch = (!startDate || rowDataEmiterii >= startDate) && (!endDate || rowDataEmiterii <= endDate);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (clientMatch && statusMatch && dateMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterClient.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterStartDate.addEventListener('change', filterTable);
    filterEndDate.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);

    resetFiltersBtn.addEventListener('click', function() {
        filterClient.value = 'all';
        filterStatus.value = 'all';
        filterStartDate.value = '';
        filterEndDate.value = '';
        filterSearch.value = '';
        filterTable(); // Apply reset filters
    });


    // Deschide modalul pentru adăugare
    addFacturaBtn.addEventListener('click', function() {
        facturaForm.reset();
        facturaForm.classList.remove('was-validated'); // Remove validation feedback
        document.getElementById('facturaAction').value = 'add';
        document.getElementById('facturaId').value = '';
        document.getElementById('addEditFacturaModalLabel').innerHTML = '<i class="bx bx-plus-circle me-2"></i> Adaugă Factură Nouă';
        
        // Setează data emiterii și scadenței la data curentă implicit
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        document.getElementById('modalDataEmiterii').value = formattedDate;
        
        // Setează data scadenței la 30 de zile de la data curentă
        const scadentaDate = new Date();
        scadentaDate.setDate(now.getDate() + 30); // 30 days from now
        document.getElementById('modalDataScadenta').value = scadentaDate.toISOString().substring(0, 10);

        document.getElementById('modalMoneda').value = 'RON'; // Moneda implicită
        document.getElementById('modalValoareTVA').value = '0.00'; // Default TVA
        document.getElementById('modalCostAsociat').value = '0.00'; // Default Cost
        document.getElementById('modalStatus').value = 'Emisa'; // Default Status
    });

    // Deschide modalul pentru editare
    facturiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-factura-btn') || e.target.closest('.edit-factura-btn')) {
            const btn = e.target.closest('.edit-factura-btn');
            const row = btn.closest('tr');
            facturaForm.classList.remove('was-validated'); // Remove validation feedback
            document.getElementById('facturaAction').value = 'edit';
            document.getElementById('facturaId').value = row.getAttribute('data-id');
            document.getElementById('addEditFacturaModalLabel').innerHTML = '<i class="bx bx-edit me-2"></i> Editează Factura';

            document.getElementById('modalSelectClient').value = row.getAttribute('data-id-client');
            document.getElementById('modalNumarFactura').value = row.getAttribute('data-numar-factura');
            document.getElementById('modalDataEmiterii').value = row.getAttribute('data-data-emiterii');
            document.getElementById('modalDataScadenta').value = row.getAttribute('data-data-scadenta');
            document.getElementById('modalValoareTotala').value = row.getAttribute('data-valoare-totala');
            document.getElementById('modalMoneda').value = row.getAttribute('data-moneda');
            document.getElementById('modalValoareTVA').value = row.getAttribute('data-valoare-tva');
            document.getElementById('modalCostAsociat').value = row.getAttribute('data-cost-asociat');
            document.getElementById('modalStatus').value = row.getAttribute('data-status');
            document.getElementById('modalObservatiiFactura').value = row.getAttribute('data-observatii');
        }
    });

    // Populate View Details Modal (Event delegation)
    facturiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-details-factura-btn') || e.target.closest('.view-details-factura-btn')) {
            const btn = e.target.closest('.view-details-factura-btn');
            const row = btn.closest('tr');

            // Map data from row attributes to modal fields
            document.getElementById('detailClientName').textContent = row.children[0].textContent;
            document.getElementById('detailNumarFactura').textContent = row.children[1].textContent;
            document.getElementById('detailDataEmiterii').textContent = row.children[2].textContent;
            document.getElementById('detailDataScadenta').textContent = row.children[3].textContent;
            document.getElementById('detailValoareTotala').textContent = row.children[4].textContent;
            document.getElementById('detailValoareTVA').textContent = row.children[5].textContent;
            document.getElementById('detailCostAsociat').textContent = row.children[6].textContent;
            document.getElementById('detailProfitEstimat').textContent = row.children[7].textContent;
            document.getElementById('detailSumaPlatitaCurenta').textContent = row.children[8].textContent;
            document.getElementById('detailSumaRamasaDePlata').textContent = row.children[9].textContent;
            document.getElementById('detailStatus').textContent = row.children[10].textContent;
            document.getElementById('detailObservatiiFactura').textContent = row.getAttribute('data-observatii') || 'N/A';

            const viewFacturaDetailsModalInstance = new bootstrap.Modal(viewFacturaDetailsModal);
            viewFacturaDetailsModalInstance.show();
        }
    });


    // Trimiterea formularului (Adaugă/Editează)
    facturaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!facturaForm.checkValidity()) {
            e.stopPropagation();
            facturaForm.classList.add('was-validated');
            // You might want to show a toast message here as well
            return;
        }
        facturaForm.classList.add('was-validated');

        const formData = new FormData(facturaForm);

        fetch('process_facturi.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            } else {
                return response.text().then(text => {
                    throw new Error('Server returned non-JSON response: ' + text);
                });
            }
        })
        .then(data => {
            if (data.success) {
                // You could use a toast message here as well
                const modalInstance = bootstrap.Modal.getInstance(addEditFacturaModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                location.reload(); // Reîncarcă pagina pentru a vedea modificările
            } else {
                alert('Eroare: ' + (data.message || 'A apărut o eroare necunoscută.'));
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea facturii:', error);
            alert('A apărut o eroare la salvarea facturii. Detalii: ' + error.message);
        });
    });

    // Ștergerea facturii
    facturiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-factura-btn') || e.target.closest('.delete-factura-btn')) {
            const btn = e.target.closest('.delete-factura-btn');
            const facturaIdToDelete = btn.getAttribute('data-id');
            document.getElementById('deleteFacturaId').value = facturaIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteFacturaModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteFacturaBtn.addEventListener('click', function() {
        const facturaIdToDelete = document.getElementById('deleteFacturaId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', facturaIdToDelete);

        fetch('process_facturi.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            } else {
                return response.text().then(text => {
                    throw new Error('Server returned non-JSON response: ' + text);
                });
            }
        })
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare la ștergere: ' + (data.