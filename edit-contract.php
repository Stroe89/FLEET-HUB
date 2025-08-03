<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php'; // Asigură-te că include Bootstrap 5, Boxicons etc.

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Preluăm ID-ul contractului din URL
$contract_id = $_GET['id'] ?? null;

if (!$contract_id || !is_numeric($contract_id)) {
    $_SESSION['error_message'] = "ID contract invalid. Vă rugăm să selectați un contract valid pentru editare.";
    header("Location: contracte-clienti.php");
    exit();
}

// Preluăm datele contractului existent
$contract_data = null;
$stmt_contract = $conn->prepare("
    SELECT 
        c.*, cl.nume_companie 
    FROM 
        contracte c
    JOIN
        clienti cl ON c.id_client = cl.id
    WHERE 
        c.id = ? AND c.is_deleted = FALSE
");
if ($stmt_contract) {
    $stmt_contract->bind_param("i", $contract_id);
    $stmt_contract->execute();
    $result_contract = $stmt_contract->get_result();
    if ($result_contract->num_rows > 0) {
        $contract_data = $result_contract->fetch_assoc();
    }
    $stmt_contract->close();
}

if (!$contract_data) {
    $_SESSION['error_message'] = "Contractul cu ID-ul specificat nu a fost găsit sau a fost șters.";
    header("Location: contracte-clienti.php");
    exit();
}

// Preluăm lista de clienți pentru dropdown
$clienti_list = [];
$stmt_clienti = $conn->prepare("SELECT id, nume_companie FROM clienti WHERE is_active = TRUE ORDER BY nume_companie ASC");
if ($stmt_clienti) {
    $stmt_clienti->execute();
    $result_clienti = $stmt_clienti->get_result();
    while ($row = $result_clienti->fetch_assoc()) {
        $clienti_list[] = $row;
    }
    $stmt_clienti->close();
}

$conn->close(); // Închide conexiunea la baza de date

// Mesaje de succes sau eroare din sesiune (pentru după procesare)
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

// Liste predefinite pentru dropdown-uri (identice cu adauga-contract.php)
$tipuri_contracte = ['Transport Marfă', 'Închiriere Autocar', 'Logistice', 'Mentenanță', 'Altele'];
sort($tipuri_contracte);

$statusuri_contracte = ['Activ', 'În Negociere', 'Suspendat', 'Expirat', 'Anulat']; // Ordine logică pentru adăugare
sort($statusuri_contracte);

$monede_disponibile = ['RON', 'EUR', 'USD']; // Monede comune

// Pentru Persoana de Contact a Clientului, ideal ar fi să o populezi dinamic după selecția clientului (prin AJAX)
// Aici vom lăsa câmpuri de text simple.
?>

<title>NTS TOUR | Editează Contract</title>

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

    /* Stiluri specifice pentru formularul de adăugare contract */
    .form-group-card {
        background-color: #2a3042; /* Fundal card */
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .form-group-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
    }
    .form-group-card h5 {
        color: #ffffff;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 1.4rem;
        font-weight: 600;
    }
    .form-group-card .section-header {
        color: #ffffff;
        font-size: 1.1rem;
        font-weight: 500;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
    }
    .form-group-card .section-header i {
        font-size: 1.3rem;
        margin-right: 0.6rem;
    }

    /* File Upload Indicator */
    .file-upload-status {
        margin-top: 0.5rem;
        font-size: 0.9em;
        color: #a0a0a0;
    }
    .file-upload-status.success {
        color: #28a745;
    }
    .file-upload-status.error {
        color: #dc3545;
    }
    .progress-bar {
        height: 8px;
        border-radius: 5px;
        background-color: #0d6efd;
        transition: width 0.4s ease;
    }
    .progress {
        height: 8px;
        background-color: #495057;
        border-radius: 5px;
        margin-top: 0.5rem;
    }
    /* General site-wide styling (from template/header.php or custom.css) */
    .page-breadcrumb .breadcrumb-item a {
        color: #a0a0a0;
        font-size: 0.95rem;
    }
    .page-breadcrumb .breadcrumb-item a:hover {
        color: #ffffff;
    }
    .page-breadcrumb .breadcrumb-item.active {
        color: #e0e0e0;
        font-weight: 500;
        font-size: 0.95rem;
    }
    .bx {
        vertical-align: middle;
        margin-right: 4px;
        font-size: 1.1em;
    }
    /* Validation feedback */
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #fa5c7c;
    }
    .invalid-feedback {
        color: #fa5c7c;
        font-size: 0.8em;
    }
    .form-control.is-valid, .form-select.is-valid {
        border-color: #0acf83;
    }
    .valid-feedback {
        color: #0acf83;
        font-size: 0.8em;
    }
    /* CORECTIE STIL: Asigură-te că main-content respectă sidebar-ul. */
    /* Aceste stiluri ar trebui să fie în fișierul CSS principal al temei (template/header.php sau custom.css). */
    /* Sunt incluse aici temporar pentru a rezolva problema vizuală. */
    .main-wrapper {
        display: flex; /* Assuming your main layout uses flexbox for sidebar and content */
    }
    .main-content {
        flex-grow: 1; /* Permite conținutului să ocupe spațiul disponibil */
        padding: 20px; /* Adaugă padding consistent */
        /* Dacă sidebar-ul tău are o lățime fixă, s-ar putea să ai nevoie de un margin-left pe main-content */
        /* width: calc(100% - var(--sidebar-width)); */
        /* margin-left: var(--sidebar-width); */
    }
    /* Ajustare a lățimii coloanelor din formular pentru a se încadra mai bine */
    .form-group-card .row > div {
        flex: 0 0 auto;
        width: 100%; /* Default la 100% pe mobil */
    }
    @media (min-width: 768px) { /* Medium devices (tablets) */
        .form-group-card .col-md-4 {
            width: 33.333333%;
        }
        .form-group-card .col-md-6 {
            width: 50%;
        }
        /* Ajustări pentru col-lg-4 dacă e cazul */
        .form-group-card .col-lg-4 {
            width: 33.333333%;
        }
    }
    /* Asigură că `.input-group` din `valoare_contract` nu depășește lățimea */
    .input-group .form-control, .input-group .form-select {
        flex: 1 1 auto; /* Permite elementelor să se extindă flexibil */
        width: 1%; /* Fix pentru Bootstrap 5, împiedică elementele să treacă de 100% */
    }
</style>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">


<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4">
            <div class="breadcrumb-title pe-3">Gestionare Contracte</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i> Acasă</a></li>
                        <li class="breadcrumb-item"><a href="contracte-clienti.php">Contracte Clienți</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><i class="bx bx-edit"></i> Editează Contract</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Formular Editare Contract (ID: <?php echo htmlspecialchars($contract_data['id']); ?>)</h4>
                        <p class="text-muted mb-4">Actualizează detaliile contractului <strong><?php echo htmlspecialchars($contract_data['nume_contract']); ?></strong> pentru clientul <strong><?php echo htmlspecialchars($contract_data['nume_companie']); ?></strong>.</p>
                        <hr class="mb-4">

                        <?php if ($success_message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                <div><?php echo $success_message; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                <div><?php echo $error_message; ?></div>
                            </div>
                        <?php endif; ?>

                        <form id="editContractForm" action="process_contract.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars($contract_data['id']); ?>">

                            <div class="form-group-card mb-4">
                                <h5 class="mb-4"><i class="bx bx-info-circle me-2"></i> Informații Generale Contract</h5>
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-4">
                                        <label for="id_client" class="form-label">Client: <span class="text-danger">*</span></label>
                                        <select class="form-select" id="id_client" name="id_client" required>
                                            <option value="">-- Selectează Clientul --</option>
                                            <?php foreach ($clienti_list as $client): ?>
                                                <option value="<?php echo htmlspecialchars($client['id']); ?>" <?php echo ($contract_data['id_client'] == $client['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($client['nume_companie']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Selectarea clientului este obligatorie.</div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label for="nume_contract" class="form-label">Nume Contract: <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nume_contract" name="nume_contract" placeholder="Ex: Contract Servicii Transport 2025" value="<?php echo htmlspecialchars($contract_data['nume_contract']); ?>" required>
                                        <div class="invalid-feedback">Numele contractului este obligatoriu.</div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label for="numar_contract" class="form-label">Număr Contract: <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="numar_contract" name="numar_contract" placeholder="Ex: TRN-2025-001" value="<?php echo htmlspecialchars($contract_data['numar_contract']); ?>" required>
                                        <div class="invalid-feedback">Numărul contractului este obligatoriu.</div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label for="tip_contract" class="form-label">Tip Contract: <span class="text-danger">*</span></label>
                                        <select class="form-select" id="tip_contract" name="tip_contract" required>
                                            <option value="">-- Selectează Tipul --</option>
                                            <?php foreach ($tipuri_contracte as $tip): ?>
                                                <option value="<?php echo htmlspecialchars($tip); ?>" <?php echo ($contract_data['tip_contract'] == $tip) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tip); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Tipul contractului este obligatoriu.</div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label for="status_contract" class="form-label">Status Contract: <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status_contract" name="status_contract" required>
                                            <option value="">-- Selectează Statusul --</option>
                                            <?php foreach ($statusuri_contracte as $status): ?>
                                                <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($contract_data['status_contract'] == $status) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($status); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Statusul contractului este obligatoriu.</div>
                                    </div>
                                    <div class="col-md-6 col-lg-4">
                                        <label for="valoare_contract" class="form-label">Valoare Contract:</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" id="valoare_contract" name="valoare_contract" placeholder="Ex: 15000.00" value="<?php echo htmlspecialchars($contract_data['valoare_contract'] ?? ''); ?>">
                                            <select class="form-select" name="moneda">
                                                <?php foreach ($monede_disponibile as $moneda): ?>
                                                    <option value="<?php echo htmlspecialchars($moneda); ?>" <?php echo ($contract_data['moneda'] == $moneda) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($moneda); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Valoare contract invalidă.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-card mb-4">
                                <h5 class="mb-4"><i class="bx bx-calendar me-2"></i> Perioada Contractuală</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="data_semnare" class="form-label">Dată Semnare:</label>
                                        <input type="date" class="form-control" id="data_semnare" name="data_semnare" value="<?php echo htmlspecialchars($contract_data['data_semnare'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="data_inceput" class="form-label">Dată Început: <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="data_inceput" name="data_inceput" value="<?php echo htmlspecialchars($contract_data['data_inceput']); ?>" required>
                                        <div class="invalid-feedback">Data de început este obligatorie.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="data_expirare" class="form-label">Dată Expirare: <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="data_expirare" name="data_expirare" value="<?php echo htmlspecialchars($contract_data['data_expirare']); ?>" required>
                                        <div class="invalid-feedback">Data de expirare este obligatorie.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-card mb-4">
                                <h5 class="mb-4"><i class="bx bx-user-plus me-2"></i> Contact Client (Specific Contractului)</h5>
                                <p class="text-muted small mb-3">Introduceți detalii de contact specifice pentru acest contract, dacă diferă de contactul principal al clientului.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="persoana_contact_client" class="form-label">Nume Persoană Contact:</label>
                                        <input type="text" class="form-control" id="persoana_contact_client" name="persoana_contact_client" placeholder="Ex: Popescu Ioana" value="<?php echo htmlspecialchars($contract_data['persoana_contact_client'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="email_contact_client" class="form-label">Email Contact:</label>
                                        <input type="email" class="form-control" id="email_contact_client" name="email_contact_client" placeholder="Ex: ioana.popescu@client.ro" value="<?php echo htmlspecialchars($contract_data['email_contact_client'] ?? ''); ?>">
                                        <div class="invalid-feedback">Adresa de email nu este validă.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="telefon_contact_client" class="form-label">Telefon Contact:</label>
                                        <input type="text" class="form-control" id="telefon_contact_client" name="telefon_contact_client" placeholder="Ex: 07xx xxx xxx" value="<?php echo htmlspecialchars($contract_data['telefon_contact_client'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-card mb-4">
                                <h5 class="mb-4"><i class="bx bx-upload me-2"></i> Fișier Contract & Observații</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="contract_file" class="form-label">Încarcă Fișier Nou (PDF, DOCX, JPG, max 10MB):</label>
                                        <input type="file" class="form-control" id="contract_file" name="contract_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        <div class="invalid-feedback file-feedback">Fișierul trebuie să fie PDF, DOC, DOCX, JPG, PNG și maxim 10MB.</div>
                                        <div class="file-upload-status" id="fileStatus">Niciun fișier nou selectat.</div>
                                        <div class="progress mt-2" style="display: none;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <?php if (!empty($contract_data['cale_fisier'])): ?>
                                            <p class="text-muted small mt-2">Fișier existent: <a href="<?php echo htmlspecialchars($contract_data['cale_fisier']); ?>" target="_blank"><?php echo htmlspecialchars($contract_data['nume_original_fisier'] ?? 'Fișier actual'); ?></a> (încărcați un fișier nou pentru a-l înlocui)</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="termeni_plata" class="form-label">Termeni Plată:</label>
                                        <input type="text" class="form-control" id="termeni_plata" name="termeni_plata" placeholder="Ex: Net 30 zile, Avans 50%" value="<?php echo htmlspecialchars($contract_data['termeni_plata'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="observatii" class="form-label">Observații / Detalii Suplimentare:</label>
                                        <textarea class="form-control" id="observatii" name="observatii" rows="3" placeholder="Adaugă observații sau clauze speciale..."><?php echo htmlspecialchars($contract_data['observatii'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-3 mt-4">
                                <a href="contracte-clienti.php" class="btn btn-secondary"><i class="bx bx-x-circle me-1"></i> Anulează</a>
                                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Salvează Modificările</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editContractForm = document.getElementById('editContractForm');
    const contractFile = document.getElementById('contract_file');
    const fileStatus = document.getElementById('fileStatus');
    const progressBarContainer = contractFile.closest('.col-md-6').querySelector('.progress');
    const progressBar = contractFile.closest('.col-md-6').querySelector('.progress-bar');

    // Client-side file validation
    contractFile.addEventListener('change', function() {
        const file = this.files[0];
        const feedbackDiv = this.nextElementSibling; // invalid-feedback

        if (!file) {
            this.classList.remove('is-invalid', 'is-valid');
            feedbackDiv.style.display = 'none';
            fileStatus.textContent = 'Niciun fișier nou selectat.';
            fileStatus.classList.remove('success', 'error');
            progressBarContainer.style.display = 'none';
            progressBar.style.width = '0%';
            return;
        }

        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        const maxSize = 10 * 1024 * 1024; // 10MB

        let isValid = true;
        let errorMessage = 'Fișierul trebuie să fie PDF, DOC, DOCX, JPG, PNG și maxim 10MB.';

        if (!allowedTypes.includes(file.type)) {
            isValid = false;
            errorMessage = `Tipul fișierului pentru "${file.name}" nu este permis. Se acceptă doar PDF, DOC, DOCX, JPG, PNG.`;
        } else if (file.size > maxSize) {
            isValid = false;
            errorMessage = `Dimensiunea fișierului pentru "${file.name}" depășește limita de 10MB.`;
        }

        if (!isValid) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            feedbackDiv.textContent = errorMessage;
            feedbackDiv.style.display = 'block';
            fileStatus.textContent = `Eroare: ${errorMessage}`;
            fileStatus.classList.remove('success');
            fileStatus.classList.add('error');
            progressBarContainer.style.display = 'none';
            progressBar.style.width = '0%';
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            feedbackDiv.style.display = 'none';
            fileStatus.textContent = `Fișier selectat: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            fileStatus.classList.remove('error');
            fileStatus.classList.add('success');
            // Simulate progress bar
            progressBarContainer.style.display = 'block';
            progressBar.style.width = '100%';
            progressBar.setAttribute('aria-valuenow', '100');
        }
    });

    // Form submission validation
    editContractForm.addEventListener('submit', function(event) {
        // Clear any previous validation styles
        editContractForm.classList.remove('was-validated');

        // Check overall form validity
        if (!editContractForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Custom validation for file input
        const fileInputValid = contractFile.files.length === 0 || contractFile.classList.contains('is-valid');
        if (!fileInputValid) {
            event.preventDefault();
            event.stopPropagation();
            contractFile.classList.add('is-invalid');
            contractFile.nextElementSibling.style.display = 'block'; // Show invalid feedback
        }

        editContractForm.classList.add('was-validated');
    });

    // Client-side date validation (optional but good practice)
    const dataInceput = document.getElementById('data_inceput');
    const dataExpirare = document.getElementById('data_expirare');

    function validateDates() {
        const startDate = new Date(dataInceput.value);
        const endDate = new Date(dataExpirare.value);

        if (dataInceput.value && dataExpirare.value && startDate > endDate) {
            dataExpirare.setCustomValidity('Data de expirare nu poate fi înainte de data de început.');
            dataExpirare.classList.add('is-invalid');
        } else {
            dataExpirare.setCustomValidity(''); // Clear custom validity
            dataExpirare.classList.remove('is-invalid');
        }
    }

    dataInceput.addEventListener('change', validateDates);
    dataExpirare.addEventListener('change', validateDates);
    validateDates(); // Run on load to check pre-filled dates
});
</script>