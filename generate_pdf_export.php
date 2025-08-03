<?php
session_start();
require_once 'db_connect.php';

// Activează afișarea erorilor pentru depanare (dezactivează în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$export_type = $_GET['type'] ?? '';
$export_format = $_GET['format'] ?? '';
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

error_log("PROCESS_EXPORT.PHP: Cerere export primită. Tip: " . $export_type . ", Format: " . $export_format . ", Perioada: " . ($start_date ?? 'NULL') . " - " . ($end_date ?? 'NULL'));

try {
    if (empty($export_type) || empty($export_format)) {
        throw new Exception("Tipul de export și formatul sunt obligatorii.");
    }

    $data_to_export = [];
    $headers = [];
    $file_name_prefix = strtolower(str_replace(' ', '_', $export_type));
    $file_name = $file_name_prefix . '_export_' . date('Ymd_His');

    // Construiește interogarea SQL și preia datele
    switch ($export_type) {
        case 'Facturi':
            $sql = "SELECT f.numar_factura, c.nume_companie, c.persoana_contact, f.data_emiterii, f.data_scadenta, f.valoare_totala, f.moneda, f.status, f.observatii 
                    FROM facturi f JOIN clienti c ON f.id_client = c.id 
                    WHERE f.data_emiterii BETWEEN ? AND ? ORDER BY f.data_emiterii ASC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului pentru Facturi: " . $conn->error);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $data_to_export[] = $row;
            }
            $stmt->close();
            $headers = ['Număr Factură', 'Nume Companie', 'Persoană Contact', 'Dată Emitere', 'Dată Scadență', 'Valoare Totală', 'Monedă', 'Status', 'Observații'];
            break;
        case 'Încasări':
            $sql = "SELECT pc.data_platii, pc.suma_platita, pc.moneda, pc.metoda_platii, f.numar_factura, c.nume_companie, pc.observatii 
                    FROM plati_clienti pc JOIN facturi f ON pc.id_factura = f.id JOIN clienti c ON f.id_client = c.id 
                    WHERE pc.data_platii BETWEEN ? AND ? ORDER BY pc.data_platii ASC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului pentru Încasări: " . $conn->error);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $data_to_export[] = $row;
            }
            $stmt->close();
            $headers = ['Dată Plată', 'Sumă Plătită', 'Monedă', 'Metodă Plată', 'Număr Factură', 'Nume Companie', 'Observații'];
            break;
        case 'Plăți': // Presupunem că plăți se referă la cheltuieli
        case 'Cheltuieli':
            $sql = "SELECT ch.data_cheltuielii, ch.descriere, ch.suma, ch.moneda, ch.categorie, v.model, v.numar_inmatriculare, ch.observatii 
                    FROM cheltuieli ch LEFT JOIN vehicule v ON ch.id_vehicul = v.id 
                    WHERE ch.data_cheltuielii BETWEEN ? AND ? ORDER BY ch.data_cheltuielii ASC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("Eroare la pregătirea query-ului pentru Cheltuieli: " . $conn->error);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $data_to_export[] = $row;
            }
            $stmt->close();
            $headers = ['Dată Cheltuială', 'Descriere', 'Sumă', 'Monedă', 'Categorie', 'Vehicul Model', 'Vehicul Nr. Înmatriculare', 'Observații'];
            break;
        default:
            throw new Exception("Tip de export necunoscut.");
    }

    if (empty($data_to_export)) {
        $_SESSION['error_message'] = "Nu s-au găsit date pentru export în perioada selectată.";
        header("Location: export-contabilitate.php");
        exit();
    }

    // Procesează exportul în formatul cerut
    switch ($export_format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $file_name . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers); // Scrie anteturile
            foreach ($data_to_export as $row) {
                fputcsv($output, $row); // Scrie rândurile de date
            }
            fclose($output);
            error_log("PROCESS_EXPORT.PHP: Export CSV reușit pentru tipul " . $export_type);
            break;
        case 'pdf':
            // Implementarea generării PDF necesită o bibliotecă PHP dedicată (ex: FPDF, TCPDF)
            // Pași generali:
            // 1. Includeți biblioteca (ex: require_once('path/to/fpdf.php');)
            // 2. Instanțiați un nou obiect PDF (ex: $pdf = new FPDF();)
            // 3. Adăugați pagini, setări de font, antet, subsol
            // 4. Iterați prin $data_to_export și adăugați conținutul în PDF
            // 5. Trimiteți PDF-ul la browser (ex: $pdf->Output('D', $file_name . '.pdf');)
            
            $_SESSION['error_message'] = "Generarea PDF-ului necesită o bibliotecă dedicată (ex: FPDF/TCPDF) și o implementare complexă de backend.";
            error_log("PROCESS_EXPORT.PHP: Eroare - Încercare de generare PDF fără implementare.");
            header("Location: export-contabilitate.php");
            break;
        case 'excel':
            // Implementarea generării Excel (.xlsx) necesită o bibliotecă PHP dedicată (ex: PhpSpreadsheet)
            // Pași generali:
            // 1. Instalați biblioteca (composer require phpoffice/phpspreadsheet)
            // 2. Includeți autoloader-ul (require 'vendor/autoload.php';)
            // 3. Creați un nou Spreadsheet (ex: $spreadsheet = new Spreadsheet();)
            // 4. Adăugați datele și anteturile în foaia de lucru
            // 5. Scrieți fișierul Excel și trimiteți-l la browser
            
            $_SESSION['error_message'] = "Generarea Excel-ului necesită o bibliotecă dedicată (ex: PhpSpreadsheet) și o implementare complexă de backend.";
            error_log("PROCESS_EXPORT.PHP: Eroare - Încercare de generare Excel fără implementare.");
            header("Location: export-contabilitate.php");
            break;
        default:
            throw new Exception("Format de export necunoscut.");
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Eroare la export: " . $e->getMessage();
    error_log("PROCESS_EXPORT.PHP: Eroare la export: " . $e->getMessage());
    header("Location: export-contabilitate.php");
} finally {
    if(isset($conn)) { $conn->close(); }
}
exit();
?>
```
---

### **3. `template/header.php` (Actualizat cu Noul Link)**

Voi adăuga un link către `export-contabilitate.php` în sidebar, sub categoria "Contabilitate".


```php
<?php
// Asigură că sesiunea este pornită pentru a accesa $_SESSION['user_role']
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$user_role = $_SESSION['user_role'] ?? 'Guest'; // Rolul utilizatorului, implicit 'Guest'
?>
<!doctype html>
<html lang="ro" data-bs-theme="blue-theme">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="icon" href="assets/images/favicon-32x32.png" type="image/png">
  <link href="assets/css/pace.min.css" rel="stylesheet">
  <script src="assets/js/pace.min.js"></script>
  <link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/metisMenu.min.css">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/mm-vertical.css">
  <link rel="stylesheet" type="text/css" href="assets/plugins/simplebar/css/simplebar.css">
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
  <!-- Boxicons CSS pentru icoane (asigură-te că este inclus corect) -->
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
  <link href="sass/main.css" rel="stylesheet">
  <link href="sass/dark-theme.css" rel="stylesheet">
  <link href="sass/blue-theme.css" rel="stylesheet">
  <link href="sass/semi-dark.css" rel="stylesheet">
  <link href="sass/bordered-theme.css" rel="stylesheet">
  <link href="sass/responsive.css" rel="stylesheet"> 

  <style>
    /* Stiluri esentiale pastrate din original (sau din responsive.css) */
    .metismenu .has-arrow::after { display: none !important; }
    .vehicle-card-img { height: 200px; object-fit: contain; background-color: rgba(0,0,0,0.05); padding: 5px; }

    /* Reparatia pentru layout si footer "lipit" */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .main-wrapper {
        flex-grow: 1;
    }
    /* Stiluri pentru sidebar (asigură-te că acestea sunt definite în fișierele tale SASS/CSS externe) */
    .sidebar-wrapper {
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1030;
        transition: all 0.3s ease;
        width: 280px;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        /* Culorile de fundal ale sidebar-ului */
        background: linear-gradient(to bottom, #4a69bd, #3b5998); 
        box-shadow: 2px 0 5px rgba(0,0,0,0.2);
    }
    .sidebar-nav {
        flex-grow: 1; 
        display: flex;
        flex-direction: column;
    }
    .metismenu {
        flex-grow: 1; 
    }
    .metismenu li.logout-item {
        margin-top: auto; 
    }
    /* Stiluri pentru elementele de meniu */
    .metismenu a {
        color: #e0e0e0;
        transition: all 0.3s ease;
        padding: 12px 20px;
    }
    .metismenu a:hover,
    .metismenu a.active {
        background-color: rgba(255,255,255,0.1);
        color: #fff;
        border-radius: 5px;
    }
    .metismenu .parent-icon {
        font-size: 1.3rem;
        margin-right: 10px;
        color: #f0f0f0;
    }
    .metismenu .menu-title {
        font-size: 1rem;
        font-weight: 500;
    }
    .metismenu ul { /* Sub-meniuri */
        background-color: rgba(0,0,0,0.1);
        border-top: 1px solid rgba(255,255,255,0.05);
        padding: 5px 0;
    }
    .metismenu ul a {
        padding: 8px 20px 8px 45px;
        font-size: 0.95rem;
    }
    .metismenu ul a:hover,
    .metismenu ul a.active {
        background-color: rgba(255,255,255,0.15);
    }
    .metismenu ul .material-icons-outlined,
    .metismenu ul .bx-right-arrow-alt {
        font-size: 0.8rem;
        margin-right: 8px;
    }
    /* Stiluri pentru badge-uri în sidebar */
    .sidebar-badge {
        margin-left: auto; /* Împinge badge-ul la dreapta */
        padding: 0.25em 0.6em;
        border-radius: 0.25rem;
        font-size: 0.75em;
        font-weight: bold;
    }
    .sidebar-badge.bg-danger { background-color: #dc3545; }
    .sidebar-badge.bg-warning { background-color: #ffc107; color: #343a40; }
    .sidebar-badge.bg-info { background-color: #17a2b8; }

    /* Stiluri pentru bara de căutare globală din sidebar */
    .sidebar-search-input {
        background-color: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        margin: 1rem;
    }
    .sidebar-search-input::placeholder {
        color: rgba(255,255,255,0.6);
    }

    /* Stiluri pentru toggle Dark/Light Mode */
    .theme-toggle-switch {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        border-top: 1px solid rgba(255,255,255,0.1);
        margin-top: 1rem;
    }
    .theme-toggle-switch .form-check-label {
        color: #fff;
        margin-left: 0.5rem;
    }
    .theme-toggle-switch .form-check-input {
        background-color: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.3);
    }
    .theme-toggle-switch .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    /* Stiluri pentru afișarea rolului utilizatorului */
    .user-role-display {
        color: #b0b0b0;
        font-size: 0.9em;
        text-align: center;
        padding: 0.5rem 1rem;
        border-top: 1px solid rgba(255,255,255,0.05);
        margin-top: 0.5rem;
    }
  </style>
</head>

<body>
  <header class="top-header">
    <nav class="navbar navbar-expand align-items-center gap-4">
      <div class="btn-toggle">
        <a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
      </div>
    </nav>
  </header>

   <aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
      <div class="logo-icon" style="padding: 10px;">
        <img src="assets/images/logo-dark.webp" class="logo-img" alt="NTS TOUR Logo" style="width: 150px; height: auto;">
      </div>
      <!-- Tagline-ul companiei -->
      <div class="logo-text" style="font-size: 0.8rem; color: rgba(255,255,255,0.7);">by MediaExpertSolution</div>
      <div class="sidebar-close">
        <span class="material-icons-outlined">close</span>
      </div>
    </div>

    <!-- Căutare rapidă globală în sidebar -->
    <input type="text" class="form-control sidebar-search-input" placeholder="Căutare rapidă...">

    <div class="sidebar-nav">
        <ul class="metismenu" id="sidenav">
          <li><a href="index.php"><div class="parent-icon"><i class="material-icons-outlined">dashboard</i></div><div class="menu-title">Panou control</div></a></li>
          
          <!-- Flotă -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">directions_car</i></div><div class="menu-title">Flotă</div></a><ul>
            <li><a href="vehicule.php"><i class="material-icons-outlined">arrow_right</i>Vehicule</a></li>
            <li><a href="salveaza-vehicul.php"><i class="material-icons-outlined">arrow_right</i>Adaugă Vehicul</a></li>
            <li><a href="curse-active.php"><i class="material-icons-outlined">arrow_right</i>Curse active</a></li>
            <li><a href="planificare-rute.php"><i class="material-icons-outlined">arrow_right</i>Planificare rute</a></li>
            <li><a href="istoric-curse.php"><i class="material-icons-outlined">arrow_right</i>Istoric curse</a></li>
            <li><a href="alocare-vehicul-sofer.php"><i class="material-icons-outlined">arrow_right</i>Alocare vehicul șofer</a></li>
            <li><a href="plan-revizii.php"><i class="material-icons-outlined">arrow_right</i>Plan revizii</a></li>
            </ul></li>
          
          <!-- Documente -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">description</i></div><div class="menu-title">Documente</div></a><ul>
            <li><a href="documente-vehicule.php"><i class="material-icons-outlined">arrow_right</i>Documente vehicule</a></li>
            <li><a href="contracte-angajati.php"><i class="material-icons-outlined">arrow_right</i>Contracte angajați</a></li>
            <li><a href="contracte-clienti.php"><i class="material-icons-outlined">arrow_right</i>Contracte clienți</a></li>
            <li><a href="polite-asigurare.php"><i class="material-icons-outlined">arrow_right</i>Polițe asigurare</a></li>
            <li><a href="notificari-documente-expirate.php"><i class="material-icons-outlined">arrow_right</i>Expirări curente</a></li>
            <li><a href="adauga-document.php"><i class="material-icons-outlined">arrow_right</i>Upload documente multiple</a></li>
            </ul></li>

          <!-- Angajați -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">people</i></div><div class="menu-title">Angajați</div></a><ul>
            <li><a href="lista-angajati.php"><i class="material-icons-outlined">arrow_right</i>Listă angajați</a></li>
            <li><a href="fise-individuale.php"><i class="material-icons-outlined">arrow_right</i>Fișe individuale</a></li>
            <li><a href="adauga-angajat.php"><i class="material-icons-outlined">arrow_right</i>Adaugă angajat</a></li>
            <li><a href="disponibilitate-grafica.php"><i class="material-icons-outlined">arrow_right</i>Disponibilitate grafică</a></li>
            <li><a href="permisiuni-utilizatori.php"><i class="material-icons-outlined">arrow_right</i>Permisiuni utilizatori</a></li>
            <li><a href="salarii-bonusuri.php"><i class="material-icons-outlined">arrow_right</i>Salarii & bonusuri</a></li>
            </ul></li>
          
          <!-- CRM -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">trending_up</i></div><div class="menu-title">CRM</div></a><ul>
            <li><a href="lista-clienti.php"><i class="material-icons-outlined">arrow_right</i>Listă clienți</a></li>
            <li><a href="adauga-client.php"><i class="material-icons-outlined">arrow_right</i>Adaugă client</a></li>
            <li><a href="istoric-comenzi-clienti.php"><i class="material-icons-outlined">arrow_right</i>Istoric comenzi clienți</a></li>
            <li><a href="facturare-transport.php"><i class="material-icons-outlined">arrow_right</i>Facturare transport</a></li>
            <li><a href="status-plati-clienti.php"><i class="material-icons-outlined">arrow_right</i>Status plăți clienți</a></li>
            <li><a href="ofertare-transport.php"><i class="material-icons-outlined">arrow_right</i>Ofertare transport</a></li>
            </ul></li>
          
          <!-- Contabilitate -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">account_balance_wallet</i></div><div class="menu-title">Contabilitate</div></a><ul>
            <li><a href="facturi-emise.php"><i class="material-icons-outlined">arrow_right</i>Facturi emise</a></li>
            <li><a href="emite-factura-noua.php"><i class="material-icons-outlined">arrow_right</i>Emite factură nouă</a></li>
            <li><a href="incasari-plati.php"><i class="material-icons-outlined">arrow_right</i>Încasări & plăți</a></li>
            <li><a href="cheltuieli-flota.php"><i class="material-icons-outlined">arrow_right</i>Cheltuieli flotă</a></li>
            <li><a href="raport-financiar-lunar.php"><i class="material-icons-outlined">arrow_right</i>Raport financiar lunar</a></li>
            <li><a href="cash-flow-vizual.php"><i class="material-icons-outlined">arrow_right</i>Cash-flow vizual</a></li>
            <li><a href="export-contabilitate.php"><i class="material-icons-outlined">arrow_right</i>Export contabilitate</a></li>
            <li><a href="configurare-fiscala.php"><i class="material-icons-outlined">arrow_right</i>Configurare TVA și monede</a></li>
            </ul></li>

          <!-- Mentenanță -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">build</i></div><div class="menu-title">Mentenanță</div><span class="sidebar-badge bg-danger">3</span></a><ul> <!-- Exemplu badge -->
            <li><a href="plan-revizii.php"><i class="material-icons-outlined">arrow_right</i>Programări service</a></li>
            <li><a href="notificari-probleme-raportate.php"><i class="material-icons-outlined">arrow_right</i>Probleme raportate</a></li>
            <li><a href="confirmare-lucrari.php"><i class="material-icons-outlined">arrow_right</i>Confirmare lucrări efectuate</a></li>
            <li><a href="plan-revizii.php"><i class="material-icons-outlined">arrow_right</i>Planificare revizii</a></li>
            <li><a href="istoric-mentenanta.php"><i class="material-icons-outlined">arrow_right</i>Istoric mentenanta pe vehicul</a></li>
            </ul></li>
          
          <!-- Rapoarte -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">assessment</i></div><div class="menu-title">Rapoarte</div></a><ul>
            <li><a href="raport-flota-zilnic.php"><i class="material-icons-outlined">arrow_right</i>Raport flotă zilnic</a></li>
            <li><a href="raport-flota-lunar.php"><i class="material-icons-outlined">arrow_right</i>Raport flotă lunar</a></li>
            <li><a href="raport-financiar.php"><i class="material-icons-outlined">arrow_right</i>Raport financiar</a></li>
            <li><a href="raport-consum-combustibil.php"><i class="material-icons-outlined">arrow_right</i>Raport consum combustibil</a></li>
            <li><a href="raport-cost-km.php"><i class="material-icons-outlined">arrow_right</i>Raport cost/km per vehicul</a></li>
            <li><a href="export-rapoarte.php"><i class="material-icons-outlined">arrow_right</i>Export rapoarte</a></li> <!-- Noul link -->
            </ul></li>

          <!-- Calendar -->
          <li><a href="calendar.php"><div class="parent-icon"><i class="material-icons-outlined">calendar_today</i></div><div class="menu-title">Calendar</div><span class="sidebar-badge bg-info">5</span></a></li> <!-- Exemplu badge -->
          
          <!-- Notificări -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">notifications_active</i></div><div class="menu-title">Notificări</div><span class="sidebar-badge bg-warning">2</span></a><ul> <!-- Exemplu badge -->
            <li><a href="notificari-documente-expirate.php"><i class="material-icons-outlined">arrow_right</i>Alerte documente</a></li>
            <li><a href="notificari-mentenanta.php"><i class="material-icons-outlined">arrow_right</i>Alerte mentenanță</a></li>
            <li><a href="notificari-soferi.php"><i class="material-icons-outlined">arrow_right</i>Alerte șoferi</a></li>
            <li><a href="notificari-curse.php"><i class="material-icons-outlined">arrow_right</i>Alerte curse</a></li>
            </ul></li>
          
          <!-- Setări -->
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">settings</i></div><div class="menu-title">Setări</div></a><ul>
            <li><a href="date-companie.php"><i class="material-icons-outlined">arrow_right</i>Date companie</a></li>
            <li><a href="utilizatori-permisiuni.php"><i class="material-icons-outlined">arrow_right</i>Utilizatori & permisiuni</a></li>
            <li><a href="setari-notificari.php"><i class="material-icons-outlined">arrow_right</i>Setări notificări</a></li>
            <li><a href="integrari.php"><i class="material-icons-outlined">arrow_right</i>Integrare GPS / ERP / SmartBill</a></li>
            <li><a href="configurare-fiscala.php"><i class="material-icons-outlined">arrow_right</i>Configurare TVA și monede</a></li>
            <li><a href="setari-tema.php"><i class="material-icons-outlined">arrow_right</i>Dark/Light mode</a></li>
            </ul></li>

          <!-- Deconectare -->
          <li class="logout-item">
            <a href="logout.php">
                <div class="parent-icon"><i class="material-icons-outlined">logout</i></div>
                <div class="menu-title">Deconectare</div>
            </a>
          </li>
         </ul>
    </div>

    <!-- Afișare rol utilizator -->
    <div class="user-role-display">
        Rol: <?php echo htmlspecialchars($user_role); ?>
    </div>

    <!-- Dark/Light Mode Toggle -->
    <div class="theme-toggle-switch">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="darkModeToggle">
            <label class="form-check-label" for="darkModeToggle">Mod Întunecat</label>
        </div>
    </div>
  </aside>
