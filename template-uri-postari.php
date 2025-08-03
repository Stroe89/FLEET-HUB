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

// --- Preluare Date pentru Tabele ---
$all_marketing_templates = [];
$platforms_available = ['facebook', 'instagram', 'tiktok', 'whatsapp', 'telegram'];

if (tableExists($conn, 'marketing_templates')) {
    $sql_templates = "SELECT id, nume_template, titlu_implicit, continut_text_implicit, continut_html_implicit, platforme_compatibile, data_creare FROM marketing_templates ORDER BY data_creare DESC";
    $result_templates = $conn->query($sql_templates);
    if ($result_templates) {
        while ($row = $result_templates->fetch_assoc()) {
            $all_marketing_templates[] = $row;
        }
    }
} else {
    // Date mock dacă tabelul nu există
    $all_marketing_templates = [
        ['id' => 1, 'nume_template' => 'Promoție Vară', 'titlu_implicit' => 'Reduceri Estivale!', 'continut_text_implicit' => 'Profitați de reducerile noastre de vară la serviciile de transport!', 'continut_html_implicit' => '<h1>Reduceri de Vară!</h1><p>Detalii aici...</p>', 'platforme_compatibile' => '["facebook", "instagram"]', 'data_creare' => '2025-06-01 10:00:00'],
        ['id' => 2, 'nume_template' => 'Anunț Recrutare', 'titlu_implicit' => 'Angajăm Șoferi Profesioniști', 'continut_text_implicit' => 'Căutăm șoferi cu experiență pentru flota noastră. Aplică acum!', 'continut_html_implicit' => '<h2>Cariere la NTS TOUR</h2><p>Detalii și aplicare...</p>', 'platforme_compatibile' => '["facebook", "tiktok", "linkedin"]', 'data_creare' => '2025-05-15 14:30:00'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Șabloane Postări</title>

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

    /* Stiluri pentru butoane */
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        font-weight: bold !important;
        padding: 0.75rem 1.5rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
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
    /* Stiluri pentru butoanele de acțiune din tabel */
    .table .btn-sm {
        padding: 0.3rem 0.6rem !important; /* Mărește puțin butoanele mici */
        font-size: 0.8rem !important;
        width: auto !important; /* Anulează width: 100% de pe mobil */
    }
    /* Stiluri pentru selectorul de platforme în modal */
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
        height: 100px; /* Înălțime fixă pentru butoane */
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
    /* Iconițe specifice platformelor */
    .platform-selector .form-check-label .bx.bxl-facebook-square { color: #1877F2; }
    .platform-selector .form-check-label .bx.bxl-instagram { color: #E4405F; }
    .platform-selector .form-check-label .bx.bxl-tiktok { color: #000; filter: invert(1); } /* TikTok are logo negru/alb */
    .platform-selector .form-check-label .bx.bxl-whatsapp { color: #25D366; }
    .platform-selector .form-check-label .bx.bxl-telegram { color: #0088CC; }
    .platform-selector .form-check-input:checked + .form-check-label .bx { color: #fff; } /* Iconițe albe când sunt selectate */
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Marketing (Social Manager)</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Șabloane Postări</li>
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
                        <h4 class="card-title">Gestionare Șabloane Postări Social Media</h4>
                        <p class="text-muted">Creează, editează și gestionează șabloanele de postări pentru campaniile tale de marketing.</p>
                        <hr>

                        <!-- Butoane de Acțiuni -->
                        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal"><i class="bx bx-plus me-2"></i>Adaugă Șablon Nou</button>
                            <button type="button" class="btn btn-success" id="exportExcelBtn"><i class="bx bxs-file-excel me-2"></i>Export Excel</button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn"><i class="bx bxs-file-pdf me-2"></i>Export PDF</button>
                            <button type="button" class="btn btn-info" id="printListBtn"><i class="bx bx-printer me-2"></i>Printează</button>
                        </div>

                        <!-- Tabelul cu Șabloane -->
                        <?php if (empty($all_marketing_templates)): ?>
                            <div class="alert alert-info">Nu există șabloane de postări înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="templatesTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nume Șablon</th>
                                            <th>Titlu Implicit</th>
                                            <th>Platforme Compatibile</th>
                                            <th>Dată Creare</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="templatesTableBody">
                                        <?php foreach ($all_marketing_templates as $template): ?>
                                            <tr 
                                                data-id="<?php echo htmlspecialchars($template['id']); ?>"
                                                data-nume="<?php echo htmlspecialchars($template['nume_template']); ?>"
                                                data-titlu="<?php echo htmlspecialchars($template['titlu_implicit'] ?? ''); ?>"
                                                data-continut-text="<?php echo htmlspecialchars($template['continut_text_implicit'] ?? ''); ?>"
                                                data-continut-html="<?php echo htmlspecialchars($template['continut_html_implicit'] ?? ''); ?>"
                                                data-platforme="<?php echo htmlspecialchars($template['platforme_compatibile'] ?? '[]'); ?>"
                                                data-data-creare="<?php echo htmlspecialchars($template['data_creare']); ?>"
                                            >
                                                <td data-label="ID:"><?php echo htmlspecialchars($template['id']); ?></td>
                                                <td data-label="Nume Șablon:"><?php echo htmlspecialchars($template['nume_template']); ?></td>
                                                <td data-label="Titlu Implicit:"><?php echo htmlspecialchars($template['titlu_implicit'] ?? 'N/A'); ?></td>
                                                <td data-label="Platforme Compatibile:">
                                                    <?php 
                                                        $platforms = json_decode($template['platforme_compatibile'], true);
                                                        if (!empty($platforms)) {
                                                            foreach ($platforms as $platform) {
                                                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(ucfirst($platform)) . '</span>';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                    ?>
                                                </td>
                                                <td data-label="Dată Creare:"><?php echo (new DateTime($template['data_creare']))->format('d.m.Y H:i'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-2 edit-template-btn" data-id="<?php echo $template['id']; ?>" data-bs-toggle="modal" data-bs-target="#editTemplateModal"><i class="bx bx-edit"></i> Editează</button>
                                                    <button type="button" class="btn btn-sm btn-outline-info me-2 preview-template-btn" data-id="<?php echo $template['id']; ?>" data-bs-toggle="modal" data-bs-target="#previewTemplateModal"><i class="bx bx-show"></i> Preview</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-template-btn" data-id="<?php echo $template['id']; ?>"><i class="bx bx-trash"></i> Șterge</button>
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

<!-- Modale pentru Șabloane -->

<!-- Modal Adaugă Șablon -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTemplateModalLabel">Adaugă Șablon Postare Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTemplateForm" action="process_marketing.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_marketing_template">
                    <div class="mb-3">
                        <label for="templateName" class="form-label">Nume Șablon:</label>
                        <input type="text" class="form-control" id="templateName" name="nume_template" required>
                    </div>
                    <div class="mb-3">
                        <label for="templateTitle" class="form-label">Titlu Implicit Postare:</label>
                        <input type="text" class="form-control" id="templateTitle" name="titlu_implicit">
                    </div>
                    <div class="mb-3">
                        <label for="templateContentText" class="form-label">Conținut Text Implicit:</label>
                        <textarea class="form-control" id="templateContentText" name="continut_text_implicit" rows="5" placeholder="Textul implicit al postării."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="templateContentHtml" class="form-label">Conținut HTML Implicit (Opțional):</label>
                        <textarea class="form-control" id="templateContentHtml" name="continut_html_implicit" rows="5" placeholder="Conținut HTML pentru platforme care suportă (ex: bloguri, anumite postări FB)."></textarea>
                        <small class="form-text text-muted">Recomandat: utilizați un editor HTML extern pentru complexitate.</small>
                    </div>
                    <h5 class="mb-3 mt-4">Platforme Compatibile</h5>
                    <div class="row row-cols-2 row-cols-md-3 g-3 mb-4 platform-selector">
                        <?php foreach ($platforms_available as $platform): ?>
                            <div class="col">
                                <input class="form-check-input" type="checkbox" id="addPlatform_<?php echo htmlspecialchars($platform); ?>" name="platforms[]" value="<?php echo htmlspecialchars($platform); ?>">
                                <label class="form-check-label" for="addPlatform_<?php echo htmlspecialchars($platform); ?>">
                                    <i class="bx bxl-<?php echo htmlspecialchars($platform); ?>"></i>
                                    <span><?php echo htmlspecialchars(ucfirst($platform)); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Șablon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editează Șablon -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTemplateModalLabel">Editează Șablon Postare</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTemplateForm" action="process_marketing.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_marketing_template">
                    <input type="hidden" name="id" id="editTemplateId">
                    <div class="mb-3">
                        <label for="editTemplateName" class="form-label">Nume Șablon:</label>
                        <input type="text" class="form-control" id="editTemplateName" name="nume_template" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTemplateTitle" class="form-label">Titlu Implicit Postare:</label>
                        <input type="text" class="form-control" id="editTemplateTitle" name="titlu_implicit">
                    </div>
                    <div class="mb-3">
                        <label for="editTemplateContentText" class="form-label">Conținut Text Implicit:</label>
                        <textarea class="form-control" id="editTemplateContentText" name="continut_text_implicit" rows="5" placeholder="Textul implicit al postării."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editTemplateContentHtml" class="form-label">Conținut HTML Implicit (Opțional):</label>
                        <textarea class="form-control" id="editTemplateContentHtml" name="continut_html_implicit" rows="5" placeholder="Conținut HTML pentru platforme care suportă (ex: bloguri, anumite postări FB)."></textarea>
                        <small class="form-text text-muted">Recomandat: utilizați un editor HTML extern pentru complexitate.</small>
                    </div>
                    <h5 class="mb-3 mt-4">Platforme Compatibile</h5>
                    <div class="row row-cols-2 row-cols-md-3 g-3 mb-4 platform-selector">
                        <?php foreach ($platforms_available as $platform): ?>
                            <div class="col">
                                <input class="form-check-input edit-platform-checkbox" type="checkbox" id="editPlatform_<?php echo htmlspecialchars($platform); ?>" name="platforms[]" value="<?php echo htmlspecialchars($platform); ?>">
                                <label class="form-check-label" for="editPlatform_<?php echo htmlspecialchars($platform); ?>">
                                    <i class="bx bxl-<?php echo htmlspecialchars($platform); ?>"></i>
                                    <span><?php echo htmlspecialchars(ucfirst($platform)); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmă Ștergere Șablon -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTemplateModalLabel">Confirmă Ștergerea Șablonului</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi șablonul <strong id="deleteTemplateNameDisplay"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" id="deleteTemplateIdConfirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTemplateBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview Șablon -->
<div class="modal fade" id="previewTemplateModal" tabindex="-1" aria-labelledby="previewTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTemplateModalLabel">Preview Șablon: <span id="previewTemplateName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe id="templatePreviewIframe" style="width: 100%; height: 600px; border: none; background-color: #fff;"></iframe>
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
    const allMarketingTemplatesData = <?php echo json_encode($all_marketing_templates); ?>;

    // --- Elemente DOM pentru Modale ---
    const addTemplateModal = document.getElementById('addTemplateModal');
    const editTemplateModal = document.getElementById('editTemplateModal');
    const deleteTemplateModal = document.getElementById('deleteTemplateModal');
    const previewTemplateModal = document.getElementById('previewTemplateModal');

    const editTemplateForm = document.getElementById('editTemplateForm');
    const deleteTemplateIdConfirm = document.getElementById('deleteTemplateIdConfirm');
    const deleteTemplateNameDisplay = document.getElementById('deleteTemplateNameDisplay');
    const confirmDeleteTemplateBtn = document.getElementById('confirmDeleteTemplateBtn');
    const templatePreviewIframe = document.getElementById('templatePreviewIframe');
    const previewTemplateName = document.getElementById('previewTemplateName');

    // --- Funcționalitate Filtrare Tabel Principal Șabloane (poți adăuga un input de căutare în HTML) ---
    const searchTemplateInput = document.getElementById('searchTemplate'); // Asigură-te că ai un input cu acest ID în HTML
    const templatesTableBody = document.getElementById('templatesTableBody');

    function filterTemplatesTable() {
        const searchText = (searchTemplateInput ? searchTemplateInput.value.toLowerCase().trim() : '');
        
        document.querySelectorAll('#templatesTableBody tr').forEach(row => {
            const rowNume = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const rowTitlu = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const rowPlatforms = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

            if (searchText === '' || rowNume.includes(searchText) || rowTitlu.includes(searchText) || rowPlatforms.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    if (searchTemplateInput) {
        searchTemplateInput.addEventListener('input', filterTemplatesTable);
    }
    filterTemplatesTable(); // Rulează la încărcarea paginii

    // --- Logică Modale Șablon (Editare / Ștergere / Preview) ---

    // Populează modalul de editare șablon
    document.querySelectorAll('.edit-template-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const template = allMarketingTemplatesData.find(t => t.id == id);
            if (template) {
                document.getElementById('editTemplateId').value = template.id;
                document.getElementById('editTemplateName').value = template.nume_template;
                document.getElementById('editTemplateTitle').value = template.titlu_implicit || '';
                document.getElementById('editTemplateContentText').value = template.continut_text_implicit || '';
                document.getElementById('editTemplateContentHtml').value = template.continut_html_implicit || '';

                // Resetează și selectează platformele compatibile
                document.querySelectorAll('.edit-platform-checkbox').forEach(cb => cb.checked = false);
                const compatiblePlatforms = JSON.parse(template.platforme_compatibile || '[]');
                compatiblePlatforms.forEach(platform => {
                    const checkbox = document.getElementById(`editPlatform_${platform}`);
                    if (checkbox) checkbox.checked = true;
                });

                new bootstrap.Modal(editTemplateModal).show();
            }
        });
    });

    // Deschide modalul de confirmare ștergere șablon
    document.querySelectorAll('.delete-template-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentTemplateIdToDelete = this.dataset.id;
            const templateName = this.closest('tr').querySelector('td[data-label="Nume Șablon:"]').textContent;
            deleteTemplateNameDisplay.textContent = templateName;
            new bootstrap.Modal(deleteTemplateModal).show();
        });
    });

    // Confirmă ștergerea șablonului
    confirmDeleteTemplateBtn.addEventListener('click', function() {
        if (currentTemplateIdToDelete) {
            const formData = new FormData();
            formData.append('action', 'delete_marketing_template');
            formData.append('id', currentTemplateIdToDelete);

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
                console.error('Eroare la ștergerea șablonului:', error);
                alert('A apărut o eroare la ștergerea șablonului.');
            })
            .finally(() => {
                const modalInstance = bootstrap.Modal.getInstance(deleteTemplateModal);
                if (modalInstance) { modalInstance.hide(); }
            });
        }
    });

    // Populează și afișează modalul de preview șablon
    document.querySelectorAll('.preview-template-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const template = allMarketingTemplatesData.find(t => t.id == id);
            if (template) {
                previewTemplateName.textContent = template.nume_template;
                // Încarcă conținutul HTML în iframe
                const iframeDoc = templatePreviewIframe.contentWindow.document;
                iframeDoc.open();
                iframeDoc.write(template.continut_html_implicit || template.continut_text_implicit || 'Nu există conținut pentru previzualizare.');
                iframeDoc.close();
                new bootstrap.Modal(previewTemplateModal).show();
            }
        });
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
        exportTableToPDF('templatesTable', 'Lista Sabloane Postari');
    });

    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        const table = document.getElementById('templatesTable');
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
        XLSX.utils.book_append_sheet(wb, ws, "Sabloane Postari");
        XLSX.writeFile(wb, `Lista_Sabloane_Postari.xlsx`);
    });

    document.getElementById('printListBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const tableToPrint = document.getElementById('templatesTable').cloneNode(true);
        // Elimină coloana "Acțiuni" din clona tabelului înainte de printare
        tableToPrint.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

        const printContent = `
            <html>
            <head>
                <title>Listă Șabloane Postări</title>
                <style>
                    body { font-family: 'Noto Sans', sans-serif; color: #333; margin: 20px; }
                    h1 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Listă Șabloane Postări</h1>
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
