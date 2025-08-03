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

    error_log("PROCESS_MARKETING.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'create_post':
            case 'edit_post': // Acțiune pentru salvarea ca draft sau editare
                $post_id = $_POST['post_id'] ?? null;
                $titlu_postare = trim($_POST['titlu_postare'] ?? '');
                $continut_text = trim($_POST['continut_text'] ?? '');
                $continut_html = $_POST['continut_html'] ?? null; 
                $platforms = json_decode($_POST['platforms'] ?? '[]', true); 
                $data_programare = empty($_POST['data_programare']) ? null : $_POST['data_programare'];
                $status_post = $_POST['status'] ?? 'draft'; // Statusul trimis de formular

                $message_success = "Postarea a fost salvată ca draft.";
                if ($status_post === 'publicata') {
                    $message_success = "Postarea a fost publicată imediat!";
                } elseif ($status_post === 'programata') {
                    $message_success = "Postarea a fost programată cu succes!";
                }

                if (empty($titlu_postare) || empty($continut_text)) {
                    throw new Exception("Titlul și conținutul text sunt obligatorii.");
                }
                if (empty($platforms)) {
                    throw new Exception("Trebuie să selectezi cel puțin o platformă.");
                }

                $platforms_json = json_encode($platforms);
                $destinatari_suplimentari_json = json_encode([]); 

                if ($action === 'edit_post' && $post_id) { // Editare postare existentă
                    $sql = "UPDATE marketing_posts SET titlu_postare = ?, continut_text = ?, continut_html = ?, platforme_selectate = ?, data_programare = ?, status = ?, destinatari_suplimentari = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului UPDATE post: " . $conn->error);
                    $stmt->bind_param("sssssssi", $titlu_postare, $continut_text, $continut_html, $platforms_json, $data_programare, $status_post, $destinatari_suplimentari_json, $post_id);
                } else { // Creare postare nouă
                    $sql = "INSERT INTO marketing_posts (titlu_postare, continut_text, continut_html, platforme_selectate, data_programare, status, data_creare, destinatari_suplimentari) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului CREATE post: " . $conn->error);
                    $stmt->bind_param("sssssss", $titlu_postare, $continut_text, $continut_html, $platforms_json, $data_programare, $status_post, $destinatari_suplimentari_json);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea postării: " . $stmt->error);
                }
                $stmt->close();
                
                $_SESSION['success_message'] = $message_success;

                if ($status_post === 'publicata') {
                    error_log("DEBUG: Postare '" . $titlu_postare . "' cu status '" . $status_post . "' către platforme: " . implode(', ', $platforms) . " (simulat).");
                    $_SESSION['success_message'] .= " Postarea a fost simulată a fi publicată.";
                }
                
                break;

            case 'delete_post':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID postare invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM marketing_posts WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE post: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea postării: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Postarea a fost ștearsă cu succes!";
                break;

            case 'fetch_calendar_posts':
                $start = $_POST['start'] ?? '';
                $end = $_POST['end'] ?? '';
                $events = [];

                if (!tableExists($conn, 'marketing_posts')) {
                     sendJsonResponse('success', 'Tabelul marketing_posts nu există, se folosesc date mock.', []);
                     exit();
                }

                $sql = "SELECT id, titlu_postare, data_programare, status, platforme_selectate FROM marketing_posts WHERE (data_programare BETWEEN ? AND ?) OR (status = 'publicata' AND data_creare BETWEEN ? AND ?) OR (status = 'draft')";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului calendar posts: " . $conn->error);
                $stmt->bind_param("ssss", $start, $end, $start, $end);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $event_start = $row['data_programare'];
                    if ($row['status'] === 'draft' && empty($event_start)) {
                        $event_start = date('Y-m-d H:i:s'); 
                    } else if (empty($event_start)) {
                        continue;
                    }

                    $events[] = [
                        'id' => $row['id'],
                        'title' => $row['titlu_postare'],
                        'start' => $event_start,
                        'end' => $event_start, 
                        'allDay' => false,
                        'extendedProps' => [
                            'status' => $row['status'],
                            'platforme_selectate' => $row['platforme_selectate'],
                        ],
                        'classNames' => ['fc-event-' . str_replace(' ', '_', $row['status'])] 
                    ];
                }
                $stmt->close();
                sendJsonResponse('success', 'Evenimente calendar preluate cu succes.', $events);
                break;

            case 'update_post_schedule': 
                $id = $_POST['id'] ?? null;
                $data_programare = $_POST['data_programare'] ?? null;
                $status = $_POST['status'] ?? 'programata'; 

                if (empty($id) || !is_numeric($id) || empty($data_programare)) {
                    throw new Exception("ID postare sau dată programare invalidă.");
                }

                $sql = "UPDATE marketing_posts SET data_programare = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului UPDATE schedule: " . $conn->error);
                $stmt->bind_param("ssi", $data_programare, $status, $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la actualizarea programării postării: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Programarea postării a fost actualizată!";
                sendJsonResponse('success', 'Programarea postării a fost actualizată!');
                break;

            // --- Acțiuni Șablon Marketing (existente, neschimbate) ---
            case 'add_marketing_template':
                $nume_template = trim($_POST['nume_template'] ?? '');
                $titlu_implicit = trim($_POST['titlu_implicit'] ?? '');
                $continut_text_implicit = trim($_POST['continut_text_implicit'] ?? '');
                $continut_html_implicit = $_POST['continut_html_implicit'] ?? null;
                $platforme_compatibile = json_encode($_POST['platforms'] ?? []);

                if (empty($nume_template) || empty($continut_text_implicit)) {
                    throw new Exception("Numele șablonului și conținutul text sunt obligatorii.");
                }

                $sql = "INSERT INTO marketing_templates (nume_template, titlu_implicit, continut_text_implicit, continut_html_implicit, platforme_compatibile, data_creare) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD marketing template: " . $conn->error);
                $stmt->bind_param("sssss", $nume_template, $titlu_implicit, $continut_text_implicit, $continut_html_implicit, $platforme_compatibile);
                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry
                        throw new Exception("Un șablon cu acest nume există deja.");
                    }
                    throw new Exception("Eroare la adăugarea șablonului de marketing: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Șablonul de marketing a fost adăugat cu succes!";
                sendJsonResponse('success', 'Șablonul de marketing a fost adăugat cu succes!');
                break;

            case 'edit_marketing_template':
                $id = $_POST['id'] ?? null;
                $nume_template = trim($_POST['nume_template'] ?? '');
                $titlu_implicit = trim($_POST['titlu_implicit'] ?? '');
                $continut_text_implicit = trim($_POST['continut_text_implicit'] ?? '');
                $continut_html_implicit = $_POST['continut_html_implicit'] ?? null;
                $platforme_compatibile = json_encode($_POST['platforms'] ?? []);

                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID șablon invalid pentru editare.");
                }
                if (empty($nume_template) || empty($continut_text_implicit)) {
                    throw new Exception("Numele șablonului și conținutul text sunt obligatorii.");
                }

                $sql = "UPDATE marketing_templates SET nume_template = ?, titlu_implicit = ?, continut_text_implicit = ?, continut_html_implicit = ?, platforme_compatibile = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT marketing template: " . $conn->error);
                $stmt->bind_param("sssssi", $nume_template, $titlu_implicit, $continut_text_implicit, $continut_html_implicit, $platforme_compatibile, $id);
                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry
                        throw new Exception("Un alt șablon cu acest nume există deja.");
                    }
                    throw new Exception("Eroare la actualizarea șablonului de marketing: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Șablonul de marketing a fost actualizat cu succes!";
                sendJsonResponse('success', 'Șablonul de marketing a fost actualizat cu succes!');
                break;

            case 'delete_marketing_template':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID șablon invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM marketing_templates WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE marketing template: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea șablonului de marketing: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Șablonul de marketing a fost șters cu succes!";
                sendJsonResponse('success', 'Șablonul de marketing a fost șters cu succes!');
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_MARKETING.PHP: Eroare în tranzacție: " . $e->getMessage());
        sendJsonResponse('error', $e->getMessage()); // Trimite răspuns JSON și în caz de eroare
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    
    // Redirecționăm doar dacă acțiunea nu a trimis deja un răspuns JSON
    if (!headers_sent()) {
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'dashboard-marketing.php';
        header("Location: " . $redirect_url);
        exit();
    }

} else {
    sendJsonResponse('error', 'Cerere invalidă.');
}
ob_end_flush();
?>
