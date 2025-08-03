<?php
ob_start();
session_start();
require_once 'config/database.php';

// Activează afișarea erorilor pentru depanare
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    error_log("PROCESS_CONFIGURARI_FISCALE.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        switch ($action) {
            case 'save':
                $id = $_POST['id'] ?? 1;
                $cota_tva = floatval($_POST['cota_tva'] ?? 19.0);
                $moneda_implicita = trim($_POST['moneda_implicita'] ?? 'RON');
                $tara_selectata = trim($_POST['tara_selectata'] ?? 'România');

                // Validări de bază
                if ($cota_tva <= 0 || $cota_tva > 100) {
                    throw new Exception("Cota TVA trebuie să fie între 0.01% și 100%.");
                }

                if (empty($moneda_implicita)) {
                    throw new Exception("Moneda implicită este obligatorie.");
                }

                // Verifică dacă înregistrarea există
                $checkStmt = $pdo->prepare("SELECT id FROM configurari_fiscale WHERE id = ?");
                $checkStmt->execute([$id]);
                $exists = $checkStmt->fetch();

                if ($exists) {
                    // UPDATE
                    $stmt = $pdo->prepare("
                        UPDATE configurari_fiscale 
                        SET cota_tva = ?, moneda_implicita = ?, tara_selectata = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$cota_tva, $moneda_implicita, $tara_selectata, $id]);
                } else {
                    // INSERT
                    $stmt = $pdo->prepare("
                        INSERT INTO configurari_fiscale (id, cota_tva, moneda_implicita, tara_selectata, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$id, $cota_tva, $moneda_implicita, $tara_selectata]);
                }

                $pdo->commit();
                $_SESSION['success_message'] = "Configurările fiscale au fost salvate cu succes pentru țara: " . htmlspecialchars($tara_selectata);
                break;

            default:
                throw new Exception("Acțiune invalidă: " . $action);
        }
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();
        }
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_CONFIGURARI_FISCALE.PHP: Eroare în tranzacție: " . $e->getMessage());
    }
} else {
    $_SESSION['error_message'] = "Metoda de cerere invalidă.";
}

// Redirecționare înapoi la pagina de configurări
header("Location: configurare-fiscala.php");
exit();
?>
