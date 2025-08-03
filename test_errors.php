 <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    echo "PHP este funcțional. Dacă vezi acest mesaj, erorile vor fi afișate mai jos:\n";

    // Aici poți încerca să accesezi baza de date sau să cauzezi o eroare intenționat
    // pentru a verifica dacă afișarea erorilor funcționează.
    // De exemplu:
    // require_once 'db_connect.php'; 
    // $result = $conn->query("SELECT * FROM tabel_inexistent");
    // var_dump($result);

    // Sau o eroare simplă:
    // undefined_function_call(); 
    ?>
    ```