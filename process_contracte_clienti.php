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
        $nr_reg_com = htmlspecialchars($_POST['nr_reg_com'] ?? '');
        $observatii = htmlspecialchars($_POST['observatii'] ?? '');

        // Validare de bază
        if (empty($nume_companie)) {
            throw new Exception("Numele companiei este obligatoriu.");
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Adresa de email este invalidă.");
        }

        // Verifică unicitatea emailului (dacă este cazul)
        $stmt_check_email = $conn->prepare("SELECT COUNT(*) FROM clienti WHERE email = ? AND id != ?");
        $stmt_check_email->bind_param("si", $email, $id);
        $stmt_check_email->execute();
        $stmt_check_email->bind_result($count_email);
        $stmt_check_email->fetch();
        $stmt_check_email->close();
        if ($count_email > 0) {
            throw new Exception("Adresa de email există deja pentru un alt client.");
        }

        // Verifică unicitatea CUI (dacă este cazul)
        if (!empty($cui)) {
            $stmt_check_cui = $conn->prepare("SELECT COUNT(*) FROM clienti WHERE cui = ? AND id != ?");
            $stmt_check_cui->bind_param("si", $cui, $id);
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
            $stmt = $conn->prepare("INSERT INTO clienti (nume_companie, persoana_contact, telefon, email, adresa, cui, nr_reg_com, observatii) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Eroare la pregătirea interogării de adăugare: " . $conn->error);
            }
            $stmt->bind_param("ssssssss", $nume_companie, $persoana_contact, $telefon, $email, $adresa, $cui, $nr_reg_com, $observatii);

            if ($stmt->execute()) {
                $new_client_id = $conn->insert_id;
                $response = ['success' => true, 'message' => 'Client adăugat cu succes!', 'client' => ['id' => $new_client_id]];
            } else {
                throw new Exception("Eroare la adăugarea clientului în baza de date: " . $stmt->error);
            }
            $stmt->close();

        } elseif ($action === 'edit') {
            if (is_null($id)) {
                throw new Exception("ID-ul clientului este necesar pentru editare.");
            }
            // Pregătește și execută actualizarea în baza de date
            $stmt = $conn->prepare("UPDATE clienti SET nume_companie = ?, persoana_contact = ?, telefon = ?, email = ?, adresa = ?, cui = ?, nr_reg_com = ?, observatii = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Eroare la pregătirea interogării de editare: " . $conn->error);
            }
            $stmt->bind_param("ssssssssi", $nume_companie, $persoana_contact, $telefon, $email, $adresa, $cui, $nr_reg_com, $observatii, $id);

            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Client actualizat cu succes!'];
            } else {
                throw new Exception("Eroare la actualizarea clientului în baza de date: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];

        // Șterge contractele asociate (dacă nu ai ON DELETE CASCADE)
        // $stmt_contracts = $conn->prepare("DELETE FROM contracte_clienti WHERE id_client = ?");
        // $stmt_contracts->bind_param("i", $id);
        // $stmt_contracts->execute();
        // $stmt_contracts->close();

        $stmt = $conn->prepare("DELETE FROM clienti WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Eroare la pregătirea interogării de ștergere: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Client șters cu succes!'];
        } else {
            throw new Exception("Eroare la ștergerea clientului: " . $stmt->error);
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
