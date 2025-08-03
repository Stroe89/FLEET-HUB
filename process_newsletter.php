<?php
ob_start(); // Începe output buffering
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Funcție pentru a trimite un răspuns JSON
function sendJsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    error_log("PROCESS_NEWSLETTER.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            // --- Acțiuni Abonat ---
            case 'add_subscriber':
                $email = trim($_POST['email'] ?? '');
                $nume = trim($_POST['nume'] ?? '');
                $prenume = trim($_POST['prenume'] ?? '');
                $status = trim($_POST['status'] ?? 'activ');
                $sursa_abonare = trim($_POST['sursa_abonare'] ?? 'manual');

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Email invalid sau lipsă.");
                }

                $sql = "INSERT INTO abonati_newsletter (email, nume, prenume, data_abonare, status, sursa_abonare) VALUES (?, ?, ?, NOW(), ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD subscriber: " . $conn->error);
                $stmt->bind_param("sssss", $email, $nume, $prenume, $status, $sursa_abonare);
                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry
                        throw new Exception("Un abonat cu acest email există deja.");
                    }
                    throw new Exception("Eroare la adăugarea abonatului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Abonatul a fost adăugat cu succes!";
                sendJsonResponse('success', 'Abonatul a fost adăugat cu succes!');
                break;

            case 'edit_subscriber':
                $id = $_POST['id'] ?? null;
                $email = trim($_POST['email'] ?? '');
                $nume = trim($_POST['nume'] ?? '');
                $prenume = trim($_POST['prenume'] ?? '');
                $status = trim($_POST['status'] ?? 'activ');
                $sursa_abonare = trim($_POST['sursa_abonare'] ?? 'manual'); // Păstrăm sursa existentă

                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID abonat invalid pentru editare.");
                }
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Email invalid sau lipsă.");
                }

                $sql = "UPDATE abonati_newsletter SET email = ?, nume = ?, prenume = ?, status = ?, sursa_abonare = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT subscriber: " . $conn->error);
                $stmt->bind_param("sssssi", $email, $nume, $prenume, $status, $sursa_abonare, $id);
                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry
                        throw new Exception("Un alt abonat cu acest email există deja.");
                    }
                    throw new Exception("Eroare la actualizarea abonatului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Abonatul a fost actualizat cu succes!";
                sendJsonResponse('success', 'Abonatul a fost actualizat cu succes!');
                break;

            case 'delete_subscriber':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID abonat invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM abonati_newsletter WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE subscriber: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea abonatului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Abonatul a fost șters cu succes!";
                sendJsonResponse('success', 'Abonatul a fost șters cu succes!');
                break;

            // --- Acțiuni Adăugare Abonați din Clienți/Angajați (existente, neschimbate) ---
            case 'add_subscribers_from_clients':
                $client_ids = $_POST['client_ids'] ?? [];
                if (empty($client_ids)) {
                    throw new Exception("Niciun client selectat.");
                }
                $added_count = 0;
                $skipped_count = 0;

                foreach ($client_ids as $client_id) {
                    if (!is_numeric($client_id)) continue;

                    $sql_client = "SELECT nume_companie, email_contact FROM clienti WHERE id = ?";
                    $stmt_client = $conn->prepare($sql_client);
                    if ($stmt_client === false) {
                        error_log("Eroare pregătire query client: " . $conn->error);
                        $skipped_count++;
                        continue;
                    }
                    $stmt_client->bind_param("i", $client_id);
                    $stmt_client->execute();
                    $result_client = $stmt_client->get_result();
                    $client_data = $result_client->fetch_assoc();
                    $stmt_client->close();

                    if ($client_data && !empty($client_data['email_contact']) && filter_var($client_data['email_contact'], FILTER_VALIDATE_EMAIL)) {
                        $email = $client_data['email_contact'];
                        $nume = $client_data['nume_companie'];
                        $prenume = null; // Companiile nu au prenume
                        $sursa = 'client';

                        // Verifică dacă abonatul există deja
                        $sql_check = "SELECT id, status FROM abonati_newsletter WHERE email = ?";
                        $stmt_check = $conn->prepare($sql_check);
                        $stmt_check->bind_param("s", $email);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        $existing_subscriber = $result_check->fetch_assoc();
                        $stmt_check->close();

                        if ($existing_subscriber) {
                            // Dacă există, asigură-te că este activ
                            if ($existing_subscriber['status'] != 'activ') {
                                $stmt_update_status = $conn->prepare("UPDATE abonati_newsletter SET status = 'activ' WHERE id = ?");
                                $stmt_update_status->bind_param("i", $existing_subscriber['id']);
                                $stmt_update_status->execute();
                                $stmt_update_status->close();
                            }
                            // Nu adăugăm ID-ul la final_recipients_ids aici, deoarece această acțiune este doar pentru a adăuga în abonati_newsletter
                            // și apoi lista de abonați va fi reîmprospătată pe front-end
                        } else {
                            // Dacă nu există, adaugă-l ca nou abonat
                            $stmt_insert_new = $conn->prepare("INSERT INTO abonati_newsletter (email, nume, prenume, data_abonare, status, sursa_abonare) VALUES (?, ?, ?, NOW(), 'activ', ?)");
                            $stmt_insert_new->bind_param("ssss", $email, $nume, $prenume, $sursa);
                            if ($stmt_insert_new->execute()) {
                                $added_count++;
                            } else {
                                error_log("Eroare insert client: " . $stmt_insert_new->error);
                                $skipped_count++;
                            }
                            $stmt_insert_new->close();
                        }
                    } else {
                        $skipped_count++; // Email invalid sau lipsă
                    }
                }
                $_SESSION['success_message'] = "S-au adăugat " . $added_count . " abonați din clienți. S-au omis " . $skipped_count . " clienți (existenți sau cu email invalid).";
                sendJsonResponse('success', $_SESSION['success_message']);
                break;

            case 'add_subscribers_from_employees':
                $employee_ids = $_POST['employee_ids'] ?? [];
                if (empty($employee_ids)) {
                    throw new Exception("Niciun angajat selectat.");
                }
                $added_count = 0;
                $skipped_count = 0;

                foreach ($employee_ids as $employee_id) {
                    if (!is_numeric($employee_id)) continue;

                    $sql_employee = "SELECT nume, prenume, email FROM angajati WHERE id = ?";
                    $stmt_employee = $conn->prepare($sql_employee);
                    if ($stmt_employee === false) {
                        error_log("Eroare pregătire query angajat: " . $conn->error);
                        $skipped_count++;
                        continue;
                    }
                    $stmt_employee->bind_param("i", $employee_id);
                    $stmt_employee->execute();
                    $result_employee = $stmt_employee->get_result();
                    $employee_data = $result_employee->fetch_assoc();
                    $stmt_employee->close();

                    if ($employee_data && !empty($employee_data['email']) && filter_var($employee_data['email'], FILTER_VALIDATE_EMAIL)) {
                        $email = $employee_data['email'];
                        $nume = $employee_data['nume'];
                        $prenume = $employee_data['prenume'];
                        $sursa = 'angajat';

                        // Verifică dacă abonatul există deja
                        $sql_check = "SELECT id, status FROM abonati_newsletter WHERE email = ?";
                        $stmt_check = $conn->prepare($sql_check);
                        $stmt_check->bind_param("s", $email);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        $existing_subscriber = $result_check->fetch_assoc();
                        $stmt_check->close();

                        if ($existing_subscriber) {
                            if ($existing_subscriber['status'] != 'activ') {
                                $stmt_update_status = $conn->prepare("UPDATE abonati_newsletter SET status = 'activ' WHERE id = ?");
                                $stmt_update_status->bind_param("i", $existing_subscriber['id']);
                                $stmt_update_status->execute();
                                $stmt_update_status->close();
                            }
                            // Nu adăugăm ID-ul la final_recipients_ids aici
                        } else {
                            $stmt_insert_new = $conn->prepare("INSERT INTO abonati_newsletter (email, nume, prenume, data_abonare, status, sursa_abonare) VALUES (?, ?, ?, NOW(), 'activ', ?)");
                            $stmt_insert_new->bind_param("ssss", $email, $nume, $prenume, $sursa);
                            if ($stmt_insert_new->execute()) {
                                $added_count++;
                            } else {
                                error_log("Eroare insert angajat: " . $stmt_insert_new->error);
                                $skipped_count++;
                            }
                            $stmt_insert_new->close();
                        }
                    } else {
                        $skipped_count++; // Email invalid sau lipsă
                    }
                }
                $_SESSION['success_message'] = "S-au adăugat " . $added_count . " abonați din angajați. S-au omis " . $skipped_count . " angajați (existenți sau cu email invalid).";
                sendJsonResponse('success', $_SESSION['success_message']);
                break;

            // --- Acțiuni Șablon (existente, neschimbate) ---
            case 'add_template':
                $nume_template = trim($_POST['nume_template'] ?? '');
                $subiect_implicit = trim($_POST['subiect_implicit'] ?? '');
                $continut_html = $_POST['continut_html'] ?? '';

                if (empty($nume_template)) {
                    throw new Exception("Numele șablonului este obligatoriu.");
                }

                $sql = "INSERT INTO newsletter_templates (nume_template, subiect_implicit, continut_html) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD template: " . $conn->error);
                $stmt->bind_param("sss", $nume_template, $subiect_implicit, $continut_html);
                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry
                        throw new Exception("Un șablon cu acest nume există deja.");
                    }
                    throw new Exception("Eroare la adăugarea șablonului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Șablonul a fost adăugat cu succes!";
                sendJsonResponse('success', 'Șablonul a fost adăugat cu succes!');
                break;

            case 'edit_template':
                $id = $_POST['id'] ?? null;
                $nume_template = trim($_POST['nume_template'] ?? '');
                $subiect_implicit = trim($_POST['subiect_implicit'] ?? '');
                $continut_html = $_POST['continut_html'] ?? '';

                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID șablon invalid pentru editare.");
                }
                if (empty($nume_template)) {
                    throw new Exception("Numele șablonului este obligatoriu.");
                }

                $sql = "UPDATE newsletter_templates SET nume_template = ?, subiect_implicit = ?, continut_html = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT template: " . $conn->error);
                $stmt->bind_param("sssi", $nume_template, $subiect_implicit, $continut_html, $id);
                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry
                        throw new Exception("Un alt șablon cu acest nume există deja.");
                    }
                    throw new Exception("Eroare la actualizarea șablonului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Șablonul a fost actualizat cu succes!";
                sendJsonResponse('success', 'Șablonul a fost actualizat cu succes!');
                break;

            case 'delete_template':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID șablon invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM newsletter_templates WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE template: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    // Verificăm dacă ștergerea a eșuat din cauza unei constrângeri de cheie externă
                    if ($conn->errno == 1451) { 
                        throw new Exception("Șablonul nu poate fi șters deoarece este utilizat în campanii de newsletter existente. Vă rugăm să actualizați campaniile înainte de a șterge șablonul.");
                    }
                    throw new Exception("Eroare la ștergerea șablonului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Șablonul a fost șters cu succes!";
                sendJsonResponse('success', 'Șablonul a fost șters cu succes!');
                break;

            // --- Acțiuni Campanie Newsletter ---
            case 'create_campaign':
            case 'edit_campaign':
            case 'send_campaign_now': // Acțiune separată pentru trimitere imediată
                $campaign_id = $_POST['id'] ?? null;
                $nume_campanie = trim($_POST['nume_campanie'] ?? '');
                $subiect = trim($_POST['subiect'] ?? '');
                $id_template = empty($_POST['id_template']) ? null : (int)$_POST['id_template'];
                $continut_personalizat_html = $_POST['continut_personalizat_html'] ?? '';
                $send_option = $_POST['send_option'] ?? 'now';
                $data_trimitere = null;
                $status_campanie = 'draft'; // Status implicit la creare/salvare

                // Procesează destinatarii
                $recipient_ids = $_POST['recipient_ids'] ?? []; // ID-uri de abonați existenți
                $client_emails = $_POST['client_emails'] ?? []; // Email-uri de la clienți selectați
                $employee_emails = $_POST['employee_emails'] ?? []; // Email-uri de la angajați selectați
                $manual_emails_raw = trim($_POST['manual_emails'] ?? '');

                $final_recipients_ids = []; // Pentru a stoca ID-urile abonaților din baza de date

                // 1. Adaugă abonați existenți selectați
                if (!empty($recipient_ids)) {
                    foreach ($recipient_ids as $id) {
                        if (is_numeric($id)) {
                            $final_recipients_ids[] = (int)$id;
                        }
                    }
                }

                // 2. Adaugă email-uri din clienți și angajați, transformându-le în abonați dacă nu există
                $emails_to_check_and_add = array_merge($client_emails, $employee_emails);
                foreach ($emails_to_check_and_add as $email_addr) {
                    if (filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
                        $email_addr = trim($email_addr);
                        // Verifică dacă emailul există deja ca abonat activ
                        $stmt_check_email = $conn->prepare("SELECT id, status FROM abonati_newsletter WHERE email = ?");
                        $stmt_check_email->bind_param("s", $email_addr);
                        $stmt_check_email->execute();
                        $result_check_email = $stmt_check_email->get_result();
                        $existing_subscriber = $result_check_email->fetch_assoc();
                        $stmt_check_email->close();

                        if ($existing_subscriber) {
                            // Dacă există, asigură-te că este activ
                            if ($existing_subscriber['status'] != 'activ') {
                                $stmt_update_status = $conn->prepare("UPDATE abonati_newsletter SET status = 'activ' WHERE id = ?");
                                $stmt_update_status->bind_param("i", $existing_subscriber['id']);
                                $stmt_update_status->execute();
                                $stmt_update_status->close();
                            }
                            $final_recipients_ids[] = $existing_subscriber['id'];
                        } else {
                            // Dacă nu există, adaugă-l ca nou abonat
                            $source = (in_array($email_addr, $client_emails) ? 'client' : 'angajat');
                            $nume_prenume = ''; 
                            if (in_array($email_addr, $client_emails)) {
                                // Caută numele companiei din lista de clienți
                                $client_info = array_values(array_filter($GLOBALS['all_clients'], fn($c) => $c['email_contact'] == $email_addr));
                                if (!empty($client_info)) $nume_prenume = $client_info[0]['nume_companie'];
                            } else {
                                // Caută numele/prenumele din lista de angajați
                                $employee_info = array_values(array_filter($GLOBALS['all_employees'], fn($e) => $e['email'] == $email_addr));
                                if (!empty($employee_info)) $nume_prenume = $employee_info[0]['nume'] . ' ' . $employee_info[0]['prenume'];
                            }

                            $stmt_insert_new = $conn->prepare("INSERT INTO abonati_newsletter (email, nume, data_abonare, status, sursa_abonare) VALUES (?, ?, NOW(), 'activ', ?)");
                            $stmt_insert_new->bind_param("sss", $email_addr, $nume_prenume, $source);
                            $stmt_insert_new->execute();
                            $new_subscriber_id = $conn->insert_id;
                            $stmt_insert_new->close();
                            if ($new_subscriber_id) {
                                $final_recipients_ids[] = $new_subscriber_id;
                            }
                        }
                    }
                }
                
                // 3. Adaugă email-uri introduse manual
                $manual_emails_array = [];
                if (!empty($manual_emails_raw)) {
                    $lines = explode("\n", $manual_emails_raw);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        $email_match = [];
                        $nume = ''; $prenume = '';
                        if (preg_match('/^(.*)<(.+?)>$/', $line, $email_match)) { // Format "Nume Prenume <email>"
                            $email_addr = trim($email_match[2]);
                            $name_part = trim($email_match[1]);
                            // Încercăm să separăm nume și prenume dacă formatul este "Nume Prenume"
                            $name_parts = explode(' ', $name_part);
                            $nume = $name_parts[0] ?? '';
                            $prenume = $name_parts[1] ?? '';
                        } else {
                            $email_addr = $line;
                        }
                        
                        if (filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
                            // Verifică dacă emailul există deja ca abonat activ
                            $stmt_check_email = $conn->prepare("SELECT id, status FROM abonati_newsletter WHERE email = ?");
                            $stmt_check_email->bind_param("s", $email_addr);
                            $stmt_check_email->execute();
                            $result_check_email = $stmt_check_email->get_result();
                            $existing_subscriber = $result_check_email->fetch_assoc();
                            $stmt_check_email->close();

                            if ($existing_subscriber) {
                                if ($existing_subscriber['status'] != 'activ') {
                                    $stmt_update_status = $conn->prepare("UPDATE abonati_newsletter SET status = 'activ' WHERE id = ?");
                                    $stmt_update_status->bind_param("i", $existing_subscriber['id']);
                                    $stmt_update_status->execute();
                                    $stmt_update_status->close();
                                }
                                $final_recipients_ids[] = $existing_subscriber['id'];
                            } else {
                                $stmt_insert_new = $conn->prepare("INSERT INTO abonati_newsletter (email, nume, prenume, data_abonare, status, sursa_abonare) VALUES (?, ?, ?, NOW(), 'activ', 'manual')");
                                $stmt_insert_new->bind_param("ssss", $email_addr, $nume, $prenume, $sursa);
                                $stmt_insert_new->execute();
                                $new_subscriber_id = $conn->insert_id;
                                $stmt_insert_new->close();
                                if ($new_subscriber_id) {
                                    $final_recipients_ids[] = $new_subscriber_id;
                                }
                            }
                            $manual_emails_array[] = $email_addr; // Adaugă doar emailul valid la lista de emailuri manuale
                        }
                    }
                }
                
                $final_recipients_ids = array_values(array_unique($final_recipients_ids)); // Elimină duplicatele și reindexează
                $numar_destinatari = count($final_recipients_ids);

                // Setează statusul și data de trimitere/programare
                if ($send_option === 'schedule') {
                    $data_trimitere = $_POST['data_trimitere'] ?? null;
                    if (empty($data_trimitere)) {
                        throw new Exception("Data și ora de programare sunt obligatorii.");
                    }
                    $status_campanie = 'programata';
                } else { // 'now' sau 'send_campaign_now'
                    $data_trimitere = date('Y-m-d H:i:s');
                    $status_campanie = 'trimisa'; // Se trimite imediat
                }

                $destinatari_ids_json = json_encode($final_recipients_ids);
                $manual_emails_json = json_encode($manual_emails_array); // Salvează email-urile manuale procesate

                if ($action === 'create_campaign') {
                    $sql = "INSERT INTO campanii_newsletter (nume_campanie, subiect, id_template, continut_personalizat_html, data_creare, data_trimitere, status, numar_destinatari, destinatari_ids, destinatari_emails_manual) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului CREATE campaign: " . $conn->error);
                    
                    $stmt->bind_param("ssisssis", $nume_campanie, $subiect, $id_template, $continut_personalizat_html, $data_trimitere, $status_campanie, $numar_destinatari, $destinatari_ids_json, $manual_emails_json);
                    if (!$stmt->execute()) {
                        throw new Exception("Eroare la crearea campaniei: " . $stmt->error);
                    }
                    $stmt->close();
                    $_SESSION['success_message'] = "Campania a fost creată cu succes!";
                } else { // edit_campaign sau send_campaign_now
                    if (empty($campaign_id) || !is_numeric($campaign_id)) {
                        throw new Exception("ID campanie invalid pentru editare/trimitere.");
                    }

                    $sql = "UPDATE campanii_newsletter SET nume_campanie = ?, subiect = ?, id_template = ?, continut_personalizat_html = ?, data_trimitere = ?, status = ?, numar_destinatari = ?, destinatari_ids = ?, destinatari_emails_manual = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT campaign: " . $conn->error);

                    $stmt->bind_param("ssisssisii", $nume_campanie, $subiect, $id_template, $continut_personalizat_html, $data_trimitere, $status_campanie, $numar_destinatari, $destinatari_ids_json, $manual_emails_json, $campaign_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Eroare la actualizarea campaniei: " . $stmt->error);
                    }
                    $stmt->close();
                    $_SESSION['success_message'] = "Campania a fost actualizată cu succes!";
                }
                
                // Dacă statusul este 'trimisa', declanșează trimiterea reală (placeholder)
                if ($status_campanie === 'trimisa') {
                    // AICI AR VENI LOGICA REALĂ DE TRIMITERE EMAILURI
                    // Aceasta ar implica:
                    // 1. Preluarea listei de emailuri finale (din $final_recipients_ids și $manual_emails_array)
                    // 2. Utilizarea unei biblioteci de email (ex: PHPMailer) și a setărilor SMTP din setari_newsletter
                    // 3. Iterarea prin emailuri și trimiterea fiecărui email cu conținutul campaniei
                    // 4. Actualizarea numar_deschideri și numar_clickuri (prin tracking pixels/link-uri, ulterior)
                    error_log("DEBUG: Campania '" . $nume_campanie . "' cu subiectul '" . $subiect . "' către " . $numar_destinatari . " destinatari a fost 'trimisă' (simulat).");
                    $_SESSION['success_message'] .= " Emailurile au fost simulate a fi trimise.";
                }
                
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_NEWSLETTER.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    
    // Redirecționăm întotdeauna înapoi la pagina de unde a venit cererea
    // Sau la o pagină implicită dacă HTTP_REFERER nu este setat
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'lista-abonati.php';
    header("Location: " . $redirect_url);
    exit();

} else {
    sendJsonResponse('error', 'Cerere invalidă.');
}
ob_end_flush();
?>
