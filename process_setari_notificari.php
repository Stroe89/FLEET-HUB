<?php
ob_start(); // Începe output buffering
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    error_log("PROCESS_SETARI_NOTIFICARI.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'save':
                $id = $_POST['id'] ?? 1; // ID-ul este întotdeauna 1
                $notificari_email_activate = isset($_POST['notificari_email_activate']) ? 1 : 0;
                $notificari_sms_activate = isset($_POST['notificari_sms_activate']) ? 1 : 0;
                $notifica_expirare_documente_zile = (int)($_POST['notifica_expirare_documente_zile'] ?? 30);
                $notifica_probleme_noi_email = isset($_POST['notifica_probleme_noi_email']) ? 1 : 0;
                $notifica_revizii_programate_zile = (int)($_POST['notifica_revizii_programate_zile'] ?? 7);
                $email_destinatie_notificari = trim($_POST['email_destinatie_notificari'] ?? '');
                $sms_destinatie_notificari = trim($_POST['sms_destinatie_notificari'] ?? '');

                // Validări de bază
                if ($notifica_expirare_documente_zile < 1 || $notifica_revizii_programate_zile < 1) {
                    throw new Exception("Numărul de zile pentru notificări trebuie să fie cel puțin 1.");
                }
                // Validare format email-uri (simplificată)
                if (!empty($email_destinatie_notificari)) {
                    $emails = explode(',', $email_destinatie_notificari);
                    foreach ($emails as $email) {
                        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Una sau mai multe adrese de email sunt invalide.");
                        }
                    }
                }
                // Validare format numere de telefon (simplificată)
                if (!empty($sms_destinatie_notificari)) {
                    $phones = explode(',', $sms_destinatie_notificari);
                    foreach ($phones as $phone) {
                        if (!preg_match('/^[0-9\s\-\(\)\+]+$/', trim($phone))) { // Permite cifre, spații, -, (), +
                            throw new Exception("Unul sau mai multe numere de telefon sunt invalide.");
                        }
                    }
                }


                // Folosim INSERT ... ON DUPLICATE KEY UPDATE pentru a insera/actualiza rândul cu id=1
                $stmt = $conn->prepare("
                    INSERT INTO setari_notificari (id, notificari_email_activate, notificari_sms_activate, notifica_expirare_documente_zile, notifica_probleme_noi_email, notifica_revizii_programate_zile, email_destinatie_notificari, sms_destinatie_notificari)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        notificari_email_activate = VALUES(notificari_email_activate),
                        notificari_sms_activate = VALUES(notificari_sms_activate),
                        notifica_expirare_documente_zile = VALUES(notifica_expirare_documente_zile),
                        notifica_probleme_noi_email = VALUES(notifica_probleme_noi_email),
                        notifica_revizii_programate_zile = VALUES(notifica_revizii_programate_zile),
                        email_destinatie_notificari = VALUES(email_destinatie_notificari),
                        sms_destinatie_notificari = VALUES(sms_destinatie_notificari)
                ");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului SAVE: " . $conn->error);
                $stmt->bind_param("iiiiisss", $id, $notificari_email_activate, $notificari_sms_activate, $notifica_expirare_documente_zile, $notifica_probleme_noi_email, $notifica_revizii_programate_zile, $email_destinatie_notificari, $sms_destinatie_notificari);

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea setărilor de notificări: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Setările de notificări au fost salvate cu succes!";
                error_log("PROCESS_SETARI_NOTIFICARI.PHP: Setări notificări salvate cu succes.");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_SETARI_NOTIFICARI.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: setari-notificari.php"); // Redirecționăm întotdeauna la pagina de setări notificări
    exit();

} else {
    error_log("PROCESS_SETARI_NOTIFICARI.PHP: Cerere non-POST. Redirecționare.");
    header("Location: setari-notificari.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
