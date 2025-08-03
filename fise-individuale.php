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

$id_angajat = $_GET['id'] ?? null;
$angajat_info = null;
$alocari_angajat = [];
$contracte_angajat = [];
$display_employee_details = false; // Flag pentru a decide ce conținut să afișăm

// --- Logica principală: Afișare Detalii Angajat Specific ---
if ($id_angajat && is_numeric($id_angajat)) {
    // Preluăm informațiile angajatului
    $stmt_angajat = $conn->prepare("SELECT * FROM angajati WHERE id = ?");
    if ($stmt_angajat === false) {
        die("Eroare la pregătirea interogării angajatului: " . $conn->error);
    }
    $stmt_angajat->bind_param("i", $id_angajat);
    $stmt_angajat->execute();
    $result_angajat = $stmt_angajat->get_result();
    if ($result_angajat->num_rows > 0) {
        $angajat_info = $result_angajat->fetch_assoc();
    } else {
        $error_message = "Angajatul nu a fost găsit.";
    }
    $stmt_angajat->close();

    if ($angajat_info) {
        // Preluăm alocările de vehicule pentru acest angajat
        // CORECTAT: Folosim 'data_inceput' conform structurii tabelei tale
        $stmt_alocari = $conn->prepare("
            SELECT avs.*, v.model, v.numar_inmatriculare, v.tip as tip_vehicul
            FROM alocari_vehicule_soferi avs
            JOIN vehicule v ON avs.id_vehicul = v.id
            WHERE avs.id_sofer = ?
            ORDER BY avs.data_inceput DESC
        ");
        if ($stmt_alocari) {
            $stmt_alocari->bind_param("i", $id_angajat);
            $stmt_alocari->execute();
            $result_alocari = $stmt_alocari->get_result();
            while ($row = $result_alocari->fetch_assoc()) {
                $alocari_angajat[] = $row;
            }
            $stmt_alocari->close();
        }

        // Preluăm contractele pentru acest angajat
        $stmt_contracte = $conn->prepare("
            SELECT ca.*
            FROM contracte_angajati ca
            WHERE ca.id_angajat = ?
            ORDER BY ca.data_semnare DESC
        ");
        if ($stmt_contracte) {
            $stmt_contracte->bind_param("i", $id_angajat);
            $stmt_contracte->execute();
            $result_contracte = $stmt_contracte->get_result();
            while ($row = $result_contracte->fetch_assoc()) {
                $contracte_angajat[] = $row;
            }
            $stmt_contracte->close();
        }

        $display_employee_details = true;
    }
} 

// --- Logica pentru Afișarea Listei de Angajați (dacă nu s-a specificat un ID) ---
$all_angajati = [];
if (!$display_employee_details) {
    $stmt_all_angajati = $conn->prepare("SELECT id, nume, prenume, cod_intern, functie, telefon, email FROM angajati ORDER BY nume ASC, prenume ASC"); 
    if ($stmt_all_angajati === false) { 
        die("Eroare la pregătirea interogării pentru toți angajații: " . $conn->error);
    }
    if ($stmt_all_angajati->execute()) { 
        $result_all_angajati = $stmt_all_angajati->get_result();
        while ($row = $result_all_angajati->fetch_assoc()) {
            $all_angajati[] = $row;
        }
        $stmt_all_angajati->close();
    }
}
$conn->close(); // Închidem conexiunea la baza de date aici, după toate operațiile.

?>

<title>NTS TOUR | Fișe Individuale</title>

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

    /* Stiluri specifice pentru lista de angajați (selecție) */
    .employee-select-item {
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
    .employee-select-item:hover {
        background-color: #3b435a;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
        cursor: pointer;
    }
    .employee-select-item .employee-info {
        flex-grow: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .employee-select-item .employee-info i {
        font-size: 2rem;
        color: #6a90f1;
    }
    .employee-select-item .employee-info span {
        font-size: 1.15rem;
        font-weight: 500;
    }
    .employee-select-item .btn {
        flex-shrink: 0;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
    }
    .employee-search-input-group .input-group-text {
        background-color: #34495e;
        border-color: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        border-radius: 0.5rem 0 0 0.5rem;
    }
    .employee-search-input-group .form-control {
        background-color: #1a2035;
        color: #e0e0e0;
        border-color: rgba(255, 255, 255, 0.2);
        border-radius: 0 0.5rem 0.5rem 0;
    }
    .employee-search-input-group .form-control::placeholder {
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
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Fișe Individuale</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php if ($display_employee_details): // Afisam detaliile angajatului specific ?>
                            <h4 class="card-title">Fișa Angajatului: <?php echo htmlspecialchars($angajat_info['nume'] . ' ' . $angajat_info['prenume']); ?></h4>
                            <p class="text-muted mb-3">Cod Intern: <strong><?php echo htmlspecialchars($angajat_info['cod_intern']); ?></strong> | Funcție: <strong><?php echo htmlspecialchars($angajat_info['functie']); ?></strong></p>
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
                                <a href="editeaza-angajat.php?id=<?php echo $angajat_info['id']; ?>" class="btn btn-primary me-2"><i class="bx bx-edit"></i> Editează Angajat</a>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteEmployeeModal" data-id="<?php echo $angajat_info['id']; ?>" data-nume="<?php echo htmlspecialchars($angajat_info['nume'] . ' ' . $angajat_info['prenume']); ?>"><i class="bx bx-trash"></i> Șterge Angajat</button>
                            </div>

                            <!-- Navigare pe tab-uri -->
                            <ul class="nav nav-tabs mb-3" id="employeeDetailsTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">Detalii Personale & Angajare</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="allocations-tab" data-bs-toggle="tab" data-bs-target="#allocations" type="button" role="tab" aria-controls="allocations" aria-selected="false">Alocări Vehicule</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="contracts-tab" data-bs-toggle="tab" data-bs-target="#contracts" type="button" role="tab" aria-controls="contracts" aria-selected="false">Contracte</button>
                                </li>
                            </ul>

                            <!-- Conținutul tab-urilor -->
                            <div class="tab-content" id="employeeDetailsTabContent">
                                <!-- Tab: Detalii Personale & Angajare -->
                                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                                    <div class="detail-list-item">
                                        <span>Nume Complet:</span>
                                        <strong><?php echo htmlspecialchars($angajat_info['nume'] . ' ' . $angajat_info['prenume']); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Cod Intern:</span>
                                        <strong><?php echo htmlspecialchars($angajat_info['cod_intern']); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Dată Angajare:</span>
                                        <strong><?php echo htmlspecialchars(date('d.m.Y', strtotime($angajat_info['data_angajare']))); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Funcție:</span>
                                        <strong><?php htmlspecialchars($angajat_info['functie']); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Telefon:</span>
                                        <strong><?php htmlspecialchars($angajat_info['telefon'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Email:</span>
                                        <strong><?php htmlspecialchars($angajat_info['email'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Adresă:</span>
                                        <strong><?php htmlspecialchars($angajat_info['adresa'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Salariu Brut:</span>
                                        <strong><?php htmlspecialchars(number_format($angajat_info['salariu'] ?? 0, 2, ',', '.')) . ' RON'; ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Status:</span>
                                        <strong><?php htmlspecialchars($angajat_info['status']); ?></strong>
                                    </div>
                                    <div class="detail-list-item">
                                        <span>Observații:</span>
                                        <strong><?php htmlspecialchars($angajat_info['observatii'] ?? 'N/A'); ?></strong>
                                    </div>
                                </div>

                                <!-- Tab: Alocări Vehicule -->
                                <div class="tab-pane fade" id="allocations" role="tabpanel" aria-labelledby="allocations-tab">
                                    <?php if (empty($alocari_angajat)): ?>
                                        <div class="alert alert-info">Nu există alocări de vehicule pentru acest angajat.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-in-tab">
                                                <thead>
                                                    <tr>
                                                        <th>Vehicul</th>
                                                        <th>Tip Vehicul</th>
                                                        <th>Dată Alocare</th>
                                                        <th>Dată Returnare</th>
                                                        <th>Acțiuni</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($alocari_angajat as $alocare): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($alocare['model'] . ' (' . $alocare['numar_inmatriculare'] . ')'); ?></td>
                                                            <td><?php echo htmlspecialchars($alocare['tip_vehicul'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($alocare['data_inceput']))); ?></td>
                                                            <td><?php echo htmlspecialchars($alocare['data_sfarsit'] ? date('d.m.Y', strtotime($alocare['data_sfarsit'])) : 'Curentă'); ?></td>
                                                            <td>
                                                                <a href="alocare-vehicul-sofer.php?id=<?php echo $alocare['id']; ?>" class="btn btn-sm btn-outline-info">Vezi/Editează</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab: Contracte -->
                                <div class="tab-pane fade" id="contracts" role="tabpanel" aria-labelledby="contracts-tab">
                                    <?php if (empty($contracte_angajat)): ?>
                                        <div class="alert alert-info">Nu există contracte înregistrate pentru acest angajat.</div>
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
                                                        <th>Acțiuni</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($contracte_angajat as $contract): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($contract['tip_contract']); ?></td>
                                                            <td><?php echo htmlspecialchars($contract['numar_contract']); ?></td>
                                                            <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($contract['data_semnare']))); ?></td>
                                                            <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($contract['data_inceput']))); ?></td>
                                                            <td><?php echo htmlspecialchars($contract['data_sfarsit'] ? date('d.m.Y', strtotime($contract['data_sfarsit'])) : 'Nedeterminată'); ?></td>
                                                            <td>
                                                                <a href="contracte-angajati.php?id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-outline-info">Vezi/Editează</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else: // Afisam lista de angajați pentru selectie ?>
                            <h4 class="card-title">Selectează un Angajat pentru a Vizualiza Fișa Individuală</h4>
                            <hr>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($all_angajati)): ?>
                                <p>Te rog să alegi un angajat din lista de mai jos:</p>
                                
                                <!-- Bara de căutare pentru angajați -->
                                <div class="input-group mb-3 employee-search-input-group">
                                    <span class="input-group-text"><i class="bx bx-search" style="font-size: 1.2rem; color: #ffffff;"></i></span>
                                    <input type="text" class="form-control" id="employeeSearchInput" placeholder="Caută angajat după nume, cod, funcție...">
                                </div>

                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="employeeListContainer">
                                    <?php foreach ($all_angajati as $angajat): ?>
                                        <div class="col employee-select-card-col" data-search="<?php echo strtolower(htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume'] . ' ' . $angajat['cod_intern'] . ' ' . $angajat['functie'] . ' ' . ($angajat['telefon'] ?? '') . ' ' . ($angajat['email'] ?? ''))); ?>">
                                            <div class="card h-100 employee-select-card">
                                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                                                    <div class="employee-icon-container">
                                                        <i class="bx bxs-user-detail"></i>
                                                    </div>
                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></h5>
                                                    <p class="card-text text-muted mb-1">Cod: <strong><?php echo htmlspecialchars($angajat['cod_intern']); ?></strong></p>
                                                    <p class="card-text text-secondary mb-3">Funcție: <strong><?php echo htmlspecialchars($angajat['functie']); ?></strong></p>
                                                    <a href="fise-individuale.php?id=<?php echo $angajat['id']; ?>" class="btn btn-sm btn-primary mt-auto">Vezi Fișa</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div id="noEmployeesFound" class="alert alert-info mt-3" style="display: none;">Nu au fost găsiți angajați care să corespundă căutării.</div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">Nu există angajați înregistrați în sistem. <a href="adauga-angajat.php" class="alert-link">Adaugă un angajat nou.</a></div>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal Confirmare Ștergere Angajat (reutilizat de la lista-angajati.php) -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEmployeeModalLabel">Confirmă Ștergerea Angajatului</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi angajatul <strong id="employeeToDeleteName"></strong>? Această acțiune este ireversibilă și va șterge toate alocările și contractele asociate!
                <input type="hidden" id="employeeToDeleteId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteEmployeeBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logica pentru filtrarea angajaților pe pagina de selecție
    const employeeSearchInput = document.getElementById('employeeSearchInput');
    const employeeListContainer = document.getElementById('employeeListContainer');
    const noEmployeesFoundMessage = document.getElementById('noEmployeesFound');

    if (employeeSearchInput && employeeListContainer) {
        function filterEmployeeSelection() {
            const searchText = employeeSearchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            document.querySelectorAll('.employee-select-card-col').forEach(item => {
                const searchData = item.getAttribute('data-search');
                if (searchData.includes(searchText)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (noEmployeesFoundMessage) {
                noEmployeesFoundMessage.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        employeeSearchInput.addEventListener('input', filterEmployeeSelection);
        filterEmployeeSelection(); // Rulează la încărcarea paginii
    }

    // Logica pentru modalul de ștergere angajat (dacă este afișat)
    const deleteEmployeeModal = document.getElementById('deleteEmployeeModal');
    const confirmDeleteEmployeeBtn = document.getElementById('confirmDeleteEmployeeBtn');

    if (deleteEmployeeModal && confirmDeleteEmployeeBtn) {
        deleteEmployeeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Butonul care a declanșat modalul
            const employeeId = button.getAttribute('data-id');
            const employeeName = button.getAttribute('data-nume');

            const employeeToDeleteIdInput = deleteEmployeeModal.querySelector('#employeeToDeleteId');
            const employeeToDeleteNameSpan = deleteEmployeeModal.querySelector('#employeeToDeleteName');

            if (employeeToDeleteIdInput) employeeToDeleteIdInput.value = employeeId;
            if (employeeToDeleteNameSpan) employeeToDeleteNameSpan.textContent = employeeName;
        });

        confirmDeleteEmployeeBtn.addEventListener('click', function() {
            const employeeIdToDelete = document.getElementById('employeeToDeleteId').value;
            
            // Trimite cererea de ștergere către process_angajati.php
            fetch('process_angajati.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete&id=' + employeeIdToDelete
            })
            .then(response => response.text())
            .then(data => {
                console.log(data); // Pentru depanare
                const modalInstance = bootstrap.Modal.getInstance(deleteEmployeeModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                location.reload(); // Reîncarcă pagina pentru a vedea modificările
            })
            .catch(error => {
                console.error('Eroare la ștergerea angajatului:', error);
                alert('A apărut o eroare la ștergerea angajatului.');
            });
        });
    }

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
