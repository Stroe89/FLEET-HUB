<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Inițializează variabilele de mesaj pentru a evita "Undefined variable" warnings
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

// Preluăm lista de angajați pentru dropdown-uri de filtrare și adăugare
$angajati_list = [];
$stmt_angajati = $conn->prepare("SELECT id, nume, prenume FROM angajati ORDER BY nume ASC, prenume ASC");
if ($stmt_angajati) {
    $stmt_angajati->execute();
    $result_angajati = $stmt_angajati->get_result();
    while ($row = $result_angajati->fetch_assoc()) {
        $angajati_list[] = $row;
    }
    $stmt_angajati->close();
}

// Preluăm lista de contracte angajați
$contracte_list = [];
$sql_contracte = "
    SELECT ca.*, a.nume, a.prenume, a.id as angajat_id_from_join
    FROM contracte_angajati ca
    JOIN angajati a ON ca.id_angajat = a.id
    ORDER BY ca.data_semnare DESC
";
$result_contracte = $conn->query($sql_contracte);
if ($result_contracte) {
    while ($row = $result_contracte->fetch_assoc()) {
        $contracte_list[] = $row;
    }
}
$conn->close();

// Tipuri de contracte pentru filtrare (poți adăuga mai multe sau le poți prelua dintr-o tabelă)
$tipuri_contract = ['Muncă', 'Colaborare', 'Confidențialitate', 'Servicii', 'Altele'];
?>

<title>NTS TOUR | Contracte Angajați</title>

<link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    /* Stiluri generale preluate din temă */
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

    /* Stiluri specifice pentru tabelul de contracte */
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
    /* Badge-uri pentru tipuri de contract (exemplu, extindeți după nevoi) */
    .badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
        color: #fff; /* Default text color for badges */
    }
    .badge-tip-muncă { background-color: #0d6efd !important; }
    .badge-tip-colaborare { background-color: #28a745 !important; }
    .badge-tip-confidențialitate { background-color: #6c757d !important; }
    .badge-tip-servicii { background-color: #17a2b8 !important; }
    .badge-tip-altele { background-color: #ffc107 !important; color: #343a40 !important; } /* Culoare text diferită pentru warning */

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
        /* Ascunde headerele originale ale tabelului */
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

    /* Stiluri pentru DataTables și Bootstrap Toasts */
    /* Adjust DataTables specific styles for dark theme */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_processing,
    .dataTables_wrapper .dataTables_paginate {
        color: #e0e0e0 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #e0e0e0 !important;
        background-color: #3b435a !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background-color: #0d6efd !important;
        color: #fff !important;
        border-color: #0d6efd !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #4a536b !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        background-color: #1a2035 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 0.5rem !important;
        padding: 0.375rem 0.75rem;
    }
    
    /* Bootstrap Toast Container */
    .toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1080; /* Higher than modals if needed */
    }
    .toast {
        background-color: #2a3042;
        color: #e0e0e0;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .toast-header {
        background-color: #3b435a;
        color: #ffffff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .toast-body {
        background-color: #2a3042;
        color: #e0e0e0;
    }
    .toast-success .toast-header { background-color: #2c5234; color: #fff; }
    .toast-danger .toast-header { background-color: #5c2c31; color: #fff; }
    .toast-info .toast-header { background-color: #203354; color: #fff; }

</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Contracte Angajați</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Contracte Angajați</h4>
                        <hr>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditContractModal" id="addContractBtn">
                            <i class="bx bx-plus"></i> Adaugă Contract Nou
                        </button>

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterAngajat" class="form-label">Filtrează după Angajat:</label>
                                <select class="form-select" id="filterAngajat">
                                    <option value="">Toți Angajații</option>
                                    <?php foreach ($angajati_list as $angajat): ?>
                                        <option value="<?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?>"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterTipContract" class="form-label">Filtrează după Tip Contract:</label>
                                <select class="form-select" id="filterTipContract">
                                    <option value="">Toate Tipurile</option>
                                    <?php foreach ($tipuri_contract as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Căutarea globală este gestionată direct de DataTables -->
                            <div class="col-md-4 mb-3">
                                <label for="globalSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="globalSearch" placeholder="Cauta text în tabel...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Contractelor -->
                        <?php if (empty($contracte_list)): ?>
                            <div class="alert alert-info">Nu există contracte de angajați înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <!-- ID-ul tabelului este acum 'contracteDataTable' -->
                                <table class="table table-hover" id="contracteDataTable">
                                    <thead>
                                        <tr>
                                            <th>Angajat</th>
                                            <th>Tip Contract</th>
                                            <th>Număr Contract</th>
                                            <th>Dată Semnare</th>
                                            <th>Dată Început</th>
                                            <th>Dată Sfârșit</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contracte_list as $contract): ?>
                                            <tr 
                                                data-id="<?php echo $contract['id']; ?>"
                                                data-id-angajat="<?php echo $contract['id_angajat']; ?>"
                                                data-tip-contract="<?php echo htmlspecialchars($contract['tip_contract']); ?>"
                                                data-numar-contract="<?php echo htmlspecialchars($contract['numar_contract']); ?>"
                                                data-data-semnare="<?php echo htmlspecialchars($contract['data_semnare']); ?>"
                                                data-data-inceput="<?php echo htmlspecialchars($contract['data_inceput']); ?>"
                                                data-data-sfarsit="<?php echo htmlspecialchars($contract['data_sfarsit'] ?? ''); ?>"
                                                data-cale-fisier="<?php echo htmlspecialchars($contract['cale_fisier'] ?? ''); ?>"
                                                data-nume-original-fisier="<?php echo htmlspecialchars($contract['nume_original_fisier'] ?? ''); ?>"
                                                data-observatii="<?php echo htmlspecialchars($contract['observatii']); ?>"
                                            >
                                                <td data-label="Angajat:"><?php echo htmlspecialchars($contract['nume'] . ' ' . $contract['prenume']); ?></td>
                                                <td data-label="Tip Contract:"><span class="badge badge-tip-<?php echo strtolower(str_replace(' ', '_', $contract['tip_contract'])); ?>"><?php echo htmlspecialchars($contract['tip_contract']); ?></span></td>
                                                <td data-label="Număr Contract:"><?php echo htmlspecialchars($contract['numar_contract']); ?></td>
                                                <td data-label="Dată Semnare:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($contract['data_semnare']))); ?></td>
                                                <td data-label="Dată Început:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($contract['data_inceput']))); ?></td>
                                                <td><?php echo htmlspecialchars($contract['data_sfarsit'] ? date('d.m.Y', strtotime($contract['data_sfarsit'])) : 'Nedeterminată'); ?></td>
                                                <td>
                                                    <!-- Buton "Vezi Fișă Angajat" -->
                                                    <a href="fise-individuale.php?id=<?php echo htmlspecialchars($contract['id_angajat']); ?>" class="btn btn-sm btn-info mb-1 w-100"><i class="bx bx-user"></i> Vezi Fișă Angajat</a>
                                                    <?php if (!empty($contract['cale_fisier'])): ?>
                                                        <a href="download_document.php?id=<?php echo $contract['id']; ?>&type=contract" class="btn btn-sm btn-outline-info mb-1 w-100"><i class="bx bx-download"></i> Descarcă</a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-contract-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#addEditContractModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-contract-btn w-100" data-id="<?php echo $contract['id']; ?>" data-numar="<?php echo htmlspecialchars($contract['numar_contract']); ?>">Șterge</button>
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

<!-- Container pentru Toast-uri -->
<div class="toast-container"></div>

<!-- Modal Adaugă/Editează Contract -->
<div class="modal fade" id="addEditContractModal" tabindex="-1" aria-labelledby="addEditContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditContractModalLabel">Adaugă Contract Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="contractForm" action="process_contracte_angajati.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="contractAction" name="action" value="add">
                    <input type="hidden" id="contractId" name="id">
                    <input type="hidden" id="existingFilePath" name="existing_file_path">
                    <input type="hidden" id="existingFileName" name="existing_file_name">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalSelectAngajat" class="form-label">Angajat:</label>
                            <select class="form-select" id="modalSelectAngajat" name="id_angajat" required>
                                <option value="">Alege un angajat</option>
                                <?php foreach ($angajati_list as $angajat): ?>
                                    <option value="<?php echo $angajat['id']; ?>"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Te rog selectează un angajat.
                            </div>
                            <!-- Link pentru adăugare angajat dacă nu e în listă -->
                            <small class="form-text text-muted mt-2">
                                Angajatul nu este în listă? <a href="adauga-angajat.php" target="_blank" class="btn btn-sm btn-link p-0 align-baseline">Adaugă un angajat nou aici.</a>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label for="modalTipContract" class="form-label">Tip Contract:</label>
                            <select class="form-select" id="modalTipContract" name="tip_contract" required>
                                <option value="">Selectează Tipul</option>
                                <?php foreach ($tipuri_contract as $tip): ?>
                                    <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Te rog selectează un tip de contract.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalNumarContract" class="form-label">Număr Contract:</label>
                            <input type="text" class="form-control" id="modalNumarContract" name="numar_contract" required>
                            <div class="invalid-feedback">
                                Te rog introdu numărul contractului.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataSemnare" class="form-label">Dată Semnare:</label>
                            <input type="date" class="form-control" id="modalDataSemnare" name="data_semnare" required>
                            <div class="invalid-feedback">
                                Te rog introdu data de semnare.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataInceput" class="form-label">Dată Început:</label>
                            <input type="date" class="form-control" id="modalDataInceput" name="data_inceput" required>
                            <div class="invalid-feedback">
                                Te rog introdu data de început.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataSfarsit" class="form-label">Dată Sfârșit (opțional):</label>
                            <input type="date" class="form-control" id="modalDataSfarsit" name="data_sfarsit">
                            <small class="form-text text-muted">Lăsați gol pentru contracte pe perioadă nedeterminată.</small>
                        </div>
                        <div class="col-12">
                            <label for="modalContractFile" class="form-label">Încarcă Fișier Contract (PDF/Imagine, opțional):</label>
                            <input class="form-control" type="file" id="modalContractFile" name="contract_file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted" id="currentFileText" style="display:none;">Fișier curent: <a href="#" id="currentFileLink" target="_blank"></a> <span class="text-danger" id="removeFileLink" style="cursor:pointer;">[Elimină]</span></small>
                            <small class="form-text text-muted" id="fileUploadInfo">Se va folosi fișierul existent dacă nu încarci unul nou.</small>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Contract</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Contract -->
<div class="modal fade" id="deleteContractModal" tabindex="-1" aria-labelledby="deleteContractModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteContractModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi contractul <strong id="deleteContractNumber"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteContractId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteContractBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditContractModal = document.getElementById('addEditContractModal');
    const contractForm = document.getElementById('contractForm');
    const addContractBtn = document.getElementById('addContractBtn');
    const deleteContractModal = document.getElementById('deleteContractModal');
    const confirmDeleteContractBtn = document.getElementById('confirmDeleteContractBtn');
    
    // Inițializează DataTables
    const contracteDataTable = $('#contracteDataTable').DataTable({
        "language": {
            "sEmptyTable":     "Nu există date în tabel",
            "sInfo":           "Afișez _START_ până la _END_ din _TOTAL_ înregistrări",
            "sInfoEmpty":      "Afișez 0 până la 0 din 0 înregistrări",
            "sInfoFiltered":   "(filtrat dintr-un total de _MAX_ înregistrări)",
            "sInfoPostFix":    "",
            "sInfoThousands":  ",",
            "sLengthMenu":     "Afișează _MENU_ înregistrări",
            "sLoadingRecords": "Încărcare...",
            "sProcessing":     "Procesare...",
            "sSearch":         "Caută:",
            "sZeroRecords":    "Nu am găsit înregistrări care să se potrivească",
            "oPaginate": {
                "sFirst":    "Prima",
                "sLast":     "Ultima",
                "sNext":     "Următoarea",
                "sPrevious": "Anteriorul"
            },
            "oAria": {
                "sSortAscending":  ": activează pentru a sorta coloana ascendent",
                "sSortDescending": ": activează pentru a sorta coloana descendent"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [6] } // Dezactivează sortarea pe coloana de Acțiuni
        ],
        "order": [[ 3, "desc" ]] // Sortează după "Dată Semnare" descendent implicit
    });

    const currentFileText = document.getElementById('currentFileText');
    const currentFileLink = document.getElementById('currentFileLink');
    const removeFileLink = document.getElementById('removeFileLink');
    const modalContractFile = document.getElementById('modalContractFile');
    const fileUploadInfo = document.getElementById('fileUploadInfo');

    const filterAngajat = document.getElementById('filterAngajat');
    const filterTipContract = document.getElementById('filterTipContract');
    const globalSearchInput = document.getElementById('globalSearch'); // Selector pentru inputul de căutare globală

    // Căutarea globală DataTables
    globalSearchInput.addEventListener('keyup', function() {
        contracteDataTable.search(this.value).draw();
    });

    filterAngajat.addEventListener('change', function() {
        contracteDataTable.columns(0).search(this.value).draw(); // Filtrează pe prima coloană (Angajat)
    });

    filterTipContract.addEventListener('change', function() {
        contracteDataTable.columns(1).search(this.value).draw(); // Filtrează pe a doua coloană (Tip Contract)
    });
    
    // Funcție pentru afișarea Toast-urilor Bootstrap
    function showToast(type, message) {
        const toastContainer = document.querySelector('.toast-container');
        const toastId = `toast-${Date.now()}`;
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    // Deschide modalul pentru adăugare
    addContractBtn.addEventListener('click', function() {
        contractForm.reset();
        contractForm.classList.remove('was-validated'); // Elimină clasele de validare
        document.getElementById('contractAction').value = 'add';
        document.getElementById('contractId').value = '';
        document.getElementById('addEditContractModalLabel').textContent = 'Adaugă Contract Nou';
        
        // Setează datele implicite la data curentă
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        document.getElementById('modalDataSemnare').value = formattedDate;
        document.getElementById('modalDataInceput').value = formattedDate;
        document.getElementById('modalDataSfarsit').value = ''; // Asigură că este gol pentru nedeterminată
        
        currentFileText.style.display = 'none'; // Ascunde info fișier curent
        modalContractFile.value = ''; // Golește input-ul de fișier
        document.getElementById('existingFilePath').value = '';
        document.getElementById('existingFileName').value = '';
        fileUploadInfo.textContent = 'Încarcă Fișier Contract (PDF/Imagine, opțional):'; // Reset info text
    });

    // Deschide modalul pentru editare
    // Delegare de eveniment pentru butoanele de editare din tabel
    $('#contracteDataTable tbody').on('click', '.edit-contract-btn', function() {
        const row = $(this).closest('tr');
        // const data = contracteDataTable.row(row).data(); // Aceasta ar fi folosită cu server-side processing, nu este cazul aici.

        contractForm.classList.remove('was-validated'); // Elimină clasele de validare
        document.getElementById('contractAction').value = 'edit';
        document.getElementById('contractId').value = row.attr('data-id'); // Folosim attr pentru id
        document.getElementById('addEditContractModalLabel').textContent = 'Editează Contract';

        document.getElementById('modalSelectAngajat').value = row.attr('data-id-angajat');
        document.getElementById('modalTipContract').value = row.attr('data-tip-contract');
        document.getElementById('modalNumarContract').value = row.attr('data-numar-contract');
        document.getElementById('modalDataSemnare').value = row.attr('data-data-semnare');
        document.getElementById('modalDataInceput').value = row.attr('data-data-inceput');
        document.getElementById('modalDataSfarsit').value = row.attr('data-data-sfarsit');
        document.getElementById('modalObservatii').value = row.attr('data-observatii');

        const currentFilePath = row.attr('data-cale-fisier');
        const currentOriginalFileName = row.attr('data-nume-original-fisier'); // Corectat numele atributului data-

        if (currentFilePath && currentFilePath !== 'null' && currentFilePath !== '') {
            currentFileLink.href = currentFilePath;
            currentFileLink.textContent = currentOriginalFileName || 'Fișier atașat';
            document.getElementById('existingFilePath').value = currentFilePath;
            document.getElementById('existingFileName').value = currentOriginalFileName;
            currentFileText.style.display = 'block';
            fileUploadInfo.textContent = 'Se va folosi fișierul existent dacă nu încarci unul nou.';
        } else {
            currentFileText.style.display = 'none';
            document.getElementById('existingFilePath').value = '';
            document.getElementById('existingFileName').value = '';
            fileUploadInfo.textContent = 'Niciun fișier atașat. Poți încărca unul acum.';
        }
        modalContractFile.value = ''; // Golește input-ul de fișier la editare
    });

    // Elimina fișierul existent din modal la editare (fără a-l șterge de pe server încă)
    removeFileLink.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('existingFilePath').value = '';
        document.getElementById('existingFileName').value = '';
        currentFileText.style.display = 'none';
        fileUploadInfo.textContent = 'Fișierul existent a fost eliminat. Poți încărca un fișier nou.';
    });


    // Trimiterea formularului (Adaugă/Editează) cu AJAX
    contractForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validare Bootstrap client-side
        if (!contractForm.checkValidity()) {
            e.stopPropagation();
            contractForm.classList.add('was-validated');
            showToast('danger', 'Te rog completează toate câmpurile obligatorii.');
            return;
        }
        contractForm.classList.add('was-validated'); // Asigură că validarea vizuală e aplicată

        const formData = new FormData(contractForm);
        
        fetch('process_contracte_angajati.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Dacă răspunsul nu este OK (e.g., 500 Internal Server Error), aruncă o eroare
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json(); // Așteaptă un răspuns JSON
        })
        .then(data => {
            const modalInstance = bootstrap.Modal.getInstance(addEditContractModal);
            if (modalInstance) {
                modalInstance.hide();
            }

            if (data.success) {
                showToast('success', data.message);
                // Reîncărcăm pagina pentru a asigura sincronizarea completă cu DB și DataTables
                setTimeout(() => { // Adăugat un scurt delay pentru a vedea toast-ul
                    location.reload(); 
                }, 500); // 0.5 secunde
            } else {
                showToast('danger', data.message || 'A apărut o eroare la salvarea contractului.');
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea contractului:', error);
            showToast('danger', 'A apărut o eroare la salvarea contractului. Detalii: ' + error.message);
        });
    });

    // Ștergerea contractului - delegare de eveniment
    $('#contracteDataTable tbody').on('click', '.delete-contract-btn', function() {
        const contractIdToDelete = $(this).attr('data-id');
        const contractNumber = $(this).attr('data-numar'); // Folosim noul atribut data-numar
        document.getElementById('deleteContractId').value = contractIdToDelete;
        document.getElementById('deleteContractNumber').textContent = contractNumber;
        const deleteModalInstance = new bootstrap.Modal(deleteContractModal);
        deleteModalInstance.show();
    });

    confirmDeleteContractBtn.addEventListener('click', function() {
        const contractIdToDelete = document.getElementById('deleteContractId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', contractIdToDelete);

        fetch('process_contracte_angajati.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json(); // Așteaptă un răspuns JSON
        })
        .then(data => {
            const modalInstance = bootstrap.Modal.getInstance(deleteContractModal);
            if (modalInstance) {
                modalInstance.hide();
            }

            if (data.success) {
                showToast('success', data.message);
                // Elimină rândul din DataTables fără reîncărcarea paginii
                contracteDataTable.row($(`tr[data-id="${contractIdToDelete}"]`)).remove().draw();
            } else {
                showToast('danger', data.message || 'A apărut o eroare la ștergerea contractului.');
            }
        })
        .catch(error => {
            console.error('Eroare la ștergerea contractului:', error);
            showToast('danger', 'A apărut o eroare la ștergerea contractului. Detalii: ' + error.message);
        });
    });
});
</script>
