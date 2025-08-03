<?php
ob_start(); // Începe output buffering
session_start();
require_once 'db_connect.php'; //

// Activează afișarea erorilor PENTRU DEPANARE (DEZACTIVEAZĂ ÎN PRODUCȚIE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Configuration Placeholder ---
$upload_dir = 'uploads/contracts/'; // Director pentru fișierele contractelor
$allowed_file_types = [
    'application/pdf', 
    'application/msword', // .doc
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
    'image/jpeg', 
    'image/png'
];
$max_file_size = 10 * 1024 * 1024; // 10MB

// Get current user ID for audit trails
$current_user_id = $_SESSION['user_id'] ?? null;
if (empty($current_user_id)) {
    $_SESSION['error_message'] = "Sesiune invalidă sau utilizator neautentificat. Vă rugăm să vă autentificați din nou.";
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    // Begin transaction for data integrity
    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
                // Preluăm datele din formular și le curățăm/validăm
                $id_client = filter_var(trim($_POST['id_client'] ?? ''), FILTER_VALIDATE_INT); // Validare int
                $nume_contract = trim($_POST['nume_contract'] ?? '');
                $tip_contract = trim($_POST['tip_contract'] ?? '');
                $numar_contract = trim($_POST['numar_contract'] ?? '');
                
                $data_semnare = !empty($_POST['data_semnare']) ? trim($_POST['data_semnare']) : null; // Preluare ca null dacă gol
                
                $data_inceput = trim($_POST['data_inceput'] ?? '');
                $data_expirare = trim($_POST['data_expirare'] ?? '');
                
                $valoare_contract = !empty($_POST['valoare_contract']) ? (float)trim($_POST['valoare_contract']) : null; // Preluare ca float sau null
                
                $moneda = trim($_POST['moneda'] ?? 'EUR');
                $status_contract = trim($_POST['status_contract'] ?? 'Activ');
                
                $termeni_plata = !empty($_POST['termeni_plata']) ? trim($_POST['termeni_plata']) : null;
                $persoana_contact_client = !empty($_POST['persoana_contact_client']) ? trim($_POST['persoana_contact_client']) : null;
                $email_contact_client = !empty($_POST['email_contact_client']) ? trim($_POST['email_contact_client']) : null;
                $telefon_contact_client = !empty($_POST['telefon_contact_client']) ? trim($_POST['telefon_contact_client']) : null;
                $observatii = !empty($_POST['observatii']) ? trim($_POST['observatii']) : null;
                
                $cale_fisier = null;
                $nume_original_fisier = null;

                // Validare obligatorie
                if (empty($id_client) || $id_client === false || empty($nume_contract) || empty($numar_contract) || empty($tip_contract) || empty($data_inceput) || empty($data_expirare) || empty($status_contract)) {
                    throw new Exception("Câmpurile marcate cu * sunt obligatorii: Client, Nume Contract, Număr Contract, Tip Contract, Dată Început, Dată Expirare, Status Contract. ID Client invalid.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inceput) || !strtotime($data_inceput)) {
                    throw new Exception("Data de început nu este validă.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_expirare) || !strtotime($data_expirare)) {
                    throw new Exception("Data de expirare nu este validă.");
                }
                if (strtotime($data_inceput) > strtotime($data_expirare)) {
                    throw new Exception("Data de expirare nu poate fi înainte de data de început.");
                }
                if (!is_null($email_contact_client) && !filter_var($email_contact_client, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Adresa de email a contactului clientului nu este validă.");
                }
                if (!is_null($valoare_contract) && !is_numeric($valoare_contract)) {
                     throw new Exception("Valoarea contractului trebuie să fie un număr.");
                }

                // Validare dacă id_client există în tabela clienti
                $stmt_client_check = $conn->prepare("SELECT COUNT(id) FROM clienti WHERE id = ? AND is_active = TRUE");
                if ($stmt_client_check === false) throw new Exception("Eroare la pregătirea verificării clientului: " . $conn->error);
                $stmt_client_check->bind_param("i", $id_client);
                $stmt_client_check->execute();
                $client_exists = $stmt_client_check->get_result()->fetch_row()[0];
                $stmt_client_check->close();
                if ($client_exists == 0) {
                    throw new Exception("Clientul selectat nu este valid sau nu există în baza de date.");
                }

                // Logica pentru upload fișier
                if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] == UPLOAD_ERR_OK) {
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            throw new Exception("Eroare la crearea directorului de upload pentru contracte.");
                        }
                    }

                    $file_extension = pathinfo($_FILES['contract_file']['name'], PATHINFO_EXTENSION);
                    $new_file_name = uniqid('contract_') . '.' . $file_extension;
                    $cale_fisier = $upload_dir . $new_file_name;
                    $nume_original_fisier = basename($_FILES['contract_file']['name']);

                    if (!in_array($_FILES['contract_file']['type'], $allowed_file_types)) {
                        throw new Exception("Tipul fișierului contractului nu este permis. Se acceptă doar PDF, DOC, DOCX, JPG, PNG.");
                    }
                    if ($_FILES['contract_file']['size'] > $max_file_size) {
                        throw new Exception("Dimensiunea fișierului contractului depășește limita de 10MB.");
                    }
                    if (!move_uploaded_file($_FILES['contract_file']['tmp_name'], $cale_fisier)) {
                        throw new Exception("Eroare la încărcarea fișierului contractului. Verificați permisiunile directorului.");
                    }
                } else if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] != UPLOAD_ERR_NO_FILE) {
                     throw new Exception("Eroare la încărcarea fișierului contractului: Cod eroare " . $_FILES['contract_file']['error']);
                }

                // Inserare în baza de date
                $stmt = $conn->prepare("INSERT INTO contracte (id_client, nume_contract, tip_contract, numar_contract, data_semnare, data_inceput, data_expirare, valoare_contract, moneda, status_contract, termeni_plata, persoana_contact_client, email_contact_client, telefon_contact_client, cale_fisier, nume_original_fisier, observatii, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    $_SESSION['error_message'] = "Eroare la pregătirea query-ului de adăugare contract: " . $conn->error;
                    header("Location: adauga-contract.php");
                    exit();
                }
                
                // CORECTIE CRITICĂ FINALĂ: Șirul de tipuri trebuie să aibă 18 caractere pentru 18 variabile.
                // Re-tastat manual pentru a elimina orice caractere invizibile.
                $types_string_add = "issssssdsssssssssi"; // Acesta este șirul corect de 18 caractere
                                                        // i s s s s s s d s s s s s s s s s i
                                                        // 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8
                                                        //               ^                  ^
                                                        //       valoare_contract       observatii   current_user_id
                                                        //         (double)              (string)     (int)
                                                        // S-a adăugat un 's' în plus pentru observatii, după ultimul 's' al nume_original_fisier.
                
                $stmt->bind_param(
                    $types_string_add,
                    $id_client,
                    $nume_contract,
                    $tip_contract,
                    $numar_contract,
                    $data_semnare,
                    $data_inceput,
                    $data_expirare,
                    $valoare_contract,
                    $moneda,
                    $status_contract,
                    $termeni_plata,
                    $persoana_contact_client,
                    $email_contact_client,
                    $telefon_contact_client,
                    $cale_fisier,
                    $nume_original_fisier,
                    $observatii,
                    $current_user_id
                );

                if (!$stmt->execute()) {
                    $_SESSION['error_message'] = "Eroare la adăugarea contractului: " . $stmt->error;
                    header("Location: adauga-contract.php");
                    exit();
                }
                $stmt->close();
                $_SESSION['success_message'] = "Contractul a fost adăugat cu succes!";
                break;

            case 'edit':
                // Logic for editing an existing contract
                $contract_id = filter_var(trim($_POST['contract_id'] ?? ''), FILTER_VALIDATE_INT); // Validare int
                if ($contract_id === false || empty($contract_id)) {
                    throw new Exception("ID contract invalid pentru editare.");
                }
                
                // Fetch existing contract details first, especially file path and original name if not re-uploading
                $stmt_fetch = $conn->prepare("SELECT cale_fisier, nume_original_fisier FROM contracte WHERE id = ?");
                if ($stmt_fetch === false) throw new Exception("Eroare la pregătirea interogării de preluare fișier existent: " . $conn->error);
                $stmt_fetch->bind_param("i", $contract_id);
                $stmt_fetch->execute();
                $result_fetch = $stmt_fetch->get_result();
                $existing_contract = $result_fetch->fetch_assoc();
                $stmt_fetch->close();
                
                $cale_fisier = $existing_contract['cale_fisier'] ?? null; // Keep existing file path by default
                $nume_original_fisier = $existing_contract['nume_original_fisier'] ?? null; // Keep existing original file name by default

                // Preluăm datele actualizate (similar cu 'add' case)
                $id_client = filter_var(trim($_POST['id_client'] ?? ''), FILTER_VALIDATE_INT); // Validare int
                $nume_contract = trim($_POST['nume_contract'] ?? '');
                $tip_contract = trim($_POST['tip_contract'] ?? '');
                $numar_contract = trim($_POST['numar_contract'] ?? '');
                
                $data_semnare = !empty($_POST['data_semnare']) ? trim($_POST['data_semnare']) : null;
                $data_inceput = trim($_POST['data_inceput'] ?? '');
                $data_expirare = trim($_POST['data_expirare'] ?? '');
                
                $valoare_contract = !empty($_POST['valoare_contract']) ? (float)trim($_POST['valoare_contract']) : null;
                
                $moneda = trim($_POST['moneda'] ?? 'EUR');
                $status_contract = trim($_POST['status_contract'] ?? 'Activ');
                
                $termeni_plata = !empty($_POST['termeni_plata']) ? trim($_POST['termeni_plata']) : null;
                $persoana_contact_client = !empty($_POST['persoana_contact_client']) ? trim($_POST['persoana_contact_client']) : null;
                $email_contact_client = !empty($_POST['email_contact_client']) ? trim($_POST['email_contact_client']) : null;
                $telefon_contact_client = !empty($_POST['telefon_contact_client']) ? trim($_POST['telefon_contact_client']) : null;
                $observatii = !empty($_POST['observatii']) ? trim($_POST['observatii']) : null;

                // Validation (similar to 'add' case)
                if (empty($id_client) || $id_client === false || empty($nume_contract) || empty($numar_contract) || empty($tip_contract) || empty($data_inceput) || empty($data_expirare) || empty($status_contract)) {
                    throw new Exception("Câmpurile marcate cu * sunt obligatorii pentru editare.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inceput) || !strtotime($data_inceput)) {
                    throw new Exception("Data de început nu este validă pentru editare.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_expirare) || !strtotime($data_expirare)) {
                    throw new Exception("Data de expirare nu este validă pentru editare.");
                }
                if (strtotime($data_inceput) > strtotime($data_expirare)) {
                    throw new Exception("Data de expirare nu poate fi înainte de data de început pentru editare.");
                }
                if (!is_null($email_contact_client) && !filter_var($email_contact_client, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Adresa de email a contactului clientului nu este validă pentru editare.");
                }
                if (!is_null($valoare_contract) && !is_numeric($valoare_contract)) {
                     throw new Exception("Valoarea contractului trebuie să fie un număr.");
                }

                // Validare dacă id_client există în tabela clienti
                $stmt_client_check = $conn->prepare("SELECT COUNT(id) FROM clienti WHERE id = ? AND is_active = TRUE");
                if ($stmt_client_check === false) throw new Exception("Eroare la pregătirea verificării clientului (edit): " . $conn->error);
                $stmt_client_check->bind_param("i", $id_client);
                $stmt_client_check->execute();
                $client_exists = $stmt_client_check->get_result()->fetch_row()[0];
                $stmt_client_check->close();
                if ($client_exists == 0) {
                    throw new Exception("Clientul selectat nu este valid sau nu există în baza de date.");
                }

                // Logica pentru upload fișier pentru EDIT
                if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] == UPLOAD_ERR_OK) {
                    // Delete old file if it exists
                    if (!empty($existing_contract['cale_fisier']) && file_exists($existing_contract['cale_fisier'])) {
                        unlink($existing_contract['cale_fisier']);
                        error_log("PROCESS_CONTRACT.PHP: Fișier vechi șters: " . $existing_contract['cale_fisier']);
                    }

                    $file_extension = pathinfo($_FILES['contract_file']['name'], PATHINFO_EXTENSION);
                    $new_file_name = uniqid('contract_') . '.' . $file_extension;
                    $cale_fisier = $upload_dir . $new_file_name;
                    $nume_original_fisier = basename($_FILES['contract_file']['name']);

                    if (!in_array($_FILES['contract_file']['type'], $allowed_file_types)) {
                        throw new Exception("Tipul fișierului contractului nu este permis pentru editare.");
                    }
                    if ($_FILES['contract_file']['size'] > $max_file_size) {
                        throw new Exception("Dimensiunea fișierului contractului depășește limita pentru editare.");
                    }
                    if (!move_uploaded_file($_FILES['contract_file']['tmp_name'], $cale_fisier)) {
                        throw new Exception("Eroare la re-încărcarea fișierului contractului. Verificați permisiunile directorului.");
                    }
                } else if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] != UPLOAD_ERR_NO_FILE) {
                     throw new Exception("Eroare la încărcarea fișierului contractului (editare): Cod eroare " . $_FILES['contract_file']['error']);
                }

                // Update în baza de date
                $stmt = $conn->prepare("UPDATE contracte SET 
                                        id_client = ?, nume_contract = ?, tip_contract = ?, numar_contract = ?, data_semnare = ?, 
                                        data_inceput = ?, data_expirare = ?, valoare_contract = ?, moneda = ?, status_contract = ?, 
                                        termeni_plata = ?, persoana_contact_client = ?, email_contact_client = ?, telefon_contact_client = ?, 
                                        cale_fisier = ?, nume_original_fisier = ?, observatii = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                                        WHERE id = ?");
                if ($stmt === false) {
                    $_SESSION['error_message'] = "Eroare la pregătirea query-ului de actualizare contract: " . $conn->error;
                    header("Location: adauga-contract.php"); // Redirecționare către pagina de adăugare/editare
                    exit();
                }
                
                // CORECTIE CRITICĂ FINALĂ: Re-tastare șir de tipuri și apel explicit bind_param pentru EDIT
                // String-ul de tipuri trebuie să aibă EXACT 19 caractere
                $types_string_edit = "issssssdssssssssiii"; // Manual, 19 caractere
                
                $stmt->bind_param(
                    $types_string_edit,
                    $id_client,                 // i (int)
                    $nume_contract,             // s (string)
                    $tip_contract,              // s (string)
                    $numar_contract,            // s (string)
                    $data_semnare,              // s (string, poate fi null)
                    $data_inceput,              // s (string)
                    $data_expirare,             // s (string)
                    $valoare_contract,          // d (float/double, poate fi null)
                    $moneda,                    // s (string)
                    $status_contract,           // s (string)
                    $termeni_plata,             // s (string, poate fi null)
                    $persoana_contact_client,   // s (string, poate fi null)
                    $email_contact_client,      // s (string, poate fi null)
                    $telefon_contact_client,    // s (string, poate fi null)
                    $cale_fisier,               // s (string, poate fi null)
                    $nume_original_fisier,      // s (string, poate fi null)
                    $observatii,                // s (string, poate fi null)
                    $current_user_id,           // i (int)
                    $contract_id                // i (int, pentru WHERE)
                );
                
                if (!$stmt->execute()) {
                    $_SESSION['error_message'] = "Eroare la actualizarea contractului: " . $stmt->error;
                    header("Location: adauga-contract.php"); // Redirecționare către pagina de adăugare/editare
                    exit();
                }
                $stmt->close();
                $_SESSION['success_message'] = "Contractul a fost actualizat cu succes!";
                break;

            case 'delete':
                // Implement soft delete
                $contract_id = $_POST['contract_id'] ?? null;
                if (empty($contract_id) || !is_numeric($contract_id)) {
                    throw new Exception("ID contract invalid pentru ștergere.");
                }

                // Fetch file path before soft deleting (if you need to move it to an archive in cloud storage)
                $stmt_file = $conn->prepare("SELECT cale_fisier FROM contracte WHERE id = ?");
                if ($stmt_file === false) throw new Exception("Eroare la pregătirea query-ului SELECT FILE for delete: " . $conn->error);
                $stmt_file->bind_param("i", $contract_id);
                $stmt_file->execute();
                $result_file = $stmt_file->get_result();
                $file_data = $result_file->fetch_assoc();
                $stmt_file->close();

                // Soft delete in DB
                $stmt = $conn->prepare("UPDATE contracte SET is_deleted = TRUE, updated_at = CURRENT_TIMESTAMP, updated_by = ? WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului de ștergere logică contract: " . $conn->error);
                $stmt->bind_param("ii", $current_user_id, $contract_id);
                if (!$stmt->execute()) throw new Exception("Eroare la ștergerea contractului (marcare ca inactiv): " . $stmt->error);
                $stmt->close();
                $_SESSION['success_message'] = "Contractul a fost șters (marcat ca inactiv) cu succes!";
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare Contract: " . $e->getMessage();
        error_log("PROCESS_CONTRACT.PHP Eroare: " . $e->getMessage());
        // Redirecționare către pagina de adăugare/editare în caz de eroare.
        // În acest caz, "adauga-contract.php" ar afișa mesajul de eroare din sesiune.
        header("Location: adauga-contract.php");
        exit();
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: contracte-clienti.php");
    exit();

} else {
    echo "Metodă de cerere nepermisă.";
    exit();
}
ob_end_flush();
?>