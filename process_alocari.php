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

    error_log("PROCESS_ALOCARI.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_vehicul = $_POST['id_vehicul'] ?? null;
                $id_sofer = $_POST['id_sofer'] ?? null;
                $data_inceput = $_POST['data_inceput'] ?? null;
                
                // IMPORTANT: Verificăm dacă data_sfarsit este trimisă și dacă este goală.
                // Dacă este goală (sau checkbox-ul "permanent" a golit-o), o setăm la NULL.
                $data_sfarsit = empty($_POST['data_sfarsit']) ? null : $_POST['data_sfarsit'];

                if (empty($id_vehicul) || !is_numeric($id_vehicul)) {
                    throw new Exception("ID vehicul invalid sau lipsă.");
                }
                if (empty($id_sofer) || !is_numeric($id_sofer)) {
                    throw new Exception("ID șofer invalid sau lipsă.");
                }
                if (empty($data_inceput)) {
                    throw new Exception("Data de început este obligatorie.");
                }

                if ($action == 'add') {
                    $sql = "INSERT INTO alocari_vehicule_soferi (id_vehicul, id_sofer, data_inceput, data_sfarsit) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD alocare: " . $conn->error);
                    $stmt->bind_param("iiss", $id_vehicul, $id_sofer, $data_inceput, $data_sfarsit);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID alocare invalid pentru editare.");
                    }
                    $sql = "UPDATE alocari_vehicule_soferi SET id_vehicul = ?, id_sofer = ?, data_inceput = ?, data_sfarsit = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT alocare: " . $conn->error);
                    $stmt->bind_param("iissi", $id_vehicul, $id_sofer, $data_inceput, $data_sfarsit, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea alocării: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Alocarea a fost salvată cu succes!";
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID alocare invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM alocari_vehicule_soferi WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE alocare: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea alocării: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Alocarea a fost ștearsă cu succes!";
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_ALOCARI.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    
    // Redirecționăm întotdeauna înapoi la pagina de alocări
    header("Location: alocare-vehicul-sofer.php");
    exit();

} else {
    $_SESSION['error_message'] = "Eroare: Acest script trebuie accesat prin trimiterea unui formular POST.";
    header("Location: alocare-vehicul-sofer.php");
    exit();
}
ob_end_flush();
?>
