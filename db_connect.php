<?php
// Detaliile de conectare la baza de date
$servername = "localhost";
$username = "r122401ntst"; // Utilizatorul real de pe serverul de hosting
$password = "6rs0zd7_N_Z,";     // Parola reală de pe serverul de hosting
$dbname = "r122401ntst_wp577"; // Numele bazei de date de pe serverul de hosting (sau r122401ntst_wp577 dacă este cazul)

// Crearea conexiunii
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificarea conexiunii
if ($conn->connect_error) {
    die("Conexiune eșuată: " . $conn->connect_error);
}

// Setează setul de caractere la UTF-8 (important pentru caractere românești)
$conn->set_charset("utf8mb4");

// NU închide tag-ul PHP aici pentru a evita spațiile albe accidentale
