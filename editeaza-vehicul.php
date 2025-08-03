<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie
require_once 'template/header.php';

// Verificam daca un ID a fost trimis prin URL
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>Niciun vehicul specificat.</div>";
    require_once 'template/footer.php';
    exit();
}

$id = $_GET['id'];

// Preluam datele curente ale vehiculului din baza de date, inclusiv noile coloane
$stmt = $conn->prepare("SELECT * FROM vehicule WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Vehiculul nu a fost găsit.</div>";
    require_once 'template/footer.php';
    exit();
}

$vehicul = $result->fetch_assoc();
$stmt->close();

// Preluăm tipurile de vehicule existente din baza de date pentru dropdown
$tipuri_vehicul_db = [];
$sql_tipuri_db = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
$result_tipuri_db = $conn->query($sql_tipuri_db);
if ($result_tipuri_db) {
    while ($row = $result_tipuri_db->fetch_assoc()) {
        $tipuri_vehicul_db[] = $row['tip'];
    }
}

// Tipuri de vehicule predefinite din domeniul transporturilor
$tipuri_vehicul_predefinite = [
    'Autocar', 'Microbuz', 'Minibus (8+1)', 'Camion (Rigid)', 'Camion (Articulat)', 
    'Autoutilitară', 'Furgonetă', 'Trailer (Semiremorcă)', 'Remorcă', 'Autoturism',
    'Mașină de Intervenție', 'Platformă Auto', 'Basculantă', 'Cisternă', 'Frigorifică',
    'Container', 'Duba', 'Altele'
];

// Combinăm tipurile din DB cu cele predefinite și eliminăm duplicatele
$tipuri_vehicul_finale = array_unique(array_merge($tipuri_vehicul_db, $tipuri_vehicul_predefinite));
sort($tipuri_vehicul_finale); // Sortează alfabetic

// Statusuri vehicul
$statusuri_vehicul = ['Disponibil', 'În cursă', 'În service', 'Indisponibil'];

$conn->close(); // Închidem conexiunea după preluarea datelor
?>

<title>NTS TOUR | Editare Vehicul</title>

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
    }
    .card-header, .modal-header, .modal-footer {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .modal-content {
        background-color: #2a3042 !important;
        color: #e0e0e0 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
    }
    .modal-title {
        color: #ffffff !important;
    }
    .btn-close {
        filter: invert(1);
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
    hr {
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .btn-primary, .btn-secondary, .btn-danger, .btn-info, .btn-warning, .btn-success, .btn-outline-primary, .btn-outline-danger {
        border-radius: 0.5rem !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: bold !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
    }
    .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-info:hover, .btn-warning:hover, .btn-success:hover, .btn-outline-primary:hover, .btn-outline-danger:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
    }
    .alert {
        color: #ffffff !important;
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
    /* Stil specific pentru a ascunde săgețile de la input-ul de tip number */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
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
                    <li class="breadcrumb-item active" aria-current="page">Editare Vehicul</li>
                </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Editare Vehicul: <?php echo htmlspecialchars($vehicul['model']); ?> (<?php echo htmlspecialchars($vehicul['numar_inmatriculare']); ?>)</h4>
                        <hr>
                        <form class="row g-3" action="salveaza-vehicul.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($vehicul['id']); ?>">
                            <input type="hidden" name="existing_imagine_path" value="<?php echo htmlspecialchars($vehicul['imagine_path']); ?>">

                            <div class="col-md-6">
                                <label for="numarInmatriculare" class="form-label">Număr Înmatriculare:</label>
                                <input type="text" class="form-control" id="numarInmatriculare" name="numar_inmatriculare" value="<?php echo htmlspecialchars($vehicul['numar_inmatriculare']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="model" class="form-label">Model:</label>
                                <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($vehicul['model']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="tipVehicul" class="form-label">Tip Vehicul:</label>
                                <select id="tipVehicul" name="tip" class="form-select" required>
                                    <option value="">Alege...</option>
                                    <?php foreach ($tipuri_vehicul_finale as $tip) : ?>
                                        <option value="<?php echo htmlspecialchars($tip); ?>" <?php if ($vehicul['tip'] == $tip) echo 'selected'; ?>><?php echo htmlspecialchars($tip); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="anFabricatie" class="form-label">An Fabricație:</label>
                                <input type="number" class="form-control" id="anFabricatie" name="an_fabricatie" value="<?php echo htmlspecialchars($vehicul['an_fabricatie']); ?>" min="1900" max="<?php echo date('Y'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="kilometraj" class="form-label">Kilometraj Actual:</label>
                                <input type="number" class="form-control" id="kilometraj" name="kilometraj" value="<?php echo htmlspecialchars($vehicul['kilometraj']); ?>" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status:</label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php foreach ($statusuri_vehicul as $status) : ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" <?php if ($vehicul['status'] == $status) echo 'selected'; ?>><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                                <label for="costAchizitie" class="form-label">Cost Achiziție:</label>
                                <input type="number" step="0.01" class="form-control" id="costAchizitie" name="cost_achizitie" value="<?php echo htmlspecialchars($vehicul['cost_achizitie'] ?? ''); ?>" min="0" placeholder="Ex: 85000.00">
                            </div>
                            <div class="col-md-6">
                                <label for="kmRevizie" class="form-label">Revizie la (km):</label>
                                <input type="number" class="form-control" id="kmRevizie" name="km_revizie" value="<?php echo htmlspecialchars($vehicul['km_revizie'] ?? ''); ?>" min="0" placeholder="Ex: 50000">
                            </div>
                            <div class="col-12">
                                <label for="observatii" class="form-label">Observații Suplimentare:</label>
                                <textarea class="form-control" id="observatii" name="observatii" rows="3" placeholder="Detalii despre vehicul, dotări speciale, etc."><?php echo htmlspecialchars($vehicul['observatii'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label for="imagineVehicul" class="form-label">Încarcă imagine nouă (opțional)</label>
                                <input class="form-control" type="file" id="imagineVehicul" name="imagine" accept="image/png, image/jpeg, image/gif">
                                <?php if (!empty($vehicul['imagine_path'])) : ?>
                                    <small>Imaginea curentă: <a href="<?php echo htmlspecialchars($vehicul['imagine_path']); ?>" target="_blank">vezi imaginea</a></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <a href="vehicule.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div> </div>
</main>
<?php require_once 'template/footer.php'; ?>
