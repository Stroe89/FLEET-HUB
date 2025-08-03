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

    error_log("PROCESS_OFERTE.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $id = $_POST['id'] ?? null; // Va fi null pentru 'add'
                $id_client = $_POST['id_client'] ?? null;
                $numar_oferta = trim($_POST['numar_oferta'] ?? '');
                $data_oferta = $_POST['data_oferta'] ?? '';
                $descriere_serviciu = trim($_POST['descriere_serviciu'] ?? '');
                $valoare_oferta = empty($_POST['valoare_oferta']) ? null : (float)$_POST['valoare_oferta'];
                $moneda = trim($_POST['moneda'] ?? 'RON');
                $status = $_POST['status'] ?? 'Emisa';
                $observatii = trim($_POST['observatii'] ?? '');
                $cale_pdf = $_POST['cale_pdf'] ?? null; // Calea PDF-ului, dacă este pasată (ex: după generare)

                // Validări de bază
                if (empty($id_client) || !is_numeric($id_client) || empty($numar_oferta) || empty($data_oferta) || empty($descriere_serviciu) || empty($valoare_oferta)) {
                    throw new Exception("Clientul, numărul ofertei, data, descrierea serviciului și valoarea sunt obligatorii.");
                }
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_oferta) || !strtotime($data_oferta)) {
                    throw new Exception("Data ofertei nu este validă. Format așteptat:YYYY-MM-DD.");
                }

                if ($action == 'add') {
                    $stmt = $conn->prepare("INSERT INTO oferte_transport (id_client, numar_oferta, data_oferta, descriere_serviciu, valoare_oferta, moneda, status, observatii, cale_pdf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD: " . $conn->error);
                    $stmt->bind_param("isdsissss", $id_client, $numar_oferta, $data_oferta, $descriere_serviciu, $valoare_oferta, $moneda, $status, $observatii, $cale_pdf);
                } else { // edit
                    if (empty($id) || !is_numeric($id)) {
                        throw new Exception("ID ofertă invalid pentru editare.");
                    }
                    $stmt = $conn->prepare("UPDATE oferte_transport SET id_client = ?, numar_oferta = ?, data_oferta = ?, descriere_serviciu = ?, valoare_oferta = ?, moneda = ?, status = ?, observatii = ?, cale_pdf = ? WHERE id = ?");
                    if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT: " . $conn->error);
                    $stmt->bind_param("isdsissssi", $id_client, $numar_oferta, $data_oferta, $descriere_serviciu, $valoare_oferta, $moneda, $status, $observatii, $cale_pdf, $id);
                }

                if (!$stmt->execute()) {
                    // Verificăm dacă eroarea este de tip "Duplicate entry" pentru numar_oferta
                    if ($conn->errno == 1062) {
                        throw new Exception("O ofertă cu numărul '" . htmlspecialchars($numar_oferta) . "' există deja.");
                    } else {
                        throw new Exception("Eroare la executarea operației: " . $stmt->error);
                    }
                }
                $stmt->close();
                $_SESSION['success_message'] = "Oferta a fost salvată cu succes!";
                error_log("PROCESS_OFERTE.PHP: Oferta salvată cu succes (ID: " . ($id ?? $conn->insert_id) . ").");
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (empty($id) || !is_numeric($id)) {
                    throw new Exception("ID ofertă invalid pentru ștergere.");
                }

                // Opțional: Șterge fișierul PDF asociat dacă există
                $stmt_pdf = $conn->prepare("SELECT cale_pdf FROM oferte_transport WHERE id = ?");
                if ($stmt_pdf === false) throw new Exception("Eroare la pregătirea query-ului SELECT PDF: " . $conn->error);
                $stmt_pdf->bind_param("i", $id);
                $stmt_pdf->execute();
                $result_pdf = $stmt_pdf->get_result();
                $pdf_data = $result_pdf->fetch_assoc();
                $stmt_pdf->close();

                if ($pdf_data && !empty($pdf_data['cale_pdf']) && file_exists($pdf_data['cale_pdf'])) {
                    unlink($pdf_data['cale_pdf']);
                    error_log("PROCESS_OFERTE.PHP: Fișier PDF ofertă șters: " . $pdf_data['cale_pdf']);
                }

                $stmt = $conn->prepare("DELETE FROM oferte_transport WHERE id = ?");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului DELETE: " . $conn->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Eroare la ștergerea ofertei: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Oferta a fost ștearsă cu succes!";
                error_log("PROCESS_OFERTE.PHP: Oferta ștearsă cu succes (ID: " . $id . ").");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_OFERTE.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: ofertare-transport.php"); // Redirecționăm întotdeauna la pagina listei de oferte
    exit();

} else {
    error_log("PROCESS_OFERTE.PHP: Cerere non-POST. Redirecționare.");
    header("Location: ofertare-transport.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
