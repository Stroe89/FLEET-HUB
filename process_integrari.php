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

    error_log("PROCESS_INTEGRARI.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $nume_integrare = trim($_POST['nume_integrare'] ?? '');
                $tip_integrare = $_POST['tip_integrare'] ?? '';
                $status = $_POST['status'] ?? 'Inactiv';
                $descriere = trim($_POST['descriere'] ?? '');
                $config_json_raw = $_POST['config_json'] ?? '{}'; // Preluăm stringul JSON

                // Validări de bază
                if (empty($nume_integrare) || empty($tip_integrare)) {
                    throw new Exception("Numele și tipul integrării sunt obligatorii.");
                }

                // Validează și encodează JSON-ul
                $config_json = json_decode($config_json_raw);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Configurația JSON este invalidă: " . json_last_error_msg());
                }
                $config_json = json_encode($config_json); // Re-encode pentru a asigura formatul corect

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO integrari_servicii (nume_integrare, tip_integrare, status, descriere, config_json) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("sssss", $nume_integrare, $tip_integrare, $status, $descriere, $config_json);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID integrare invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE integrari_servicii SET nume_integrare = ?, tip_integrare = ?, status = ?, descriere = ?, config_json = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("sssssi", $nume_integrare, $tip_integrare, $status, $descriere, $config_json, $id);
                }

                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry for nume_integrare
                        throw new Exception("O integrare cu numele '" . htmlspecialchars($nume_integrare) . "' există deja.");
                    } else {
                        throw new Exception("Eroare la salvarea integrării: " . $stmt->error);
                    }
                }
                $stmt->close();
                $_SESSION['success_message'] = "Integrarea a fost salvată cu succes!";
                error_log("PROCESS_INTEGRARI.PHP: Integrare salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID integrare invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM integrari_servicii WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea integrării: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Integrarea a fost ștearsă cu succes!";
                error_log("PROCESS_INTEGRARI.PHP: Integrare ștearsă cu succes (ID: " . $id . ").");
                break;

            case 'test': // Acțiune pentru testarea integrării (placeholder)
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID integrare invalid pentru testare.");
                }
                // Aici ar veni logica reală de testare a conexiunii cu serviciul extern
                // Pentru moment, doar actualizăm last_tested_at și status
                $sql_update_test = "UPDATE integrari_servicii SET last_tested_at = NOW(), status = 'Activ' WHERE id = ?";
                $stmt_update_test = $conn->prepare($sql_update_test);
                if ($stmt_update_test === false) throw new Exception("Eroare la pregătirea query-ului TEST: " . $conn->error);
                $stmt_update_test->bind_param("i", $id);
                if (!$stmt_update_test->execute()) {
                    throw new Exception("Eroare la actualizarea statusului testării: " . $stmt_update_test->error);
                }
                $stmt_update_test->close();
                $_SESSION['success_message'] = "Integrarea a fost testată cu succes!";
                error_log("PROCESS_INTEGRARI.PHP: Integrare testată cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_INTEGRARI.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: integrari.php"); // Redirecționăm întotdeauna la pagina listei de integrări
    exit();

} else {
    error_log("PROCESS_INTEGRARI.PHP: Cerere non-POST. Redirecționare.");
    header("Location: integrari.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
