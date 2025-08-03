<?php
// Conectarea la baza de date
require_once 'db_connect.php';

// Verificam daca ID-ul a fost trimis prin GET
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // --- Pas optional, dar recomandat: Stergerea imaginii de pe server ---
    // 1. Selectam calea imaginii din baza de date inainte de a sterge randul
    $stmt_select = $conn->prepare("SELECT imagine_path FROM vehicule WHERE id = ?");
    if ($stmt_select === false) {
        echo "Eroare la pregătirea interogării de selecție: " . $conn->error;
        exit();
    }
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $imagine_path = $row['imagine_path'];
        
        // 2. Daca exista o cale si fisierul exista pe server, il stergem
        if (!empty($imagine_path) && file_exists($imagine_path)) {
            unlink($imagine_path); // Functia unlink() sterge un fisier
        }
    }
    $stmt_select->close();
    // --- Sfarsit pas stergere imagine ---


    // Pregatim si executam interogarea de stergere
    $stmt_delete = $conn->prepare("DELETE FROM vehicule WHERE id = ?");
    if ($stmt_delete === false) {
        echo "Eroare la pregătirea interogării de ștergere: " . $conn->error;
        exit();
    }
    $stmt_delete->bind_param("i", $id); // "i" inseamna ca ID-ul este un integer

    if ($stmt_delete->execute()) {
        // Stergerea a avut succes, trimitem un mesaj de succes
        echo "Vehiculul a fost șters cu succes.";
    } else {
        // A aparut o eroare, trimitem un mesaj de eroare
        echo "Eroare la ștergerea vehiculului: " . $stmt_delete->error;
    }

    $stmt_delete->close();
    $conn->close();

} else {
    // Daca nu a fost trimis niciun ID, trimitem un mesaj de eroare
    echo "Eroare: ID-ul vehiculului nu a fost specificat.";
}
exit(); // Asiguram ca niciun alt output nu este trimis
?>
