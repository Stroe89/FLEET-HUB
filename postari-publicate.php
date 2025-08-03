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

// --- Preluare Postări Publicate ---
$published_posts = [];
$platforms_available = ['facebook', 'instagram', 'tiktok', 'whatsapp', 'telegram'];

if (tableExists($conn, 'marketing_posts')) {
    $sql_published_posts = "SELECT id, titlu_postare, continut_text, continut_html, platforme_selectate, data_programare, status FROM marketing_posts WHERE status = 'publicata' ORDER BY data_programare DESC";
    $result_published_posts = $conn->query($sql_published_posts);
    if ($result_published_posts) {
        while ($row = $result_published_posts->fetch_assoc()) {
            $published_posts[] = $row;
        }
    }
} else {
    // Date mock dacă tabelul nu există
    $published_posts = [
        ['id' => 10, 'titlu_postare' => 'Oferte de Vară (Mock)', 'continut_text' => 'Nu ratați super ofertele de vară!', 'continut_html' => '<h1>Oferte de Vară!</h1>', 'platforme_selectate' => '["facebook", "instagram"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('-10 days')), 'status' => 'publicata'],
        ['id' => 11, 'titlu_postare' => 'Sfaturi Mentenanță (Mock)', 'continut_text' => 'Află cum să-ți menții flota în formă maximă.', 'continut_html' => '<p>Articol complet...</p>', 'platforme_selectate' => '["facebook", "tiktok"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('-5 days')), 'status' => 'publicata'],
        ['id' => 12, 'titlu_postare' => 'Recrutăm Șoferi (Mock)', 'continut_text' => 'Echipa NTS TOUR caută noi talente. Aplică acum!', 'continut_html' => '<p>Detalii despre post...</p>', 'platforme_selectate' => '["facebook", "instagram", "tiktok"]', 'data_programare' => date('Y-m-d H:i:s', strtotime('-2 days')), 'status' => 'publicata'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Postări Publicate</title>

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
    /* Stiluri pentru preview (iframe) */
    #postPreviewIframe {
        width: 100%;
        height: 600px;
        border: none;
        background-color: #fff; /* Fundal alb pentru conținutul preview */
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Marketing (Social Manager)</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Postări Publicate</li>
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
                        <h4 class="card-title">Listă Postări Publicate</h4>
                        <p class="text-muted">Vizualizează și gestionează postările de social media care au fost publicate.</p>
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

                        <!-- Tabelul cu Postări Publicate -->
                        <?php if (empty($published_posts)): ?>
                            <div class="alert alert-info">Nu există postări publicate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="publishedPostsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Titlu Postare</th>
                                            <th>Platforme</th>
                                            <th>Dată Publicare</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="publishedPostsTableBody">
                                        <?php foreach ($published_posts as $post): ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($post['id']); ?>"
                                                data-titlu="<?php echo htmlspecialchars($post['titlu_postare']); ?>"
                                                data-continut-text="<?php echo htmlspecialchars($post['continut_text'] ?? ''); ?>"
                                                data-continut-html="<?php echo htmlspecialchars($post['continut_html'] ?? ''); ?>"
                                                data-platforme="<?php echo htmlspecialchars($post['platforme_selectate'] ?? '[]'); ?>"
                                                data-data-publicare="<?php echo htmlspecialchars($post['data_programare']); ?>"
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
                                                <td data-label="Dată Publicare:"><?php echo (new DateTime($post['data_programare']))->format('d.m.Y H:i'); ?></td>
                                                <td data-label="Status:">
                                                    <span class="badge bg-success"><?php echo htmlspecialchars(ucfirst($post['status'])); ?></span>
                                                </td>
                                                <td>
                                                    <a href="creeaza-postare-noua.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary me-2"><i class="bx bx-edit"></i> Editează</a>
                                                    <button type="button" class="btn btn-sm btn-outline-info me-2 preview-post-btn" data-id="<?php echo $post['id']; ?>" data-bs-toggle="modal" data-bs-target="#previewPostModal"><i class="bx bx-show"></i> Preview</button>
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

<!-- Modale (doar pentru confirmare ștergere/previzualizare, editarea se face pe creeaza-postare-noua.php) -->

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

<!-- Modal Preview Postare -->
<div class="modal fade" id="previewPostModal" tabindex="-1" aria-labelledby="previewPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewPostModalLabel">Preview Postare: <span id="previewPostTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe id="postPreviewIframe" style="width: 100%; height: 600px; border: none; background-color: #fff;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
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
    const allPublishedPostsData = <?php echo json_encode($published_posts); ?>;
    const postsMap = {}; // Map pentru acces rapid la postări după ID
    allPublishedPostsData.forEach(post => {
        postsMap[post.id] = post;
    });

    // --- Elemente DOM pentru Modale ---
    const deletePostModal = document.getElementById('deletePostModal');
    const previewPostModal = document.getElementById('previewPostModal');

    const deletePostIdConfirm = document.getElementById('deletePostIdConfirm');
    const deletePostTitleDisplay = document.getElementById('deletePostTitleDisplay');
    const confirmDeletePostBtn = document.getElementById('confirmDeletePostBtn');

    const postPreviewIframe = document.getElementById('postPreviewIframe');
    const previewPostTitle = document.getElementById('previewPostTitle');

    // --- Funcționalitate Filtrare Tabel ---
    const filterPlatform = document.getElementById('filterPlatform');
    const searchPost = document.getElementById('searchPost');
    const publishedPostsTableBody = document.getElementById('publishedPostsTableBody');

    function filterPublishedPostsTable() {
        const selectedPlatform = filterPlatform.value;
        const searchText = searchPost.value.toLowerCase().trim();

        document.querySelectorAll('#publishedPostsTableBody tr').forEach(row => {
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

    filterPlatform.addEventListener('change', filterPublishedPostsTable);
    searchPost.addEventListener('input', filterPublishedPostsTable);
    filterPublishedPostsTable(); // Rulează la încărcarea paginii

    // --- Logică Butoane Acțiune (Preview / Șterge) ---

    // Populează și afișează modalul de preview postare
    document.querySelectorAll('.preview-post-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const post = postsMap[id];
            if (post) {
                previewPostTitle.textContent = post.titlu_postare;
                // Încarcă conținutul HTML în iframe
                const iframeDoc = postPreviewIframe.contentWindow.document;
                iframeDoc.open();
                iframeDoc.write(post.continut_html || post.continut_text || 'Nu există conținut pentru previzualizare.');
                iframeDoc.close();
                new bootstrap.Modal(previewPostModal).show();
            }
        });
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
        exportTableToPDF('publishedPostsTable', 'Postari Publicate');
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('publishedPostsTable');
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
        XLSX.utils.book_append_sheet(wb, ws, "Postari Publicate");
        XLSX.writeFile(wb, `Postari_Publicate.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('publishedPostsTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Postări Publicate</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Postări Publicate</h1>
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
