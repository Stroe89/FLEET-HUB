<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifică autentificarea (opțional, dar recomandat și aici pentru scripturile de procesare)
if (!isset($_SESSION['user_id'])) {
    // Redirecționează la login sau returnează o eroare JSON
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    error_log("PROCESS_PLANIFICARE_RUTE.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_vehicul = $_POST['id_vehicul'] ?? null;
                $id_sofer = empty($_POST['id_sofer']) ? null : (int)$_POST['id_sofer'];
                $nume_ruta = trim($_POST['nume_ruta'] ?? '');
                $locatie_start = trim($_POST['locatie_plecare'] ?? '');
                $locatie_final = trim($_POST['locatie_final'] ?? '');
                $data_plecare_estimata = $_POST['data_plecare_estimata'] ?? '';
                $data_sosire_estimata = empty($_POST['data_sosire_estimata']) ? null : $_POST['data_sosire_estimata'];
                $distanta_estimata_km = empty($_POST['distanta_estimata_km']) ? null : (float)$_POST['distanta_estimata_km'];
                $timp_estimat_ore = empty($_POST['timp_estimat_ore']) ? null : (float)$_POST['timp_estimat_ore'];
                $status = $_POST['status'] ?? 'Planificată';
                $observatii = trim($_POST['observatii'] ?? '');

                // Validări de bază
                if (empty($id_vehicul) || !is_numeric($id_vehicul) || empty($nume_ruta) || empty($locatie_start) || empty($locatie_final) || empty($data_plecare_estimata)) {
                    throw new Exception("Vehiculul, numele rutei, locațiile de plecare/sosire și data de plecare estimată sunt obligatorii.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/", $data_plecare_estimata) || !strtotime($data_plecare_estimata)) {
                    throw new Exception("Data de plecare estimată nu este validă. Format așteptat:YYYY-MM-DDTHH:MM.");
                }
                if (!empty($data_sosire_estimata) && (!preg_match("/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/", $data_sosire_estimata) || !strtotime($data_sosire_estimata))) {
                    throw new Exception("Data de sosire estimată nu este validă. Format așteptat:YYYY-MM-DDTHH:MM.");
                }
                if (!empty($data_sosire_estimata) && strtotime($data_sosire_estimata) < strtotime($data_plecare_estimata)) {
                    throw new Exception("Data de sosire estimată nu poate fi înainte de data de plecare estimată.");
                }

                // Formatare date pentru MySQL DATETIME
                $data_plecare_estimata_mysql = date('Y-m-d H:i:s', strtotime($data_plecare_estimata));
                $data_sosire_estimata_mysql = $data_sosire_estimata ? date('Y-m-d H:i:s', strtotime($data_sosire_estimata)) : null;

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO planificare_rute (id_vehicul, id_sofer, nume_ruta, locatie_start, locatie_final, data_plecare_estimata, data_sosire_estimata, distanta_estimata_km, timp_estimat_ore, status, observatii) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("issssssddss", $id_vehicul, $id_sofer, $nume_ruta, $locatie_start, $locatie_final, $data_plecare_estimata_mysql, $data_sosire_estimata_mysql, $distanta_estimata_km, $timp_estimat_ore, $status, $observatii);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID rută invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE planificare_rute SET id_vehicul = ?, id_sofer = ?, nume_ruta = ?, locatie_start = ?, locatie_final = ?, data_plecare_estimata = ?, data_sosire_estimata = ?, distanta_estimata_km = ?, timp_estimat_ore = ?, status = ?, observatii = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("issssssddssi", $id_vehicul, $id_sofer, $nume_ruta, $locatie_start, $locatie_final, $data_plecare_estimata_mysql, $data_sosire_estimata_mysql, $distanta_estimata_km, $timp_estimat_ore, $status, $observatii, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea rutei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Ruta a fost salvată cu succes!";
                error_log("PROCESS_PLANIFICARE_RUTE.PHP: Rută salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID rută invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM planificare_rute WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea rutei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Ruta a fost ștearsă cu succes!";
                error_log("PROCESS_PLANIFICARE_RUTE.PHP: Rută ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_PLANIFICARE_RUTE.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        // Conexiunea la baza de date este închisă automat la sfârșitul scriptului principal
        // if(isset($conn)) { $conn->close(); }
    }
    header("Location: planificare-rute.php"); // Redirecționăm întotdeauna la pagina listei de rute
    exit();

} else {
    error_log("PROCESS_PLANIFICARE_RUTE.PHP: Cerere non-POST. Redirecționare.");
    header("Location: planificare-rute.php"); // Redirecționează dacă nu e POST
    exit();
}
?>
