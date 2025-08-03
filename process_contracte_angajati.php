<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json'); // Foarte important pentru a indica răspuns JSON

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Autentificare necesară.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Acțiune necunoscută.'];

try {
    if ($action === 'add' || $action === 'edit') {
        // Colectează și sanitizează datele
        $id = ($action === 'edit') ? (int)$_POST['id'] : null;
        $id_angajat = (int)$_POST['id_angajat'];
        $tip_contract = htmlspecialchars($_POST['tip_contract']);
        $numar_contract = htmlspecialchars($_POST['numar_contract']);
        $data_semnare = $_POST['data_semnare'];
        $data_inceput = $_POST['data_inceput'];
        $data_sfarsit = !empty($_POST['data_sfarsit']) ? $_POST['data_sfarsit'] : null;
        $observatii = htmlspecialchars($_POST['observatii']);

        $cale_fisier = $_POST['existing_file_path'] ?? null;
        $nume_original_fisier = $_POST['existing_file_name'] ?? null;

        // Logica de upload fișier
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/contracte_angajati/'; // Asigură-te că acest director există și are permisiuni de scriere
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $original_file_name_uploaded = basename($_FILES['contract_file']['name']); // Numele original al fișierului ÎNCĂRCAT acum
            $file_extension = pathinfo($original_file_name_uploaded, PATHINFO_EXTENSION);
            $new_file_name_unique = uniqid('contract_') . '.' . $file_extension;
            $target_file = $upload_dir . $new_file_name_unique;

            if (move_uploaded_file($_FILES['contract_file']['tmp_name'], $target_file)) {
                // Dacă există un fișier vechi și se încarcă unul nou, șterge-l pe cel vechi
                if ($action === 'edit' && !empty($_POST['existing_file_path']) && file_exists($_POST['existing_file_path'])) {
                    unlink($_POST['existing_file_path']);
                }
                $cale_fisier = $target_file;
                $nume_original_fisier = $original_file_name_uploaded;
            } else {
                throw new Exception("Eroare la încărcarea fișierului.");
            }
        } else if ($action === 'edit' && isset($_POST['remove_current_file_flag']) && $_POST['remove_current_file_flag'] === 'true') {
             // Această logică este pentru cazul în care utilizatorul a apăsat "Elimină" lângă fișierul existent.
             // Necesită un input hidden suplimentar 'remove_current_file_flag' setat la 'true' în JS la eliminare.
             if (!empty($cale_fisier) && file_exists($cale_fisier)) {
                 unlink($cale_fisier);
             }
             $cale_fisier = null;
             $nume_original_fisier = null;
        }
        // Notă: Dacă nu se încarcă un fișier nou și nu s-a cerut eliminarea celui existent,
        // $cale_fisier și $nume_original_fisier își vor păstra valorile din $_POST['existing_file_path'] / ['existing_file_name'].

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO contracte_angajati (id_angajat, tip_contract, numar_contract, data_semnare, data_inceput, data_sfarsit, cale_fisier, nume_original_fisier, observatii) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $id_angajat, $tip_contract, $numar_contract, $data_semnare, $data_inceput, $data_sfarsit, $cale_fisier, $nume_original_fisier, $observatii);
            if ($stmt->execute()) {
                $new_contract_id = $conn->insert_id;
                // Preia datele complete pentru a le returna clientului (inclusiv nume/prenume angajat)
                $stmt_select = $conn->prepare("SELECT ca.*, a.nume, a.prenume FROM contracte_angajati ca JOIN angajati a ON ca.id_angajat = a.id WHERE ca.id = ?");
                $stmt_select->bind_param("i", $new_contract_id);
                $stmt_select->execute();
                $result = $stmt_select->get_result();
                $new_contract_data = $result->fetch_assoc();
                $response = ['success' => true, 'message' => 'Contract adăugat cu succes!', 'contract' => $new_contract_data];
            } else {
                throw new Exception("Eroare la adăugarea contractului: " . $stmt->error);
            }
            $stmt->close();

        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE contracte_angajati SET id_angajat = ?, tip_contract = ?, numar_contract = ?, data_semnare = ?, data_inceput = ?, data_sfarsit = ?, cale_fisier = ?, nume_original_fisier = ?, observatii = ? WHERE id = ?");
            $stmt->bind_param("issssssssi", $id_angajat, $tip_contract, $numar_contract, $data_semnare, $data_inceput, $data_sfarsit, $cale_fisier, $nume_original_fisier, $observatii, $id);
            if ($stmt->execute()) {
                // Preia datele complete pentru a le returna clientului
                $stmt_select = $conn->prepare("SELECT ca.*, a.nume, a.prenume FROM contracte_angajati ca JOIN angajati a ON ca.id_angajat = a.id WHERE ca.id = ?");
                $stmt_select->bind_param("i", $id);
                $stmt_select->execute();
                $result = $stmt_select->get_result();
                $updated_contract_data = $result->fetch_assoc();
                $response = ['success' => true, 'message' => 'Contract actualizat cu succes!', 'contract' => $updated_contract_data];
            } else {
                throw new Exception("Eroare la actualizarea contractului: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];

        // Oprește-te și șterge fișierul asociat înainte de a șterge înregistrarea din DB
        $stmt_file = $conn->prepare("SELECT cale_fisier FROM contracte_angajati WHERE id = ?");
        $stmt_file->bind_param("i", $id);
        $stmt_file->execute();
        $result_file = $stmt_file->get_result();
        if ($row_file = $result_file->fetch_assoc()) {
            if (!empty($row_file['cale_fisier']) && file_exists($row_file['cale_fisier'])) {
                unlink($row_file['cale_fisier']); // Șterge fișierul fizic
            }
        }
        $stmt_file->close();

        $stmt = $conn->prepare("DELETE FROM contracte_angajati WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Contract șters cu succes!'];
        } else {
            throw new Exception("Eroare la ștergerea contractului: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Acțiune invalidă.';
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    // Loghează eroarea aici pentru depanare
    error_log("Contractes process error: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
exit();
?>
