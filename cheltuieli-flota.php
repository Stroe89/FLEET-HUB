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

// Preluăm lista de vehicule pentru dropdown-uri de filtrare și adăugare
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

// Preluăm lista de cheltuieli
$cheltuieli_list = [];
$sql_cheltuieli = "
    SELECT ch.*, v.model, v.numar_inmatriculare
    FROM cheltuieli ch
    LEFT JOIN vehicule v ON ch.id_vehicul = v.id
    ORDER BY ch.data_cheltuielii DESC, ch.created_at DESC
";
$result_cheltuieli = $conn->query($sql_cheltuieli);
if ($result_cheltuieli) {
    while ($row = $result_cheltuieli->fetch_assoc()) {
        $cheltuieli_list[] = $row;
    }
}
$conn->close();

// Categorii de cheltuieli pentru filtrare
$categorii_cheltuieli = [
    'Combustibil', 'Mentenanță', 'Taxe', 'Asigurări', 'Salarii', 'Chirie',
    'Amenzi', 'Piese de schimb', 'Anvelope', 'Altele'
];
?>

<title>NTS TOUR | Cheltuieli Flotă</title>

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

    /* Stiluri specifice pentru tabelul de cheltuieli */
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
    .badge-categorie-combustibil { background-color: #0d6efd !important; color: #fff !important; }
    .badge-categorie-mentenanță { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-categorie-taxe { background-color: #28a745 !important; color: #fff !important; }
    .badge-categorie-asigurări { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-categorie-salarii { background-color: #6f42c1 !important; color: #fff !important; }
    .badge-categorie-chirie { background-color: #fd7e14 !important; color: #fff !important; }
    .badge-categorie-amenzi { background-color: #dc3545 !important; color: #fff !important; }
    .badge-categorie-piese_de_schimb { background-color: #9c27b0 !important; color: #fff !important; }
    .badge-categorie-anvelope { background-color: #00bcd4 !important; color: #fff !important; }
    .badge-categorie-altele { background-color: #607d8b !important; color: #fff !important; }


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
            <div class="breadcrumb-title pe-3">Contabilitate</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cheltuieli Flotă</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Cheltuieli Flotă</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditCheltuialaModal" id="addCheltuialaBtn">
                            <i class="bx bx-plus"></i> Adaugă Cheltuială Nouă
                        </button>

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterVehicul" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicul">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterCategorie" class="form-label">Filtrează după Categorie:</label>
                                <select class="form-select" id="filterCategorie">
                                    <option value="all">Toate Categoriile</option>
                                    <?php foreach ($categorii_cheltuieli as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta descriere, observații...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Cheltuielilor -->
                        <?php if (empty($cheltuieli_list)): ?>
                            <div class="alert alert-info">Nu există cheltuieli înregistrate pentru flotă.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Dată</th>
                                            <th>Descriere</th>
                                            <th>Categorie</th>
                                            <th>Sumă</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cheltuieliTableBody">
                                        <?php foreach ($cheltuieli_list as $cheltuiala): ?>
                                            <tr 
                                                data-id="<?php echo $cheltuiala['id']; ?>"
                                                data-id-vehicul="<?php echo htmlspecialchars($cheltuiala['id_vehicul'] ?? ''); ?>"
                                                data-data-cheltuielii="<?php echo htmlspecialchars($cheltuiala['data_cheltuielii']); ?>"
                                                data-descriere="<?php echo htmlspecialchars($cheltuiala['descriere']); ?>"
                                                data-suma="<?php echo htmlspecialchars($cheltuiala['suma']); ?>"
                                                data-moneda="<?php echo htmlspecialchars($cheltuiala['moneda']); ?>"
                                                data-categorie="<?php echo htmlspecialchars($cheltuiala['categorie']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($cheltuiala['observatii']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($cheltuiala['model'] . ' ' . $cheltuiala['numar_inmatriculare'] . ' ' . $cheltuiala['descriere'] . ' ' . $cheltuiala['categorie'] . ' ' . $cheltuiala['observatii'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($cheltuiala['model'] ? $cheltuiala['model'] . ' (' . $cheltuiala['numar_inmatriculare'] . ')' : 'N/A'); ?></td>
                                                <td data-label="Dată:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($cheltuiala['data_cheltuielii']))); ?></td>
                                                <td data-label="Descriere:"><?php echo htmlspecialchars(mb_strimwidth($cheltuiala['descriere'], 0, 50, "...")); ?></td>
                                                <td data-label="Categorie:"><span class="badge badge-categorie-<?php echo strtolower(str_replace(' ', '_', $cheltuiala['categorie'])); ?>"><?php echo htmlspecialchars($cheltuiala['categorie']); ?></span></td>
                                                <td data-label="Sumă:"><?php echo htmlspecialchars($cheltuiala['suma'] . ' ' . $cheltuiala['moneda']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-cheltuiala-btn" data-bs-toggle="modal" data-bs-target="#addEditCheltuialaModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-cheltuiala-btn" data-id="<?php echo $cheltuiala['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Cheltuială -->
<div class="modal fade" id="addEditCheltuialaModal" tabindex="-1" aria-labelledby="addEditCheltuialaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditCheltuialaModalLabel">Adaugă Cheltuială Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cheltuialaForm" action="process_cheltuieli.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="cheltuialaAction" name="action" value="add">
                    <input type="hidden" id="cheltuialaId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalDescriere" class="form-label">Descriere Cheltuială:</label>
                            <input type="text" class="form-control" id="modalDescriere" name="descriere" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataCheltuielii" class="form-label">Dată Cheltuială:</label>
                            <input type="date" class="form-control" id="modalDataCheltuielii" name="data_cheltuielii" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalSuma" class="form-label">Sumă:</label>
                            <input type="number" step="0.01" class="form-control" id="modalSuma" name="suma" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalMoneda" class="form-label">Monedă:</label>
                            <input type="text" class="form-control" id="modalMoneda" name="moneda" value="RON" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCategorie" class="form-label">Categorie:</label>
                            <select class="form-select" id="modalCategorie" name="categorie" required>
                                <option value="">Selectează Categoria</option>
                                <?php foreach ($categorii_cheltuieli as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalIdVehicul" class="form-label">Vehicul (opțional):</label>
                            <select class="form-select" id="modalIdVehicul" name="id_vehicul">
                                <option value="">Fără vehicul</option>
                                <?php foreach ($vehicule_list as $veh): ?>
                                    <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
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
                    <button type="submit" class="btn btn-primary">Salvează Cheltuială</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Cheltuială -->
<div class="modal fade" id="deleteCheltuialaModal" tabindex="-1" aria-labelledby="deleteCheltuialaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCheltuialaModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi această cheltuială? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteCheltuialaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCheltuialaBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditCheltuialaModal = document.getElementById('addEditCheltuialaModal');
    const cheltuialaForm = document.getElementById('cheltuialaForm');
    const addCheltuialaBtn = document.getElementById('addCheltuialaBtn');
    const deleteCheltuialaModal = document.getElementById('deleteCheltuialaModal');
    const confirmDeleteCheltuialaBtn = document.getElementById('confirmDeleteCheltuialaBtn');
    const cheltuieliTableBody = document.getElementById('cheltuieliTableBody');

    // Filtrare
    const filterVehicul = document.getElementById('filterVehicul');
    const filterCategorie = document.getElementById('filterCategorie');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedCategorie = filterCategorie.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#cheltuieliTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowCategorie = row.getAttribute('data-categorie');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const categorieMatch = (selectedCategorie === 'all' || rowCategorie === selectedCategorie);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && categorieMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterCategorie.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addCheltuialaBtn.addEventListener('click', function() {
        cheltuialaForm.reset();
        document.getElementById('cheltuialaAction').value = 'add';
        document.getElementById('cheltuialaId').value = '';
        document.getElementById('addEditCheltuialaModalLabel').textContent = 'Adaugă Cheltuială Nouă';
        // Setează data cheltuielii la data curentă implicit
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        document.getElementById('modalDataCheltuielii').value = formattedDate;
        document.getElementById('modalMoneda').value = 'RON'; // Moneda implicită
    });

    // Deschide modalul pentru editare
    cheltuieliTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-cheltuiala-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('cheltuialaAction').value = 'edit';
            document.getElementById('cheltuialaId').value = row.getAttribute('data-id');
            document.getElementById('addEditCheltuialaModalLabel').textContent = 'Editează Cheltuială';

            document.getElementById('modalDescriere').value = row.getAttribute('data-descriere');
            document.getElementById('modalDataCheltuielii').value = row.getAttribute('data-data-cheltuielii');
            document.getElementById('modalSuma').value = row.getAttribute('data-suma');
            document.getElementById('modalMoneda').value = row.getAttribute('data-moneda');
            document.getElementById('modalCategorie').value = row.getAttribute('data-categorie');
            document.getElementById('modalIdVehicul').value = row.getAttribute('data-id-vehicul');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    cheltuialaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(cheltuialaForm);

        fetch('process_cheltuieli.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditCheltuialaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea cheltuielii:', error);
            alert('A apărut o eroare la salvarea cheltuielii.');
        });
    });

    // Ștergerea cheltuielii
    cheltuieliTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-cheltuiala-btn')) {
            const cheltuialaIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteCheltuialaId').value = cheltuialaIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteCheltuialaModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteCheltuialaBtn.addEventListener('click', function() {
        const cheltuialaIdToDelete = document.getElementById('deleteCheltuialaId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', cheltuialaIdToDelete);

        fetch('process_cheltuieli.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteCheltuialaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea cheltuielii:', error);
            alert('A apărut o eroare la ștergerea cheltuielii.');
        });
    });
});
</script>
