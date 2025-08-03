<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$locations = [];

// Preluăm locațiile vehiculelor din baza de date
$sql = "SELECT id_vehicul, latitudine, longitudine, ultima_actualizare, status_vehicul, viteza, directie FROM locatii_vehicule";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

$conn->close();

echo json_encode($locations);
?>
