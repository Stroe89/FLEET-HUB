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

// Preluăm lista de angajați existentă cu toate câmpurile noi
$angajati_list = [];
$sql_angajati = "SELECT * FROM angajati ORDER BY nume ASC, prenume ASC";
$result_angajati = $conn->query($sql_angajati);
if ($result_angajati) {
    while ($row = $result_angajati->fetch_assoc()) {
        $angajati_list[] = $row;
    }
}
$conn->close();

// Funcții și statusuri pentru filtrare (extinse)
$functii_angajati = ['Sofer', 'Dispecer', 'Mecanic', 'Administrator', 'Contabil', 'Manager', 'Altele'];
$statusuri_angajati = ['Activ', 'Inactiv', 'Concediu', 'Suspendat', 'Demisionat'];

// Categorii de permis de conducere (pentru afișare în detalii)
$categorii_permis_disponibile = ['A', 'A1', 'A2', 'B', 'B1', 'BE', 'C', 'C1', 'CE', 'C1E', 'D', 'D1', 'DE', 'D1E', 'Tr', 'Tb', 'Tv'];
?>

<title>NTS TOUR | Listă Angajați</title>

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
            <div class="breadcrumb-title pe-3">Listă Angajați</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Angajați</h4>
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

                        <a href="adauga-angajat.php" class="btn btn-primary mb-4">
                            <i class="bx bx-user-plus"></i> Adaugă Angajat Nou
                        </a>

                        <!-- Secțiunea de Filtrare -->
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
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume, email, telefon, CNP, CI...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Angajaților -->
                        <?php if (empty($angajati_list)): ?>
                            <div class="alert alert-info">Nu există angajați înregistrați.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nume Complet</th>
                                            <th>Cod Intern</th>
                                            <th>Funcție</th>
                                            <th>Dată Angajare</th>
                                            <th>Telefon</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Categorii Permis</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="angajatiTableBody">
                                        <?php foreach ($angajati_list as $angajat): ?>
                                            <tr 
                                                data-id="<?php echo $angajat['id']; ?>"
                                                data-nume="<?php echo htmlspecialchars($angajat['nume']); ?>"
                                                data-prenume="<?php echo htmlspecialchars($angajat['prenume']); ?>"
                                                data-cod-intern="<?php echo htmlspecialchars($angajat['cod_intern']); ?>"
                                                data-data-angajare="<?php echo htmlspecialchars($angajat['data_angajare']); ?>"
                                                data-functie="<?php echo htmlspecialchars($angajat['functie']); ?>"
                                                data-telefon="<?php echo htmlspecialchars($angajat['telefon']); ?>"
                                                data-email="<?php echo htmlspecialchars($angajat['email']); ?>"
                                                data-adresa="<?php echo htmlspecialchars($angajat['adresa']); ?>"
                                                data-salariu="<?php echo htmlspecialchars($angajat['salariu']); ?>"
                                                data-status="<?php echo htmlspecialchars($angajat['status']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($angajat['observatii']); ?>"
                                                data-data-nastere="<?php echo htmlspecialchars($angajat['data_nastere'] ?? ''); ?>"
                                                data-loc-nastere="<?php echo htmlspecialchars($angajat['loc_nastere'] ?? ''); ?>"
                                                data-nationalitate="<?php echo htmlspecialchars($angajat['nationalitate'] ?? ''); ?>"
                                                data-cnp="<?php echo htmlspecialchars($angajat['cnp'] ?? ''); ?>"
                                                data-serie-ci="<?php echo htmlspecialchars($angajat['serie_ci'] ?? ''); ?>"
                                                data-numar-ci="<?php echo htmlspecialchars($angajat['numar_ci'] ?? ''); ?>"
                                                data-numar-permis="<?php echo htmlspecialchars($angajat['numar_permis'] ?? ''); ?>"
                                                data-data-emitere-permis="<?php echo htmlspecialchars($angajat['data_emitere_permis'] ?? ''); ?>"
                                                data-data-expirare-permis="<?php echo htmlspecialchars($angajat['data_expirare_permis'] ?? ''); ?>"
                                                data-autoritate-emitenta-permis="<?php echo htmlspecialchars($angajat['autoritate_emitenta_permis'] ?? ''); ?>"
                                                data-categorii-permis="<?php echo htmlspecialchars($angajat['categorii_permis'] ?? ''); ?>"
                                                data-data-valabilitate-fisa-medicala="<?php echo htmlspecialchars($angajat['data_valabilitate_fisa_medicala'] ?? ''); ?>"
                                                data-data-valabilitate-aviz-psihologic="<?php echo htmlspecialchars($angajat['data_valabilitate_aviz_psihologic'] ?? ''); ?>"
                                                data-nume-contact-urgenta="<?php echo htmlspecialchars($angajat['nume_contact_urgenta'] ?? ''); ?>"
                                                data-relatie-contact-urgenta="<?php echo htmlspecialchars($angajat['relatie_contact_urgenta'] ?? ''); ?>"
                                                data-telefon-contact-urgenta="<?php echo htmlspecialchars($angajat['telefon_contact_urgenta'] ?? ''); ?>"
                                                data-atestate="<?php echo htmlspecialchars($angajat['atestate'] ?? ''); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume'] . ' ' . $angajat['cod_intern'] . ' ' . $angajat['functie'] . ' ' . ($angajat['telefon'] ?? '') . ' ' . ($angajat['email'] ?? '') . ' ' . ($angajat['cnp'] ?? '') . ' ' . ($angajat['numar_ci'] ?? ''))); ?>"
                                            >
                                                <td data-label="Nume Complet:"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></td>
                                                <td data-label="Cod Intern:"><?php echo htmlspecialchars($angajat['cod_intern']); ?></td>
                                                <td data-label="Funcție:"><?php echo htmlspecialchars($angajat['functie']); ?></td>
                                                <td data-label="Dată Angajare:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($angajat['data_angajare']))); ?></td>
                                                <td data-label="Telefon:"><?php echo htmlspecialchars($angajat['telefon'] ?? 'N/A'); ?></td>
                                                <td data-label="Email:"><?php echo htmlspecialchars($angajat['email'] ?? 'N/A'); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $angajat['status'])); ?>"><?php echo htmlspecialchars($angajat['status']); ?></span></td>
                                                <td data-label="Categorii Permis:"><?php echo htmlspecialchars($angajat['categorii_permis'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <a href="fise-individuale.php?id=<?php echo $angajat['id']; ?>" class="btn btn-sm btn-outline-info me-1 mb-1">Vezi Fișa</a>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-angajat-btn me-1 mb-1" data-bs-toggle="modal" data-bs-target="#addEditAngajatModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-angajat-btn mb-1" data-id="<?php echo $angajat['id']; ?>" data-nume="<?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Angajat -->
<div class="modal fade" id="addEditAngajatModal" tabindex="-1" aria-labelledby="addEditAngajatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> <!-- Modal mai mare pentru mai multe câmpuri -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditAngajatModalLabel">Adaugă Angajat Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="angajatForm" action="process_angajati.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="angajatAction" name="action" value="add">
                    <input type="hidden" id="angajatId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalNume" class="form-label">Nume:</label>
                            <input type="text" class="form-control" id="modalNume" name="nume" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalPrenume" class="form-label">Prenume:</label>
                            <input type="text" class="form-control" id="modalPrenume" name="prenume" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCodIntern" class="form-label">Cod Intern Angajat:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="modalCodIntern" name="cod_intern" readonly required>
                                <button class="btn btn-info" type="button" id="generateModalCodeBtn">Generează</button>
                            </div>
                            <small class="form-text text-muted">Acest cod este generat automat și este unic.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataAngajare" class="form-label">Dată Angajare:</label>
                            <input type="date" class="form-control" id="modalDataAngajare" name="data_angajare" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalFunctie" class="form-label">Funcție:</label>
                            <select class="form-select" id="modalFunctie" name="functie" required>
                                <option value="">Selectează funcția</option>
                                <?php foreach ($functii_angajati as $functie): ?>
                                    <option value="<?php echo htmlspecialchars($functie); ?>"><?php echo htmlspecialchars($functie); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalTelefon" class="form-label">Telefon:</label>
                            <input type="tel" class="form-control" id="modalTelefon" name="telefon">
                        </div>
                        <div class="col-md-6">
                            <label for="modalEmail" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="modalEmail" name="email">
                        </div>
                        <div class="col-12">
                            <label for="modalAdresa" class="form-label">Adresă:</label>
                            <textarea class="form-control" id="modalAdresa" name="adresa" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="modalSalariu" class="form-label">Salariu (RON):</label>
                            <input type="number" step="0.01" class="form-control" id="modalSalariu" name="salariu">
                        </div>
                        <div class="col-md-6">
                            <label for="modalStatus" class="form-label">Status:</label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_angajati as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații Generale:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>

                        <!-- Detalii Personale Suplimentare -->
                        <div class="col-12"><hr><h6 class="mt-2">Detalii Personale</h6></div>
                        <div class="col-md-4">
                            <label for="modalDataNastere" class="form-label">Dată Naștere:</label>
                            <input type="date" class="form-control" id="modalDataNastere" name="data_nastere">
                        </div>
                        <div class="col-md-4">
                            <label for="modalLocNastere" class="form-label">Loc Naștere:</label>
                            <input type="text" class="form-control" id="modalLocNastere" name="loc_nastere">
                        </div>
                        <div class="col-md-4">
                            <label for="modalNationalitate" class="form-label">Naționalitate:</label>
                            <input type="text" class="form-control" id="modalNationalitate" name="nationalitate" placeholder="Ex: Română">
                        </div>
                        <div class="col-md-6">
                            <label for="modalCnp" class="form-label">CNP:</label>
                            <input type="text" class="form-control" id="modalCnp" name="cnp" maxlength="13">
                        </div>
                        <div class="col-md-3">
                            <label for="modalSerieCi" class="form-label">Serie CI:</label>
                            <input type="text" class="form-control" id="modalSerieCi" name="serie_ci" maxlength="2">
                        </div>
                        <div class="col-md-3">
                            <label for="modalNumarCi" class="form-label">Număr CI:</label>
                            <input type="text" class="form-control" id="modalNumarCi" name="numar_ci" maxlength="6">
                        </div>

                        <!-- Detalii Permis de Conducere -->
                        <div class="col-12"><hr><h6 class="mt-2">Detalii Permis de Conducere</h6></div>
                        <div class="col-md-6">
                            <label for="modalNumarPermis" class="form-label">Număr Permis:</label>
                            <input type="text" class="form-control" id="modalNumarPermis" name="numar_permis">
                        </div>
                        <div class="col-md-6">
                            <label for="modalAutoritateEmitentaPermis" class="form-label">Autoritate Emitentă Permis:</label>
                            <input type="text" class="form-control" id="modalAutoritateEmitentaPermis" name="autoritate_emitenta_permis">
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataEmiterePermis" class="form-label">Dată Emitere Permis:</label>
                            <input type="date" class="form-control" id="modalDataEmiterePermis" name="data_emitere_permis">
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataExpirarePermis" class="form-label">Dată Expirare Permis:</label>
                            <input type="date" class="form-control" id="modalDataExpirarePermis" name="data_expirare_permis">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Categorii Permis:</label>
                            <div class="checkbox-group p-3">
                                <?php foreach ($categorii_permis_disponibile as $cat): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="modalCat_<?php echo htmlspecialchars($cat); ?>" name="categorii_permis[]" value="<?php echo htmlspecialchars($cat); ?>">
                                        <label class="form-check-label" for="modalCat_<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Detalii Medicale și Atestate -->
                        <div class="col-12"><hr><h6 class="mt-2">Detalii Medicale & Atestate</h6></div>
                        <div class="col-md-6">
                            <label for="modalDataFisaMedicala" class="form-label">Dată Valabilitate Fișă Medicală:</label>
                            <input type="date" class="form-control" id="modalDataFisaMedicala" name="data_valabilitate_fisa_medicala">
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataAvizPsihologic" class="form-label">Dată Valabilitate Aviz Psihologic:</label>
                            <input type="date" class="form-control" id="modalDataAvizPsihologic" name="data_valabilitate_aviz_psihologic">
                        </div>
                        <div class="col-12">
                            <label for="modalAtestate" class="form-label">Atestate (CPC, CPI, ADR, etc.):</label>
                            <textarea class="form-control" id="modalAtestate" name="atestate" rows="2" placeholder="Ex: CPC Marfă, CPI Persoane"></textarea>
                        </div>

                        <!-- Contact de Urgență -->
                        <div class="col-12"><hr><h6 class="mt-2">Contact de Urgență</h6></div>
                        <div class="col-md-4">
                            <label for="modalNumeContactUrgenta" class="form-label">Nume Contact Urgență:</label>
                            <input type="text" class="form-control" id="modalNumeContactUrgenta" name="nume_contact_urgenta">
                        </div>
                        <div class="col-md-4">
                            <label for="modalRelatieContactUrgenta" class="form-label">Relație Contact Urgență:</label>
                            <input type="text" class="form-control" id="modalRelatieContactUrgenta" name="relatie_contact_urgenta" placeholder="Ex: Soție, Frate">
                        </div>
                        <div class="col-md-4">
                            <label for="modalTelefonContactUrgenta" class="form-label">Telefon Contact Urgență:</label>
                            <input type="tel" class="form-control" id="modalTelefonContactUrgenta" name="telefon_contact_urgenta">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Angajat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Angajat -->
<div class="modal fade" id="deleteAngajatModal" tabindex="-1" aria-labelledby="deleteAngajatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAngajatModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi angajatul <strong id="deleteAngajatName"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteAngajatId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAngajatBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditAngajatModal = document.getElementById('addEditAngajatModal');
    const angajatForm = document.getElementById('angajatForm');
    const addAngajatBtn = document.getElementById('addAngajatBtn');
    const deleteAngajatModal = document.getElementById('deleteAngajatModal');
    const confirmDeleteAngajatBtn = document.getElementById('confirmDeleteAngajatBtn');
    const angajatiTableBody = document.getElementById('angajatiTableBody');

    // Câmpuri din modal
    const modalCodIntern = document.getElementById('modalCodIntern');
    const generateModalCodeBtn = document.getElementById('generateModalCodeBtn');
    const modalDataAngajare = document.getElementById('modalDataAngajare');
    const modalNume = document.getElementById('modalNume');
    const modalPrenume = document.getElementById('modalPrenume');
    const modalFunctie = document.getElementById('modalFunctie');
    const modalTelefon = document.getElementById('modalTelefon');
    const modalEmail = document.getElementById('modalEmail');
    const modalAdresa = document.getElementById('modalAdresa');
    const modalSalariu = document.getElementById('modalSalariu');
    const modalStatus = document.getElementById('modalStatus');
    const modalObservatii = document.getElementById('modalObservatii');
    // Noi câmpuri
    const modalDataNastere = document.getElementById('modalDataNastere');
    const modalLocNastere = document.getElementById('modalLocNastere');
    const modalNationalitate = document.getElementById('modalNationalitate');
    const modalCnp = document.getElementById('modalCnp');
    const modalSerieCi = document.getElementById('modalSerieCi');
    const modalNumarCi = document.getElementById('modalNumarCi');
    const modalNumarPermis = document.getElementById('modalNumarPermis');
    const modalDataEmiterePermis = document.getElementById('modalDataEmiterePermis');
    const modalDataExpirarePermis = document.getElementById('modalDataExpirarePermis');
    const modalAutoritateEmitentaPermis = document.getElementById('modalAutoritateEmitentaPermis');
    const modalAtestate = document.getElementById('modalAtestate');
    const modalDataFisaMedicala = document.getElementById('modalDataFisaMedicala');
    const modalDataAvizPsihologic = document.getElementById('modalDataAvizPsihologic');
    const modalNumeContactUrgenta = document.getElementById('modalNumeContactUrgenta');
    const modalRelatieContactUrgenta = document.getElementById('modalRelatieContactUrgenta');
    const modalTelefonContactUrgenta = document.getElementById('modalTelefonContactUrgenta');

    // Funcție pentru generarea unui cod intern unic
    function generateUniqueCode(length = 8) {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    // Filtrare Tabel
    const filterFunctie = document.getElementById('filterFunctie');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedFunctie = filterFunctie.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#angajatiTableBody tr').forEach(row => {
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


    // Deschide modalul pentru adăugare
    addAngajatBtn.addEventListener('click', function() {
        console.log("Adaugă Angajat Nou - Buton Clicked");
        angajatForm.reset(); // Resetează toate câmpurile formularului
        document.getElementById('angajatAction').value = 'add';
        document.getElementById('angajatId').value = '';
        document.getElementById('addEditAngajatModalLabel').textContent = 'Adaugă Angajat Nou';
        
        // Generează codul la deschiderea modalului (pentru adăugare)
        modalCodIntern.value = generateUniqueCode();
        // Setează data angajării la data curentă implicit
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        modalDataAngajare.value = formattedDate;

        // Resetează și alte câmpuri la valori implicite/goale
        modalNationalitate.value = 'Română'; // Setează naționalitatea implicită
        document.querySelectorAll('input[name="categorii_permis[]"]').forEach(checkbox => {
            checkbox.checked = false; // Deselectează toate categoriile de permis
        });
        console.log("Modal Adaugă Angajat resetat și pre-populat.");
    });

    // Deschide modalul pentru editare
    angajatiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-angajat-btn')) {
            console.log("Editează Angajat - Buton Clicked");
            const row = e.target.closest('tr');
            const angajatId = row.getAttribute('data-id');
            console.log("Angajat ID:", angajatId);

            document.getElementById('angajatAction').value = 'edit';
            document.getElementById('angajatId').value = angajatId;
            document.getElementById('addEditAngajatModalLabel').textContent = 'Editează Angajat';

            // Populează câmpurile din formularul de editare
            modalNume.value = row.getAttribute('data-nume');
            modalPrenume.value = row.getAttribute('data-prenume');
            modalCodIntern.value = row.getAttribute('data-cod-intern');
            modalDataAngajare.value = row.getAttribute('data-data-angajare');
            modalFunctie.value = row.getAttribute('data-functie');
            modalTelefon.value = row.getAttribute('data-telefon');
            modalEmail.value = row.getAttribute('data-email');
            modalAdresa.value = row.getAttribute('data-adresa');
            modalSalariu.value = row.getAttribute('data-salariu');
            modalStatus.value = row.getAttribute('data-status');
            modalObservatii.value = row.getAttribute('data-observatii');

            // Populează noile câmpuri
            modalDataNastere.value = row.getAttribute('data-data-nastere');
            modalLocNastere.value = row.getAttribute('data-loc-nastere');
            modalNationalitate.value = row.getAttribute('data-nationalitate');
            modalCnp.value = row.getAttribute('data-cnp');
            modalSerieCi.value = row.getAttribute('data-serie-ci');
            modalNumarCi.value = row.getAttribute('data-numar-ci');
            modalNumarPermis.value = row.getAttribute('data-numar-permis');
            modalDataEmiterePermis.value = row.getAttribute('data-data-emitere-permis');
            modalDataExpirarePermis.value = row.getAttribute('data-data-expirare-permis');
            modalAutoritateEmitentaPermis.value = row.getAttribute('data-autoritate-emitenta-permis');
            modalAtestate.value = row.getAttribute('data-atestate');
            modalDataFisaMedicala.value = row.getAttribute('data-data-valabilitate-fisa-medicala');
            modalDataAvizPsihologic.value = row.getAttribute('data-data-valabilitate-aviz-psihologic');
            modalNumeContactUrgenta.value = row.getAttribute('data-nume-contact-urgenta');
            modalRelatieContactUrgenta.value = row.getAttribute('data-relatie-contact-urgenta');
            modalTelefonContactUrgenta.value = row.getAttribute('data-telefon-contact-urgenta');

            // Populează categoriile de permis (checkbox-uri)
            const categoriiPermisAngajat = (row.getAttribute('data-categorii-permis') || '').split(',');
            document.querySelectorAll('input[name="categorii_permis[]"]').forEach(checkbox => {
                checkbox.checked = categoriiPermisAngajat.includes(checkbox.value);
            });
            console.log("Modal Editează Angajat populat cu datele angajatului:", angajatId);
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    angajatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log("Formular Angajat - Submit Initiated");
        const formData = new FormData(angajatForm);

        // Colectează categoriile de permis selectate
        const selectedCategories = [];
        document.querySelectorAll('input[name="categorii_permis[]"]:checked').forEach(checkbox => {
            selectedCategories.push(checkbox.value);
        });
        formData.set('categorii_permis', selectedCategories.join(',')); // Actualizează valoarea în FormData

        // Logăm FormData pentru a vedea ce se trimite
        for (let pair of formData.entries()) {
            console.log(pair[0]+ ': ' + pair[1]); 
        }

        fetch('process_angajati.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log("Răspuns de la server:", data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditAngajatModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            // Verificăm dacă răspunsul conține "Eroare" pentru a afișa alertă
            if (data.includes("Eroare:")) {
                alert(data); // Afișează eroarea direct utilizatorului
            } else {
                alert('Modificările au fost salvate cu succes!');
                location.reload(); // Reîncarcă pagina pentru a vedea modificările
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea angajatului:', error);
            alert('A apărut o eroare la salvarea angajatului.');
        });
    });

    // Ștergerea angajatului
    angajatiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-angajat-btn')) {
            console.log("Șterge Angajat - Buton Clicked");
            const angajatIdToDelete = e.target.getAttribute('data-id');
            const angajatName = e.target.closest('tr').querySelector('td[data-label="Nume Complet:"]').textContent; 
            console.log("Angajat ID pentru ștergere:", angajatIdToDelete, "Nume:", angajatName);

            document.getElementById('deleteAngajatId').value = angajatIdToDelete;
            document.getElementById('deleteAngajatName').textContent = angajatName; 
            
            const deleteModalInstance = new bootstrap.Modal(deleteAngajatModal);
            deleteModalInstance.show();
            console.log("Modal Șterge Angajat deschis.");
        }
    });

    confirmDeleteAngajatBtn.addEventListener('click', function() {
        console.log("Confirmă Ștergere - Buton Clicked");
        const angajatIdToDelete = document.getElementById('deleteAngajatId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', angajatIdToDelete);

        fetch('process_angajati.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log("Răspuns de la server (ștergere):", data);
            const modalInstance = bootstrap.Modal.getInstance(deleteAngajatModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            if (data.includes("Eroare:")) {
                alert(data); // Afișează eroarea direct utilizatorului
            } else {
                alert('Angajatul a fost șters cu succes!');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Eroare la ștergerea angajatului:', error);
            alert('A apărut o eroare la ștergerea angajatului.');
        });
    });

    // Fix pentru blocarea paginii după închiderea modalurilor (generic)
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            console.log("Modal ascuns, curățare body.");
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    });
});
</script>
