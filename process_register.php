<?php
session_start(); // Porneste sesiunea pentru a putea stoca mesaje
require_once 'db_connect.php'; // Include fisierul de conectare la baza de date

// Activam afisarea erorilor pentru debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_code = $_POST['employee_code'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validari de baza
    if (empty($employee_code) || empty($username) || empty($password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "Toate câmpurile sunt obligatorii.";
        header("Location: register.php"); // Redirectioneaza inapoi la pagina de inregistrare
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Parolele nu se potrivesc.";
        header("Location: register.php"); // Redirectioneaza inapoi la pagina de inregistrare
        exit();
    }

    // Criptarea parolei
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $conn->begin_transaction(); // Incepem o tranzactie pentru a asigura integritatea datelor

    try {
        // 1. Verificam Codul Angajatului si obtinem rolul
        $stmt = $conn->prepare("SELECT id, rol, folosit FROM coduri_angajati WHERE cod = ?");
        if ($stmt === false) {
            throw new Exception("Eroare la pregătirea query-ului pentru cod angajat: " . $conn->error);
        }
        $stmt->bind_param("s", $employee_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $code_data = $result->fetch_assoc();
        $stmt->close();

        if (!$code_data) {
            throw new Exception("Codul de angajat nu există.");
        }
        if ($code_data['folosit']) {
            throw new Exception("Codul de angajat a fost deja folosit.");
        }
        $role = $code_data['rol']; // Preluam rolul asociat codului
        $code_id = $code_data['id']; // Preluam ID-ul codului de angajat

        // 2. Verificam daca numele de utilizator exista deja
        $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE nume_utilizator = ?");
        if ($stmt === false) {
            throw new Exception("Eroare la pregătirea query-ului pentru nume utilizator: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("Numele de utilizator există deja. Alege altul.");
        }
        $stmt->close();

        // 3. Inseram noul utilizator in tabelul 'utilizatori'
        $stmt = $conn->prepare("INSERT INTO utilizatori (cod_angajat, nume_utilizator, parola_hash, rol) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception("Eroare la pregătirea query-ului pentru inserare utilizator: " . $conn->error);
        }
        $stmt->bind_param("ssss", $employee_code, $username, $hashed_password, $role);
        if (!$stmt->execute()) {
            throw new Exception("Eroare la înregistrarea utilizatorului: " . $stmt->error);
        }
        $new_user_id = $conn->insert_id; // Obtine ID-ul noului utilizator inserat
        $stmt->close();

        // 4. Marcam codul de angajat ca folosit in tabelul 'coduri_angajati'
        $stmt = $conn->prepare("UPDATE coduri_angajati SET folosit = TRUE, id_utilizator_folosit = ? WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Eroare la pregătirea query-ului pentru actualizare cod angajat: " . $conn->error);
        }
        $stmt->bind_param("ii", $new_user_id, $code_id);
        if (!$stmt->execute()) {
            throw new Exception("Eroare la marcarea codului de angajat ca folosit: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit(); // Finalizam tranzactia (salvam toate schimbarile)
        $_SESSION['success_message'] = "Contul a fost creat cu succes! Acum te poți autentifica.";
        header("Location: login.php"); // Redirectioneaza la pagina de login
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Anulam tranzactia in caz de eroare
        $_SESSION['error_message'] = "Eroare la înregistrare: " . $e->getMessage();
        header("Location: register.php"); // Redirectioneaza inapoi la pagina de inregistrare
        exit();
    } finally {
        // Asiguram ca conexiunea la baza de date este inchisa
        if(isset($conn)) { $conn->close(); }
    }
} else {
    // Daca scriptul nu a fost accesat prin metoda POST, redirectioneaza la pagina de inregistrare
    header("Location: register.php");
    exit();
}
?>
