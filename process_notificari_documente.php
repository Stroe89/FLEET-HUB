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

    error_log("PROCESS_NOTIFICARI_DOCUMENTE.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'save_settings':
                $id = $_POST['id'] ?? 1; // ID-ul este întotdeauna 1
                $notificari_email_activate = isset($_POST['notificari_email_activate']) ? 1 : 0;
                $notificari_sms_activate = isset($_POST['notificari_sms_activate']) ? 1 : 0;
                $notifica_expirare_documente_zile = (int)($_POST['notifica_expirare_documente_zile'] ?? 30);
                $email_destinatie_notificari = trim($_POST['email_destinatie_notificari'] ?? '');
                $sms_destinatie_notificari = trim($_POST['sms_destinatie_notificari'] ?? '');

                // Validări de bază
                if ($notifica_expirare_documente_zile < 1) {
                    throw new Exception("Numărul de zile pentru notificări trebuie să fie cel puțin 1.");
                }
                if (!empty($email_destinatie_notificari)) {
                    $emails = explode(',', $email_destinatie_notificari);
                    foreach ($emails as $email) {
                        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Una sau mai multe adrese de email sunt invalide.");
                        }
                    }
                }
                if (!empty($sms_destinatie_notificari)) {
                    $phones = explode(',', $sms_destinatie_notificari);
                    foreach ($phones as $phone) {
                        if (!preg_match('/^[0-9\s\-\(\)\+]+$/', trim($phone))) {
                            throw new Exception("Unul sau mai multe numere de telefon sunt invalide.");
                        }
                    }
                }

                // Folosim INSERT ... ON DUPLICATE KEY UPDATE pentru a insera/actualiza rândul cu id=1
                $stmt = $conn->prepare("
                    INSERT INTO setari_notificari (id, notificari_email_activate, notificari_sms_activate, notifica_expirare_documente_zile, email_destinatie_notificari, sms_destinatie_notificari)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        notificari_email_activate = VALUES(notificari_email_activate),
                        notificari_sms_activate = VALUES(notificari_sms_activate),
                        notifica_expirare_documente_zile = VALUES(notifica_expirare_documente_zile),
                        email_destinatie_notificari = VALUES(email_destinatie_notificari),
                        sms_destinatie_notificari = VALUES(sms_destinatie_notificari)
                ");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului SAVE SETTINGS: " . $conn->error);
                $stmt->bind_param("iiiiss", $id, $notificari_email_activate, $notificari_sms_activate, $notifica_expirare_documente_zile, $email_destinatie_notificari, $sms_destinatie_notificari);

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea setărilor de notificări: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Setările de notificare au fost salvate cu succes!";
                error_log("PROCESS_NOTIFICARI_DOCUMENTE.PHP: Setări notificări salvate cu succes.");
                break;

            case 'send_notification':
                $document_id = $_POST['document_id'] ?? null;
                $vehicul_info = $_POST['vehicul_info'] ?? 'N/A';
                $document_name = $_POST['document_name'] ?? 'N/A';
                $data_expirare = $_POST['data_expirare'] ?? 'N/A';

                if (empty($document_id) || !is_numeric($document_id)) {
                    throw new Exception("ID document invalid pentru trimitere notificare.");
                }

                // Preluăm setările curente de notificare
                $sql_settings = "SELECT notificari_email_activate, notificari_sms_activate, email_destinatie_notificari, sms_destinatie_notificari FROM setari_notificari WHERE id = 1";
                $result_settings = $conn->query($sql_settings);
                $current_settings = $result_settings->fetch_assoc();

                $message_subject = "Avertisment Expirare Document: " . $document_name . " pentru " . $vehicul_info;
                $message_body = "Documentul '" . $document_name . "' pentru vehiculul '" . $vehicul_info . "' expiră la data de " . $data_expirare . ". Vă rugăm să luați măsuri.";

                $notification_sent = false;

                // Logica pentru trimitere EMAIL
                if ($current_settings['notificari_email_activate'] && !empty($current_settings['email_destinatie_notificari'])) {
                    $email_recipients = explode(',', $current_settings['email_destinatie_notificari']);
                    foreach ($email_recipients as $recipient_email) {
                        $recipient_email = trim($recipient_email);
                        if (filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
                            // AICI AR TREBUI SĂ INTEGRAȚI O BIBLIOTECĂ DE TRIMITERE EMAIL (ex: PHPMailer)
                            // Exemplu placeholder:
                            // mail($recipient_email, $message_subject, $message_body);
                            error_log("PROCESS_NOTIFICARI_DOCUMENTE.PHP: Notificare EMAIL trimisă către " . $recipient_email . " pentru document ID " . $document_id);
                            $notification_sent = true;
                        }
                    }
                }

                // Logica pentru trimitere SMS / WhatsApp
                if ($current_settings['notificari_sms_activate'] && !empty($current_settings['sms_destinatie_notificari'])) {
                    $sms_recipients = explode(',', $current_settings['sms_destinatie_notificari']);
                    foreach ($sms_recipients as $recipient_phone) {
                        $recipient_phone = trim($recipient_phone);
                        // AICI AR TREBUI SĂ INTEGRAȚI O BIBLIOTECĂ/API PENTRU SMS/WHATSAPP (ex: Twilio, WhatsApp Business API)
                        // Exemplu placeholder:
                        // send_sms_via_api($recipient_phone, $message_body);
                        error_log("PROCESS_NOTIFICARI_DOCUMENTE.PHP: Notificare SMS/WhatsApp trimisă către " . $recipient_phone . " pentru document ID " . $document_id);
                        $notification_sent = true;
                    }
                }

                if ($notification_sent) {
                    $_SESSION['success_message'] = "Notificarea a fost trimisă cu succes!";
                } else {
                    $_SESSION['error_message'] = "Notificarea nu a putut fi trimisă. Verificați setările sau dacă sunt activate metode de notificare.";
                }
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_NOTIFICARI_DOCUMENTE.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: notificari-documente-expirate.php"); // Redirecționăm întotdeauna la pagina de alerte documente
    exit();

} else {
    error_log("PROCESS_NOTIFICARI_DOCUMENTE.PHP: Cerere non-POST. Redirecționare.");
    header("Location: notificari-documente-expirate.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
