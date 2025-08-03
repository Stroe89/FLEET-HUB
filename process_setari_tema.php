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

    error_log("PROCESS_SETARI_TEMA.PHP: Cerere POST primită. Acțiune: " . $action . ", Conținut: " . print_r($_POST, true));

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'save':
                $id = $_POST['id'] ?? 1; // ID-ul este întotdeauna 1
                $theme_mode = trim($_POST['theme_mode'] ?? 'blue-theme');
                $accent_color = trim($_POST['accent_color'] ?? '#0d6efd');
                $font_family = trim($_POST['font_family'] ?? 'Noto Sans');

                // Validări de bază (opțional, dar recomandat)
                if (empty($theme_mode) || empty($accent_color) || empty($font_family)) {
                    throw new Exception("Toate câmpurile temei sunt obligatorii.");
                }

                // Folosim INSERT ... ON DUPLICATE KEY UPDATE pentru a insera/actualiza rândul cu id=1
                $stmt = $conn->prepare("
                    INSERT INTO setari_tema (id, theme_mode, accent_color, font_family)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        theme_mode = VALUES(theme_mode),
                        accent_color = VALUES(accent_color),
                        font_family = VALUES(font_family)
                ");
                if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului SAVE: " . $conn->error);
                $stmt->bind_param("isss", $id, $theme_mode, $accent_color, $font_family);

                if (!$stmt->execute()) {
                    throw new Exception("Eroare la salvarea setărilor de temă: " . $stmt->error);
                }
                $stmt->close();
                $_SESSION['success_message'] = "Setările de temă au fost salvate cu succes!";
                error_log("PROCESS_SETARI_TEMA.PHP: Setări temă salvate cu succes.");
                break;

            default:
                throw new Exception("Acțiune invalidă.");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_SETARI_TEMA.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    header("Location: setari-tema.php"); // Redirecționăm întotdeauna la pagina de setări temă
    exit();

} else {
    error_log("PROCESS_SETARI_TEMA.PHP: Cerere non-POST. Redirecționare.");
    header("Location: setari-tema.php"); // Redirecționează dacă nu e POST
    exit();
}
ob_end_flush();
?>
