<?php
ob_start(); // Începe output buffering
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Funcție pentru a trimite un răspuns JSON
function sendJsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    error_log("PROCESS_SOCIAL_ACCOUNTS.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction(); 

    try {
        switch ($action) {
            case 'save_connection':
                $platform_name = trim($_POST['platform_name'] ?? '');
                $api_key = empty(trim($_POST['api_key'] ?? '')) ? null : trim($_POST['api_key']);
                $api_secret = empty(trim($_POST['api_secret'] ?? '')) ? null : trim($_POST['api_secret']);
                $access_token = empty(trim($_POST['access_token'] ?? '')) ? null : trim($_POST['access_token']);

                if (empty($platform_name)) {
                    throw new Exception("Numele platformei este obligatoriu.");
                }

                // Simulează testul de conexiune
                $is_connected = false;
                $connection_status_message = "Credențiale incomplete.";

                // O simulare simplă: dacă cel puțin un câmp cheie este completat, considerăm "conectat"
                if ($platform_name === 'whatsapp' || $platform_name === 'telegram' || $platform_name === 'tiktok') {
                    if (!empty($access_token)) {
                        $is_connected = true;
                        $connection_status_message = "Conectat OK (simulat).";
                    } else {
                        $connection_status_message = "Token de acces lipsă.";
                    }
                } else { // Facebook, Instagram (pot necesita key/secret)
                    if (!empty($api_key) && !empty($api_secret)) {
                        $is_connected = true;
                        $connection_status_message = "Conectat OK (simulat).";
                    } else {
                        $connection_status_message = "API Key sau Secret lipsă.";
                    }
                }
                
                $last_connected_at = $is_connected ? date('Y-m-d H:i:s') : null;

                $sql = "
                    INSERT INTO social_accounts_settings (platform_name, api_key, api_secret, access_token, is_connected, connection_status_message, last_connected_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        api_key = VALUES(api_key),
                        api_secret = VALUES(api_secret),
                        access_token = VALUES(access_token),
                        is_connected = VALUES(is_connected),
                        connection_status_message = VALUES(connection_status_message),
                        last_connected_at = VALUES(last_connected_at)
                ";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului SAVE connection: " . $conn->error);
                
                $stmt->bind_param("sssssis", 
                    $platform_name, 
                    $api_key, 
                    $api_secret, 
                    $access_token, 
                    $is_connected, 
                    $connection_status_message, 
                    $last_connected_at
                );

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea setărilor de conexiune: " . $stmt->error);
                }
                $stmt->close();
                
                $_SESSION['success_message'] = "Setările pentru " . ucfirst($platform_name) . " au fost salvate. Status: " . $connection_status_message;
                break;

            case 'disconnect_platform':
                $platform_name = trim($_POST['platform_name'] ?? '');

                if (empty($platform_name)) {
                    throw new Exception("Numele platformei este obligatoriu pentru deconectare.");
                }

                $sql = "UPDATE social_accounts_settings SET api_key = NULL, api_secret = NULL, access_token = NULL, is_connected = FALSE, connection_status_message = 'Deconectat manual', last_connected_at = NULL WHERE platform_name = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DISCONNECT: " . $conn->error);
                $stmt->bind_param("s", $platform_name);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la deconectarea platformei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Contul " . ucfirst($platform_name) . " a fost deconectat cu succes.";
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit(); 
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_SOCIAL_ACCOUNTS.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    
    // Redirecționăm întotdeauna înapoi la pagina de setări conturi social media
    header("Location: setari-conturi-conectate.php");
    exit();

} else {
    sendJsonResponse('error', 'Cerere invalidă.');
}
ob_end_flush();
?>
