<?php
session_start();
require_once 'core/auth_middleware.php';
require_once 'tcpdf/tcpdf.php';

// Ensure we only output JSON
ob_clean(); // Clear any previous output
header('Content-Type: application/json');

try {
    $uploadDir = __DIR__ . '/uploads/pdf';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $conn = getDbConnection();
    $user_id = $_SESSION['user_id'];
    
    // Generate PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('NTS Tour');
    $pdf->SetTitle('Date Companie');
    $pdf->AddPage();
    
    // Get company data
    $stmt = $conn->prepare("SELECT * FROM company_data WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company_data = $result->fetch_assoc();

    // Add content to PDF
    $html = "<h1>Date Companie</h1>";
    $html .= "<p>Nume Companie: {$company_data['nume_companie']}</p>";
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Save PDF
    $pdfName = 'company_' . $user_id . '_' . time() . '.pdf';
    $pdfPath = $uploadDir . '/' . $pdfName;
    $pdf->Output($pdfPath, 'F');
    
    // Generate URL
    $pdfUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/pdf/' . $pdfName;
    
    echo json_encode([
        'success' => true,
        'pdfUrl' => $pdfUrl
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}