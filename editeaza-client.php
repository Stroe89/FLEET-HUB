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

// Preluăm ID-ul clientului din URL
$id_client = $_GET['id'] ?? null;
$client = null; // Variabila pentru a stoca datele clientului

if ($id_client && is_numeric($id_client)) {
    // Preluăm datele clientului din baza de date
    $stmt = $conn->prepare("SELECT * FROM clienti WHERE id = ?");
    if ($stmt === false) {
        die("Eroare la pregătirea interogării: " . $conn->error);
    }
    $stmt->bind_param("i", $id_client);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $client = $result->fetch_assoc();
    } else {
        $error_message = "Clientul nu a fost găsit.";
    }
    $stmt->close();
} else {
    $error_message = "ID client invalid sau lipsă.";
}

// Dacă clientul nu a fost găsit, inițializăm cu valori goale pentru a evita erori PHP
if (!$client) {
    $client = [
        'nume_companie' => '',
        'persoana_contact' => '',
        'telefon' => '',
        'email' => '',
        'adresa' => '',
        'cui' => '',
        'nr_reg_com' => '',
        'observatii' => ''
    ];
}

$conn->close();
?>

<title>NTS TOUR | Editează Client</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
    /* Stiluri specifice pentru formular */
    .input-group-text {
        background-color: #3b435a !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: #e0e0e0 !important;
        border-radius: 0.5rem 0 0 0.5rem !important; /* Doar stânga rotunjit */
    }
    .input-group > .form-control,
    .input-group > .form-select {
        border-radius: 0 0.5rem 0.5rem 0 !important; /* Doar dreapta rotunjit */
    }
    /* Bootstrap Toast Container */
    .toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1080; /* Higher than modals if needed */
    }
    .toast {
        background-color: #2a3042;
        color: #e0e0e0;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .toast-header {
        background-color: #3b435a;
        color: #ffffff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .toast-body {
        background-color: #2a3042;
        color: #e0e0e0;
    }
    .toast-success .toast-header { background-color: #2c5234; color: #fff; }
    .toast-danger .toast-header { background-color: #5c2c31; color: #fff; }
    .toast-info .toast-header { background-color: #203354; color: #fff; }

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
                        <li class="breadcrumb-item active" aria-current="page">Editează Client</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Editează Client: <?php echo htmlspecialchars($client['nume_companie'] ?? $client['persoana_contact']); ?></h4>
                        <hr>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!$client['nume_companie'] && !$client['persoana_contact']): // Verificăm dacă clientul a fost găsit ?>
                            <div class="alert alert-warning" role="alert">
                                Clientul nu a fost găsit sau ID-ul este invalid. Te rugăm să te întorci la <a href="fise-clienti.php" class="alert-link">lista de clienți</a>.
                            </div>
                        <?php else: ?>
                            <form id="editClientForm" action="process_clienti.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_client); ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="numeCompanie" class="form-label">Nume Companie:</label>
                                        <input type="text" class="form-control" id="numeCompanie" name="nume_companie" value="<?php echo htmlspecialchars($client['nume_companie']); ?>" required>
                                        <div class="invalid-feedback">Te rog introdu numele companiei.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="persoanaContact" class="form-label">Persoană Contact:</label>
                                        <input type="text" class="form-control" id="persoanaContact" name="persoana_contact" value="<?php echo htmlspecialchars($client['persoana_contact']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cui" class="form-label">CUI:</label>
                                        <input type="text" class="form-control" id="cui" name="cui" value="<?php echo htmlspecialchars($client['cui']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nrRegCom" class="form-label">Nr. Reg. Com.:</label>
                                        <input type="text" class="form-control" id="nrRegCom" name="nr_reg_com" value="<?php echo htmlspecialchars($client['nr_reg_com']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telefon" class="form-label">Telefon:</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                            <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($client['telefon']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email:</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>">
                                        </div>
                                        <div class="invalid-feedback">Te rog introdu o adresă de email validă.</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="adresa" class="form-label">Adresă:</label>
                                        <textarea class="form-control" id="adresa" name="adresa" rows="2"><?php echo htmlspecialchars($client['adresa']); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="observatii" class="form-label">Observații:</label>
                                        <textarea class="form-control" id="observatii" name="observatii" rows="3"><?php echo htmlspecialchars($client['observatii']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="fise-clienti.php" class="btn btn-secondary">Anulează</a>
                                    <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Container pentru Toast-uri -->
<div class="toast-container"></div>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcție pentru afișarea Toast-urilor Bootstrap
    function showToast(type, message) {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            const body = document.querySelector('body');
            const newToastContainer = document.createElement('div');
            newToastContainer.classList.add('toast-container');
            newToastContainer.style.position = 'fixed';
            newToastContainer.style.top = '1rem';
            newToastContainer.style.right = '1rem';
            newToastContainer.style.zIndex = '1080';
            body.appendChild(newToastContainer);
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
        document.querySelector('.toast-container').insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    const editClientForm = document.getElementById('editClientForm');
    if (editClientForm) {
        editClientForm.addEventListener('submit', function(event) {
            event.preventDefault();

            if (!editClientForm.checkValidity()) {
                event.stopPropagation();
                editClientForm.classList.add('was-validated');
                showToast('danger', 'Te rog completează toate câmpurile obligatorii.');
                return;
            }
            editClientForm.classList.add('was-validated');

            const formData = new FormData(editClientForm);
            formData.append('action', 'edit'); // Acțiunea este 'edit'

            fetch('process_clienti.php', { // Același fișier de procesare ca la adăugare
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
                    // Redirecționează către fișa clientului după editare
                    setTimeout(() => {
                        window.location.href = `fise-clienti.php?id=${formData.get('id')}`;
                    }, 1500);
                } else {
                    showToast('danger', data.message || 'A apărut o eroare la salvarea clientului.');
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
