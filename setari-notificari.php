<?php
session_start();
require_once 'db_connect.php';
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

// Preluăm setările existente (ar trebui să fie un singur rând cu id=1)
$setari_notificari = [
    'notificari_email_activate' => true,
    'notificari_sms_activate' => false,
    'notifica_expirare_documente_zile' => 30,
    'notifica_probleme_noi_email' => true,
    'notifica_revizii_programate_zile' => 7,
    'email_destinatie_notificari' => '',
    'sms_destinatie_notificari' => ''
];

$sql_settings = "SELECT * FROM setari_notificari WHERE id = 1";
$result_settings = $conn->query($sql_settings);
if ($result_settings && $result_settings->num_rows > 0) {
    $setari_notificari = array_merge($setari_notificari, $result_settings->fetch_assoc());
}
$conn->close();
?>

<title>NTS TOUR | Setări Notificări</title>

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
            <div class="breadcrumb-title pe-3">Setări</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Setări Notificări</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Configurare Notificări</h4>
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

                        <form id="notificationSettingsForm" action="process_setari_notificari.php" method="POST">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="1"> <!-- ID-ul este întotdeauna 1 pentru acest tabel -->
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <h5 class="card-title mt-2">Notificări Email</h5>
                                    <hr>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="emailNotifications" name="notificari_email_activate" value="1" <?php if ($setari_notificari['notificari_email_activate']) echo 'checked'; ?>>
                                        <label class="form-check-label" for="emailNotifications">Activează Notificări Email</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notifyNewProblemsEmail" name="notifica_probleme_noi_email" value="1" <?php if ($setari_notificari['notifica_probleme_noi_email']) echo 'checked'; ?>>
                                        <label class="form-check-label" for="notifyNewProblemsEmail">Notifică prin Email la Probleme Noi</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="emailDestinations" class="form-label">Adrese Email Destinație (separate prin virgulă):</label>
                                    <textarea class="form-control" id="emailDestinations" name="email_destinatie_notificari" rows="3" placeholder="email1@example.com, email2@example.com"><?php echo htmlspecialchars($setari_notificari['email_destinatie_notificari']); ?></textarea>
                                    <small class="form-text text-muted">Introduceți adresele de email unde doriți să primiți notificări, separate prin virgulă.</small>
                                </div>

                                <div class="col-12">
                                    <h5 class="card-title mt-4">Notificări SMS</h5>
                                    <hr>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="smsNotifications" name="notificari_sms_activate" value="1" <?php if ($setari_notificari['notificari_sms_activate']) echo 'checked'; ?>>
                                        <label class="form-check-label" for="smsNotifications">Activează Notificări SMS</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="smsDestinations" class="form-label">Numere Telefon Destinație (separate prin virgulă):</label>
                                    <textarea class="form-control" id="smsDestinations" name="sms_destinatie_notificari" rows="3" placeholder="07xxxxxxxx, 07yyyyyyyy"><?php echo htmlspecialchars($setari_notificari['sms_destinatie_notificari']); ?></textarea>
                                    <small class="form-text text-muted">Introduceți numerele de telefon unde doriți să primiți notificări, separate prin virgulă.</small>
                                </div>

                                <div class="col-12">
                                    <h5 class="card-title mt-4">Setări Specifice Notificărilor</h5>
                                    <hr>
                                </div>
                                <div class="col-md-6">
                                    <label for="docExpirationDays" class="form-label">Notifică Expirare Documente (zile înainte):</label>
                                    <input type="number" class="form-control" id="docExpirationDays" name="notifica_expirare_documente_zile" value="<?php echo htmlspecialchars($setari_notificari['notifica_expirare_documente_zile']); ?>" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="revizieScheduledDays" class="form-label">Notifică Revizii Programate (zile înainte):</label>
                                    <input type="number" class="form-control" id="revizieScheduledDays" name="notifica_revizii_programate_zile" value="<?php echo htmlspecialchars($setari_notificari['notifica_revizii_programate_zile']); ?>" min="1" required>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Salvează Setări Notificări</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>
