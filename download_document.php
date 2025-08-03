<?php
session_start(); // Porneste sesiunea (daca e necesar pentru autentificare)
require_once 'db_connect.php'; // Conexiunea la baza de date

// Verificam daca ID-ul documentului a fost trimis prin URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID document invalid.");
}

$document_id = $_GET['id'];

// Preluam calea fisierului si numele original din baza de date
$stmt = $conn->prepare("SELECT cale_fisier, nume_original_fisier FROM documente WHERE id = ?");
if ($stmt === false) {
    die("Eroare la pregătirea interogării: " . $conn->error);
}
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Verificam daca documentul exista si calea fisierului este valida
if ($document && !empty($document['cale_fisier']) && file_exists($document['cale_fisier'])) {
    $file_path = $document['cale_fisier'];
    $file_name = $document['nume_original_fisier'];

    // Asiguram ca fisierul nu este un director traversal (securitate)
    // basename() extrage doar numele fisierului din cale
    $file_name = basename($file_name);
    // realpath() rezolva orice path-uri relative sau simbolice
    $file_path_real = realpath($file_path); 
    
    // Verificam daca calea reala a fisierului se afla in directorul de upload permis
    // Aceasta este o masura de securitate cruciala
    $upload_dir = realpath('uploads/documents/');
    if ($upload_dir === false || strpos($file_path_real, $upload_dir) !== 0) {
        die("Acces interzis la fisier. Calea nu este în directorul de upload permis.");
    }

    // Setam headerele HTTP pentru a forta descarcarea fisierului
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream'); // Tip generic pentru descarcare
    header('Content-Disposition: attachment; filename="' . $file_name . '"'); // Numele fisierului la descarcare
    header('Expires: 0'); // Nu se cacheaza
    header('Cache-Control: must-revalidate'); // Revalidare obligatorie
    header('Pragma: public'); // Pentru compatibilitate cu browsere vechi
    header('Content-Length: ' . filesize($file_path_real)); // Dimensiunea fisierului

    // Citeste si trimite fisierul catre browser
    readfile($file_path_real);
    exit(); // Oprim executia scriptului dupa trimiterea fisierului
} else {
    // Daca fisierul nu exista in baza de date sau pe server
    die("Fișierul nu a fost găsit sau nu există.");
}
?>
