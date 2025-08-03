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

// Preluăm lista de clienți existentă
$clienti_list = [];
$sql_clienti = "SELECT * FROM clienti ORDER BY nume_companie ASC";
$result_clienti = $conn->query($sql_clienti);
if ($result_clienti) {
    while ($row = $result_clienti->fetch_assoc()) {
        $clienti_list[] = $row;
    }
}
$conn->close();

// Statusuri pentru filtrare
$statusuri_clienti = ['Activ', 'Inactiv', 'Potential'];
?>

<title>NTS TOUR | Listă Clienți</title>

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

    /* Stiluri specifice pentru tabelul de clienți */
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
    .badge-status-potential { background-color: #17a2b8 !important; color: #fff !important; }

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
            <div class="breadcrumb-title pe-3">Listă Clienți</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Clienți</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditClientModal" id="addClientBtn">
                            <i class="bx bx-user-plus"></i> Adaugă Client Nou
                        </button>

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_clienti as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume companie, persoană contact, email, CUI...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Clienților -->
                        <?php if (empty($clienti_list)): ?>
                            <div class="alert alert-info">Nu există clienți înregistrați.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nume Companie</th>
                                            <th>Persoană Contact</th>
                                            <th>Email</th>
                                            <th>Telefon</th>
                                            <th>CUI</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientiTableBody">
                                        <?php foreach ($clienti_list as $client): ?>
                                            <tr 
                                                data-id="<?php echo $client['id']; ?>"
                                                data-nume-companie="<?php echo htmlspecialchars($client['nume_companie']); ?>"
                                                data-persoana-contact="<?php echo htmlspecialchars($client['persoana_contact']); ?>"
                                                data-email="<?php echo htmlspecialchars($client['email']); ?>"
                                                data-telefon="<?php echo htmlspecialchars($client['telefon']); ?>"
                                                data-adresa="<?php echo htmlspecialchars($client['adresa']); ?>"
                                                data-cui="<?php echo htmlspecialchars($client['cui']); ?>"
                                                data-nr-reg-com="<?php echo htmlspecialchars($client['nr_reg_com']); ?>"
                                                data-termen-plata="<?php echo htmlspecialchars($client['termen_plata']); ?>"
                                                data-status="<?php echo htmlspecialchars($client['status']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($client['observatii']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($client['nume_companie'] . ' ' . $client['persoana_contact'] . ' ' . $client['email'] . ' ' . $client['telefon'] . ' ' . $client['cui'])); ?>"
                                            >
                                                <td data-label="Nume Companie:"><?php echo htmlspecialchars($client['nume_companie']); ?></td>
                                                <td data-label="Persoană Contact:"><?php echo htmlspecialchars($client['persoana_contact']); ?></td>
                                                <td data-label="Email:"><?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></td>
                                                <td data-label="Telefon:"><?php echo htmlspecialchars($client['telefon'] ?? 'N/A'); ?></td>
                                                <td data-label="CUI:"><?php echo htmlspecialchars($client['cui'] ?? 'N/A'); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower($client['status']); ?>"><?php echo htmlspecialchars($client['status']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-client-btn" data-bs-toggle="modal" data-bs-target="#addEditClientModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-client-btn" data-id="<?php echo $client['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Client -->
<div class="modal fade" id="addEditClientModal" tabindex="-1" aria-labelledby="addEditClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditClientModalLabel">Adaugă Client Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="clientForm" action="process_clienti.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="clientAction" name="action" value="add">
                    <input type="hidden" id="clientId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalNumeCompanie" class="form-label">Nume Companie:</label>
                            <input type="text" class="form-control" id="modalNumeCompanie" name="nume_companie" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalPersoanaContact" class="form-label">Persoană Contact:</label>
                            <input type="text" class="form-control" id="modalPersoanaContact" name="persoana_contact" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalEmail" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="modalEmail" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="modalTelefon" class="form-label">Telefon:</label>
                            <input type="tel" class="form-control" id="modalTelefon" name="telefon">
                        </div>
                        <div class="col-12">
                            <label for="modalAdresa" class="form-label">Adresă:</label>
                            <textarea class="form-control" id="modalAdresa" name="adresa" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCUI" class="form-label">CUI:</label>
                            <input type="text" class="form-control" id="modalCUI" name="cui">
                        </div>
                        <div class="col-md-6">
                            <label for="modalNrRegCom" class="form-label">Nr. Reg. Com.:</label>
                            <input type="text" class="form-control" id="modalNrRegCom" name="nr_reg_com">
                        </div>
                        <div class="col-md-6">
                            <label for="modalTermenPlata" class="form-label">Termen de Plată (zile):</label>
                            <input type="number" class="form-control" id="modalTermenPlata" name="termen_plata" value="30">
                        </div>
                        <div class="col-md-6">
                            <label for="modalStatus" class="form-label">Status:</label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_clienti as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Client -->
<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteClientModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi acest client? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteClientId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteClientBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditClientModal = document.getElementById('addEditClientModal');
    const clientForm = document.getElementById('clientForm');
    const addClientBtn = document.getElementById('addClientBtn');
    const deleteClientModal = document.getElementById('deleteClientModal');
    const confirmDeleteClientBtn = document.getElementById('confirmDeleteClientBtn');
    const clientiTableBody = document.getElementById('clientiTableBody');

    // Filtrare
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#clientiTableBody tr').forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addClientBtn.addEventListener('click', function() {
        clientForm.reset();
        document.getElementById('clientAction').value = 'add';
        document.getElementById('clientId').value = '';
        document.getElementById('addEditClientModalLabel').textContent = 'Adaugă Client Nou';
        document.getElementById('modalTermenPlata').value = '30'; // Valoare implicită
    });

    // Deschide modalul pentru editare
    clientiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-client-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('clientAction').value = 'edit';
            document.getElementById('clientId').value = row.getAttribute('data-id');
            document.getElementById('addEditClientModalLabel').textContent = 'Editează Client';

            document.getElementById('modalNumeCompanie').value = row.getAttribute('data-nume-companie');
            document.getElementById('modalPersoanaContact').value = row.getAttribute('data-persoana-contact');
            document.getElementById('modalEmail').value = row.getAttribute('data-email');
            document.getElementById('modalTelefon').value = row.getAttribute('data-telefon');
            document.getElementById('modalAdresa').value = row.getAttribute('data-adresa');
            document.getElementById('modalCUI').value = row.getAttribute('data-cui');
            document.getElementById('modalNrRegCom').value = row.getAttribute('data-nr-reg-com');
            document.getElementById('modalTermenPlata').value = row.getAttribute('data-termen-plata');
            document.getElementById('modalStatus').value = row.getAttribute('data-status');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    clientForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(clientForm);

        fetch('process_clienti.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditClientModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea clientului:', error);
            alert('A apărut o eroare la salvarea clientului.');
        });
    });

    // Ștergerea clientului
    clientiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-client-btn')) {
            const clientIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteClientId').value = clientIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteClientModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteClientBtn.addEventListener('click', function() {
        const clientIdToDelete = document.getElementById('deleteClientId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', clientIdToDelete);

        fetch('process_clienti.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteClientModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea clientului:', error);
            alert('A apărut o eroare la ștergerea clientului.');
        });
    });
});
</script>
