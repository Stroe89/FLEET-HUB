<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mesaje de succes sau eroare din sesiune - Acestea vor fi acum gestionate de Toastr/AJAX
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

// Funcții angajați disponibile
$functii_angajati = ['Sofer', 'Dispecer', 'Mecanic', 'Administrator', 'Contabil', 'Manager', 'Altele'];
$statusuri_angajati = ['Activ', 'Inactiv', 'Concediu', 'Suspendat', 'Demisionat'];

// Categorii de permis de conducere
$categorii_permis_disponibile = ['A', 'A1', 'A2', 'B', 'B1', 'BE', 'C', 'C1', 'CE', 'C1E', 'D', 'D1', 'DE', 'D1E', 'Tr', 'Tb', 'Tv'];

// Valori implicite pentru un formular gol (pentru un angajat nou)
// Asigură-te că aceste chei există în tabela ta 'angajati'
$angajat = [
    'nume' => '',
    'prenume' => '',
    'cod_intern' => '', // Va fi generat automat
    'data_angajare' => '',
    'functie' => '',
    'telefon' => '',
    'email' => '',
    'adresa' => '',
    'salariu' => '',
    'status' => 'Activ',
    'observatii' => '',
    // Noi câmpuri (asigură-te că există în baza de date)
    'data_nastere' => '',
    'loc_nastere' => '',
    'nationalitate' => 'Română',
    'cnp' => '',
    'serie_ci' => '',
    'numar_ci' => '',
    'numar_permis' => '',
    'data_emitere_permis' => '',
    'data_expirare_permis' => '',
    'autoritate_emitenta_permis' => '',
    'categorii_permis' => '', // Va fi un string 'B,C,CE'
    'data_valabilitate_fisa_medicala' => '',
    'data_valabilitate_aviz_psihologic' => '',
    'nume_contact_urgenta' => '',
    'relatie_contact_urgenta' => '',
    'telefon_contact_urgenta' => '',
    'atestate' => ''
];
?>

<title>NTS TOUR | Adaugă Angajat</title>

<!-- Adăugat: Link pentru Bootstrap Icons (opțional, pentru mai multe iconițe) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    /* Stiluri generale preluate din temă */
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
    /* Stiluri specifice pentru formular */
    .btn-generate-code {
        background-color: #17a2b8 !important;
        border-color: #17a2b8 !important;
        color: #fff !important;
        font-weight: bold;
    }
    .btn-generate-code:hover {
        background-color: #138496 !important;
        border-color: #138496 !important;
    }
    /* Stil pentru ascunderea săgeților de la input type="number" */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }

    /* Stiluri pentru checkbox-uri multiple (categorii permis) */
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem; /* Spațiu între checkbox-uri */
        padding: 0.5rem 0.75rem; /* Adăugat padding pentru a arăta mai bine */
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.5rem;
        background-color: #1a2035;
    }
    .checkbox-group .form-check {
        margin-right: 0.5rem;
        margin-bottom: 0;
    }
    .checkbox-group .form-check-label {
        color: #e0e0e0;
        cursor: pointer;
    }
    .checkbox-group .form-check-input {
        cursor: pointer;
    }

    /* Stiluri pentru iconițe în input-group */
    .input-group-text {
        background-color: #3b435a !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: #e0e0e0 !important;
        border-radius: 0.5rem 0 0 0.5rem !important; /* Doar stânga rotunjit */
    }
    .input-group > .form-control,
    .input-group > .form-select {
        border-radius: 0 0.5rem 0.5rem 0 !important; /* Doar dreapta rotunjit */
    }
    /* Specific pentru butonul de generare cod */
    .input-group > .btn-generate-code {
        border-radius: 0 0.5rem 0.5rem 0 !important;
    }

    /* Bootstrap Toast Container */
    .toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1080; /* Higher than modals if needed */
    }
    .toast {
        background-color: #2a3042;
        color: #e0e0e0;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .toast-header {
        background-color: #3b435a;
        color: #ffffff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .toast-body {
        background-color: #2a3042;
        color: #e0e0e0;
    }
    .toast-success .toast-header { background-color: #2c5234; color: #fff; }
    .toast-danger .toast-header { background-color: #5c2c31; color: #fff; }
    .toast-info .toast-header { background-color: #203354; color: #fff; }

</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Adaugă Angajat</div>
            <div class="ps-3">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Adaugă Angajat Nou</h4>
                        <hr>

                        <!-- Mesaje de succes/eroare PHP - vor fi înlocuite de Toastr/AJAX pe termen lung -->
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form id="addAngajatForm" action="process_angajati.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="add">
                            
                            <!-- Secțiunea Detalii Angajat -->
                            <div class="row g-3">
                                <div class="col-12"><h6 class="mb-3">Detalii Angajat</h6></div>
                                <div class="col-md-6">
                                    <label for="nume" class="form-label">Nume:</label>
                                    <input type="text" class="form-control" id="nume" name="nume" value="<?php echo htmlspecialchars($angajat['nume']); ?>" required>
                                    <div class="invalid-feedback">Te rog introdu numele.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="prenume" class="form-label">Prenume:</label>
                                    <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo htmlspecialchars($angajat['prenume']); ?>" required>
                                    <div class="invalid-feedback">Te rog introdu prenumele.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="codIntern" class="form-label">Cod Intern Angajat:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="codIntern" name="cod_intern" value="<?php echo htmlspecialchars($angajat['cod_intern']); ?>" readonly required>
                                        <button class="btn btn-generate-code" type="button" id="generateCodeBtn">Generează</button>
                                        <div class="invalid-feedback">Codul intern este obligatoriu.</div>
                                    </div>
                                    <small class="form-text text-muted">Acest cod este generat automat și este unic.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="dataAngajare" class="form-label">Dată Angajare:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="dataAngajare" name="data_angajare" value="<?php echo htmlspecialchars($angajat['data_angajare']); ?>" required>
                                        <div class="invalid-feedback">Te rog introdu data angajării.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="functie" class="form-label">Funcție:</label>
                                    <select class="form-select" id="functie" name="functie" required>
                                        <option value="">Selectează funcția</option>
                                        <?php foreach ($functii_angajati as $functie): ?>
                                            <option value="<?php echo htmlspecialchars($functie); ?>" <?php if ($angajat['functie'] == $functie) echo 'selected'; ?>><?php echo htmlspecialchars($functie); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Te rog selectează o funcție.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefon" class="form-label">Telefon:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($angajat['telefon']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($angajat['email']); ?>">
                                    </div>
                                    <div class="invalid-feedback">Te rog introdu o adresă de email validă.</div>
                                </div>
                                <div class="col-12">
                                    <label for="adresa" class="form-label">Adresă:</label>
                                    <textarea class="form-control" id="adresa" name="adresa" rows="2"><?php echo htmlspecialchars($angajat['adresa']); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="salariu" class="form-label">Salariu (RON):</label>
                                    <input type="number" step="0.01" class="form-control" id="salariu" name="salariu" value="<?php echo htmlspecialchars($angajat['salariu']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status:</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <?php foreach ($statusuri_angajati as $status): ?>
                                            <option value="<?php echo htmlspecialchars($status); ?>" <?php if ($angajat['status'] == $status) echo 'selected'; ?>><?php echo htmlspecialchars($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Te rog selectează un status.</div>
                                </div>

                                <!-- Detalii Personale Suplimentare -->
                                <div class="col-12"><hr><h6 class="mt-2">Detalii Personale</h6></div>
                                <div class="col-md-6">
                                    <label for="dataNastere" class="form-label">Dată Naștere:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="dataNastere" name="data_nastere" value="<?php echo htmlspecialchars($angajat['data_nastere']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="locNastere" class="form-label">Loc Naștere:</label>
                                    <input type="text" class="form-control" id="locNastere" name="loc_nastere" value="<?php echo htmlspecialchars($angajat['loc_nastere']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="nationalitate" class="form-label">Naționalitate:</label>
                                    <input type="text" class="form-control" id="nationalitate" name="nationalitate" value="<?php echo htmlspecialchars($angajat['nationalitate']); ?>" placeholder="Ex: Română">
                                </div>
                                <div class="col-md-6">
                                    <label for="cnp" class="form-label">CNP:</label>
                                    <input type="text" class="form-control" id="cnp" name="cnp" value="<?php echo htmlspecialchars($angajat['cnp']); ?>" maxlength="13" pattern="[0-9]{13}">
                                    <div class="invalid-feedback">CNP-ul trebuie să conțină exact 13 cifre.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="serieCi" class="form-label">Serie CI:</label>
                                    <input type="text" class="form-control" id="serieCi" name="serie_ci" value="<?php echo htmlspecialchars($angajat['serie_ci']); ?>" maxlength="2">
                                </div>
                                <div class="col-md-6">
                                    <label for="numarCi" class="form-label">Număr CI:</label>
                                    <input type="text" class="form-control" id="numarCi" name="numar_ci" value="<?php echo htmlspecialchars($angajat['numar_ci']); ?>" maxlength="6">
                                </div>

                                <!-- Detalii Permis de Conducere -->
                                <div class="col-12"><hr><h6 class="mt-2">Detalii Permis de Conducere</h6></div>
                                <div class="col-md-6">
                                    <label for="numarPermis" class="form-label">Număr Permis:</label>
                                    <input type="text" class="form-control" id="numarPermis" name="numar_permis" value="<?php echo htmlspecialchars($angajat['numar_permis']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="dataEmiterePermis" class="form-label">Dată Emitere Permis:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="dataEmiterePermis" name="data_emitere_permis" value="<?php echo htmlspecialchars($angajat['data_emitere_permis']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="dataExpirarePermis" class="form-label">Dată Expirare Permis:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="dataExpirarePermis" name="data_expirare_permis" value="<?php echo htmlspecialchars($angajat['data_expirare_permis']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="autoritateEmitentaPermis" class="form-label">Autoritate Emitentă Permis:</label>
                                    <input type="text" class="form-control" id="autoritateEmitentaPermis" name="autoritate_emitenta_permis" value="<?php echo htmlspecialchars($angajat['autoritate_emitenta_permis']); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Categorii Permis:</label>
                                    <div class="checkbox-group">
                                        <?php 
                                        $categorii_selectate = explode(',', $angajat['categorii_permis']);
                                        foreach ($categorii_permis_disponibile as $cat): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="cat_<?php echo htmlspecialchars($cat); ?>" name="categorii_permis[]" value="<?php echo htmlspecialchars($cat); ?>" <?php if (in_array($cat, $categorii_selectate)) echo 'checked'; ?>>
                                                <label class="form-check-label" for="cat_<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Detalii Medicale și Atestate -->
                                <div class="col-12"><hr><h6 class="mt-2">Detalii Medicale & Atestate</h6></div>
                                <div class="col-md-6">
                                    <label for="dataFisaMedicala" class="form-label">Dată Valabilitate Fișă Medicală:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="dataFisaMedicala" name="data_valabilitate_fisa_medicala" value="<?php echo htmlspecialchars($angajat['data_valabilitate_fisa_medicala']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="dataAvizPsihologic" class="form-label">Dată Valabilitate Aviz Psihologic:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="dataAvizPsihologic" name="data_valabilitate_aviz_psihologic" value="<?php echo htmlspecialchars($angajat['data_valabilitate_aviz_psihologic']); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="atestate" class="form-label">Atestate (CPC, CPI, ADR, etc.):</label>
                                    <textarea class="form-control" id="atestate" name="atestate" rows="2" placeholder="Ex: CPC Marfă, CPI Persoane"><?php echo htmlspecialchars($angajat['atestate']); ?></textarea>
                                </div>

                                <!-- Contact de Urgență -->
                                <div class="col-12"><hr><h6 class="mt-2">Contact de Urgență</h6></div>
                                <div class="col-md-4">
                                    <label for="numeContactUrgenta" class="form-label">Nume Contact Urgență:</label>
                                    <input type="text" class="form-control" id="numeContactUrgenta" name="nume_contact_urgenta" value="<?php echo htmlspecialchars($angajat['nume_contact_urgenta']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="relatieContactUrgenta" class="form-label">Relație Contact Urgență:</label>
                                    <input type="text" class="form-control" id="relatieContactUrgenta" name="relatie_contact_urgenta" value="<?php echo htmlspecialchars($angajat['relatie_contact_urgenta']); ?>" placeholder="Ex: Soție, Frate">
                                </div>
                                <div class="col-md-4">
                                    <label for="telefonContactUrgenta" class="form-label">Telefon Contact Urgență:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                        <input type="tel" class="form-control" id="telefonContactUrgenta" name="telefon_contact_urgenta" value="<?php echo htmlspecialchars($angajat['telefon_contact_urgenta']); ?>">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="observatii" class="form-label">Observații Generale:</label>
                                    <textarea class="form-control" id="observatii" name="observatii" rows="3"><?php echo htmlspecialchars($angajat['observatii']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="lista-angajati.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary">Salvează Angajat</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Container pentru Toast-uri -->
<div class="toast-container"></div>

<?php require_once 'template/footer.php'; ?>

<!-- Asigură-te că jQuery este inclus înainte de acest script, dacă nu este deja în footer.php -->
<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateCodeBtn = document.getElementById('generateCodeBtn');
    const codInternInput = document.getElementById('codIntern');
    const addAngajatForm = document.getElementById('addAngajatForm');

    // Funcție pentru generarea unui cod intern unic
    function generateUniqueCode(length = 8) {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    // Generează codul la încărcarea paginii dacă câmpul este gol
    if (codInternInput.value === '') {
        codInternInput.value = generateUniqueCode();
    }

    // Generează codul la click pe buton
    generateCodeBtn.addEventListener('click', function() {
        codInternInput.value = generateUniqueCode();
    });

    // Setează data angajării la data curentă implicit la încărcarea paginii
    const dataAngajareInput = document.getElementById('dataAngajare');
    if (dataAngajareInput.value === '') {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0'); // Months start at 0!
        const dd = String(today.getDate()).padStart(2, '0');
        dataAngajareInput.value = `${yyyy}-${mm}-${dd}`;
    }

    // Funcție pentru afișarea Toast-urilor Bootstrap
    function showToast(type, message) {
        const toastContainer = document.querySelector('.toast-container');
        const toastId = `toast-${Date.now()}`;
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    // Trimiterea formularului cu AJAX
    addAngajatForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Oprește trimiterea normală a formularului

        // Validare Bootstrap client-side
        if (!addAngajatForm.checkValidity()) {
            event.stopPropagation();
            addAngajatForm.classList.add('was-validated');
            showToast('danger', 'Te rog completează toate câmpurile obligatorii.');
            return;
        }
        addAngajatForm.classList.add('was-validated'); // Asigură că validarea vizuală e aplicată

        const formData = new FormData(addAngajatForm);

        // Colectează categoriile de permis selectate
        const categoriiPermis = [];
        document.querySelectorAll('input[name="categorii_permis[]"]:checked').forEach(checkbox => {
            categoriiPermis.push(checkbox.value);
        });
        formData.set('categorii_permis', categoriiPermis.join(',')); // Actualizează valoarea în FormData

        fetch('process_angajati.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json(); // Așteaptă un răspuns JSON de la server
        })
        .then(data => {
            if (data.success) {
                showToast('success', data.message);
                // Resetează formularul după succes
                addAngajatForm.reset();
                addAngajatForm.classList.remove('was-validated'); // Elimină feedback-ul de validare
                // Re-generează codul intern și data angajării pentru un nou angajat
                codInternInput.value = generateUniqueCode();
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                dataAngajareInput.value = `${yyyy}-${mm}-${dd}`;

                // Opțional: redirecționează către lista de angajați după un scurt delay
                setTimeout(() => {
                    window.location.href = 'lista-angajati.php';
                }, 1500); // Redirecționează după 1.5 secunde
            } else {
                showToast('danger', data.message || 'A apărut o eroare la salvarea angajatului.');
            }
        })
        .catch(error => {
            console.error('Eroare la salvarea angajatului:', error);
            showToast('danger', 'A apărut o eroare la salvarea angajatului. Detalii: ' + error.message);
        });
    });
});
</script>