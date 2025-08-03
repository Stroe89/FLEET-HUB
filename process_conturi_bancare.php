<?php
// process_conturi_bancare.php - Procesează adăugarea/editarea/ștergerea conturilor bancare
require_once 'core/auth_middleware.php';
require_once 'db_connect.php';

// Asigură că utilizatorul este autentificat și are permisiunea necesară
init_page_access('company_admin', 'index.php', "Nu aveți permisiunea de a gestiona conturile bancare.");

$current_user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT); // ID-ul contului bancar (doar pentru edit)
    $nume_banca = filter_var($_POST['nume_banca'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $iban = filter_var($_POST['iban'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $swift = filter_var($_POST['swift'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $adresa_banca = filter_var($_POST['adresa_banca'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $moneda = filter_var($_POST['moneda'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($action === 'add') {
        $sql = "INSERT INTO conturi_bancare (user_id, nume_banca, iban, swift, adresa_banca, moneda) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // 1 'i' (user_id) + 5 's' (nume_banca, iban, swift, adresa_banca, moneda) = 6 total
        $stmt->bind_param("isssss", $current_user_id, $nume_banca, $iban, $swift, $adresa_banca, $moneda);
    } else { // action === 'edit'
        $sql = "UPDATE conturi_bancare SET nume_banca = ?, iban = ?, swift = ?, adresa_banca = ?, moneda = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        // 5 's' (nume_banca -> moneda) + 1 'i' (id) + 1 'i' (user_id) = 7 total
        $stmt->bind_param("sssssii", $nume_banca, $iban, $swift, $adresa_banca, $moneda, $id, $current_user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Contul bancar a fost salvat cu succes!";
    } else {
        $_SESSION['error_message'] = "Eroare la salvarea contului bancar: " . $stmt->error;
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    if ($id) {
        $sql = "DELETE FROM conturi_bancare WHERE id = ? AND user_id = ?"; // Asigură ștergerea doar pentru userul curent
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $current_user_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Contul bancar a fost șters cu succes!";
        } else {
            $_SESSION['error_message'] = "Eroare la ștergerea contului bancar: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "ID cont bancar invalid pentru ștergere.";
    }
} else {
    $_SESSION['error_message'] = "Acțiune nevalidă.";
}

$conn->close();
header("Location: configurare-fiscala.php"); // Redirecționează înapoi la pagina fiscală
exit();
?>