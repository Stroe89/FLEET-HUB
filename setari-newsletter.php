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

// Preluăm setările curente din baza de date
$settings = [
    'nume_expeditor_implicit' => '',
    'email_expeditor_implicit' => '',
    'adresa_raspuns_implicit' => '',
    'text_subsol_implicit' => '',
    'integrare_smtp_host' => '',
    'integrare_smtp_port' => '',
    'integrare_smtp_user' => '',
    'integrare_smtp_pass' => ''
];

$sql_settings = "SELECT * FROM setari_newsletter WHERE id = 1";
$result_settings = $conn->query($sql_settings);
if ($result_settings && $result_settings->num_rows > 0) {
    $settings = array_merge($settings, $result_settings->fetch_assoc());
}

$conn->close(); // Închidem conexiunea la baza de date
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Setări Newsletter</title>

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

    /* Stiluri specifice pentru butoane */
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success {
        font-weight: bold !important;
        padding: 0.8rem 1.5rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out !important, box-shadow 0.2s ease-in-out !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
    }
    .btn-primary { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; }
    .btn-info { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: #fff !important; }
    .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #343a40 !important; }
    .btn-success { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Newsletter</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Setări Newsletter</li>
                </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Setări Generale Newsletter</h4>
                        <p class="text-muted">Configurează detaliile expeditorului și setările SMTP pentru trimiterea newsletter-elor.</p>
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

                        <form action="process_newsletter_settings.php" method="POST">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="id" value="1"> <!-- Presupunem că avem o singură înregistrare de setări cu ID 1 -->

                            <h5 class="mb-3">Detalii Expeditor Implicit</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="numeExpeditor" class="form-label">Nume Expeditor Implicit:</label>
                                    <input type="text" class="form-control" id="numeExpeditor" name="nume_expeditor_implicit" value="<?php echo htmlspecialchars($settings['nume_expeditor_implicit']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="emailExpeditor" class="form-label">Email Expeditor Implicit:</label>
                                    <input type="email" class="form-control" id="emailExpeditor" name="email_expeditor_implicit" value="<?php echo htmlspecialchars($settings['email_expeditor_implicit']); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="adresaRaspuns" class="form-label">Adresă de Răspuns Implicită (Reply-To):</label>
                                    <input type="email" class="form-control" id="adresaRaspuns" name="adresa_raspuns_implicit" value="<?php echo htmlspecialchars($settings['adresa_raspuns_implicit']); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="textSubsol" class="form-label">Text Subsol Implicit (Footer):</label>
                                    <textarea class="form-control" id="textSubsol" name="text_subsol_implicit" rows="3"><?php echo htmlspecialchars($settings['text_subsol_implicit']); ?></textarea>
                                </div>
                            </div>

                            <h5 class="mb-3 mt-4">Integrare SMTP (Opțional)</h5>
                            <p class="text-muted">Completează aceste detalii doar dacă dorești să utilizezi un server SMTP extern pentru trimiterea email-urilor.</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="smtpHost" class="form-label">SMTP Host:</label>
                                    <input type="text" class="form-control" id="smtpHost" name="integrare_smtp_host" value="<?php echo htmlspecialchars($settings['integrare_smtp_host']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtpPort" class="form-label">SMTP Port:</label>
                                    <input type="number" class="form-control" id="smtpPort" name="integrare_smtp_port" value="<?php echo htmlspecialchars($settings['integrare_smtp_port']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtpUser" class="form-label">SMTP Utilizator:</label>
                                    <input type="text" class="form-control" id="smtpUser" name="integrare_smtp_user" value="<?php echo htmlspecialchars($settings['integrare_smtp_user']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="smtpPass" class="form-label">SMTP Parolă:</label>
                                    <input type="password" class="form-control" id="smtpPass" name="integrare_smtp_pass" value="<?php echo htmlspecialchars($settings['integrare_smtp_pass']); ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Salvează Setările</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>
