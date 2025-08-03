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
    echo json_encode(['status' => $status, 'message' => $message, 'locations' => $data]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    error_log("PROCESS_HARTA_LIVE.PHP: Cerere POST primită. Acțiune: " . $action);

    try {
        switch ($action) {
            case 'fetch_locations':
                $locations = [];
                $sql = "SELECT id, model, numar_inmatriculare, tip, locatie_curenta_lat, locatie_curenta_lng, ultima_actualizare_locatie FROM vehicule WHERE locatie_curenta_lat IS NOT NULL AND locatie_curenta_lng IS NOT NULL";
                $result = $conn->query($sql);

                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $locations[] = $row;
                    }
                    sendJsonResponse('success', 'Locații preluate cu succes.', $locations);
                } else {
                    throw new Exception("Eroare la preluarea locațiilor vehiculelor: " . $conn->error);
                }
                break;

            // Poți adăuga aici alte acțiuni, cum ar fi actualizarea manuală a locației unui vehicul
            // case 'update_location':
            //     $id_vehicul = $_POST['id_vehicul'] ?? null;
            //     $lat = $_POST['lat'] ?? null;
            //     $lng = $_POST['lng'] ?? null;
            //     $timestamp = date('Y-m-d H:i:s');
            //     if (empty($id_vehicul) || !is_numeric($id_vehicul) || empty($lat) || empty($lng)) {
            //         throw new Exception("Date invalide pentru actualizarea locației.");
            //     }
            //     $stmt = $conn->prepare("UPDATE vehicule SET locatie_curenta_lat = ?, locatie_curenta_lng = ?, ultima_actualizare_locatie = ? WHERE id = ?");
            //     $stmt->bind_param("ddsi", $lat, $lng, $timestamp, $id_vehicul);
            //     if (!$stmt->execute()) {
            //         throw new Exception("Eroare la actualizarea locației: " . $stmt->error);
            //     }
            //     $stmt->close();
            //     sendJsonResponse('success', 'Locație actualizată cu succes.');
            //     break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
    } catch (Exception $e) {
        sendJsonResponse('error', $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }

} else {
    sendJsonResponse('error', 'Cerere invalidă.');
}
ob_end_flush();
?>
