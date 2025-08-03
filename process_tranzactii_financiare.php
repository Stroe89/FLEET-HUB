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

    error_log("PROCESS_TRANZACTII_FINANCIARE.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $tip_tranzactie = $_POST['tip_tranzactie'] ?? '';
                $data_tranzactiei = $_POST['data_tranzactiei'] ?? '';
                $suma = empty($_POST['suma']) ? null : (float)$_POST['suma'];
                $moneda = trim($_POST['moneda'] ?? 'RON');
                $descriere = trim($_POST['descriere'] ?? '');
                $categorie = trim($_POST['categorie'] ?? '');
                $observatii = trim($_POST['observatii'] ?? '');

                // Validări de bază
                if (empty($tip_tranzactie) || empty($data_tranzactiei) || empty($suma) || empty($descriere)) {
                    throw new Exception("Tipul, data, suma și descrierea tranzacției sunt obligatorii.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_tranzactiei) || !strtotime($data_tranzactiei)) {
                    throw new Exception("Data tranzacției nu este validă. Format așteptat:YYYY-MM-DD.");
                }

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO registru_financiar (tip_tranzactie, data_tranzactiei, suma, moneda, descriere, categorie, observatii) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("sdsdsss", $tip_tranzactie, $data_tranzactiei, $suma, $moneda, $descriere, $categorie, $observatii);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID tranzacție invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE registru_financiar SET tip_tranzactie = ?, data_tranzactiei = ?, suma = ?, moneda = ?, descriere = ?, categorie = ?, observatii = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("sdsdsssi", $tip_tranzactie, $data_tranzactiei, $suma, $moneda, $descriere, $categorie, $observatii, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la executarea operației: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Tranzacția a fost salvată cu succes!";
                error_log("PROCESS_TRANZACTII_FINANCIARE.PHP: Tranzacție salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID tranzacție invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM registru_financiar WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea tranzacției: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Tranzacția a fost ștearsă cu succes!";
                error_log("PROCESS_TRANZACTII_FINANCIARE.PHP: Tranzacție ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_TRANZACTII_FINANCIARE.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: incasari-plati.php"); // Redirecționăm întotdeauna la pagina listei de tranzacții
    exit();

} else {
    error_log("PROCESS_TRANZACTII_FINANCIARE.PHP: Cerere non-POST. Redirecționare.");
    header("Location: incasari-plati.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
