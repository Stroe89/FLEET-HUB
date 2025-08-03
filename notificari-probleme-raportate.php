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

// Funcție ajutătoare pentru a verifica existența unui tabel
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
    return $result && $result->num_rows > 0;
}

// --- Preluare Probleme Raportate ---
$reported_problems = [];
$problem_statuses = ['Nouă', 'În curs de rezolvare', 'Rezolvată', 'Anulată'];
$problem_types = ['Mecanică', 'Electrică', 'Estetică', 'Documente', 'Anvelope', 'Altele'];

if (tableExists($conn, 'probleme_raportate')) {
    $sql_problems = "
        SELECT 
            pr.id, 
            pr.id_vehicul, 
            pr.nume_raportor, 
            pr.tip_problema, 
            pr.descriere_problema, 
            pr.gravitate, 
            pr.data_raportare, 
            pr.status,
            v.numar_inmatriculare,
            v.model
        FROM 
            probleme_raportate pr
        LEFT JOIN 
            vehicule v ON pr.id_vehicul = v.id
        ORDER BY 
            pr.data_raportare DESC
    ";
    $result_problems = $conn->query($sql_problems);
    if ($result_problems) {
        while ($row = $result_problems->fetch_assoc()) {
            $reported_problems[] = $row;
        }
    } else {
        $error_message .= "Eroare la preluarea problemelor raportate: " . $conn->error;
    }
} else {
    $error_message .= "Tabelul 'probleme_raportate' nu există. Vă rugăm să-l creați.";
    // Date mock dacă tabelul nu există
    $reported_problems = [
        ['id' => 1, 'id_vehicul' => 101, 'nume_raportor' => 'Ion Popescu', 'tip_problema' => 'Mecanică', 'descriere_problema' => 'Motorul scoate un zgomot ciudat la peste 80km/h.', 'gravitate' => 4, 'data_raportare' => '2025-07-01 10:30:00', 'status' => 'Nouă', 'numar_inmatriculare' => 'B 10 ABC', 'model' => 'Mercedes Actros'],
        ['id' => 2, 'id_vehicul' => 102, 'nume_raportor' => 'Maria Ionescu', 'tip_problema' => 'Electrica', 'descriere_problema' => 'Lumina de frână dreapta spate nu funcționează.', 'gravitate' => 2, 'data_raportare' => '2025-06-28 14:00:00', 'status' => 'În curs de rezolvare', 'numar_inmatriculare' => 'B 20 DEF', 'model' => 'Ford Transit'],
        ['id' => 3, 'id_vehicul' => 101, 'nume_raportor' => 'Ion Popescu', 'tip_problema' => 'Anvelope', 'descriere_problema' => 'Presiune scăzută la anvelopa stânga față.', 'gravitate' => 3, 'data_raportare' => '2025-06-25 09:15:00', 'status' => 'Rezolvată', 'numar_inmatriculare' => 'B 10 ABC', 'model' => 'Mercedes Actros'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Probleme Raportate</title>

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
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .card-title {
        color: #ffffff !important;
    }
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
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
    .alert {
        color: #ffffff !important;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
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

    /* Stiluri specifice pentru tabele */
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
    /* Stiluri pentru butoanele de acțiune din tabel */
    .table .btn-sm {
        padding: 0.3rem 0.6rem !important;
        font-size: 0.8rem !important;
        width: auto !important;
    }
    .table .badge {
        padding: 0.4em 0.7em;
        border-radius: 0.3rem;
        font-size: 0.85em;
        font-weight: 600;
    }
    /* Culori specifice pentru statusurile problemelor */
    .badge-status-Nouă { background-color: #007bff !important; color: #fff !important; } /* Blue */
    .badge-status-În_curs_de_rezolvare { background-color: #ffc107 !important; color: #343a40 !important; } /* Yellow */
    .badge-status-Rezolvată { background-color: #28a745 !important; color: #fff !important; } /* Green */
    .badge-status-Anulată { background-color: #6c757d !important; color: #fff !important; } /* Grey */

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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listă Probleme Raportate</h4>
                        <p class="text-muted">Vizualizează, filtrează și gestionează problemele raportate pentru vehicule.</p>
                        <hr>

                        <!-- Secțiunea de Filtrare și Căutare -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($problem_statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterType" class="form-label">Filtrează după Tip:</label>
                                <select class="form-select" id="filterType">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($problem_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="searchProblem" class="form-label">Căutare:</label>
                                <input type="text" class="form-control" id="searchProblem" placeholder="Căutare după vehicul, raportor, descriere...">
                            </div>
                        </div>

                        <!-- Butoane de Acțiuni -->
                        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                            <a href="raporteaza-problema.php" class="btn btn-primary"><i class="bx bx-plus me-2"></i>Raportează Problemă Nouă</a>
                            <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel me-2"></i>Export Excel</button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="bx bxs-file-pdf me-2"></i>Export PDF</button>
                            <button type="button" class="btn btn-info" id="printListBtn"><i class="bx bx-printer me-2"></i>Printează</button>
                        </div>

                        <!-- Tabelul cu Probleme Raportate -->
                        <?php if (empty($reported_problems)): ?>
                            <div class="alert alert-info">Nu există probleme raportate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="problemsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Vehicul</th>
                                            <th>Raportor</th>
                                            <th>Tip Problemă</th>
                                            <th>Descriere</th>
                                            <th>Gravitate</th>
                                            <th>Dată Raportare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="problemsTableBody">
                                        <?php foreach ($reported_problems as $problem): ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($problem['id']); ?>"
                                                data-id-vehicul="<?php echo htmlspecialchars($problem['id_vehicul']); ?>"
                                                data-raportor="<?php echo htmlspecialchars($problem['nume_raportor']); ?>"
                                                data-tip="<?php echo htmlspecialchars($problem['tip_problema']); ?>"
                                                data-descriere="<?php echo htmlspecialchars($problem['descriere_problema']); ?>"
                                                data-gravitate="<?php echo htmlspecialchars($problem['gravitate']); ?>"
                                                data-status="<?php echo htmlspecialchars($problem['status']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($problem['numar_inmatriculare'] . ' ' . $problem['model'] . ' ' . $problem['nume_raportor'] . ' ' . $problem['tip_problema'] . ' ' . $problem['descriere_problema'] . ' ' . $problem['status'])); ?>"
                                            >
                                                <td data-label="ID:"><?php echo htmlspecialchars($problem['id']); ?></td>
                                                <td data-label="Vehicul:">
                                                    <?php echo htmlspecialchars($problem['model'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($problem['numar_inmatriculare'] ?? 'N/A'); ?>)
                                                </td>
                                                <td data-label="Raportor:"><?php echo htmlspecialchars($problem['nume_raportor']); ?></td>
                                                <td data-label="Tip Problemă:"><?php echo htmlspecialchars($problem['tip_problema']); ?></td>
                                                <td data-label="Descriere:"><?php echo htmlspecialchars(mb_strimwidth($problem['descriere_problema'], 0, 50, "...", "UTF-8")); ?></td>
                                                <td data-label="Gravitate:"><?php echo htmlspecialchars($problem['gravitate']); ?>/5</td>
                                                <td data-label="Dată Raportare:"><?php echo (new DateTime($problem['data_raportare']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Status:">
                                                    <?php 
                                                        $status_class = 'bg-info';
                                                        if ($problem['status'] == 'Nouă') $status_class = 'bg-primary';
                                                        else if ($problem['status'] == 'În curs de rezolvare') $status_class = 'bg-warning text-dark';
                                                        else if ($problem['status'] == 'Rezolvată') $status_class = 'bg-success';
                                                        else if ($problem['status'] == 'Anulată') $status_class = 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($problem['status']); ?></span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info me-2 view-problem-btn" data-id="<?php echo $problem['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewProblemModal"><i class="bx bx-show"></i> Vezi</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-2 edit-status-btn" data-id="<?php echo $problem['id']; ?>" data-bs-toggle="modal" data-bs-target="#editProblemStatusModal"><i class="bx bx-cog"></i> Status</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-problem-btn" data-id="<?php echo $problem['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
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

<!-- Modale -->

<!-- Modal Vizualizare Problemă -->
<div class="modal fade" id="viewProblemModal" tabindex="-1" aria-labelledby="viewProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProblemModalLabel">Detalii Problemă: <span id="viewProblemTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Vehicul:</strong> <span id="viewProblemVehicle"></span></p>
                <p><strong>Raportor:</strong> <span id="viewProblemReporter"></span></p>
                <p><strong>Tip Problemă:</strong> <span id="viewProblemType"></span></p>
                <p><strong>Gravitate:</strong> <span id="viewProblemRating"></span>/5</p>
                <p><strong>Dată Raportare:</strong> <span id="viewProblemDate"></span></p>
                <p><strong>Status:</strong> <span id="viewProblemStatus" class="badge"></span></p>
                <hr>
                <h6>Descriere Detaliată:</h6>
                <p id="viewProblemDescription"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editează Status Problemă -->
<div class="modal fade" id="editProblemStatusModal" tabindex="-1" aria-labelledby="editProblemStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProblemStatusModalLabel">Actualizează Status Problemă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProblemStatusForm" action="report_problem.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="editProblemId">
                    <p>Vehicul: <strong id="editProblemVehicleInfo"></strong></p>
                    <div class="mb-3">
                        <label for="newProblemStatus" class="form-label">Noul Status:</label>
                        <select class="form-select" id="newProblemStatus" name="status" required>
                            <?php foreach ($problem_statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmă Ștergere Problemă -->
<div class="modal fade" id="deleteProblemModal" tabindex="-1" aria-labelledby="deleteProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProblemModalLabel">Confirmă Ștergerea Problemei</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi problema raportată pentru vehiculul <strong id="deleteProblemVehicleDisplay"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteProblemIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProblemBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>


<?php require_once 'template/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Data PHP pentru JavaScript ---
    const allProblemsData = <?php echo json_encode($reported_problems); ?>;
    const problemsMap = {};
    allProblemsData.forEach(problem => {
        problemsMap[problem.id] = problem;
    });

    // --- Elemente DOM pentru Filtrare ---
    const filterStatus = document.getElementById('filterStatus');
    const filterType = document.getElementById('filterType');
    const searchProblem = document.getElementById('searchProblem');
    const problemsTableBody = document.getElementById('problemsTableBody');

    function filterProblemsTable() {
        const selectedStatus = filterStatus.value;
        const selectedType = filterType.value;
        const searchText = searchProblem.value.toLowerCase().trim();

        document.querySelectorAll('#problemsTableBody tr').forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            const rowType = row.getAttribute('data-tip');
            const rowSearchText = row.getAttribute('data-search-text');

            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const typeMatch = (selectedType === 'all' || rowType === selectedType);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (statusMatch && typeMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterStatus.addEventListener('change', filterProblemsTable);
    filterType.addEventListener('change', filterProblemsTable);
    searchProblem.addEventListener('input', filterProblemsTable);
    filterProblemsTable(); // Rulează la încărcarea paginii

    // --- Logică Modale (Vizualizare / Editare Status / Ștergere) ---

    // Modal Vizualizare Problemă
    const viewProblemModal = document.getElementById('viewProblemModal');
    document.querySelectorAll('.view-problem-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const problem = problemsMap[id];
            if (problem) {
                document.getElementById('viewProblemTitle').textContent = problem.titlu_problema || `Problema #${problem.id}`;
                document.getElementById('viewProblemVehicle').textContent = `${problem.model || 'N/A'} (${problem.numar_inmatriculare || 'N/A'})`;
                document.getElementById('viewProblemReporter').textContent = problem.nume_raportor || 'N/A';
                document.getElementById('viewProblemType').textContent = problem.tip_problema || 'N/A';
                document.getElementById('viewProblemRating').textContent = problem.gravitate || 'N/A';
                document.getElementById('viewProblemDate').textContent = new Date(problem.data_raportare).toLocaleString('ro-RO');
                
                const statusBadge = document.getElementById('viewProblemStatus');
                statusBadge.textContent = problem.status;
                statusBadge.className = `badge badge-status-${problem.status.replace(/ /g, '_')}`;

                document.getElementById('viewProblemDescription').textContent = problem.descriere_problema || 'N/A';
                new bootstrap.Modal(viewProblemModal).show();
            }
        });
    });

    // Modal Editează Status Problemă
    const editProblemStatusModal = document.getElementById('editProblemStatusModal');
    const editProblemStatusForm = document.getElementById('editProblemStatusForm');
    const editProblemIdInput = document.getElementById('editProblemId');
    const editProblemVehicleInfoSpan = document.getElementById('editProblemVehicleInfo');
    const newProblemStatusSelect = document.getElementById('newProblemStatus');

    document.querySelectorAll('.edit-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const problem = problemsMap[id];
            if (problem) {
                editProblemIdInput.value = problem.id;
                editProblemVehicleInfoSpan.textContent = `${problem.model || 'N/A'} (${problem.numar_inmatriculare || 'N/A'})`;
                newProblemStatusSelect.value = problem.status; // Pre-selectează statusul curent
                new bootstrap.Modal(editProblemStatusModal).show();
            }
        });
    });

    editProblemStatusForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(editProblemStatusForm);

        fetch('report_problem.php', { // Același script de procesare
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(editProblemStatusModal);
            if (modalInstance) { modalInstance.hide(); }
            if (data.includes("success")) {
                alert('Statusul problemei a fost actualizat cu succes!');
                location.reload(); 
            } else {
                alert('Eroare la actualizarea statusului: ' + data);
            }
        })
        .catch(error => {
            console.error('Eroare la actualizarea statusului:', error);
            alert('A apărut o eroare la actualizarea statusului.');
        });
    });


    // Modal Confirmă Ștergere Problemă
    const deleteProblemModal = document.getElementById('deleteProblemModal');
    const deleteProblemIdConfirm = document.getElementById('deleteProblemIdConfirm');
    const deleteProblemVehicleDisplay = document.getElementById('deleteProblemVehicleDisplay');
    const confirmDeleteProblemBtn = document.getElementById('confirmDeleteProblemBtn');

    document.querySelectorAll('.delete-problem-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentProblemIdToDelete = this.dataset.id;
            const problem = problemsMap[currentProblemIdToDelete];
            if (problem) {
                deleteProblemVehicleDisplay.textContent = `${problem.model || 'N/A'} (${problem.numar_inmatriculare || 'N/A'})`;
                deleteProblemIdConfirm.value = currentProblemIdToDelete;
                new bootstrap.Modal(deleteProblemModal).show();
            }
        });
    });

    confirmDeleteProblemBtn.addEventListener('click', function() {
        const problemIdToDelete = document.getElementById('deleteProblemIdConfirm').value;
        if (problemIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_problem');
            formData.append('id', problemIdToDelete);

            fetch('report_problem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data);
                const modalInstance = bootstrap.Modal.getInstance(deleteProblemModal);
                if (modalInstance) { modalInstance.hide(); }
                if (data.includes("success")) {
                    alert('Problema a fost ștearsă cu succes!');
                    location.reload(); 
                } else {
                    alert('Eroare la ștergerea problemei: ' + data);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea problemei:', error);
                alert('A apărut o eroare la ștergerea problemei.');
            });
        }
    });


    // --- Funcționalitate Export (PDF, Excel, Print) ---
    function exportTableToPDF(tableId, title) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const headers = [];
        document.querySelectorAll(`#${tableId} thead th`).forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
            if (row.style.display !== 'none') { // Doar rândurile vizibile
                const rowData = [];
                // Excludem coloana de Acțiuni (ultima coloană)
                row.querySelectorAll('td:not(:last-child)').forEach(td => {
                    const badgeSpan = td.querySelector('.badge');
                    if (badgeSpan) {
                        rowData.push(badgeSpan.textContent);
                    } else {
                        rowData.push(td.textContent);
                    }
                });
                data.push(rowData);
            }
        });

        doc.text(title, 40, 40); 
        doc.autoTable({
            startY: 60,
            head: [headers],
            body: data,
            theme: 'striped',
            styles: {
                font: 'Noto Sans',
                fontSize: 8,
                cellPadding: 5,
                valign: 'middle',
                overflow: 'linebreak'
            },
            headStyles: {
                fillColor: [59, 67, 90],
                textColor: [255, 255, 255],
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [42, 48, 66]
            },
            bodyStyles: {
                textColor: [224, 224, 224]
            },
            didParseCell: function(data) {
                if (data.section === 'head') {
                    data.cell.styles.textColor = [255, 255, 255];
                }
            }
        });

        doc.save(`${title.replace(/ /g, '_')}.pdf`);
    }

    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        exportTableToPDF('problemsTable', 'Lista Probleme Raportate');
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('problemsTable');
        const clonedTable = table.cloneNode(true);
        const tbody = clonedTable.querySelector('tbody');
        Array.from(tbody.children).forEach(row => {
            if (row.style.display === 'none') {
                tbody.removeChild(row);
            }
        });
        // Elimină coloana "Acțiuni" din clona tabelului înainte de export
        clonedTable.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const ws = XLSX.utils.table_to_sheet(clonedTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Probleme Raportate");
        XLSX.writeFile(wb, `Probleme_Raportate.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('problemsTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Probleme Raportate</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Probleme Raportate</h1>
                ${tableToPrint.outerHTML}
            </body>
            </html>
        `;
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
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
