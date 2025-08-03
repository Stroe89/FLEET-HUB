<?php
/**
 * NTS TOUR - Sistema de Salvare Teme
 * Salvează preferințele utilizatorului pentru teme
 */

session_start();
require_once 'db_connect.php';

// Setează header-ele pentru JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verifică dacă este request POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metoda nu este permisă', 405);
    }

    // Obține datele JSON
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Date JSON invalide', 400);
    }

    // Verifică dacă tema este prezentă
    if (!isset($data['theme']) || empty($data['theme'])) {
        throw new Exception('Tema nu a fost specificată', 400);
    }

    $theme = trim($data['theme']);

    // Validare temă
    $allowed_themes = [
        'blue-theme', 
        'light', 
        'dark', 
        'semi-dark', 
        'bordered-theme',
        'cyberpunk-theme',
        'ocean-theme',
        'forest-theme',
        'sunset-theme',
        'rose-theme',
        'space-theme',
        'mint-theme',
        'navy-stellar'
    ];

    if (!in_array($theme, $allowed_themes)) {
        throw new Exception('Tema specificată nu este validă', 400);
    }

    // Salvare în sesiune pentru acces rapid
    $_SESSION['theme_mode'] = $theme;

    // Răspuns de succes pentru utilizatori neautentificați
    $response = [
        'success' => true,
        'message' => 'Tema salvată în sesiune',
        'theme' => $theme,
        'saved_to' => 'session'
    ];

    // Dacă utilizatorul este autentificat, salvează și în baza de date
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $user_id = intval($_SESSION['user_id']);

        // Verifică dacă coloana theme_preference există, altfel o creează
        $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'theme_preference'");
        if ($check_column->num_rows === 0) {
            $alter_query = "ALTER TABLE users ADD COLUMN theme_preference VARCHAR(50) DEFAULT 'blue-theme'";
            if (!$conn->query($alter_query)) {
                error_log("Eroare la crearea coloanei theme_preference: " . $conn->error);
            }
        }

        // Pregătește și execută query-ul de update
        $stmt = $conn->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Eroare la pregătirea query-ului: ' . $conn->error, 500);
        }

        $stmt->bind_param("si", $theme, $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response = [
                    'success' => true,
                    'message' => 'Tema salvată cu succes în baza de date',
                    'theme' => $theme,
                    'saved_to' => 'database',
                    'user_id' => $user_id
                ];
            } else {
                // Posibil ca utilizatorul să nu existe sau tema să fie deja setată
                $response = [
                    'success' => true,
                    'message' => 'Tema salvată (nu s-au făcut modificări în BD)',
                    'theme' => $theme,
                    'saved_to' => 'database_no_change',
                    'user_id' => $user_id
                ];
            }
        } else {
            throw new Exception('Eroare la salvarea în baza de date: ' . $stmt->error, 500);
        }

        $stmt->close();
    }

    // Returnează răspunsul de succes
    echo json_encode($response);

} catch (Exception $e) {
    // Log eroarea pentru debugging
    error_log("Eroare save_theme.php: " . $e->getMessage());

    // Returnează răspunsul de eroare
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} finally {
    // Închide conexiunea la baza de date dacă este deschisă
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
