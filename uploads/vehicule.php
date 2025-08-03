<?php
require_once 'db_connect.php';

// --- Logica pentru statistici ---
$stats = ['Disponibil' => 0, 'În cursă' => 0, 'În service' => 0, 'Total' => 0];
$sql_stats = "SELECT status, COUNT(*) as count FROM vehicule GROUP BY status";
$result_stats = $conn->query($sql_stats);
if ($result_stats && $result_stats->num_rows > 0) {
    while($row_stat = $result_stats->fetch_assoc()) {
        if (isset($stats[$row_stat['status']])) {
            $stats[$row_stat['status']] = $row_stat['count'];
        }
    }
}
$stats['Total'] = array_sum($stats);

// --- Logica pentru a prelua vehiculele si actele lor ---
$sql = "
    SELECT 
        v.*,
        (SELECT d.data_expirare FROM documente d WHERE d.id_vehicul = v.id AND d.tip_document = 'ITP' ORDER BY d.data_expirare DESC LIMIT 1) as data_expirare_itp,
        (SELECT d.data_expirare FROM documente d WHERE d.id_vehicul = v.id AND d.tip_document = 'RCA' ORDER BY d.data_expirare DESC LIMIT 1) as data_expirare_rca,
        (SELECT d.data_expirare FROM documente d WHERE d.id_vehicul = v.id AND d.tip_document = 'Rovinieta' ORDER BY d.data_expirare DESC LIMIT 1) as data_expirare_rovinieta
    FROM 
        vehicule v
    ORDER BY 
        v.id DESC
";
$result = $conn->query($sql);

// --- Logica pentru a genera dinamic butoanele de filtrare pe tip ---
$tipuri_vehicule = [];
$sql_tipuri = "SELECT DISTINCT tip FROM vehicule WHERE tip IS NOT NULL AND tip != '' ORDER BY tip ASC";
$result_tipuri = $conn->query($sql_tipuri);
if ($result_tipuri && $result_tipuri->num_rows > 0) {
    while($row_tip = $result_tipuri->fetch_assoc()) {
        $tipuri_vehicule[] = $row_tip['tip'];
    }
}


require_once 'template/header.php'; 
?>

<title>NTS TOUR | Flotă Vehicule</title>

<style>
/* Stiluri pentru noul layout al cardului */
.details-card .card-body { padding: 0; }
.details-card-img-container { padding: 1rem; }
.details-card-info { padding: 1rem; }
.document-info { list-style: none; padding-left: 0; font-size: 0.9rem; }
.document-info li { display: flex; justify-content: space-between; padding: .5rem 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
.document-info li:last-child { border-bottom: none; }
.text-danger-custom { color: #ff6b6b !important; font-weight: bold; }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Flotă</div>
            <div class="ps-3"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 p-0"><li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li><li class="breadcrumb-item active" aria-current="page">Vehicule</li></ol></nav></div>
        </div>
        <div class="row">
             <div class="col-12 col-lg-3"><div class="card stat-card border-left-success"><div class="card-body"><div class="d-flex align-items-center"><div><p class="mb-0 text-secondary">Disponibile</p><h4 class="my-1"><?php echo $stats['Disponibil']; ?></h4></div><div class="widgets-icons bg-light-success text-success ms-auto"><i class="bx bxs-car"></i></div></div></div></div></div>
            <div class="col-12 col-lg-3"><div class="card stat-card border-left-warning"><div class="card-body"><div class="d-flex align-items-center"><div><p class="mb-0 text-secondary">În Cursă</p><h4 class="my-1"><?php echo $stats['În cursă']; ?></h4></div><div class="widgets-icons bg-light-warning text-warning ms-auto"><i class="bx bxs-stopwatch"></i></div></div></div></div></div>
            <div class="col-12 col-lg-3"><div class="card stat-card border-left-danger"><div class="card-body"><div class="d-flex align-items-center"><div><p class="mb-0 text-secondary">În Service</p><h4 class="my-1"><?php echo $stats['În service']; ?></h4></div><div class="widgets-icons bg-light-danger text-danger ms-auto"><i class="bx bxs-wrench"></i></div></div></div></div></div>
            <div class="col-12 col-lg-3"><div class="card stat-card border-left-info"><div class="card-body"><div class="d-flex align-items-center"><div><p class="mb-0 text-secondary">Total Vehicule</p><h4 class="my-1"><?php echo $stats['Total']; ?></h4></div><div class="widgets-icons bg-light-info text-info ms-auto"><i class="bx bxs-collection"></i></div></div></div></div></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-lg-4 col-xl-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Caută după număr sau model...">
                        </div>
                    </div>
                    <div class="col-lg-8 col-xl-8">
                        <div class="d-flex flex-wrap justify-content-lg-start gap-2">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-filter active" data-filter-group="status" data-filter="all">Toate Statusurile</button>
                                <button type="button" class="btn btn-outline-primary btn-filter" data-filter-group="status" data-filter="Disponibil">Disponibile</button>
                                <button type="button" class="btn btn-outline-primary btn-filter" data-filter-group="status" data-filter="În cursă">În Cursă</button>
                            </div>
                             <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-filter active" data-filter-group="tip" data-filter="all">Toate Tipurile</button>
                                <?php foreach ($tipuri_vehicule as $tip): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-filter" data-filter-group="tip" data-filter="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        
        <div class="row row-cols-1 row-cols-xl-2 g-4" id="vehicle-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $status_class = 'bg-secondary';
                    if ($row['status'] == 'Disponibil') { $status_class = 'bg-success'; } elseif ($row['status'] == 'În service') { $status_class = 'bg-danger'; } elseif ($row['status'] == 'În cursă') { $status_class = 'bg-warning text-dark'; }
            ?>
            <div class="col vehicle-card" data-status="<?php echo htmlspecialchars($row['status']); ?>" data-tip="<?php echo htmlspecialchars($row['tip']); ?>" data-search="<?php echo strtolower(htmlspecialchars($row['numar_inmatriculare']) . ' ' . htmlspecialchars($row['model'])); ?>">
                <div class="card h-100 details-card">
                    <div class="card-body">
                        <div class="row g-0">
                            <div class="col-md-5 details-card-img-container text-center">
                                <img src="<?php echo !empty($row['imagine_path']) ? htmlspecialchars($row['imagine_path']) : 'assets/images/vehicles/placeholder.jpg'; ?>" class="img-fluid rounded" alt="...">
                                <div class="mt-3"><span class="badge <?php echo $status_class; ?> fs-6"><?php echo htmlspecialchars($row['status']); ?></span></div>
                            </div>
                            <div class="col-md-7">
                                <div class="details-card-info d-flex flex-column h-100">
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['model']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($row['numar_inmatriculare']); ?></p>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Kilometraj:</span><strong><?php echo number_format($row['kilometraj'], 0, ',', '.'); ?> km</strong></div>
                                        <h6 class="mt-3">Situație Acte</h6>
                                        <ul class="document-info">
                                            <?php
                                                $today = new DateTime();
                                                $itp_exp = $row['data_expirare_itp'] ? new DateTime($row['data_expirare_itp']) : null; $itp_class = ($itp_exp && $itp_exp < $today) ? 'text-danger-custom' : '';
                                                $rca_exp = $row['data_expirare_rca'] ? new DateTime($row['data_expirare_rca']) : null; $rca_class = ($rca_exp && $rca_exp < $today) ? 'text-danger-custom' : '';
                                                $rov_exp = $row['data_expirare_rovinieta'] ? new DateTime($row['data_expirare_rovinieta']) : null; $rov_class = ($rov_exp && $rov_exp < $today) ? 'text-danger-custom' : '';
                                            ?>
                                            <li><span>ITP</span> <strong class="<?php echo $itp_class; ?>"><?php echo $itp_exp ? $itp_exp->format('d.m.Y') : 'N/A'; ?></strong></li>
                                            <li><span>Asigurare RCA</span> <strong class="<?php echo $rca_class; ?>"><?php echo $rca_exp ? $rca_exp->format('d.m.Y') : 'N/A'; ?></strong></li>
                                            <li><span>Rovinietă</span> <strong class="<?php echo $rov_class; ?>"><?php echo $rov_exp ? $rov_exp->format('d.m.Y') : 'N/A'; ?></strong></li>
                                        </ul>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-auto">
                                        <a href="editeaza-vehicul.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Editare</a>
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reportProblemModal" data-id="<?php echo $row['id']; ?>">Raportează</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?php echo $row['id']; ?>">Șterge</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } } else { echo '<div class="col-12"><div class="alert alert-info">Nu există vehicule în flotă.</div></div>'; } $conn->close(); ?>
        </div>
        <div id="no-results" class="col-12 text-center" style="display: none;"><h4 class="text-muted mt-5">Nu au fost găsite vehicule care să corespundă criteriilor.</h4></div>

        <div class="modal fade" id="confirmDeleteModal" tabindex="-1"> ... </div>
        <div class="modal fade" id="reportProblemModal" tabindex="-1"> ... </div>
    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const filterButtons = document.querySelectorAll('.btn-filter');
    const allCards = document.querySelectorAll('.vehicle-card');
    const noResultsMessage = document.getElementById('no-results');
    let filters = { status: 'all', tip: 'all' };

    function filterVehicles() {
        const searchText = searchInput.value.toLowerCase();
        let visibleCount = 0;
        allCards.forEach(card => {
            const statusMatch = (filters.status === 'all' || filters.status === card.dataset.status);
            const tipMatch = (filters.tip === 'all' || filters.tip === card.dataset.tip);
            const searchMatch = (card.dataset.search.includes(searchText));
            if (statusMatch && tipMatch && searchMatch) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterVehicles);
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const group = this.dataset.filterGroup;
            filters[group] = this.dataset.filter;
            document.querySelectorAll(`.btn-filter[data-filter-group="${group}"]`).forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            filterVehicles();
        });
    });

    // Logica pentru modale
    var confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if(confirmDeleteModal) { /* ... */ }
    var reportProblemModal = document.getElementById('reportProblemModal');
    if(reportProblemModal) { /* ... */ }
});
</script>