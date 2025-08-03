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

// Preluăm intervalul de date selectat sau setăm implicit pe ultima lună
$start_date_str = $_GET['start_date'] ?? date('Y-m-01', strtotime('-1 month'));
$end_date_str = $_GET['end_date'] ?? date('Y-m-t');

// --- Logică pentru Preluarea Datelor Campaniilor Trimise ---
$sent_campaigns = [];
$total_sent = 0;
$total_opens = 0;
$total_clicks = 0;

// Preluăm campaniile trimise în intervalul de date specificat
$sql_sent_campaigns = "
    SELECT 
        id, nume_campanie, subiect, data_trimitere, status, 
        numar_destinatari, numar_deschideri, numar_clickuri
    FROM 
        campanii_newsletter
    WHERE 
        status = 'trimisa' AND data_trimitere BETWEEN ? AND ?
    ORDER BY 
        data_trimitere DESC
";
$stmt_sent_campaigns = $conn->prepare($sql_sent_campaigns);
if ($stmt_sent_campaigns) {
    $stmt_sent_campaigns->bind_param("ss", $start_date_str, $end_date_str);
    $stmt_sent_campaigns->execute();
    $result_sent_campaigns = $stmt_sent_campaigns->get_result();
    while ($row = $result_sent_campaigns->fetch_assoc()) {
        $sent_campaigns[] = $row;
        $total_sent += $row['numar_destinatari'];
        $total_opens += $row['numar_deschideri'];
        $total_clicks += $row['numar_clickuri'];
    }
    $stmt_sent_campaigns->close();
}

$open_rate_avg = ($total_sent > 0) ? ($total_opens / $total_sent) * 100 : 0;
$click_rate_avg = ($total_sent > 0) ? ($total_clicks / $total_sent) * 100 : 0;


$conn->close(); // Închidem conexiunea la baza de date
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Campanii Trimise</title>

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

    /* Stiluri specifice pentru campanii trimise */
    .stat-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
    }
    .stat-card .card-body {
        background-color: #2a3042 !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem;
    }
    .stat-card .widgets-icons {
        font-size: 2.5rem !important;
        opacity: 0.7 !important;
        padding: 10px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.08);
    }
    .stat-card.border-left-info { border-left-color: #007bff !important; }
    .stat-card.border-left-success { border-left-color: #28a745 !important; }
    .stat-card.border-left-warning { border-left-color: #ffc107 !important; }
    .stat-card.border-left-danger { border-left-color: #dc3545 !important; }
    .stat-card.border-left-primary { border-left-color: #0d6efd !important; }

    /* Stiluri pentru butoane */
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        font-weight: bold !important;
        padding: 0.8rem 1.5rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out !important, box-shadow 0.2s ease-in-out !important;
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

    /* Stiluri pentru badge-uri */
    .badge {
        padding: 0.4em 0.7em;
        border-radius: 0.3rem;
        font-size: 0.85em;
        font-weight: 600;
    }
    .badge.bg-warning { background-color: #ffc107 !important; color: #343a40 !important; }
    .badge.bg-danger { background-color: #dc3545 !important; color: #fff !important; }
    .badge.bg-success { background-color: #28a745 !important; color: #fff !important; }
    .badge.bg-info { background-color: #17a2b8 !important; color: #fff !important; }

    /* Stiluri pentru tabel */
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
            <div class="breadcrumb-title pe-3">Newsletter</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Campanii Trimise</li>
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

        <!-- Statistici Cheie Campanii Trimise -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
            <div class="col">
                <div class="card stat-card border-left-info">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Total Campanii Trimise</p><h4 class="my-1"><?php echo number_format(count($sent_campaigns), 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-paper-plane"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-success">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Total Deschideri</p><h4 class="my-1"><?php echo number_format($total_opens, 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bx-envelope-open"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-warning">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Total Click-uri</p><h4 class="my-1"><?php echo number_format($total_clicks, 0, ',', '.'); ?></h4></div>
                        <div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bx-mouse"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card border-left-primary">
                    <div class="card-body">
                        <div><p class="mb-0 text-secondary">Rata Medie Deschidere</p><h4 class="my-1"><?php echo number_format($open_rate_avg, 2, ',', '.'); ?>%</h4></div>
                        <div class="widgets-icons bg-light-primary text-primary ms-auto"><i class="bx bx-line-chart"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtre și Butoane de Export -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <label for="startDate" class="form-label">Dată Început:</label>
                <input type="date" class="form-control" id="startDate" value="<?php echo htmlspecialchars($start_date_str); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="endDate" class="form-label">Dată Sfârșit:</label>
                <input type="date" class="form-control" id="endDate" value="<?php echo htmlspecialchars($end_date_str); ?>">
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100 me-2" id="applyDateFilterBtn">Aplică Filtru Dată</button>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" class="btn btn-primary me-2" id="exportPdfBtn"><i class="bx bxs-file-pdf"></i> Export PDF</button>
                <button type="button" class="btn btn-success me-2" id="exportExcelBtn"><i class="bx bxs-file-excel"></i> Export Excel</button>
                <button type="button" class="btn btn-info" id="printReportBtn"><i class="bx bx-printer"></i> Printează</button>
            </div>
        </div>

        <!-- Listă Campanii Trimise -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Detalii Campanii Trimise</h4>
                        <hr>
                        <?php if (empty($sent_campaigns)): ?>
                            <div class="alert alert-info">Nu există campanii trimise în perioada selectată.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="sentCampaignsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nume Campanie</th>
                                            <th>Subiect</th>
                                            <th>Dată Trimitere</th>
                                            <th>Destinatari</th>
                                            <th>Deschideri</th>
                                            <th>Click-uri</th>
                                            <th>Rată Deschidere</th>
                                            <th>Rată Click</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sent_campaigns as $campaign): ?>
                                            <tr>
                                                <td data-label="ID:"><?php echo htmlspecialchars($campaign['id']); ?></td>
                                                <td data-label="Nume Campanie:"><?php echo htmlspecialchars($campaign['nume_campanie']); ?></td>
                                                <td data-label="Subiect:"><?php echo htmlspecialchars(mb_strimwidth($campaign['subiect'], 0, 50, "...")); ?></td>
                                                <td data-label="Dată Trimitere:"><?php echo (new DateTime($campaign['data_trimitere']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Destinatari:"><?php echo number_format($campaign['numar_destinatari'], 0, ',', '.'); ?></td>
                                                <td data-label="Deschideri:"><?php echo number_format($campaign['numar_deschideri'], 0, ',', '.'); ?></td>
                                                <td data-label="Click-uri:"><?php echo number_format($campaign['numar_clickuri'], 0, ',', '.'); ?></td>
                                                <td data-label="Rată Deschidere:">
                                                    <?php 
                                                        $open_rate = ($campaign['numar_destinatari'] > 0) ? ($campaign['numar_deschideri'] / $campaign['numar_destinatari']) * 100 : 0;
                                                        $open_rate_class = 'bg-info';
                                                        if ($open_rate >= 20) $open_rate_class = 'bg-success';
                                                        else if ($open_rate < 10) $open_rate_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $open_rate_class; ?>"><?php echo number_format($open_rate, 2, ',', '.'); ?>%</span>
                                                </td>
                                                <td data-label="Rată Click:">
                                                    <?php 
                                                        $click_rate = ($campaign['numar_destinatari'] > 0) ? ($campaign['numar_clickuri'] / $campaign['numar_destinatari']) * 100 : 0;
                                                        $click_rate_class = 'bg-info';
                                                        if ($click_rate >= 2) $click_rate_class = 'bg-success';
                                                        else if ($click_rate < 1) $click_rate_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $click_rate_class; ?>"><?php echo number_format($click_rate, 2, ',', '.'); ?>%</span>
                                                </td>
                                                <td>
                                                    <a href="creeaza-newsletter.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-primary me-2"><i class="bx bx-edit"></i> Editează</a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-campaign-btn" data-id="<?php echo $campaign['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
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

<!-- Modal Confirmă Ștergere Campanie -->
<div class="modal fade" id="deleteCampaignModal" tabindex="-1" aria-labelledby="deleteCampaignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCampaignModalLabel">Confirmă Ștergerea Campaniei</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi campania <strong id="deleteCampaignNameDisplay"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteCampaignId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCampaignBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const applyDateFilterBtn = document.getElementById('applyDateFilterBtn');
    const deleteCampaignModal = document.getElementById('deleteCampaignModal');
    const confirmDeleteCampaignBtn = document.getElementById('confirmDeleteCampaignBtn');
    let currentCampaignIdToDelete = null;

    // Functie pentru a reincarca pagina cu noul interval de date
    applyDateFilterBtn.addEventListener('click', function() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        window.location.href = `campanii-trimise.php?start_date=${startDate}&end_date=${endDate}`;
    });

    // Functii de Export si Print
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const title = `Raport Campanii Trimise - Perioada: ${startDateInput.value} - ${endDateInput.value}`;
        
        // Adăugăm statistici sumare
        const summaryHtml = `
            <h3 style="color:#333;">Statistici Campanii Trimise</h3>
            <p><strong>Total Campanii Trimise:</strong> ${document.querySelector('.stat-card:nth-child(1) h4').textContent}</p>
            <p><strong>Total Deschideri:</strong> ${document.querySelector('.stat-card:nth-child(2) h4').textContent}</p>
            <p><strong>Total Click-uri:</strong> ${document.querySelector('.stat-card:nth-child(3) h4').textContent}</p>
            <p><strong>Rata Medie Deschidere:</strong> ${document.querySelector('.stat-card:nth-child(4) h4').textContent}</p>
            <br>
        `;
        
        doc.html(summaryHtml, {
            callback: function (doc) {
                // Adăugăm tabelul principal
                doc.addPage(); // Adaugă o pagină nouă pentru tabel
                doc.text("Detalii Campanii Trimise", 40, 40);
                doc.autoTable({
                    startY: 60,
                    html: '#sentCampaignsTable',
                    theme: 'striped',
                    styles: { font: 'Noto Sans', fontSize: 8, cellPadding: 5, valign: 'middle', overflow: 'linebreak' },
                    headStyles: { fillColor: [59, 67, 90], textColor: [255, 255, 255], fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [42, 48, 66] },
                    bodyStyles: { textColor: [224, 224, 224] }
                });

                doc.save(`Raport_Campanii_Trimise_${startDateInput.value}_${endDateInput.value}.pdf`);
            },
            x: 10,
            y: 10,
            width: 580,
            windowWidth: 794
        });
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const wb = XLSX.utils.book_new();

        // Sheet 1: Sumar
        const summaryData = [
            ["Raport Campanii Trimise Sumar"],
            ["Perioada:", `${startDateInput.value} - ${endDateInput.value}`],
            [],
            ["Metrica", "Valoare"],
            ["Total Campanii Trimise", document.querySelector('.stat-card:nth-child(1) h4').textContent],
            ["Total Deschideri", document.querySelector('.stat-card:nth-child(2) h4').textContent],
            ["Total Click-uri", document.querySelector('.stat-card:nth-child(3) h4').textContent],
            ["Rata Medie Deschidere", document.querySelector('.stat-card:nth-child(4) h4').textContent]
        ];
        const ws_summary = XLSX.utils.aoa_to_sheet(summaryData);
        XLSX.utils.book_append_sheet(wb, ws_summary, "Sumar Campanii");

        // Sheet 2: Detalii Campanii Trimise
        const campaignsTable = document.getElementById('sentCampaignsTable');
        if (campaignsTable) {
            const ws_campaigns = XLSX.utils.table_to_sheet(campaignsTable);
            XLSX.utils.book_append_sheet(wb, ws_campaigns, "Campanii Trimise");
        }
        
        XLSX.writeFile(wb, `Raport_Campanii_Trimise_${startDateInput.value}_${endDateInput.value}.xlsx`);
    });

    document.getElementById('printReportBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const printContent = `
            <html>
            <head>
                <title>Raport Campanii Trimise - Perioada: ${startDateInput.value} - ${endDateInput.value}</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1, h3 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .badge { 
                        display: inline-block; 
                        padding: 0.25em 0.4em; 
                        font-size: 75%; 
                        font-weight: 700; 
                        line-height: 1; 
                        text-align: center; 
                        white-space: nowrap; 
                        vertical-align: baseline; 
                        border-radius: 0.25rem; 
                    }
                    .badge.bg-warning { background-color: #ffc107; color: #343a40; }
                    .badge.bg-danger { background-color: #dc3545; color: #fff; }
                    .badge.bg-success { background-color: #28a745; color: #fff; }
                    .badge.bg-info { background-color: #17a2b8; color: #fff; }
                </style>
            </head>
            <body>
                <h1>Raport Campanii Trimise - Perioada: ${startDateInput.value} - ${endDateInput.value}</h1>
                <h3>Statistici Campanii Trimise</h3>
                <p><strong>Total Campanii Trimise:</strong> ${document.querySelector('.stat-card:nth-child(1) h4').textContent}</p>
                <p><strong>Total Deschideri:</strong> ${document.querySelector('.stat-card:nth-child(2) h4').textContent}</p>
                <p><strong>Total Click-uri:</strong> ${document.querySelector('.stat-card:nth-child(3) h4').textContent}</p>
                <p><strong>Rata Medie Deschidere:</strong> ${document.querySelector('.stat-card:nth-child(4) h4').textContent}</p>
                <br>
                <h3>Detalii Campanii Trimise</h3>
                ${document.getElementById('sentCampaignsTable') ? document.getElementById('sentCampaignsTable').outerHTML : '<p>Nu există campanii trimise.</p>'}
            </body>
            </html>
        `;
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    });

    // --- Logică pentru Ștergere Campanie ---
    const deleteCampaignModal = document.getElementById('deleteCampaignModal');
    const confirmDeleteCampaignBtn = document.getElementById('confirmDeleteCampaignBtn');
    let currentCampaignIdToDelete = null;

    document.querySelectorAll('.delete-campaign-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentCampaignIdToDelete = this.dataset.id;
            const campaignName = this.closest('tr').querySelector('td:nth-child(2)').textContent;
            document.getElementById('deleteCampaignNameDisplay').textContent = campaignName;
            new bootstrap.Modal(deleteCampaignModal).show();
        });
    });

    confirmDeleteCampaignBtn.addEventListener('click', function() {
        if (currentCampaignIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_campaign');
            formData.append('id', currentCampaignIdToDelete);

            fetch('process_newsletter.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); // Reîncarcă pagina pentru a actualiza lista
                } else {
                    alert('Eroare: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea campaniei:', error);
                alert('A apărut o eroare la ștergerea campaniei.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(deleteCampaignModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
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