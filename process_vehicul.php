<?php
include 'config.php'; // Conectarea la baza de date

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $marca = $_POST['marca'] ?? '';
    $model = $_POST['model'] ?? '';
    $numar = $_POST['numar_inmatriculare'] ?? '';
    $an = $_POST['an_fabricatie'] ?? '';
    $culoare = $_POST['culoare'] ?? '';
    $combustibil = $_POST['tip_combustibil'] ?? '';
    $capacitate = $_POST['capacitate'] ?? '';

    $sql = "INSERT INTO vehicule (marca, model, numar_inmatriculare, an_fabricatie, culoare, tip_combustibil, capacitate) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisss", $marca, $model, $numar, $an, $culoare, $combustibil, $capacitate);

    if ($stmt->execute()) {
        $vehicul_id = $stmt->insert_id;
        header("Location: documente-vehicule.php?vehicul_id=" . $vehicul_id);
        exit();
    } else {
        echo "Eroare la salvare: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Acces interzis.";
}
?>
