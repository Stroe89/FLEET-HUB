<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mesaje de succes sau eroare din sesiune (pentru consistență)
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

// Tipuri de date disponibile pentru export
$tipuri_export = ['Facturi', 'Încasări', 'Plăți', 'Cheltuieli'];

// Anii disponibili pentru filtrare (exemplu: ultimii 5 ani)
$current_year = date('Y');
$ani_disponibili = [];
for ($i = $current_year; $i >= $current_year - 5; $i--) {
    $ani_disponibili[] = $i;
}

$conn->close();
?>

<title>NTS TOUR | Export Contabilitate</title>

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

    /* Stiluri specifice pentru secțiunea de export */
    .export-options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    .export-options-grid .btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .export-options-grid .btn i {
        margin-right: 0.5rem;
        font-size: 1.2rem;
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Contabilitate</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Export Contabilitate</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Export Date Contabilitate</h4>
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

                        <form id="exportForm" action="process_export.php" method="GET"> <!-- Va fi un script dedicat de procesare export -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="exportType" class="form-label">Tip Date:</label>
                                    <select class="form-select" id="exportType" name="type" required>
                                        <option value="">Selectează tipul</option>
                                        <?php foreach ($tipuri_export as $tip): ?>
                                            <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="startDate" class="form-label">Dată Început:</label>
                                    <input type="date" class="form-control" id="startDate" name="start_date">
                                </div>
                                <div class="col-md-4">
                                    <label for="endDate" class="form-label">Dată Sfârșit:</label>
                                    <input type="date" class="form-control" id="endDate" name="end_date">
                                </div>
                            </div>

                            <h5 class="card-title mt-4">Formate de Export</h5>
                            <hr>
                            <div class="export-options-grid">
                                <button type="submit" class="btn btn-success" name="format" value="csv">
                                    <i class="bx bxs-file-csv"></i> Export CSV
                                </button>
                                <button type="submit" class="btn btn-danger" name="format" value="pdf">
                                    <i class="bx bxs-file-pdf"></i> Export PDF
                                </button>
                                <button type="submit" class="btn btn-primary" name="format" value="excel">
                                    <i class="bx bxs-file-excel"></i> Export Excel
                                </button>
                                <!-- Alte butoane de export, ex: pentru integrare ERP -->
                            </div>
                            <p class="text-muted mt-3">
                                * Notă: Funcționalitățile de export PDF și Excel necesită biblioteci PHP dedicate (ex: FPDF/TCPDF pentru PDF, PhpSpreadsheet pentru Excel) și o implementare complexă de backend. Acestea sunt indicate ca placeholder.
                            </p>
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
    const exportForm = document.getElementById('exportForm');
    const exportType = document.getElementById('exportType');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');

    // Setează datele implicite (ex: luna curentă)
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    startDate.value = firstDayOfMonth.toISOString().substring(0, 10);
    endDate.value = lastDayOfMonth.toISOString().substring(0, 10);

    // Validare simplă înainte de export (opțional)
    exportForm.addEventListener('submit', function(event) {
        if (exportType.value === '') {
            alert('Te rog selectează un tip de date pentru export.');
            event.preventDefault();
            return;
        }
        if (startDate.value && endDate.value && new Date(startDate.value) > new Date(endDate.value)) {
            alert('Data de început nu poate fi după data de sfârșit.');
            event.preventDefault();
            return;
        }
        // Aici poți adăuga logica reală de trimitere a cererii de export
        // De exemplu, poți schimba action-ul formularului sau poți face un fetch
        // Pentru simplificare, formularul se va trimite către process_export.php cu parametrii GET
    });
});
</script>
