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
$stmt_clienti = $conn->prepare("SELECT id, nume_companie, persoana_contact FROM clienti ORDER BY nume_companie ASC");
if ($stmt_clienti) {
    $stmt_clienti->execute();
    $result_clienti = $stmt_clienti->get_result();
    while ($row = $result_clienti->fetch_assoc()) {
        $clienti_list[] = $row;
    }
    $stmt_clienti->close();
}

// Preluăm lista de oferte
$oferte_list = [];
$sql_oferte = "
    SELECT ot.*, c.nume_companie, c.persoana_contact
    FROM oferte_transport ot
    JOIN clienti c ON ot.id_client = c.id
    ORDER BY ot.data_oferta DESC
";
$result_oferte = $conn->query($sql_oferte);
if ($result_oferte) {
    while ($row = $result_oferte->fetch_assoc()) {
        $oferte_list[] = $row;
    }
}
$conn->close();

// Statusuri pentru filtrare
$statusuri_oferta = ['Emisa', 'Acceptata', 'Refuzata', 'Anulata'];
?>

<title>NTS TOUR | Ofertare Transport</title>

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

    /* Stiluri specifice pentru tabelul de oferte */
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
    .badge-status-acceptata { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-refuzata { background-color: #6c757d !important; color: #fff !important; }
    .badge-status-anulata { background-color: #dc3545 !important; color: #fff !important; }

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
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Ofertare Transport</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Oferte Transport</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditOfertaModal" id="addOfertaBtn">
                            <i class="bx bx-plus"></i> Adaugă Ofertă Nouă
                        </button>

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterClient" class="form-label">Filtrează după Client:</label>
                                <select class="form-select" id="filterClient">
                                    <option value="all">Toți Clienții</option>
                                    <?php foreach ($clienti_list as $client): ?>
                                        <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['nume_companie'] . ' (' . $client['persoana_contact'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_oferta as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta număr ofertă, descriere, client...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Ofertelor -->
                        <?php if (empty($oferte_list)): ?>
                            <div class="alert alert-info">Nu există oferte înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Nr. Ofertă</th>
                                            <th>Dată Ofertă</th>
                                            <th>Descriere Serviciu</th>
                                            <th>Valoare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="oferteTableBody">
                                        <?php foreach ($oferte_list as $oferta): ?>
                                            <tr 
                                                data-id="<?php echo $oferta['id']; ?>"
                                                data-id-client="<?php echo $oferta['id_client']; ?>"
                                                data-numar-oferta="<?php echo htmlspecialchars($oferta['numar_oferta']); ?>"
                                                data-data-oferta="<?php echo htmlspecialchars($oferta['data_oferta']); ?>"
                                                data-descriere-serviciu="<?php echo htmlspecialchars($oferta['descriere_serviciu']); ?>"
                                                data-valoare-oferta="<?php echo htmlspecialchars($oferta['valoare_oferta']); ?>"
                                                data-moneda="<?php echo htmlspecialchars($oferta['moneda']); ?>"
                                                data-status="<?php echo htmlspecialchars($oferta['status']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($oferta['observatii']); ?>"
                                                data-cale-pdf="<?php echo htmlspecialchars($oferta['cale_pdf']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($oferta['numar_oferta'] . ' ' . $oferta['nume_companie'] . ' ' . $oferta['persoana_contact'] . ' ' . $oferta['descriere_serviciu'])); ?>"
                                            >
                                                <td data-label="Client:"><?php echo htmlspecialchars($oferta['nume_companie'] . ' (' . $oferta['persoana_contact'] . ')'); ?></td>
                                                <td data-label="Nr. Ofertă:"><?php echo htmlspecialchars($oferta['numar_oferta']); ?></td>
                                                <td data-label="Dată Ofertă:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($oferta['data_oferta']))); ?></td>
                                                <td data-label="Descriere Serviciu:"><?php echo htmlspecialchars(mb_strimwidth($oferta['descriere_serviciu'], 0, 50, "...")); ?></td>
                                                <td data-label="Valoare:"><?php echo htmlspecialchars($oferta['valoare_oferta'] . ' ' . $oferta['moneda']); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower($oferta['status']); ?>"><?php echo htmlspecialchars($oferta['status']); ?></span></td>
                                                <td>
                                                    <?php if (!empty($oferta['cale_pdf'])): ?>
                                                        <a href="<?php echo htmlspecialchars($oferta['cale_pdf']); ?>" target="_blank" class="btn btn-sm btn-outline-info mb-1 w-100"><i class="bx bxs-file-pdf"></i> Vezi PDF</a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-oferta-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#addEditOfertaModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-oferta-btn w-100" data-id="<?php echo $oferta['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Ofertă -->
<div class="modal fade" id="addEditOfertaModal" tabindex="-1" aria-labelledby="addEditOfertaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditOfertaModalLabel">Adaugă Ofertă Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ofertaForm" action="process_oferte.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="ofertaAction" name="action" value="add">
                    <input type="hidden" id="ofertaId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalSelectClient" class="form-label">Client:</label>
                            <select class="form-select" id="modalSelectClient" name="id_client" required>
                                <option value="">Alege un client</option>
                                <?php foreach ($clienti_list as $client): ?>
                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['nume_companie'] . ' (' . $client['persoana_contact'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalNumarOferta" class="form-label">Număr Ofertă:</label>
                            <input type="text" class="form-control" id="modalNumarOferta" name="numar_oferta" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataOferta" class="form-label">Dată Ofertă:</label>
                            <input type="date" class="form-control" id="modalDataOferta" name="data_oferta" required>
                        </div>
                        <div class="col-12">
                            <label for="modalDescriereServiciu" class="form-label">Descriere Serviciu:</label>
                            <textarea class="form-control" id="modalDescriereServiciu" name="descriere_serviciu" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="modalValoareOferta" class="form-label">Valoare Ofertă:</label>
                            <input type="number" step="0.01" class="form-control" id="modalValoareOferta" name="valoare_oferta" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalMoneda" class="form-label">Monedă:</label>
                            <input type="text" class="form-control" id="modalMoneda" name="moneda" value="RON" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalStatus" class="form-label">Status:</label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_oferta as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                        <!-- Calea PDF - nu este editabilă direct, este generată -->
                        <div class="col-12" id="pdfPathDisplay" style="display:none;">
                            <label class="form-label">Cale PDF:</label>
                            <p id="modalCalePdf" class="form-control-plaintext text-white"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="button" class="btn btn-info" id="generatePdfBtn" style="display:none;"><i class="bx bxs-file-pdf"></i> Generează PDF</button>
                    <button type="submit" class="btn btn-primary">Salvează Ofertă</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Ofertă -->
<div class="modal fade" id="deleteOfertaModal" tabindex="-1" aria-labelledby="deleteOfertaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteOfertaModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi această ofertă? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteOfertaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteOfertaBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditOfertaModal = document.getElementById('addEditOfertaModal');
    const ofertaForm = document.getElementById('ofertaForm');
    const addOfertaBtn = document.getElementById('addOfertaBtn');
    const deleteOfertaModal = document.getElementById('deleteOfertaModal');
    const confirmDeleteOfertaBtn = document.getElementById('confirmDeleteOfertaBtn');
    const oferteTableBody = document.getElementById('oferteTableBody');
    const generatePdfBtn = document.getElementById('generatePdfBtn');
    const pdfPathDisplay = document.getElementById('pdfPathDisplay');
    const modalCalePdf = document.getElementById('modalCalePdf');


    // Filtrare
    const filterClient = document.getElementById('filterClient');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedClientId = filterClient.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#oferteTableBody tr').forEach(row => {
            const rowClientId = row.getAttribute('data-id-client');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const clientMatch = (selectedClientId === 'all' || rowClientId === selectedClientId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (clientMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterClient.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addOfertaBtn.addEventListener('click', function() {
        ofertaForm.reset();
        document.getElementById('ofertaAction').value = 'add';
        document.getElementById('ofertaId').value = '';
        document.getElementById('addEditOfertaModalLabel').textContent = 'Adaugă Ofertă Nouă';
        // Setează data ofertei la data curentă implicit
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        document.getElementById('modalDataOferta').value = formattedDate;
        document.getElementById('modalMoneda').value = 'RON'; // Moneda implicită
        generatePdfBtn.style.display = 'none'; // Ascunde butonul Generează PDF la adăugare
        pdfPathDisplay.style.display = 'none'; // Ascunde calea PDF la adăugare
    });

    // Deschide modalul pentru editare
    oferteTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-oferta-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('ofertaAction').value = 'edit';
            document.getElementById('ofertaId').value = row.getAttribute('data-id');
            document.getElementById('addEditOfertaModalLabel').textContent = 'Editează Ofertă';

            document.getElementById('modalSelectClient').value = row.getAttribute('data-id-client');
            document.getElementById('modalNumarOferta').value = row.getAttribute('data-numar-oferta');
            document.getElementById('modalDataOferta').value = row.getAttribute('data-data-oferta');
            document.getElementById('modalDescriereServiciu').value = row.getAttribute('data-descriere-serviciu');
            document.getElementById('modalValoareOferta').value = row.getAttribute('data-valoare-oferta');
            document.getElementById('modalMoneda').value = row.getAttribute('data-moneda');
            document.getElementById('modalStatus').value = row.getAttribute('data-status');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
            
            const calePdf = row.getAttribute('data-cale-pdf');
            if (calePdf) {
                modalCalePdf.textContent = calePdf;
                pdfPathDisplay.style.display = 'block';
                generatePdfBtn.style.display = 'none'; // Nu generăm din nou dacă există deja
            } else {
                pdfPathDisplay.style.display = 'none';
                generatePdfBtn.style.display = 'block'; // Afișează butonul Generează PDF dacă nu există
            }
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    ofertaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(ofertaForm);

        fetch('process_oferte.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditOfertaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea ofertei:', error);
            alert('A apărut o eroare la salvarea ofertei.');
        });
    });

    // Generare PDF (Placeholder)
    generatePdfBtn.addEventListener('click', function() {
        const ofertaId = document.getElementById('ofertaId').value;
        if (!ofertaId) {
            alert('Te rog salvează oferta înainte de a genera PDF-ul.');
            return;
        }
        // Aici ar trebui să faci un apel AJAX către un script PHP care generează PDF-ul
        // Exemplu: fetch('generate_oferta_pdf.php?id=' + ofertaId)
        alert('Funcționalitatea de generare PDF va fi implementată ulterior. Oferta ID: ' + ofertaId);
        // După generare, ar trebui să actualizezi calea PDF în baza de date și să reîncarci pagina
        // location.reload(); 
    });


    // Ștergerea ofertei
    oferteTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-oferta-btn')) {
            const ofertaIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteOfertaId').value = ofertaIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteOfertaModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteOfertaBtn.addEventListener('click', function() {
        const ofertaIdToDelete = document.getElementById('deleteOfertaId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', ofertaIdToDelete);

        fetch('process_oferte.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteOfertaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea ofertei:', error);
            alert('A apărut o eroare la ștergerea ofertei.');
        });
    });
});
</script>
