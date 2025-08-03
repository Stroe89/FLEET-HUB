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

// Preluăm lista de mecanici pentru dropdown-uri de filtrare și adăugare
$mecanici_list = [];
$stmt_mecanici = $conn->prepare("SELECT id, nume, prenume FROM angajati WHERE functie = 'Mecanic' ORDER BY nume ASC, prenume ASC");
if ($stmt_mecanici) {
    $stmt_mecanici->execute();
    $result_mecanici = $stmt_mecanici->get_result();
    while ($row = $result_mecanici->fetch_assoc()) {
        $mecanici_list[] = $row;
    }
    $stmt_mecanici->close();
}

// Preluăm înregistrările de mentenanță care necesită confirmare
$mentenanta_awaiting_confirmation = [];
$sql_mentenanta = "
    SELECT im.*, v.model, v.numar_inmatriculare, v.tip as tip_vehicul,
           a.nume as mecanic_nume, a.prenume as mecanic_prenume
    FROM istoric_mentenanta im
    JOIN vehicule v ON im.id_vehicul = v.id
    LEFT JOIN angajati a ON im.id_mecanic = a.id
    WHERE im.status IN ('În Așteptare', 'În Desfășurare')
    ORDER BY im.data_intrare_service DESC
";
$result_mentenanta = $conn->query($sql_mentenanta);
if ($result_mentenanta) {
    while ($row = $result_mentenanta->fetch_assoc()) {
        $mentenanta_awaiting_confirmation[] = $row;
    }
}
$conn->close();

// Tipuri de mentenanță pentru filtrare
$tipuri_mentenanta = ['Revizie Periodică', 'Reparație Majoră', 'Reparație Minoră', 'Inspecție Tehnică', 'Schimb Anvelope', 'Altele'];

// Statusuri mentenanță pentru filtrare (doar cele relevante pentru confirmare)
$statusuri_confirmare = ['În Așteptare', 'În Desfășurare'];

// Tipuri de vehicule pentru filtrare (extrage din lista de vehicule pentru a fi dinamice)
$tipuri_vehicul_list = array_unique(array_column($vehicule_list, 'tip'));
sort($tipuri_vehicul_list);
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Confirmare Lucrări</title>

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

    /* Stiluri specifice pentru tabelul de mentenanță */
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
    /* Badge-uri pentru statusul mentenanței */
    .badge-status-în_așteptare { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-status-în_desfășurare { background-color: #0d6efd !important; color: #fff !important; }
    .badge-status-finalizată { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-anulată { background-color: #dc3545 !important; color: #fff !important; }

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
            <div class="breadcrumb-title pe-3">Mentenanță</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Confirmare Lucrări</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Lucrări Mentenanță în Așteptare/Desfășurare</h4>
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

                        <!-- Secțiunea de Filtrare Avansată -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-3 mb-3">
                                <label for="filterVehicul" class="form-label">Filtrează după Vehicul:</label>
                                <select class="form-select" id="filterVehicul">
                                    <option value="all">Toate Vehiculele</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterTipMentenanta" class="form-label">Filtrează după Tip Mentenanță:</label>
                                <select class="form-select" id="filterTipMentenanta">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_mentenanta as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterMecanic" class="form-label">Filtrează după Mecanic:</label>
                                <select class="form-select" id="filterMecanic">
                                    <option value="all">Toți Mecanicii</option>
                                    <?php foreach ($mecanici_list as $mecanic): ?>
                                        <option value="<?php echo $mecanic['id']; ?>"><?php echo htmlspecialchars($mecanic['nume'] . ' ' . $mecanic['prenume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_confirmare as $status): // Doar statusurile de confirmare ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterStartDate" class="form-label">Dată Intrare (de la):</label>
                                <input type="date" class="form-control" id="filterStartDate">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filterSearch" class="form-label">Căutare Text:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta descriere, observații, factură...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Înregistrărilor de Mentenanță Așteptând Confirmare -->
                        <?php if (empty($mentenanta_awaiting_confirmation)): ?>
                            <div class="alert alert-info">Nu există înregistrări de mentenanță în așteptare sau în desfășurare.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Mentenanță</th>
                                            <th>Dată Intrare</th>
                                            <th>Status</th>
                                            <th>Mecanic</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mentenantaTableBody">
                                        <?php foreach ($mentenanta_awaiting_confirmation as $record): ?>
                                            <tr 
                                                data-id="<?php echo $record['id']; ?>"
                                                data-id-vehicul="<?php echo $record['id_vehicul']; ?>"
                                                data-tip-vehicul="<?php echo htmlspecialchars($record['tip_vehicul'] ?? ''); ?>"
                                                data-tip-mentenanta="<?php echo htmlspecialchars($record['tip_mentenanta']); ?>"
                                                data-descriere-problema="<?php echo htmlspecialchars($record['descriere_problema']); ?>"
                                                data-descriere-lucrari="<?php echo htmlspecialchars($record['descriere_lucrari']); ?>"
                                                data-data-intrare-service="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($record['data_intrare_service']))); ?>"
                                                data-data-iesire-service="<?php echo htmlspecialchars($record['data_iesire_service'] ? date('Y-m-d\TH:i', strtotime($record['data_iesire_service'])) : ''); ?>"
                                                data-cost-total="<?php echo htmlspecialchars($record['cost_total']); ?>"
                                                data-factura-serie="<?php echo htmlspecialchars($record['factura_serie']); ?>"
                                                data-factura-numar="<?php echo htmlspecialchars($record['factura_numar']); ?>"
                                                data-status="<?php echo htmlspecialchars($record['status']); ?>"
                                                data-id-mecanic="<?php echo htmlspecialchars($record['id_mecanic'] ?? ''); ?>"
                                                data-observatii="<?php echo htmlspecialchars($record['observatii']); ?>"
                                                data-km-la-intrare="<?php echo htmlspecialchars($record['km_la_intrare']); ?>"
                                                data-km-la-iesire="<?php echo htmlspecialchars($record['km_la_iesire']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($record['model'] . ' ' . $record['numar_inmatriculare'] . ' ' . $record['tip_mentenanta'] . ' ' . $record['descriere_problema'] . ' ' . $record['descriere_lucrari'] . ' ' . $record['factura_serie'] . ' ' . $record['factura_numar'] . ' ' . ($record['mecanic_nume'] ?? '') . ' ' . ($record['mecanic_prenume'] ?? '') . ' ' . $record['observatii'])); ?>"
                                            >
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($record['model'] . ' (' . $record['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Tip Mentenanță:"><?php echo htmlspecialchars($record['tip_mentenanta']); ?></td>
                                                <td data-label="Dată Intrare:"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($record['data_intrare_service']))); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                                <td data-label="Mecanic:"><?php echo htmlspecialchars($record['mecanic_nume'] ? $record['mecanic_nume'] . ' ' . $record['mecanic_prenume'] : 'N/A'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-success confirm-mentenanta-btn w-100" data-bs-toggle="modal" data-bs-target="#confirmMentenantaModal">Confirmă Lucrare</button>
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

<!-- Modal Confirmare Lucrare Mentenanță -->
<div class="modal fade" id="confirmMentenantaModal" tabindex="-1" aria-labelledby="confirmMentenantaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmMentenantaModalLabel">Confirmă Finalizarea Lucrării</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="confirmMentenantaForm" action="process_mentenanta.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="confirm">
                    <input type="hidden" id="confirmMentenantaId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vehicul:</label>
                            <p class="form-control-plaintext text-white" id="confirmVehicul"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tip Mentenanță:</label>
                            <p class="form-control-plaintext text-white" id="confirmTipMentenanta"></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descriere Lucrări Efectuate (inițială):</label>
                            <p class="form-control-plaintext text-white" id="confirmDescriereLucrariInitiala"></p>
                        </div>
                        <div class="col-md-6">
                            <label for="modalDataIesireService" class="form-label">Dată Ieșire Service (reală):</label>
                            <input type="datetime-local" class="form-control" id="modalDataIesireService" name="data_iesire_service" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalKmLaIesire" class="form-label">Km la Ieșire Service:</label>
                            <input type="number" class="form-control" id="modalKmLaIesire" name="km_la_iesire" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCostTotal" class="form-label">Cost Total Final (RON):</label>
                            <input type="number" step="0.01" class="form-control" id="modalCostTotal" name="cost_total" value="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalFacturaSerie" class="form-label">Serie Factură:</label>
                            <input type="text" class="form-control" id="modalFacturaSerie" name="factura_serie">
                        </div>
                        <div class="col-md-6">
                            <label for="modalFacturaNumar" class="form-label">Număr Factură:</label>
                            <input type="text" class="form-control" id="modalFacturaNumar" name="factura_numar">
                        </div>
                        <div class="col-12">
                            <label for="modalObservatii" class="form-label">Observații Finale (opțional):</label>
                            <textarea class="form-control" id="modalObservatii" name="observatii" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success">Confirmă & Finalizează</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mentenantaTableBody = document.getElementById('mentenantaTableBody');
    const confirmMentenantaModal = document.getElementById('confirmMentenantaModal');
    const confirmMentenantaForm = document.getElementById('confirmMentenantaForm');

    // Filtrare Tabel
    const filterVehicul = document.getElementById('filterVehicul');
    const filterTipMentenanta = document.getElementById('filterTipMentenanta');
    const filterMecanic = document.getElementById('filterMecanic');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');
    const filterStartDate = document.getElementById('filterStartDate');

    function filterTable() {
        const selectedVehiculId = filterVehicul.value;
        const selectedTipMentenanta = filterTipMentenanta.value;
        const selectedMecanicId = filterMecanic.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#mentenantaTableBody tr').forEach(row => {
            const rowVehiculId = row.getAttribute('data-id-vehicul');
            const rowTipMentenanta = row.getAttribute('data-tip-mentenanta');
            const rowMecanicId = row.getAttribute('data-id-mecanic');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');
            const rowDataIntrare = new Date(row.getAttribute('data-data-intrare-service')).getTime();

            let dateMatch = true;
            if (filterStartDate.value) {
                const startFilterTime = new Date(filterStartDate.value).getTime();
                if (rowDataIntrare < startFilterTime) {
                    dateMatch = false;
                }
            }

            const vehiculMatch = (selectedVehiculId === 'all' || rowVehiculId === selectedVehiculId);
            const tipMentenantaMatch = (selectedTipMentenanta === 'all' || rowTipMentenanta === selectedTipMentenanta);
            const mecanicMatch = (selectedMecanicId === 'all' || rowMecanicId === selectedMecanicId);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (vehiculMatch && tipMentenantaMatch && mecanicMatch && statusMatch && searchMatch && dateMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterVehicul.addEventListener('change', filterTable);
    filterTipMentenanta.addEventListener('change', filterTable);
    filterMecanic.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterStartDate.addEventListener('change', filterTable);


    // Deschide modalul pentru confirmare lucrare
    mentenantaTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('confirm-mentenanta-btn')) {
            const row = e.target.closest('tr');
            
            document.getElementById('confirmMentenantaId').value = row.getAttribute('data-id');
            document.getElementById('confirmVehicul').textContent = row.querySelector('td[data-label="Vehicul:"]').textContent;
            document.getElementById('confirmTipMentenanta').textContent = row.getAttribute('data-tip-mentenanta');
            document.getElementById('confirmDescriereLucrariInitiala').textContent = row.getAttribute('data-descriere-lucrari') || 'N/A';

            // Setează data/ora de ieșire la momentul curent
            const now = new Date();
            const formattedNow = now.toISOString().substring(0, 16);
            document.getElementById('modalDataIesireService').value = formattedNow;
            
            // Populează cu valori existente dacă există (la re-editare sau dacă sunt deja pre-populate)
            document.getElementById('modalKmLaIesire').value = row.getAttribute('data-km-la-iesire') || '';
            document.getElementById('modalCostTotal').value = parseFloat(row.getAttribute('data-cost-total')).toFixed(2);
            document.getElementById('modalFacturaSerie').value = row.getAttribute('data-factura-serie') || '';
            document.getElementById('modalFacturaNumar').value = row.getAttribute('data-factura-numar') || '';
            document.getElementById('modalObservatii').value = row.getAttribute('data-observatii') || '';


            const confirmMentenantaModalInstance = new bootstrap.Modal(confirmMentenantaModal);
            confirmMentenantaModalInstance.show();
        }
    });

    // Trimiterea formularului de confirmare
    confirmMentenantaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(confirmMentenantaForm);
        formData.append('action', 'confirm'); // Acțiunea specifică pentru confirmare

        fetch('process_mentenanta.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(confirmMentenantaModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la confirmarea lucrării:', error);
            alert('A apărut o eroare la confirmarea lucrării.');
        });
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