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

// Preluăm intervalul de date selectat sau setăm implicit pe luna curentă
$start_date_str = $_GET['start_date'] ?? date('Y-m-01');
$end_date_str = $_GET['end_date'] ?? date('Y-m-t');

$start_date_dt = new DateTime($start_date_str);
$end_date_dt = new DateTime($end_date_str);

// Preluăm lista de angajați
$angajati_list = [];
$stmt_angajati = $conn->prepare("SELECT id, nume, prenume, functie, status FROM angajati ORDER BY nume ASC, prenume ASC");
if ($stmt_angajati) {
    $stmt_angajati->execute();
    $result_angajati = $stmt_angajati->get_result();
    while ($row = $result_angajati->fetch_assoc()) {
        $angajati_list[] = $row;
    }
    $stmt_angajati->close();
}

// Preluăm activitățile angajaților pentru perioada selectată
$employee_activities = [];

// Curse active/programate pentru perioada
$sql_curse = "
    SELECT ca.id_sofer, ca.data_inceput, ca.data_estimata_sfarsit, ca.locatie_plecare, ca.locatie_destinatie, ca.status
    FROM curse_active ca
    WHERE (ca.data_inceput BETWEEN ? AND ?) OR (ca.data_estimata_sfarsit BETWEEN ? AND ?) OR (ca.data_inceput <= ? AND ca.data_estimata_sfarsit >= ?)
";
$stmt_curse = $conn->prepare($sql_curse);
if ($stmt_curse) {
    $stmt_curse->bind_param("ssssss", $start_date_str, $end_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str);
    $stmt_curse->execute();
    $result_curse = $stmt_curse->get_result();
    while ($row = $result_curse->fetch_assoc()) {
        $employee_activities[$row['id_sofer']]['curse'][] = $row;
    }
    $stmt_curse->close();
}

// Rute planificate pentru perioada
$sql_rute = "
    SELECT pr.id_sofer, pr.data_plecare_estimata, pr.data_sosire_estimata, pr.nume_ruta, pr.locatie_start, pr.locatie_final, pr.status
    FROM planificare_rute pr
    WHERE (pr.data_plecare_estimata BETWEEN ? AND ?) OR (pr.data_sosire_estimata BETWEEN ? AND ?) OR (pr.data_plecare_estimata <= ? AND pr.data_sosire_estimata >= ?)
";
$stmt_rute = $conn->prepare($sql_rute);
if ($stmt_rute) {
    $stmt_rute->bind_param("ssssss", $start_date_str, $end_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str);
    $stmt_rute->execute();
    $result_rute = $stmt_rute->get_result();
    while ($row = $result_rute->fetch_assoc()) {
        $employee_activities[$row['id_sofer']]['rute'][] = $row;
    }
    $stmt_rute->close();
}

// Concedii/Absențe pentru perioada
$sql_concedii = "
    SELECT c.id_angajat, c.tip_concediu, c.data_inceput, c.data_sfarsit, c.status
    FROM concedii c
    WHERE c.status = 'Aprobat' AND ((c.data_inceput BETWEEN ? AND ?) OR (c.data_sfarsit BETWEEN ? AND ?) OR (c.data_inceput <= ? AND c.data_sfarsit >= ?))
";
$stmt_concedii = $conn->prepare($sql_concedii);
if ($stmt_concedii) {
    $stmt_concedii->bind_param("ssssss", $start_date_str, $end_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str);
    $stmt_concedii->execute();
    $result_concedii = $stmt_concedii->get_result();
    while ($row = $result_concedii->fetch_assoc()) {
        $employee_activities[$row['id_angajat']]['concedii'][] = $row;
    }
    $stmt_concedii->close();
}

$conn->close(); // Închidem conexiunea după toate operațiile DB

// Funcții și statusuri pentru filtrare
$functii_angajati = ['Sofer', 'Dispecer', 'Mecanic', 'Administrator', 'Contabil', 'Manager', 'Altele'];
$statusuri_angajati = ['Activ', 'Inactiv', 'Concediu', 'Suspendat', 'Demisionat'];
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Disponibilitate Grafică</title>

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

    /* Stiluri specifice pentru tabel */
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
    /* Badge-uri pentru statusul angajatului */
    .badge-status-activ { background-color: #28a745 !important; color: #fff !important; }
    .badge-status-inactiv { background-color: #6c757d !important; color: #fff !important; }
    .badge-status-concediu { background-color: #17a2b8 !important; color: #fff !important; }
    .badge-status-suspendat { background-color: #dc3545 !important; color: #fff !important; }
    .badge-status-demisionat { background-color: #ffc107 !important; color: #343a40 !important; }

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
            <div class="breadcrumb-title pe-3">Disponibilitate</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Disponibilitate Angajați</h4>
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

                        <!-- Secțiunea de Filtrare Perioadă și Angajat -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="startDate" class="form-label">Dată Început:</label>
                                <input type="date" class="form-control" id="startDate" value="<?php echo htmlspecialchars($start_date_str); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="endDate" class="form-label">Dată Sfârșit:</label>
                                <input type="date" class="form-control" id="endDate" value="<?php echo htmlspecialchars($end_date_str); ?>">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="applyDateFilterBtn">Aplică Filtru Dată</button>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterFunctie" class="form-label">Filtrează după Funcție:</label>
                                <select class="form-select" id="filterFunctie">
                                    <option value="all">Toate Funcțiile</option>
                                    <?php foreach ($functii_angajati as $functie): ?>
                                        <option value="<?php echo htmlspecialchars($functie); ?>"><?php echo htmlspecialchars($functie); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterStatus" class="form-label">Filtrează după Status Angajat:</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate Statusurile</option>
                                    <?php foreach ($statusuri_angajati as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="filterSearch" class="form-label">Caută Angajat:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="filterSearch" placeholder="Cauta nume, funcție...">
                                </div>
                            </div>
                        </div>

                        <!-- Butoane de Export -->
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" class="btn btn-primary me-2" id="exportPdfBtn"><i class="bx bxs-file-pdf"></i> Export PDF</button>
                            <button type="button" class="btn btn-success me-2" id="exportExcelBtn"><i class="bx bxs-file-excel"></i> Export Excel</button>
                            <button type="button" class="btn btn-info" id="printReportBtn"><i class="bx bx-printer"></i> Printează</button>
                        </div>

                        <!-- Tabelul de Disponibilitate -->
                        <?php if (empty($angajati_list)): ?>
                            <div class="alert alert-info">Nu există angajați înregistrați.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="availabilityTable">
                                    <thead>
                                        <tr>
                                            <th>Nume Angajat</th>
                                            <th>Funcție</th>
                                            <th>Status Angajat</th>
                                            <th>Activitate în Perioadă (<?php echo htmlspecialchars($start_date_dt->format('d.m.Y')); ?> - <?php echo htmlspecialchars($end_date_dt->format('d.m.Y')); ?>)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="availabilityTableBody">
                                        <?php foreach ($angajati_list as $angajat): 
                                            $has_activity = false;
                                            $activity_details = [];

                                            // Verifică curse
                                            if (isset($employee_activities[$angajat['id']]['curse'])) {
                                                foreach ($employee_activities[$angajat['id']]['curse'] as $cursa) {
                                                    $start = new DateTime($cursa['data_inceput']);
                                                    $end = $cursa['data_estimata_sfarsit'] ? new DateTime($cursa['data_estimata_sfarsit']) : null;
                                                    $activity_details[] = "Cursă: " . htmlspecialchars($cursa['locatie_plecare']) . " - " . htmlspecialchars($cursa['locatie_destinatie']) . " (" . htmlspecialchars($cursa['status']) . ") " . $start->format('d.m') . ($end ? "-" . $end->format('d.m') : '');
                                                    $has_activity = true;
                                                }
                                            }
                                            // Verifică rute
                                            if (isset($employee_activities[$angajat['id']]['rute'])) {
                                                foreach ($employee_activities[$angajat['id']]['rute'] as $ruta) {
                                                    $start = new DateTime($ruta['data_plecare_estimata']);
                                                    $end = $ruta['data_sosire_estimata'] ? new DateTime($ruta['data_sosire_estimata']) : null;
                                                    $activity_details[] = "Rută: " . htmlspecialchars($ruta['nume_ruta']) . " (" . htmlspecialchars($ruta['status']) . ") " . $start->format('d.m') . ($end ? "-" . $end->format('d.m') : '');
                                                    $has_activity = true;
                                                }
                                            }
                                            // Verifică concedii
                                            if (isset($employee_activities[$angajat['id']]['concedii'])) {
                                                foreach ($employee_activities[$angajat['id']]['concedii'] as $concediu) {
                                                    $start = new DateTime($concediu['data_inceput']);
                                                    $end = new DateTime($concediu['data_sfarsit']);
                                                    $activity_details[] = "Concediu: " . htmlspecialchars($concediu['tip_concediu']) . " (" . $start->format('d.m') . "-" . $end->format('d.m') . ")";
                                                    $has_activity = true;
                                                }
                                            }
                                        ?>
                                            <tr 
                                                data-id="<?php echo $angajat['id']; ?>"
                                                data-functie="<?php echo htmlspecialchars($angajat['functie']); ?>"
                                                data-status="<?php echo htmlspecialchars($angajat['status']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume'] . ' ' . $angajat['functie'] . ' ' . implode(' ', $activity_details))); ?>"
                                            >
                                                <td data-label="Nume Angajat:"><?php echo htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']); ?></td>
                                                <td data-label="Funcție:"><?php echo htmlspecialchars($angajat['functie']); ?></td>
                                                <td data-label="Status Angajat:"><span class="badge badge-status-<?php echo strtolower(str_replace(' ', '_', $angajat['status'])); ?>"><?php echo htmlspecialchars($angajat['status']); ?></span></td>
                                                <td data-label="Activitate în Perioadă:">
                                                    <?php if ($has_activity): ?>
                                                        <ul class="list-unstyled mb-0">
                                                            <?php foreach ($activity_details as $detail): ?>
                                                                <li><?php echo $detail; ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Disponibil</span>
                                                    <?php endif; ?>
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

<?php require_once 'template/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const applyDateFilterBtn = document.getElementById('applyDateFilterBtn');
    const filterFunctie = document.getElementById('filterFunctie');
    const filterStatus = document.getElementById('filterStatus');
    const filterSearch = document.getElementById('filterSearch');
    const availabilityTableBody = document.getElementById('availabilityTableBody');

    // Functie pentru a aplica filtrele de tabel
    function filterTable() {
        const selectedFunctie = filterFunctie.value;
        const selectedStatus = filterStatus.value;
        const searchText = filterSearch.value.toLowerCase().trim();

        document.querySelectorAll('#availabilityTableBody tr').forEach(row => {
            const rowFunctie = row.getAttribute('data-functie');
            const rowStatus = row.getAttribute('data-status');
            const rowSearchText = row.getAttribute('data-search-text');

            const functieMatch = (selectedFunctie === 'all' || rowFunctie === selectedFunctie);
            const statusMatch = (selectedStatus === 'all' || rowStatus === selectedStatus);
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (functieMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterFunctie.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterSearch.addEventListener('input', filterTable);
    filterTable(); // Rulează la încărcarea paginii

    // Functie pentru a reincarca pagina cu noul interval de date
    applyDateFilterBtn.addEventListener('click', function() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        window.location.href = `disponibilitate-grafica.php?start_date=${startDate}&end_date=${endDate}`;
    });

    // Functii de Export si Print (similare cu raport-flota-zilnic.php)
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4');
        doc.setFont('Noto Sans', 'normal');

        const title = `Raport Disponibilitate Angajați - ${startDateInput.value} - ${endDateInput.value}`;
        const headers = [];
        document.querySelectorAll('#availabilityTable thead th').forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll('#availabilityTableBody tr').forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach(td => {
                const badgeSpan = td.querySelector('.badge');
                if (badgeSpan) {
                    rowData.push(badgeSpan.textContent);
                } else {
                    rowData.push(td.textContent);
                }
            });
            data.push(rowData);
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

        doc.save(`Raport_Disponibilitate_Angajati_${startDateInput.value}_${endDateInput.value}.pdf`);
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('availabilityTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Disponibilitate Angajati");
        XLSX.writeFile(wb, `Raport_Disponibilitate_Angajati_${startDateInput.value}_${endDateInput.value}.xlsx`);
    });

    document.getElementById('printReportBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const printContent = `
            <html>
            <head>
                <title>Raport Disponibilitate Angajați - ${startDateInput.value} - ${endDateInput.value}</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
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
                    .badge-status-activ { background-color: #28a745; color: #fff; }
                    .badge-status-inactiv { background-color: #6c757d; color: #fff; }
                    .badge-status-concediu { background-color: #17a2b8; color: #fff; }
                    .badge-status-suspendat { background-color: #dc3545; color: #fff; }
                    .badge-status-demisionat { background-color: #ffc107; color: #343a40; }
                    .bg-success { background-color: #28a745; color: #fff; }
                </style>
            </head>
            <body>
                <h1>Raport Disponibilitate Angajați - ${startDateInput.value} - ${endDateInput.value}</h1>
                ${document.getElementById('availabilityTable').outerHTML}
            </body>
            </html>
        `;
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    });
});
</script>
