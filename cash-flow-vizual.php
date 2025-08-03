<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mesaje de succes sau eroare din sesiune (pentru consistență)
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
$view_type = $_GET['view_type'] ?? 'total'; // 'total' sau 'categorii'

// Validare simplă pentru lună, an și tip vizualizare
if (!is_numeric($selected_month) || $selected_month < 1 || $selected_month > 12) {
    $selected_month = date('m');
}
if (!is_numeric($selected_year) || $selected_year < 2000 || $selected_year > 2100) {
    $selected_year = date('Y');
}
if (!in_array($view_type, ['total', 'categorii'])) {
    $view_type = 'total';
}

// Calculăm prima și ultima zi a lunii selectate
$start_date_month = date('Y-m-01', strtotime($selected_year . '-' . $selected_month . '-01'));
$end_date_month = date('Y-m-t', strtotime($selected_year . '-' . $selected_month . '-01'));

// --- Logica pentru Preluarea Datelor pentru Grafice ---

$labels_days = []; // Zilele lunii (pentru graficele pe zile)
$revenues_daily_values = [];
$expenses_daily_values = [];
$expenses_categories_monthly_totals = []; // Suma totală pe categorii pentru graficul Donut
$expenses_daily_by_category = []; // Cheltuieli pe zile, defalcate pe categorii (pentru graficul Bară Stivuită)

$current_date_dt = new DateTime($start_date_month);
while ($current_date_dt->format('Y-m-d') <= $end_date_month) {
    $labels_days[] = $current_date_dt->format('d M'); // Ex: 01 Ian
    $date_str = $current_date_dt->format('Y-m-d');
    $revenues_daily_values[$date_str] = 0;
    $expenses_daily_values[$date_str] = 0;
    $expenses_daily_by_category[$date_str] = []; // Inițializăm pentru fiecare zi
    $current_date_dt->modify('+1 day');
}

// 1. Date pentru graficul Total (Venituri și Cheltuieli pe zile)
$sql_revenues_daily = "
    SELECT DATE(data_emiterii) as date, SUM(valoare_totala) as total
    FROM facturi
    WHERE status = 'Platita' AND data_emiterii BETWEEN ? AND ?
    GROUP BY DATE(data_emiterii)
    ORDER BY date ASC
";
$stmt_revenues_daily = $conn->prepare($sql_revenues_daily);
if ($stmt_revenues_daily) {
    $stmt_revenues_daily->bind_param("ss", $start_date_month, $end_date_month);
    $stmt_revenues_daily->execute();
    $result_revenues_daily = $stmt_revenues_daily->get_result();
    while ($row = $result_revenues_daily->fetch_assoc()) {
        $revenues_daily_values[$row['date']] = (float)$row['total'];
    }
    $stmt_revenues_daily->close();
}

$sql_expenses_daily = "
    SELECT data_cheltuielii as date, SUM(suma) as total
    FROM cheltuieli
    WHERE data_cheltuielii BETWEEN ? AND ?
    GROUP BY data_cheltuielii
    ORDER BY date ASC
";
$stmt_expenses_daily = $conn->prepare($sql_expenses_daily);
if ($stmt_expenses_daily) {
    $stmt_expenses_daily->bind_param("ss", $start_date_month, $end_date_month);
    $stmt_expenses_daily->execute();
    $result_expenses_daily = $stmt_expenses_daily->get_result();
    while ($row = $result_expenses_daily->fetch_assoc()) {
        $expenses_daily_values[$row['date']] = (float)$row['total'];
    }
    $stmt_expenses_daily->close();
}

// 2. Date pentru grafice pe Categorii (Cheltuieli totale pe categorii și pe zile)
$categorii_cheltuieli_distincte = []; // Toate categoriile găsite în DB
$sql_all_categories = "SELECT DISTINCT categorie FROM cheltuieli WHERE categorie IS NOT NULL ORDER BY categorie ASC";
$result_all_categories = $conn->query($sql_all_categories);
if ($result_all_categories) {
    while($row = $result_all_categories->fetch_assoc()) {
        $categorii_cheltuieli_distincte[] = $row['categorie'];
    }
}

// Inițializăm expenses_daily_by_category cu 0 pentru fiecare categorie în fiecare zi
foreach ($expenses_daily_by_category as $date => &$categories_for_day) {
    foreach ($categorii_cheltuieli_distincte as $cat) {
        $categories_for_day[$cat] = 0;
    }
}
unset($categories_for_day); // Unset reference

// Preluăm cheltuielile pe categorii pentru totalul lunar (Donut Chart)
$sql_categories_expenses_monthly = "
    SELECT categorie, SUM(suma) as total
    FROM cheltuieli
    WHERE data_cheltuielii BETWEEN ? AND ? AND categorie IS NOT NULL
    GROUP BY categorie
    ORDER BY total DESC
";
$stmt_categories_expenses_monthly = $conn->prepare($sql_categories_expenses_monthly);
if ($stmt_categories_expenses_monthly) {
    $stmt_categories_expenses_monthly->bind_param("ss", $start_date_month, $end_date_month);
    $stmt_categories_expenses_monthly->execute();
    $result_categories_expenses_monthly = $stmt_categories_expenses_monthly->get_result();
    while ($row = $result_categories_expenses_monthly->fetch_assoc()) {
        $expenses_categories_monthly_totals[$row['categorie']] = (float)$row['total'];
    }
    $stmt_categories_expenses_monthly->close();
}

// Preluăm cheltuielile pe zile și categorii (Stacked Bar Chart)
$sql_expenses_daily_by_category = "
    SELECT data_cheltuielii as date, categorie, SUM(suma) as total
    FROM cheltuieli
    WHERE data_cheltuielii BETWEEN ? AND ? AND categorie IS NOT NULL
    GROUP BY data_cheltuielii, categorie
    ORDER BY data_cheltuielii ASC
";
$stmt_expenses_daily_by_category = $conn->prepare($sql_expenses_daily_by_category);
if ($stmt_expenses_daily_by_category) {
    $stmt_expenses_daily_by_category->bind_param("ss", $start_date_month, $end_date_month);
    $stmt_expenses_daily_by_category->execute();
    $result_expenses_daily_by_category = $stmt_expenses_daily_by_category->get_result();
    while ($row = $result_expenses_daily_by_category->fetch_assoc()) {
        $expenses_daily_by_category[$row['date']][$row['categorie']] = (float)$row['total'];
    }
    $stmt_expenses_daily_by_category->close();
}

$conn->close();

// Numele lunilor în română
$month_names = [
    '01' => 'Ianuarie', '02' => 'Februarie', '03' => 'Martie', '04' => 'Aprilie',
    '05' => 'Mai', '06' => 'Iunie', '07' => 'Iulie', '08' => 'August',
    '09' => 'Septembrie', '10' => 'Octombrie', '11' => 'Noiembrie', '12' => 'Decembrie'
];
?>

<title>NTS TOUR | Cash-flow Vizual</title>

<!-- Include ApexCharts library -->
<script src="assets/plugins/apexchart/apexcharts.min.js"></script>
<!-- Optional: Include custom ApexCharts configurations if needed, but we'll define options here -->
<!-- <script src="assets/plugins/apexchart/apex-custom-chart.js"></script> -->

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

    /* Stiluri specifice graficelor */
    .chart-container {
        position: relative;
        height: 400px; /* Înălțime fixă pentru grafice */
        width: 100%;
        margin-bottom: 2rem;
    }
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
                        <li class="breadcrumb-item active" aria-current="page">Cash-flow Vizual</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Cash-flow Vizual pentru <?php echo $month_names[$selected_month] . ' ' . $selected_year; ?></h4>
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

                        <!-- Filtre Lună/An și Tip Vizualizare -->
                        <div class="row mb-4 filter-section">
                            <div class="col-md-3 mb-3">
                                <label for="selectMonth" class="form-label">Selectează Luna:</label>
                                <select class="form-select" id="selectMonth">
                                    <?php foreach ($month_names as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php if ($selected_month == $num) echo 'selected'; ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="selectYear" class="form-label">Selectează Anul:</label>
                                <select class="form-select" id="selectYear">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php if ($selected_year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="viewType" class="form-label">Tip Vizualizare:</label>
                                <select class="form-select" id="viewType">
                                    <option value="total" <?php if ($view_type == 'total') echo 'selected'; ?>>Total Venituri/Cheltuieli</option>
                                    <option value="categorii" <?php if ($view_type == 'categorii') echo 'selected'; ?>>Cheltuieli pe Categorii</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="applyFilterBtn">Aplică Filtru</button>
                            </div>
                        </div>

                        <!-- Grafice -->
                        <div class="row mt-4">
                            <?php if ($view_type == 'total'): ?>
                            <div class="col-12">
                                <h5 class="card-title">Evoluția Zilnică Venituri și Cheltuieli</h5>
                                <div class="chart-container">
                                    <div id="cashFlowLineChart"></div>
                                </div>
                            </div>
                            <?php elseif ($view_type == 'categorii'): ?>
                            <div class="col-12 col-lg-6">
                                <h5 class="card-title">Distribuția Cheltuielilor pe Categorii (Total Lunar)</h5>
                                <div class="chart-container">
                                    <div id="expensesDonutChart"></div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <h5 class="card-title">Cheltuieli pe Categorii (Evoluție Zilnică)</h5>
                                <div class="chart-container">
                                    <div id="expensesStackedBarChart"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<!-- Include ApexCharts library -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectMonth = document.getElementById('selectMonth');
    const selectYear = document.getElementById('selectYear');
    const viewType = document.getElementById('viewType');
    const applyFilterBtn = document.getElementById('applyFilterBtn');

    applyFilterBtn.addEventListener('click', function() {
        const month = selectMonth.value;
        const year = selectYear.value;
        const type = viewType.value;
        window.location.href = `cash-flow-vizual.php?month=${month}&year=${year}&view_type=${type}`;
    });

    const currentViewType = "<?php echo $view_type; ?>";
    const labelsDays = <?php echo json_encode(array_values($labels_days)); ?>;
    const selectedYear = "<?php echo $selected_year; ?>";

    // Culori dinamice pentru grafice (pentru categorii)
    const dynamicColors = [
        '#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0',
        '#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B',
        '#4ecdc4', '#c7f464', '#81D4FA', '#fd6a6a', '#546E7A',
        '#2b908f', '#f9a3a4', '#90ee7e', '#fa4443', '#69d2e7'
    ];

    if (currentViewType === 'total') {
        const revenuesDailyValues = <?php echo json_encode(array_values($revenues_daily_values)); ?>;
        const expensesDailyValues = <?php echo json_encode(array_values($expenses_daily_values)); ?>;

        const optionsLineChart = {
            series: [
                {
                    name: 'Venituri',
                    data: revenuesDailyValues
                },
                {
                    name: 'Cheltuieli',
                    data: expensesDailyValues
                }
            ],
            chart: {
                height: 400,
                type: 'line',
                foreColor: '#e0e0e0', // Text alb
                background: '#2a3042', // Fundal card
                zoom: { enabled: false },
                toolbar: { show: false },
                dropShadow: {
                    enabled: true,
                    top: 3,
                    left: 14,
                    blur: 4,
                    opacity: 0.12
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 4,
                colors: ['#00E396', '#FF4560'], // Culori markeri
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { size: 6 }
            },
            colors: ['#00E396', '#FF4560'], // Culori linii
            grid: {
                show: true,
                borderColor: 'rgba(255, 255, 255, 0.1)', // Culoare grid
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            },
            xaxis: {
                categories: labelsDays,
                labels: {
                    style: { colors: '#e0e0e0' } // Culoare etichete X
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: { enabled: false }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toFixed(0) + ' RON';
                    },
                    style: { colors: '#e0e0e0' } // Culoare etichete Y
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            legend: {
                labels: { colors: '#ffffff' }, // Culoare text legendă
                position: 'bottom'
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (val) {
                        return val.toFixed(2) + ' RON';
                    }
                }
            }
        };
        const chartLine = new ApexCharts(document.querySelector("#cashFlowLineChart"), optionsLineChart);
        chartLine.render();

    } else if (currentViewType === 'categorii') {
        const categoriesExpensesMonthlyTotals = <?php echo json_encode($expenses_categories_monthly_totals); ?>;
        const categories = <?php echo json_encode(array_keys($expenses_categories_monthly_totals)); ?>;
        const expensesTotals = <?php echo json_encode(array_values($expenses_categories_monthly_totals)); ?>;

        // Grafic Donut pentru distribuția cheltuielilor pe categorii
        const optionsDonutChart = {
            series: expensesTotals,
            labels: categories,
            chart: {
                height: 400,
                type: 'donut',
                foreColor: '#e0e0e0',
                background: '#2a3042',
            },
            colors: dynamicColors, // Culori dinamice pentru segmente
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '16px',
                                color: '#e0e0e0',
                                offsetY: -10
                            },
                            value: {
                                show: true,
                                fontSize: '22px',
                                color: '#ffffff',
                                offsetY: 10,
                                formatter: function (val) {
                                    return parseFloat(val).toFixed(2) + ' RON';
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total',
                                fontSize: '16px',
                                color: '#e0e0e0',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(2) + ' RON';
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                labels: { colors: '#ffffff' },
                position: 'bottom',
                formatter: function(val, opts) {
                    return val + " - " + opts.w.globals.series[opts.seriesIndex].toFixed(2) + ' RON';
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (val) {
                        return parseFloat(val).toFixed(2) + ' RON';
                    }
                }
            }
        };
        const chartDonut = new ApexCharts(document.querySelector("#expensesDonutChart"), optionsDonutChart);
        chartDonut.render();

        // Grafic Bară Stivuită pentru cheltuieli pe categorii (zilnic)
        const expensesDailyByCategoryData = <?php echo json_encode($expenses_daily_by_category); ?>;
        const allCategories = <?php echo json_encode($categorii_cheltuieli_distincte); ?>;
        const dailyLabels = labelsDays; // Zilele lunii

        const stackedBarSeries = [];
        allCategories.forEach((category, index) => {
            const dataForCategory = dailyLabels.map(dateLabel => {
                const fullDate = dateLabel + ' ' + selectedYear;
                const dateKey = new Date(fullDate).toISOString().substring(0,10);
                return expensesDailyByCategoryData[dateKey][category] || 0;
            });
            stackedBarSeries.push({
                name: category,
                data: dataForCategory,
                color: dynamicColors[index % dynamicColors.length]
            });
        });

        const optionsStackedBarChart = {
            series: stackedBarSeries,
            chart: {
                height: 400,
                type: 'bar',
                stacked: true,
                foreColor: '#e0e0e0',
                background: '#2a3042',
                zoom: { enabled: false },
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 4,
                    borderRadiusApplication: 'around',
                    borderRadiusWhenStacked: 'last',
                    columnWidth: '70%'
                },
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: dailyLabels,
                labels: {
                    style: { colors: '#e0e0e0' }
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: { enabled: false }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toFixed(0) + ' RON';
                    },
                    style: { colors: '#e0e0e0' }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            grid: {
                show: true,
                borderColor: 'rgba(255, 255, 255, 0.1)',
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            },
            legend: {
                labels: { colors: '#ffffff' },
                position: 'bottom'
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (val) {
                        return val.toFixed(2) + ' RON';
                    }
                }
            }
        };
        const chartStackedBar = new ApexCharts(document.querySelector("#expensesStackedBarChart"), optionsStackedBarChart);
        chartStackedBar.render();
    }
});
</script>
