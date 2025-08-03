<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie
require_once 'template/header.php'; //

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

// Preluăm lista de angajați cu salariul lor curent și date agregate despre bonusuri/bonuri masă
$angajati_list = [];
$sql_angajati = "
    SELECT 
        a.id, a.nume, a.prenume, a.functie, a.status,
        s.id as salariu_id, s.salariu_baza, s.moneda_salariu, s.frecventa_plata, s.iban, s.banca, s.data_inceput_salariu, s.data_sfarsit_salariu, s.observatii as salariu_observatii,
        
        -- Calculează suma totală a bonusurilor pentru angajat (simplificat, toate bonusurile, nu pe o perioadă specifică)
        COALESCE((SELECT SUM(valoare) FROM bonusuri WHERE id_angajat = a.id), 0) as total_bonus_value,
        COALESCE((SELECT moneda FROM bonusuri WHERE id_angajat = a.id ORDER BY data_acordare DESC LIMIT 1), 'RON') as bonus_moneda_majoritara,
        
        -- Calculează suma totală a bonurilor de masă pentru angajat (numar_bonuri * valoare_bon_unitar)
        COALESCE((SELECT SUM(numar_bonuri * valoare_bon_unitar) FROM bonuri_masa WHERE id_angajat = a.id), 0) as total_bonuri_masa_value
    FROM 
        angajati a
    LEFT JOIN 
        salarii s ON a.id = s.id_angajat AND s.data_sfarsit_salariu IS NULL
    ORDER BY 
        a.nume ASC, a.prenume ASC
";
$result_angajati = $conn->query($sql_angajati);
if ($result_angajati) {
    while ($row = $result_angajati->fetch_assoc()) {
        // Calculează totalul venit estimativ (salariu de bază + bonusuri + bonuri masă)
        // Convertim totul în RON pentru estimare, dacă e necesar, sau afișăm doar suma
        $salariu_baza = $row['salariu_baza'] ?? 0;
        $total_bonus_value = $row['total_bonus_value'] ?? 0;
        $total_bonuri_masa_value = $row['total_bonuri_masa_value'] ?? 0;
        
        // Simplu total, fără conversii complicate între valute în acest stadiu
        $row['estimated_total_income'] = $salariu_baza + $total_bonus_value + $total_bonuri_masa_value;
        $row['estimated_total_income_currency'] = $row['moneda_salariu'] ?? 'RON'; // Folosim moneda salariului de bază

        $angajati_list[] = $row;
    }
}

// Preluăm liste pentru dropdown-uri
$frecvente_plata = ['Lunar', 'Săptămânal', 'Bi-Lunar', 'Orar'];
$monede = ['RON', 'EUR', 'USD', 'GBP'];
$tipuri_bonus = ['Bonus Performanță', 'Primă Sărbători', 'Comision', 'Alte Prime', 'Diurnă'];
$functii_angajati = ['Sofer', 'Dispecer', 'Mecanic', 'Administrator', 'Contabil', 'Manager', 'Altele'];
$statusuri_angajati = ['Activ', 'Inactiv', 'Concediu', 'Suspendat', 'Demisionat'];

$conn->close(); // Închidem conexiunea după toate operațiile DB
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Salarii & Bonusuri</title>

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

    /* Stiluri specifice pentru tabelul de angajați */
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
    /* Badge-uri pentru statusul angajatului */
    .badge-status-activ { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-inactiv { background-color: #6c757d !important; color: #fff !important; }
    .badge-status-concediu { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-status-suspendat { background-color: #dc3545 !important; color: #fff !important; }
    .badge-status-demisionat { background-color: #ffc107 !important; color: #343a40 !important; }

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
            <div class="breadcrumb-title pe-3">Salarii & Bonusuri</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Salarii & Bonusuri</h4>
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

                       
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterFunctie" class="form-label">Filtrează după Funcție:</label>
                                <select class="form-select" id="filterFunctie">
                                    <option value="all">Toate Funcțiile</option>
                                    <?php foreach ($functii_angajati as $functie): ?>
                                        <option value="<?php echo htmlspecialchars($functie); ?>"><?php echo htmlspecialchars($functie); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_angajati as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume, email, IBAN...">
                                </div>
                            </div>
                        </div>

                       
                        <?php if (empty($angajati_list)): ?>
                            <div class="alert alert-info">Nu există angajați înregistrați.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nume Complet</th>
                                            <th>Funcție</th>
                                            <th>Salariu Bază</th>
                                            <th>Bonusuri (Total)</th>
                                            <th>Bonuri Masă (Total)</th>
                                            <th>Total Venit Estimativ</th>
                                            <th>IBAN</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="angajatiSalariiTableBody">
                                        <?php foreach ($angajati_list as $angajat): ?>
                                            <tr 
                                                data-id="<?php echo $angajat['id']; ?>"
                                                data-nume="<?php echo htmlspecialchars($angajat['nume']); ?>"
                                                data-prenume="<?php echo htmlspecialchars($angajat['prenume']); ?>"
                                                data-functie="<?php echo htmlspecialchars($angajat['functie']); ?>"
                                                data-status="<?php echo htmlspecialchars($angajat['status']); ?>"
                                                data-salariu-id="<?php echo htmlspecialchars($angajat['salariu_id'] ?? ''); ?>"
                                                data-salariu-baza="<?php echo htmlspecialchars($angajat['salariu_baza'] ?? ''); ?>"
                                                data-moneda-salariu="<?php echo htmlspecialchars($angajat['moneda_salariu'] ?? ''); ?>"
                                                data-frecventa-plata="<?php echo htmlspecialchars($angajat['frecventa_plata'] ?? ''); ?>"
                                                data-iban="<?php echo htmlspecialchars($angajat['iban'] ?? ''); ?>"
                                                data-banca="<?php echo htmlspecialchars($angajat['banca'] ?? ''); ?>"
                                                data-data-inceput-salariu="<?php echo htmlspecialchars($angajat['data_inceput_salariu'] ?? ''); ?>"
                                                data-salariu-observatii="<?php echo htmlspecialchars($angajat['salariu_observatii'] ?? ''); ?>"
                                                data-total-bonus-value="<?php echo htmlspecialchars($angajat['total_bonus_value'] ?? ''); ?>"
                                                data-total-bonuri-masa-value="<?php echo htmlspecialchars($angajat['total_bonuri_masa_value'] ?? ''); ?>"
                                                data-estimated-total-income="<?php echo htmlspecialchars($angajat['estimated_total_income'] ?? ''); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume'] . ' ' . $angajat['functie'] . ' ' . ($angajat['iban'] ?? '') . ' ' . ($angajat['banca'] ?? ''))); ?>"
                                            >
                                                <td data-label="Nume Complet:"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></td>
                                                <td data-label="Funcție:"><?php echo htmlspecialchars($angajat['functie']); ?></td>
                                                <td data-label="Salariu Bază:"><?php echo htmlspecialchars(number_format($angajat['salariu_baza'] ?? 0, 2, ',', '.')) . ' ' . htmlspecialchars($angajat['moneda_salariu'] ?? 'RON'); ?></td>
                                                <td data-label="Bonusuri (Total):"><?php echo htmlspecialchars(number_format($angajat['total_bonus_value'] ?? 0, 2, ',', '.')) . ' ' . htmlspecialchars($angajat['bonus_moneda_majoritara'] ?? 'RON'); ?></td>
                                                <td data-label="Bonuri Masă (Total):"><?php echo htmlspecialchars(number_format($angajat['total_bonuri_masa_value'] ?? 0, 2, ',', '.')) . ' RON'; ?></td>
                                                <td data-label="Total Venit Estimativ:"><strong><?php echo htmlspecialchars(number_format($angajat['estimated_total_income'] ?? 0, 2, ',', '.')) . ' ' . htmlspecialchars($angajat['estimated_total_income_currency']); ?></strong></td>
                                                <td data-label="IBAN:"><?php echo htmlspecialchars($angajat['iban'] ?? 'N/A'); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $angajat['status'])); ?>"><?php echo htmlspecialchars($angajat['status']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-salariu-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#salariuModal">
                                                        <?php echo ($angajat['salariu_id'] ? 'Editează Salariu' : 'Adaugă Salariu'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info add-bonus-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#bonusModal">Adaugă Bonus</button>
                                                    <button type="button" class="btn btn-sm btn-outline-success add-bonuri-masa-btn w-100" data-bs-toggle="modal" data-bs-target="#bonuriMasaModal">Adaugă Bonuri Masă</button>
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


<div class="modal fade" id="salariuModal" tabindex="-1" aria-labelledby="salariuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salariuModalLabel">Adaugă/Editează Salariu pentru <span id="salariuAngajatNume"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="salariuForm" action="process_salarii.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="salariuAction" name="action" value="add_salary">
                    <input type="hidden" id="salariuId" name="id">
                    <input type="hidden" id="salariuAngajatId" name="id_angajat">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="salariuBaza" class="form-label">Salariu Bază:</label>
                            <input type="number" step="0.01" class="form-control" id="salariuBaza" name="salariu_baza" required>
                        </div>
                        <div class="col-md-6">
                            <label for="monedaSalariu" class="form-label">Moneda:</label>
                            <select class="form-select" id="monedaSalariu" name="moneda_salariu" required>
                                <?php foreach ($monede as $moneda): ?>
                                    <option value="<?php echo htmlspecialchars($moneda); ?>"><?php echo htmlspecialchars($moneda); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="frecventaPlata" class="form-label">Frecvența Plății:</label>
                            <select class="form-select" id="frecventaPlata" name="frecventa_plata" required>
                                <?php foreach ($frecvente_plata as $frecventa): ?>
                                    <option value="<?php echo htmlspecialchars($frecventa); ?>"><?php echo htmlspecialchars($frecventa); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="dataInceputSalariu" class="form-label">Dată Început Salariu:</label>
                            <input type="date" class="form-control" id="dataInceputSalariu" name="data_inceput_salariu" required>
                        </div>
                        <div class="col-12">
                            <label for="iban" class="form-label">IBAN:</label>
                            <input type="text" class="form-control" id="iban" name="iban" placeholder="ROxxBANKxxxxxxxxxxxxxxxxxxxx">
                        </div>
                        <div class="col-12">
                            <label for="banca" class="form-label">Banca:</label>
                            <input type="text" class="form-control" id="banca" name="banca">
                        </div>
                        <div class="col-12">
                            <label for="salariuObservatii" class="form-label">Observații Salariu:</label>
                            <textarea class="form-control" id="salariuObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Salariu</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="bonusModal" tabindex="-1" aria-labelledby="bonusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bonusModalLabel">Adaugă Bonus pentru <span id="bonusAngajatNume"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bonusForm" action="process_salarii.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_bonus">
                    <input type="hidden" id="bonusAngajatId" name="id_angajat">
                    
                    <div class="mb-3">
                        <label for="tipBonus" class="form-label">Tip Bonus:</label>
                        <select class="form-select" id="tipBonus" name="tip_bonus" required>
                            <option value="">Selectează tipul</option>
                            <?php foreach ($tipuri_bonus as $tip): ?>
                                <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="valoareBonus" class="form-label">Valoare:</label>
                        <input type="number" step="0.01" class="form-control" id="valoareBonus" name="valoare" required>
                    </div>
                    <div class="mb-3">
                        <label for="monedaBonus" class="form-label">Moneda:</label>
                        <select class="form-select" id="monedaBonus" name="moneda" required>
                            <?php foreach ($monede as $moneda): ?>
                                <option value="<?php echo htmlspecialchars($moneda); ?>"><?php echo htmlspecialchars($moneda); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dataAcordareBonus" class="form-label">Dată Acordare:</label>
                        <input type="date" class="form-control" id="dataAcordareBonus" name="data_acordare" required>
                    </div>
                    <div class="mb-3">
                        <label for="bonusObservatii" class="form-label">Observații Bonus:</label>
                        <textarea class="form-control" id="bonusObservatii" name="observatii" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Adaugă Bonus</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="bonuriMasaModal" tabindex="-1" aria-labelledby="bonuriMasaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bonuriMasaModalLabel">Adaugă Bonuri Masă pentru <span id="bonuriMasaAngajatNume"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bonuriMasaForm" action="process_salarii.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_bonuri_masa">
                    <input type="hidden" id="bonuriMasaAngajatId" name="id_angajat">
                    
                    <div class="mb-3">
                        <label for="numarBonuri" class="form-label">Număr Bonuri:</label>
                        <input type="number" class="form-control" id="numarBonuri" name="numar_bonuri" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="valoareBonUnitar" class="form-label">Valoare Bon Unitar (RON):</label>
                        <input type="number" step="0.01" class="form-control" id="valoareBonUnitar" name="valoare_bon_unitar" value="15.00" required>
                    </div>
                    <div class="mb-3">
                        <label for="perioadaLuna" class="form-label">Perioada (Lună/An):</label>
                        <input type="month" class="form-control" id="perioadaLuna" name="perioada_luna" required>
                    </div>
                    <div class="mb-3">
                        <label for="dataAcordareBonuri" class="form-label">Dată Acordare:</label>
                        <input type="date" class="form-control" id="dataAcordareBonuri" name="data_acordare" required>
                    </div>
                    <div class="mb-3">
                        <label for="bonuriObservatii" class="form-label">Observații Bonuri Masă:</label>
                        <textarea class="form-control" id="bonuriObservatii" name="observatii" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Adaugă Bonuri</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRecordModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi înregistrarea <strong id="deleteRecordType"></strong> pentru <strong id="deleteRecordAngajatNume"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteRecordId">
                <input type="hidden" id="deleteRecordActionType">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRecordBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const angajatiSalariiTableBody = document.getElementById('angajatiSalariiTableBody');
    const salariuModal = document.getElementById('salariuModal');
    const salariuForm = document.getElementById('salariuForm');
    const bonusModal = document.getElementById('bonusModal');
    const bonusForm = document.getElementById('bonusForm');
    const bonuriMasaModal = document.getElementById('bonuriMasaModal');
    const bonuriMasaForm = document.getElementById('bonuriMasaForm');
    const deleteRecordModal = document.getElementById('deleteRecordModal');
    const confirmDeleteRecordBtn = document.getElementById('confirmDeleteRecordBtn');

    // Câmpuri din modalul Salariu
    const salariuAngajatNumeSpan = document.getElementById('salariuAngajatNume');
    const salariuAngajatIdInput = document.getElementById('salariuAngajatId');
    const salariuIdInput = document.getElementById('salariuId');
    const salariuActionInput = document.getElementById('salariuAction');
    const salariuBazaInput = document.getElementById('salariuBaza');
    const monedaSalariuSelect = document.getElementById('monedaSalariu');
    const frecventaPlataSelect = document.getElementById('frecventaPlata');
    const ibanInput = document.getElementById('iban');
    const bancaInput = document.getElementById('banca');
    const dataInceputSalariuInput = document.getElementById('dataInceputSalariu');
    const salariuObservatiiTextarea = document.getElementById('salariuObservatii');

    // Câmpuri din modalul Bonus
    const bonusAngajatNumeSpan = document.getElementById('bonusAngajatNume');
    const bonusAngajatIdInput = document.getElementById('bonusAngajatId');
    const dataAcordareBonusInput = document.getElementById('dataAcordareBonus');
    const perioadaLunaInput = document.getElementById('perioadaLuna'); // Pentru bonuri de masă

    // Câmpuri din modalul Bonuri Masă
    const bonuriMasaAngajatNumeSpan = document.getElementById('bonuriMasaAngajatNume');
    const bonuriMasaAngajatIdInput = document.getElementById('bonuriMasaAngajatId');
    const dataAcordareBonuriInput = document.getElementById('dataAcordareBonuri');


    // --- Filtrare Tabel ---
    const filterFunctie = document.getElementById('filterFunctie');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedFunctie = filterFunctie.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#angajatiSalariiTableBody tr').forEach(row => {
            const rowFunctie = row.getAttribute('data-functie');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const functieMatch = (selectedFunctie === 'all' || rowFunctie === selectedFunctie);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (functieMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterFunctie.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterTable(); // Rulează la încărcarea paginii


    // --- Logica Modal Salariu ---
    angajatiSalariiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-salariu-btn')) {
            const row = e.target.closest('tr');
            const angajatId = row.getAttribute('data-id');
            const angajatNumeComplet = row.querySelector('td[data-label="Nume Complet:"]').textContent;
            
            salariuAngajatNumeSpan.textContent = angajatNumeComplet;
            salariuAngajatIdInput.value = angajatId;

            const salariuId = row.getAttribute('data-salariu-id');
            if (salariuId) {
                // Mod editare salariu existent
                salariuActionInput.value = 'edit_salary';
                salariuIdInput.value = salariuId;
                salariuModalLabel.textContent = `Editează Salariu pentru ${angajatNumeComplet}`;

                salariuBazaInput.value = row.getAttribute('data-salariu-baza');
                monedaSalariuSelect.value = row.getAttribute('data-moneda-salariu');
                frecventaPlataSelect.value = row.getAttribute('data-frecventa-plata');
                ibanInput.value = row.getAttribute('data-iban');
                bancaInput.value = row.getAttribute('data-banca');
                dataInceputSalariuInput.value = row.getAttribute('data-data-inceput-salariu');
                salariuObservatiiTextarea.value = row.getAttribute('data-salariu-observatii');

            } else {
                // Mod adăugare salariu nou
                salariuActionInput.value = 'add_salary';
                salariuIdInput.value = ''; // Asigură că ID-ul salariului este gol
                salariuModalLabel.textContent = `Adaugă Salariu pentru ${angajatNumeComplet}`;
                salariuForm.reset(); // Resetează formularul pentru un salariu nou
                
                // Setează data de început la data curentă
                const now = new Date();
                const formattedDate = now.toISOString().substring(0, 10);
                dataInceputSalariuInput.value = formattedDate;
                monedaSalariuSelect.value = 'RON'; // Default value
                frecventaPlataSelect.value = 'Lunar'; // Default value
            }
            new bootstrap.Modal(salariuModal).show();
        }
    });

    salariuForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(salariuForm);
        fetch('process_salarii.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(salariuModal);
            if (modalInstance) { modalInstance.hide(); }
            location.reload();
        })
        .catch(error => { console.error('Eroare Salariu:', error); alert('A apărut o eroare la salvarea salariului.'); });
    });


    // --- Logica Modal Bonus ---
    angajatiSalariiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-bonus-btn')) {
            const row = e.target.closest('tr');
            const angajatId = row.getAttribute('data-id');
            const angajatNumeComplet = row.querySelector('td[data-label="Nume Complet:"]').textContent;

            bonusAngajatNumeSpan.textContent = angajatNumeComplet;
            bonusAngajatIdInput.value = angajatId;
            bonusForm.reset();
            
            // Setează data acordării la data curentă
            const now = new Date();
            const formattedDate = now.toISOString().substring(0, 10);
            dataAcordareBonusInput.value = formattedDate;
            monedaBonus.value = 'RON'; // Default
            new bootstrap.Modal(bonusModal).show();
        }
    });

    bonusForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(bonusForm);
        fetch('process_salarii.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(bonusModal);
            if (modalInstance) { modalInstance.hide(); }
            location.reload();
        })
        .catch(error => { console.error('Eroare Bonus:', error); alert('A apărut o eroare la salvarea bonusului.'); });
    });


    // --- Logica Modal Bonuri Masă ---
    angajatiSalariiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-bonuri-masa-btn')) {
            const row = e.target.closest('tr');
            const angajatId = row.getAttribute('data-id');
            const angajatNumeComplet = row.querySelector('td[data-label="Nume Complet:"]').textContent;

            bonuriMasaAngajatNumeSpan.textContent = angajatNumeComplet;
            bonuriMasaAngajatIdInput.value = angajatId;
            bonuriMasaForm.reset();
            
            // Setează data acordării și luna/anul curent
            const now = new Date();
            const formattedDate = now.toISOString().substring(0, 10);
            const formattedMonth = now.toISOString().substring(0, 7);
            dataAcordareBonuriInput.value = formattedDate;
            perioadaLunaInput.value = formattedMonth;
            new bootstrap.Modal(bonuriMasaModal).show();
        }
    });

    bonuriMasaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(bonuriMasaForm);
        fetch('process_salarii.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(bonuriMasaModal);
            if (modalInstance) { modalInstance.hide(); }
            location.reload();
        })
        .catch(error => { console.error('Eroare Bonuri Masă:', error); alert('A apărut o eroare la salvarea bonurilor de masă.'); });
    });


    // --- Logica Modal Ștergere (Generic) ---
    // Aceasta va necesita butoane specifice de ștergere pentru fiecare tip de înregistrare (salariu, bonus, bonuri)
    // și va trebui să preia ID-ul înregistrării și tipul acțiunii de ștergere.
    // Deocamdată, nu avem butoane de ștergere pentru salariu/bonus/bonuri în tabel, doar pentru angajat.
    // Dacă vei adăuga butoane de ștergere la nivel de salariu/bonus/bonuri, ele vor trebui să aibă
    // data-action-type="delete_salary", data-action-type="delete_bonus", etc.
    angajatiSalariiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-record-btn')) { // Folosim o clasă generică pentru toate butoanele de ștergere
            const recordIdToDelete = e.target.getAttribute('data-id');
            const recordType = e.target.getAttribute('data-record-type'); // Ex: 'salariu', 'bonus', 'bonuri_masa'
            const angajatNume = e.target.closest('tr').querySelector('td[data-label="Nume Complet:"]').textContent;

            document.getElementById('deleteRecordId').value = recordIdToDelete;
            document.getElementById('deleteRecordActionType').value = `delete_${recordType}`; // Ex: delete_salary
            document.getElementById('deleteRecordType').textContent = recordType;
            document.getElementById('deleteRecordAngajatNume').textContent = angajatNume;
            
            new bootstrap.Modal(deleteRecordModal).show();
        }
    });

    confirmDeleteRecordBtn.addEventListener('click', function() {
        const recordIdToDelete = document.getElementById('deleteRecordId').value;
        const actionType = document.getElementById('deleteRecordActionType').value;
        const formData = new FormData();
        formData.append('action', actionType);
        formData.append('id', recordIdToDelete);

        fetch('process_salarii.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteRecordModal);
            if (modalInstance) { modalInstance.hide(); }
            location.reload();
        })
        .catch(error => { console.error('Eroare Ștergere Înregistrare:', error); alert('A apărut o eroare la ștergerea înregistrării.'); });
    });


    // --- Fix pentru blocarea paginii după închiderea modalurilor (generic) ---
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