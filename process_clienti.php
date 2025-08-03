<?php
session_start();
require_once 'db_connect.php'; // Asigură-te că acest fișier este corect și funcționează

header('Content-Type: application/json'); // Foarte important pentru a indica răspuns JSON

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Autentificare necesară.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Acțiune necunoscută.'];

try {
    if ($action === 'add' || $action === 'edit') {
        // Colectează și sanitizează datele clientului
        $id = ($action === 'edit') ? (int)$_POST['id'] : null; // ID-ul este necesar doar la editare
        
        $nume_companie = htmlspecialchars($_POST['nume_companie'] ?? '');
        $persoana_contact = htmlspecialchars($_POST['persoana_contact'] ?? '');
        $telefon = htmlspecialchars($_POST['telefon'] ?? '');
        $email = htmlspecialchars($_POST['email'] ?? '');
        $adresa = htmlspecialchars($_POST['adresa'] ?? '');
        $cui = htmlspecialchars($_POST['cui'] ?? '');
        // Corrected line for nr_reg_com (was previously identified as a source of syntax error)
        $nr_reg_com = htmlspecialchars($_POST['nr_reg_com'] ?? ''); 
        $observatii = htmlspecialchars($_POST['observatii'] ?? '');

        // --- START New fields collection and sanitization ---
        $tip_client = htmlspecialchars($_POST['tip_client'] ?? 'Persoană Juridică'); // Default if not set
        $cnp = htmlspecialchars($_POST['cnp'] ?? '');
        $serie_ci = htmlspecialchars($_POST['serie_ci'] ?? '');
        $iban = htmlspecialchars($_POST['iban'] ?? '');
        $banca = htmlspecialchars($_POST['banca'] ?? '');
        // Ensure capital_social is a float; default to 0.00 if empty or invalid
        $capital_social = isset($_POST['capital_social']) && is_numeric($_POST['capital_social']) ? (float)$_POST['capital_social'] : 0.00;
        $obiect_activitate = htmlspecialchars($_POST['obiect_activitate'] ?? '');
        $status_client = htmlspecialchars($_POST['status_client'] ?? 'Activ'); // Default if not set
        // Categories come as JSON string from Choices.js. Store directly as string.
        $categorii_json = $_POST['categorii'] ?? '[]'; 
        // --- END New fields collection and sanitization ---

        // Validare de bază
        if (empty($nume_companie)) {
            throw new Exception("Numele companiei este obligatoriu.");
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Adresa de email este invalidă.");
        }

        // Verifică unicitatea emailului (dacă este cazul)
        $stmt_check_email_sql = "SELECT COUNT(*) FROM clienti WHERE email = ?";
        if ($action === 'edit') {
            $stmt_check_email_sql .= " AND id != ?";
        }
        $stmt_check_email = $conn->prepare($stmt_check_email_sql);
        if ($stmt_check_email === false) {
            throw new Exception("Eroare la pregătirea interogării de verificare email: " . $conn->error);
        }
        if ($action === 'edit') {
            $stmt_check_email->bind_param("si", $email, $id);
        } else {
            $stmt_check_email->bind_param("s", $email);
        }
        $stmt_check_email->execute();
        $stmt_check_email->bind_result($count_email);
        $stmt_check_email->fetch();
        $stmt_check_email->close();
        if ($count_email > 0) {
            throw new Exception("Adresa de email există deja pentru un alt client.");
        }

        // Verifică unicitatea CUI (dacă este cazul)
        if (!empty($cui)) {
            $stmt_check_cui_sql = "SELECT COUNT(*) FROM clienti WHERE cui = ?";
            if ($action === 'edit') {
                $stmt_check_cui_sql .= " AND id != ?";
            }
            $stmt_check_cui = $conn->prepare($stmt_check_cui_sql);
            if ($stmt_check_cui === false) {
                throw new Exception("Eroare la pregătirea interogării de verificare CUI: " . $conn->error);
            }
            if ($action === 'edit') {
                $stmt_check_cui->bind_param("si", $cui, $id);
            } else {
                $stmt_check_cui->bind_param("s", $cui);
            }
            $stmt_check_cui->execute();
            $stmt_check_cui->bind_result($count_cui);
            $stmt_check_cui->fetch();
            $stmt_check_cui->close();
            if ($count_cui > 0) {
                throw new Exception("CUI-ul există deja pentru un alt client.");
            }
        }
        
        if ($action === 'add') {
            // Pregătește și execută inserarea în baza de date
            $sql = "INSERT INTO clienti (
                        nume_companie, persoana_contact, telefon, email, adresa, cui, nr_reg_com, observatii,
                        tip_client, cnp, serie_ci, iban, banca, capital_social, obiect_activitate, status_client, categorii
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Eroare la pregătirea interogării de adăugare: " . $conn->error);
            }
            $stmt->bind_param(
                "sssssssssssssdsss", // s = string, d = double (for capital_social)
                $nume_companie, $persoana_contact, $telefon, $email, $adresa, $cui, $nr_reg_com, $observatii,
                $tip_client, $cnp, $serie_ci, $iban, $banca, $capital_social, $obiect_activitate, $status_client, $categorii_json
            );

            if ($stmt->execute()) {
                $new_client_id = $conn->insert_id;
                $response = ['success' => true, 'message' => 'Client adăugat cu succes!', 'client_id' => $new_client_id];
            } else {
                throw new Exception("Eroare la adăugarea clientului în baza de date: " . $stmt->error);
            }
            $stmt->close();

        } elseif ($action === 'edit') {
            if (is_null($id)) {
                throw new Exception("ID-ul clientului este necesar pentru editare.");
            }
            // Pregătește și execută actualizarea în baza de date
            $sql = "UPDATE clienti SET 
                        nume_companie = ?, persoana_contact = ?, telefon = ?, email = ?, adresa = ?, cui = ?, nr_reg_com = ?, observatii = ?,
                        tip_client = ?, cnp = ?, serie_ci = ?, iban = ?, banca = ?, capital_social = ?, obiect_activitate = ?, status_client = ?, categorii = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Eroare la pregătirea interogării de editare: " . $conn->error);
            }
            $stmt->bind_param(
                "sssssssssssssdsssi", // s = string, d = double (for capital_social), i = integer (for id)
                $nume_companie, $persoana_contact, $telefon, $email, $adresa, $cui, $nr_reg_com, $observatii,
                $tip_client, $cnp, $serie_ci, $iban, $banca, $capital_social, $obiect_activitate, $status_client, $categorii_json,
                $id
            );

            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Client actualizat cu succes!'];
            } else {
                throw new Exception("Eroare la actualizarea clientului în baza de date: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];

        // Recommended: Soft delete (mark as is_deleted = TRUE) instead of physical delete
        // Make sure you have an `is_deleted` BOOLEAN column in your `clienti` table, default `FALSE`
        $stmt = $conn->prepare("UPDATE clienti SET is_deleted = TRUE WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Eroare la pregătirea interogării de ștergere (soft delete): " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Client marcat ca șters (inactiv) cu succes!'];
        } else {
            throw new Exception("Eroare la marcarea clientului ca șters: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Acțiune invalidă.';
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Client process error: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
exit();