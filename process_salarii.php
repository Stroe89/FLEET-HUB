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

    error_log("PROCESS_SALARII.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'add_salary':
            case 'edit_salary':
                $id = $_POST['id'] ?? null; // ID-ul înregistrării de salariu
                $id_angajat = $_POST['id_angajat'] ?? null;
                $salariu_baza = $_POST['salariu_baza'] ?? null;
                $moneda_salariu = trim($_POST['moneda_salariu'] ?? 'RON');
                $frecventa_plata = trim($_POST['frecventa_plata'] ?? 'Lunar');
                $iban = empty(trim($_POST['iban'] ?? '')) ? null : trim($_POST['iban']);
                $banca = empty(trim($_POST['banca'] ?? '')) ? null : trim($_POST['banca']);
                $data_inceput_salariu = $_POST['data_inceput_salariu'] ?? null;
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);

                if (empty($id_angajat) || !is_numeric($id_angajat) || empty($salariu_baza) || !is_numeric($salariu_baza) || empty($data_inceput_salariu)) {
                    throw new Exception("Angajatul, salariul de bază și data de început sunt obligatorii.");
                }

                // La editare, dacă se modifică salariul de bază sau data de început, încheiem salariul vechi
                if ($action == 'edit_salary' && $id) {
                    // Verificăm salariul curent pentru a decide dacă trebuie încheiat
                    $stmt_check_current = $conn->prepare("SELECT data_inceput_salariu, salariu_baza FROM salarii WHERE id = ?");
                    $stmt_check_current->bind_param("i", $id);
                    $stmt_check_current->execute();
                    $result_check_current = $stmt_check_current->get_result();
                    $current_salary_record = $result_check_current->fetch_assoc();
                    $stmt_check_current->close();

                    if ($current_salary_record && ($current_salary_record['salariu_baza'] != $salariu_baza || $current_salary_record['data_inceput_salariu'] != $data_inceput_salariu)) {
                        // Încheiem salariul vechi (setăm data_sfarsit_salariu la ziua precedentă datei de început a noului salariu)
                        $data_sfarsit_vechi = (new DateTime($data_inceput_salariu))->modify('-1 day')->format('Y-m-d');
                        $stmt_end_old_salary = $conn->prepare("UPDATE salarii SET data_sfarsit_salariu = ? WHERE id = ?");
                        $stmt_end_old_salary->bind_param("si", $data_sfarsit_vechi, $id);
                        $stmt_end_old_salary->execute();
                        $stmt_end_old_salary->close();

                        // Acum adăugăm noul salariu ca o nouă înregistrare
                        $action = 'add_salary'; // Schimbăm acțiunea la add pentru a insera noul salariu
                        $id = null; // Resetăm ID-ul pentru a forța o inserare
                    } else {
                        // Dacă nu s-a schimbat salariul de bază sau data de început, facem un update direct
                        $sql = "UPDATE salarii SET salariu_baza = ?, moneda_salariu = ?, frecventa_plata = ?, iban = ?, banca = ?, data_inceput_salariu = ?, observatii = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT SALARY: " . $conn->error);
                        
                        $types = "dssssssi"; // d(salariu_baza), s(moneda), s(frecventa), s(iban), s(banca), s(data_inceput), s(observatii), i(id)
                        $params = [$salariu_baza, $moneda_salariu, $frecventa_plata, $iban, $banca, $data_inceput_salariu, $observatii, $id];
                        array_unshift($params, $types);
                        call_user_func_array([$stmt, 'bind_param'], $params);
                        if (!$stmt->execute()) {
                            throw new Exception("Eroare la actualizarea salariului: " . $stmt->error);
                        }
                        $stmt->close();
                        $_SESSION['success_message'] = "Salariul a fost actualizat cu succes!";
                        error_log("PROCESS_SALARII.PHP: Salariu actualizat cu succes (ID: " . $id . ").");
                        break; // Ieșim din switch, am terminat cu update-ul
                    }
                }

                // Dacă acțiunea a rămas 'add_salary' (fie inițial, fie după încheierea celui vechi)
                if ($action == 'add_salary') {
                    // Închidem orice salariu "activ" anterior pentru acest angajat (dacă există)
                    $stmt_end_prev_salary = $conn->prepare("UPDATE salarii SET data_sfarsit_salariu = ? WHERE id_angajat = ? AND data_sfarsit_salariu IS NULL");
                    $data_sfarsit_prev = (new DateTime($data_inceput_salariu))->modify('-1 day')->format('Y-m-d');
                    $stmt_end_prev_salary->bind_param("si", $data_sfarsit_prev, $id_angajat);
                    $stmt_end_prev_salary->execute();
                    $stmt_end_prev_salary->close();

                    // Inserăm noul salariu
                    $sql = "INSERT INTO salarii (id_angajat, salariu_baza, moneda_salariu, frecventa_plata, iban, banca, data_inceput_salariu, observatii) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD SALARY: " . $conn->error);
                    
                    $types = "idssssss"; // i(id_angajat), d(salariu_baza), s(moneda), s(frecventa), s(iban), s(banca), s(data_inceput), s(observatii)
                    $params = [$id_angajat, $salariu_baza, $moneda_salariu, $frecventa_plata, $iban, $banca, $data_inceput_salariu, $observatii];
                    array_unshift($params, $types);
                    call_user_func_array([$stmt, 'bind_param'], $params);
                    if (!$stmt->execute()) {
                        throw new Exception("Eroare la salvarea salariului: " . $stmt->error);
                    }
                    $stmt->close();
                    $_SESSION['success_message'] = "Salariul a fost salvat cu succes!";
                    error_log("PROCESS_SALARII.PHP: Salariu adăugat cu succes (Angajat ID: " . $id_angajat . ").");
                }
                break;

            case 'delete_salary':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID salariu invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM salarii WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE SALARY: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea salariului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Salariul a fost șters cu succes!";
                error_log("PROCESS_SALARII.PHP: Salariu șters cu succes (ID: " . $id . ").");
                break;

            case 'add_bonus':
                $id_angajat = $_POST['id_angajat'] ?? null;
                $tip_bonus = trim($_POST['tip_bonus'] ?? '');
                $valoare = $_POST['valoare'] ?? null;
                $moneda = trim($_POST['moneda'] ?? 'RON');
                $data_acordare = $_POST['data_acordare'] ?? null;
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);

                if (empty($id_angajat) || !is_numeric($id_angajat) || empty($tip_bonus) || empty($valoare) || !is_numeric($valoare) || empty($data_acordare)) {
                    throw new Exception("Angajatul, tipul, valoarea și data acordării bonusului sunt obligatorii.");
                }

                $sql = "INSERT INTO bonusuri (id_angajat, tip_bonus, valoare, moneda, data_acordare, observatii) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD BONUS: " . $conn->error);
                
                $types = "isdsss"; // i(id_angajat), s(tip_bonus), d(valoare), s(moneda), s(data_acordare), s(observatii)
                $params = [$id_angajat, $tip_bonus, $valoare, $moneda, $data_acordare, $observatii];
                array_unshift($params, $types);
                call_user_func_array([$stmt, 'bind_param'], $params);

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea bonusului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Bonusul a fost adăugat cu succes!";
                error_log("PROCESS_SALARII.PHP: Bonus adăugat cu succes (Angajat ID: " . $id_angajat . ").");
                break;

            case 'delete_bonus':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID bonus invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM bonusuri WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE BONUS: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea bonusului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Bonusul a fost șters cu succes!";
                error_log("PROCESS_SALARII.PHP: Bonus șters cu succes (ID: " . $id . ").");
                break;

            case 'add_bonuri_masa':
                $id_angajat = $_POST['id_angajat'] ?? null;
                $numar_bonuri = $_POST['numar_bonuri'] ?? null;
                $valoare_bon_unitar = $_POST['valoare_bon_unitar'] ?? null;
                $perioada_luna = $_POST['perioada_luna'] ?? null;
                $data_acordare = $_POST['data_acordare'] ?? null;
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);

                if (empty($id_angajat) || !is_numeric($id_angajat) || empty($numar_bonuri) || !is_numeric($numar_bonuri) || empty($valoare_bon_unitar) || !is_numeric($valoare_bon_unitar) || empty($perioada_luna) || empty($data_acordare)) {
                    throw new Exception("Angajatul, numărul bonurilor, valoarea unitară, perioada și data acordării sunt obligatorii.");
                }

                $sql = "INSERT INTO bonuri_masa (id_angajat, numar_bonuri, valoare_bon_unitar, perioada_luna, data_acordare, observatii) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD BONURI MASA: " . $conn->error);
                
                $types = "iidsis"; // i(id_angajat), i(numar_bonuri), d(valoare_unitar), s(perioada_luna), s(data_acordare), s(observatii)
                $params = [$id_angajat, $numar_bonuri, $valoare_bon_unitar, $perioada_luna, $data_acordare, $observatii];
                array_unshift($params, $types);
                call_user_func_array([$stmt, 'bind_param'], $params);

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea bonurilor de masă: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Bonurile de masă au fost adăugate cu succes!";
                error_log("PROCESS_SALARII.PHP: Bonuri masă adăugate cu succes (Angajat ID: " . $id_angajat . ").");
                break;

            case 'delete_bonuri_masa':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID bonuri masă invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM bonuri_masa WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE BONURI MASA: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea bonurilor de masă: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Bonurile de masă au fost șterse cu succes!";
                error_log("PROCESS_SALARII.PHP: Bonuri masă șterse cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_SALARII.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: salarii-bonusuri.php"); 
    exit();

} else {
    error_log("PROCESS_SALARII.PHP: Cerere non-POST. Redirecționare.");
    header("Location: salarii-bonusuri.php"); 
    exit();
}
ob_end_flush();
?>
