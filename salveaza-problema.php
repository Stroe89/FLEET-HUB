<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Preluam datele din formular
    $id_vehicul = $_POST['id_vehicul'];
    $tip_problema = $_POST['tip_problema'];
    $prioritate = $_POST['prioritate'];
    $descriere = $_POST['descriere'];

    // Validam ca am primit datele necesare
    if (!empty($id_vehicul) && !empty($tip_problema) && !empty($prioritate) && !empty($descriere)) {

        // Pregatim interogarea SQL pentru a insera problema in tabelul 'mentenanta'
        $stmt = $conn->prepare("INSERT INTO mentenanta (id_vehicul, tip_problema, prioritate, descriere) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $id_vehicul, $tip_problema, $prioritate, $descriere);

        if ($stmt->execute()) {
            // Daca totul a mers bine, redirectionam inapoi cu un mesaj de succes
            header("Location: vehicule.php?status=problema_raportata");
        } else {
            echo "Eroare la salvarea problemei: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Toate câmpurile sunt obligatorii.";
    }

    $conn->close();

} else {
    // Daca nu s-a trimis prin POST, redirectionam
    header("Location: index.php");
}
exit();
?>