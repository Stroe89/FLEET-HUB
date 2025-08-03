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
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare, tip FROM vehicule ORDER BY model ASC, numar_inmatriculare ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

// Preluăm lista de polițe de asigurare
$polite_list = [];
$sql_polite = "
    SELECT pa.*, v.model, v.numar_inmatriculare, v.tip as tip_vehicul
    FROM polite_asigurare pa
    JOIN vehicule v ON pa.id_vehicul = v.id
    ORDER BY pa.data_sfarsit_valabilitate DESC
";
$result_polite = $conn->query($sql_polite);
if ($result_polite) {
    while ($row = $result_polite->fetch_assoc()) {
        $polite_list[] = $row;
    }
}
$conn->close();

// Tipuri de asigurare pentru filtrare
$tipuri_asigurare = ['RCA', 'CASCO', 'CMR', 'RCA Cargo', 'Asigurare de Viață', 'Altele'];
?>

<title>NTS TOUR | Polițe Asigurare</title>

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

    /* Stiluri specifice pentru tabelul de polițe */
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
    /* Badge-uri pentru tipuri de asigurare (exemplu, extindeți după nevoi) */
    .badge-tip-rca { background-color: #0d6efd !important; color: #fff !important; }
    .badge-tip-casco { background-color: #28a745 !important; color: #fff !important; }
    .badge-tip-cmr { background-color: #6c757d !important; color: #fff !important; }
    .badge-tip-rca_cargo { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-tip-asigurare_de_viață { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-tip-altele { background-color: #dc3545 !important; color: #fff !important; }

    /* Stil pentru notificări de expirare */
    .expiration-warning {
        color: #ffc107; /* Galben pentru avertisment */
        font-weight: bold;
    }
    .expiration-danger {
        color: #dc3545; /* Roșu pentru expirat */
        font-weight: bold;
    }

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
            <div class="breadcrumb-title pe-3">Documente</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Polițe Asigurare</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Polițe Asigurare</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditPolitaModal" id="addPolitaBtn">
                            <i class="bx bx-plus"></i> Adaugă Poliță Nouă
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
                                <label for="filterTipAsigurare" class="form-label">Filtrează după Tip Asigurare:</label>
                                <select class="form-select" id="filterTipAsigurare">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_asigurare as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta număr poliță, companie...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Polițelor -->
                        <?php if (empty($polite_list)): ?>
                            <div class="alert alert-info">Nu există polițe de asigurare înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Asigurare</th>
                                            <th>Nr. Poliță</th>
                                            <th>Companie</th>
                                            <th>Dată Emitere</th>
                                            <th>Valabilitate</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="politeTableBody">
                                        <?php foreach ($polite_list as $polita):
                                            $today = new DateTime();
                                            $data_sfarsit_valabilitate_dt = new DateTime($polita['data_sfarsit_valabilitate']);
                                            $interval = $today->diff($data_sfarsit_valabilitate_dt);
                                            
                                            $status_valabilitate = '';
                                            $status_class = '';

                                            if ($data_sfarsit_valabilitate_dt < $today) {
                                                $status_valabilitate = 'Expirată';
                                                $status_class = 'expiration-danger';
                                            } elseif ($interval->days <= 30) {
                                                $status_valabilitate = 'Expiră în ' . $interval->days . ' zile';
                                                $status_class = 'expiration-warning';
                                            } else {
                                                $status_valabilitate = 'Valabilă';
                                                $status_class = ''; // Nici o clasă specială pentru valabil
                                            }
                                        ?>
                                            <tr 
                                                data-id="<?php echo $polita['id']; ?>"
                                                data-id-vehicul="<?php echo $polita['id_vehicul']; ?>"
                                                data-tip-asigurare="<?php echo htmlspecialchars($polita['tip_asigurare']); ?>"
                                                data-numar-polita="<?php echo htmlspecialchars($polita['numar_polita']); ?>"
                                                data-companie-asigurare="<?php echo htmlspecialchars($polita['companie_asigurare']); ?>"
                                                data-data-emitere="<?php echo htmlspecialchars($polita['data_emitere']); ?>"
                                                data-data-inceput-valabilitate="<?php echo htmlspecialchars($polita['data_inceput_valabilitate']); ?>"
                                                data-data-sfarsit-valabilitate="<?php echo htmlspecialchars($polita['data_sfarsit_valabilitate']); ?>"
                                                data-valoare-asigurata="<?php echo htmlspecialchars($polita['valoare_asigurata']); ?>"
                                                data-prima-asigurare="<?php echo htmlspecialchars($polita['prima_asigurare']); ?>"
                                                data-moneda="<?php echo htmlspecialchars($polita['moneda']); ?>"
                                                data-cale-fisier="<?php echo htmlspecialchars($polita['cale_fisier'] ?? ''); ?>"
                                                data-nume-original-fisier="<?php echo htmlspecialchars($polita['nume_original_fisier'] ?? ''); ?>"
                                                data-observatii="<?php echo htmlspecialchars($polita['observatii']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($polita['model'] . ' ' . $polita['numar_inmatriculare'] . ' ' . $polita['tip_asigurare'] . ' ' . $polita['numar_polita'] . ' ' . $polita['companie_asigurare'] . ' ' . $polita['observatii'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($polita['model'] . ' (' . $polita['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Tip Asigurare:"><span class="badge badge-tip-<?php echo strtolower(str_replace(' ', '_', $polita['tip_asigurare'])); ?>"><?php echo htmlspecialchars($polita['tip_asigurare']); ?></span></td>
                                                <td data-label="Nr. Poliță:"><?php echo htmlspecialchars($polita['numar_polita']); ?></td>
                                                <td data-label="Companie:"><?php echo htmlspecialchars($polita['companie_asigurare']); ?></td>
                                                <td data-label="Dată Emitere:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($polita['data_emitere']))); ?></td>
                                                <td data-label="Valabilitate:"><?php echo htmlspecialchars(date('d.m.Y', strtotime($polita['data_inceput_valabilitate']))) . ' - ' . htmlspecialchars(date('d.m.Y', strtotime($polita['data_sfarsit_valabilitate']))); ?></td>
                                                <td data-label="Status:"><span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_valabilitate); ?></span></td>
                                                <td>
                                                    <?php if (!empty($polita['cale_fisier'])): ?>
                                                        <a href="download_document.php?id=<?php echo $polita['id']; ?>&type=polita" class="btn btn-sm btn-outline-info mb-1 w-100"><i class="bx bx-download"></i> Descarcă</a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-polita-btn mb-1 w-100" data-bs-toggle="modal" data-bs-target="#addEditPolitaModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-polita-btn w-100" data-id="<?php echo $polita['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Poliță Asigurare -->
<div class="modal fade" id="addEditPolitaModal" tabindex="-1" aria-labelledby="addEditPolitaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditPolitaModalLabel">Adaugă Poliță Asigurare Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="politaForm" action="process_polite_asigurare.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="politaAction" name="action" value="add">
                    <input type="hidden" id="politaId" name="id">
                    <input type="hidden" id="existingFilePath" name="existing_file_path">
                    <input type="hidden" id="existingFileName" name="existing_file_name">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalSelectVehicul" class="form-label">Vehicul:</label>
                            <select class="form-select" id="modalSelectVehicul" name="id_vehicul" required>
                                <option value="">Alege un vehicul</option>
                                <?php foreach ($vehicule_list as $veh): ?>
                                    <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalTipAsigurare" class="form-label">Tip Asigurare:</label>
                            <select class="form-select" id="modalTipAsigurare" name="tip_asigurare" required>
                                <option value="">Selectează Tipul</option>
                                <?php foreach ($tipuri_asigurare as $tip): ?>
                                    <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalNumarPolita" class="form-label">Număr Poliță:</label>
                            <input type="text" class="form-control" id="modalNumarPolita" name="numar_polita" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCompanieAsigurare" class="form-label">Companie Asigurare:</label>
                            <input type="text" class="form-control" id="modalCompanieAsigurare" name="companie_asigurare" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataEmitere" class="form-label">Dată Emitere:</label>
                            <input type="date" class="form-control" id="modalDataEmitere" name="data_emitere" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataInceputValabilitate" class="form-label">Dată Început Valabilitate:</label>
                            <input type="date" class="form-control" id="modalDataInceputValabilitate" name="data_inceput_valabilitate" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataSfarsitValabilitate" class="form-label">Dată Sfârșit Valabilitate:</label>
                            <input type="date" class="form-control" id="modalDataSfarsitValabilitate" name="data_sfarsit_valabilitate" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalValoareAsigurata" class="form-label">Valoare Asigurată:</label>
                            <input type="number" step="0.01" class="form-control" id="modalValoareAsigurata" name="valoare_asigurata">
                        </div>
                        <div class="col-md-6">
                            <label for="modalPrimaAsigurare" class="form-label">Prima Asigurare:</label>
                            <input type="number" step="0.01" class="form-control" id="modalPrimaAsigurare" name="prima_asigurare">
                        </div>
                        <div class="col-md-6">
                            <label for="modalMoneda" class="form-label">Monedă:</label>
                            <input type="text" class="form-control" id="modalMoneda" name="moneda" value="RON" required>
                        </div>
                        <div class="col-12">
                            <label for="modalPolitaFile" class="form-label">Încarcă Fișier Poliță (PDF/Imagine, opțional):</label>
                            <input class="form-control" type="file" id="modalPolitaFile" name="polita_file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted" id="currentFileText" style="display:none;">Fișier curent: <a href="#" id="currentFileLink" target="_blank"></a></small>
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații:</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Poliță</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Poliță -->
<div class="modal fade" id="deletePolitaModal" tabindex="-1" aria-labelledby="deletePolitaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePolitaModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi polița <strong id="deletePolitaNumber"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deletePolitaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePolitaBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditPolitaModal = document.getElementById('addEditPolitaModal');
    const politaForm = document.getElementById('politaForm');
    const addPolitaBtn = document.getElementById('addPolitaBtn');
    const deletePolitaModal = document.getElementById('deletePolitaModal');
    const confirmDeletePolitaBtn = document.getElementById('confirmDeletePolitaBtn');
    const politeTableBody = document.getElementById('politeTableBody');

    const currentFileText = document.getElementById('currentFileText');
    const currentFileLink = document.getElementById('currentFileLink');
    const modalPolitaFile = document.getElementById('modalPolitaFile');

    // Filtrare
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipAsigurare = document.getElementById('filterTipAsigurare');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipAsigurare = filterTipAsigurare.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#politeTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipAsigurare = row.getAttribute('data-tip-asigurare');
            const rowSearchText = row.getAttribute('data-search-text');

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipAsigurareMatch = (selectedTipAsigurare === 'all' || rowTipAsigurare === selectedTipAsigurare);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipAsigurareMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipAsigurare.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addPolitaBtn.addEventListener('click', function() {
        politaForm.reset();
        document.getElementById('politaAction').value = 'add';
        document.getElementById('politaId').value = '';
        document.getElementById('addEditPolitaModalLabel').textContent = 'Adaugă Poliță Nouă';
        
        // Setează datele implicite la data curentă
        const now = new Date();
        const formattedDate = now.toISOString().substring(0, 10);
        document.getElementById('modalDataEmitere').value = formattedDate;
        document.getElementById('modalDataInceputValabilitate').value = formattedDate;
        document.getElementById('modalDataSfarsitValabilitate').value = ''; 
        document.getElementById('modalMoneda').value = 'RON';
        
        currentFileText.style.display = 'none'; // Ascunde info fișier curent
        modalPolitaFile.value = ''; // Golește input-ul de fișier
        document.getElementById('existingFilePath').value = '';
        document.getElementById('existingFileName').value = '';
    });

    // Deschide modalul pentru editare
    politeTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-polita-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('politaAction').value = 'edit';
            document.getElementById('politaId').value = row.getAttribute('data-id');
            document.getElementById('addEditPolitaModalLabel').textContent = 'Editează Poliță Asigurare';

            document.getElementById('modalSelectVehicul').value = row.getAttribute('data-id-vehicul');
            document.getElementById('modalTipAsigurare').value = row.getAttribute('data-tip-asigurare');
            document.getElementById('modalNumarPolita').value = row.getAttribute('data-numar-polita');
            document.getElementById('modalCompanieAsigurare').value = row.getAttribute('data-companie-asigurare');
            document.getElementById('modalDataEmitere').value = row.getAttribute('data-data-emitere');
            document.getElementById('modalDataInceputValabilitate').value = row.getAttribute('data-data-inceput-valabilitate');
            document.getElementById('modalDataSfarsitValabilitate').value = row.getAttribute('data-data-sfarsit-valabilitate');
            document.getElementById('modalValoareAsigurata').value = row.getAttribute('data-valoare-asigurata');
            document.getElementById('modalPrimaAsigurare').value = row.getAttribute('data-prima-asigurare');
            document.getElementById('modalMoneda').value = row.getAttribute('data-moneda');
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii');

            const currentFilePath = row.getAttribute('data-cale-fisier');
            const currentOriginalFileName = row.getAttribute('data-nume-original-fisier');

            if (currentFilePath && currentFilePath !== 'null' && currentFilePath !== '') {
                currentFileLink.href = currentFilePath;
                currentFileLink.textContent = currentOriginalFileName || 'Fișier atașat';
                currentFileText.style.display = 'block';
                document.getElementById('existingFilePath').value = currentFilePath;
                document.getElementById('existingFileName').value = currentOriginalFileName;
            } else {
                currentFileText.style.display = 'none';
                document.getElementById('existingFilePath').value = '';
                document.getElementById('existingFileName').value = '';
            }
            modalPolitaFile.value = ''; // Golește input-ul de fișier la editare
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    politaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(politaForm);

        // Dacă nu se încarcă un fișier nou, păstrează calea existentă
        if (modalPolitaFile.files.length === 0 && document.getElementById('existingFilePath').value) {
            formData.append('cale_fisier', document.getElementById('existingFilePath').value);
            formData.append('nume_original_fisier', document.getElementById('existingFileName').value);
        }

        fetch('process_polite_asigurare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditPolitaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea poliței:', error);
            alert('A apărut o eroare la salvarea poliței.');
        });
    });

    // Ștergerea poliței
    politeTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-polita-btn')) {
            const politaIdToDelete = e.target.getAttribute('data-id');
            const politaNumber = e.target.closest('tr').getAttribute('data-numar-polita');
            document.getElementById('deletePolitaId').value = politaIdToDelete;
            document.getElementById('deletePolitaNumber').textContent = politaNumber;
            const deleteModalInstance = new bootstrap.Modal(deletePolitaModal);
            deleteModalInstance.show();
        }
    });

    confirmDeletePolitaBtn.addEventListener('click', function() {
        const politaIdToDelete = document.getElementById('deletePolitaId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', politaIdToDelete);

        fetch('process_polite_asigurare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deletePolitaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea poliței:', error);
            alert('A apărut o eroare la ștergerea poliței.');
        });
    });
});
</script>
