<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie

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

// Acum includem header-ul.
// Asigură-te că db_connect.php este inclus și conexiunea $conn este deschisă înainte de header.php
require_once 'template/header.php';
?>

<title>NTS TOUR | Adaugă Vehicul Nou</title>

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
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Adaugă Vehicul</div>
            <div class="ps-3">
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Formular Adăugare Vehicul</h4>
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
                            <input type="hidden" name="action" value="add">
                            
                            <h5 class="mb-3">Secțiune 1: Detalii Principale</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="numarInmatriculare" class="form-label">Număr Înmatriculare:</label>
                                    <input type="text" class="form-control" id="numarInmatriculare" name="numar_inmatriculare" placeholder="Ex: B 01 ABC" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="model" class="form-label">Model:</label>
                                    <input type="text" class="form-control" id="model" name="model" placeholder="Ex: Mercedes-Benz Actros" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipVehicul" class="form-label">Tip Vehicul:</label>
                                    <select id="tipVehicul" name="tip" class="form-select" required>
                                        <option value="">Alege...</option>
                                        <?php foreach ($tipuri_vehicul_finale as $tip_opt): ?>
                                            <option value="<?php echo htmlspecialchars($tip_opt); ?>"><?php echo htmlspecialchars($tip_opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="anFabricatie" class="form-label">An Fabricație:</label>
                                    <input type="number" class="form-control" id="anFabricatie" name="an_fabricatie" min="1900" max="<?php echo date('Y'); ?>" placeholder="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="kilometraj" class="form-label">Kilometraj Actual:</label>
                                    <input type="number" class="form-control" id="kilometraj" name="kilometraj" min="0" placeholder="0" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status:</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <?php foreach ($statusuri_vehicul as $status_opt): ?>
                                            <option value="<?php echo htmlspecialchars($status_opt); ?>"><?php echo htmlspecialchars($status_opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <h5 class="mb-3 mt-4">Secțiune 2: Detalii Tehnice și Achiziție (Opțional)</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="serieSasiu" class="form-label">Serie Șasiu (VIN):</label>
                                    <input type="text" class="form-control" id="serieSasiu" name="serie_sasiu" placeholder="Ex: WDB123...">
                                </div>
                                <div class="col-md-6">
                                    <label for="culoare" class="form-label">Culoare:</label>
                                    <input type="text" class="form-control" id="culoare" name="culoare" placeholder="Ex: Albastru">
                                </div>
                                <div class="col-md-6">
                                    <label for="capacitateCilindrica" class="form-label">Capacitate Cilindrică (cm³):</label>
                                    <input type="number" class="form-control" id="capacitateCilindrica" name="capacitate_cilindrica" min="0" placeholder="Ex: 12800">
                                </div>
                                <div class="col-md-6">
                                    <label for="putereCp" class="form-label">Putere (CP):</label>
                                    <input type="number" class="form-control" id="putereCp" name="putere_cp" min="0" placeholder="Ex: 450">
                                </div>
                                <div class="col-md-6">
                                    <label for="consumMediu" class="form-label">Consum Mediu (l/100km):</label>
                                    <input type="number" step="0.01" class="form-control" id="consumMediu" name="consum_mediu" min="0" placeholder="Ex: 28.5">
                                </div>
                                <div class="col-md-6">
                                    <label for="masaMaximaAutorizata" class="form-label">Masă Maximă Autorizată (kg):</label>
                                    <input type="number" class="form-control" id="masaMaximaAutorizata" name="masa_maxima_autorizata" min="0" placeholder="Ex: 40000">
                                </div>
                                <div class="col-md-6">
                                    <label for="sarcinaUtila" class="form-label">Sarcină Utilă (kg):</label>
                                    <input type="number" class="form-control" id="sarcinaUtila" name="sarcina_utila" min="0" placeholder="Ex: 25000">
                                </div>
                                <div class="col-md-6">
                                    <label for="numarLocuri" class="form-label">Număr Locuri (inclusiv șofer):</label>
                                    <input type="number" class="form-control" id="numarLocuri" name="numar_locuri" min="1" placeholder="Ex: 50">
                                </div>
                                <div class="col-md-6">
                                    <label for="dataAchizitie" class="form-label">Dată Achiziție:</label>
                                    <input type="date" class="form-control" id="dataAchizitie" name="data_achizitie">
                                </div>
                                <div class="col-md-6">
                                    <label for="costAchizitie" class="form-label">Cost Achiziție (RON):</label>
                                    <input type="number" step="0.01" class="form-control" id="costAchizitie" name="cost_achizitie" min="0" placeholder="Ex: 85000.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="kmRevizie" class="form-label">Revizie la (km):</label>
                                    <input type="number" class="form-control" id="kmRevizie" name="km_revizie" min="0" placeholder="Ex: 50000">
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
                                        <img src="https://placehold.co/300x200/2a3042/e0e0e0?text=Previzualizare+imagine" id="imagePreview" alt="Previzualizare imagine" class="img-fluid mt-3" style="display: none;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="vehicule.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary">Adaugă Vehicul</button>
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
            // Pentru adauga-vehicul.php, dacă nu e selectată o imagine, pur și simplu ascundem previzualizarea
            imagePreview.style.display = 'none';
            imagePreview.src = "https://placehold.co/300x200/2a3042/e0e0e0?text=Fara+imagine"; // Reset la placeholder
        }
    }

    if (imagineVehiculInput) {
        imagineVehiculInput.addEventListener('change', updateImagePreview);
        // Nu apelăm updateImagePreview() la încărcarea paginii pentru adauga-vehicul.php
        // deoarece nu există o imagine existentă inițial.
    }
});
</script>
