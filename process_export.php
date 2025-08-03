<?php
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$export_type = $_GET['type'] ?? '';
$export_format = $_GET['format'] ?? '';
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

error_log("PROCESS_EXPORT.PHP: Cerere export primită. Tip: " . $export_type . ", Format: " . $export_format . ", Perioada: " . $start_date . " - " . $end_date);

try {
    if (empty($export_type) || empty($export_format)) {
        throw new Exception("Tipul de export și formatul sunt obligatorii.");
    }

    // Aici ar veni logica complexă de preluare a datelor și generare a fișierului
    // Exemplu simplificat:
    $data_to_export = [];
    $file_name = strtolower($export_type) . '_export_' . date('Ymd_His');

    switch ($export_type) {
        case 'Facturi':
            $sql = "SELECT f.*, c.nume_companie FROM facturi f JOIN clienti c ON f.id_client = c.id WHERE f.data_emiterii BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $data_to_export[] = $row;
            }
            $stmt->close();
            break;
        case 'Încasări':
            $sql = "SELECT pc.*, f.numar_factura, c.nume_companie FROM plati_clienti pc JOIN facturi f ON pc.id_factura = f.id JOIN clienti c ON f.id_client = c.id WHERE pc.data_platii BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $data_to_export[] = $row;
            }
            $stmt->close();
            break;
        case 'Plăți': // Presupunem că plăți se referă la cheltuieli
            $sql = "SELECT * FROM cheltuieli WHERE data_cheltuielii BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $data_to_export[] = $row;
            }
            $stmt->close();
            break;
        case 'Cheltuieli': // Duplicat pentru claritate, dacă 'Plăți' e altceva
            $sql = "SELECT * FROM cheltuieli WHERE data_cheltuielii BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $data_to_export[] = $row;
            }
            $stmt->close();
            break;
        default:
            throw new Exception("Tip de export necunoscut.");
    }

    if (empty($data_to_export)) {
        $_SESSION['error_message'] = "Nu s-au găsit date pentru export în perioada selectată.";
        header("Location: export-contabilitate.php");
        exit();
    }

    switch ($export_format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $file_name . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys($data_to_export[0])); // Header
            foreach ($data_to_export as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            break;
        case 'pdf':
            // Aici ar trebui să generezi un PDF cu o bibliotecă precum FPDF sau TCPDF
            // Exemplu: require('fpdf/fpdf.php'); $pdf = new FPDF(); ... $pdf->Output('D', $file_name . '.pdf');
            $_SESSION['error_message'] = "Generarea PDF-ului necesită o bibliotecă dedicată (ex: FPDF/TCPDF) și o implementare complexă.";
            header("Location: export-contabilitate.php");
            break;
        case 'excel':
            // Aici ar trebui să generezi un fișier Excel cu o bibliotecă precum PhpSpreadsheet
            // Exemplu: require 'vendor/autoload.php'; use PhpOffice\PhpSpreadsheet\Spreadsheet; ...
            $_SESSION['error_message'] = "Generarea Excel-ului necesită o bibliotecă dedicată (ex: PhpSpreadsheet) și o implementare complexă.";
            header("Location: export-contabilitate.php");
            break;
        default:
            throw new Exception("Format de export necunoscut.");
    }
    
    error_log("PROCESS_EXPORT.PHP: Export reușit pentru tipul " . $export_type . " în format " . $export_format);

} catch (Exception $e) {
    $_SESSION['error_message'] = "Eroare la export: " . $e->getMessage();
    error_log("PROCESS_EXPORT.PHP: Eroare la export: " . $e->getMessage());
    header("Location: export-contabilitate.php");
} finally {
    if(isset($conn)) { $conn->close(); }
}
exit();
ob_end_flush();
?>
