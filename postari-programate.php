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

// --- Preluare Postări Programate ---
$scheduled_posts = [];
$platforms_available = ['facebook', 'instagram', 'tiktok', 'whatsapp', 'telegram'];

if (tableExists($conn, 'marketing_posts')) {
    $sql_scheduled_posts = "SELECT id, titlu_postare, continut_text, continut_html, platforme_selectate, data_programare, status FROM marketing_posts WHERE status = 'programata' ORDER BY data_programare ASC";
    $result_scheduled_posts = $conn->query($sql_scheduled_posts);
    if ($result_scheduled_posts) {
        while ($row = $result_scheduled_posts->fetch_assoc()) {
            $scheduled_posts[] = $row;
        }
    }
} else {
    // Date mock dacă tabelul nu există
    $scheduled_posts = [
        ['id' => 1, 'titlu_postare' => 'Promoția de Toamnă (Mock)', 'continut_text' => 'Nu ratați noile oferte de toamnă!', 'continut_html' => '<h1>Promoție de Toamnă!</h1><p>Detalii...</p>', 'platforme_selectate' => '["facebook", "instagram"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('+2 days')), 'status' => 'programata'],
        ['id' => 2, 'titlu_postare' => 'Webinar Gratuit (Mock)', 'continut_text' => 'Înscrie-te la webinarul nostru despre optimizarea flotei.', 'continut_html' => '<p>Link de înregistrare...</p>', 'platforme_selectate' => '["facebook", "linkedin"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('+5 days')), 'status' => 'programata'],
        ['id' => 3, 'titlu_postare' => 'Noutăți Produse (Mock)', 'continut_text' => 'Am lansat noi produse și servicii!', 'continut_html' => '<p>Descoperă noutățile!</p>', 'platforme_selectate' => '["tiktok", "instagram"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('+10 days')), 'status' => 'programata'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Postări Programate</title>

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
    /* Stiluri pentru butoanele de acțiune din tabel */
    .table .btn-sm {
        padding: 0.3rem 0.6rem !important;
        font-size: 0.8rem !important;
        width: auto !important;
    }
    .table .badge {
        padding: 0.4em 0.7em;
        border-radius: 0.3rem;
        font-size: 0.85em;
        font-weight: 600;
    }
    .table .badge.bg-warning { background-color: #ffc107 !important; color: #343a40 !important; }
    .table .badge.bg-success { background-color: #28a745 !important; color: #fff !important; }
    .table .badge.bg-danger { background-color: #dc3545 !important; color: #fff !important; }
    .table .badge.bg-secondary { background-color: #6c757d !important; color: #fff !important; }

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
    /* Stiluri pentru selectorul de platforme în modal (dacă e cazul) */
    .platform-selector .form-check-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease;
        background-color: #1a2035;
        color: #e0e0e0;
        height: 100px;
    }
    .platform-selector .form-check-input:checked + .form-check-label {
        background-color: var(--primary-accent-color);
        border-color: var(--primary-accent-color);
        color: #fff;
    }
    .platform-selector .form-check-label i {
        font-size: 2.5rem;
        margin-bottom: 5px;
    }
    .platform-selector .form-check-label span {
        font-size: 0.9em;
        font-weight: bold;
    }
    .platform-selector .form-check-label .bx.bxl-facebook-square { color: #1877F2; }
    .platform-selector .form-check-label .bx.bxl-instagram { color: #E4405F; }
    .platform-selector .form-check-label .bx.bxl-tiktok { color: #000; filter: invert(1); }
    .platform-selector .form-check-label .bx.bxl-whatsapp { color: #25D366; }
    .platform-selector .form-check-label .bx.bxl-telegram { color: #0088CC; }
    .platform-selector .form-check-input:checked + .form-check-label .bx { color: #fff; }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Marketing (Social Manager)</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Postări Programate</li>
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
                        <h4 class="card-title">Listă Postări Programate</h4>
                        <p class="text-muted">Vizualizează și gestionează postările de social media programate pentru publicare.</p>
                        <hr>

                        <!-- Filtre -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="filterPlatform" class="form-label">Filtrează după Platformă:</label>
                                <select class="form-select" id="filterPlatform">
                                    <option value="all">Toate Platformele</option>
                                    <?php foreach ($platforms_available as $platform): ?>
                                        <option value="<?php echo htmlspecialchars($platform); ?>"><?php echo htmlspecialchars(ucfirst($platform)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="searchPost" class="form-label">Căutare:</label>
                                <input type="text" class="form-control" id="searchPost" placeholder="Căutare după titlu...">
                            </div>
                        </div>

                        <!-- Butoane de Acțiuni -->
                        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                            <a href="creeaza-postare-noua.php" class="btn btn-primary"><i class="bx bx-plus me-2"></i>Creează Postare Nouă</a>
                            <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel me-2"></i>Export Excel</button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="bx bxs-file-pdf me-2"></i>Export PDF</button>
                            <button type="button" class="btn btn-info" id="printListBtn"><i class="bx bx-printer me-2"></i>Printează</button>
                        </div>

                        <!-- Tabelul cu Postări Programate -->
                        <?php if (empty($scheduled_posts)): ?>
                            <div class="alert alert-info">Nu există postări programate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="scheduledPostsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Titlu Postare</th>
                                            <th>Platforme</th>
                                            <th>Dată Programare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scheduledPostsTableBody">
                                        <?php foreach ($scheduled_posts as $post): ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($post['id']); ?>"
                                                data-titlu="<?php echo htmlspecialchars($post['titlu_postare']); ?>"
                                                data-platforme="<?php echo htmlspecialchars($post['platforme_selectate'] ?? '[]'); ?>"
                                                data-data-programare="<?php echo htmlspecialchars($post['data_programare']); ?>"
                                                data-status="<?php echo htmlspecialchars($post['status']); ?>"
                                                data-search-text="<?php echo strtolower(htmlspecialchars($post['titlu_postare'] . ' ' . implode(' ', json_decode($post['platforme_selectate'], true) ?? []))); ?>"
                                            >
                                                <td data-label="ID:"><?php echo htmlspecialchars($post['id']); ?></td>
                                                <td data-label="Titlu Postare:"><?php echo htmlspecialchars($post['titlu_postare']); ?></td>
                                                <td data-label="Platforme:">
                                                    <?php 
                                                        $platforms = json_decode($post['platforme_selectate'], true);
                                                        if (!empty($platforms)) {
                                                            foreach ($platforms as $platform) {
                                                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(ucfirst($platform)) . '</span>';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                    ?>
                                                </td>
                                                <td data-label="Dată Programare:"><?php echo (new DateTime($post['data_programare']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Status:">
                                                    <span class="badge bg-warning text-dark"><?php echo htmlspecialchars(ucfirst($post['status'])); ?></span>
                                                </td>
                                                <td>
                                                    <a href="creeaza-postare-noua.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary me-2"><i class="bx bx-edit"></i> Editează</a>
                                                    <button type="button" class="btn btn-sm btn-success publish-now-btn" data-id="<?php echo $post['id']; ?>"><i class="bx bx-send"></i> Publică Acum</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-post-btn" data-id="<?php echo $post['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
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

<!-- Modale (doar pentru confirmare ștergere/publicare, editarea se face pe creeaza-postare-noua.php) -->

<!-- Modal Confirmă Publicare Acum -->
<div class="modal fade" id="publishNowModal" tabindex="-1" aria-labelledby="publishNowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="publishNowModalLabel">Confirmă Publicarea Imediată</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să publici postarea <strong id="publishPostTitleDisplay"></strong> acum? Această acțiune va schimba statusul postării în "Publicată".
                <input type="hidden" id="publishPostIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-success" id="confirmPublishNowBtn">Publică Acum</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmă Ștergere Postare -->
<div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePostModalLabel">Confirmă Ștergerea Postării</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi postarea <strong id="deletePostTitleDisplay"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deletePostIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePostBtn">Șterge</button>
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
    // --- Data PHP pentru JavaScript ---
    const allScheduledPostsData = <?php echo json_encode($scheduled_posts); ?>;

    // --- Elemente DOM pentru Modale ---
    const publishNowModal = document.getElementById('publishNowModal');
    const deletePostModal = document.getElementById('deletePostModal');

    const publishPostIdConfirm = document.getElementById('publishPostIdConfirm');
    const publishPostTitleDisplay = document.getElementById('publishPostTitleDisplay');
    const confirmPublishNowBtn = document.getElementById('confirmPublishNowBtn');

    const deletePostIdConfirm = document.getElementById('deletePostIdConfirm');
    const deletePostTitleDisplay = document.getElementById('deletePostTitleDisplay');
    const confirmDeletePostBtn = document.getElementById('confirmDeletePostBtn');

    // --- Funcționalitate Filtrare Tabel ---
    const filterPlatform = document.getElementById('filterPlatform');
    const searchPost = document.getElementById('searchPost');
    const scheduledPostsTableBody = document.getElementById('scheduledPostsTableBody');

    function filterScheduledPostsTable() {
        const selectedPlatform = filterPlatform.value;
        const searchText = searchPost.value.toLowerCase().trim();

        document.querySelectorAll('#scheduledPostsTableBody tr').forEach(row => {
            const rowPlatforms = JSON.parse(row.getAttribute('data-platforme') || '[]');
            const rowSearchText = row.getAttribute('data-search-text');

            const platformMatch = (selectedPlatform === 'all' || rowPlatforms.includes(selectedPlatform));
            const searchMatch = (searchText === '' || rowSearchText.includes(searchText));

            if (platformMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterPlatform.addEventListener('change', filterScheduledPostsTable);
    searchPost.addEventListener('input', filterScheduledPostsTable);
    filterScheduledPostsTable(); // Rulează la încărcarea paginii

    // --- Logică Butoane Acțiune (Publică Acum / Șterge) ---

    // Deschide modalul de confirmare publicare imediată
    document.querySelectorAll('.publish-now-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.id;
            const postTitle = this.closest('tr').querySelector('td[data-label="Titlu Postare:"]').textContent;
            publishPostIdConfirm.value = postId;
            publishPostTitleDisplay.textContent = postTitle;
            new bootstrap.Modal(publishNowModal).show();
        });
    });

    // Confirmă publicarea imediată
    confirmPublishNowBtn.addEventListener('click', function() {
        const postId = publishPostIdConfirm.value;
        if (postId) {
            const formData = new FormData();
            formData.append('action', 'publish_now'); // Acțiunea specifică de publicare imediată
            formData.append('post_id', postId);
            // Nu e nevoie de alte câmpuri, PHP va prelua restul datelor postării din DB

            fetch('process_marketing.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); 
                } else {
                    alert('Eroare: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la publicarea postării:', error);
                alert('A apărut o eroare la publicarea postării.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(publishNowModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
    });

    // Deschide modalul de confirmare ștergere postare
    document.querySelectorAll('.delete-post-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentPostIdToDelete = this.dataset.id;
            const postTitle = this.closest('tr').querySelector('td[data-label="Titlu Postare:"]').textContent;
            deletePostTitleDisplay.textContent = postTitle;
            new bootstrap.Modal(deletePostModal).show();
        });
    });

    // Confirmă ștergerea postării
    confirmDeletePostBtn.addEventListener('click', function() {
        if (currentPostIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_post');
            formData.append('id', currentPostIdToDelete);

            fetch('process_marketing.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); 
                } else {
                    alert('Eroare: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Eroare la ștergerea postării:', error);
                alert('A apărut o eroare la ștergerea postării.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(deletePostModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
    });

    // --- Funcționalitate Export (PDF, Excel, Print) ---
    function exportTableToPDF(tableId, title) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4'); 
        doc.setFont('Noto Sans', 'normal'); 

        const headers = [];
        document.querySelectorAll(`#${tableId} thead th`).forEach(th => {
            headers.push(th.textContent);
        });

        const data = [];
        document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
            if (row.style.display !== 'none') { // Doar rândurile vizibile
                const rowData = [];
                // Excludem coloana de Acțiuni (ultima coloană)
                row.querySelectorAll('td:not(:last-child)').forEach(td => {
                    rowData.push(td.textContent);
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

        doc.save(`${title.replace(/ /g, '_')}.pdf`);
    }

    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        exportTableToPDF('scheduledPostsTable', 'Postari Programate');
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('scheduledPostsTable');
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
        XLSX.utils.book_append_sheet(wb, ws, "Postari Programate");
        XLSX.writeFile(wb, `Postari_Programate.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('scheduledPostsTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Postări Programate</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Postări Programate</h1>
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
