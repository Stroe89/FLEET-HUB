<?php
// preview_company_data_pdf.php
session_start();

// Verifică autentificarea (opțional, dar recomandat și pentru preview)
if (!isset($_SESSION['user_id'])) {
    die("Acces neautorizat pentru previzualizare.");
}

require_once 'vendor/autoload.php'; // Asigură-te că Dompdf este instalat via Composer

use Dompdf\Dompdf;
use Dompdf\Options;

// Colectează datele POST. Folosim '?? ""' pentru a evita erorile dacă un câmp lipsește.
$nume_companie = $_POST['nume_companie'] ?? 'Nume Companie (Nedefinit)';
$cui = $_POST['cui'] ?? '';
$nr_reg_com = $_POST['nr_reg_com'] ?? '';
$adresa = $_POST['adresa'] ?? 'Adresă (Nedefinită)';
$oras = $_POST['oras'] ?? '';
$judet = $_POST['judet'] ?? '';
$cod_postal = $_POST['cod_postal'] ?? '';
$telefon = $_POST['telefon'] ?? 'Telefon (Nedefinit)';
$email = $_POST['email'] ?? 'Email (Nedefinit)';
$website = $_POST['website'] ?? '';
$logo_path = $_POST['existing_logo_path'] ?? ''; // Previzualizăm logo-ul existent

// Noile câmpuri
$bank_name = $_POST['bank_name'] ?? '';
$bank_iban = $_POST['bank_iban'] ?? '';
$bank_swift = $_POST['bank_swift'] ?? '';
$reprezentant_legal = $_POST['reprezentant_legal'] ?? '';
$functie_reprezentant = $_POST['functie_reprezentant'] ?? '';
$cod_fiscal = $_POST['cod_fiscal'] ?? '';
$activitate_principala = $_POST['activitate_principala'] ?? '';
$numar_angajati = $_POST['numar_angajati'] ?? '';
$capital_social = $_POST['capital_social'] ?? '';
$telefon_secundar = $_POST['telefon_secundar'] ?? '';
$email_secundar = $_POST['email_secundar'] ?? '';
$tara = $_POST['tara'] ?? '';
$regiune = $_POST['regiune'] ?? '';


// Convertirea logo-ului în Base64 pentru a fi inclus direct în PDF
$base64_logo = '';
if (!empty($logo_path) && file_exists($logo_path)) {
    $type = pathinfo($logo_path, PATHINFO_EXTENSION);
    $data = file_get_contents($logo_path);
    if ($data !== FALSE) { // Asigură-te că citirea fișierului a avut succes
        $base64_logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}


// Conținutul HTML pentru PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Previzualizare Antet Companie</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif; 
            margin: 15px; /* Redus de la 20px la 15px */
            padding: 0; /* Eliminat padding-ul */
            color: #333;
            font-size: 10pt; /* Dimensiune font redusă */
        }
        .header {
            width: 100%;
            display: table; 
            margin-bottom: 20px; /* Redus de la 30px la 20px */
            border-bottom: 1px solid #eee;
            padding-bottom: 10px; /* Redus de la 15px la 10px */
        }
        .header-left {
            display: table-cell;
            width: 25%; /* Mărit de la 20% la 25% pentru logo */
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 75%; /* Redus de la 80% la 75% */
            vertical-align: top;
            text-align: right;
        }
        .logo {
            max-width: 150px; /* Menținut la 150px pentru vizibilitate bună */
            height: auto;
            display: block; 
            margin-right: 10px; /* Spațiu la dreapta logo-ului */
        }
        h1 {
            margin: 0;
            font-size: 1.5em; /* Redus de la 1.8em */
            color: #2a3042;
        }
        p {
            margin: 1px 0; /* Redus de la 2px */
            font-size: 0.85em; /* Redus de la 0.9em */
            line-height: 1.3; /* Ajustat line-height */
        }
        .section-title {
            font-size: 1.1em; /* Redus de la 1.2em */
            color: #6a90f1;
            margin-top: 20px; /* Redus de la 25px */
            margin-bottom: 8px; /* Redus de la 10px */
            border-bottom: 1px dashed #ddd;
            padding-bottom: 4px; /* Redus de la 5px */
        }
        .data-row {
            clear: both;
            margin-bottom: 3px; /* Redus de la 5px */
        }
        .data-label {
            font-weight: bold;
            width: 35%; /* Mărit de la 30% */
            float: left;
            font-size: 0.85em; /* Corespunde cu p */
        }
        .data-value {
            width: 65%; /* Redus de la 70% */
            float: left;
            font-size: 0.85em; /* Corespunde cu p */
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px; /* Redus de la 8px */
            text-align: left;
            font-size: 0.85em; /* Corespunde cu p */
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            text-align: center;
            font-size: 0.75em; /* Redus de la 0.8em */
            color: #777;
            margin-top: 30px; /* Redus de la 50px */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
';
if (!empty($base64_logo)) { 
    $html .= '<img src="' . $base64_logo . '" class="logo">';
}
$html .= '
        </div>
        <div class="header-right">
            <h1>' . htmlspecialchars($nume_companie) . '</h1>
            <p>' . htmlspecialchars($adresa) . (!empty($oras) ? ', ' . htmlspecialchars($oras) : '') . (!empty($judet) ? ', ' . htmlspecialchars($judet) : '') . (!empty($cod_postal) ? ', CP: ' . htmlspecialchars($cod_postal) : '') . '</p>
            <p>' . (!empty($tara) ? htmlspecialchars($tara) : '') . (!empty($regiune) ? ' (' . htmlspecialchars($regiune) . ')' : '') . '</p>
            <p>Telefon: ' . htmlspecialchars($telefon) . (!empty($telefon_secundar) ? ' / ' . htmlspecialchars($telefon_secundar) : '') . '</p>
            <p>Email: ' . htmlspecialchars($email) . (!empty($email_secundar) ? ' / ' . htmlspecialchars($email_secundar) : '') . '</p>
            ' . (!empty($website) ? '<p>Website: <a href="' . htmlspecialchars($website) . '">' . htmlspecialchars($website) . '</a></p>' : '') . '
            ' . (!empty($cui) ? '<p>CUI: ' . htmlspecialchars($cui) : '') . (!empty($nr_reg_com) ? ' | Nr. Reg. Com.: ' . htmlspecialchars($nr_reg_com) . '</p>' : '') . '
        </div>
    </div>

    <div class="section-title">Detalii complete companie</div>

    <div class="data-row clearfix">
        <div class="data-label">Nume Companie:</div>
        <div class="data-value">' . htmlspecialchars($nume_companie) . '</div>
    </div>
    <div class="data-row clearfix">
        <div class="data-label">CUI:</div>
        <div class="data-value">' . htmlspecialchars($cui) . '</div>
    </div>
    <div class="data-row clearfix">
        <div class="data-label">Nr. Reg. Com.:</div>
        <div class="data-value">' . htmlspecialchars($nr_reg_com) . '</div>
    </div>
    ' . (!empty($cod_fiscal) ? '<div class="data-row clearfix"><div class="data-label">Cod Fiscal:</div><div class="data-value">' . htmlspecialchars($cod_fiscal) . '</div></div>' : '') . '
    ' . (!empty($adresa) ? '<div class="data-row clearfix"><div class="data-label">Adresă:</div><div class="data-value">' . htmlspecialchars($adresa) . '</div></div>' : '') . '
    ' . (!empty($oras) ? '<div class="data-row clearfix"><div class="data-label">Oraș:</div><div class="data-value">' . htmlspecialchars($oras) . '</div></div>' : '') . '
    ' . (!empty($judet) ? '<div class="data-row clearfix"><div class="data-label">Județ:</div><div class="data-value">' . htmlspecialchars($judet) . '</div></div>' : '') . '
    ' . (!empty($cod_postal) ? '<div class="data-row clearfix"><div class="data-label">Cod Poștal:</div><div class="data-value">' . htmlspecialchars($cod_postal) . '</div></div>' : '') . '
    ' . (!empty($tara) ? '<div class="data-row clearfix"><div class="data-label">Țara:</div><div class="data-value">' . htmlspecialchars($tara) . '</div></div>' : '') . '
    ' . (!empty($regiune) ? '<div class="data-row clearfix"><div class="data-label">Regiune:</div><div class="data-value">' . htmlspecialchars($regiune) . '</div></div>' : '') . '
    ' . (!empty($telefon) ? '<div class="data-row clearfix"><div class="data-label">Telefon Principal:</div><div class="data-value">' . htmlspecialchars($telefon) . '</div></div>' : '') . '
    ' . (!empty($telefon_secundar) ? '<div class="data-row clearfix"><div class="data-label">Telefon Secundar:</div><div class="data-value">' . htmlspecialchars($telefon_secundar) . '</div></div>' : '') . '
    ' . (!empty($email) ? '<div class="data-row clearfix"><div class="data-label">Email Principal:</div><div class="data-value">' . htmlspecialchars($email) . '</div></div>' : '') . '
    ' . (!empty($email_secundar) ? '<div class="data-row clearfix"><div class="data-label">Email Secundar:</div><div class="data-value">' . htmlspecialchars($email_secundar) . '</div></div>' : '') . '
    ' . (!empty($website) ? '<div class="data-row clearfix"><div class="data-label">Website:</div><div class="data-value"><a href="' . htmlspecialchars($website) . '">' . htmlspecialchars($website) . '</a></div></div>' : '') . '
    ' . (!empty($activitate_principala) ? '<div class="data-row clearfix"><div class="data-label">Activitate Principală:</div><div class="data-value">' . htmlspecialchars($activitate_principala) . '</div></div>' : '') . '
    ' . (!empty($numar_angajati) ? '<div class="data-row clearfix"><div class="data-label">Număr Angajați:</div><div class="data-value">' . htmlspecialchars($numar_angajati) . '</div></div>' : '') . '
    ' . (!empty($capital_social) ? '<div class="data-row clearfix"><div class="data-label">Capital Social:</div><div class="data-value">' . htmlspecialchars($capital_social) . ' RON</div></div>' : '') . '

    <div class="section-title">Detalii Bancare</div>
    ' . (!empty($bank_name) ? '<div class="data-row clearfix"><div class="data-label">Nume Bancă:</div><div class="data-value">' . htmlspecialchars($bank_name) . '</div></div>' : '') . '
    ' . (!empty($bank_iban) ? '<div class="data-row clearfix"><div class="data-label">IBAN:</div><div class="data-value">' . htmlspecialchars($bank_iban) . '</div></div>' : '') . '
    ' . (!empty($bank_swift) ? '<div class="data-row clearfix"><div class="data-label">SWIFT/BIC:</div><div class="data-value">' . htmlspecialchars($bank_swift) . '</div></div>' : '') . '
    
    <div class="section-title">Reprezentant Legal</div>
    ' . (!empty($reprezentant_legal) ? '<div class="data-row clearfix"><div class="data-label">Nume Reprezentant:</div><div class="data-value">' . htmlspecialchars($reprezentant_legal) . '</div></div>' : '') . '
    ' . (!empty($functie_reprezentant) ? '<div class="data-row clearfix"><div class="data-label">Funcție Reprezentant:</div><div class="data-value">' . htmlspecialchars($functie_reprezentant) . '</div></div>' : '') . '

    <div class="footer">
        <p>Aceasta este o previzualizare a datelor companiei. Pentru rapoarte complete, utilizați funcția de generare rapoarte.</p>
    </div>
</body>
</html>
';

// Instanțiază Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

// Setează dimensiunea și orientarea paginii
$dompdf->setPaper('A4', 'portrait');

// Rederizează HTML ca PDF
$dompdf->render();

// Trimitere către browser pentru vizualizare
$dompdf->stream("previzualizare_companie.pdf", array("Attachment" => false));
?>