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

// Preluăm lista de tranzacții financiare
$tranzactii_list = [];
$sql_tranzactii = "SELECT * FROM registru_financiar ORDER BY data_tranzactiei DESC, created_at DESC";
$result_tranzactii = $conn->query($sql_tranzactii);
if ($result_tranzactii) {
    while ($row = $result_tranzactii->fetch_assoc()) {
        $tranzactii_list[] = $row;
    }
}
$conn->close();

// Tipuri de tranzacții și categorii pentru filtrare și adăugare
$tipuri_tranzactii = ['Incasare', 'Plata'];
$categorii_tranzactii = [
    'Transport', 'Combustibil', 'Salarii', 'Mentenanță', 'Taxe', 'Chirie',
    'Amenzi', 'Asigurări', 'Altele'
];
?>

<title>NTS TOUR | Încasări & Plăți</title>

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

    /* Stiluri specifice pentru tabelul de tranzacții */
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
    .badge-tip-incasare { background-color: #28a745 !important; color: #fff !important; } /* Verde pentru încasări */
    .badge-tip-plata { background-color: #dc3545 !important; color: #fff !important; } /* Roșu pentru plăți */

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
                        <li class="breadcrumb-item active" aria-current="page">Încasări & Plăți</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Registru Financiar: Încasări & Plăți</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditTranzactieModal" id="addTranzactieBtn">
                            <i class="bx bx-plus"></i> Adaugă Tranzacție
                        </button>

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterTipTranzactie" class="form-label">Filtrează după Tip:</label>
                                <select class="form-select" id="filterTipTranzactie">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_tranzactii as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterCategorie" class="form-label">Filtrează după Categorie:</label>
                                <select class="form-select" id="filterCategorie">
                                    <option value="all">Toate Categoriile</option>
                                    <?php foreach ($categorii_tranzactii as $cat): ?>
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

                        <!-- Lista Tranzacțiilor -->
                        <?php if (empty($tranzactii_list)): ?>
                            <div class="alert alert-info">Nu există tranzacții înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tip</th>
                                            <th>Dată</th>
                                            <th>Sumă</th>
                                            <th>Descriere</th>
                                            <th>Categorie</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tranzactiiTableBody">
                                        <?php foreach ($tranzactii_list as $tranzactie): ?>
                                            <tr 
                                                data-id="<?php echo $tranzactie['id']; ?>"
                                                data-tip-tranzactie="<?php echo htmlspecialchars($tranzactie['tip_tranzactie']); ?>"
                                                data-data-tranzactiei="<?php echo htmlspecialchars($tranzactie['data_tranzactiei']); ?>"
                                                data-suma="<?php echo htmlspecialchars($tranzactie['suma']); ?>"
                                                data-moneda="<?php echo htmlspecialchars($tranzactie['moneda']); ?>"
                                                data-descriere="<?php echo htmlspecialchars($tranzactie['descriere']); ?>"
                                                data-categorie="<?php echo htmlspecialchars($tranzactie['categorie']); ?>"
                                                data-observatii="<?php echo htmlspecialchars($tranzactie['observatii']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($tranzactie['descriere'] . ' ' . $tranzactie['categorie'] . ' ' . $tranzactie['observatii'])); ?>"
                                            >
                                                <td data-label="Tip:"><span class="badge badge-tip-<?php echo strtolower($tranzactie['tip_tranzactie']); ?>"><?php echo htmlspecialchars($tranzactie['tip_tranzactie']); ?></span></td>
                                                <td data-label="Dată:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($tranzactie['data_tranzactiei']))); ?></td>
                                                <td data-label="Sumă:"><?php echo htmlspecialchars($tranzactie['suma'] . ' ' . $tranzactie['moneda']); ?></td>
                                                <td data-label="Descriere:"><?php echo htmlspecialchars(mb_strimwidth($tranzactie['descriere'], 0, 50, "...")); ?></td>
                                                <td data-label="Categorie:"><?php echo htmlspecialchars($tranzactie['categorie'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-tranzactie-btn" data-bs-toggle="modal" data-bs-target="#addEditTranzactieModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-tranzactie-btn" data-id="<?php echo $tranzactie['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Tranzacție -->
<div class="modal fade" id="addEditTranzactieModal" tabindex="-1" aria-labelledby="addEditTranzactieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditTranzactieModalLabel">Adaugă Tranzacție Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="tranzactieForm" action="process_tranzactii_financiare.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="tranzactieAction" name="action" value="add">
                    <input type="hidden" id="tranzactieId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalTipTranzactie" class="form-label">Tip Tranzacție:</label>
                            <select class="form-select" id="modalTipTranzactie" name="tip_tranzactie" required>
                                <?php foreach ($tipuri_tranzactii as $tip): ?>
                                    <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataTranzactiei" class="form-label">Dată Tranzacție:</label>
                            <input type="date" class="form-control" id="modalDataTranzactiei" name="data_tranzactiei" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalSuma" class="form-label">Sumă:</label>
                            <input type="number" step="0.01" class="form-control" id="modalSuma" name="suma" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalMoneda" class="form-label">Monedă:</label>
                            <input type="text" class="form-control" id="modalMoneda" name="moneda" value="RON" required>
                        </div>
                        <div class="col-12">
                            <label for="modalDescriere" class="form-label">Descriere:</label>
                            <textarea class="form-control" id="modalDescriere" name="descriere" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCategorie" class="form-label">Categorie:</label>
                            <select class="form-select" id="modalCategorie" name="categorie">
                                <option value="">Fără Categorie</option>
                                <?php foreach ($categorii_tranzactii as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
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
                    <button type="submit" class="btn btn-primary">Salvează Tranzacție</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Tranzacție -->
<div class="modal fade" id="deleteTranzactieModal" tabindex="-1" aria-labelledby="deleteTranzactieModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTranzactieModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi această tranzacție? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteTranzactieId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTranzactieBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditTranzactieModal = document.getElementById('addEditTranzactieModal');
    const tranzactieForm = document.getElementById('tranzactieForm');
    const addTranzactieBtn = document.getElementById('addTranzactieBtn');
    const deleteTranzactieModal = document.getElementById('deleteTranzactieModal');
    const confirmDeleteTranzactieBtn = document.getElementById('confirmDeleteTranzactieBtn');
    const tranzactiiTableBody = document.getElementById('tranzactiiTableBody');

    // Filtrare
    const filterTipTranzactie = document.getElementById('filterTipTranzactie');
    const filterCategorie = document.getElementById('filterCategorie');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedTipTranzactie = filterTipTranzactie.value;
        const selectedCategorie = filterCategorie.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#tranzactiiTableBody tr').forEach(row => {
            const rowTipTranzactie = row.getAttribute('data-tip-tranzactie');
            const rowCategorie = row.getAttribute('data-categorie');
            const rowSearchText = row.getAttribute('data-search-text');

            const tipTranzactieMatch = (selectedTipTranzactie === 'all' || rowTipTranzactie === selectedTipTranzactie);
            const categorieMatch = (selectedCategorie === 'all' || rowCategorie === selectedCategorie);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (tipTranzactieMatch && categorieMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterTipTranzactie.addEventListener('change', filterTable);
    filterCategorie.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addTranzactieBtn.addEventListener('click', function() {
        tranzactieForm.reset();
        document.getElementById('tranzactieAction').value = 'add';
        document.getElementById('tranzactieId').value = '';
        document.getElementById('addEditTranzactieModalLabel').textContent = 'Adaugă Tranzacție Nouă';
        // Setează data tranzacției la data curentă implicit
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        document.getElementById('modalDataTranzactiei').value = formattedDate;
        document.getElementById('modalMoneda').value = 'RON'; // Moneda implicită
    });

    // Deschide modalul pentru editare
    tranzactiiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-tranzactie-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('tranzactieAction').value = 'edit';
            document.getElementById('tranzactieId').value = row.getAttribute('data-id');
            document.getElementById('addEditTranzactieModalLabel').textContent = 'Editează Tranzacție';

            document.getElementById('modalTipTranzactie').value = row.getAttribute('data-tip-tranzactie');
            document.getElementById('modalDataTranzactiei').value = row.getAttribute('data-data-tranzactiei');
            document.getElementById('modalSuma').value = row.getAttribute('data-suma');
            document.getElementById('modalMoneda').value = row.getAttribute('data-moneda');
            document.getElementById('modalDescriere').value = row.getAttribute('data-descriere');
            document.getElementById('modalCategorie').value = row.getAttribute('data-categorie');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    tranzactieForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(tranzactieForm);

        fetch('process_tranzactii_financiare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditTranzactieModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea tranzacției:', error);
            alert('A apărut o eroare la salvarea tranzacției.');
        });
    });

    // Ștergerea tranzacției
    tranzactiiTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-tranzactie-btn')) {
            const tranzactieIdToDelete = e.target.getAttribute('data-id');
            document.getElementById('deleteTranzactieId').value = tranzactieIdToDelete;
            const deleteModalInstance = new bootstrap.Modal(deleteTranzactieModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteTranzactieBtn.addEventListener('click', function() {
        const tranzactieIdToDelete = document.getElementById('deleteTranzactieId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', tranzactieIdToDelete);

        fetch('process_tranzactii_financiare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteTranzactieModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea tranzacției:', error);
            alert('A apărut o eroare la ștergerea tranzacției.');
        });
    });
});
</script>
