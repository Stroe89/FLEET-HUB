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

// --- Preluare Date pentru Dropdown-uri și Populare Formular (dacă se editează o campanie) ---
$campaign_id = $_GET['id'] ?? null;
$campaign_data = null;

if ($campaign_id && is_numeric($campaign_id)) {
    if (tableExists($conn, 'campanii_newsletter')) {
        $stmt_campaign = $conn->prepare("SELECT * FROM campanii_newsletter WHERE id = ?");
        if ($stmt_campaign) {
            $stmt_campaign->bind_param("i", $campaign_id);
            $stmt_campaign->execute();
            $result_campaign = $stmt_campaign->get_result();
            $campaign_data = $result_campaign->fetch_assoc();
            $stmt_campaign->close();
        }
    }
}

$all_templates = [];
if (tableExists($conn, 'newsletter_templates')) {
    $sql_templates = "SELECT id, nume_template, subiect_implicit FROM newsletter_templates ORDER BY nume_template ASC";
    $result_templates = $conn->query($sql_templates);
    if ($result_templates) {
        while ($row = $result_templates->fetch_assoc()) {
            $all_templates[] = $row;
        }
    }
} else {
    $all_templates = [
        ['id' => 1, 'nume_template' => 'Șablon Implicit', 'subiect_implicit' => 'Noutăți de la Noi'],
        ['id' => 2, 'nume_template' => 'Șablon Promoțional', 'subiect_implicit' => 'Ofertă Specială!'],
    ];
}

$all_clients = [];
if (tableExists($conn, 'clienti')) {
    $sql_clients = "SELECT id, nume_companie, email_contact FROM clienti WHERE email_contact IS NOT NULL AND email_contact != '' ORDER BY nume_companie ASC";
    $result_clients = $conn->query($sql_clients);
    if ($result_clients) {
        while ($row = $result_clients->fetch_assoc()) {
            $all_clients[] = $row;
        }
    }
} else {
    $all_clients = [
        ['id' => 1, 'nume_companie' => 'Global Logistics SRL', 'email_contact' => 'contact@globallogistics.ro'],
        ['id' => 2, 'nume_companie' => 'Transport Rapid SA', 'email_contact' => 'office@transportrapid.com'],
    ];
}

$all_employees = [];
if (tableExists($conn, 'angajati')) {
    $sql_employees = "SELECT id, nume, prenume, email, functie FROM angajati WHERE email IS NOT NULL AND email != '' ORDER BY nume ASC";
    $result_employees = $conn->query($sql_employees);
    if ($result_employees) {
        while ($row = $result_employees->fetch_assoc()) {
            $all_employees[] = $row;
        }
    }
} else {
    $all_employees = [
        ['id' => 101, 'nume' => 'Popescu', 'prenume' => 'Ion', 'email' => 'ion.popescu@example.com', 'functie' => 'Sofer'],
        ['id' => 102, 'nume' => 'Ionescu', 'prenume' => 'Maria', 'email' => 'maria.ionescu@example.com', 'functie' => 'Dispecer'],
    ];
}

$conn->close();
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | <?php echo $campaign_data ? 'Editează' : 'Creează'; ?> Newsletter</title>

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

    /* Stiluri specifice butoane */
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

    /* Stiluri pentru tab-uri */
    .nav-tabs .nav-link {
        color: #e0e0e0;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-bottom-color: transparent;
        background-color: #3b435a;
        margin-right: 5px;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .nav-tabs .nav-link.active {
        color: #ffffff;
        background-color: #2a3042;
        border-color: rgba(255, 255, 255, 0.1);
        border-bottom-color: #2a3042;
    }
    .nav-tabs .nav-link:hover {
        border-color: rgba(255, 255, 255, 0.2);
    }
    .tab-content {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-top: none;
        border-radius: 0 0 0.75rem 0.75rem;
        padding: 1.5rem;
    }
    /* Stiluri specifice pentru secțiunea destinatari */
    .recipient-list-container {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        background-color: #1a2035;
        padding: 10px;
    }
    .recipient-list-item {
        padding: 8px 0;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.05);
    }
    .recipient-list-item:last-child {
        border-bottom: none;
    }
    .recipient-list-item label {
        margin-bottom: 0;
        cursor: pointer;
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
                    <li class="breadcrumb-item"><a href="dashboard-newsletter.php">Dashboard Newsletter</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $campaign_data ? 'Editează' : 'Creează'; ?> Campanie</li>
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
                        <h4 class="card-title"><?php echo $campaign_data ? 'Editează Campanie Newsletter' : 'Creează Campanie Newsletter Nouă'; ?></h4>
                        <p class="text-muted">Completează detaliile campaniei, alege un șablon și selectează destinatarii.</p>
                        <hr>

                        <form id="newsletterCampaignForm" action="process_newsletter.php" method="POST">
                            <input type="hidden" name="action" value="<?php echo $campaign_data ? 'edit_campaign' : 'create_campaign'; ?>">
                            <?php if ($campaign_data): ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($campaign_data['id']); ?>">
                            <?php endif; ?>

                            <!-- Detalii Campanie -->
                            <h5 class="mb-3">Detalii Campanie</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="campaignName" class="form-label">Nume Campanie:</label>
                                    <input type="text" class="form-control" id="campaignName" name="nume_campanie" value="<?php echo htmlspecialchars($campaign_data['nume_campanie'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="campaignSubject" class="form-label">Subiect Email:</label>
                                    <input type="text" class="form-control" id="campaignSubject" name="subiect" value="<?php echo htmlspecialchars($campaign_data['subiect'] ?? ''); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="templateSelector" class="form-label">Selectează Șablon:</label>
                                    <select class="form-select" id="templateSelector" name="id_template">
                                        <option value="">Fără șablon (conținut personalizat)</option>
                                        <?php foreach ($all_templates as $template): ?>
                                            <option 
                                                value="<?php echo htmlspecialchars($template['id']); ?>" 
                                                data-subject="<?php echo htmlspecialchars($template['subiect_implicit'] ?? ''); ?>"
                                                data-content="<?php echo htmlspecialchars($template['continut_html'] ?? ''); ?>"
                                                <?php echo (isset($campaign_data['id_template']) && $campaign_data['id_template'] == $template['id']) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($template['nume_template']); ?> (Subiect: <?php echo htmlspecialchars($template['subiect_implicit'] ?? 'N/A'); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Selectarea unui șablon va pre-popula subiectul și conținutul. Poți edita ulterior.</small>
                                </div>
                                <div class="col-12">
                                    <label for="campaignContent" class="form-label">Conținut Email (HTML):</label>
                                    <textarea class="form-control" id="campaignContent" name="continut_personalizat_html" rows="15" placeholder="Introduceți conținutul HTML al newsletter-ului aici. Recomandat: utilizați un editor HTML extern pentru complexitate."><?php echo htmlspecialchars($campaign_data['continut_personalizat_html'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Pentru un editor WYSIWYG avansat (ex: TinyMCE, CKEditor), va fi necesară integrare suplimentară.</small>
                                </div>
                            </div>

                            <!-- Selecție Destinatari -->
                            <h5 class="mb-3 mt-4">Selecție Destinatari</h5>
                            <div class="alert alert-info">
                                Poți selecta destinatari individuali, sau poți importa liste din clienți/angajați. Email-urile duplicate vor fi ignorate automat.
                            </div>
                            <ul class="nav nav-tabs mb-3" id="recipientTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individualRecipients" type="button" role="tab" aria-controls="individualRecipients" aria-selected="true">Abonați Exiștenți</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="from-clients-tab" data-bs-toggle="tab" data-bs-target="#fromClients" type="button" role="tab" aria-controls="fromClients" aria-selected="false">Din Clienți</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="from-employees-tab" data-bs-toggle="tab" data-bs-target="#fromEmployees" type="button" role="tab" aria-controls="fromEmployees" aria-selected="false">Din Angajați</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="manual-add-tab" data-bs-toggle="tab" data-bs-target="#manualAdd" type="button" role="tab" aria-controls="manualAdd" aria-selected="false">Adaugă Manual</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="recipientTabsContent">
                                <!-- Tab: Abonați Exiștenți (din abonati_newsletter) -->
                                <div class="tab-pane fade show active" id="individualRecipients" role="tabpanel" aria-labelledby="individual-tab">
                                    <div class="mb-3">
                                        <input type="text" class="form-control" id="searchExistingSubscribers" placeholder="Căutare abonat după email, nume, prenume...">
                                    </div>
                                    <div class="recipient-list-container">
                                        <?php if (empty($all_subscribers)): ?>
                                            <div class="alert alert-info">Nu există abonați înregistrați.</div>
                                        <?php else: ?>
                                            <?php foreach ($all_subscribers as $subscriber): ?>
                                                <div class="form-check recipient-list-item" data-search-text="<?php echo strtolower(htmlspecialchars($subscriber['email'] . ' ' . ($subscriber['nume'] ?? '') . ' ' . ($subscriber['prenume'] ?? '') . ' ' . $subscriber['status'] . ' ' . ($subscriber['sursa_abonare'] ?? ''))); ?>">
                                                    <input class="form-check-input existing-subscriber-checkbox" type="checkbox" name="recipient_ids[]" value="<?php echo htmlspecialchars($subscriber['id']); ?>" id="subscriber_<?php echo htmlspecialchars($subscriber['id']); ?>"
                                                        <?php 
                                                            // Selectează abonații dacă campania este editată și ei sunt deja destinatari
                                                            if ($campaign_data && isset($campaign_data['destinatari_ids']) && in_array($subscriber['id'], json_decode($campaign_data['destinatari_ids']))) {
                                                                echo 'checked';
                                                            }
                                                        ?>
                                                    >
                                                    <label class="form-check-label" for="subscriber_<?php echo htmlspecialchars($subscriber['id']); ?>">
                                                        <?php echo htmlspecialchars($subscriber['email']); ?> (<?php echo htmlspecialchars($subscriber['nume'] ?? ''); ?> <?php echo htmlspecialchars($subscriber['prenume'] ?? ''); ?>) - Status: <?php echo htmlspecialchars(ucfirst($subscriber['status'])); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Tab: Din Clienți -->
                                <div class="tab-pane fade" id="fromClients" role="tabpanel" aria-labelledby="from-clients-tab">
                                    <div class="mb-3">
                                        <input type="text" class="form-control" id="searchClientsForNewsletter" placeholder="Căutare client după nume companie sau email...">
                                    </div>
                                    <div class="recipient-list-container">
                                        <?php if (empty($all_clients)): ?>
                                            <div class="alert alert-info">Nu există clienți înregistrați.</div>
                                        <?php else: ?>
                                            <?php foreach ($all_clients as $client): ?>
                                                <div class="form-check recipient-list-item" data-search-text="<?php echo strtolower(htmlspecialchars($client['nume_companie'] . ' ' . $client['email_contact'])); ?>">
                                                    <input class="form-check-input client-for-newsletter-checkbox" type="checkbox" name="client_emails[]" value="<?php echo htmlspecialchars($client['email_contact']); ?>" id="client_<?php echo htmlspecialchars($client['id']); ?>"
                                                        <?php 
                                                            // Selectează clienții dacă campania este editată și ei sunt deja destinatari
                                                            if ($campaign_data && isset($campaign_data['destinatari_emails']) && in_array($client['email_contact'], json_decode($campaign_data['destinatari_emails']))) {
                                                                echo 'checked';
                                                            }
                                                        ?>
                                                    >
                                                    <label class="form-check-label" for="client_<?php echo htmlspecialchars($client['id']); ?>">
                                                        <?php echo htmlspecialchars($client['nume_companie']); ?> (<?php echo htmlspecialchars($client['email_contact']); ?>)
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <small class="form-text text-muted">Vor fi adăugați doar clienții cu email valid și care nu sunt deja abonați activi.</small>
                                </div>

                                <!-- Tab: Din Angajați -->
                                <div class="tab-pane fade" id="fromEmployees" role="tabpanel" aria-labelledby="from-employees-tab">
                                    <div class="mb-3">
                                        <input type="text" class="form-control" id="searchEmployeesForNewsletter" placeholder="Căutare angajat după nume sau email...">
                                    </div>
                                    <div class="recipient-list-container">
                                        <?php if (empty($all_employees)): ?>
                                            <div class="alert alert-info">Nu există angajați înregistrați.</div>
                                        <?php else: ?>
                                            <?php foreach ($all_employees as $employee): ?>
                                                <div class="form-check recipient-list-item" data-search-text="<?php echo strtolower(htmlspecialchars($employee['nume'] . ' ' . $employee['prenume'] . ' ' . $employee['email'] . ' ' . $employee['functie'])); ?>">
                                                    <input class="form-check-input employee-for-newsletter-checkbox" type="checkbox" name="employee_emails[]" value="<?php echo htmlspecialchars($employee['email']); ?>" id="employee_<?php echo htmlspecialchars($employee['id']); ?>"
                                                        <?php 
                                                            // Selectează angajații dacă campania este editată și ei sunt deja destinatari
                                                            if ($campaign_data && isset($campaign_data['destinatari_emails']) && in_array($employee['email'], json_decode($campaign_data['destinatari_emails']))) {
                                                                echo 'checked';
                                                            }
                                                        ?>
                                                    >
                                                    <label class="form-check-label" for="employee_<?php echo htmlspecialchars($employee['id']); ?>">
                                                        <?php echo htmlspecialchars($employee['nume']); ?> <?php echo htmlspecialchars($employee['prenume']); ?> (<?php echo htmlspecialchars($employee['email']); ?>) - Funcție: <?php echo htmlspecialchars($employee['functie']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <small class="form-text text-muted">Vor fi adăugați doar angajații cu email valid și care nu sunt deja abonați activi.</small>
                                </div>

                                <!-- Tab: Adaugă Manual -->
                                <div class="tab-pane fade" id="manualAdd" role="tabpanel" aria-labelledby="manual-add-tab">
                                    <div class="mb-3">
                                        <label for="manualEmails" class="form-label">Adrese Email (separate prin virgulă sau pe rânduri noi):</label>
                                        <textarea class="form-control" id="manualEmails" name="manual_emails" rows="5" placeholder="exemplu1@domeniu.com, exemplu2@domeniu.com"></textarea>
                                        <small class="form-text text-muted">Poți adăuga și nume/prenume opțional: "Nume Prenume &lt;email@domeniu.com&gt;"</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Programare Trimitere -->
                            <h5 class="mb-3 mt-4">Programare Trimitere</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="sendOption" class="form-label">Opțiune Trimitere:</label>
                                    <select class="form-select" id="sendOption" name="send_option">
                                        <option value="now" <?php echo ($campaign_data && $campaign_data['status'] == 'draft') ? 'selected' : ''; ?>>Trimite Acum</option>
                                        <option value="schedule" <?php echo ($campaign_data && $campaign_data['status'] == 'programata') ? 'selected' : ''; ?>>Programează Trimitere</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="scheduleDateTimeGroup" style="<?php echo ($campaign_data && $campaign_data['status'] == 'programata') ? '' : 'display: none;'; ?>">
                                    <label for="scheduleDateTime" class="form-label">Dată și Oră Programare:</label>
                                    <input type="datetime-local" class="form-control" id="scheduleDateTime" name="data_trimitere" value="<?php echo $campaign_data['data_trimitere'] ? (new DateTime($campaign_data['data_trimitere']))->format('Y-m-d\TH:i') : ''; ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="dashboard-newsletter.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary">Salvează Campanie</button>
                                <?php if ($campaign_data && $campaign_data['status'] == 'draft'): ?>
                                    <button type="submit" class="btn btn-success" name="action" value="send_campaign_now">Trimite Acum</button>
                                <?php endif; ?>
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
    // --- Data PHP pentru JavaScript ---
    const allTemplatesData = <?php echo json_encode($all_templates); ?>;
    const allClientsData = <?php echo json_encode($all_clients); ?>;
    const allEmployeesData = <?php echo json_encode($all_employees); ?>;
    const campaignData = <?php echo json_encode($campaign_data); ?>;

    // --- Elemente DOM ---
    const templateSelector = document.getElementById('templateSelector');
    const campaignSubjectInput = document.getElementById('campaignSubject');
    const campaignContentTextarea = document.getElementById('campaignContent');
    const sendOptionSelect = document.getElementById('sendOption');
    const scheduleDateTimeGroup = document.getElementById('scheduleDateTimeGroup');
    const scheduleDateTimeInput = document.getElementById('scheduleDateTime');

    // Selectoare pentru tab-urile de destinatari
    const searchExistingSubscribersInput = document.getElementById('searchExistingSubscribers');
    const individualRecipientsTabPane = document.getElementById('individualRecipients');

    const searchClientsForNewsletterInput = document.getElementById('searchClientsForNewsletter');
    const fromClientsTabPane = document.getElementById('fromClients');

    const searchEmployeesForNewsletterInput = document.getElementById('searchEmployeesForNewsletter');
    const fromEmployeesTabPane = document.getElementById('fromEmployees');

    // --- Funcționalitate Selector Șablon ---
    templateSelector.addEventListener('change', function() {
        const selectedTemplateId = this.value;
        if (selectedTemplateId) {
            const template = allTemplatesData.find(t => t.id == selectedTemplateId);
            if (template) {
                campaignSubjectInput.value = template.subiect_implicit || '';
                campaignContentTextarea.value = template.continut_html || '';
            }
        } else {
            // Dacă se alege "Fără șablon", golim câmpurile
            if (!campaignData) { // Golim doar dacă nu edităm o campanie existentă
                campaignSubjectInput.value = '';
                campaignContentTextarea.value = '';
            }
        }
    });

    // La încărcarea paginii, dacă edităm o campanie, aplicăm datele
    if (campaignData) {
        // Dacă campania are un șablon, pre-populăm conținutul
        if (campaignData.id_template) {
            const template = allTemplatesData.find(t => t.id == campaignData.id_template);
            if (template) {
                // Asigurăm că subiectul și conținutul campaniei au prioritate dacă sunt diferite de șablon
                campaignSubjectInput.value = campaignData.subiect || template.subiect_implicit || '';
                campaignContentTextarea.value = campaignData.continut_personalizat_html || template.continut_html || '';
            }
        } else {
            campaignSubjectInput.value = campaignData.subiect || '';
            campaignContentTextarea.value = campaignData.continut_personalizat_html || '';
        }
    }


    // --- Funcționalitate Programare Trimitere ---
    sendOptionSelect.addEventListener('change', function() {
        if (this.value === 'schedule') {
            scheduleDateTimeGroup.style.display = 'block';
            scheduleDateTimeInput.setAttribute('required', 'required');
        } else {
            scheduleDateTimeGroup.style.display = 'none';
            scheduleDateTimeInput.removeAttribute('required');
        }
    });
    // Asigură starea corectă la încărcarea paginii
    sendOptionSelect.dispatchEvent(new Event('change'));

    // --- Funcționalitate Căutare în Liste de Destinatari ---
    function filterList(inputElement, containerElement, dataArray, fieldsToSearch) {
        inputElement.addEventListener('input', function() {
            const searchText = this.value.toLowerCase().trim();
            containerElement.querySelectorAll('.recipient-list-item').forEach(item => {
                const itemSearchText = item.getAttribute('data-search-text');
                if (searchText === '' || itemSearchText.includes(searchText)) {
                    item.style.display = 'flex'; // Folosim flex pentru layout-ul form-check
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Aplică filtrele de căutare
    filterList(searchExistingSubscribersInput, individualRecipientsTabPane, allSubscribersData, ['email', 'nume', 'prenume', 'status', 'sursa_abonare']);
    filterList(searchClientsForNewsletterInput, fromClientsTabPane, allClientsData, ['nume_companie', 'email_contact']);
    filterList(searchEmployeesForNewsletterInput, fromEmployeesTabPane, allEmployeesData, ['nume', 'prenume', 'email', 'functie']);

    // --- Logică pentru pre-selectarea destinatarilor la editare campanie ---
    if (campaignData && campaignData.destinatari_ids) {
        const selectedIds = JSON.parse(campaignData.destinatari_ids);
        document.querySelectorAll('.existing-subscriber-checkbox').forEach(checkbox => {
            if (selectedIds.includes(parseInt(checkbox.value))) {
                checkbox.checked = true;
            }
        });
    }
    if (campaignData && campaignData.destinatari_emails_manual) {
        document.getElementById('manualEmails').value = JSON.parse(campaignData.destinatari_emails_manual).join(', ');
    }
});
</script>
