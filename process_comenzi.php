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

// Preluăm istoricul comenzilor
$comenzi_list = [];
$sql_comenzi = "
    SELECT cc.*, c.nume_companie, c.persoana_contact
    FROM comenzi_clienti cc
    JOIN clienti c ON cc.id_client = c.id
    ORDER BY cc.data_comanda DESC
";
$result_comenzi = $conn->query($sql_comenzi);
if ($result_comenzi) {
    while ($row = $result_comenzi->fetch_assoc()) {
        $comenzi_list[] = $row;
    }
}
$conn->close();

// Statusuri pentru filtrare
$statusuri_comanda = ['Noua', 'In_proces', 'Finalizata', 'Anulata'];
?>

<title>NTS TOUR | Istoric Comenzi Clienți</title>

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

    /* Stiluri specifice pentru tabelul de comenzi */
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
    .badge-status-noua { background-color: #0d6efd !important; color: #fff !important; }
    .badge-status-in_proces { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-status-finalizata { background-color: #28a745 !important; color: #fff !important; }
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
                        <li class="breadcrumb-item active" aria-current="page">Istoric Comenzi Clienți</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Istoric Comenzi Clienți</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditComandaModal" id="addComandaBtn">
                            <i class="bx bx-plus"></i> Adaugă Comandă Nouă
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
                                    <?php foreach ($statusuri_comanda as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta descriere, client...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Comenzilor -->
                        <?php if (empty($comenzi_list)): ?>
                            <div class="alert alert-info">Nu există comenzi înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Dată Comandă</th>
                                            <th>Descriere Serviciu</th>
                                            <th>Valoare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="comenziTableBody">
                                        <?php foreach ($comenzi_list as $comanda): ?>
                                            <tr 
                                                data-id="<?php echo $comanda['id']; ?>"
                                                data-id-client="<?php echo $comanda['id_client']; ?>"
                                                data-data-comanda="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($comanda['data_comanda']))); ?>"
                                                data-descriere-serviciu="<?php echo htmlspecialchars($comanda['descriere_serviciu']); ?>"
                                                data-valoare="<?php echo htmlspecialchars($comanda['valoare']); ?>"
                                                data-moneda="<?php echo htmlspecialchars($comanda['moneda']); ?>"
                                                data-status="<?php echo htmlspecialchars($comanda['status']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($comanda['observatii']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($comanda['nume_companie'] . ' ' . $comanda['persoana_contact'] . ' ' . $comanda['descriere_serviciu'])); ?>"
                                            >
                                                <td data-label="Client:"><?php echo htmlspecialchars($comanda['nume_companie'] . ' (' . $comanda['persoana_contact'] . ')'); ?></td>
                                                <td data-label="Dată Comandă:"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($comanda['data_comanda']))); ?></td>
                                                <td data-label="Descriere Serviciu:"><?php echo htmlspecialchars(mb_strimwidth($comanda['descriere_serviciu'], 0, 50, "...")); ?></td>
                                                <td data-label="Valoare:"><?php echo htmlspecialchars($comanda['valoare'] . ' ' . $comanda['moneda']); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace('_', '-', $comanda['status'])); ?>"><?php echo htmlspecialchars($comanda['status']); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-comanda-btn" data-bs-toggle="modal" data-bs-target="#addEditComandaModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-comanda-btn" data-id="<?php echo $comanda['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Comandă -->
<div class="modal fade" id="addEditComandaModal" tabindex="-1" aria-labelledby="addEditComandaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditComandaModalLabel">Adaugă Comandă Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="comandaForm" action="process_comenzi.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="comandaAction" name="action" value="add">
                    <input type="hidden" id="comandaId" name="id">
                    
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
                            <label for="modalDataComanda" class="form-label">Dată Comandă:</label>
                            <input type="datetime-local" class="form-control" id="modalDataComanda" name="data_comanda" required>
                        </div>
                        <div class="col-12">
                            <label for="modalDescriereServiciu" class="form-label">Descriere Serviciu:</label>
                            <textarea class="form-control" id="modalDescriereServiciu" name="descriere_serviciu" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="modalValoare" class="form-label">Valoare:</label>
                            <input type="number" step="0.01" class="form-control" id="modalValoare" name="valoare" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalMoneda" class="form-label">Monedă:</label>
                            <input type="text" class="form-control" id="modalMoneda" name="moneda" value="RON" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalStatus" class="form-label">Status:</label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_comanda as $status): ?>
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
                    <button type="submit" class="btn btn-primary">Salvează Comanda</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Comandă -->
<div class="modal fade" id="deleteComandaModal" tabindex="-1" aria-labelledby="deleteComandaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteComandaModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi această comandă? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteComandaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteComandaBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditComandaModal = document.getElementById('addEditComandaModal');
    const comandaForm = document.getElementById('comandaForm');
    const addComandaBtn = document.getElementById('addComandaBtn');
    const deleteComandaModal = document.getElementById('deleteComandaModal');
    const confirmDeleteComandaBtn = document.getElementById('confirmDeleteComandaBtn');
    const comenziTableBody = document.getElementById('comenziTableBody');

    // Filtrare
    const filterClient = document.getElementById('filterClient');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedClientId = filterClient.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#comenziTableBody tr').forEach(row => {
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
    addComandaBtn.addEventListener('click', function() {
        comandaForm.reset();
        document.getElementById('comandaAction').value = 'add';
        document.getElementById('comandaId').value = '';
        document.getElementById('addEditComandaModalLabel').textContent = 'Adaugă Comandă Nouă';
        // Setează data comenzii la data curentă implicit
        const now = new Date();
        const formattedNow = now.toISOString().substring(0, 16);
        document.getElementById('modalDataComanda').value = formattedNow;
        document.getElementById('modalMoneda').value = 'RON'; // Setează moneda implicită
    });

    // Deschide modalul pentru editare
    comenziTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-comanda-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('comandaAction').value = 'edit';
            document.getElementById('comandaId').value = row.getAttribute('data-id');
            document.getElementById('addEditComandaModalLabel').textContent = 'Editează Comanda';

            document.getElementById('modalSelectClient').value = row.getAttribute('data-id-client');
            document.getElementById('modalDataComanda').value = row.getAttribute('data-data-comanda');
            document.getElementById('modalDescriereServiciu').value = row.getAttribute('data-descriere-serviciu');
            document.getElementById('modalValoare').value = row.getAttribute('data-valoare');
            document.getElementById('modalMoneda').value = row.getAttribute('data-moneda');
            document.getElementById('modalStatus').value = row.getAttribute('data-status');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    comandaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(comandaForm);

        fetch('process_comenzi.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditComandaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea comenzii:', error);
            alert('A apărut o eroare la salvarea comenzii.');
        });
    });

    // Ștergerea comenzii
    comenziTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-comanda-btn')) {
            const comandaIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteComandaId').value = comandaIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteComandaModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteComandaBtn.addEventListener('click', function() {
        const comandaIdToDelete = document.getElementById('deleteComandaId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', comandaIdToDelete);

        fetch('process_comenzi.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteComandaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea comenzii:', error);
            alert('A apărut o eroare la ștergerea comenzii.');
        });
    });
});
</script>
