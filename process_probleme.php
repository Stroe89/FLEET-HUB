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

    error_log("PROCESS_PROBLEME.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'add': // Adăugat pentru a putea raporta și direct aici dacă se dorește
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_vehicul = $_POST['id_vehicul'] ?? null;
                $nume_raportor = trim($_POST['nume_raportor'] ?? '');
                $tip_problema = trim($_POST['tip_problema'] ?? '');
                $descriere_problema = trim($_POST['descriere_problema'] ?? '');
                $gravitate = $_POST['gravitate'] ?? null;
                $status = trim($_POST['status'] ?? 'Raportată');
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);

                // Câmpuri specifice rezolvării (doar la edit/resolve, dar le preluăm pentru consistență)
                $data_rezolvare = empty($_POST['data_rezolvare']) ? null : $_POST['data_rezolvare'];
                $solutie_rezolvare = empty(trim($_POST['solutie_rezolvare'] ?? '')) ? null : trim($_POST['solutie_rezolvare']);

                // Validări de bază
                if (empty($id_vehicul) || !is_numeric($id_vehicul) || empty($nume_raportor) || empty($tip_problema) || empty($descriere_problema) || empty($gravitate) || !is_numeric($gravitate)) {
                    throw new Exception("Vehiculul, raportorul, tipul problemei, descrierea și gravitatea sunt obligatorii.");
                }
                if ($gravitate < 1 || $gravitate > 5) {
                    throw new Exception("Gravitatea trebuie să fie între 1 și 5.");
                }

                // Formatare date pentru MySQL DATETIME
                $data_rezolvare_mysql = $data_rezolvare ? date('Y-m-d H:i:s', strtotime($data_rezolvare)) : null;

                // Definirea variabilelor într-o listă pentru a fi ușor de numărat și de trimis la bind_param
                $params = [
                    $id_vehicul, $nume_raportor, $tip_problema, $descriere_problema, $gravitate, $status, $data_rezolvare_mysql, $solutie_rezolvare, $observatii
                ];

                if ($action == 'add') {
                    $sql = "INSERT INTO probleme_raportate (id_vehicul, nume_raportor, tip_problema, descriere_problema, gravitate, status, data_rezolvare, solutie_rezolvare, observatii) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    
                    // String de tipuri: i(id_vehicul), restul string-uri sau null
                    $types = "isssissss"; 

                    // Asigură că variabilele numerice sunt de tipul corect pentru bind_param
                    $params[0] = (int)$params[0]; // id_vehicul
                    $params[4] = (int)$params[4]; // gravitate

                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID problemă invalid pentru editare.");
                    }
                    $sql = "UPDATE probleme_raportate SET id_vehicul = ?, nume_raportor = ?, tip_problema = ?, descriere_problema = ?, gravitate = ?, status = ?, data_rezolvare = ?, solutie_rezolvare = ?, observatii = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    
                    // String de tipuri: i(id_vehicul), restul string-uri sau null, i(id) la final
                    $types = "isssissssi"; 
                    
                    // Adaugă ID-ul la sfârșitul listei de variabile pentru UPDATE
                    $params[] = $id; 
                    
                    // Asigură că variabilele numerice sunt de tipul corect pentru bind_param
                    $params[0] = (int)$params[0]; // id_vehicul
                    $params[4] = (int)$params[4]; // gravitate
                    $params[9] = (int)$params[9]; // id-ul de la WHERE
                }

                // Apelăm bind_param folosind call_user_func_array
                array_unshift($params, $types); // Adaugă string-ul de tipuri la începutul array-ului
                call_user_func_array([$stmt, 'bind_param'], $params);
                
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la executarea operației: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Problema a fost salvată cu succes!";
                error_log("PROCESS_PROBLEME.PHP: Problemă salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'resolve': // Acțiune separată pentru rezolvare
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID problemă invalid pentru rezolvare.");
                }
                $data_rezolvare = $_POST['data_rezolvare'] ?? null;
                $solutie_rezolvare = trim($_POST['solutie_rezolvare'] ?? '');
                $observatii = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);
                $status = 'Rezolvată';

                if (empty($data_rezolvare) || !strtotime($data_rezolvare)) {
                    throw new Exception("Data rezolvării este obligatorie.");
                }
                if (empty($solutie_rezolvare)) {
                    throw new Exception("Soluția de rezolvare este obligatorie.");
                }

                $data_rezolvare_mysql = date('Y-m-d H:i:s', strtotime($data_rezolvare));

                $sql_resolve = "UPDATE probleme_raportate SET status = ?, data_rezolvare = ?, solutie_rezolvare = ?, observatii = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_resolve);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului RESOLVE: " . $conn->error);
                
                $types = "ssssi"; // s(status), s(data_rezolvare), s(solutie_rezolvare), s(observatii), i(id)
                $params_resolve = [$status, $data_rezolvare_mysql, $solutie_rezolvare, $observatii, $id];

                array_unshift($params_resolve, $types);
                call_user_func_array([$stmt, 'bind_param'], $params_resolve);

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la rezolvarea problemei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Problema a fost marcată ca rezolvată!";
                error_log("PROCESS_PROBLEME.PHP: Problemă rezolvată cu succes (ID: " . $id . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID problemă invalid pentru ștergere.");
                }
                $stmt = $conn->prepare("DELETE FROM probleme_raportate WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea problemei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Problema a fost ștearsă cu succes!";
                error_log("PROCESS_PROBLEME.PHP: Problemă ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_PROBLEME.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: notificari-probleme-raportate.php"); 
    exit();

} else {
    error_log("PROCESS_PROBLEME.PHP: Cerere non-POST. Redirecționare.");
    header("Location: notificari-probleme-raportate.php"); 
    exit();
}
ob_end_flush();
?>
