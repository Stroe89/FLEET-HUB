<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Preluam datele din formular
    $id = $_POST['id'];
    $numar_inmatriculare = $_POST['numar_inmatriculare'];
    $model = $_POST['model'];
    $tip = $_POST['tip'];
    $an_fabricatie = $_POST['an_fabricatie'];
    $kilometraj = $_POST['kilometraj'];
    $status = $_POST['status'];
    $data_revizie = $_POST['data_revizie'];
    $km_revizie = $_POST['km_revizie'];
    $imagine_veche = $_POST['imagine_veche'];

    $imagine_path = $imagine_veche; // Presupunem ca pastram imaginea veche

    // Verificam daca a fost incarcata o imagine NOUA
    if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] == 0) {
        // Stergem imaginea veche, daca exista
        if (!empty($imagine_veche) && file_exists($imagine_veche)) {
            unlink($imagine_veche);
        }

        // Incarcam imaginea noua
        $upload_dir = 'uploads/';
        $file_name = uniqid() . '-' . basename($_FILES["imagine"]["name"]);
        $imagine_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($_FILES['imagine']['tmp_name'], $imagine_path)) {
            echo "Eroare la încărcarea noii imagini.";
            $imagine_path = $imagine_veche; // Daca esueaza, revenim la cea veche
        }
    }

    // Pregatim interogarea de UPDATE
    $stmt = $conn->prepare("UPDATE vehicule SET numar_inmatriculare = ?, model = ?, tip = ?, an_fabricatie = ?, kilometraj = ?, status = ?, data_revizie = ?, km_revizie = ?, imagine_path = ? WHERE id = ?");
    
    // Legam parametrii
    $stmt->bind_param("sssiisissi", $numar_inmatriculare, $model, $tip, $an_fabricatie, $kilometraj, $status, $data_revizie, $km_revizie, $imagine_path, $id);

    // Executam
    if ($stmt->execute()) {
        header("Location: vehicule.php?status=actualizat_succes");
    } else {
        echo "Eroare la actualizarea datelor: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: index.php");
}
exit();
?>