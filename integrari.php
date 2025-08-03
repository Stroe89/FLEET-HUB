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

// Preluăm lista de integrări existente
$integrari_list = [];
$sql_integrari = "SELECT * FROM integrari_servicii ORDER BY nume_integrare ASC";
$result_integrari = $conn->query($sql_integrari);
if ($result_integrari) {
    while ($row = $result_integrari->fetch_assoc()) {
        // Decodificăm JSON-ul pentru a-l folosi în JavaScript
        $row['config_json_decoded'] = json_decode($row['config_json'], true);
        $integrari_list[] = $row;
    }
}
$conn->close();

// Tipurile de integrare disponibile
$tipuri_integrare = ['GPS', 'ERP', 'Facturare', 'Altele'];
$statusuri_integrare = ['Activ', 'Inactiv', 'Eroare'];
?>

<title>NTS TOUR | Integrări Servicii</title>

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

    /* Stiluri specifice pentru tabelul de integrări */
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
    .badge-status-Activ { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-Inactiv { background-color: #6c757d !important; color: #fff !important; }
    .badge-status-Eroare { background-color: #dc3545 !important; color: #fff !important; }

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
            <div class="breadcrumb-title pe-3">Setări</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Integrări Servicii</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Integrări Servicii</h4>
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

                        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addEditIntegrationModal" id="addIntegrationBtn">
                            <i class="bx bx-plus"></i> Adaugă Integrare Nouă
                        </button>

                        <!-- Secțiunea de Filtrare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="filterType" class="form-label">Filtrează după Tip:</label>
                                <select class="form-select" id="filterType">
                                    <option value="all">Toate Tipurile</option>
                                    <?php foreach ($tipuri_integrare as $tip): ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_integrare as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume, descriere...">
                                </div>
                            </div>
                        </div>

                        <!-- Lista Integrărilor -->
                        <?php if (empty($integrari_list)): ?>
                            <div class="alert alert-info">Nu există integrări înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nume Integrare</th>
                                            <th>Tip</th>
                                            <th>Status</th>
                                            <th>Ultima Testare</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="integrationsTableBody">
                                        <?php foreach ($integrari_list as $integrare): ?>
                                            <tr 
                                                data-id="<?php echo $integrare['id']; ?>"
                                                data-nume-integrare="<?php echo htmlspecialchars($integrare['nume_integrare']); ?>"
                                                data-tip-integrare="<?php echo htmlspecialchars($integrare['tip_integrare']); ?>"
                                                data-status="<?php echo htmlspecialchars($integrare['status']); ?>"
                                                data-descriere="<?php echo htmlspecialchars($integrare['descriere']); ?>"
                                                data-config-json="<?php echo htmlspecialchars($integrare['config_json']); ?>"
                                                data-last-tested-at="<?php echo htmlspecialchars($integrare['last_tested_at'] ?? ''); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($integrare['nume_integrare'] . ' ' . $integrare['tip_integrare'] . ' ' . $integrare['descriere'])); ?>"
                                            >
                                                <td data-label="Nume Integrare:"><?php echo htmlspecialchars($integrare['nume_integrare']); ?></td>
                                                <td data-label="Tip:"><?php echo htmlspecialchars($integrare['tip_integrare']); ?></td>
                                                <td data-label="Status:"><span class="badge badge-status-<?php echo htmlspecialchars($integrare['status']); ?>"><?php echo htmlspecialchars($integrare['status']); ?></span></td>
                                                <td data-label="Ultima Testare:"><?php echo htmlspecialchars($integrare['last_tested_at'] ? date('d.m.Y H:i', strtotime($integrare['last_tested_at'])) : 'N/A'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info test-integration-btn" data-id="<?php echo $integrare['id']; ?>">Testează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-integration-btn" data-bs-toggle="modal" data-bs-target="#addEditIntegrationModal">Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-integration-btn" data-id="<?php echo $integrare['id']; ?>">Șterge</button>
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

<!-- Modal Adaugă/Editează Integrare -->
<div class="modal fade" id="addEditIntegrationModal" tabindex="-1" aria-labelledby="addEditIntegrationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditIntegrationModalLabel">Adaugă Integrare Nouă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="integrationForm" action="process_integrari.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="integrationAction" name="action" value="add">
                    <input type="hidden" id="integrationId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modalNumeIntegrare" class="form-label">Nume Integrare:</label>
                            <input type="text" class="form-control" id="modalNumeIntegrare" name="nume_integrare" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modalTipIntegrare" class="form-label">Tip Integrare:</label>
                            <select class="form-select" id="modalTipIntegrare" name="tip_integrare" required>
                                <option value="">Selectează Tipul</option>
                                <?php foreach ($tipuri_integrare as $tip): ?>
                                    <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="modalDescriere" class="form-label">Descriere:</label>
                            <textarea class="form-control" id="modalDescriere" name="descriere" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="modalStatus" class="form-label">Status:</label>
                            <select class="form-select" id="modalStatus" name="status" required>
                                <?php foreach ($statusuri_integrare as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalLastTestedAt" class="form-label">Ultima Testare:</label>
                            <input type="text" class="form-control" id="modalLastTestedAt" name="last_tested_at" readonly>
                        </div>

                        <!-- Câmpuri dinamice pentru configurații specifice -->
                        <div class="col-12" id="dynamicConfigFields">
                            <!-- Câmpurile specifice tipului de integrare vor fi injectate aici de JS -->
                            <h5 class="card-title mt-4">Detalii Configurare</h5>
                            <hr>
                            <div id="gpsFields" style="display:none;">
                                <div class="mb-3">
                                    <label for="gpsApiUrl" class="form-label">URL API GPS:</label>
                                    <input type="url" class="form-control" id="gpsApiUrl" name="config_json[gps_api_url]" placeholder="https://api.gpsprovider.com">
                                </div>
                                <div class="mb-3">
                                    <label for="gpsApiKey" class="form-label">Cheie API GPS:</label>
                                    <input type="text" class="form-control" id="gpsApiKey" name="config_json[gps_api_key]">
                                </div>
                            </div>
                            <div id="erpFields" style="display:none;">
                                <div class="mb-3">
                                    <label for="erpApiUrl" class="form-label">URL API ERP:</label>
                                    <input type="url" class="form-control" id="erpApiUrl" name="config_json[erp_api_url]" placeholder="https://api.erp.com">
                                </div>
                                <div class="mb-3">
                                    <label for="erpUsername" class="form-label">Utilizator ERP:</label>
                                    <input type="text" class="form-control" id="erpUsername" name="config_json[erp_username]">
                                </div>
                                <div class="mb-3">
                                    <label for="erpPassword" class="form-label">Parolă ERP:</label>
                                    <input type="password" class="form-control" id="erpPassword" name="config_json[erp_password]">
                                </div>
                            </div>
                            <div id="facturareFields" style="display:none;">
                                <div class="mb-3">
                                    <label for="facturareProvider" class="form-label">Furnizor Facturare (ex: SmartBill):</label>
                                    <input type="text" class="form-control" id="facturareProvider" name="config_json[facturare_provider]">
                                </div>
                                <div class="mb-3">
                                    <label for="facturareApiKey" class="form-label">Cheie API Facturare:</label>
                                    <input type="text" class="form-control" id="facturareApiKey" name="config_json[facturare_api_key]">
                                </div>
                            </div>
                             <div id="alteleFields" style="display:none;">
                                <div class="mb-3">
                                    <label for="alteleCustomConfig" class="form-label">Configurare Personalizată (JSON):</label>
                                    <textarea class="form-control" id="alteleCustomConfig" name="config_json[altele_custom_config]" rows="4" placeholder='{"key1": "value1", "key2": "value2"}'></textarea>
                                    <small class="form-text text-muted">Introduceți configurația în format JSON valid.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Integrare</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmare Ștergere Integrare -->
<div class="modal fade" id="deleteIntegrationModal" tabindex="-1" aria-labelledby="deleteIntegrationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteIntegrationModalLabel">Confirmă Ștergerea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi integrarea <strong id="deleteIntegrationName"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteIntegrationId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteIntegrationBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEditIntegrationModal = document.getElementById('addEditIntegrationModal');
    const integrationForm = document.getElementById('integrationForm');
    const addIntegrationBtn = document.getElementById('addIntegrationBtn');
    const deleteIntegrationModal = document.getElementById('deleteIntegrationModal');
    const confirmDeleteIntegrationBtn = document.getElementById('confirmDeleteIntegrationBtn');
    const integrationsTableBody = document.getElementById('integrationsTableBody');

    const modalTipIntegrare = document.getElementById('modalTipIntegrare');
    const dynamicConfigFields = document.getElementById('dynamicConfigFields');
    const gpsFields = document.getElementById('gpsFields');
    const erpFields = document.getElementById('erpFields');
    const facturareFields = document.getElementById('facturareFields');
    const alteleFields = document.getElementById('alteleFields');

    // Funcție pentru a afișa/ascunde câmpurile dinamice
    function toggleDynamicFields() {
        const selectedType = modalTipIntegrare.value;
        gpsFields.style.display = 'none';
        erpFields.style.display = 'none';
        facturareFields.style.display = 'none';
        alteleFields.style.display = 'none';

        // Resetează atributele 'name' pentru a nu trimite câmpuri goale nefolosite
        Array.from(dynamicConfigFields.querySelectorAll('input, textarea')).forEach(input => {
            input.removeAttribute('name');
        });

        switch (selectedType) {
            case 'GPS':
                gpsFields.style.display = 'block';
                gpsFields.querySelectorAll('input').forEach(input => input.setAttribute('name', input.id.replace('gps', 'config_json[gps_')));
                break;
            case 'ERP':
                erpFields.style.display = 'block';
                erpFields.querySelectorAll('input').forEach(input => input.setAttribute('name', input.id.replace('erp', 'config_json[erp_')));
                break;
            case 'Facturare':
                facturareFields.style.display = 'block';
                facturareFields.querySelectorAll('input').forEach(input => input.setAttribute('name', input.id.replace('facturare', 'config_json[facturare_')));
                break;
            case 'Altele':
                alteleFields.style.display = 'block';
                alteleFields.querySelectorAll('textarea').forEach(input => input.setAttribute('name', input.id.replace('altele', 'config_json[altele_')));
                break;
        }
    }

    modalTipIntegrare.addEventListener('change', toggleDynamicFields);


    // Filtrare
    const filterType = document.getElementById('filterType');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');

    function filterTable() {
        const selectedType = filterType.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#integrationsTableBody tr').forEach(row => {
            const rowType = row.getAttribute('data-tip-integrare');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const typeMatch = (selectedType === 'all' || rowType === selectedType);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (typeMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterType.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);


    // Deschide modalul pentru adăugare
    addIntegrationBtn.addEventListener('click', function() {
        integrationForm.reset();
        document.getElementById('integrationAction').value = 'add';
        document.getElementById('integrationId').value = '';
        document.getElementById('addEditIntegrationModalLabel').textContent = 'Adaugă Integrare Nouă';
        document.getElementById('modalLastTestedAt').value = 'N/A';
        toggleDynamicFields(); // Ascunde toate câmpurile dinamice inițial
    });

    // Deschide modalul pentru editare
    integrationsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-integration-btn')) {
            const row = e.target.closest('tr');
            document.getElementById('integrationAction').value = 'edit';
            document.getElementById('integrationId').value = row.getAttribute('data-id');
            document.getElementById('addEditIntegrationModalLabel').textContent = 'Editează Integrare';

            document.getElementById('modalNumeIntegrare').value = row.getAttribute('data-nume-integrare');
            document.getElementById('modalTipIntegrare').value = row.getAttribute('data-tip-integrare');
            document.getElementById('modalDescriere').value = row.getAttribute('data-descriere');
            document.getElementById('modalStatus').value = row.getAttribute('data-status');
            document.getElementById('modalLastTestedAt').value = row.getAttribute('data-last-tested-at');
            
            // Populează câmpurile dinamice
            const configJson = JSON.parse(row.getAttribute('data-config-json') || '{}');
            modalTipIntegrare.value = row.getAttribute('data-tip-integrare'); // Setează tipul înainte de a apela toggle
            toggleDynamicFields(); // Afișează câmpurile corecte

            if (configJson) {
                switch (modalTipIntegrare.value) {
                    case 'GPS':
                        document.getElementById('gpsApiUrl').value = configJson.gps_api_url || '';
                        document.getElementById('gpsApiKey').value = configJson.gps_api_key || '';
                        break;
                    case 'ERP':
                        document.getElementById('erpApiUrl').value = configJson.erp_api_url || '';
                        document.getElementById('erpUsername').value = configJson.erp_username || '';
                        document.getElementById('erpPassword').value = configJson.erp_password || '';
                        break;
                    case 'Facturare':
                        document.getElementById('facturareProvider').value = configJson.facturare_provider || '';
                        document.getElementById('facturareApiKey').value = configJson.facturare_api_key || '';
                        break;
                    case 'Altele':
                        document.getElementById('alteleCustomConfig').value = JSON.stringify(configJson.altele_custom_config || {}) || '';
                        break;
                }
            }
        }
    });

    // Trimiterea formularului (Adaugă/Editează)
    integrationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(integrationForm);

        // Colectează datele din câmpurile dinamice într-un obiect JSON
        const configJsonData = {};
        const selectedType = modalTipIntegrare.value;
        switch (selectedType) {
            case 'GPS':
                configJsonData.gps_api_url = document.getElementById('gpsApiUrl').value;
                configJsonData.gps_api_key = document.getElementById('gpsApiKey').value;
                break;
            case 'ERP':
                configJsonData.erp_api_url = document.getElementById('erpApiUrl').value;
                configJsonData.erp_username = document.getElementById('erpUsername').value;
                configJsonData.erp_password = document.getElementById('erpPassword').value;
                break;
            case 'Facturare':
                configJsonData.facturare_provider = document.getElementById('facturareProvider').value;
                configJsonData.facturare_api_key = document.getElementById('facturareApiKey').value;
                break;
            case 'Altele':
                try {
                    configJsonData.altele_custom_config = JSON.parse(document.getElementById('alteleCustomConfig').value || '{}');
                } catch (error) {
                    alert('Configurarea personalizată trebuie să fie un JSON valid.');
                    return;
                }
                break;
        }
        formData.append('config_json', JSON.stringify(configJsonData));

        fetch('process_integrari.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Pentru depanare
            const modalInstance = bootstrap.Modal.getInstance(addEditIntegrationModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload(); // Reîncarcă pagina pentru a vedea modificările
        })
        .catch(error => {
            console.error('Eroare la salvarea integrării:', error);
            alert('A apărut o eroare la salvarea integrării.');
        });
    });

    // Testează Integrarea (Placeholder)
    integrationsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('test-integration-btn')) {
            const integrationId = e.target.getAttribute('data-id');
            const integrationName = e.target.closest('tr').getAttribute('data-nume-integrare');
            
            // Aici ar trebui să faci un apel AJAX către un script PHP care testează integrarea
            // Exemplu: fetch('test_integration.php?id=' + integrationId)
            alert(`Testare integrare "${integrationName}" (ID: ${integrationId}). Funcționalitatea de testare va fi implementată ulterior.`);
            // După testare, poți actualiza coloana 'last_tested_at' și 'status' în DB
            // location.reload();
        }
    });

    // Ștergerea integrării
    integrationsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-integration-btn')) {
            const integrationIdToDelete = e.target.getAttribute('data-id');
            const integrationName = e.target.closest('tr').getAttribute('data-nume-integrare');
            document.getElementById('deleteIntegrationId').value = integrationIdToDelete;
            document.getElementById('deleteIntegrationName').textContent = integrationName;
            const deleteModalInstance = new bootstrap.Modal(deleteIntegrationModal);
            deleteModalInstance.show();
        }
    });

    confirmDeleteIntegrationBtn.addEventListener('click', function() {
        const integrationIdToDelete = document.getElementById('deleteIntegrationId').value;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', integrationIdToDelete);

        fetch('process_integrari.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            const modalInstance = bootstrap.Modal.getInstance(deleteIntegrationModal);
            if (modalInstance) {
                modalInstance.hide();
            }
            location.reload();
        })
        .catch(error => {
            console.error('Eroare la ștergerea integrării:', error);
            alert('A apărut o eroare la ștergerea integrării.');
        });
    });

    // Inițializează câmpurile dinamice la încărcarea paginii
    toggleDynamicFields();
});
</script>
