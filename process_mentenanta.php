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

    error_log("PROCESS_MENTENANTA.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_vehicul = $_POST['id_vehicul'] ?? null;
                $tip_mentenanta = trim($_POST['tip_mentenanta'] ?? '');
                $descriere_problema = empty(trim($_POST['descriere_problema'] ?? '')) ? null : trim($_POST['descriere_problema']);
                $descriere_lucrari = trim($_POST['descriere_lucrari'] ?? '');
                $data_intrare_service = $_POST['data_intrare_service'] ?? '';
                $data_iesire_service = empty($_POST['data_iesire_service']) ? null : $_POST['data_iesire_service'];
                $cost_total = empty(trim($_POST['cost_total'] ?? '')) ? 0.00 : (float)trim($_POST['cost_total']);
                $factura_serie = empty(trim($_POST['factura_serie'] ?? '')) ? null : trim($_POST['factura_serie']);
                $factura_numar = empty(trim($_POST['factura_numar'] ?? '')) ? null : trim($_POST['factura_numar']);
                $status = trim($_POST['status'] ?? 'În Așteptare');
                $id_mecanic = empty($_POST['id_mecanic']) ? null : (int)$_POST['id_mecanic'];
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);
                $km_la_intrare = empty(trim($_POST['km_la_intrare'] ?? '')) ? null : (int)trim($_POST['km_la_intrare']);
                $km_la_iesire = empty(trim($_POST['km_la_iesire'] ?? '')) ? null : (int)trim($_POST['km_la_iesire']);

                // Validări de bază
                if (empty($id_vehicul) || !is_numeric($id_vehicul) || empty($tip_mentenanta) || empty($descriere_lucrari) || empty($data_intrare_service)) {
                    throw new Exception("Vehiculul, tipul mentenanței, descrierea lucrărilor și data intrării în service sunt obligatorii.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/", $data_intrare_service) || !strtotime($data_intrare_service)) {
                    throw new Exception("Data intrării în service nu este validă. Format așteptat:YYYY-MM-DDTHH:MM.");
                }
                if (!empty($data_iesire_service) && (!preg_match("/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/", $data_iesire_service) || !strtotime($data_iesire_service))) {
                    throw new Exception("Data ieșirii din service nu este validă. Format așteptat:YYYY-MM-DDTHH:MM.");
                }
                if (!empty($data_iesire_service) && strtotime($data_iesire_service) < strtotime($data_intrare_service)) {
                    throw new Exception("Data ieșirii din service nu poate fi înainte de data intrării.");
                }

                // Formatare date pentru MySQL DATETIME
                $data_intrare_service_mysql = date('Y-m-d H:i:s', strtotime($data_intrare_service));
                $data_iesire_service_mysql = $data_iesire_service ? date('Y-m-d H:i:s', strtotime($data_iesire_service)) : null;

                // Definirea variabilelor într-o listă pentru a fi ușor de numărat și de trimis la bind_param
                $params = [
                    $id_vehicul, $tip_mentenanta, $descriere_problema, $descriere_lucrari, $data_intrare_service_mysql, $data_iesire_service_mysql,
                    $cost_total, $factura_serie, $factura_numar, $status, $id_mecanic, $observatii, $km_la_intrare, $km_la_iesire
                ];

                if ($action == 'add') {
                    $sql = "INSERT INTO istoric_mentenanta (id_vehicul, tip_mentenanta, descriere_problema, descriere_lucrari, data_intrare_service, data_iesire_service, cost_total, factura_serie, factura_numar, status, id_mecanic, observatii, km_la_intrare, km_la_iesire) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    
                    // String de tipuri: 1 integer (id_vehicul), 1 double (cost_total), restul string-uri sau null
                    // Pentru simplitate și robustete, tratăm toate ca string-uri, iar MySQL va face conversia
                    // Excepție: id_vehicul și id_mecanic sunt INT. Cost total e DECIMAL (float).
                    $types = "issssdsssissii"; // i(id_vehicul), s(tip_mentenanta), s(descriere_problema), s(descriere_lucrari), s(data_intrare), s(data_iesire), d(cost_total), s(factura_serie), s(factura_numar), s(status), i(id_mecanic), s(observatii), i(km_intrare), i(km_iesire)
                    
                    // Asigură că variabilele numerice sunt de tipul corect pentru bind_param
                    $params[0] = (int)$params[0]; // id_vehicul
                    $params[6] = (float)$params[6]; // cost_total
                    $params[10] = $params[10] === null ? null : (int)$params[10]; // id_mecanic
                    $params[12] = $params[12] === null ? null : (int)$params[12]; // km_la_intrare
                    $params[13] = $params[13] === null ? null : (int)$params[13]; // km_la_iesire

                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID înregistrare mentenanță invalid pentru editare.");
                    }
                    $sql = "UPDATE istoric_mentenanta SET id_vehicul = ?, tip_mentenanta = ?, descriere_problema = ?, descriere_lucrari = ?, data_intrare_service = ?, data_iesire_service = ?, cost_total = ?, factura_serie = ?, factura_numar = ?, status = ?, id_mecanic = ?, observatii = ?, km_la_intrare = ?, km_la_iesire = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    
                    // String de tipuri pentru 14 variabile + ID: 1 integer (id_vehicul), 1 double (cost_total), 2 integer (id_mecanic, km_intrare, km_iesire), restul string-uri
                    $types = "issssdsssissiii"; // i(id_vehicul), s(tip_mentenanta), s(descriere_problema), s(descriere_lucrari), s(data_intrare), s(data_iesire), d(cost_total), s(factura_serie), s(factura_numar), s(status), i(id_mecanic), s(observatii), i(km_intrare), i(km_iesire), i(id)
                    
                    // Adaugă ID-ul la sfârșitul listei de variabile pentru UPDATE
                    $params[] = $id; 
                    
                    // Asigură că variabilele numerice sunt de tipul corect pentru bind_param
                    $params[0] = (int)$params[0]; // id_vehicul
                    $params[6] = (float)$params[6]; // cost_total
                    $params[10] = $params[10] === null ? null : (int)$params[10]; // id_mecanic
                    $params[12] = $params[12] === null ? null : (int)$params[12]; // km_la_intrare
                    $params[13] = $params[13] === null ? null : (int)$params[13]; // km_la_iesire
                    $params[14] = (int)$params[14]; // id-ul de la WHERE
                }

                // Apelăm bind_param folosind call_user_func_array
                array_unshift($params, $types); // Adaugă string-ul de tipuri la începutul array-ului
                call_user_func_array([$stmt, 'bind_param'], $params);
                
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la executarea operației: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Înregistrarea de mentenanță a fost salvată cu succes!";
                error_log("PROCESS_MENTENANTA.PHP: Înregistrare mentenanță salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID înregistrare mentenanță invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM istoric_mentenanta WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea înregistrării de mentenanță: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Înregistrarea de mentenanță a fost ștearsă cu succes!";
                error_log("PROCESS_MENTENANTA.PHP: Înregistrare mentenanță ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_MENTENANTA.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: istoric-mentenanta.php"); 
    exit();

} else {
    error_log("PROCESS_MENTENANTA.PHP: Cerere non-POST. Redirecționare.");
    header("Location: istoric-mentenanta.php"); 
    exit();
}
ob_end_flush();
?>
