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

    error_log("PROCESS_UTILIZATORI.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $id_rol = $_POST['id_rol'] ?? null;

                // Validări de bază
                if (empty($username) || empty($password) || empty($id_rol) || !is_numeric($id_rol)) {
                    throw new Exception("Numele utilizatorului, parola și rolul sunt obligatorii.");
                }

                // Hash parola
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO utilizatori (username, password_hash, id_rol) VALUES (?, ?, ?)");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                $stmt->bind_param("ssi", $username, $password_hash, $id_rol);

                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry for username
                        throw new Exception("Numele de utilizator '" . htmlspecialchars($username) . "' există deja.");
                    } else {
                        throw new Exception("Eroare la adăugarea utilizatorului: " . $stmt->error);
                    }
                }
                $stmt->close();
                $_SESSION['success_message'] = "Utilizatorul a fost adăugat cu succes!";
                error_log("PROCESS_UTILIZATORI.PHP: Utilizator adăugat cu succes.");
                break;

            case 'edit':
                $id = $_POST['id'] ?? null;
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? ''; // Poate fi gol
                $id_rol = $_POST['id_rol'] ?? null;

                if (empty($id) || !is_numeric($id) || empty($username) || empty($id_rol) || !is_numeric($id_rol)) {
                    throw new Exception("ID utilizator, nume utilizator sau rol invalid.");
                }

                $sql_update = "UPDATE utilizatori SET username = ?, id_rol = ? ";
                $params = [$username, $id_rol];
                $types = "si";

                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql_update .= ", password_hash = ? ";
                    $params[] = $password_hash;
                    $types .= "s";
                }

                $sql_update .= "WHERE id = ?";
                $params[] = $id;
                $types .= "i";

                $stmt = $conn->prepare($sql_update);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);

                // Folosim call_user_func_array pentru bind_param dinamic
                call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
                
                if (!$stmt->execute()) {
                    if ($conn->errno == 1062) { // Duplicate entry for username
                        throw new Exception("Numele de utilizator '" . htmlspecialchars($username) . "' există deja.");
                    } else {
                        throw new Exception("Eroare la actualizarea utilizatorului: " . $stmt->error);
                    }
                }
                $stmt->close();
                $_SESSION['success_message'] = "Utilizatorul a fost actualizat cu succes!";
                error_log("PROCESS_UTILIZATORI.PHP: Utilizator actualizat cu succes (ID: " . $id . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID utilizator invalid pentru ștergere.");
                }

                // Protecție: Nu permite ștergerea singurului administrator sau a utilizatorului curent
                if ($id == $_SESSION['user_id']) {
                    throw new Exception("Nu vă puteți șterge propriul cont.");
                }
                $sql_check_admin = "SELECT COUNT(*) as count FROM utilizatori WHERE id_rol = (SELECT id FROM roluri_utilizatori WHERE nume_rol = 'Administrator')";
                $result_check_admin = $conn->query($sql_check_admin);
                $admin_count = $result_check_admin->fetch_assoc()['count'];
                
                $sql_user_role = "SELECT id_rol FROM utilizatori WHERE id = ?";
                $stmt_user_role = $conn->prepare($sql_user_role);
                $stmt_user_role->bind_param("i", $id);
                $stmt_user_role->execute();
                $user_role_id = $stmt_user_role->get_result()->fetch_assoc()['id_rol'];
                $stmt_user_role->close();

                $sql_admin_role_id = "SELECT id FROM roluri_utilizatori WHERE nume_rol = 'Administrator'";
                $result_admin_role_id = $conn->query($sql_admin_role_id);
                $admin_role_id = $result_admin_role_id->fetch_assoc()['id'];


                if ($user_role_id == $admin_role_id && $admin_count <= 1) {
                    throw new Exception("Nu puteți șterge ultimul cont de administrator.");
                }

                $stmt = $conn->prepare("DELETE FROM utilizatori WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea utilizatorului: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Utilizatorul a fost șters cu succes!";
                error_log("PROCESS_UTILIZATORI.PHP: Utilizator șters cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_UTILIZATORI.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: utilizatori-permisiuni.php"); // Redirecționăm întotdeauna la pagina listei de utilizatori
    exit();

} else {
    error_log("PROCESS_UTILIZATORI.PHP: Cerere non-POST. Redirecționare.");
    header("Location: utilizatori-permisiuni.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
