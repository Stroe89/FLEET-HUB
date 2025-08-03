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

    error_log("PROCESS_CURSE_ACTIVE.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_vehicul = $_POST['id_vehicul'] ?? null;
                $id_sofer = $_POST['id_sofer'] ?? null;
                $data_inceput = $_POST['data_inceput'] ?? null;
                $data_sfarsit = empty($_POST['data_sfarsit']) ? null : $_POST['data_sfarsit'];
                $locatie_plecare = trim($_POST['locatie_plecare'] ?? '');
                $locatie_destinatie = trim($_POST['locatie_destinatie'] ?? '');
                $kilometraj_parcurs = empty(trim($_POST['kilometraj_parcurs'] ?? '')) ? 0 : (int)trim($_POST['kilometraj_parcurs']);
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);
                $status = trim($_POST['status'] ?? 'În desfășurare'); // Statusul trimis de formular sau implicit

                // Validări de bază
                if (empty($id_vehicul) || !is_numeric($id_vehicul)) {
                    throw new Exception("ID vehicul invalid sau lipsă.");
                }
                if (empty($id_sofer) || !is_numeric($id_sofer)) {
                    throw new Exception("ID șofer invalid sau lipsă.");
                }
                if (empty($data_inceput)) {
                    throw new Exception("Data de început este obligatorie.");
                }
                if (empty($locatie_plecare) || empty($locatie_destinatie)) {
                    throw new Exception("Locația de plecare și destinație sunt obligatorii.");
                }

                if ($action == 'add') {
                    $sql = "INSERT INTO curse (id_vehicul, id_sofer, data_inceput, data_sfarsit, locatie_plecare, locatie_destinatie, kilometraj_parcurs, observatii, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD cursă: " . $conn->error);
                    $stmt->bind_param("iissisiss", $id_vehicul, $id_sofer, $data_inceput, $data_sfarsit, $locatie_plecare, $locatie_destinatie, $kilometraj_parcurs, $observatii, $status);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID cursă invalid pentru editare.");
                    }
                    $sql = "UPDATE curse SET id_vehicul = ?, id_sofer = ?, data_inceput = ?, data_sfarsit = ?, locatie_plecare = ?, locatie_destinatie = ?, kilometraj_parcurs = ?, observatii = ?, status = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT cursă: " . $conn->error);
                    $stmt->bind_param("iissisissi", $id_vehicul, $id_sofer, $data_inceput, $data_sfarsit, $locatie_plecare, $locatie_destinatie, $kilometraj_parcurs, $observatii, $status, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea cursei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Cursa a fost salvată cu succes!";
                break;

            case 'complete': // Finalizează cursa
                $id = $_POST['id'] ?? null;
                $kilometraj_parcurs = empty(trim($_POST['kilometraj_parcurs'] ?? '')) ? 0 : (int)trim($_POST['kilometraj_parcurs']);
                $data_sfarsit = empty($_POST['data_sfarsit']) ? date('Y-m-d H:i:s') : $_POST['data_sfarsit']; // Setează data curentă dacă nu e specificată
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);

                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID cursă invalid pentru finalizare.");
                }

                // Interogare UPDATE simplificată pentru finalizare
                $sql = "UPDATE curse SET kilometraj_parcurs = ?, data_sfarsit = ?, observatii = ?, status = 'Finalizată' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului COMPLETE cursă: " . $conn->error);
                $stmt->bind_param("isssi", $kilometraj_parcurs, $data_sfarsit, $observatii, $id);

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la finalizarea cursei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Cursa a fost finalizată cu succes!";
                break;

            case 'cancel': // Anulează cursa
                $id = $_POST['id'] ?? null;
                
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID cursă invalid pentru anulare.");
                }

                // Interogare UPDATE simplificată pentru anulare
                $sql = "UPDATE curse SET status = 'Anulată' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului CANCEL cursă: " . $conn->error);
                $status_anulat = 'Anulată'; // Variabilă pentru bind_param
                $stmt->bind_param("si", $status_anulat, $id); 
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la anularea cursei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Cursa a fost anulată cu succes!";
                break;

            case 'delete_cursa': // Acțiune pentru ștergerea unei curse (dacă este necesar să o păstrăm)
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID cursă invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM curse WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE cursă: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea cursei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Cursa a fost ștearsă cu succes!";
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_CURSE_ACTIVE.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    
    // Redirecționăm întotdeauna înapoi la pagina de unde a venit cererea
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'curse-active.php'; // Redirecționează către curse-active.php
    header("Location: " . $redirect_url);
    exit();

} else {
    $_SESSION['error_message'] = "Eroare: Acest script trebuie accesat prin trimiterea unui formular POST.";
    header("Location: curse-active.php");
    exit();
}
ob_end_flush();
?>
