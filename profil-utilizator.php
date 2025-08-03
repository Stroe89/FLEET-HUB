<?php
ob_start(); // Începe output buffering
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        $user_id = $_POST['user_id'] ?? null;
        if (empty($user_id) || !is_numeric($user_id)) {
            throw new Exception("ID utilizator invalid sau lipsă.");
        }

        // Preluăm datele din formular
        $nume = trim($_POST['nume'] ?? '');
        $prenume = trim($_POST['prenume'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        $adresa = trim($_POST['adresa'] ?? '');
        $data_nasterii = empty($_POST['data_nasterii']) ? null : $_POST['data_nasterii'];
        $tara = trim($_POST['tara'] ?? '');
        $oras = trim($_POST['oras'] ?? '');
        $cod_postal = trim($_POST['cod_postal'] ?? '');
        $despre_mine = trim($_POST['despre_mine'] ?? '');

        // Câmpuri editabile doar de administrator
        $rol = $_POST['rol'] ?? null; // Va fi setat doar dacă adminul trimite
        $status_cont = $_POST['status_cont'] ?? null; // Va fi setat doar dacă adminul trimite

        // Preluăm calea imaginii existente din baza de date pentru a o șterge dacă se încarcă una nouă
        $current_image_path = null;
        $stmt_get_img = $conn->prepare("SELECT imagine_profil_path FROM angajati WHERE id = ?");
        if ($stmt_get_img) {
            $stmt_get_img->bind_param("i", $user_id);
            $stmt_get_img->execute();
            $result_img = $stmt_get_img->get_result();
            if ($row_img = $result_img->fetch_assoc()) {
                $current_image_path = $row_img['imagine_profil_path'];
            }
            $stmt_get_img->close();
        }

        $new_image_path = $current_image_path; // Presupunem că păstrăm imaginea existentă

        // Logica pentru upload-ul de imagine nouă
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = 'uploads/profile_images/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Eroare la crearea directorului de upload: " . $upload_dir);
                }
            }
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('profile_') . '.' . $file_extension;
            $destination_path = $upload_dir . $file_name;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination_path)) {
                throw new Exception("Eroare la încărcarea imaginii de profil.");
            }

            // Șterge imaginea veche dacă există și s-a încărcat o imagine nouă
            if ($current_image_path && file_exists($current_image_path)) {
                unlink($current_image_path);
            }
            $new_image_path = $destination_path;
        }


        // Construim interogarea SQL dinamic, în funcție de rolul utilizatorului care face update-ul
        // Administratorul poate schimba rolul și statusul contului
        // Utilizatorul normal poate schimba doar detaliile personale
        $sql = "UPDATE angajati SET nume = ?, prenume = ?, email = ?, telefon = ?, adresa = ?, data_nasterii = ?, tara = ?, oras = ?, cod_postal = ?, imagine_profil_path = ?, despre_mine = ?";
        $types = "sssssssssss";
        $params = [
            &$nume, &$prenume, &$email, &$telefon, &$adresa, &$data_nasterii, &$tara, &$oras, &$cod_postal, &$new_image_path, &$despre_mine
        ];

        // Adăugăm rolul și statusul doar dacă sunt trimise și utilizatorul are permisiuni de admin
        // (presupunem că $_SESSION['user_role'] este setat corect la login)
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Administrator') {
            if ($rol !== null) {
                $sql .= ", rol = ?";
                $types .= "s";
                $params[] = &$rol;
            }
            if ($status_cont !== null) {
                $sql .= ", status_cont = ?";
                $types .= "s";
                $params[] = &$status_cont;
            }
        }
        
        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = &$user_id;

        // Pregătim și executăm interogarea
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Eroare la pregătirea interogării de actualizare: " . $conn->error);
        }

        // Adăugăm stringul de tipuri la începutul array-ului de parametri
        array_unshift($params, $types);
        call_user_func_array([$stmt, 'bind_param'], $params);

        if (!$stmt->execute()) {
            if ($conn->errno == 1062) { // Duplicate entry for email
                throw new Exception("Adresa de email '" . htmlspecialchars($email) . "' este deja folosită de un alt utilizator.");
            }
            throw new Exception("Eroare la actualizarea profilului: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        $_SESSION['success_message'] = "Profilul a fost actualizat cu succes!";
        echo "success"; // Răspuns simplu pentru AJAX
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("PROCESS_USER_PROFILE.PHP: Eroare: " . $e->getMessage());
        echo "error: " . $e->getMessage(); // Răspuns simplu pentru AJAX
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo "Cerere invalidă.";
}
ob_end_flush();
?>
