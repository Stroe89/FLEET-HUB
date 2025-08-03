<?php
session_start(); // ASIGURĂ-TE CĂ ACEASTA ESTE PRIMA LINIE DE COD PHP!
require_once 'db_connect.php'; // A doua linie
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mesaje de succes sau eroare din sesiune
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Preluăm lista de vehicule cu locațiile lor curente
$vehicule_locations = [];
$sql_vehicule_loc = "SELECT id, model, numar_inmatriculare, tip, locatie_curenta_lat, locatie_curenta_lng, ultima_actualizare_locatie FROM vehicule ORDER BY numar_inmatriculare ASC";
$result_vehicule_loc = $conn->query($sql_vehicule_loc);
if ($result_vehicule_loc) {
    while ($row = $result_vehicule_loc->fetch_assoc()) {
        $vehicule_locations[] = $row;
    }
}

// Preluăm tipurile de vehicule existente pentru dropdown-ul de adăugare
$tipuri_vehicul_db = [];
$sql_tipuri_db = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
$result_tipuri_db = $conn->query($sql_tipuri_db);
if ($result_tipuri_db) {
    while ($row = $result_tipuri_db->fetch_assoc()) {
        $tipuri_vehicul_db[] = $row['tip'];
    }
}

// Tipuri de vehicule predefinite din domeniul transporturilor
$tipuri_vehicul_predefined = [
    'Autocar', 'Microbuz', 'Minibus (8+1)', 'Camion (Rigid)', 'Camion (Articulat)', 
    'Autoutilitară', 'Furgonetă', 'Trailer (Semiremorcă)', 'Remorcă', 'Autoturism',
    'Mașină de Intervenție', 'Platformă Auto', 'Basculantă', 'Cisternă', 'Frigorifică',
    'Container', 'Duba', 'Altele', 'Autotren', 'Cap Tractor', 'Semiremorcă Frigorifică',
    'Semiremorcă Prelată', 'Semiremorcă Cisternă', 'Semiremorcă Basculantă', 'Autospecială',
    'Vehicul Electric', 'Vehicul Hibrid'
];

// Combinăm tipurile din DB cu cele predefinite și eliminăm duplicatele
$tipuri_vehicul_finale = array_unique(array_merge($tipuri_vehicul_db, $tipuri_vehicul_predefined));
sort($tipuri_vehicul_finale); // Sortează alfabetic

$conn->close(); // Închidem conexiunea la baza de date
?>

<?php require_once 'template/header.php'; ?>

<title>NTS TOUR | Harta Live</title>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIINfQPDQwrL5NLgyOXgOcqixgwa9HFADzoc="
     crossorigin=""/>

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

    /* Stiluri specifice hărții */
    #map {
        height: 600px; /* Înălțimea hărții */
        width: 100%;
        border-radius: 0.75rem;
        z-index: 1; /* Asigură că harta este sub modale */
        background-color: #3b435a; /* Fundal vizibil chiar dacă harta nu se încarcă */
    }
    .leaflet-control-attribution {
        color: #e0e0e0 !important;
        background-color: rgba(42, 48, 66, 0.7) !important;
    }
    .leaflet-control-zoom a {
        background-color: #3b435a !important;
        color: #ffffff !important;
        border-radius: 0.25rem !important;
    }
    .leaflet-popup-content-wrapper {
        background-color: #2a3042 !important;
        color: #e0e0e0 !important;
        border-radius: 0.5rem !important;
    }
    .leaflet-popup-tip {
        background-color: #2a3042 !important;
    }
    /* Stil pentru iconițele personalizate */
    .custom-div-icon {
        background-color: transparent;
        border: none;
    }
    .custom-div-icon i {
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5); /* Umbră pentru vizibilitate */
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
                        <li class="breadcrumb-item active" aria-current="page">Harta Live</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Harta Live a Flotei</h4>
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

                        <!-- Tab-uri pentru vizualizarea hărții și adăugare vehicul -->
                        <ul class="nav nav-tabs mb-3" id="mapTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="view-map-tab" data-bs-toggle="tab" data-bs-target="#viewMap" type="button" role="tab" aria-controls="viewMap" aria-selected="true">Vizualizare Hartă</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="add-vehicle-tab" data-bs-toggle="tab" data-bs-target="#addVehicle" type="button" role="tab" aria-controls="addVehicle" aria-selected="false">Adaugă Vehicul pe Hartă</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="mapTabsContent">
                            <!-- Tab: Vizualizare Hartă -->
                            <div class="tab-pane fade show active" id="viewMap" role="tabpanel" aria-labelledby="view-map-tab">
                                <div id="map"></div>
                            </div>

                            <!-- Tab: Adaugă Vehicul pe Hartă -->
                            <div class="tab-pane fade" id="addVehicle" role="tabpanel" aria-labelledby="add-vehicle-tab">
                                <div class="alert alert-info">
                                    Dă click pe hartă pentru a selecta locația noului vehicul. Coordonatele vor fi pre-populate în formular.
                                </div>
                                <form id="addVehicleLocationForm" action="salveaza-vehicul.php" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="locatie_curenta_lat" id="formLat">
                                    <input type="hidden" name="locatie_curenta_lng" id="formLng">
                                    <input type="hidden" name="ultima_actualizare_locatie" id="formLastUpdate">

                                    <h5 class="mb-3">Detalii Vehicul Nou</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="numarInmatriculare" class="form-label">Număr Înmatriculare:</label>
                                            <input type="text" class="form-control" id="numarInmatriculare" name="numar_inmatriculare" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="model" class="form-label">Model:</label>
                                            <input type="text" class="form-control" id="model" name="model" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tipVehicul" class="form-label">Tip Vehicul:</label>
                                            <select id="tipVehicul" name="tip" class="form-select" required>
                                                <option value="">Alege...</option>
                                                <?php foreach ($tipuri_vehicul_finale as $tip): ?>
                                                    <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="anFabricatie" class="form-label">An Fabricație:</label>
                                            <input type="number" class="form-control" id="anFabricatie" name="an_fabricatie" min="1900" max="<?php echo date('Y'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="kilometraj" class="form-label">Kilometraj Actual:</label>
                                            <input type="number" class="form-control" id="kilometraj" name="kilometraj" min="0" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Status:</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="Disponibil">Disponibil</option>
                                                <option value="În cursă">În cursă</option>
                                                <option value="În service">În service</option>
                                                <option value="Indisponibil">Indisponibil</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="latitudineDisplay" class="form-label">Latitudine:</label>
                                            <input type="text" class="form-control" id="latitudineDisplay" readonly placeholder="Click pe hartă pentru a selecta">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="longitudineDisplay" class="form-label">Longitudine:</label>
                                            <input type="text" class="form-control" id="longitudineDisplay" readonly placeholder="Click pe hartă pentru a selecta">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button type="reset" class="btn btn-secondary">Resetează Formular</button>
                                        <button type="submit" class="btn btn-primary">Adaugă Vehicul</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZQXlgWuT/zT7A0uT5i"
     crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM Content Loaded for harta-live.php");

    const mapElement = document.getElementById('map');
    const mapTabs = document.getElementById('mapTabs');
    const addVehicleLocationForm = document.getElementById('addVehicleLocationForm');
    const formLatInput = document.getElementById('formLat');
    const formLngInput = document.getElementById('formLng');
    const formLastUpdateInput = document.getElementById('formLastUpdate');
    const latitudeDisplay = document.getElementById('latitudineDisplay');
    const longitudeDisplay = document.getElementById('longitudineDisplay');

    let map = null;
    let vehicleMarkers = {}; // Obiect pentru a ține evidența markerelor vehiculelor
    let newVehicleMarker = null; // Marker temporar pentru adăugare vehicul

    // Locație implicită (București, România)
    const defaultLat = 44.4268;
    const defaultLng = 26.1025;
    const defaultZoom = 7;

    // Funcție pentru a obține iconița în funcție de tipul vehiculului
    function getVehicleIcon(vehicleType) {
        let iconClass = 'bxs-car'; // Default
        switch (vehicleType) {
            case 'Autocar': iconClass = 'bxs-bus'; break;
            case 'Microbuz': iconClass = 'bxs-bus-school'; break;
            case 'Camion (Rigid)':
            case 'Camion (Articulat)': iconClass = 'bxs-truck'; break;
            case 'Autoutilitară':
            case 'Furgonetă': iconClass = 'bxs-truck-alt'; break;
            case 'Trailer (Semiremorcă)':
            case 'Remorcă': iconClass = 'bxs-truck'; break;
            case 'Basculantă': iconClass = 'bxs-truck-loading'; break;
            case 'Cisternă': iconClass = 'bxs-gas-pump'; break;
            case 'Frigorifică': iconClass = 'bxs-thermometer'; break;
            case 'Autotren': iconClass = 'bxs-truck'; break;
            case 'Cap Tractor': iconClass = 'bxs-truck'; break;
            case 'Semiremorcă Frigorifică': iconClass = 'bxs-truck'; break; 
            case 'Semiremorcă Prelată': iconClass = 'bxs-truck'; break;
            case 'Semiremorcă Cisternă': iconClass = 'bxs-gas-pump'; break;
            case 'Semiremorcă Basculantă': iconClass = 'bxs-truck-loading'; break;
            case 'Autospecială': iconClass = 'bxs-ambulance'; break;
            case 'Vehicul Electric': iconClass = 'bxs-car-battery'; break;
            case 'Vehicul Hibrid': iconClass = 'bxs-car-mechanic'; break;
            case 'Mașină de Intervenție': iconClass = 'bxs-car-mechanic'; break;
            case 'Platformă Auto': iconClass = 'bxs-car-wash'; break;
            case 'Container': iconClass = 'bxs-package'; break;
            case 'Duba': iconClass = 'bxs-truck-alt'; break;
            default: iconClass = 'bxs-car'; break;
        }
        return L.divIcon({
            className: 'custom-div-icon',
            html: `<i class='bx ${iconClass}' style='font-size: 28px; color: #0d6efd;'></i>`, // Culoarea albastră
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -20]
        });
    }


    // Funcție pentru inițializarea hărții
    function initializeMap(lat, lng, zoom) {
        console.log("Initializing map with:", lat, lng, zoom);
        if (map) {
            map.remove(); // Elimină harta existentă dacă există
            console.log("Existing map removed.");
        }

        map = L.map('map').setView([lat, lng], zoom);
        console.log("New map instance created.");

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        console.log("Tile layer added.");

        // Adaugă markere pentru vehiculele existente
        addVehicleMarkers();
        console.log("Initial vehicle markers added.");

        // Activează funcționalitatea de click pe hartă pentru adăugare vehicul
        map.on('click', onMapClick);
        console.log("Map click listener attached.");

        // Reîmprospătează markerele la fiecare 30 de secunde
        setInterval(refreshVehicleMarkers, 30000); 
        console.log("Refresh interval set.");

        // map.invalidateSize(); // Aceasta va fi apelată la schimbarea tab-ului
        // console.log("map.invalidateSize() called during initialization.");
    }

    // Funcție pentru a adăuga markerele vehiculelor pe hartă
    function addVehicleMarkers() {
        console.log("Adding/refreshing vehicle markers.");
        // Curățăm markerele vechi
        for (const id in vehicleMarkers) {
            if (map.hasLayer(vehicleMarkers[id])) {
                map.removeLayer(vehicleMarkers[id]);
            }
        }
        vehicleMarkers = {};

        // Adăugăm markere noi
        const vehiculeLocations = <?php echo json_encode($vehicule_locations); ?>;
        vehiculeLocations.forEach(function(veh) {
            if (veh.locatie_curenta_lat && veh.locatie_curenta_lng) {
                const lat = parseFloat(veh.locatie_curenta_lat);
                const lng = parseFloat(veh.locatie_curenta_lng);
                const model = veh.model;
                const numar = veh.numar_inmatriculare;
                const tip = veh.tip;
                const ultimaActualizare = veh.ultima_actualizare_locatie;

                const marker = L.marker([lat, lng], { icon: getVehicleIcon(tip) }).addTo(map)
                    .bindPopup(`<b>${model} (${numar})</b><br>Tip: ${tip}<br>Ultima actualizare: ${ultimaActualizare ? new Date(ultimaActualizare).toLocaleString('ro-RO') : 'N/A'}`);
                
                vehicleMarkers[veh.id] = marker;
            }
        });
        console.log("Current vehicle markers:", vehicleMarkers);
    }

    // Funcție pentru a reîmprospăta locațiile vehiculelor (prin AJAX)
    function refreshVehicleMarkers() {
        console.log("Refreshing vehicle markers via AJAX.");
        fetch('process_harta_live.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=fetch_locations'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                console.log("Locations fetched successfully:", data.locations);
                // Actualizăm markerele existente sau adăugăm altele noi
                data.locations.forEach(veh => {
                    if (veh.locatie_curenta_lat && veh.locatie_curenta_lng) {
                        const lat = parseFloat(veh.locatie_curenta_lat);
                        const lng = parseFloat(veh.locatie_curenta_lng);
                        const model = veh.model;
                        const numar = veh.numar_inmatriculare;
                        const tip = veh.tip;
                        const ultimaActualizare = veh.ultima_actualizare_locatie;

                        if (vehicleMarkers[veh.id]) {
                            // Mută markerul existent
                            vehicleMarkers[veh.id].setLatLng([lat, lng]);
                            vehicleMarkers[veh.id].getPopup().setContent(`<b>${model} (${numar})</b><br>Tip: ${tip}<br>Ultima actualizare: ${ultimaActualizare ? new Date(ultimaActualizare).toLocaleString('ro-RO') : 'N/A'}`);
                        } else {
                            // Adaugă marker nou
                            const marker = L.marker([lat, lng], { icon: getVehicleIcon(tip) }).addTo(map)
                                .bindPopup(`<b>${model} (${numar})</b><br>Tip: ${tip}<br>Ultima actualizare: ${ultimaActualizare ? new Date(ultimaActualizare).toLocaleString('ro-RO') : 'N/A'}`);
                            vehicleMarkers[veh.id] = marker;
                        }
                    } else {
                        // Dacă vehiculul nu mai are locație, șterge markerul
                        if (vehicleMarkers[veh.id]) {
                            map.removeLayer(vehicleMarkers[veh.id]);
                            delete vehicleMarkers[veh.id];
                        }
                    }
                });
                // Elimină markerele vehiculelor care nu mai sunt în data.locations
                for (const id in vehicleMarkers) {
                    if (!data.locations.some(veh => veh.id == id)) {
                        map.removeLayer(vehicleMarkers[id]);
                        delete vehicleMarkers[id];
                    }
                    // Adaugă o verificare suplimentară pentru a curăța markerele temporare
                    if (newVehicleMarker && map.hasLayer(newVehicleMarker)) {
                        map.removeLayer(newVehicleMarker);
                        newVehicleMarker = null;
                    }
                }

            } else {
                console.error('Eroare la reîmprospătarea locațiilor:', data.message);
            }
        })
        .catch(error => {
            console.error('Eroare la fetch reîmprospătare locații:', error);
        });
    }

    // Funcție pentru a obține locația utilizatorului
    function getUserLocation() {
        console.log("Attempting to get user location.");
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                console.log("User location obtained:", lat, lng);
                initializeMap(lat, lng, 13); // Zoom mai mare pentru locația utilizatorului
                L.marker([lat, lng]).addTo(map)
                    .bindPopup('Locația ta curentă').openPopup();
            }, function(error) {
                console.warn('Eroare la obținerea locației (Geolocation):', error.message);
                initializeMap(defaultLat, defaultLng, defaultZoom); // Folosește locația implicită (București)
            });
        } else {
            console.warn('Geolocation nu este suportată de browserul tău.');
            initializeMap(defaultLat, defaultLng, defaultZoom); // Folosește locația implicită (București)
        }
    }

    // Funcție la click pe hartă (pentru tab-ul "Adaugă Vehicul")
    function onMapClick(e) {
        console.log("Map clicked at:", e.latlng.lat, e.latlng.lng);
        const addVehicleTabPane = document.getElementById('addVehicle');
        
        // Verificăm dacă tab-ul "Adaugă Vehicul" este activ
        if (addVehicleTabPane && addVehicleTabPane.classList.contains('show')) {
            const lat = e.latlng.lat.toFixed(8);
            const lng = e.latlng.lng.toFixed(8);

            formLatInput.value = lat;
            formLngInput.value = lng;
            latitudeDisplay.value = lat;
            longitudeDisplay.value = lng;
            formLastUpdateInput.value = new Date().toISOString().slice(0, 19).replace('T', ' '); // Format MySQL DATETIME

            // Curățăm markerul vechi dacă există
            if (newVehicleMarker) {
                map.removeLayer(newVehicleMarker);
                console.log("Previous new vehicle marker removed.");
            }
            // Adăugăm un marker nou la locația click-ului
            newVehicleMarker = L.marker([lat, lng], { icon: getVehicleIcon('Autoturism') }).addTo(map) // Folosim o iconiță generică
                .bindPopup(`Locație selectată: ${lat}, ${lng}`).openPopup();
            console.log("New vehicle marker added at:", lat, lng);
        } else {
            console.log("Map click ignored: 'Adaugă Vehicul' tab is not active.");
        }
    }

    // Inițializăm harta la încărcarea paginii
    // Aceasta este deja apelată de getUserLocation()
    // getUserLocation(); 


    // Logica pentru schimbarea tab-urilor
    const mapTabsNav = document.getElementById('mapTabs'); // Referința la elementul nav-tabs
    if (mapTabsNav) {
        mapTabsNav.addEventListener('shown.bs.tab', function (event) {
            console.log("Tab changed to:", event.target.id);
            // Invalidează dimensiunea hărții pentru a se re-renderiza corect în noul tab
            if (map) {
                map.invalidateSize();
                console.log("map.invalidateSize() called on tab change.");
            }
            // Dacă trecem la tab-ul de vizualizare hartă, curățăm markerul de adăugare
            if (event.target.id === 'view-map-tab') {
                if (newVehicleMarker) {
                    map.removeLayer(newVehicleMarker);
                    newVehicleMarker = null;
                    console.log("New vehicle marker removed as 'View Map' tab is active.");
                }
            }
        });
    }

    // Resetarea formularului de adăugare vehicul pe hartă
    const addVehicleLocationFormResetBtn = addVehicleLocationForm.querySelector('button[type="reset"]');
    if (addVehicleLocationFormResetBtn) {
        addVehicleLocationFormResetBtn.addEventListener('click', function() {
            console.log("Resetting add vehicle form.");
            if (newVehicleMarker) {
                map.removeLayer(newVehicleMarker);
                newVehicleMarker = null;
            }
            latitudeDisplay.value = '';
            longitudeDisplay.value = '';
            formLastUpdateInput.value = '';
            console.log("Form reset and marker cleared.");
        });
    }
});
</script>
