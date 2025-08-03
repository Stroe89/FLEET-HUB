<?php
// process_login.php - Procesează cererea de autentificare
session_start();
require_once 'config/database.php'; // Folosim noua configurație

// Funcție pentru validarea și sanitizarea inputului
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_code = sanitizeInput($_POST['employee_code'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($employee_code) || empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Toate câmpurile sunt obligatorii!";
        header("Location: login.php");
        exit();
    }

    try {
        // Conectare la baza de date
        $pdo = getDBConnection();
        
        // Căutare utilizator în baza de date cu cod angajat și username
        $stmt = $pdo->prepare("
            SELECT id, employee_code, username, password_hash, role, status, full_name 
            FROM users 
            WHERE employee_code = ? AND username = ? AND status = 'active'
        ");
        $stmt->execute([$employee_code, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Autentificare reușită
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['employee_code'] = $user['employee_code'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['logged_in'] = true;
            
            // Actualizare ultimul login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            $_SESSION['success_message'] = "Bine ai venit, " . htmlspecialchars($user['full_name']) . "!";
            
            // Redirecționează către pagina principală sau dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Autentificare eșuată
            $_SESSION['error_message'] = "Cod angajat, nume utilizator sau parolă incorectă!";
            header("Location: login.php");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error_message'] = "Eroare la autentificare. Încearcă din nou.";
        header("Location: login.php");
        exit();
    }
} else {
    // Dacă nu este o cerere POST, redirecționează la pagina de login
    header("Location: login.php");
    exit();
}
?>