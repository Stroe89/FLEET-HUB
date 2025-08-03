<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mesaje de succes sau eroare din sesiune (pentru consistență, chiar dacă nu sunt folosite direct aici)
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

// Preluăm luna și anul din GET sau setăm la luna/anul curent
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Validare simplă pentru lună și an
if (!is_numeric($selected_month) || $selected_month < 1 || $selected_month > 12) {
    $selected_month = date('m');
}
if (!is_numeric($selected_year) || $selected_year < 2000 || $selected_year > 2100) { // Limite rezonabile
    $selected_year = date('Y');
}

// Calculăm prima și ultima zi a lunii selectate
$start_date_month = date('Y-m-01', strtotime($selected_year . '-' . $selected_month . '-01'));
$end_date_month = date('Y-m-t', strtotime($selected_year . '-' . $selected_month . '-01'));

// --- Logica pentru Calculul Veniturilor ---
$total_revenues = 0;
$sql_revenues = "SELECT SUM(valoare_totala) as total FROM facturi WHERE status = 'Platita' AND data_emiterii BETWEEN ? AND ?";
$stmt_revenues = $conn->prepare($sql_revenues);
if ($stmt_revenues) {
    $stmt_revenues->bind_param("ss", $start_date_month, $end_date_month);
    $stmt_revenues->execute();
    $result_revenues = $stmt_revenues->get_result();
    if ($row = $result_revenues->fetch_assoc()) {
        $total_revenues = $row['total'] ?? 0;
    }
    $stmt_revenues->close();
}

// --- Logica pentru Calculul Cheltuielilor ---
$total_expenses = 0;
$sql_expenses = "SELECT SUM(suma) as total FROM cheltuieli WHERE data_cheltuielii BETWEEN ? AND ?";
$stmt_expenses = $conn->prepare($sql_expenses);
if ($stmt_expenses) {
    $stmt_expenses->bind_param("ss", $start_date_month, $end_date_month);
    $stmt_expenses->execute();
    $result_expenses = $stmt_expenses->get_result();
    if ($row = $result_expenses->fetch_assoc()) {
        $total_expenses = $row['total'] ?? 0;
    }
    $stmt_expenses->close();
}

$net_profit = $total_revenues - $total_expenses;

$conn->close();

// Numele lunilor în română
$month_names = [
    '01' => 'Ianuarie', '02' => 'Februarie', '03' => 'Martie', '04' => 'Aprilie',
    '05' => 'Mai', '06' => 'Iunie', '07' => 'Iulie', '08' => 'August',
    '09' => 'Septembrie', '10' => 'Octombrie', '11' => 'Noiembrie', '12' => 'Decembrie'
];
?>

<title>NTS TOUR | Raport Financiar Lunar</title>

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

    /* Stiluri specifice raportului */
    .report-summary-card .card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-height: 150px;
    }
    .report-summary-card h4 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    .report-summary-card p {
        font-size: 1rem;
        color: #b0b0b0;
    }
    .report-summary-card.bg-success { background-color: #28a745 !important; }
    .report-summary-card.bg-danger { background-color: #dc3545 !important; }
    .report-summary-card.bg-info { background-color: #17a2b8 !important; }

    /* Stiluri pentru filtre */
    .filter-section .input-group-text {
        background-color: #3b435a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .filter-section .form-control, .filter-section .form-select {
        background-color: #1a2035 !important;
        color: #e0e0e0 !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Contabilitate</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Raport Financiar Lunar</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Raport Financiar pentru <?php echo $month_names[$selected_month] . ' ' . $selected_year; ?></h4>
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

                        <!-- Filtre Lună/An -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-4 mb-3">
                                <label for="selectMonth" class="form-label">Selectează Luna:</label>
                                <select class="form-select" id="selectMonth">
                                    <?php foreach ($month_names as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php if ($selected_month == $num) echo 'selected'; ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="selectYear" class="form-label">Selectează Anul:</label>
                                <select class="form-select" id="selectYear">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): // De la anul curent înapoi ?>
                                        <option value="<?php echo $y; ?>" <?php if ($selected_year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="applyFilterBtn">Aplică Filtru</button>
                            </div>
                        </div>

                        <!-- Sumar Financiar -->
                        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                            <div class="col">
                                <div class="card report-summary-card bg-success">
                                    <div class="card-body">
                                        <p class="mb-0">Venituri Totale</p>
                                        <h4><?php echo number_format($total_revenues, 2, ',', '.'); ?> RON</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card report-summary-card bg-danger">
                                    <div class="card-body">
                                        <p class="mb-0">Cheltuieli Totale</p>
                                        <h4><?php echo number_format($total_expenses, 2, ',', '.'); ?> RON</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card report-summary-card bg-info">
                                    <div class="card-body">
                                        <p class="mb-0">Profit Net</p>
                                        <h4><?php echo number_format($net_profit, 2, ',', '.'); ?> RON</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalii (Opțional: afișează listele de facturi și cheltuieli aici) -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="card-title">Detalii Venituri și Cheltuieli</h5>
                                <hr>
                                <p class="text-muted">Această secțiune ar putea afișa tabele detaliate cu facturile plătite și cheltuielile pentru luna selectată. Pentru simplitate, momentan afișăm doar sumarul. Puteți extinde această secțiune pentru a include tabele cu date.</p>
                                <!-- Exemplu de tabel pentru venituri/cheltuieli, dacă doriți să le afișați -->
                                <!--
                                <h6 class="card-title mt-4">Venituri Detaliate</h6>
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Factura</th><th>Client</th><th>Valoare</th><th>Dată Plată</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>#2025001</td><td>Client A</td><td>1500.00 RON</td><td>01.06.2025</td></tr>
                                    </tbody>
                                </table>

                                <h6 class="card-title mt-4">Cheltuieli Detaliate</h6>
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Descriere</th><th>Categorie</th><th>Sumă</th><th>Dată</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Combustibil Crafter</td><td>Combustibil</td><td>300.00 RON</td><td>05.06.2025</td></tr>
                                    </tbody>
                                </table>
                                -->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectMonth = document.getElementById('selectMonth');
    const selectYear = document.getElementById('selectYear');
    const applyFilterBtn = document.getElementById('applyFilterBtn');

    applyFilterBtn.addEventListener('click', function() {
        const month = selectMonth.value;
        const year = selectYear.value;
        window.location.href = `raport-financiar-lunar.php?month=${month}&year=${year}`;
    });
});
</script>
