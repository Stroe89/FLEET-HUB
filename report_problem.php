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

// Preluăm lista de vehicule pentru dropdown-uri de filtrare
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare FROM vehicule ORDER BY model ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

// Preluăm problemele raportate existente
$probleme_raportate = [];
$sql_probleme = "
    SELECT pr.*, v.model, v.numar_inmatriculare
    FROM probleme_raportate pr
    JOIN vehicule v ON pr.id_vehicul = v.id
    ORDER BY pr.data_raportare DESC
";
$result_probleme = $conn->query($sql_probleme);
if ($result_probleme) {
    while ($row = $result_probleme->fetch_assoc()) {
        $probleme_raportate[] = $row;
    }
}
$conn->close();

// Tipurile de probleme disponibile (trebuie să corespundă cu raporteaza-problema.php)
$tipuri_probleme = [
    'Mecanica - Motor', 'Mecanica - Transmisie', 'Mecanica - Franare', 'Mecanica - Suspensie',
    'Electrica - Baterie', 'Electrica - Lumini', 'Electrica - Sistem Pornire', 'Electrica - Climatizare',
    'Estetica - Caroserie', 'Estetica - Interior', 'Documente - Expirate', 'Documente - Lipsa',
    'Anvelope - Uzura', 'Anvelope - Presiune', 'Administrativ - Amenzi', 'Administrativ - Licente',
    'Siguranta - Centuri', 'Siguranta - Airbaguri', 'S.S.M.D. - Echipament', 'Altele'
];
?>

<title>NTS TOUR | Probleme Raportate</title>

<style>
    /* Stiluri generale pentru a asigura ca tot textul este alb */
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
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success {
        border-radius: 0.5rem !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: bold !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover {
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

    /* Stiluri specifice pentru tabelul de probleme */
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
    .badge-rezolvata-true { background-color: #28a745 !important; color: #fff !important; }
    .badge-rezolvata-false { background-color: #dc3545 !important; color: #fff !important; }
    .badge-gravitate-1 { background-color: #28a745 !important; }
    .badge-gravitate-2 { background-color: #17a2b8 !important; }
    .badge-gravitate-3 { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-gravitate-4 { background-color: #fd7e14 !important; }
    .badge-gravitate-5 { background-color: #dc3545 !important; }

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
                        <li class="breadcrumb-item active" aria-current="page">Probleme Raportate</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Lista Problemelor Raportate</h4>
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

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterVehicle" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicle">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <option value="Rezolvata">Rezolvate</option>
                                    <option value="Nerezolvata">Nerezolvate</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta descriere, tip problemă...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Problemelor Raportate -->
                        <?php if (empty($probleme_raportate)): ?>
                            <div class="alert alert-info">Nu există probleme raportate înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Problemă</th>
                                            <th>Descriere</th>
                                            <th>Gravitate</th>
                                            <th>Raportor</th>
                                            <th>Dată Raportare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="problemeTableBody">
                                        <?php foreach ($probleme_raportate as $problema):
                                            $status_text = $problema['rezolvata'] ? 'Rezolvata' : 'Nerezolvata';
                                            $status_class = $problema['rezolvata'] ? 'badge-rezolvata-true' : 'badge-rezolvata-false';
                                            $gravitate_class = 'badge-gravitate-' . $problema['gravitate'];
                                        ?>
                                            <tr 
                                                data-id="<?php echo $problema['id']; ?>"
                                                data-id-vehicul="<?php echo $problema['id_vehicul']; ?>"
                                                data-tip-problema="<?php echo htmlspecialchars($problema['tip_problema']); ?>"
                                                data-descriere="<?php echo htmlspecialchars($problema['descriere_problema']); ?>"
                                                data-gravitate="<?php echo htmlspecialchars($problema['gravitate']); ?>"
                                                data-raportor="<?php echo htmlspecialchars($problema['nume_raportor']); ?>"
                                                data-status="<?php echo htmlspecialchars($status_text); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($problema['model'] . ' ' . $problema['numar_inmatriculare'] . ' ' . $problema['tip_problema'] . ' ' . $problema['descriere_problema'] . ' ' . $problema['nume_raportor'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($problema['model'] . ' (' . $problema['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Tip Problemă:"><?php echo htmlspecialchars($problema['tip_problema']); ?></td>
                                                <td data-label="Descriere:"><?php echo htmlspecialchars(mb_strimwidth($problema['descriere_problema'], 0, 50, "...")); ?></td>
                                                <td data-label="Gravitate:"><span class="badge <?php echo $gravitate_class; ?>"><?php echo htmlspecialchars($problema['gravitate']); ?></span></td>
                                                <td data-label="Raportor:"><?php echo htmlspecialchars($problema['nume_raportor']); ?></td>
                                                <td data-label="Dată Raportare:"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($problema['data_raportare']))); ?></td>
                                                <td data-label="Status:"><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-problem-btn" data-bs-toggle="modal" data-bs-target="#viewProblemModal">Detalii</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-problem-btn" data-bs-toggle="modal" data-bs-target="#editProblemModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-problem-btn" data-id="<?php echo $problema['id']; ?>">Șterge</button>
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

<!-- Modal Vizualizare Detalii Problemă -->
<div class="modal fade" id="viewProblemModal" tabindex="-1" aria-labelledby="viewProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProblemModalLabel">Detalii Problemă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Vehicul:</strong> <span id="viewVehicleInfo"></span></p>
                <p><strong>Tip Problemă:</strong> <span id="viewProblemType"></span></p>
                <p><strong>Descriere:</strong> <span id="viewProblemDescription"></span></p>
                <p><strong>Gravitate:</strong> <span id="viewProblemRating"></span></p>
                <p><strong>Raportor:</strong> <span id="viewReporterName"></span></p>
                <p><strong>Dată Raportare:</strong> <span id="viewReportDate"></span></p>
                <p><strong>Status:</strong> <span id="viewStatus"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editează Problemă -->
<div class="modal fade" id="editProblemModal" tabindex="-1" aria-labelledby="editProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProblemModalLabel">Editează Problemă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProblemForm" action="report_problem.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="editProblemId" name="id">
                    
                    <div class="mb-3">
                        <label for="editProblemDescription" class="form-label">Descriere:</label>
                        <textarea class="form-control" id="editProblemDescription" name="problem_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editProblemRating" class="form-label">Gravitate (1-5):</label>
                        <input type="number" class="form-control" id="editProblemRating" name="problem_rating" min="1" max="5" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="editProblemResolved" name="rezolvata">
                        <label class="form-check-label" for="editProblemResolved">
                            Marchează ca Rezolvată
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Problemă -->
<div class="modal fade" id="deleteProblemModal" tabindex="-1" aria-labelledby="deleteProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProblemModalLabel">Confirmă Ștergerea Problemei</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi această problemă? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteProblemId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProblemBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const problemeTableBody = document.getElementById('problemeTableBody');
    const viewProblemModal = document.getElementById('viewProblemModal');
    const editProblemModal = document.getElementById('editProblemModal');
    const editProblemForm = document.getElementById('editProblemForm');
    const deleteProblemModal = document.getElementById('deleteProblemModal');
    const confirmDeleteProblemBtn = document.getElementById('confirmDeleteProblemBtn');

    // Filtrare
    const filterVehicle = document.getElementById('filterVehicle');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedVehicleId = filterVehicle.value;
        const selectedStatus = filterStatus.value; // "Rezolvata" sau "Nerezolvata"
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#problemeTableBody tr').forEach(row => {
            const rowVehicleId = row.getAttribute('data-id-vehicul');
            const rowStatus = row.getAttribute('data-status'); // "Rezolvata" sau "Nerezolvata"
            const rowSearchText = row.getAttribute('data-search-text');

            const vehicleMatch = (selectedVehicleId === 'all' || rowVehicleId === selectedVehicleId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehicleMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicle.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Vizualizare Detalii Problemă
    problemeTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-problem-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('viewVehicleInfo').textContent = row.querySelector('td[data-label="Vehicul:"]').textContent;
            document.getElementById('viewProblemType').textContent = row.querySelector('td[data-label="Tip Problemă:"]').textContent;
            document.getElementById('viewProblemDescription').textContent = row.getAttribute('data-descriere'); // Preluăm descrierea completă
            document.getElementById('viewProblemRating').textContent = row.querySelector('td[data-label="Gravitate:"] .badge').textContent;
            document.getElementById('viewReporterName').textContent = row.querySelector('td[data-label="Raportor:"]').textContent;
            document.getElementById('viewReportDate').textContent = row.querySelector('td[data-label="Dată Raportare:"]').textContent;
            document.getElementById('viewStatus').textContent = row.querySelector('td[data-label="Status:"] .badge').textContent;
        }
    });

    // Editează Problemă
    problemeTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-problem-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('editProblemId').value = row.getAttribute('data-id');
            document.getElementById('editProblemDescription').value = row.getAttribute('data-descriere');
            document.getElementById('editProblemRating').value = row.getAttribute('data-gravitate');
            document.getElementById('editProblemResolved').checked = (row.getAttribute('data-status') === 'Rezolvata');
        }
    });

    // Trimiterea formularului de editare
    editProblemForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(editProblemForm);
        
        // Asigură că 'rezolvata' este trimis chiar dacă nu e bifat
        if (!formData.has('rezolvata')) {
            formData.append('rezolvata', '0');
        }

        fetch('report_problem.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(editProblemModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la salvarea modificărilor:', error);
            alert('A apărut o eroare la salvarea modificărilor.');
        });
    });

    // Șterge Problemă
    problemeTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-problem-btn')) {
            const problemIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteProblemId').value = problemIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteProblemModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteProblemBtn.addEventListener('click', function() {
        const problemIdToDelete = document.getElementById('deleteProblemId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', problemIdToDelete);

        fetch('report_problem.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteProblemModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea problemei:', error);
            alert('A apărut o eroare la ștergerea problemei.');
        });
    });
});
</script>
