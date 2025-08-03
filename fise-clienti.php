<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Inițializează variabilele de mesaj
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

$id_client = $_GET['id'] ?? null;
$client_info = null;
$contracte_client = [];
$display_client_details = false; // Flag pentru a decide ce conținut să afișăm

// --- Logica principală: Afișare Detalii Client Specific ---
if ($id_client && is_numeric($id_client)) {
    // Preluăm informațiile clientului
    $stmt_client = $conn->prepare("SELECT * FROM clienti WHERE id = ?");
    if ($stmt_client === false) {
        die("Eroare la pregătirea interogării clientului: " . $conn->error);
    }
    $stmt_client->bind_param("i", $id_client);
    $stmt_client->execute();
    $result_client = $stmt_client->get_result();
    if ($result_client->num_rows > 0) {
        $client_info = $result_client->fetch_assoc();
    } else {
        $error_message = "Clientul nu a fost găsit.";
    }
    $stmt_client->close();

    if ($client_info) {
        // Preluăm contractele pentru acest client
        $stmt_contracte = $conn->prepare("
            SELECT cc.*
            FROM contracte_clienti cc
            WHERE cc.id_client = ?
            ORDER BY cc.data_semnare DESC
        ");
        if ($stmt_contracte) {
            $stmt_contracte->bind_param("i", $id_client);
            $stmt_contracte->execute();
            $result_contracte = $stmt_contracte->get_result();
            while ($row = $result_contracte->fetch_assoc()) {
                $contracte_client[] = $row;
            }
            $stmt_contracte->close();
        }

        $display_client_details = true;
    }
} 

// --- Logica pentru Afișarea Listei de Clienți (dacă nu s-a specificat un ID) ---
$all_clienti = [];
if (!$display_client_details) {
    $stmt_all_clienti = $conn->prepare("SELECT id, nume_companie, persoana_contact, telefon, email FROM clienti ORDER BY nume_companie ASC, persoana_contact ASC"); 
    if ($stmt_all_clienti === false) { 
        die("Eroare la pregătirea interogării pentru toți clienții: " . $conn->error);
    }
    if ($stmt_all_clienti->execute()) { 
        $result_all_clienti = $stmt_all_clienti->get_result();
        while ($row = $result_all_clienti->fetch_assoc()) {
            // Formatează numele clientului pentru afișare în listă
            $display_name = '';
            if (!empty($row['nume_companie']) && !empty($row['persoana_contact'])) {
                $display_name = htmlspecialchars($row['nume_companie'] . ' (' . $row['persoana_contact'] . ')');
            } elseif (!empty($row['nume_companie'])) {
                $display_name = htmlspecialchars($row['nume_companie']);
            } elseif (!empty($row['persoana_contact'])) {
                $display_name = htmlspecialchars($row['persoana_contact']);
            } else {
                $display_name = 'Client ID: ' . $row['id'];
            }
            $row['display_name'] = $display_name;
            $all_clienti[] = $row;
        }
        $stmt_all_clienti->close();
    }
}
$conn->close(); // Închidem conexiunea la baza de date aici, după toate operațiile.

// Tipuri de contracte pentru afișare (dacă sunt necesare în detalii)
$tipuri_contract_clienti_display = ['Servicii', 'Colaborare', 'Vanzare', 'Mentenanță', 'Altele'];
?>

<title>NTS TOUR | Fișe Clienți</title>

<style>
    /* Stiluri generale preluate din temă */
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

    /* Stiluri specifice pentru lista de selecție clienți */
    .client-select-item {
        background-color: #2a3042;
        border-color: rgba(255, 255, 255, 0.1);
        color: #e0e0e0;
        padding: 1rem 1.5rem;
        margin-bottom: 0.75rem;
        border-radius: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
    }
    .client-select-item:hover {
        background-color: #3b435a;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
        cursor: pointer;
    }
    .client-select-item .client-info {
        flex-grow: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .client-select-item .client-info i {
        font-size: 2rem;
        color: #6a90f1;
    }
    .client-select-item .client-info span {
        font-size: 1.15rem;
        font-weight: 500;
    }
    .client-select-item .btn {
        flex-shrink: 0;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
    }
    .client-search-input-group .input-group-text {
        background-color: #34495e;
        border-color: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        border-radius: 0.5rem 0 0 0.5rem;
    }
    .client-search-input-group .form-control {
        background-color: #1a2035;
        color: #e0e0e0;
        border-color: rgba(255, 255, 255, 0.2);
        border-radius: 0 0.5rem 0.5rem 0;
    }
    .client-search-input-group .form-control::placeholder {
        color: #b0b0b0;
        opacity: 0.7;
    }

    /* Stiluri pentru tab-uri */
    .nav-tabs {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .nav-tabs .nav-link {
        color: #e0e0e0;
        border: 1px solid transparent;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
    }
    .nav-tabs .nav-link:hover {
        border-color: rgba(255, 255, 255, 0.2) rgba(255, 255, 255, 0.2) #3b435a;
        background-color: #3b435a;
        color: #ffffff;
    }
    .nav-tabs .nav-link.active {
        color: #ffffff;
        background-color: #3b435a;
        border-color: rgba(255, 255, 255, 0.1) rgba(255, 255, 255, 0.1) #3b435a;
        border-bottom-color: #3b435a; /* Active tab underline */
        font-weight: bold;
    }
    .tab-content {
        padding: 1.5rem 0;
    }

    /* Stiluri pentru liste de detalii (în tab-uri) */
    .detail-list-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
    }
    .detail-list-item:last-child {
        border-bottom: none;
    }
    .detail-list-item span:first-child {
        font-weight: 500;
        color: #b0b0b0;
    }
    .detail-list-item strong {
        color: #ffffff;
    }

    /* Stiluri pentru tabelele din tab-uri */
    .table-in-tab {
        color: #e0e0e0 !important;
        background-color: #2a3042 !important; /* Fundal tabel în tab */
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .table-in-tab th, .table-in-tab td {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .table-in-tab thead th {
        background-color: #3b435a !important; /* Fundal header tabel în tab */
        color: #ffffff !important;
    }
    .table-in-tab tbody tr:hover {
        background-color: #3b435a !important;
    }
    /* Badge-uri pentru tipuri de contract în fișa clientului */
    .badge-tip-servicii { background-color: #0d6efd !important; }
    .badge-tip-colaborare { background-color: #28a745 !important; }
    .badge-tip-vanzare { background-color: #6c757d !important; }
    .badge-tip-mentenanță { background-color: #17a2b8 !important; }
    .badge-tip-altele { background-color: #ffc107 !important; color: #343a40 !important; }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Clienți</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item"><a href="fise-clienti.php">Listă Clienți</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Fișa Clientului</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php if ($display_client_details): // Afisam detaliile clientului specific ?>
                            <h4 class="card-title">Fișa Clientului: <?php echo htmlspecialchars($client_info['nume_companie'] ?? $client_info['persoana_contact']); ?></h4>
                            <p class="text-muted mb-3">Persoană Contact: <strong><?php echo htmlspecialchars($client_info['persoana_contact'] ?? 'N/A'); ?></strong></p>
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

                            <div class="d-flex justify-content-end mb-4">
                                <a href="editeaza-client.php?id=<?php echo $client_info['id']; ?>" class="btn btn-primary me-2"><i class="bx bx-edit"></i> Editează Client</a>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteClientModal" data-id="<?php echo $client_info['id']; ?>" data-nume="<?php echo htmlspecialchars($client_info['nume_companie'] ?? $client_info['persoana_contact']); ?>"><i class="bx bx-trash"></i> Șterge Client</button>
                            </div>

                            <!-- Navigare pe tab-uri -->
                            <ul class="nav nav-tabs mb-3" id="clientDetailsTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">Detalii Generale</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="contracts-tab" data-bs-toggle="tab" data-bs-target="#contracts" type="button" role="tab" aria-controls="contracts" aria-selected="false">Contracte</button>
                                </li>
                            </ul>

                            <!-- Conținutul tab-urilor -->
                            <div class="tab-content" id="clientDetailsTabContent">
                                <!-- Tab: Detalii Generale -->
                                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                                    <div class="detail-list-item">
                                        <span>Nume Companie:</span>
                                        <strong><?php echo htmlspecialchars($client_info['nume_companie'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Persoană Contact:</span>
                                        <strong><?php echo htmlspecialchars($client_info['persoana_contact'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>CUI:</span>
                                        <strong><?php echo htmlspecialchars($client_info['cui'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Nr. Reg. Com.:</span>
                                        <strong><?php echo htmlspecialchars($client_info['nr_reg_com'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Telefon:</span>
                                        <strong><?php echo htmlspecialchars($client_info['telefon'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Email:</span>
                                        <strong><?php echo htmlspecialchars($client_info['email'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Adresă:</span>
                                        <strong><?php echo htmlspecialchars($client_info['adresa'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Observații:</span>
                                        <strong><?php echo htmlspecialchars($client_info['observatii'] ?? 'N/A'); ?></strong>
                                    </div>
                                </div>

                                <!-- Tab: Contracte -->
                                <div class="tab-pane fade" id="contracts" role="tabpanel" aria-labelledby="contracts-tab">
                                    <?php if (empty($contracte_client)): ?>
                                        <div class="alert alert-info">Nu există contracte înregistrate pentru acest client.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-in-tab">
                                                <thead>
                                                    <tr>
                                                        <th>Tip Contract</th>
                                                        <th>Număr Contract</th>
                                                        <th>Dată Semnare</th>
                                                        <th>Dată Început</th>
                                                        <th>Dată Sfârșit</th>
                                                        <th>Valoare</th>
                                                        <th>Acțiuni</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($contracte_client as $contract): ?>
                                                        <tr>
                                                            <td><span class="badge badge-tip-<?php echo strtolower(str_replace(' ', '_', $contract['tip_contract'])); ?>"><?php echo htmlspecialchars($contract['tip_contract']); ?></span></td>
                                                            <td><?php echo htmlspecialchars($contract['numar_contract']); ?></td>
                                                            <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($contract['data_semnare']))); ?></td>
                                                            <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($contract['data_inceput']))); ?></td>
                                                            <td><?php echo htmlspecialchars($contract['data_sfarsit'] ? date('d.m.Y', strtotime($contract['data_sfarsit'])) : 'Nedeterminată'); ?></td>
                                                            <td><?php echo htmlspecialchars(number_format($contract['valoare'] ?? 0, 2, ',', '.')) . ' ' . htmlspecialchars($contract['moneda'] ?? 'RON'); ?></td>
                                                            <td>
                                                                <?php if (!empty($contract['cale_fisier'])): ?>
                                                                    <a href="download_document.php?id=<?php echo $contract['id']; ?>&type=contract_client" class="btn btn-sm btn-outline-info mb-1 w-100"><i class="bx bx-download"></i> Descarcă</a>
                                                                <?php endif; ?>
                                                                <a href="contracte-clienti.php?id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-outline-primary mb-1 w-100">Editează</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else: // Afisam lista de clienți pentru selectie ?>
                            <h4 class="card-title">Selectează un Client pentru a Vizualiza Fișa Individuală</h4>
                            <hr>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($all_clienti)): ?>
                                <p>Te rog să alegi un client din lista de mai jos:</p>
                                
                                <!-- Bara de căutare pentru clienți -->
                                <div class="input-group mb-3 client-search-input-group">
                                    <span class="input-group-text"><i class="bx bx-search" style="font-size: 1.2rem; color: #ffffff;"></i></span>
                                    <input type="text" class="form-control" id="clientSearchInput" placeholder="Caută client după nume companie, persoană contact, email...">
                                </div>

                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="clientListContainer">
                                    <?php foreach ($all_clienti as $client): ?>
                                        <div class="col client-select-card-col" data-search="<?php echo strtolower(htmlspecialchars($client['nume_companie'] . ' ' . ($client['persoana_contact'] ?? '') . ' ' . ($client['telefon'] ?? '') . ' ' . ($client['email'] ?? ''))); ?>">
                                            <div class="card h-100 client-select-card">
                                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                                                    <div class="client-icon-container">
                                                        <i class="bx bxs-user-detail"></i>
                                                    </div>
                                                    <h5 class="card-title mb-1"><?php echo $client['display_name']; ?></h5>
                                                    <p class="card-text text-muted mb-1">Telefon: <strong><?php echo htmlspecialchars($client['telefon'] ?? 'N/A'); ?></strong></p>
                                                    <p class="card-text text-secondary mb-3">Email: <strong><?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></strong></p>
                                                    <a href="fise-clienti.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary mt-auto">Vezi Fișa</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div id="noClientsFound" class="alert alert-info mt-3" style="display: none;">Nu au fost găsiți clienți care să corespundă căutării.</div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">Nu există clienți înregistrați în sistem. <a href="adauga-client.php" class="alert-link">Adaugă un client nou.</a></div>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal Confirmare Ștergere Client (similar cu angajați) -->
<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteClientModalLabel">Confirmă Ștergerea Clientului</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi clientul <strong id="clientToDeleteName"></strong>? Această acțiune este ireversibilă și va șterge toate contractele asociate!
                <input type="hidden" id="clientToDeleteId">
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
    // Logica pentru filtrarea clienților pe pagina de selecție
    const clientSearchInput = document.getElementById('clientSearchInput');
    const clientListContainer = document.getElementById('clientListContainer');
    const noClientsFoundMessage = document.getElementById('noClientsFound');

    if (clientSearchInput && clientListContainer) {
        function filterClientSelection() {
            const searchText = clientSearchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            document.querySelectorAll('.client-select-card-col').forEach(item => {
                const searchData = item.getAttribute('data-search');
                if (searchData.includes(searchText)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (noClientsFoundMessage) {
                noClientsFoundMessage.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        clientSearchInput.addEventListener('input', filterClientSelection);
        filterClientSelection(); // Rulează la încărcarea paginii
    }

    // Logica pentru modalul de ștergere client (dacă este afișat)
    const deleteClientModal = document.getElementById('deleteClientModal');
    const confirmDeleteClientBtn = document.getElementById('confirmDeleteClientBtn');

    if (deleteClientModal && confirmDeleteClientBtn) {
        deleteClientModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Butonul care a declanșat modalul
            const clientId = button.getAttribute('data-id');
            const clientName = button.getAttribute('data-nume');

            const clientToDeleteIdInput = deleteClientModal.querySelector('#clientToDeleteId');
            const clientToDeleteNameSpan = deleteClientModal.querySelector('#clientToDeleteName');

            if (clientToDeleteIdInput) clientToDeleteIdInput.value = clientId;
            if (clientToDeleteNameSpan) clientToDeleteNameSpan.textContent = clientName;
        });

        confirmDeleteClientBtn.addEventListener('click', function() {
            const clientIdToDelete = document.getElementById('clientToDeleteId').value;
            
            // Trimite cererea de ștergere către process_clienti.php
            fetch('process_clienti.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete&id=' + clientIdToDelete
            })
            .then(response => response.text()) // Poate fi JSON sau text simplu
            .then(data => {
                console.log(data); // Pentru depanare
                const modalInstance = bootstrap.Modal.getInstance(deleteClientModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                location.reload(); // Reîncarcă pagina pentru a vedea modificările
            })
            .catch(error => {
                console.error('Eroare la ștergerea clientului:', error);
                alert('A apărut o eroare la ștergerea clientului.'); // Folosește alert temporar pentru depanare
            });
        });
    }
});
</script>
