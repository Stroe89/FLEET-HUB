<?php
session_start();

// --- DEPANARE: Activează afișarea erorilor PHP ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- SFÂRȘIT DEPANARE ---

// Încercăm să includem db_connect.php
try {
    require_once 'db_connect.php';
} catch (Exception $e) {
    // Dacă db_connect eșuează, afișăm o eroare fatală
    die("Eroare fatală la conectarea la baza de date: " . htmlspecialchars($e->getMessage()));
}

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

// Valori implicite pentru un formular gol (pentru un client nou)
// Actualizat pentru noile câmpuri
$client = [
    'nume_companie' => '',
    'tip_client' => 'Persoană Juridică', // Default value
    'persoana_contact' => '',
    'telefon' => '',
    'email' => '',
    'adresa' => '',
    'cui' => '',
    'nr_reg_com' => '',
    'cnp' => '',
    'serie_ci' => '',
    'iban' => '',
    'banca' => '',
    'capital_social' => '',
    'obiect_activitate' => '',
    'observatii' => '',
    'status_client' => 'Activ', // Default value
    'categorii' => '' // JSON string
];

// Acum, adauga-client.php va afișa ÎNTOTDEAUNA pagina completă
require_once 'template/header.php'; // Include header-ul normal
?>
<title>NTS TOUR | Adaugă Client</title>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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

    /* Specific styles for sections */
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #6a90f1;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
    }
    .form-section-title .bx {
        font-size: 1.5rem;
        margin-right: 0.5rem;
    }
    .form-group-card {
        background-color: #1f2538;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.6rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .choices__inner {
        background-color: #1a2035 !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: #e0e0e0 !important;
        border-radius: 0.5rem !important;
        padding: 0.375rem 0.75rem !important;
        min-height: calc(2.25rem + 2px);
    }
    .choices__input {
        background-color: #1a2035 !important;
        color: #e0e0e0 !important;
    }
    .choices__list--multiple .choices__item {
        background-color: #6a90f1 !important;
        border-color: #6a90f1 !important;
        color: #ffffff !important;
        border-radius: 0.3rem !important;
        padding: 0.25rem 0.6rem !important;
        font-size: 0.875rem !important;
        margin-bottom: 0.2rem;
    }
    .choices__list--multiple .choices__item.is-highlighted {
        background-color: #4c7ad6 !important;
    }
    .choices__list--dropdown .choices__item {
        color: #e0e0e0 !important;
    }
    .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background-color: #3b435a !important;
        color: #ffffff !important;
    }
    .choices__list--dropdown {
        background-color: #2a3042 !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />


<main class="main-wrapper">
    <div class="main-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Adaugă Client</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Adaugă Client Nou</h4>
                        <p class="text-muted mb-4">Completează detaliile de mai jos pentru a înregistra un client nou.</p>
                        <hr class="mb-4">

                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form id="addClientForm" action="process_clienti.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="add">
                            
                            <div class="form-group-card">
                                <h5 class="form-section-title"><i class="bx bx-building"></i> Informații Generale Client</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="tipClient" class="form-label">Tip Client: <span class="text-danger">*</span></label>
                                        <select class="form-select" id="tipClient" name="tip_client" required>
                                            <option value="Persoană Juridică" <?php echo ($client['tip_client'] == 'Persoană Juridică') ? 'selected' : ''; ?>>Persoană Juridică</option>
                                            <option value="Persoană Fizică" <?php echo ($client['tip_client'] == 'Persoană Fizică') ? 'selected' : ''; ?>>Persoană Fizică</option>
                                            <option value="Instituție Publică" <?php echo ($client['tip_client'] == 'Instituție Publică') ? 'selected' : ''; ?>>Instituție Publică</option>
                                            <option value="ONG" <?php echo ($client['tip_client'] == 'ONG') ? 'selected' : ''; ?>>ONG</option>
                                            <option value="Altul" <?php echo ($client['tip_client'] == 'Altul') ? 'selected' : ''; ?>>Altul</option>
                                        </select>
                                        <div class="invalid-feedback">Te rog selectează tipul clientului.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="numeCompanie" class="form-label"><span id="labelNumeCompanie">Nume Companie:</span> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="numeCompanie" name="nume_companie" value="<?php echo htmlspecialchars($client['nume_companie']); ?>" required>
                                        <div class="invalid-feedback">Te rog introdu numele companiei/complet.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="persoanaContact" class="form-label">Persoană Contact (internă, dacă e cazul):</label>
                                        <input type="text" class="form-control" id="persoanaContact" name="persoana_contact" value="<?php echo htmlspecialchars($client['persoana_contact']); ?>" placeholder="Numele persoanei de contact din cadrul clientului">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="statusClient" class="form-label">Status Client: <span class="text-danger">*</span></label>
                                        <select class="form-select" id="statusClient" name="status_client" required>
                                            <option value="Activ" <?php echo ($client['status_client'] == 'Activ') ? 'selected' : ''; ?>>Activ</option>
                                            <option value="Potențial" <?php echo ($client['status_client'] == 'Potențial') ? 'selected' : ''; ?>>Potențial</option>
                                            <option value="Inactiv" <?php echo ($client['status_client'] == 'Inactiv') ? 'selected' : ''; ?>>Inactiv</option>
                                            <option value="Suspendat" <?php echo ($client['status_client'] == 'Suspendat') ? 'selected' : ''; ?>>Suspendat</option>
                                            <option value="Litigiu" <?php echo ($client['status_client'] == 'Litigiu') ? 'selected' : ''; ?>>Litigiu</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-card" id="juridicFields">
                                <h5 class="form-section-title"><i class="bx bx-detail"></i> Detalii Fiscale/Legale (Persoană Juridică)</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cui" class="form-label">CUI:</label>
                                        <input type="text" class="form-control" id="cui" name="cui" value="<?php echo htmlspecialchars($client['cui']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="nrRegCom" class="form-label">Nr. Reg. Com.:</label>
                                        <input type="text" class="form-control" id="nrRegCom" name="nr_reg_com" value="<?php echo htmlspecialchars($client['nr_reg_com']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="capitalSocial" class="form-label">Capital Social (RON):</label>
                                        <input type="number" step="0.01" class="form-control" id="capitalSocial" name="capital_social" value="<?php echo htmlspecialchars($client['capital_social']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="obiectActivitate" class="form-label">Obiect de Activitate:</label>
                                        <textarea class="form-control" id="obiectActivitate" name="obiect_activitate" rows="2" placeholder="Ex: Transport rutier de mărfuri, Servicii de turism"><?php echo htmlspecialchars($client['obiect_activitate']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-card" id="fizicFields" style="display: none;">
                                <h5 class="form-section-title"><i class="bx bx-id-card"></i> Detalii Identificare (Persoană Fizică)</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="cnp" class="form-label">CNP:</label>
                                        <input type="text" class="form-control" id="cnp" name="cnp" value="<?php echo htmlspecialchars($client['cnp']); ?>" maxlength="13">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="serieCI" class="form-label">Serie / Nr. CI:</label>
                                        <input type="text" class="form-control" id="serieCI" name="serie_ci" value="<?php echo htmlspecialchars($client['serie_ci']); ?>" placeholder="Ex: XY123456">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group-card">
                                <h5 class="form-section-title"><i class="bx bx-map"></i> Detalii Contact & Adresă</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="telefon" class="form-label">Telefon Principal:</label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($client['telefon']); ?>" placeholder="Ex: +407xxxxxxxx">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Principal:</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" placeholder="Ex: contact@companie.ro">
                                        <div class="invalid-feedback">Te rog introdu o adresă de email validă.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="iban" class="form-label">Cont IBAN:</label>
                                        <input type="text" class="form-control" id="iban" name="iban" value="<?php echo htmlspecialchars($client['iban']); ?>" placeholder="ROxxBANKxxxxxxxxxxxxxxxxxxxx">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="banca" class="form-label">Banca:</label>
                                        <input type="text" class="form-control" id="banca" name="banca" value="<?php echo htmlspecialchars($client['banca']); ?>" placeholder="Numele băncii">
                                    </div>
                                    <div class="col-12">
                                        <label for="adresa" class="form-label">Adresă Sediu/Domiciliu:</label>
                                        <textarea class="form-control" id="adresa" name="adresa" rows="2" placeholder="Strada, Număr, Bloc, Scara, Etaj, Apartament, Localitate, Județ, Cod Poștal"><?php echo htmlspecialchars($client['adresa']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-card">
                                <h5 class="form-section-title"><i class="bx bx-tag"></i> Categorii & Observații</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="categorii" class="form-label">Categorii/Etichete:</label>
                                        <select class="form-select" id="categorii" name="categorii[]" multiple>
                                            <option value="Transport Marfă">Transport Marfă</option>
                                            <option value="Transport Persoane">Transport Persoane</option>
                                            <option value="Logistică">Logistică</option>
                                            <option value="Partener">Partener</option>
                                            <option value="Furnizor">Furnizor</option>
                                            <option value="Evenimente">Evenimente</option>
                                            <option value="Publicitate">Publicitate</option>
                                            <option value="Mentenanță">Mentenanță</option>
                                            <option value="IT">IT</option>
                                            <option value="Consulting">Consulting</option>
                                        </select>
                                        <small class="form-text text-muted">Selectează una sau mai multe categorii relevante.</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="observatii" class="form-label">Observații Adiționale:</label>
                                        <textarea class="form-control" id="observatii" name="observatii" rows="3" placeholder="Informații suplimentare despre client, preferințe, istoric etc."><?php echo htmlspecialchars($client['observatii']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="fise-clienti.php" class="btn btn-secondary"><i class="bx bx-arrow-back me-1"></i> Anulează</a>
                                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Salvează Client</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
    require_once 'template/footer.php'; // Include footer-ul normal
    if ($conn) { $conn->close(); } // Închide conexiunea la baza de date
?>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Choices.js for the categories multi-select
    let categoriesChoicesInstance; // Declare outside to make it accessible for direct manipulation
    const categoriesSelect = document.getElementById('categorii');
    if (categoriesSelect) {
        categoriesChoicesInstance = new Choices(categoriesSelect, { // Store the instance here
            removeItemButton: true,
            delimiter: ',',
            editItems: true, // Allows editing selected items
            maxItemCount: -1, // No limit on items
            placeholder: true,
            placeholderValue: 'Adaugă sau selectează categorii...',
            searchPlaceholderValue: 'Caută sau adaugă...',
            shouldSort: true, // Sort options alphabetically
            duplicateItemsAllowed: false,
            // Preload existing categories if any (for edit mode)
            // For 'add' page, this will be empty, which is fine.
            // If you load client data for editing, you'd populate this:
            // items: <?php //echo json_encode(json_decode($client['categorii'] ?? '[]', true)); ?>
        });
    }

    // Funcție pentru afișarea Toast-urilor Bootstrap
    function showToast(type, message) {
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            const body = document.querySelector('body');
            toastContainer = document.createElement('div');
            toastContainer.classList.add('toast-container', 'position-fixed', 'top-0', 'end-0', 'p-3');
            toastContainer.style.zIndex = '1080';
            body.appendChild(toastContainer);
        }

        const toastId = `toast-${Date.now()}`;
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    const addClientFormPage = document.getElementById('addClientForm');
    const tipClientSelect = document.getElementById('tipClient');
    const juridicFields = document.getElementById('juridicFields');
    const fizicFields = document.getElementById('fizicFields');
    const labelNumeCompanie = document.getElementById('labelNumeCompanie');
    const numeCompanieInput = document.getElementById('numeCompanie');

    // Function to toggle field visibility based on client type
    function toggleClientTypeFields() {
        const selectedType = tipClientSelect.value;

        // Reset required state for all related fields within these sections
        // Note: 'required' attribute is better managed directly on inputs if they are always required for a type
        juridicFields.querySelectorAll('input, select, textarea').forEach(el => el.removeAttribute('required'));
        fizicFields.querySelectorAll('input, select, textarea').forEach(el => el.removeAttribute('required'));
        // numeCompanieInput.removeAttribute('required'); // This is always required, no need to remove

        if (selectedType === 'Persoană Juridică' || selectedType === 'Instituție Publică' || selectedType === 'ONG') {
            juridicFields.style.display = 'block';
            fizicFields.style.display = 'none';
            labelNumeCompanie.textContent = 'Nume Companie:';
            // Example: Make CUI required for legal entities if needed
            // document.getElementById('cui').setAttribute('required', 'required');
        } else if (selectedType === 'Persoană Fizică') {
            juridicFields.style.display = 'none';
            fizicFields.style.display = 'block';
            labelNumeCompanie.textContent = 'Nume Complet (Persoană Fizică):';
            // Example: Make CNP required for individuals if needed
            // document.getElementById('cnp').setAttribute('required', 'required');
        } else { // Altul
            juridicFields.style.display = 'none';
            fizicFields.style.display = 'none';
            labelNumeCompanie.textContent = 'Nume Client:';
        }

        // numeCompanieInput is always required regardless of type
        numeCompanieInput.setAttribute('required', 'required');

        // Re-validate the form to apply new required states visually (optional, checkValidity() handles it)
        addClientFormPage.classList.remove('was-validated');
    }

    // Initial call to set correct fields on page load
    toggleClientTypeFields();

    // Event listener for type change
    tipClientSelect.addEventListener('change', toggleClientTypeFields);

    if (addClientFormPage) {
        addClientFormPage.addEventListener('submit', function(event) {
            event.preventDefault();

            // Perform custom validation based on client type before built-in checkValidity
            const selectedType = tipClientSelect.value;
            let customValidationPassed = true;

            // Example: If CUI is conditionally required for 'Persoană Juridică'
            // if ((selectedType === 'Persoană Juridică' || selectedType === 'Instituție Publică' || selectedType === 'ONG') && document.getElementById('cui').value.trim() === '') {
            //     // Add a visual invalid state if needed, or simply set customValidationPassed = false
            //     // document.getElementById('cui').classList.add('is-invalid');
            //     customValidationPassed = false;
            // } else {
            //     // document.getElementById('cui').classList.remove('is-invalid');
            // }

            // If any custom validation fails, show error and stop
            if (!customValidationPassed) {
                showToast('danger', 'Te rog completează toate câmpurile obligatorii conform tipului de client.');
                addClientFormPage.classList.add('was-validated'); // Show validation feedback
                return;
            }


            if (!addClientFormPage.checkValidity()) {
                event.stopPropagation();
                addClientFormPage.classList.add('was-validated');
                showToast('danger', 'Te rog completează toate câmpurile obligatorii.');
                return;
            }
            addClientFormPage.classList.add('was-validated');

            const formData = new FormData(addClientFormPage);
            formData.append('action', 'add'); // Asigură-te că acțiunea este 'add'

            // Get selected categories from Choices.js and convert to JSON string
            // This is crucial: Use categoriesChoicesInstance to get the values
            if (categoriesChoicesInstance) {
                const selectedCategories = categoriesChoicesInstance.getValue(true); // true returns array of values
                formData.set('categorii', JSON.stringify(selectedCategories)); // Set as JSON string
            } else {
                formData.set('categorii', '[]'); // Fallback if Choices.js somehow wasn't initialized
            }

            fetch('process_clienti.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    addClientFormPage.reset();
                    addClientFormPage.classList.remove('was-validated');
                    
                    // Reset Choices.js using the stored instance
                    if (categoriesChoicesInstance) {
                        categoriesChoicesInstance.clearStore(); // Clear selected items
                        // If you need to revert to default options, you might need to re-init (destroy then new Choices)
                        // For a simple add form, clearStore() is usually sufficient.
                    }
                    toggleClientTypeFields(); // Reset field visibility after form reset

                    // Redirecționează către pagina de fise-clienti.php (sau detalii noului client)
                    setTimeout(() => {
                        window.location.href = `fise-clienti.php?new_client_id=${data.client_id}`; // Ensure client_id is returned by process_clienti.php
                    }, 1500);
                } else {
                    showToast('danger', data.message || 'A apărut o eroare la salvarea clientului.');
                    // If there's an error, prevent redirect and keep form data (if needed for user correction)
                }
            })
            .catch(error => {
                console.error('Eroare la salvarea clientului:', error);
                showToast('danger', 'A apărut o eroare la salvarea clientului. Detalii: ' + error.message);
            });
        });
    }
});
</script>