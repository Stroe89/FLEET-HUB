<?php
ob_start(); // Începe output buffering
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Răspunsul va fi JSON

$response = ['status' => 'error', 'message' => 'Acțiune invalidă sau cerere incorectă.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null;
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $start_date = $_POST['start_date'] ?? '';
                $end_date = $_POST['end_date'] ?? null;
                $event_type = $_POST['event_type'] ?? 'Implicit';
                $is_important = isset($_POST['is_important']) ? 1 : 0;
                $id_vehicul = empty($_POST['id_vehicul']) ? null : (int)$_POST['id_vehicul']; // Cast la int sau null
                $id_angajat = empty($_POST['id_angajat']) ? null : (int)$_POST['id_angajat']; // Cast la int sau null

                if (empty($title)) {
                    throw new Exception("Titlul evenimentului este obligatoriu.");
                }
                if (empty($start_date)) {
                    throw new Exception("Data de început este obligatorie.");
                }

                // Convert datetime-local format to MySQL datetime format
                $start_date_mysql = date('Y-m-d H:i:s', strtotime($start_date));
                $end_date_mysql = $end_date ? date('Y-m-d H:i:s', strtotime($end_date)) : null;

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO evenimente_calendar (title, description, start_date, end_date, event_type, is_important, id_vehicul, id_angajat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("sssssiii", $title, $description, $start_date_mysql, $end_date_mysql, $event_type, $is_important, $id_vehicul, $id_angajat);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID eveniment invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE evenimente_calendar SET title = ?, description = ?, start_date = ?, end_date = ?, event_type = ?, is_important = ?, id_vehicul = ?, id_angajat = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("sssssiiii", $title, $description, $start_date_mysql, $end_date_mysql, $event_type, $is_important, $id_vehicul, $id_angajat, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la executarea operației: " . $stmt->error);
                }
                $stmt->close();
                $response = ['status' => 'success', 'message' => 'Eveniment salvat cu succes!'];
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID eveniment invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM evenimente_calendar WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) throw new Exception("Eroare la ștergerea evenimentului: " . $stmt->error);
                $stmt->close();
                $response = ['status' => 'success', 'message' => 'Eveniment șters cu succes!'];
                break;

            case 'fetch':
                // Această acțiune este gestionată mai jos, în blocul GET
                throw new Exception("Acțiunea 'fetch' trebuie să fie o cerere GET.");
                break;

            default:
                throw new Exception("Acțiune necunoscută.");
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'fetch') {
    // Permitem cererile GET pentru fetch events (FullCalendar le face GET)
    try {
        $events = [];
        $stmt = $conn->prepare("SELECT id, title, description, start_date, end_date, event_type, is_important, id_vehicul, id_angajat FROM evenimente_calendar");
        if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului FETCH: " . $conn->error);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $event_class_names = [];
            if ($row['event_type']) {
                $event_class_names[] = 'fc-event-' . strtolower(str_replace(' ', '-', $row['event_type']));
            }
            if ($row['is_important'] == 1) {
                $event_class_names[] = 'fc-event-important';
            }

            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'start' => $row['start_date'],
                'end' => $row['end_date'],
                'classNames' => $event_class_names,
                'extendedProps' => [
                    'description' => $row['description'],
                    'event_type' => $row['event_type'],
                    'is_important' => $row['is_important'],
                    'id_vehicul' => $row['id_vehicul'],
                    'id_angajat' => $row['id_angajat']
                ]
            ];
        }
        $stmt->close();
        echo json_encode($events);
        exit();
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
        // Nu folosim header("Location") aici, deoarece este un răspuns AJAX
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
} else {
    // Dacă cererea nu este POST sau GET cu action=fetch
    $response = ['status' => 'error', 'message' => 'Metodă de cerere nepermisă sau acțiune nespecificată.'];
}

echo json_encode($response);
ob_end_flush();
?>
