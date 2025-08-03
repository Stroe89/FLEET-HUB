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

    error_log("PROCESS_CHELTUIELI.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $descriere = trim($_POST['descriere'] ?? '');
                $suma = empty($_POST['suma']) ? null : (float)$_POST['suma'];
                $moneda = trim($_POST['moneda'] ?? 'RON');
                $data_cheltuielii = $_POST['data_cheltuielii'] ?? '';
                $categorie = trim($_POST['categorie'] ?? '');
                $id_vehicul = empty($_POST['id_vehicul']) ? null : (int)$_POST['id_vehicul'];
                $observatii = trim($_POST['observatii'] ?? '');

                // Validări de bază
                if (empty($descriere) || empty($suma) || empty($data_cheltuielii) || empty($categorie)) {
                    throw new Exception("Descrierea, suma, data și categoria cheltuielii sunt obligatorii.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_cheltuielii) || !strtotime($data_cheltuielii)) {
                    throw new Exception("Data cheltuielii nu este validă. Format așteptat:YYYY-MM-DD.");
                }

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO cheltuieli (descriere, suma, moneda, data_cheltuielii, categorie, id_vehicul, observatii) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("sdsssis", $descriere, $suma, $moneda, $data_cheltuielii, $categorie, $id_vehicul, $observatii);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID cheltuială invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE cheltuieli SET descriere = ?, suma = ?, moneda = ?, data_cheltuielii = ?, categorie = ?, id_vehicul = ?, observatii = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("sdsssis", $descriere, $suma, $moneda, $data_cheltuielii, $categorie, $id_vehicul, $observatii, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la executarea operației: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Cheltuiala a fost salvată cu succes!";
                error_log("PROCESS_CHELTUIELI.PHP: Cheltuiala salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID cheltuială invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM cheltuieli WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea cheltuielii: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Cheltuiala a fost ștearsă cu succes!";
                error_log("PROCESS_CHELTUIELI.PHP: Cheltuiala ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_CHELTUIELI.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: cheltuieli-flota.php"); // Redirecționăm întotdeauna la pagina listei de cheltuieli
    exit();

} else {
    error_log("PROCESS_CHELTUIELI.PHP: Cerere non-POST. Redirecționare.");
    header("Location: cheltuieli-flota.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
