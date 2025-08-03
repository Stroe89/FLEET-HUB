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

// Funcție pentru a verifica existența unei coloane într-un tabel
function columnExists($conn, $tableName, $columnName) {
    if (!tableExists($conn, $tableName)) {
        return false;
    }
    $result = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($tableName) . "` LIKE '" . $conn->real_escape_string($columnName) . "'");
    return $result && $result->num_rows > 0;
}


// --- Preluare Date pentru Tabele și Dropdown-uri ---
$all_subscribers = [];
if (tableExists($conn, 'abonati_newsletter')) {
    $sql_subscribers = "SELECT id, email, nume, prenume, data_abonare, status, sursa_abonare FROM abonati_newsletter ORDER BY data_abonare DESC";
    $result_subscribers = $conn->query($sql_subscribers);
    if ($result_subscribers) {
        while ($row = $result_subscribers->fetch_assoc()) {
            $all_subscribers[] = $row;
        }
    }
} else {
    // Date mock dacă tabelul nu există
    $all_subscribers = [
        ['id' => 1, 'email' => 'manual.client1@example.com', 'nume' => 'Ion', 'prenume' => 'Popescu', 'data_abonare' => '2025-01-15 10:00:00', 'status' => 'activ', 'sursa_abonare' => 'manual'],
        ['id' => 2, 'email' => 'client.firma@example.com', 'nume' => 'SC Transport SRL', 'prenume' => '', 'data_abonare' => '2025-02-20 14:30:00', 'status' => 'activ', 'sursa_abonare' => 'client'],
        ['id' => 3, 'email' => 'angajat.sofer@example.com', 'nume' => 'Gheorghe', 'prenume' => 'Vasilescu', 'data_abonare' => '2025-03-01 09:00:00', 'status' => 'activ', 'sursa_abonare' => 'angajat'],
        ['id' => 4, 'email' => 'potential.client@example.com', 'nume' => 'Ana', 'prenume' => 'Maria', 'data_abonare' => '2025-04-05 16:00:00', 'status' => 'inactiv', 'sursa_abonare' => 'manual'],
    ];
}

$all_clients = [];
if (tableExists($conn, 'clienti') && columnExists($conn, 'clienti', 'email_contact')) {
    $sql_clients = "SELECT id, nume_companie, email_contact FROM clienti WHERE email_contact IS NOT NULL AND email_contact != '' ORDER BY nume_companie ASC";
    $result_clients = $conn->query($sql_clients);
    if ($result_clients) {
        while ($row = $result_clients->fetch_assoc()) {
            $all_clients[] = $row;
        }
    }
} else {
    // Date mock dacă tabelul sau coloana nu există
    $all_clients = [
        ['id' => 1, 'nume_companie' => 'Global Logistics SA', 'email_contact' => 'contact@globallogistics.com'],
        ['id' => 2, 'nume_companie' => 'Rapid Transport SRL', 'email_contact' => 'office@rapidtransport.ro'],
    ];
}

$all_employees = [];
if (tableExists($conn, 'angajati') && columnExists($conn, 'angajati', 'email')) {
    $sql_employees = "SELECT id, nume, prenume, email FROM angajati WHERE email IS NOT NULL AND email != '' ORDER BY nume ASC, prenume ASC";
    $result_employees = $conn->query($sql_employees);
    if ($result_employees) {
        while ($row = $result_employees->fetch_assoc()) {
            $all_employees[] = $row;
        }
    }
} else {
    // Date mock dacă tabelul sau coloana nu există
    $all_employees = [
        ['id' => 101, 'nume' => 'Popescu', 'prenume' => 'Mihai', 'email' => 'mihai.popescu@ntstour.ro'],
        ['id' => 102, 'nume' => 'Georgescu', 'prenume' => 'Elena', 'email' => 'elena.georgescu@ntstour.ro'],
    ];
}

$conn->close(); // Închidem conexiunea la baza de date

// Categorii de filtrare pentru abonați
$subscriber_statuses = ['activ', 'inactiv', 'dezabonat'];
$subscriber_sources = ['manual', 'client', 'angajat', 'website', 'import', 'eveniment']; // Extindeți după nevoi
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Listă Abonați Newsletter</title>

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
    /* Badge-uri pentru statusuri abonați */
    .badge-status-activ { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-inactiv { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge-status-dezabonat { background-color: #dc3545 !important; color: #fff !important; }

    /* Stiluri pentru butoane */
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        font-weight: bold !important;
        padding: 0.75rem 1.5rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover, .btn-outline-primary:hover, .btn-outline-danger:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
    }
    .btn-primary { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; }
    .btn-info { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: #fff !important; }
    .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #343a40 !important; }
    .btn-success { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }

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
    /* Stiluri pentru butoanele de acțiune din tabel */
    .table .btn-sm {
        padding: 0.3rem 0.6rem !important; /* Mărește puțin butoanele mici */
        font-size: 0.8rem !important;
        width: auto !important; /* Anulează width: 100% de pe mobil */
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Newsletter</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Listă Abonați</li>
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
                        <h4 class="card-title">Gestionare Abonați Newsletter</h4>
                        <p class="text-muted">Vizualizează, filtrează și gestionează abonații tăi la newsletter.</p>
                        <hr>

                        <!-- Secțiunea de Filtrare și Căutare -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($subscriber_statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSource" class="form-label">Filtrează după Sursă:</label>
                                <select class="form-select" id="filterSource">
                                    <option value="all">Toate Sursele</option>
                                    <?php foreach ($subscriber_sources as $source): ?>
                                        <option value="<?php echo htmlspecialchars($source); ?>"><?php echo htmlspecialchars(ucfirst($source)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="searchSubscriber" class="form-label">Căutare:</label>
                                <input type="text" class="form-control" id="searchSubscriber" placeholder="Căutare după email, nume, prenume...">
                            </div>
                        </div>

                        <!-- Butoane de Acțiuni -->
                        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubscriberModal"><i class="bx bx-user-plus me-2"></i>Adaugă Manual</button>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addFromClientsModal"><i class="bx bx-group me-2"></i>Adaugă din Clienți</button>
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addFromEmployeesModal"><i class="bx bx-user-pin me-2"></i>Adaugă din Angajați</button>
                            <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel me-2"></i>Export Excel</button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="bx bxs-file-pdf me-2"></i>Export PDF</button>
                            <button type="button" class="btn btn-info" id="printListBtn"><i class="bx bx-printer me-2"></i>Printează</button>
                        </div>

                        <!-- Tabelul cu Abonați -->
                        <?php if (empty($all_subscribers)): ?>
                            <div class="alert alert-info">Nu există abonați înregistrați.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="subscribersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Email</th>
                                            <th>Nume</th>
                                            <th>Prenume</th>
                                            <th>Dată Abonare</th>
                                            <th>Sursă</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="subscribersTableBody">
                                        <?php foreach ($all_subscribers as $subscriber): ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($subscriber['id']); ?>"
                                                data-email="<?php echo htmlspecialchars($subscriber['email']); ?>"
                                                data-nume="<?php echo htmlspecialchars($subscriber['nume'] ?? ''); ?>"
                                                data-prenume="<?php echo htmlspecialchars($subscriber['prenume'] ?? ''); ?>"
                                                data-data-abonare="<?php echo htmlspecialchars($subscriber['data_abonare']); ?>"
                                                data-status="<?php echo htmlspecialchars($subscriber['status']); ?>"
                                                data-sursa="<?php echo htmlspecialchars($subscriber['sursa_abonare'] ?? 'N/A'); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($subscriber['email'] . ' ' . ($subscriber['nume'] ?? '') . ' ' . ($subscriber['prenume'] ?? '') . ' ' . $subscriber['status'] . ' ' . ($subscriber['sursa_abonare'] ?? ''))); ?>"
                                            >
                                                <td data-label="ID:"><?php echo htmlspecialchars($subscriber['id']); ?></td>
                                                <td data-label="Email:"><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                                <td data-label="Nume:"><?php echo htmlspecialchars($subscriber['nume'] ?? 'N/A'); ?></td>
                                                <td data-label="Prenume:"><?php echo htmlspecialchars($subscriber['prenume'] ?? 'N/A'); ?></td>
                                                <td data-label="Dată Abonare:"><?php echo (new DateTime($subscriber['data_abonare']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Sursă:"><?php echo htmlspecialchars(ucfirst($subscriber['sursa_abonare'] ?? 'N/A')); ?></td>
                                                <td data-label="Status:">
                                                    <?php 
                                                        $status_class = 'bg-info';
                                                        if ($subscriber['status'] == 'activ') $status_class = 'bg-success';
                                                        else if ($subscriber['status'] == 'inactiv') $status_class = 'bg-warning text-dark';
                                                        else if ($subscriber['status'] == 'dezabonat') $status_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($subscriber['status'])); ?></span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-2 edit-subscriber-btn" data-id="<?php echo $subscriber['id']; ?>" data-bs-toggle="modal" data-bs-target="#editSubscriberModal"><i class="bx bx-edit"></i> Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-subscriber-btn" data-id="<?php echo $subscriber['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
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

<!-- Modal Adaugă Abonat Manual -->
<div class="modal fade" id="addSubscriberModal" tabindex="-1" aria-labelledby="addSubscriberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubscriberModalLabel">Adaugă Abonat Nou (Manual)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSubscriberForm" action="process_newsletter.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_subscriber">
                    <input type="hidden" name="sursa_abonare" value="manual">
                    <div class="mb-3">
                        <label for="subscriberEmail" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="subscriberEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="subscriberNume" class="form-label">Nume:</label>
                        <input type="text" class="form-control" id="subscriberNume" name="nume">
                    </div>
                    <div class="mb-3">
                        <label for="subscriberPrenume" class="form-label">Prenume:</label>
                        <input type="text" class="form-control" id="subscriberPrenume" name="prenume">
                    </div>
                    <div class="mb-3">
                        <label for="subscriberStatus" class="form-label">Status:</label>
                        <select class="form-select" id="subscriberStatus" name="status">
                            <option value="activ">Activ</option>
                            <option value="inactiv">Inactiv</option>
                            <option value="dezabonat">Dezabonat</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Abonat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editează Abonat -->
<div class="modal fade" id="editSubscriberModal" tabindex="-1" aria-labelledby="editSubscriberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubscriberModalLabel">Editează Abonat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSubscriberForm" action="process_newsletter.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_subscriber">
                    <input type="hidden" name="id" id="editSubscriberId">
                    <div class="mb-3">
                        <label for="editSubscriberEmail" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="editSubscriberEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSubscriberNume" class="form-label">Nume:</label>
                        <input type="text" class="form-control" id="editSubscriberNume" name="nume">
                    </div>
                    <div class="mb-3">
                        <label for="editSubscriberPrenume" class="form-label">Prenume:</label>
                        <input type="text" class="form-control" id="editSubscriberPrenume" name="prenume">
                    </div>
                    <div class="mb-3">
                        <label for="editSubscriberStatus" class="form-label">Status:</label>
                        <select class="form-select" id="editSubscriberStatus" name="status">
                            <option value="activ">Activ</option>
                            <option value="inactiv">Inactiv</option>
                            <option value="dezabonat">Dezabonat</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editSubscriberSursa" class="form-label">Sursă Abonare:</label>
                        <input type="text" class="form-control" id="editSubscriberSursa" name="sursa_abonare" readonly>
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

<!-- Modal Confirmă Ștergere Abonat -->
<div class="modal fade" id="deleteSubscriberModal" tabindex="-1" aria-labelledby="deleteSubscriberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSubscriberModalLabel">Confirmă Ștergerea Abonatului</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi abonatul <strong id="deleteSubscriberEmailDisplay"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteSubscriberIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteSubscriberBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adaugă din Clienți -->
<div class="modal fade" id="addFromClientsModal" tabindex="-1" aria-labelledby="addFromClientsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFromClientsModalLabel">Adaugă Abonați din Clienți</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFromClientsForm" action="process_newsletter.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_subscribers_from_clients">
                    <p class="text-muted">Selectează clienții pe care dorești să-i adaugi ca abonați. Doar clienții cu email valid și care nu sunt deja abonați activi vor fi adăugați.</p>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="searchClients" placeholder="Căutare client după nume sau email...">
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllClients"></th>
                                    <th>ID</th>
                                    <th>Nume Companie</th>
                                    <th>Email Contact</th>
                                </tr>
                            </thead>
                            <tbody id="clientsTableBody">
                                <?php if (empty($all_clients)): ?>
                                    <tr><td colspan="4" class="text-center">Nu există clienți înregistrați.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($all_clients as $client): ?>
                                        <tr data-search-text="<?php echo strtolower(htmlspecialchars($client['nume_companie'] . ' ' . $client['email_contact'])); ?>">
                                            <td><input type="checkbox" name="client_ids[]" value="<?php echo htmlspecialchars($client['id']); ?>" data-email="<?php echo htmlspecialchars($client['email_contact']); ?>" class="client-checkbox"></td>
                                            <td><?php echo htmlspecialchars($client['id']); ?></td>
                                            <td><?php echo htmlspecialchars($client['nume_companie']); ?></td>
                                            <td><?php echo htmlspecialchars($client['email_contact']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Adaugă Clienți Selectați</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Adaugă din Angajați -->
<div class="modal fade" id="addFromEmployeesModal" tabindex="-1" aria-labelledby="addFromEmployeesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFromEmployeesModalLabel">Adaugă Abonați din Angajați</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFromEmployeesForm" action="process_newsletter.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_subscribers_from_employees">
                    <p class="text-muted">Selectează angajații pe care dorești să-i adaugi ca abonați. Doar angajații cu email valid și care nu sunt deja abonați activi vor fi adăugați.</p>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="searchEmployees" placeholder="Căutare angajat după nume sau email...">
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllEmployees"></th>
                                    <th>ID</th>
                                    <th>Nume</th>
                                    <th>Prenume</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody id="employeesTableBody">
                                <?php if (empty($all_employees)): ?>
                                    <tr><td colspan="5" class="text-center">Nu există angajați înregistrați.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($all_employees as $employee): ?>
                                        <tr data-search-text="<?php echo strtolower(htmlspecialchars($employee['nume'] . ' ' . $employee['prenume'] . ' ' . $employee['email'] . ' ' . $employee['functie'])); ?>">
                                            <td><input type="checkbox" name="employee_ids[]" value="<?php echo htmlspecialchars($employee['id']); ?>" data-email="<?php echo htmlspecialchars($employee['email']); ?>" class="employee-checkbox"></td>
                                            <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['nume']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['prenume']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Adaugă Angajați Selectați</button>
                </div>
            </form>
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
    const allSubscribersData = <?php echo json_encode($all_subscribers); ?>;
    const allClientsData = <?php echo json_encode($all_clients); ?>;
    const allEmployeesData = <?php echo json_encode($all_employees); ?>;
    const campaignData = <?php echo json_encode($campaign_data); ?>; // Aceasta este pentru creeaza-newsletter.php, dar o păstrăm pentru context

    // --- Elemente DOM pentru Modale ---
    const addSubscriberModal = document.getElementById('addSubscriberModal');
    const editSubscriberModal = document.getElementById('editSubscriberModal');
    const deleteSubscriberModal = document.getElementById('deleteSubscriberModal');

    const editSubscriberForm = document.getElementById('editSubscriberForm');
    const deleteSubscriberIdConfirm = document.getElementById('deleteSubscriberIdConfirm');
    const deleteSubscriberEmailDisplay = document.getElementById('deleteSubscriberEmailDisplay');
    const confirmDeleteSubscriberBtn = document.getElementById('confirmDeleteSubscriberBtn');

    const addFromClientsModal = document.getElementById('addFromClientsModal');
    const addFromEmployeesModal = document.getElementById('addFromEmployeesModal');

    // --- Funcționalitate Filtrare Tabel Principal Abonați ---
    const filterStatus = document.getElementById('filterStatus');
    const filterSource = document.getElementById('filterSource');
    const searchSubscriber = document.getElementById('searchSubscriber');
    const subscribersTableBody = document.getElementById('subscribersTableBody');

    function filterSubscribersTable() {
        const selectedStatus = filterStatus.value;
        const selectedSource = filterSource.value;
        const searchText = searchSubscriber.value.toLowerCase().trim();

        document.querySelectorAll('#subscribersTableBody tr').forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            const rowSource = row.getAttribute('data-sursa');
            const rowSearchText = row.getAttribute('data-search-text');

            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const sourceMatch = (selectedSource === 'all' || rowSource === selectedSource);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (statusMatch && sourceMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterStatus.addEventListener('change', filterSubscribersTable);
    filterSource.addEventListener('change', filterSubscribersTable);
    searchSubscriber.addEventListener('input', filterSubscribersTable);
    filterSubscribersTable(); // Rulează la încărcarea paginii

    // --- Logică Modale Abonat (Editare / Ștergere) ---

    // Populează modalul de editare abonat
    document.querySelectorAll('.edit-subscriber-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const subscriber = allSubscribersData.find(s => s.id == id);
            if (subscriber) {
                document.getElementById('editSubscriberId').value = subscriber.id;
                document.getElementById('editSubscriberEmail').value = subscriber.email;
                document.getElementById('editSubscriberNume').value = subscriber.nume || '';
                document.getElementById('editSubscriberPrenume').value = subscriber.prenume || '';
                document.getElementById('editSubscriberStatus').value = subscriber.status;
                document.getElementById('editSubscriberSursa').value = subscriber.sursa_abonare || 'N/A';
            }
        });
    });

    // Deschide modalul de confirmare ștergere abonat
    document.querySelectorAll('.delete-subscriber-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentSubscriberIdToDelete = this.dataset.id;
            const subscriberEmail = this.closest('tr').querySelector('td[data-label="Email:"]').textContent;
            deleteSubscriberEmailDisplay.textContent = subscriberEmail;
            new bootstrap.Modal(deleteSubscriberModal).show();
        });
    });

    // Confirmă ștergerea abonatului
    confirmDeleteSubscriberBtn.addEventListener('click', function() {
        if (currentSubscriberIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_subscriber');
            formData.append('id', currentSubscriberIdToDelete);

            fetch('process_newsletter.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message); // Folosim alert Bootstrap sau un mesaj custom
                    location.reload(); 
                } else {
                    alert('Eroare: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea abonatului:', error);
                alert('A apărut o eroare la ștergerea abonatului.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(deleteSubscriberModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
    });

    // --- Logică Modale Adăugare din Clienți/Angajați ---

    // Populează și filtrează lista de clienți în modal
    const searchClientsInput = document.getElementById('searchClients');
    const clientsTableBody = document.getElementById('clientsTableBody');
    const selectAllClientsCheckbox = document.getElementById('selectAllClients');

    function filterClientsList() {
        const searchText = searchClientsInput.value.toLowerCase().trim();
        document.querySelectorAll('#clientsTableBody tr').forEach(row => {
            const rowSearchText = row.getAttribute('data-search-text');
            if (searchText === '' || rowSearchText.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    searchClientsInput.addEventListener('input', filterClientsList);

    // Selectează/deselectează toți clienții
    selectAllClientsCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.client-checkbox').forEach(checkbox => {
            checkbox.checked = selectAllClientsCheckbox.checked;
        });
    });

    // Populează și filtrează lista de angajați în modal
    const searchEmployeesInput = document.getElementById('searchEmployees');
    const employeesTableBody = document.getElementById('employeesTableBody');
    const selectAllEmployeesCheckbox = document.getElementById('selectAllEmployees');

    function filterEmployeesList() {
        const searchText = searchEmployeesInput.value.toLowerCase().trim();
        document.querySelectorAll('#employeesTableBody tr').forEach(row => {
            const rowSearchText = row.getAttribute('data-search-text');
            if (searchText === '' || rowSearchText.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    searchEmployeesInput.addEventListener('input', filterEmployeesList);

    // Selectează/deselectează toți angajații
    selectAllEmployeesCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
            checkbox.checked = selectAllEmployeesCheckbox.checked;
        });
    });

    // --- Funcționalitate Export (PDF, Excel, Print) ---
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const title = `Listă Abonați Newsletter`;
        const headers = [];
        document.querySelectorAll('#subscribersTable thead th').forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll('#subscribersTableBody tr').forEach(row => {
            if (row.style.display !== 'none') { // Doar rândurile vizibile
                const rowData = [];
                // Excludem coloana de Acțiuni din export (ultima coloană)
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

        doc.save(`Lista_Abonati_Newsletter.pdf`);
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('subscribersTable');
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
        XLSX.utils.book_append_sheet(wb, ws, "Abonati Newsletter");
        XLSX.writeFile(wb, `Lista_Abonati_Newsletter.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('subscribersTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Abonați Newsletter</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Abonați Newsletter</h1>
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
