<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inițializăm variabilele pentru a evita erori la afișarea formularului
$success_message = '';
$error_message = '';

// Preluăm mesajele din sesiune dacă există
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Inițializăm variabilele pentru a evita erori la afișarea formularului
$vehicul = null;
// Preluăm tipurile de vehicule existente din baza de date pentru dropdown
$tipuri_vehicul_db = [];
// Verificăm dacă tabelul 'vehicule' există înainte de a interoga
if ($conn->query("SHOW TABLES LIKE 'vehicule'")->num_rows > 0) {
    $sql_tipuri_db = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
    $result_tipuri_db = $conn->query($sql_tipuri_db);
    if ($result_tipuri_db) {
        while ($row = $result_tipuri_db->fetch_assoc()) {
            $tipuri_vehicul_db[] = $row['tip'];
        }
    }
}


// Tipuri de vehicule predefinite din domeniul transporturilor (lista extinsă)
$tipuri_vehicul_predefinite = [
    'Autoturism', 'Autoutilitară', 'Camion', 'Autocar', 'Microbuz', 'Minibus (8+1)',
    'Camion (Rigid)', 'Camion (Articulat)', 'Furgonetă', 'Trailer (Semiremorcă)',
    'Remorcă', 'Mașină de Intervenție', 'Platformă Auto', 'Basculantă', 'Cisternă',
    'Frigorifică', 'Container', 'Duba', 'Autotren', 'Cap Tractor',
    'Semiremorcă Frigorifică', 'Semiremorcă Prelată', 'Semiremorcă Cisternă',
    'Semiremorcă Basculantă', 'Autospecială', 'Vehicul Electric', 'Vehicul Hibrid', 'Altele'
];

// Combinăm tipurile din DB cu cele predefinite și eliminăm duplicatele
$tipuri_vehicul_finale = array_unique(array_merge($tipuri_vehicul_db, $tipuri_vehicul_predefinite));
sort($tipuri_vehicul_finale); // Sortează alfabetic

// Statusuri vehicul
$statusuri_vehicul = ['Disponibil', 'În cursă', 'În service', 'Indisponibil'];


// Preluăm datele curente ale vehiculului din baza de date dacă este o editare
$id_vehicul_edit = $_GET['id'] ?? null;

if ($id_vehicul_edit && is_numeric($id_vehicul_edit)) {
    $stmt = $conn->prepare("SELECT * FROM vehicule WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_vehicul_edit);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $vehicul = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Procesare formular POST - Această secțiune trebuie să fie ÎNAINTE de orice output HTML
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        $action = $_POST['action'] ?? '';
        $id_form = $_POST['id'] ?? null;

        // Preluăm și pre-procesăm variabilele pentru a le face referințe valide
        // Este crucial să le atribuim variabilelor înainte de bind_param
        $numar_inmatriculare = trim($_POST['numar_inmatriculare'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $tip = trim($_POST['tip'] ?? '');
        $an_fabricatie = (int)($_POST['an_fabricatie'] ?? 0); 
        $kilometraj = (int)($_POST['kilometraj'] ?? 0); 
        $status = trim($_POST['status'] ?? '');
        
        $serie_sasiu_val = empty(trim($_POST['serie_sasiu'] ?? '')) ? null : trim($_POST['serie_sasiu']);
        $culoare_val = empty(trim($_POST['culoare'] ?? '')) ? null : trim($_POST['culoare']);
        $capacitate_cilindrica_val = empty(trim($_POST['capacitate_cilindrica'] ?? '')) ? null : (int)trim($_POST['capacitate_cilindrica']);
        $putere_cp_val = empty(trim($_POST['putere_cp'] ?? '')) ? null : (int)trim($_POST['putere_cp']);
        $consum_mediu_val = empty(trim($_POST['consum_mediu'] ?? '')) ? null : (float)trim($_POST['consum_mediu']);
        $masa_maxima_autorizata_val = empty(trim($_POST['masa_maxima_autorizata'] ?? '')) ? null : (int)trim($_POST['masa_maxima_autorizata']);
        $sarcina_utila_val = empty(trim($_POST['sarcina_utila'] ?? '')) ? null : (int)trim($_POST['sarcina_utila']);
        $numar_locuri_val = empty(trim($_POST['numar_locuri'] ?? '')) ? null : (int)trim($_POST['numar_locuri']);
        $data_achizitie_val = empty($_POST['data_achizitie']) ? null : $_POST['data_achizitie'];
        $cost_achizitie_val = empty(trim($_POST['cost_achizitie'] ?? '')) ? null : (float)trim($_POST['cost_achizitie']);
        $km_revizie_val = empty(trim($_POST['km_revizie'] ?? '')) ? null : (int)trim($_POST['km_revizie']);
        $observatii_val = empty(trim($_POST['observatii'] ?? '')) ? null : trim($_POST['observatii']);

        $locatie_curenta_lat_val = empty(trim($_POST['locatie_curenta_lat'] ?? '')) ? null : (float)trim($_POST['locatie_curenta_lat']);
        $locatie_curenta_lng_val = empty(trim($_POST['locatie_curenta_lng'] ?? '')) ? null : (float)trim($_POST['locatie_curenta_lng']);
        $ultima_actualizare_locatie_val = empty($_POST['ultima_actualizare_locatie']) ? null : $_POST['ultima_actualizare_locatie'];
        
        $imagine_path_val = $_POST['existing_imagine_path'] ?? ''; 

        // Logica pentru upload-ul de imagine
        if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Eroare la crearea directorului de upload: " . $upload_dir);
                }
            }
            $file_name = uniqid() . '-' . basename($_FILES["imagine"]["name"]);
            $new_imagine_path = $upload_dir . $file_name;
            if (!move_uploaded_file($_FILES['imagine']['tmp_name'], $new_imagine_path)) {
                throw new Exception("Eroare la încărcarea imaginii. Verificați permisiunile directorului.");
            }
            if ($action == 'edit' && !empty($imagine_path_val) && file_exists($imagine_path_val)) {
                unlink($imagine_path_val);
            }
            $imagine_path_val = $new_imagine_path;
        } else if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] != UPLOAD_ERR_NO_FILE) {
             throw new Exception("Eroare la încărcarea imaginii: Cod eroare " . $_FILES['imagine']['error']);
        }


        if ($action == 'add') {
            $sql = "INSERT INTO vehicule (numar_inmatriculare, model, tip, an_fabricatie, kilometraj, status, serie_sasiu, culoare, capacitate_cilindrica, putere_cp, consum_mediu, masa_maxima_autorizata, sarcina_utila, numar_locuri, data_achizitie, cost_achizitie, km_revizie, observatii, imagine_path, locatie_curenta_lat, locatie_curenta_lng, ultima_actualizare_locatie) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului ADD vehicul: " . $conn->error);
            
            $types = "sssiisssiiddiiisdssdds"; 
            
            $params = [
                &$numar_inmatriculare, &$model, &$tip, &$an_fabricatie, &$kilometraj, &$status, 
                &$serie_sasiu_val, &$culoare_val, &$capacitate_cilindrica_val, &$putere_cp_val, 
                &$consum_mediu_val, &$masa_maxima_autorizata_val, &$sarcina_utila_val, &$numar_locuri_val, 
                &$data_achizitie_val, &$cost_achizitie_val, &$km_revizie_val, &$observatii_val, 
                &$imagine_path_val, &$locatie_curenta_lat_val, &$locatie_curenta_lng_val, &$ultima_actualizare_locatie_val
            ];

            array_unshift($params, $types); 
            call_user_func_array([$stmt, 'bind_param'], $params);

        } else if ($action == 'edit') {
            if (empty($id_form) || !is_numeric($id_form)) {
                throw new Exception("ID vehicul invalid pentru editare.");
            }
            $sql = "UPDATE vehicule SET numar_inmatriculare = ?, model = ?, tip = ?, an_fabricatie = ?, kilometraj = ?, status = ?, serie_sasiu = ?, culoare = ?, capacitate_cilindrica = ?, putere_cp = ?, consum_mediu = ?, masa_maxima_autorizata = ?, sarcina_utila = ?, numar_locuri = ?, data_achizitie = ?, cost_achizitie = ?, km_revizie = ?, observatii = ?, imagine_path = ?, locatie_curenta_lat = ?, locatie_curenta_lng = ?, ultima_actualizare_locatie = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului EDIT vehicul: " . $conn->error);
            
            $types = "sssiisssiiddiiisdssddsi"; 
            
            $params = [
                &$numar_inmatriculare, &$model, &$tip, &$an_fabricatie, &$kilometraj, &$status, 
                &$serie_sasiu_val, &$culoare_val, &$capacitate_cilindrica_val, &$putere_cp_val, 
                &$consum_mediu_val, &$masa_maxima_autorizata_val, &$sarcina_utila_val, &$numar_locuri_val, 
                &$data_achizitie_val, &$cost_achizitie_val, &$km_revizie_val, &$observatii_val, 
                &$imagine_path_val, &$locatie_curenta_lat_val, &$locatie_curenta_lng_val, &$ultima_actualizare_locatie_val, 
                &$id_form
            ];

            array_unshift($params, $types);
            call_user_func_array([$stmt, 'bind_param'], $params);

        } else {
            throw new Exception("Acțiune invalidă.");
        }

        if (!$stmt->execute()) {
            if ($conn->errno == 1062) {
                $error_message_detail = "O înregistrare cu acest număr de înmatriculare sau serie șasiu există deja.";
                if (strpos($stmt->error, 'numar_inmatriculare') !== false) {
                    $error_message_detail = "Numărul de înmatriculare '" . htmlspecialchars($numar_inmatriculare) . "' este deja folosit.";
                } elseif (strpos($stmt->error, 'serie_sasiu') !== false) {
                    $error_message_detail = "Seria șasiu '" . htmlspecialchars($serie_sasiu_val) . "' este deja folosită.";
                }
                throw new Exception($error_message_detail);
            } else {
                throw new Exception("Eroare la executarea operației: " . $stmt->error);
            }
        }
        $stmt->close();
        
        $conn->commit(); 
        
        $_SESSION['success_message'] = "Vehiculul a fost salvat cu succes!";
        
    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Eroare: " . $e->getMessage();
        error_log("SALVEAZA_VEHICUL.PHP: Eroare în tranzacție: " . $e->getMessage());
    } finally {
        if(isset($conn)) { $conn->close(); }
    }
    
    // Redirecționăm întotdeauna înapoi la lista de vehicule
    header("Location: vehicule.php");
    exit(); // Oprim execuția scriptului după redirecționare

} // Sfârșitul blocului POST

// Acum includem header-ul.
// Asigură-te că db_connect.php este inclus și conexiunea $conn este deschisă înainte de header.php
require_once 'template/header.php'; 
?>

<title>NTS TOUR | <?php echo $vehicul ? 'Editează' : 'Adaugă'; ?> Vehicul</title>

<style>
    /* Stiluri generale preluate din tema */
    body, html, .main-content {
        color: #ffffff !important;
    }
    .text-muted, .text-secondary {
        color: #e0e0e0 !important;
    }
    h1, h2, h3, h4, h5, h6 {
        color: #ffffff !important;
    }
    p, span, strong, label, small {
        color: #ffffff !important;
    }
    .card {
        background-color: #2a3042 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .card-title {
        color: #ffffff !important;
    }
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .form-label {
        color: #e0e0e0 !important;
    }
    .form-control, .form-select, .form-check-label {
        background-color: #1a2035 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 0.5rem !important;
    }
    .form-control::placeholder {
        color: #b0b0b0 !important;
        opacity: 0.7 !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #6a90f1 !important;
        box-shadow: 0 0 0 0.25rem rgba(106, 144, 241, 0.25) !important;
    }
    .form-check-input:checked {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success {
        font-weight: bold !important;
        padding: 0.8rem 1.5rem !important;
        border-radius: 0.5rem !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3);
    }
    .btn-primary { background-color: #007bff !important; border-color: #007bff !important; color: #fff !important; }
    .btn-info { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: #fff !important; }
    .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #343a40 !important; }
    .btn-success { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }

    /* Stil specific pentru a ascunde săgețile de la input-ul de tip number */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }

    /* Stiluri pentru previzualizarea imaginii */
    #imagePreview {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        margin-top: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: block; /* Asigură că imaginea este un bloc */
        object-fit: contain;
        background-color: rgba(0,0,0,0.1);
        padding: 5px;
    }
    .form-group-image-upload {
        text-align: center;
        padding: 1rem;
        border: 1px dashed rgba(255, 255, 255, 0.2);
        border-radius: 0.5rem;
        background-color: rgba(0,0,0,0.1);
    }
    .alert {
        color: #ffffff !important;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
    }
    .alert-info {
        background-color: #203354 !important;
        border-color: #4285f4 !important;
    }
    .alert-success {
        background-color: #2c5234 !important;
        border-color: #4caf50 !important;
    }
    .alert-danger {
        background-color: #5c2c31 !important;
        border-color: #f44336 !important;
    }
    .alert-warning {
        background-color: #6a5300 !important;
        border-color: #ffc107 !important;
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Flotă</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item"><a href="vehicule.php">Vehicule</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $vehicul ? 'Editează' : 'Adaugă'; ?> Vehicul</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title"><?php echo $vehicul ? 'Formular Editare Vehicul' : 'Formular Adăugare Vehicul Nou'; ?></h4>
                        <p class="text-muted">Completează detaliile vehiculului pentru a-l adăuga în flota ta.</p>
                        <hr>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form action="salveaza-vehicul.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $vehicul ? 'edit' : 'add'; ?>">
                            <?php if ($vehicul): ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($vehicul['id']); ?>">
                                <input type="hidden" name="existing_imagine_path" value="<?php echo htmlspecialchars($vehicul['imagine_path'] ?? ''); ?>">
                            <?php endif; ?>
                            
                            <h5 class="mb-3">Secțiune 1: Detalii Principale</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="numarInmatriculare" class="form-label">Număr Înmatriculare:</label>
                                    <input type="text" class="form-control" id="numarInmatriculare" name="numar_inmatriculare" value="<?php echo htmlspecialchars($vehicul['numar_inmatriculare'] ?? ''); ?>" placeholder="Ex: B 01 ABC" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="model" class="form-label">Model:</label>
                                    <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($vehicul['model'] ?? ''); ?>" placeholder="Ex: Mercedes-Benz Actros" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipVehicul" class="form-label">Tip Vehicul:</label>
                                    <select id="tipVehicul" name="tip" class="form-select" required>
                                        <option value="">Alege...</option>
                                        <?php foreach ($tipuri_vehicul_finale as $tip_opt): ?>
                                            <option value="<?php echo htmlspecialchars($tip_opt); ?>" <?php echo (isset($vehicul['tip']) && $vehicul['tip'] == $tip_opt) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tip_opt); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="anFabricatie" class="form-label">An Fabricație:</label>
                                    <input type="number" class="form-control" id="anFabricatie" name="an_fabricatie" value="<?php echo htmlspecialchars($vehicul['an_fabricatie'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>" placeholder="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="kilometraj" class="form-label">Kilometraj Actual:</label>
                                    <input type="number" class="form-control" id="kilometraj" name="kilometraj" value="<?php echo htmlspecialchars($vehicul['kilometraj'] ?? ''); ?>" min="0" placeholder="0" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status:</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <?php foreach ($statusuri_vehicul as $status_opt): ?>
                                            <option value="<?php echo htmlspecialchars($status_opt); ?>" <?php echo (isset($vehicul['status']) && $vehicul['status'] == $status_opt) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status_opt); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <h5 class="mb-3 mt-4">Secțiune 2: Detalii Tehnice și Achiziție (Opțional)</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="serieSasiu" class="form-label">Serie Șasiu (VIN):</label>
                                    <input type="text" class="form-control" id="serieSasiu" name="serie_sasiu" value="<?php echo htmlspecialchars($vehicul['serie_sasiu'] ?? ''); ?>" placeholder="Ex: WDB123...">
                                </div>
                                <div class="col-md-6">
                                    <label for="culoare" class="form-label">Culoare:</label>
                                    <input type="text" class="form-control" id="culoare" name="culoare" value="<?php echo htmlspecialchars($vehicul['culoare'] ?? ''); ?>" placeholder="Ex: Albastru">
                                </div>
                                <div class="col-md-6">
                                    <label for="capacitateCilindrica" class="form-label">Capacitate Cilindrică (cm³):</label>
                                    <input type="number" class="form-control" id="capacitateCilindrica" name="capacitate_cilindrica" value="<?php echo htmlspecialchars($vehicul['capacitate_cilindrica'] ?? ''); ?>" min="0" placeholder="Ex: 12800">
                                </div>
                                <div class="col-md-6">
                                    <label for="putereCp" class="form-label">Putere (CP):</label>
                                    <input type="number" class="form-control" id="putereCp" name="putere_cp" value="<?php echo htmlspecialchars($vehicul['putere_cp'] ?? ''); ?>" min="0" placeholder="Ex: 450">
                                </div>
                                <div class="col-md-6">
                                    <label for="consumMediu" class="form-label">Consum Mediu (l/100km):</label>
                                    <input type="number" step="0.01" class="form-control" id="consumMediu" name="consum_mediu" value="<?php echo htmlspecialchars($vehicul['consum_mediu'] ?? ''); ?>" min="0" placeholder="Ex: 28.5">
                                </div>
                                <div class="col-md-6">
                                    <label for="masaMaximaAutorizata" class="form-label">Masă Maximă Autorizată (kg):</label>
                                    <input type="number" class="form-control" id="masaMaximaAutorizata" name="masa_maxima_autorizata" value="<?php echo htmlspecialchars($vehicul['masa_maxima_autorizata'] ?? ''); ?>" min="0" placeholder="Ex: 40000">
                                </div>
                                <div class="col-md-6">
                                    <label for="sarcinaUtila" class="form-label">Sarcină Utilă (kg):</label>
                                    <input type="number" class="form-control" id="sarcinaUtila" name="sarcina_utila" value="<?php echo htmlspecialchars($vehicul['sarcina_utila'] ?? ''); ?>" min="0" placeholder="Ex: 25000">
                                </div>
                                <div class="col-md-6">
                                    <label for="numarLocuri" class="form-label">Număr Locuri (inclusiv șofer):</label>
                                    <input type="number" class="form-control" id="numarLocuri" name="numar_locuri" value="<?php echo htmlspecialchars($vehicul['numar_locuri'] ?? ''); ?>" min="1" placeholder="Ex: 50">
                                </div>
                                <div class="col-md-6">
                                    <label for="dataAchizitie" class="form-label">Dată Achiziție:</label>
                                    <input type="date" class="form-control" id="dataAchizitie" name="data_achizitie" value="<?php echo htmlspecialchars($vehicul['data_achizitie'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="costAchizitie" class="form-label">Cost Achiziție (RON):</label>
                                    <input type="number" step="0.01" class="form-control" id="costAchizitie" name="cost_achizitie" value="<?php echo htmlspecialchars($vehicul['cost_achizitie'] ?? ''); ?>" min="0" placeholder="Ex: 85000.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="kmRevizie" class="form-label">Revizie la (km):</label>
                                    <input type="number" class="form-control" id="kmRevizie" name="km_revizie" value="<?php echo htmlspecialchars($vehicul['km_revizie'] ?? ''); ?>" min="0" placeholder="Ex: 50000">
                                </div>
                                <div class="col-12">
                                    <label for="observatii" class="form-label">Observații Suplimentare:</label>
                                    <textarea class="form-control" id="observatii" name="observatii" rows="3" placeholder="Detalii despre vehicul, dotări speciale, etc."></textarea>
                                </div>
                            </div>

                            <h5 class="mb-3 mt-4">Secțiune 3: Imagine Vehicul (Opțional)</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="form-group-image-upload">
                                        <label for="imagineVehicul" class="form-label">Încarcă Imagine Vehicul:</label>
                                        <input class="form-control" type="file" id="imagineVehicul" name="imagine" accept="image/png, image/jpeg, image/gif">
                                        <?php 
                                        $image_src = "https://placehold.co/300x200/2a3042/e0e0e0?text=Fara+imagine";
                                        $image_display_style = "display: none;";
                                        if ($vehicul && !empty($vehicul['imagine_path'])) {
                                            $image_src = htmlspecialchars($vehicul['imagine_path']);
                                            $image_display_style = "display: block;";
                                        }
                                        ?>
                                        <img src="<?php echo $image_src; ?>" id="imagePreview" alt="Previzualizare imagine" class="img-fluid mt-3" style="<?php echo $image_display_style; ?>">
                                        <?php if ($vehicul && !empty($vehicul['imagine_path'])): ?>
                                            <small class="text-muted mt-2"><a href="<?php echo htmlspecialchars($vehicul['imagine_path']); ?>" target="_blank">Vezi imaginea la dimensiune completă</a></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="vehicule.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div> 
    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imagineVehiculInput = document.getElementById('imagineVehicul');
    const imagePreview = document.getElementById('imagePreview');

    // Funcție pentru a actualiza previzualizarea imaginii
    function updateImagePreview() {
        const file = imagineVehiculInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            // Dacă nu există fișier selectat, afișează imaginea existentă sau placeholder
            const existingPath = document.querySelector('input[name="existing_imagine_path"]')?.value;
            if (existingPath) {
                imagePreview.src = existingPath;
                imagePreview.style.display = 'block';
            } else {
                imagePreview.src = "https://placehold.co/300x200/2a3042/e0e0e0?text=Fara+imagine";
                imagePreview.style.display = 'none';
            }
        }
    }

    if (imagineVehiculInput) {
        imagineVehiculInput.addEventListener('change', updateImagePreview);
        // La încărcarea paginii, verifică dacă există deja o imagine și afișeaz-o
        updateImagePreview(); 
    }
});
</script>
