<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php'; // Assumes Bootstrap 5 and relevant JS (e.g., jQuery) are included here

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Preluăm lista de vehicule pentru dropdown
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare, tip FROM vehicule ORDER BY model ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}
$conn->close();

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

// Tipurile de documente disponibile
$tipuri_documente = ['ITP', 'RCA', 'Rovinieta', 'Asigurare Casco', 'Licenta Transport', 'Altele'];
?>

<title>NTS TOUR | Adaugă Document Nou</title>

<style>
    /* Paste the updated CSS from the previous block here */
    /* General Body & Typography */
    body {
        font-family: 'Inter', sans-serif; /* Using a modern sans-serif font */
        background-color: #1a2035; /* Dark background */
        color: #e0e0e0; /* Light text for readability */
    }

    /* Card Styling for Document Groups */
    .form-group-card {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.75rem;
        padding: 2.5rem; /* Increased padding for more breathing room */
        margin-bottom: 3rem; /* More spacing between cards */
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.3); /* Deeper shadow */
        transition: all 0.3s ease-in-out; /* Smooth transitions */
        position: relative; /* For the close button positioning */
    }
    .form-group-card:hover {
        transform: translateY(-5px); /* More pronounced lift effect */
        box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.4);
    }
    .form-group-card h5 {
        color: #ffffff;
        margin-bottom: 2rem; /* More spacing */
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 1.6rem; /* Larger heading for document groups */
        font-weight: 600;
    }

    /* Section/Category Headers within a document card */
    .form-group-card .section-header {
        color: #ffffff;
        font-size: 1.2rem; /* Slightly smaller for sub-sections */
        font-weight: 500;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.08); /* Dashed border for sub-sections */
        display: flex;
        align-items: center;
    }
    .form-group-card .section-header i {
        font-size: 1.5rem;
        margin-right: 0.75rem;
    }


    /* Form Labels & Inputs */
    .form-label {
        color: #c0c0c0;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .form-control,
    .form-select {
        background-color: #1a2035;
        color: #e0e0e0;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 0.5rem;
        padding: 0.85rem 1.25rem;
        transition: all 0.3s ease-in-out;
    }
    .form-control::placeholder {
        color: #909090;
        opacity: 0.8;
    }
    .form-control:focus,
    .form-select:focus {
        background-color: #151a2b;
        color: #ffffff;
        border-color: #6a90f1;
        box-shadow: 0 0 0 0.25rem rgba(106, 144, 241, 0.35);
    }

    /* Checkbox Styling */
    .form-check-label {
        color: #e0e0e0;
        font-weight: 400;
    }
    .form-check-input {
        background-color: #3b425b;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Buttons */
    .btn {
        border-radius: 0.6rem;
        padding: 0.8rem 1.8rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        transition: all 0.2s ease-in-out;
    }
    .btn-add-more-document {
        background-color: #28a745;
        border-color: #28a745;
        color: #ffffff;
    }
    .btn-add-more-document:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(40, 167, 69, 0.3);
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
    }
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
    }

    /* Close button on card */
    .btn-close.text-white {
        filter: invert(1);
        opacity: 0.6;
        transition: opacity 0.2s ease;
        font-size: 1.2rem;
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
    }
    .btn-close.text-white:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    /* File Upload Indicator */
    .file-upload-status {
        margin-top: 0.5rem;
        font-size: 0.9em;
        color: #a0a0a0;
    }
    .file-upload-status.success {
        color: #0acf83; /* Brighter green */
    }
    .file-upload-status.error {
        color: #fa5c7c; /* Brighter red */
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

    /* Alerts */
    .alert {
        border-radius: 0.5rem;
        padding: 1rem 1.5rem;
        font-weight: 500;
        align-items: center;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }

    /* Custom validation feedback colors for dark theme */
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #fa5c7c;
    }
    .invalid-feedback {
        color: #fa5c7c;
    }
    .form-control.is-valid, .form-select.is-valid {
        border-color: #0acf83;
    }
    .valid-feedback {
        color: #0acf83;
    }

    /* Main content padding for overall layout */
    .main-content {
        padding-top: 2.5rem; /* Increased padding */
        padding-bottom: 2.5rem;
    }

    /* Breadcrumb styles */
    .page-breadcrumb .breadcrumb-item a {
        color: #c0c0c0;
        transition: color 0.2s ease;
    }
    .page-breadcrumb .breadcrumb-item a:hover {
        color: #ffffff;
    }
    .page-breadcrumb .breadcrumb-item.active {
        color: #ffffff;
        font-weight: 500;
    }

    /* Iconography adjustments */
    .bx {
        vertical-align: middle;
        margin-right: 5px;
    }
</style>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">


<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4">
            <div class="breadcrumb-title pe-3">Adaugă Document Nou</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Înregistrează Documente Noi</h4>
                        <p class="text-muted mb-4">Completează detaliile pentru unul sau mai multe documente asociate unui vehicul. Asigură-te că toate câmpurile obligatorii sunt completate pentru o gestionare corectă.</p>
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

                        <form id="addDocumentsForm" class="needs-validation" action="process_document.php" method="POST" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="action" value="add_multiple">

                            <div class="mb-5">
                                <label for="selectVehicle" class="form-label d-flex align-items-center">
                                    <i class="bx bx-car me-2"></i> Selectează Vehiculul: <span class="text-danger ms-1">*</span>
                                </label>
                                <select class="form-select" id="selectVehicle" name="id_vehicul" required>
                                    <option value="">-- Alege un vehicul --</option>
                                    <?php foreach ($vehicule_list as $veh): ?>
                                        <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Te rog selectează un vehicul. Acest câmp este obligatoriu.
                                </div>
                            </div>

                            <div id="documentFieldsContainer">
                                <div class="form-group-card" id="documentGroup_0">
                                    <h5 class="d-flex justify-content-between align-items-center">
                                        <i class="bx bx-receipt me-2"></i> Adaugă Document Nou (1)
                                        <button type="button" class="btn-close text-white" aria-label="Șterge document" onclick="removeDocumentGroup('documentGroup_0')"></button>
                                    </h5>
                                    
                                    <h6 class="section-header"><i class="bx bx-info-circle me-2"></i> Informații Generale</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label for="docName_0" class="form-label">Nume Document: <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="docName_0" name="documents[0][nume_document_user]" placeholder="Ex: ITP 2025, Asigurare RCA" required>
                                            <div class="invalid-feedback">Numele documentului este obligatoriu.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="docType_0" class="form-label">Tip Document: <span class="text-danger">*</span></label>
                                            <select class="form-select" id="docType_0" name="documents[0][tip_document]" required>
                                                <option value="">-- Selectează tipul --</option>
                                                <?php foreach ($tipuri_documente as $tip): ?>
                                                    <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Tipul documentului este obligatoriu.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="docExpirationDate_0" class="form-label">Data Expirării: <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="docExpirationDate_0" name="documents[0][data_expirare]" required>
                                            <div class="invalid-feedback">Data expirării este obligatorie și trebuie să fie o dată validă.</div>
                                        </div>
                                    </div>

                                    <h6 class="section-header"><i class="bx bx-upload me-2"></i> Încărcare Fișier</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-8">
                                            <label for="docFile_0" class="form-label">Selectează Fișier (PDF, JPG, PNG, max 5MB):</label>
                                            <input type="file" class="form-control" id="docFile_0" name="documents[0][document_file]" accept=".pdf,.jpg,.jpeg,.png">
                                            <div class="invalid-feedback file-feedback">Fișierul trebuie să fie PDF, JPG, PNG și maxim 5MB.</div>
                                            <div class="file-upload-status" id="fileStatus_0">Niciun fișier selectat.</div>
                                            <div class="progress mt-2" style="display: none;">
                                                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-center">
                                            <div class="form-check mt-3 mt-md-0">
                                                <input class="form-check-input" type="checkbox" value="1" id="docImportant_0" name="documents[0][important]">
                                                <label class="form-check-label" for="docImportant_0">
                                                    <i class="bx bx-star me-1"></i> Marchează ca important (Notificări prioritare)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="section-header"><i class="bx bx-cog me-2"></i> Opțiuni Avansate (Opțional)</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="docNotes_0" class="form-label">Observații / Detalii suplimentare:</label>
                                            <textarea class="form-control" id="docNotes_0" name="documents[0][observatii]" rows="3" placeholder="Adaugă detalii relevante despre document..."></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="docReference_0" class="form-label">Număr Referință Internă:</label>
                                            <input type="text" class="form-control" id="docReference_0" name="documents[0][numar_referinta]" placeholder="Ex: INV-2024-001">
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>

                            <button type="button" class="btn btn-add-more-document mb-5" id="addMoreDocumentBtn">
                                <i class="bx bx-plus-circle me-1"></i> Adaugă Un Document Suplimentar
                            </button>
                            
                            <div class="d-flex justify-content-end gap-3 mt-4">
                                <a href="documente-vehicule.php" class="btn btn-secondary"><i class="bx bx-x-circle me-1"></i> Anulează</a>
                                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Salvează Toate Documentele</button>
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
    let documentCounter = 1; // Începem de la 1 pentru a genera ID-uri unice
    const documentFieldsContainer = document.getElementById('documentFieldsContainer');
    const addMoreDocumentBtn = document.getElementById('addMoreDocumentBtn');
    const addDocumentsForm = document.getElementById('addDocumentsForm');
    const tipuriDocumente = <?php echo json_encode($tipuri_documente); ?>; // Passed from PHP

    // Function to generate the HTML for a new document group
    const generateDocumentGroupHTML = (index) => {
        return `
            <h5 class="d-flex justify-content-between align-items-center">
                <i class="bx bx-receipt me-2"></i> Adaugă Document Nou (${index + 1})
                <button type="button" class="btn-close text-white" aria-label="Șterge document" onclick="removeDocumentGroup('documentGroup_${index}')"></button>
            </h5>
            
            <h6 class="section-header"><i class="bx bx-info-circle me-2"></i> Informații Generale</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="docName_${index}" class="form-label">Nume Document: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="docName_${index}" name="documents[${index}][nume_document_user]" placeholder="Ex: ITP 2025, Asigurare RCA" required>
                    <div class="invalid-feedback">Numele documentului este obligatoriu.</div>
                </div>
                <div class="col-md-4">
                    <label for="docType_${index}" class="form-label">Tip Document: <span class="text-danger">*</span></label>
                    <select class="form-select" id="docType_${index}" name="documents[${index}][tip_document]" required>
                        <option value="">-- Selectează tipul --</option>
                        ${tipuriDocumente.map(tip => `<option value="${tip}">${tip}</option>`).join('')}
                    </select>
                    <div class="invalid-feedback">Tipul documentului este obligatoriu.</div>
                </div>
                <div class="col-md-4">
                    <label for="docExpirationDate_${index}" class="form-label">Data Expirării: <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="docExpirationDate_${index}" name="documents[${index}][data_expirare]" required>
                    <div class="invalid-feedback">Data expirării este obligatorie și trebuie să fie o dată validă.</div>
                </div>
            </div>

            <h6 class="section-header"><i class="bx bx-upload me-2"></i> Încărcare Fișier</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label for="docFile_${index}" class="form-label">Selectează Fișier (PDF, JPG, PNG, max 5MB):</label>
                    <input type="file" class="form-control" id="docFile_${index}" name="documents[${index}][document_file]" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="invalid-feedback file-feedback">Fișierul trebuie să fie PDF, JPG, PNG și maxim 5MB.</div>
                    <div class="file-upload-status" id="fileStatus_${index}">Niciun fișier selectat.</div>
                    <div class="progress mt-2" style="display: none;">
                        <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check mt-3 mt-md-0">
                        <input class="form-check-input" type="checkbox" value="1" id="docImportant_${index}" name="documents[${index}][important]">
                        <label class="form-check-label" for="docImportant_${index}">
                            <i class="bx bx-star me-1"></i> Marchează ca important (Notificări prioritare)
                        </label>
                    </div>
                </div>
            </div>

            <h6 class="section-header"><i class="bx bx-cog me-2"></i> Opțiuni Avansate (Opțional)</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="docNotes_${index}" class="form-label">Observații / Detalii suplimentare:</label>
                    <textarea class="form-control" id="docNotes_${index}" name="documents[${index}][observatii]" rows="3" placeholder="Adaugă detalii relevante despre document..."></textarea>
                </div>
                <div class="col-md-6">
                    <label for="docReference_${index}" class="form-label">Număr Referință Internă:</label>
                    <input type="text" class="form-control" id="docReference_${index}" name="documents[${index}][numar_referinta]" placeholder="Ex: INV-2024-001">
                </div>
            </div>
        `;
    };

    // Function to add a new document group to the DOM
    const addDocumentGroupToDOM = () => {
        const newDocumentGroup = document.createElement('div');
        newDocumentGroup.classList.add('form-group-card');
        newDocumentGroup.id = `documentGroup_${documentCounter}`;
        newDocumentGroup.innerHTML = generateDocumentGroupHTML(documentCounter);
        documentFieldsContainer.appendChild(newDocumentGroup);
        
        // Add event listener for file input validation on change for the new input
        const newFileInput = newDocumentGroup.querySelector(`#docFile_${documentCounter}`);
        if (newFileInput) {
            newFileInput.addEventListener('change', function() {
                validateFileInput(this, documentCounter);
            });
        }
        documentCounter++;
    };

    addMoreDocumentBtn.addEventListener('click', addDocumentGroupToDOM);

    // Global function for removing document groups
    window.removeDocumentGroup = function(groupId) {
        const groupToRemove = document.getElementById(groupId);
        if (groupToRemove) {
            groupToRemove.remove();
            // Re-index document numbers for display clarity
            updateDocumentNumbers();
        }
    };

    // Function to update the displayed numbers and input IDs/names after removal
    const updateDocumentNumbers = () => {
        const docGroups = documentFieldsContainer.querySelectorAll('.form-group-card');
        docGroups.forEach((group, index) => {
            // Update the main heading text (e.g., "Document 1" to "Document 2")
            const h5 = group.querySelector('h5');
            if (h5) {
                h5.innerHTML = `<i class="bx bx-receipt me-2"></i> Adaugă Document Nou (${index + 1})
                                <button type="button" class="btn-close text-white" aria-label="Șterge document" onclick="removeDocumentGroup('documentGroup_${index}')"></button>`;
            }
            
            // Update the group's ID
            group.id = `documentGroup_${index}`;

            // Update all form elements' IDs and names
            group.querySelectorAll('[id]').forEach(element => {
                const oldId = element.id;
                // Only update elements that belong to a document group's fields
                if (oldId.startsWith('docName_') || oldId.startsWith('docType_') || 
                    oldId.startsWith('docExpirationDate_') || oldId.startsWith('docFile_') || 
                    oldId.startsWith('docImportant_') || oldId.startsWith('fileStatus_') ||
                    oldId.startsWith('docNotes_') || oldId.startsWith('docReference_')) {
                    
                    const newId = oldId.replace(/_\d+$/, `_${index}`);
                    element.id = newId;

                    // Update 'name' attribute for inputs/selects/textareas
                    if (element.name) {
                        element.name = element.name.replace(/documents\[\d+\]/, `documents[${index}]`);
                    }
                    // Update 'for' attribute for labels
                    if (element.tagName === 'LABEL') {
                        element.htmlFor = newId;
                    }
                }
            });

            // Re-attach event listener for file input to the updated element
            const fileInput = group.querySelector(`#docFile_${index}`);
            if (fileInput) {
                fileInput.removeEventListener('change', arguments.callee); // Remove old listener if it exists
                fileInput.addEventListener('change', function() {
                    validateFileInput(this, index);
                });
            }
        });
        documentCounter = docGroups.length; // Reset documentCounter to the number of remaining groups, which will be the index for the next new group.
    };

    const validateFileInput = (fileInput, index) => {
        const feedbackDiv = fileInput.nextElementSibling; // invalid-feedback
        const statusDiv = document.getElementById(`fileStatus_${index}`);
        const progressBarContainer = fileInput.closest('.col-md-6').querySelector('.progress');
        const progressBar = fileInput.closest('.col-md-6').querySelector('.progress-bar');
        const file = fileInput.files[0];

        if (!file) {
            fileInput.classList.remove('is-invalid', 'is-valid');
            feedbackDiv.style.display = 'none';
            statusDiv.textContent = 'Niciun fișier selectat.';
            statusDiv.classList.remove('success', 'error');
            progressBarContainer.style.display = 'none';
            progressBar.style.width = '0%';
            return true; // No file selected
        }

        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        let isValid = true;
        let errorMessage = 'Fișierul trebuie să fie PDF, JPG, PNG și maxim 5MB.';

        if (!allowedTypes.includes(file.type)) {
            isValid = false;
            errorMessage = `Tipul fișierului pentru "${file.name}" nu este permis. Se acceptă doar PDF, JPG, PNG.`;
        } else if (file.size > maxSize) {
            isValid = false;
            errorMessage = `Dimensiunea fișierului pentru "${file.name}" depășește limita de 5MB.`;
        }

        if (!isValid) {
            fileInput.classList.add('is-invalid');
            fileInput.classList.remove('is-valid');
            feedbackDiv.textContent = errorMessage;
            feedbackDiv.style.display = 'block';
            statusDiv.textContent = `Eroare: ${errorMessage}`;
            statusDiv.classList.remove('success');
            statusDiv.classList.add('error');
            progressBarContainer.style.display = 'none';
            progressBar.style.width = '0%';
        } else {
            fileInput.classList.remove('is-invalid');
            fileInput.classList.add('is-valid');
            feedbackDiv.style.display = 'none';
            statusDiv.textContent = `Fișier selectat: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            statusDiv.classList.remove('error');
            statusDiv.classList.add('success');
            // Simulate progress bar (real progress needs server-side integration and AJAX)
            progressBarContainer.style.display = 'block';
            progressBar.style.width = '100%';
            progressBar.setAttribute('aria-valuenow', '100');
        }
        return isValid;
    };

    // Attach validation listener to the initial file input
    document.getElementById('docFile_0').addEventListener('change', function() {
        validateFileInput(this, 0);
    });

    // Validare la trimiterea formularului
    addDocumentsForm.addEventListener('submit', function(event) {
        let formValid = true;

        // Bootstrap's built-in validation for 'required' attributes
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            formValid = false;
        }

        // Custom validation for vehicle selection
        const selectVehicle = document.getElementById('selectVehicle');
        if (selectVehicle.value === "") {
            selectVehicle.classList.add('is-invalid');
            formValid = false;
        } else {
            selectVehicle.classList.remove('is-invalid');
            selectVehicle.classList.add('is-valid');
        }

        // Asigură-te că cel puțin un document este completat
        const allDocGroups = document.querySelectorAll('.form-group-card');
        if (allDocGroups.length === 0) {
            alert('Te rog adaugă cel puțin un document înainte de a salva.'); // Updated message
            event.preventDefault();
            formValid = false;
        }

        // Validate each document group, especially file inputs
        allDocGroups.forEach((group, index) => {
            const fileInput = group.querySelector('input[type="file"]');
            if (fileInput) {
                if (!validateFileInput(fileInput, index)) { // Pass index to validateFileInput
                    formValid = false;
                }
            }

            // Also trigger validation for other required fields in the dynamically added groups
            group.querySelectorAll('input[required], select[required]').forEach(field => {
                if (!field.checkValidity()) {
                    field.classList.add('is-invalid');
                    formValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
        });

        if (!formValid) {
            event.preventDefault(); // Prevent form submission if not valid
            // Add 'was-validated' class to show Bootstrap validation styles
            this.classList.add('was-validated');
        }
    });
});
</script>