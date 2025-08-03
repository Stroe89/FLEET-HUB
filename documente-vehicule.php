<?php
session_start(); // Porneste sesiunea pentru mesaje
require_once 'db_connect.php'; // Conexiunea la baza de date
require_once 'template/header.php'; // Header-ul paginii

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$id_vehicul = $_GET['id'] ?? null;
$vehicul_info = null;
$documente_vehicul = [];
$grouped_documents = []; // Asigurăm că este inițializat
$display_vehicle_documents = false; // Flag pentru a decide ce conținut să afișăm

error_log("DOC_VEHICULE.PHP: Pagina accesată. ID Vehicul din URL: " . ($id_vehicul ?? 'NULL'));

// --- Logica principală: Afișare Documente pentru un Vehicul Specific ---
if ($id_vehicul && is_numeric($id_vehicul)) {
    error_log("DOC_VEHICULE.PHP: ID Vehicul valid: " . $id_vehicul);
    // Preluam informatiile vehiculului, inclusiv tipul
    $stmt_vehicul = $conn->prepare("SELECT numar_inmatriculare, model, tip FROM vehicule WHERE id = ?");
    if ($stmt_vehicul === false) {
        error_log("DOC_VEHICULE.PHP: Eroare la pregătirea interogării vehiculului: " . $conn->error);
        die("Eroare la pregătirea interogării vehiculului: " . $conn->error);
    }
    $stmt_vehicul->bind_param("i", $id_vehicul);
    $stmt_vehicul->execute();
    $result_vehicul = $stmt_vehicul->get_result();
    if ($result_vehicul->num_rows > 0) {
        $vehicul_info = $result_vehicul->fetch_assoc();
        error_log("DOC_VEHICULE.PHP: Informații vehicul preluate: " . print_r($vehicul_info, true));
    } else {
        error_log("DOC_VEHICULE.PHP: Vehiculul cu ID " . $id_vehicul . " nu a fost găsit.");
    }
    $stmt_vehicul->close();

    if ($vehicul_info) {
        // Preluam documentele pentru vehiculul specificat, inclusiv coloana 'important' și 'nume_document_user'
        // Asigură-te că toate coloanele sunt selectate corect
        $stmt_docs = $conn->prepare("SELECT id, tip_document, data_expirare, cale_fisier, nume_original_fisier, nume_document_user, important FROM documente WHERE id_vehicul = ? ORDER BY tip_document ASC, data_expirare ASC");
        if ($stmt_docs === false) {
            error_log("DOC_VEHICULE.PHP: Eroare la pregătirea interogării documentelor: " . $conn->error);
            die("Eroare la pregătirea interogării documentelor: " . $conn->error);
        }
        $stmt_docs->bind_param("i", $id_vehicul);
        $stmt_docs->execute();
        $result_docs = $stmt_docs->get_result();
        
        // Grupăm documentele pe categorii (tip_document)
        while ($row = $result_docs->fetch_assoc()) {
            $grouped_documents[$row['tip_document']][] = $row;
        }
        $stmt_docs->close();
        $display_vehicle_documents = true; // Setăm flag-ul pentru a afișa documentele vehiculului
        error_log("DOC_VEHICULE.PHP: Documente preluate și grupate. Număr categorii: " . count($grouped_documents));
        error_log("DOC_VEHICULE.PHP: Conținut grouped_documents: " . print_r($grouped_documents, true));

    } else {
        $error_message = "Vehiculul nu a fost găsit.";
        error_log("DOC_VEHICULE.PHP: Vehiculul nu a fost găsit pentru afișarea documentelor.");
    }
} else {
    error_log("DOC_VEHICULE.PHP: Nu s-a specificat un ID de vehicul valid. Afișăm lista tuturor vehiculelor.");
}
// --- Sfârșit Logica Principală ---

// --- Logica pentru Afișarea Listei de Vehicule (dacă nu s-a specificat un ID) ---
$all_vehicles = [];
if (!$display_vehicle_documents) {
    // Am adaugat 'imagine_path' la select pentru a o afisa in carduri
    $stmt_all_vehicles = $conn->prepare("SELECT id, numar_inmatriculare, model, tip, imagine_path FROM vehicule ORDER BY model ASC"); 
    if ($stmt_all_vehicles === false) { 
        error_log("DOC_VEHICULE.PHP: Eroare la pregătirea interogării pentru toate vehiculele: " . $conn->error);
        die("Eroare la pregătirea interogării pentru toate vehiculele: " . $conn->error);
    }
    if ($stmt_all_vehicles->execute()) { 
        $result_all_vehicles = $stmt_all_vehicles->get_result();
        while ($row = $result_all_vehicles->fetch_assoc()) {
            $all_vehicles[] = $row;
        }
        $stmt_all_vehicles->close();
        error_log("DOC_VEHICULE.PHP: Număr total vehicule pentru selecție: " . count($all_vehicles));
    } else {
        error_log("DOC_VEHICULE.PHP: Eroare la executarea interogării pentru toate vehiculele: " . $stmt_all_vehicles->error);
    }
}

// Preluăm tipurile de vehicule existente din baza de date pentru dropdown-ul de filtrare
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
    'Container', 'Duba', 'Altele'
];

// Combinăm tipurile din DB cu cele predefinite și eliminăm duplicatele
$tipuri_vehicul_for_filter = array_unique(array_merge($tipuri_vehicul_db, $tipuri_vehicul_predefined));
sort($tipuri_vehicul_for_filter); // Sortează alfabetic

$conn->close(); // Închidem conexiunea la baza de date aici, după toate operațiile.

?>

<title>NTS TOUR | Documente Vehicul</title>

<style>
    /* Stiluri specifice pentru cardul de document (din imaginea model) */
    .document-card {
        border-radius: 0.75rem; /* Colțuri mai rotunjite */
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.3); /* Umbră mai pronunțată și mai premium */
        margin-bottom: 1.5rem;
        background-color: #2a3042; /* Fundal card */
        color: #e0e0e0;
        border: 1px solid transparent; /* Bordura implicită, transparentă */
        transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Tranziție pentru bordură și umbră */
    }
    /* Stil pentru documentele importante */
    .document-card.important {
        border: 2px solid #dc3545 !important; /* Contur roșu pentru documentele importante */
        box-shadow: 0 0 15px rgba(220, 53, 69, 0.7) !important; /* Umbră roșie mai intensă */
    }

    .document-header {
        background-color: #3b435a; /* Culoare de fundal pentru header */
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        border-top-left-radius: 0.75rem; /* Colțuri rotunjite */
        border-top-right-radius: 0.75rem; /* Colțuri rotunjite */
        color: #ffffff; /* Text alb în header */
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .document-body {
        padding: 1.5rem;
    }
    .document-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background-color: #3b435a; /* Culoare de fundal pentru footer */
        border-bottom-left-radius: 0.75rem; /* Colțuri rotunjite */
        border-bottom-right-radius: 0.75rem; /* Colțuri rotunjite */
        display: flex;
        justify-content: flex-end; /* Aliniază butoanele la dreapta */
        gap: 0.75rem; /* Spațiu mai mare între butoane */
    }
    .status-valid { color: #8bc34a; font-weight: bold; } /* Verde mai deschis pentru Valabil */
    .status-expired { color: #ef5350; font-weight: bold; } /* Roșu mai deschis pentru Expirat */
    .document-info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem; /* Spațiu mai mare între rânduri */
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.15); /* Linie punctată mai vizibilă */
    }
    .document-info-row:last-of-type {
        border-bottom: none; /* Fără linie pentru ultimul rând */
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .document-info-row span:first-child {
        font-weight: 500;
        color: #b0b0b0; /* Culoare mai deschisă pentru etichete */
    }
    .document-info-row strong {
        color: #ffffff; /* Valori albe */
    }
    .document-file-link {
        display: flex;
        align-items: center;
        gap: 0.75rem; /* Spațiu mai mare */
        margin-top: 1.5rem; /* Spațiu deasupra link-ului de fișier */
        margin-bottom: 1.5rem;
        font-size: 1.15rem; /* Font puțin mai mare */
        font-weight: 500;
        color: #4285f4; /* Culoare link Google Blue */
        text-decoration: none; /* Fără subliniere implicită */
        transition: color 0.2s ease;
    }
    .document-file-link:hover {
        color: #6a90f1; /* Culoare la hover */
    }
    .document-file-link i {
        font-size: 1.8rem; /* Icoană mai mare */
        color: #4285f4;
    }
    /* Butoane de acțiune în footer-ul documentului */
    .document-footer .btn {
        border-radius: 0.5rem;
        padding: 0.6rem 1.2rem; /* Padding mai generos */
        font-weight: 500;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .document-footer .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }


    /* Stiluri pentru butoanele de acțiune pe mobil */
    @media (max-width: 767.98px) {
        .document-footer {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem; /* Spațiu între butoane stivuite */
        }
        .document-footer .btn {
            width: 100%;
            margin-bottom: 0; /* Elimină marginile de jos dacă sunt stivuite */
        }
    }
    /* Stiluri pentru lista de vehicule în documente-vehicule.php (secțiunea de selecție) */
    .vehicle-select-card-col {
        /* Asigură că fiecare coloană ocupă spațiul corect */
    }
    .vehicle-select-card {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
        height: 100%; /* Asigură înălțime consistentă */
        display: flex;
        flex-direction: column;
        align-items: center; /* Centrează conținutul */
        justify-content: center; /* Centrează conținutul pe verticală */
        padding: 1.5rem; /* Padding intern generos */
        text-align: center;
    }
    .vehicle-select-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
        cursor: pointer;
    }
    .vehicle-select-card .vehicle-icon-container {
        font-size: 4rem; /* Icoană foarte mare */
        color: #6a90f1; /* Culoare de accent */
        margin-bottom: 1rem;
        background-color: rgba(255, 255, 255, 0.08); /* Fundal subtil pentru icoană */
        border-radius: 50%; /* Formă rotundă */
        padding: 0.75rem; /* Spațiu în jurul icoanei */
        display: inline-flex; /* Pentru a centra padding-ul */
        align-items: center;
        justify-content: center;
        width: 100px; /* Dimensiune fixă pentru containerul icoanei */
        height: 100px;
    }
    .vehicle-select-card .card-title {
        font-size: 1.4rem; /* Dimensiune mai mare pentru numărul de înmatriculare */
        margin-bottom: 0.5rem;
        color: #ffffff;
        font-weight: bold;
    }
    .vehicle-select-card .card-text {
        font-size: 0.95rem;
        color: #b0b0b0;
        margin-bottom: 1rem;
    }
    .vehicle-select-card .btn {
        margin-top: auto; /* Împinge butonul la bază */
        width: 80%; /* Buton mai lat */
        max-width: 200px;
        padding: 0.75rem 1rem;
    }
    /* Ascunde imaginea reală a vehiculului în acest layout de card */
    .vehicle-select-card-img {
        display: none; 
    }
    /* Stiluri pentru bara de căutare a vehiculelor */
    .vehicle-search-input-group .input-group-text {
        background-color: #34495e;
        border-color: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        border-radius: 0.5rem 0 0 0.5rem;
    }
    .vehicle-search-input-group .form-control {
        background-color: #1a2035;
        color: #e0e0e0;
        border-color: rgba(255, 255, 255, 0.2);
        border-radius: 0 0.5rem 0.5rem 0;
    }
    .vehicle-search-input-group .form-control::placeholder {
        color: #b0b0b0; /* Culoare placeholder */
        opacity: 0.7;
    }

    /* Stiluri pentru Acordeon */
    .accordion-item {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 0.75rem; /* Spațiu mai mare între item-uri */
        border-radius: 0.75rem !important; /* Colțuri mai rotunjite */
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
    }
    .accordion-header {
        background-color: #3b435a;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem 0.75rem 0 0; /* Colțuri rotunjite */
    }
    .accordion-button {
        background-color: #3b435a;
        color: #ffffff;
        font-weight: bold;
        padding: 1rem 1.5rem;
        border: none;
        border-radius: 0.75rem !important; /* Colțuri rotunjite */
        transition: background-color 0.3s ease;
    }
    .accordion-button:not(.collapsed) {
        background-color: rgba(255, 255, 255, 0.1); /* Culoare diferită când este deschis */
        box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.1);
    }
    .accordion-button:focus {
        box-shadow: 0 0 0 0.25rem rgba(106, 144, 241, 0.25);
        border-color: #6a90f1;
    }
    .accordion-body {
        padding: 1.5rem;
        background-color: #2a3042;
        color: #e0e0e0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        border-bottom-left-radius: 0.75rem; /* Colțuri rotunjite */
        border-bottom-right-radius: 0.75rem; /* Colțuri rotunjite */
    }
    .accordion-item:last-of-type .accordion-collapse {
        border-bottom-right-radius: 0.75rem !important;
        border-bottom-left-radius: 0.75rem !important;
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Documente Vehicul</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php if ($display_vehicle_documents): // Afisam documentele vehiculului specific ?>
                            <h4 class="card-title">Documente Vehicul: <?php echo htmlspecialchars($vehicul_info['model'] . ' (' . $vehicul_info['numar_inmatriculare'] . ')'); ?></h4>
                            <p class="text-muted mb-3">Tip Vehicul: <strong><?php echo htmlspecialchars($vehicul_info['tip'] ?? 'N/A'); ?></strong></p>
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

                            <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                                <i class="bx bx-plus"></i> Adaugă Document Nou
                            </button>

                            <?php if (empty($grouped_documents)): ?>
                                <div class="alert alert-info">Nu există documente înregistrate pentru acest vehicul.</div>
                            <?php else: ?>
                                <div class="accordion" id="documentAccordion">
                                    <?php $accordion_item_id = 0; ?>
                                    <?php foreach ($grouped_documents as $tip_document => $docs_in_category): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $accordion_item_id; ?>">
                                                <button class="accordion-button <?php echo ($accordion_item_id == 0) ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $accordion_item_id; ?>" aria-expanded="<?php echo ($accordion_item_id == 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $accordion_item_id; ?>">
                                                    <?php echo htmlspecialchars($tip_document); ?> (<?php echo count($docs_in_category); ?>)
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $accordion_item_id; ?>" class="accordion-collapse collapse <?php echo ($accordion_item_id == 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $accordion_item_id; ?>" data-bs-parent="#documentAccordion">
                                                <div class="accordion-body">
                                                    <?php foreach ($docs_in_category as $doc):
                                                        $data_expirare_dt = new DateTime($doc['data_expirare']);
                                                        $today = new DateTime();
                                                        $status = ($data_expirare_dt >= $today) ? 'Valabil' : 'Expirat';
                                                        $status_class = ($status == 'Valabil') ? 'status-valid' : 'status-expired';
                                                        $important_class = ($doc['important'] == 1) ? ' important' : '';
                                                    ?>
                                                        <div class="card document-card mb-3<?php echo $important_class; ?>">
                                                            <div class="card-body">
                                                                <div class="document-info-row">
                                                                    <span>Nume Document:</span>
                                                                    <strong><?php echo htmlspecialchars($doc['nume_document_user']); ?></strong>
                                                                </div>
                                                                <div class="document-info-row">
                                                                    <span>Data Expirării:</span>
                                                                    <strong><?php echo htmlspecialchars($data_expirare_dt->format('d F Y')); ?></strong>
                                                                </div>
                                                                <div class="document-info-row">
                                                                    <span>Status:</span>
                                                                    <strong class="<?php echo $status_class; ?>"><?php echo $status; ?></strong>
                                                                </div>
                                                                <?php 
                                                                // Verifică dacă fișierul există și are o cale validă
                                                                $has_file = !empty($doc['cale_fisier']) && file_exists($doc['cale_fisier']);
                                                                $file_extension = $has_file ? strtolower(pathinfo($doc['nume_original_fisier'], PATHINFO_EXTENSION)) : '';
                                                                $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif']);
                                                                $is_pdf = ($file_extension == 'pdf');
                                                                ?>
                                                                <div class="document-preview-container mb-3">
                                                                    <?php if ($is_pdf && $has_file): ?>
                                                                        <iframe src="<?php echo htmlspecialchars($doc['cale_fisier']); ?>" width="100%" height="300px" style="border: none;"></iframe>
                                                                    <?php elseif ($is_image && $has_file): ?>
                                                                        <img src="<?php echo htmlspecialchars($doc['cale_fisier']); ?>" alt="Previzualizare Document" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
                                                                    <?php else: ?>
                                                                        <div class="alert alert-info text-center">
                                                                            <i class="bx bx-file" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                                                            Previzualizare indisponibilă.
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <?php if ($has_file): ?>
                                                                    <a href="download_document.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-info w-100 mb-2">
                                                                        <i class="bx bx-download"></i> Descărcă Document
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-secondary w-100 mb-2 print-document-btn" data-file-url="<?php echo htmlspecialchars($doc['cale_fisier']); ?>" data-file-type="<?php echo htmlspecialchars($file_extension); ?>">
                                                                        <i class="bx bx-printer"></i> Printează Document
                                                                    </button>
                                                                <?php endif; ?>
                                                                
                                                                <div class="document-footer justify-content-end w-100" style="border-top: none; padding-top: 0;">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1 me-1" data-bs-toggle="modal" data-bs-target="#editDocumentModal" data-id="<?php echo $doc['id']; ?>" data-tip="<?php echo htmlspecialchars($doc['tip_document']); ?>" data-expirare="<?php echo htmlspecialchars($doc['data_expirare']); ?>" data-important="<?php echo htmlspecialchars($doc['important']); ?>" data-nume-document-user="<?php echo htmlspecialchars($doc['nume_document_user']); ?>">Editează</button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger flex-grow-1 ms-1" data-bs-toggle="modal" data-bs-target="#deleteDocumentModal" data-id="<?php echo $doc['id']; ?>" data-tip="<?php echo htmlspecialchars($doc['tip_document']); ?>">Șterge</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $accordion_item_id++; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        <?php else: // Afisam lista de vehicule pentru selectie ?>
                            <h4 class="card-title">Selectează un Vehicul pentru a Vizualiza Documentele</h4>
                            <hr>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($all_vehicles)): ?>
                                <p>Te rog să alegi un vehicul din lista de mai jos pentru a-i gestiona documentele:</p>
                                
                                <!-- Bara de căutare pentru vehicule -->
                                <div class="input-group mb-3 vehicle-search-input-group">
                                    <span class="input-group-text"><i class="bx bx-search" style="font-size: 1.2rem; color: #ffffff;"></i></span>
                                    <input type="text" class="form-control" id="vehicleSearchInput" placeholder="Caută vehicul după model sau număr...">
                                </div>
                                <!-- Filtru Tip Vehicul pentru lista de selecție -->
                                <div class="mb-3">
                                    <label for="vehicleTypeFilter" class="form-label">Filtrează după Tip Vehicul:</label>
                                    <select class="form-select" id="vehicleTypeFilter">
                                        <option value="all">Toate Tipurile</option>
                                        <?php foreach ($tipuri_vehicul_for_filter as $tip): ?>
                                            <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="vehicleListContainer">
                                    <?php foreach ($all_vehicles as $vehicle): 
                                        // Logica pentru icoane pe baza tipului de vehicul
                                        $icon_class = 'bx bx-car'; 
                                        if (strpos(strtolower($vehicle['tip'] ?? ''), 'autocar') !== false) $icon_class = 'bx bxs-bus';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'microbuz') !== false) $icon_class = 'bx bxs-bus-school';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'camion') !== false) $icon_class = 'bx bxs-truck';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'autoutilitară') !== false) $icon_class = 'bx bxs-truck-alt';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'furgonetă') !== false) $icon_class = 'bx bxs-truck-alt';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'trailer') !== false) $icon_class = 'bx bxs-truck';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'remorcă') !== false) $icon_class = 'bx bxs-truck';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'mașină mică') !== false) $icon_class = 'bx bxs-car';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'mașină de intervenție') !== false) $icon_class = 'bx bxs-ambulance';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'platformă auto') !== false) $icon_class = 'bx bxs-car-wash';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'basculantă') !== false) $icon_class = 'bx bxs-truck-loading';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'cisternă') !== false) $icon_class = 'bx bxs-gas-pump';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'frigorifică') !== false) $icon_class = 'bx bxs-thermometer';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'container') !== false) $icon_class = 'bx bxs-package';
                                        else if (strpos(strtolower($vehicle['tip'] ?? ''), 'duba') !== false) $icon_class = 'bx bxs-truck-alt';
                                        else $icon_class = 'bx bx-car'; // Fallback
                                    ?>
                                        <div class="col vehicle-select-card-col" data-search="<?php echo strtolower(htmlspecialchars($vehicle['model'] . ' ' . $vehicle['numar_inmatriculare'] . ' ' . ($vehicle['tip'] ?? ''))); ?>" data-tip-vehicul="<?php echo htmlspecialchars($vehicle['tip'] ?? ''); ?>">
                                            <div class="card h-100 vehicle-select-card">
                                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                                                    <div class="vehicle-icon-container">
                                                        <i class="<?php echo $icon_class; ?>"></i>
                                                    </div>
                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($vehicle['numar_inmatriculare']); ?></h5>
                                                    <p class="card-text text-muted mb-1"><?php echo htmlspecialchars($vehicle['model']); ?></p>
                                                    <p class="card-text text-secondary mb-3">Tip: <strong><?php echo htmlspecialchars($vehicle['tip'] ?? 'N/A'); ?></strong></p>
                                                    <a href="documente-vehicule.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-primary mt-auto">Vezi Documente</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div id="noVehiclesFound" class="alert alert-info mt-3" style="display: none;">Nu au fost găsite vehicule care să corespundă căutării.</div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">Nu există vehicule înregistrate în sistem. <a href="adauga-vehicul.php" class="alert-link">Adaugă un vehicul nou.</a></div>
                            <?php endif; ?>
                            <a href="vehicule.php" class="btn btn-secondary mt-3">Înapoi la Lista de Vehicule</a>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal pentru Adaugă Document Nou (doar dacă afișăm documente specifice) -->
<?php if ($display_vehicle_documents): ?>
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDocumentModalLabel">Adaugă Document Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDocumentForm" action="process_document.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id_vehicul" value="<?php echo htmlspecialchars($id_vehicul); ?>">
                    
                    <div class="mb-3">
                        <label for="addDocName" class="form-label">Nume Document:</label>
                        <input type="text" class="form-control" id="addDocName" name="nume_document_user" placeholder="Ex: ITP 2025, Asigurare RCA" required>
                    </div>
                    <div class="mb-3">
                        <label for="addDocType" class="form-label">Tip Document:</label>
                        <select class="form-select" id="addDocType" name="tip_document" required>
                            <option value="">Selectează tipul</option>
                            <option value="ITP">ITP</option>
                            <option value="RCA">RCA</option>
                            <option value="Rovinieta">Rovinieta</option>
                            <option value="Asigurare Casco">Asigurare Casco</option>
                            <option value="Licenta Transport">Licență Transport</option>
                            <option value="Altele">Altele</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addDocExpirationDate" class="form-label">Data Expirării:</label>
                        <input type="date" class="form-control" id="addDocExpirationDate" name="data_expirare" required>
                    </div>
                    <div class="mb-3">
                        <label for="addDocFile" class="form-label">Încarcă Fișier (PDF, JPG, PNG, max 5MB):</label>
                        <input type="file" class="form-control" id="addDocFile" name="document_file" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="addDocImportant" name="important">
                        <label class="form-check-label" for="addDocImportant">
                            Marchează ca important
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru Editează Data Expirării Document (doar dacă afișăm documente specifice) -->
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDocumentModalLabel">Editează Document: <span id="editDocTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDocumentForm" action="process_document.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="document_id" id="editDocId">
                    <input type="hidden" name="id_vehicul" value="<?php echo htmlspecialchars($id_vehicul); ?>">

                    <div class="mb-3">
                        <label for="editDocName" class="form-label">Nume Document:</label>
                        <input type="text" class="form-control" id="editDocName" name="nume_document_user" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDocExpirationDate" class="form-label">Data Expirării:</label>
                        <input type="date" class="form-control" id="editDocExpirationDate" name="data_expirare" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="editDocImportant" name="important">
                        <label class="form-check-label" for="editDocImportant">
                            Marchează ca important
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru Șterge Document (doar dacă afișăm documente specifice) -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDocumentModalLabel">Confirmă Ștergerea Documentului</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi documentul <strong id="deleteDocTitle"></strong>? Această acțiune nu poate fi anulată.
                <input type="hidden" name="document_id" id="deleteDocId">
                <input type="hidden" name="id_vehicul" value="<?php echo htmlspecialchars($id_vehicul); ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteDocumentBtn">Șterge</button>
            </div>
        </div>
    </div>
</div>
<?php endif; // Sfârșit if ($display_vehicle_documents) ?>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logica pentru filtrarea vehiculelor pe pagina de selectie documente
    const vehicleSearchInput = document.getElementById('vehicleSearchInput');
    const vehicleTypeFilter = document.getElementById('vehicleTypeFilter'); // Noul filtru
    const vehicleListContainer = document.getElementById('vehicleListContainer');
    const noVehiclesFoundMessage = document.getElementById('noVehiclesFound');

    if (vehicleSearchInput && vehicleTypeFilter && vehicleListContainer) {
        function filterVehicleSelection() {
            const searchText = vehicleSearchInput.value.toLowerCase().trim();
            const selectedType = vehicleTypeFilter.value;
            let visibleCount = 0;

            document.querySelectorAll('.vehicle-select-card-col').forEach(item => { // Selector actualizat
                const searchData = item.getAttribute('data-search');
                const itemType = item.getAttribute('data-tip-vehicul');

                const searchMatch = (searchText === '' || searchData.includes(searchText));
                const typeMatch = (selectedType === 'all' || itemType === selectedType);

                if (searchMatch && typeMatch) {
                    item.style.display = 'block'; // Afisam elementul (col)
                    visibleCount++;
                } else {
                    item.style.display = 'none'; // Ascundem elementul
                }
            });

            if (noVehiclesFoundMessage) {
                noVehiclesFoundMessage.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        vehicleSearchInput.addEventListener('input', filterVehicleSelection);
        vehicleTypeFilter.addEventListener('change', filterVehicleSelection);
        filterVehicleSelection(); // Rulează la încărcarea paginii
    }


    // Logica pentru modalul de Adăugare Document
    const addDocumentForm = document.getElementById('addDocumentForm');
    if (addDocumentForm) {
        addDocumentForm.addEventListener('submit', function(event) {
            const fileInput = document.getElementById('addDocFile');
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                const maxSize = 5 * 1024 * 1024; // 5MB

                if (!allowedTypes.includes(file.type)) {
                    alert('Tipul de fișier nu este permis. Se acceptă doar PDF, JPG, PNG.');
                    event.preventDefault();
                    return;
                }
                if (file.size > maxSize) {
                    alert('Dimensiunea fișierului depășește limita de 5MB.');
                    event.preventDefault();
                    return;
                }
            }
        });
    }

    // Logica pentru modalul de Editare Document
    const editDocumentModal = document.getElementById('editDocumentModal');
    if (editDocumentModal) {
        editDocumentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const docId = button.getAttribute('data-id');
            const docTip = button.getAttribute('data-tip');
            const docExpirare = button.getAttribute('data-expirare');
            const docImportant = button.getAttribute('data-important');
            const docNumeUser = button.getAttribute('data-nume-document-user'); // Preluam numele documentului

            const modalTitle = editDocumentModal.querySelector('#editDocTitle');
            const docIdInput = editDocumentModal.querySelector('#editDocId');
            const docNameInput = editDocumentModal.querySelector('#editDocName'); // Campul nume document
            const docExpirationDateInput = editDocumentModal.querySelector('#editDocExpirationDate');
            const docImportantCheckbox = editDocumentModal.querySelector('#editDocImportant');

            if (modalTitle) modalTitle.textContent = docTip;
            if (docIdInput) docIdInput.value = docId;
            if (docNameInput) docNameInput.value = docNumeUser; // Seteaza numele documentului
            if (docExpirationDateInput) docExpirationDateInput.value = docExpirare;
            if (docImportantCheckbox) {
                docImportantCheckbox.checked = (docImportant === '1');
            }
        });
    }

    // Logica pentru modalul de Ștergere Document
    const deleteDocumentModal = document.getElementById('deleteDocumentModal');
    const confirmDeleteDocumentBtn = document.getElementById('confirmDeleteDocumentBtn');
    if (deleteDocumentModal && confirmDeleteDocumentBtn) {
        deleteDocumentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const docId = button.getAttribute('data-id');
            const docTip = button.getAttribute('data-tip');

            const modalTitle = deleteDocumentModal.querySelector('#deleteDocTitle');
            const docIdInput = deleteDocumentModal.querySelector('#deleteDocId');

            if (modalTitle) modalTitle.textContent = docTip;
            if (docIdInput) docIdInput.value = docId;
        });

        confirmDeleteDocumentBtn.addEventListener('click', function() {
            const docIdToDelete = document.getElementById('deleteDocId').value;
            const idVehicul = document.querySelector('#deleteDocumentModal input[name="id_vehicul"]').value;

            fetch('process_document.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete&document_id=' + docIdToDelete + '&id_vehicul=' + idVehicul
            })
            .then(response => response.text())
            .then(data => {
                console.log(data);
                const modalInstance = bootstrap.Modal.getInstance(deleteDocumentModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                location.reload();
            })
            .catch(error => {
                console.error('Eroare la ștergerea documentului:', error);
                alert('A apărut o eroare la ștergerea documentului.');
            });
        });
    }

    // Logica pentru butonul de printare
    document.querySelectorAll('.print-document-btn').forEach(button => {
        button.addEventListener('click', function() {
            const fileUrl = this.getAttribute('data-file-url');
            const fileType = this.getAttribute('data-file-type');

            if (!fileUrl) {
                alert('Nu există un fișier asociat pentru a printa.');
                return;
            }

            // Deschide fișierul într-o fereastră nouă și declanșează printarea
            const printWindow = window.open(fileUrl, '_blank');
            printWindow.onload = function() {
                // Întârziem puțin printarea pentru a permite fișierului să se încarce
                setTimeout(function() {
                    printWindow.print();
                }, 500); // 500ms întârziere
            };
        });
    });
});
</script>
