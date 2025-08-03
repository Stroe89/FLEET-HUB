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

    error_log("PROCESS_REVIZII.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'add':
            case 'edit': // Acțiune pentru editare completă sau actualizare status/finalizare
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_vehicul = $_POST['id_vehicul'] ?? null;
                $tip_revizie = trim($_POST['tip_revizie'] ?? '');
                $kilometraj_programat = empty(trim($_POST['kilometraj_programat'] ?? '')) ? null : (int)trim($_POST['kilometraj_programat']);
                $data_programata = $_POST['data_programata'] ?? null;
                
                // Câmpuri de finalizare (pot fi NULL sau goale dacă nu sunt completate)
                $kilometraj_efectuat = empty(trim($_POST['kilometraj_efectuat'] ?? '')) ? null : (int)trim($_POST['kilometraj_efectuat']);
                $data_efectuare = empty($_POST['data_efectuare']) ? null : $_POST['data_efectuare'];
                $cost_estimat = empty(trim($_POST['cost_estimat'] ?? '')) ? null : (float)trim($_POST['cost_estimat']);
                $cost_real = empty(trim($_POST['cost_real'] ?? '')) ? null : (float)trim($_POST['cost_real']);
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);
                $status = trim($_POST['status'] ?? 'Programată');
                $prioritate = trim($_POST['prioritate'] ?? 'Normală');
                $responsabil_id = empty(trim($_POST['responsabil_id'] ?? '')) ? null : (int)trim($_POST['responsabil_id']);

                // Validări de bază
                if (empty($id_vehicul) || !is_numeric($id_vehicul)) {
                    throw new Exception("Vehiculul este obligatoriu.");
                }
                if (empty($tip_revizie)) {
                    throw new Exception("Tipul reviziei este obligatoriu.");
                }
                if (empty($data_programata)) {
                    throw new Exception("Data programată este obligatorie.");
                }

                if ($action == 'add') {
                    $sql = "INSERT INTO plan_revizii (id_vehicul, tip_revizie, kilometraj_programat, data_programata, cost_estimat, prioritate, responsabil_id, observatii, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD revizie: " . $conn->error);
                    $stmt->bind_param("isiidsiss", $id_vehicul, $tip_revizie, $kilometraj_programat, $data_programata, $cost_estimat, $prioritate, $responsabil_id, $observatii, $status);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID revizie invalid pentru editare.");
                    }
                    $sql = "UPDATE plan_revizii SET id_vehicul = ?, tip_revizie = ?, kilometraj_programat = ?, data_programata = ?, kilometraj_efectuat = ?, data_efectuare = ?, cost_estimat = ?, cost_real = ?, observatii = ?, status = ?, prioritate = ?, responsabil_id = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT revizie: " . $conn->error);
                    $stmt->bind_param("isiidsddssii", 
                        $id_vehicul, $tip_revizie, $kilometraj_programat, $data_programata, 
                        $kilometraj_efectuat, $data_efectuare, $cost_estimat, $cost_real, 
                        $observatii, $status, $prioritate, $responsabil_id, $id
                    );
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea programării: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Programarea a fost salvată cu succes!";
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID revizie invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM plan_revizii WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE revizie: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea programării: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Programarea a fost ștearsă cu succes!";
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_REVIZII.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    
    // Redirecționăm întotdeauna înapoi la pagina de plan revizii
    header("Location: plan-revizii.php");
    exit();

} else {
    $_SESSION['error_message'] = "Eroare: Acest script trebuie accesat prin trimiterea unui formular POST.";
    header("Location: plan-revizii.php");
    exit();
}
ob_end_flush();
?>
