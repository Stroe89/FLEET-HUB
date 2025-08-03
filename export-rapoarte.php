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

// Lista tipurilor de rapoarte disponibile pentru export
$report_types = [
    'raport-flota-zilnic.php' => 'Raport Flotă Zilnic',
    'raport-flota-lunar.php' => 'Raport Flotă Lunar',
    'raport-financiar.php' => 'Raport Financiar',
    'raport-consum-combustibil.php' => 'Raport Consum Combustibil',
    'raport-cost-km.php' => 'Raport Cost/KM'
];

// Setează datele implicite pentru filtre
$default_start_date = date('Y-m-01');
$default_end_date = date('Y-m-t');
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Export Rapoarte</title>

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
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Rapoarte</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Export Rapoarte</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Export Rapoarte</h4>
                        <p class="text-muted">Selectează tipul de raport, intervalul de date și formatul de export.</p>
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

                        <form id="exportReportForm" class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="reportType" class="form-label">Tip Raport:</label>
                                <select class="form-select" id="reportType" required>
                                    <option value="">Selectează un raport</option>
                                    <?php foreach ($report_types as $file => $name): ?>
                                        <option value="<?php echo htmlspecialchars($file); ?>"><?php echo htmlspecialchars($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="startDate" class="form-label">Dată Început:</label>
                                <input type="date" class="form-control" id="startDate" value="<?php echo htmlspecialchars($default_start_date); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="endDate" class="form-label">Dată Sfârșit:</label>
                                <input type="date" class="form-control" id="endDate" value="<?php echo htmlspecialchars($default_end_date); ?>">
                            </div>
                            
                            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-primary" id="exportPdfBtn"><i class="bx bxs-file-pdf"></i> Export PDF</button>
                                <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel"></i> Export Excel</button>
                                <button type="button" class="btn btn-info" id="printReportBtn"><i class="bx bx-printer"></i> Printează</button>
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
    const reportTypeSelect = document.getElementById('reportType');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const exportPdfBtn = document.getElementById('exportPdfBtn');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const printReportBtn = document.getElementById('printReportBtn');

    function generateExportUrl(format) {
        const reportFile = reportTypeSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!reportFile) {
            alert('Te rog să selectezi un tip de raport.');
            return null;
        }

        let url = `${reportFile}?start_date=${startDate}&end_date=${endDate}&export_format=${format}`;
        
        // Pentru rapoartele lunare, ajustăm parametrul de dată
        if (reportFile === 'raport-flota-lunar.php') {
            const selectedMonthYear = new Date(startDate).toISOString().substring(0, 7); // YYYY-MM
            url = `${reportFile}?month=${selectedMonthYear}&export_format=${format}`;
        }
        // Pentru raportul zilnic, folosim doar start_date ca 'date'
        if (reportFile === 'raport-flota-zilnic.php') {
            url = `${reportFile}?date=${startDate}&export_format=${format}`;
        }

        return url;
    }

    exportPdfBtn.addEventListener('click', function() {
        const url = generateExportUrl('pdf');
        if (url) {
            window.open(url, '_blank');
        }
    });

    exportExcelBtn.addEventListener('click', function() {
        const url = generateExportUrl('excel');
        if (url) {
            window.open(url, '_blank');
        }
    });

    printReportBtn.addEventListener('click', function() {
        const url = generateExportUrl('print');
        if (url) {
            window.open(url, '_blank');
        }
    });
});
</script>
