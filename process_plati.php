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

    error_log("PROCESS_PLATI.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_factura = $_POST['id_factura'] ?? null;
                $data_platii = $_POST['data_platii'] ?? '';
                $suma_platita = empty($_POST['suma_platita']) ? null : (float)$_POST['suma_platita'];
                $metoda_platii = $_POST['metoda_platii'] ?? '';
                $observatii = trim($_POST['observatii'] ?? '');

                // Validări de bază
                if (empty($id_factura) || !is_numeric($id_factura) || empty($data_platii) || empty($suma_platita) || empty($metoda_platii)) {
                    throw new Exception("Factura, data plății, suma și metoda plății sunt obligatorii.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_platii) || !strtotime($data_platii)) {
                    throw new Exception("Data plății nu este validă. Format așteptat:YYYY-MM-DD.");
                }

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO plati_clienti (id_factura, data_platii, suma_platita, metoda_platii, observatii) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("isdss", $id_factura, $data_platii, $suma_platita, $metoda_platii, $observatii);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID plată invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE plati_clienti SET id_factura = ?, data_platii = ?, suma_platita = ?, metoda_platii = ?, observatii = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("isdssi", $id_factura, $data_platii, $suma_platita, $metoda_platii, $observatii, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la executarea operației: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Plata a fost salvată cu succes!";
                error_log("PROCESS_PLATI.PHP: Plata salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID plată invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM plati_clienti WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea plății: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Plata a fost ștearsă cu succes!";
                error_log("PROCESS_PLATI.PHP: Plata ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_PLATI.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: status-plati-clienti.php"); // Redirecționăm întotdeauna la pagina listei de plăți
    exit();

} else {
    error_log("PROCESS_PLATI.PHP: Cerere non-POST. Redirecționare.");
    header("Location: status-plati-clienti.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
