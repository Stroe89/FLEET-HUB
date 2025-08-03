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

// Preluăm lista de facturi și clienți pentru dropdown-uri de filtrare și adăugare
// ACUM CU MAI MULTE DETALII PENTRU CALCUL!
$facturi_list = [];
$facturi_data_map = []; // Mapă pentru a stoca toate detaliile facturii după ID
$sql_facturi_clienti = "
    SELECT f.id, f.numar_factura, f.data_factura, f.valoare_totala, f.valoare_tva, f.cost_asociat, f.moneda, f.status_plata,
           c.nume_companie, c.persoana_contact
    FROM facturi f
    JOIN clienti c ON f.id_client = c.id
    ORDER BY f.numar_factura DESC
";
$result_facturi_clienti = $conn->query($sql_facturi_clienti);
if ($result_facturi_clienti) {
    while ($row = $result_facturi_clienti->fetch_assoc()) {
        $facturi_list[] = $row;
        $facturi_data_map[$row['id']] = $row; // Stocăm întreaga linie pentru acces ușor
    }
}

// Preluăm suma totală plătită pentru fiecare factură
$suma_platita_per_factura = [];
$sql_sum_plati = "SELECT id_factura, SUM(suma_platita) as total_platit FROM plati_clienti GROUP BY id_factura";
$result_sum_plati = $conn->query($sql_sum_plati);
if ($result_sum_plati) {
    while ($row = $result_sum_plati->fetch_assoc()) {
        $suma_platita_per_factura[$row['id_factura']] = (float)$row['total_platit'];
    }
}


// Preluăm lista de plăți CU DETALII ADIȚIONALE CALCULATE
$plati_list = [];
$total_suma_incasata = 0;
$total_profit_estimat = 0;
$total_tva_colectat_estimat = 0;

$sql_plati = "
    SELECT pc.*, f.numar_factura, f.valoare_totala, f.valoare_tva, f.cost_asociat, f.moneda AS factura_moneda,
           c.nume_companie, c.persoana_contact
    FROM plati_clienti pc
    JOIN facturi f ON pc.id_factura = f.id
    JOIN clienti c ON f.id_client = c.id
    ORDER BY pc.data_platii DESC
";
$result_plati = $conn->query($sql_plati);
if ($result_plati) {
    while ($plata = $result_plati->fetch_assoc()) {
        $id_factura = $plata['id_factura'];
        $factura_valoare_totala = (float)$plata['valoare_totala'];
        $factura_valoare_tva = (float)$plata['valoare_tva'];
        $factura_cost_asociat = (float)$plata['cost_asociat'];
        $suma_platita = (float)$plata['suma_platita'];

        // Calculăm suma deja plătită pentru factură (inclusiv plata curentă dacă nu am folosit $suma_platita_per_factura dinamic)
        // Pentru a evita dubla adăugare a plății curente la suma deja plătită, e mai sigur să folosim $suma_platita_per_factura
        $suma_total_platita_factura = $suma_platita_per_factura[$id_factura] ?? 0;
        
        // Suma rămasă de plată pentru factură
        $suma_ramasa_de_plata = $factura_valoare_totala - $suma_total_platita_factura;
        if ($suma_ramasa_de_plata < 0) $suma_ramasa_de_plata = 0; // Nu poate fi negativ

        // Proporția acestei plăți din valoarea totală a facturii
        $proportie_plata = ($factura_valoare_totala > 0) ? ($suma_platita / $factura_valoare_totala) : 0;

        // Calcul profit estimat pentru această plată
        // Profitul total al facturii = Valoare Factura (fara TVA) - Cost Asociat
        // Presupunem că valoare_totala include TVA
        $valoare_fara_tva = $factura_valoare_totala - $factura_valoare_tva;
        $profit_total_factura = $valoare_fara_tva - $factura_cost_asociat;
        $profit_estimat_plata = $proportie_plata * $profit_total_factura;
        
        // TVA colectat estimat pentru această plată
        $tva_colectat_estimat_plata = $proportie_plata * $factura_valoare_tva;

        $plata['suma_ramasa_de_plata_factura'] = $suma_ramasa_de_plata;
        $plata['profit_estimat_plata'] = $profit_estimat_plata;
        $plata['tva_colectat_estimat_plata'] = $tva_colectat_estimat_plata;
        $plata['suma_total_platita_factura'] = $suma_total_platita_factura; // Pentru afisare in tabel

        $plati_list[] = $plata;

        $total_suma_incasata += $suma_platita;
        $total_profit_estimat += $profit_estimat_plata;
        $total_tva_colectat_estimat += $tva_colectat_estimat_plata;
    }
}
$conn->close();

// Metode de plată pentru filtrare și modal
$metode_plata = ['Transfer bancar', 'Card', 'Cash', 'Altele'];
?>

<title>NTS TOUR | Status Plăți Clienți</title>
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

    /* Stiluri specifice pentru tabelul de plăți */
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
    .badge-method-transfer_bancar { background-color: #0d6efd !important; color: #fff !important; }
    .badge-method-card { background-color: #28a745 !important; color: #fff !important; }
    .badge-method-cash { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-method-altele { background-color: #6c757d !important; color: #fff !important; }

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
                        <li class="breadcrumb-item active" aria-current="page"><i class="bx bx-wallet"></i> Status Plăți Clienți</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Plăți Clienți</h4>
                        <p class="text-muted mb-4">Urmăriți plățile încasate, calculați profitul estimat și TVA-ul colectat.</p>
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

                        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                            <div class="col">
                                <div class="summary-card">
                                    <i class="bx bx-dollar-circle summary-icon text-success"></i>
                                    <div class="summary-value"><?php echo number_format($total_suma_incasata, 2, ',', '.') . ' RON'; ?></div>
                                    <div class="summary-label">Total Sumă Încasată</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="summary-card">
                                    <i class="bx bx-bar-chart-alt summary-icon text-info"></i>
                                    <div class="summary-value"><?php echo number_format($total_profit_estimat, 2, ',', '.') . ' RON'; ?></div>
                                    <div class="summary-label">Profit Estimat Total</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="summary-card">
                                    <i class="bx bx-receipt summary-icon text-warning"></i>
                                    <div class="summary-value"><?php echo number_format($total_tva_colectat_estimat, 2, ',', '.') . ' RON'; ?></div>
                                    <div class="summary-label">TVA Colectat Estimat Total</div>
                                </div>
                            </div>
                        </div>


                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditPlataModal" id="addPlataBtn">
                            <i class="bx bx-plus-circle me-1"></i> Adaugă Plată Nouă
                        </button>

                        <div class="row mb-4 filter-section card p-3">
                            <h5 class="card-title mb-3">Filtrează Plățile</h5>
                            <div class="col-md-4 mb-3">
                                <label for="filterFactura" class="form-label"><i class="bx bx-file me-1"></i> Filtrează după Factură:</label>
                                <select class="form-select" id="filterFactura">
                                    <option value="all">Toate Facturile</option>
                                    <?php foreach ($facturi_list as $factura): ?>
                                        <option value="<?php echo $factura['id']; ?>"><?php echo htmlspecialchars($factura['numar_factura'] . ' - ' . $factura['nume_companie']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterMetodaPlata" class="form-label"><i class="bx bx-credit-card me-1"></i> Filtrează după Metodă Plată:</label>
                                <select class="form-select" id="filterMetodaPlata">
                                    <option value="all">Toate Metodele</option>
                                    <?php foreach ($metode_plata as $metoda): ?>
                                        <option value="<?php echo htmlspecialchars($metoda); ?>"><?php echo htmlspecialchars($metoda); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label"><i class="bx bx-search me-1"></i> Caută:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta număr factură, observații...">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-secondary" id="resetFiltersBtn"><i class="bx bx-reset me-1"></i> Resetează Filtre</button>
                            </div>
                        </div>

                        <?php if (empty($plati_list)): ?>
                            <div class="alert alert-info text-center py-4">
                                <i class="bx bx-info-circle" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                Nu există plăți înregistrate.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Factură</th>
                                            <th>Client</th>
                                            <th>Dată Plată</th>
                                            <th>Sumă Plătită</th>
                                            <th>Valoare Factură</th>
                                            <th>Plătit până acum</th>
                                            <th>Rămas de plată</th>
                                            <th>Metodă Plată</th>
                                            <th>Profit Estimat</th>
                                            <th>TVA Colectat Estimat</th>
                                            <th>Observații</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="platiTableBody">
                                        <?php foreach ($plati_list as $plata): 
                                            $factura_info = $facturi_data_map[$plata['id_factura']] ?? null;
                                            $valoare_totala_factura = $factura_info ? (float)$factura_info['valoare_totala'] : 0;
                                            $factura_moneda = $factura_info ? $factura_info['moneda'] : 'RON';
                                            $suma_platita_curenta_totala_factura = $plata['suma_total_platita_factura'];
                                        ?>
                                            <tr 
                                                data-id="<?php echo $plata['id']; ?>"
                                                data-id-factura="<?php echo $plata['id_factura']; ?>"
                                                data-data-platii="<?php echo htmlspecialchars(date('Y-m-d', strtotime($plata['data_platii']))); ?>"
                                                data-suma-platita="<?php echo htmlspecialchars($plata['suma_platita']); ?>"
                                                data-metoda-platii="<?php echo htmlspecialchars($plata['metoda_platii']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($plata['observatii']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($plata['numar_factura'] . ' ' . $plata['nume_companie'] . ' ' . $plata['persoana_contact'] . ' ' . $plata['metoda_platii'] . ' ' . $plata['observatii'])); ?>"
                                            >
                                                <td data-label="Factură:"><?php echo htmlspecialchars($plata['numar_factura']); ?></td>
                                                <td data-label="Client:"><?php echo htmlspecialchars($plata['nume_companie'] . ' (' . $plata['persoana_contact'] . ')'); ?></td>
                                                <td data-label="Dată Plată:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($plata['data_platii']))); ?></td>
                                                <td data-label="Sumă Plătită:"><?php echo number_format($plata['suma_platita'], 2, ',', '.') . ' ' . htmlspecialchars($factura_moneda); ?></td>
                                                <td data-label="Valoare Factură:"><?php echo number_format($valoare_totala_factura, 2, ',', '.') . ' ' . htmlspecialchars($factura_moneda); ?></td>
                                                <td data-label="Plătit până acum:"><?php echo number_format($suma_platita_curenta_totala_factura, 2, ',', '.') . ' ' . htmlspecialchars($factura_moneda); ?></td>
                                                <td data-label="Rămas de plată:"><?php echo number_format($plata['suma_ramasa_de_plata_factura'], 2, ',', '.') . ' ' . htmlspecialchars($factura_moneda); ?></td>
                                                <td data-label="Metodă Plată:"><span class="badge badge-method-<?php echo strtolower(str_replace(' ', '_', $plata['metoda_platii'])); ?>"><?php echo htmlspecialchars($plata['metoda_platii']); ?></span></td>
                                                <td data-label="Profit Estimat:"><?php echo number_format($plata['profit_estimat_plata'], 2, ',', '.') . ' ' . htmlspecialchars($factura_moneda); ?></td>
                                                <td data-label="TVA Colectat Estimat:"><?php echo number_format($plata['tva_colectat_estimat_plata'], 2, ',', '.') . ' ' . htmlspecialchars($factura_moneda); ?></td>
                                                <td data-label="Observații:"><?php echo htmlspecialchars(mb_strimwidth($plata['observatii'], 0, 30, "...")); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details-plata-btn" title="Vezi detalii"><i class="bx bx-show"></i></button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-plata-btn" data-bs-toggle="modal" data-bs-target="#addEditPlataModal" title="Editează"><i class="bx bx-edit"></i></button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-plata-btn" data-id="<?php echo $plata['id']; ?>" title="Șterge"><i class="bx bx-trash"></i></button>
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

<div class="modal fade" id="addEditPlataModal" tabindex="-1" aria-labelledby="addEditPlataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditPlataModalLabel"><i class="bx bx-plus-circle me-2"></i> Adaugă Plată Nouă</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="plataForm" action="process_plati.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="plataAction" name="action" value="add">
                    <input type="hidden" id="plataId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalSelectFactura" class="form-label">Factură:</label>
                            <select class="form-select" id="modalSelectFactura" name="id_factura" required>
                                <option value="">Alege o factură</option>
                                <?php foreach ($facturi_list as $factura): ?>
                                    <option 
                                        value="<?php echo $factura['id']; ?>"
                                        data-valoare-totala="<?php echo $factura['valoare_totala']; ?>"
                                        data-valoare-tva="<?php echo $factura['valoare_tva']; ?>"
                                        data-cost-asociat="<?php echo $factura['cost_asociat']; ?>"
                                        data-moneda="<?php echo $factura['moneda']; ?>"
                                        data-paid-so-far="<?php echo $suma_platita_per_factura[$factura['id']] ?? 0; ?>"
                                    >
                                        <?php echo htmlspecialchars($factura['numar_factura'] . ' - ' . $factura['nume_companie'] . ' (Val: ' . number_format($factura['valoare_totala'], 2, ',', '.') . ' ' . $factura['moneda'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Te rog selectează o factură.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataPlatii" class="form-label">Dată Plată:</label>
                            <input type="date" class="form-control" id="modalDataPlatii" name="data_platii" required>
                            <div class="invalid-feedback">Te rog introdu data plății.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalSumaPlatita" class="form-label">Sumă Plătită:</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="modalSumaPlatita" name="suma_platita" required min="0.01">
                                <span class="input-group-text" id="modalSumaPlatitaMoneda">RON</span>
                            </div>
                            <div class="invalid-feedback">Te rog introdu suma plătită.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalMetodaPlatii" class="form-label">Metodă Plată:</label>
                            <select class="form-select" id="modalMetodaPlatii" name="metoda_platii" required>
                                <?php foreach ($metode_plata as $metoda): ?>
                                    <option value="<?php echo htmlspecialchars($metoda); ?>"><?php echo htmlspecialchars($metoda); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Te rog selectează o metodă de plată.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="modalFacturaValoareTotala" class="form-label">Valoare Totală Factură:</label>
                            <p class="form-control-plaintext text-white" id="modalFacturaValoareTotala">N/A</p>
                        </div>
                        <div class="col-md-4">
                            <label for="modalFacturaPlatitPanaAcum" class="form-label">Plătit până acum (pentru factură):</label>
                            <p class="form-control-plaintext text-white" id="modalFacturaPlatitPanaAcum">N/A</p>
                        </div>
                        <div class="col-md-4">
                            <label for="modalFacturaRamasDePlata" class="form-label">Rămas de plată (pentru factură):</label>
                            <p class="form-control-plaintext text-success" id="modalFacturaRamasDePlata">N/A</p>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bx bx-x me-1"></i> Anulează</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Salvează Plată</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deletePlataModal" tabindex="-1" aria-labelledby="deletePlataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePlataModalLabel"><i class="bx bx-trash me-2"></i> Confirmă Ștergerea</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi această plată? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deletePlataId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePlataBtn"><i class="bx bx-trash-alt me-1"></i> Șterge</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPlataDetailsModal" tabindex="-1" aria-labelledby="viewPlataDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPlataDetailsModalLabel"><i class="bx bx-info-circle me-2"></i> Detalii Plată</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailFacturaNumar"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Client:</label>
                        <p class="form-control-plaintext text-white" id="detailClientNume"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dată Plată:</label>
                        <p class="form-control-plaintext text-white" id="detailDataPlatii"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sumă Plătită:</label>
                        <p class="form-control-plaintext text-white" id="detailSumaPlatita"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Metodă Plată:</label>
                        <p class="form-control-plaintext text-white" id="detailMetodaPlatii"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valoare Totală Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailValoareFactura"></p>
                    </div>
                     <div class="col-md-6">
                        <label class="form-label">Plătit Până Acum (Factură):</label>
                        <p class="form-control-plaintext text-white" id="detailPlatitPanaAcumFactura"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rămas De Plată (Factură):</label>
                        <p class="form-control-plaintext text-success" id="detailRamasDePlataFactura"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cost Asociat Facturii:</label>
                        <p class="form-control-plaintext text-white" id="detailCostAsociat"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valoare TVA Factură:</label>
                        <p class="form-control-plaintext text-white" id="detailValoareTVA"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profit Estimat din această plată:</label>
                        <p class="form-control-plaintext text-white" id="detailProfitEstimatPlata"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">TVA Colectat Estimat din această plată:</label>
                        <p class="form-control-plaintext text-white" id="detailTVAColectatEstimatPlata"></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observații:</label>
                        <p class="form-control-plaintext text-white" id="detailObservatii"></p>
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
    const addEditPlataModal = document.getElementById('addEditPlataModal');
    const plataForm = document.getElementById('plataForm');
    const addPlataBtn = document.getElementById('addPlataBtn');
    const deletePlataModal = document.getElementById('deletePlataModal');
    const confirmDeletePlataBtn = document.getElementById('confirmDeletePlataBtn');
    const platiTableBody = document.getElementById('platiTableBody');
    const viewPlataDetailsModal = document.getElementById('viewPlataDetailsModal');

    // Filtrare
    const filterFactura = document.getElementById('filterFactura');
    const filterMetodaPlata = document.getElementById('filterMetodaPlata');
    const filterSearch = document.getElementById('filterSearch');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');

    function filterTable() {
        const selectedFacturaId = filterFactura.value;
        const selectedMetodaPlata = filterMetodaPlata.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#platiTableBody tr').forEach(row => {
            const rowFacturaId = row.getAttribute('data-id-factura');
            const rowMetodaPlata = row.getAttribute('data-metoda-platii');
            const rowSearchText = row.getAttribute('data-search-text');

            const facturaMatch = (selectedFacturaId === 'all' || rowFacturaId === selectedFacturaId);
            const metodaPlataMatch = (selectedMetodaPlata === 'all' || rowMetodaPlata.toLowerCase().replace(' ', '_') === selectedMetodaPlata.toLowerCase().replace(' ', '_')); // Normalize for matching
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (facturaMatch && metodaPlataMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterFactura.addEventListener('change', filterTable);
    filterMetodaPlata.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    resetFiltersBtn.addEventListener('click', function() {
        filterFactura.value = 'all';
        filterMetodaPlata.value = 'all';
        filterSearch.value = '';
        filterTable(); // Apply reset filters
    });


    // Deschide modalul pentru adăugare
    addPlataBtn.addEventListener('click', function() {
        plataForm.reset();
        document.getElementById('plataAction').value = 'add';
        document.getElementById('plataId').value = '';
        document.getElementById('addEditPlataModalLabel').innerHTML = '<i class="bx bx-plus-circle me-2"></i> Adaugă Plată Nouă';
        
        // Setează data plății la data curentă implicit
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        document.getElementById('modalDataPlatii').value = formattedDate;
        document.getElementById('modalMetodaPlatii').value = 'Transfer bancar'; // Metoda implicită

        // Reset dynamic invoice info
        document.getElementById('modalFacturaValoareTotala').textContent = 'N/A';
        document.getElementById('modalFacturaPlatitPanaAcum').textContent = 'N/A';
        document.getElementById('modalFacturaRamasDePlata').textContent = 'N/A';
        document.getElementById('modalSumaPlatitaMoneda').textContent = 'RON'; // Default currency
    });

    // Populate invoice details on factura selection change in modal
    const modalSelectFactura = document.getElementById('modalSelectFactura');
    modalSelectFactura.addEventListener('change', function() {
        const selectedOption = modalSelectFactura.options[modalSelectFactura.selectedIndex];
        const valoareTotala = selectedOption.getAttribute('data-valoare-totala');
        const paidSoFar = selectedOption.getAttribute('data-paid-so-far');
        const moneda = selectedOption.getAttribute('data-moneda');

        let remaining = 'N/A';
        if (valoareTotala && paidSoFar && parseFloat(valoareTotala) >= 0 && parseFloat(paidSoFar) >= 0) {
            remaining = (parseFloat(valoareTotala) - parseFloat(paidSoFar)).toFixed(2);
        }

        document.getElementById('modalFacturaValoareTotala').textContent = valoareTotala ? `${parseFloat(valoareTotala).toFixed(2)} ${moneda}` : 'N/A';
        document.getElementById('modalFacturaPlatitPanaAcum').textContent = paidSoFar ? `${parseFloat(paidSoFar).toFixed(2)} ${moneda}` : 'N/A';
        document.getElementById('modalFacturaRamasDePlata').textContent = `${remaining} ${moneda || ''}`;
        document.getElementById('modalSumaPlatitaMoneda').textContent = moneda || 'RON'; // Update currency for sum paid input
    });

    // Deschide modalul pentru editare
    platiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-plata-btn') || e.target.closest('.edit-plata-btn')) {
            const btn = e.target.closest('.edit-plata-btn');
            const row = btn.closest('tr');
            document.getElementById('plataAction').value = 'edit';
            document.getElementById('plataId').value = row.getAttribute('data-id');
            document.getElementById('addEditPlataModalLabel').innerHTML = '<i class="bx bx-edit me-2"></i> Editează Plată';

            document.getElementById('modalSelectFactura').value = row.getAttribute('data-id-factura');
            // Manually trigger change to update dynamic invoice info
            modalSelectFactura.dispatchEvent(new Event('change'));

            document.getElementById('modalDataPlatii').value = row.getAttribute('data-data-platii');
            document.getElementById('modalSumaPlatita').value = row.getAttribute('data-suma-platita');
            document.getElementById('modalMetodaPlatii').value = row.getAttribute('data-metoda-platii');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
        }
    });

    // Populate View Details Modal (Event delegation)
    platiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-details-plata-btn') || e.target.closest('.view-details-plata-btn')) {
            const btn = e.target.closest('.view-details-plata-btn');
            const row = btn.closest('tr');

            // Map data from row attributes to modal fields
            document.getElementById('detailFacturaNumar').textContent = row.children[0].textContent; // First td is invoice number
            document.getElementById('detailClientNume').textContent = row.children[1].textContent; // Second td is client name
            document.getElementById('detailDataPlatii').textContent = row.children[2].textContent;
            document.getElementById('detailSumaPlatita').textContent = row.children[3].textContent;
            document.getElementById('detailValoareFactura').textContent = row.children[4].textContent;
            document.getElementById('detailPlatitPanaAcumFactura').textContent = row.children[5].textContent;
            document.getElementById('detailRamasDePlataFactura').textContent = row.children[6].textContent;
            document.getElementById('detailMetodaPlatii').textContent = row.children[7].textContent;
            document.getElementById('detailProfitEstimatPlata').textContent = row.children[8].textContent;
            document.getElementById('detailTVAColectatEstimatPlata').textContent = row.children[9].textContent;
            document.getElementById('detailObservatii').textContent = row.getAttribute('data-observatii') || 'N/A';

            const viewPlataDetailsModalInstance = new bootstrap.Modal(viewPlataDetailsModal);
            viewPlataDetailsModalInstance.show();
        }
    });


    // Trimiterea formularului (Adaugă/Editează)
    plataForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Custom validation: check if suma_platita exceeds remaining_amount
        const selectedOption = modalSelectFactura.options[modalSelectFactura.selectedIndex];
        const valoareTotala = parseFloat(selectedOption.getAttribute('data-valoare-totala') || 0);
        const paidSoFar = parseFloat(selectedOption.getAttribute('data-paid-so-far') || 0);
        const currentPlataSuma = parseFloat(document.getElementById('modalSumaPlatita').value);

        let maxAllowedPayment = valoareTotala - paidSoFar;

        // If editing, subtract the current payment amount from paidSoFar to get actual remaining before this edit
        if (document.getElementById('plataAction').value === 'edit') {
            const originalSumaPlatita = parseFloat(document.getElementById('modalSumaPlatita').getAttribute('data-original-suma-platita') || 0);
            maxAllowedPayment = valoareTotala - (paidSoFar - originalSumaPlatita);
        }

        if (currentPlataSuma > maxAllowedPayment + 0.01) { // Add a small epsilon for float comparison
            alert(`Suma introdusă (${currentPlataSuma.toFixed(2)}) depășește suma rămasă de plată pentru această factură (${maxAllowedPayment.toFixed(2)}).`);
            return;
        }


        const formData = new FormData(plataForm);

        fetch('process_plati.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if the response is JSON or plain text
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            } else {
                // If not JSON, assume it's an error message or plain text debug output
                return response.text().then(text => {
                    throw new Error('Server returned non-JSON response: ' + text);
                });
            }
        })
        .then(data => {
            if (data.success) {
                // Display success message using your alert/toast system
                location.reload(); // Reîncarcă pagina pentru a vedea modificările
                // Alternatively, dynamically update table row without reload
            } else {
                alert('Eroare: ' + (data.message || 'A apărut o eroare necunoscută.'));
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea plății:', error);
            alert('A apărut o eroare la salvarea plății. Detalii: ' + error.message);
        });
    });

    // Ștergerea plății
    platiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-plata-btn') || e.target.closest('.delete-plata-btn')) {
            const btn = e.target.closest('.delete-plata-btn');
            const plataIdToDelete = btn.getAttribute('data-id');
            document.getElementById('deletePlataId').value = plataIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deletePlataModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteComandaBtn.addEventListener('click', function() { // This should be confirmDeletePlataBtn
        const plataIdToDelete = document.getElementById('deletePlataId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', plataIdToDelete);

        fetch('process_plati.php', {
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
                alert('Eroare la ștergere: ' + (data.message || 'A apărut o eroare necunoscută.'));
            }
        })
        .catch(error => {
            console.error('Eroare la ștergerea plății:', error);
            alert('A apărut o eroare la ștergerea plății. Detalii: ' + error.message);
        });
    });

    // Fix for page blocking after closing modals (generic)
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            // Check if any other modal is still open
            const anyModalOpen = document.body.classList.contains('modal-open');
            if (!anyModalOpen) {
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        });
    });

    // Initial filter application on page load
    filterTable();

});
</script>