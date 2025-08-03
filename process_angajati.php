<?php
session_start();
require_once 'db_connect.php'; // Asigură-te că ai fișierul de conectare la baza de date

header('Content-Type: application/json'); // Foarte important pentru a indica răspuns JSON

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Autentificare necesară.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Acțiune necunoscută.'];

try {
    if ($action === 'add') {
        // Colectează și sanitizează datele din formular
        $nume = htmlspecialchars($_POST['nume'] ?? '');
        $prenume = htmlspecialchars($_POST['prenume'] ?? '');
        $cod_intern = htmlspecialchars($_POST['cod_intern'] ?? '');
        $data_angajare = $_POST['data_angajare'] ?? null;
        $functie = htmlspecialchars($_POST['functie'] ?? '');
        $telefon = htmlspecialchars($_POST['telefon'] ?? '');
        $email = htmlspecialchars($_POST['email'] ?? '');
        $adresa = htmlspecialchars($_POST['adresa'] ?? '');
        $salariu = !empty($_POST['salariu']) ? (float)$_POST['salariu'] : null;
        $status = htmlspecialchars($_POST['status'] ?? '');
        $observatii = htmlspecialchars($_POST['observatii'] ?? '');

        // Câmpuri suplimentare
        $data_nastere = $_POST['data_nastere'] ?? null;
        $loc_nastere = htmlspecialchars($_POST['loc_nastere'] ?? '');
        $nationalitate = htmlspecialchars($_POST['nationalitate'] ?? '');
        $cnp = htmlspecialchars($_POST['cnp'] ?? '');
        $serie_ci = htmlspecialchars($_POST['serie_ci'] ?? '');
        $numar_ci = htmlspecialchars($_POST['numar_ci'] ?? '');
        $numar_permis = htmlspecialchars($_POST['numar_permis'] ?? '');
        $data_emitere_permis = $_POST['data_emitere_permis'] ?? null;
        $data_expirare_permis = $_POST['data_expirare_permis'] ?? null;
        $autoritate_emitenta_permis = htmlspecialchars($_POST['autoritate_emitenta_permis'] ?? '');
        $categorii_permis = htmlspecialchars($_POST['categorii_permis'] ?? ''); // Acesta va fi un string separat prin virgulă
        $data_valabilitate_fisa_medicala = $_POST['data_valabilitate_fisa_medicala'] ?? null;
        $data_valabilitate_aviz_psihologic = $_POST['data_valabilitate_aviz_psihologic'] ?? null;
        $nume_contact_urgenta = htmlspecialchars($_POST['nume_contact_urgenta'] ?? '');
        $relatie_contact_urgenta = htmlspecialchars($_POST['relatie_contact_urgenta'] ?? '');
        $telefon_contact_urgenta = htmlspecialchars($_POST['telefon_contact_urgenta'] ?? '');
        $atestate = htmlspecialchars($_POST['atestate'] ?? '');

        // Validare de bază (se pot adăuga validări mai complexe)
        if (empty($nume) || empty($prenume) || empty($cod_intern) || empty($data_angajare) || empty($functie) || empty($status)) {
            throw new Exception("Câmpurile Nume, Prenume, Cod Intern, Dată Angajare, Funcție și Status sunt obligatorii.");
        }
        
        // Verifică unicitatea codului intern (exemplu simplificat)
        $stmt_check_code = $conn->prepare("SELECT COUNT(*) FROM angajati WHERE cod_intern = ?");
        $stmt_check_code->bind_param("s", $cod_intern);
        $stmt_check_code->execute();
        $stmt_check_code->bind_result($count);
        $stmt_check_code->fetch();
        $stmt_check_code->close();

        if ($count > 0) {
            throw new Exception("Codul intern generat există deja. Te rog generează altul.");
        }


        // Pregătește și execută inserarea în baza de date
        // Asigură-te că numărul de '?' și tipurile corespund cu numărul de coloane din tabela ta 'angajati'
        $stmt = $conn->prepare("INSERT INTO angajati (
            nume, prenume, cod_intern, data_angajare, functie, telefon, email, adresa, salariu, status, observatii,
            data_nastere, loc_nastere, nationalitate, cnp, serie_ci, numar_ci, numar_permis, data_emitere_permis,
            data_expirare_permis, autoritate_emitenta_permis, categorii_permis, data_valabilitate_fisa_medicala,
            data_valabilitate_aviz_psihologic, nume_contact_urgenta, relatie_contact_urgenta, telefon_contact_urgenta, atestate
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?
        )");

        // Tipuri de date pentru bind_param: s=string, i=integer, d=double, b=blob
        $stmt->bind_param("ssssssssdsssssssssssssssssss",
            $nume, $prenume, $cod_intern, $data_angajare, $functie, $telefon, $email, $adresa, $salariu, $status, $observatii,
            $data_nastere, $loc_nastere, $nationalitate, $cnp, $serie_ci, $numar_ci, $numar_permis, $data_emitere_permis,
            $data_expirare_permis, $autoritate_emitenta_permis, $categorii_permis, $data_valabilitate_fisa_medicala,
            $data_valabilitate_aviz_psihologic, $nume_contact_urgenta, $relatie_contact_urgenta, $telefon_contact_urgenta, $atestate
        );

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Angajat adăugat cu succes!'];
        } else {
            throw new Exception("Eroare la adăugarea angajatului: " . $stmt->error);
        }
        $stmt->close();

    } else {
        $response['message'] = 'Acțiune invalidă.';
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    // Loghează eroarea pentru depanare
    error_log("Eroare în process_angajati.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
exit();
?>