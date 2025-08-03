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

    error_log("PROCESS_FACTURI.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_client = $_POST['id_client'] ?? null;
                $numar_factura = trim($_POST['numar_factura'] ?? '');
                $data_emiterii = $_POST['data_emiterii'] ?? '';
                $data_scadenta = $_POST['data_scadenta'] ?? '';
                $valoare_totala = empty($_POST['valoare_totala']) ? null : (float)$_POST['valoare_totala'];
                $moneda = trim($_POST['moneda'] ?? 'RON');
                $status = $_POST['status'] ?? 'Emisa';
                $observatii = trim($_POST['observatii'] ?? '');

                // Validări de bază
                if (empty($id_client) || !is_numeric($id_client) || empty($numar_factura) || empty($data_emiterii) || empty($data_scadenta) || empty($valoare_totala)) {
                    throw new Exception("Clientul, numărul, data emiterii, data scadenței și valoarea facturii sunt obligatorii.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_emiterii) || !strtotime($data_emiterii)) {
                    throw new Exception("Data emiterii nu este validă. Format așteptat:YYYY-MM-DD.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_scadenta) || !strtotime($data_scadenta)) {
                    throw new Exception("Data scadenței nu este validă. Format așteptat:YYYY-MM-DD.");
                }

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO facturi (id_client, numar_factura, data_emiterii, data_scadenta, valoare_totala, moneda, status, observatii) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("isddisss", $id_client, $numar_factura, $data_emiterii, $data_scadenta, $valoare_totala, $moneda, $status, $observatii);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID factură invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE facturi SET id_client = ?, numar_factura = ?, data_emiterii = ?, data_scadenta = ?, valoare_totala = ?, moneda = ?, status = ?, observatii = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("isddisssi", $id_client, $numar_factura, $data_emiterii, $data_scadenta, $valoare_totala, $moneda, $status, $observatii, $id);
                }

                if (!$stmt->execute()) {
                    // Verificăm dacă eroarea este de tip "Duplicate entry" pentru numar_factura
                    if ($conn->errno == 1062) {
                        throw new Exception("O factură cu numărul '" . htmlspecialchars($numar_factura) . "' există deja.");
                    } else {
                        throw new Exception("Eroare la executarea operației: " . $stmt->error);
                    }
                }
                $stmt->close();
                $_SESSION['success_message'] = "Factura a fost salvată cu succes!";
                error_log("PROCESS_FACTURI.PHP: Factura salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID factură invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM facturi WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea facturii: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Factura a fost ștearsă cu succes!";
                error_log("PROCESS_FACTURI.PHP: Factura ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_FACTURI.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: facturare-transport.php"); // Redirecționăm întotdeauna la pagina listei de facturi
    exit();

} else {
    error_log("PROCESS_FACTURI.PHP: Cerere non-POST. Redirecționare.");
    header("Location: facturare-transport.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
