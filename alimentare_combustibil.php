<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifica autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// Logica de Adăugare/Editare/Ștergere
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        $id_vehicul = $_POST['id_vehicul'] ?? null;
        $data_alimentare = $_POST['data_alimentare'] ?? null;
        $cantitate_litri = $_POST['cantitate_litri'] ?? null;
        $pret_litru = $_POST['pret_litru'] ?? null;
        $kilometraj_curent = $_POST['kilometraj_curent'] ?? null;
        $locatie_alimentare = $_POST['locatie_alimentare'] ?? null;
        $tip_combustibil = $_POST['tip_combustibil'] ?? null;
        $observatii = $_POST['observatii'] ?? null;
        $id_alimentare = $_POST['id_alimentare'] ?? null;

        // Calcul cost_total
        $cost_total = ($cantitate_litri && $pret_litru) ? ($cantitate_litri * $pret_litru) : 0;

        // Validare de bază
        if (empty($id_vehicul) || empty($data_alimentare) || empty($cantitate_litri) || empty($pret_litru) || empty($kilometraj_curent)) {
            $_SESSION['error_message'] = "Toate câmpurile obligatorii trebuie completate.";
            header("Location: alimentare_combustibil.php");
            exit();
        }

        // Adăugare Alimentare
        if ($action === 'add') {
            $sql = "INSERT INTO consum_combustibil (id_vehicul, data_alimentare, cantitate_litri, pret_litru, cost_total, kilometraj_curent, locatie_alimentare, tip_combustibil, observatii) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isdddisss", $id_vehicul, $data_alimentare, $cantitate_litri, $pret_litru, $cost_total, $kilometraj_curent, $locatie_alimentare, $tip_combustibil, $observatii);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Alimentare adăugată cu succes!";
                } else {
                    $_SESSION['error_message'] = "Eroare la adăugarea alimentării: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Eroare la pregătirea interogării (add): " . $conn->error;
            }
        }
        // Editare Alimentare
        elseif ($action === 'edit' && $id_alimentare) {
            $sql = "UPDATE consum_combustibil SET id_vehicul = ?, data_alimentare = ?, cantitate_litri = ?, pret_litru = ?, cost_total = ?, kilometraj_curent = ?, locatie_alimentare = ?, tip_combustibil = ?, observatii = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isdddisssi", $id_vehicul, $data_alimentare, $cantitate_litri, $pret_litru, $cost_total, $kilometraj_curent, $locatie_alimentare, $tip_combustibil, $observatii, $id_alimentare);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Alimentare actualizată cu succes!";
                } else {
                    $_SESSION['error_message'] = "Eroare la actualizarea alimentării: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Eroare la pregătirea interogării (edit): " . $conn->error;
            }
        }
        // Ștergere Alimentare
        elseif ($action === 'delete' && $id_alimentare) {
            $sql = "DELETE FROM consum_combustibil WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $id_alimentare);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Alimentare ștearsă cu succes!";
                } else {
                    $_SESSION['error_message'] = "Eroare la ștergerea alimentării: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Eroare la pregătirea interogării (delete): " . $conn->error;
            }
        }
    }
    header("Location: alimentare_combustibil.php"); // Redirecționare pentru a preveni re-trimiterea formei
    exit();
}

// Preluare date alimentări pentru afișare
$alimentari = [];
$sql_select = "SELECT cc.*, v.model, v.numar_inmatriculare FROM consum_combustibil cc JOIN vehicule v ON cc.id_vehicul = v.id ORDER BY cc.data_alimentare DESC";
$result_select = $conn->query($sql_select);
if ($result_select) {
    while ($row = $result_select->fetch_assoc()) {
        $alimentari[] = $row;
    }
} else {
    $error_message .= "Eroare la preluarea alimentărilor: " . $conn->error;
}

// Preluare lista vehicule pentru dropdown-uri
$vehicule_list = [];
$stmt_vehicule = $conn->prepare("SELECT id, model, numar_inmatriculare FROM vehicule ORDER BY model ASC");
if ($stmt_vehicule) {
    $stmt_vehicule->execute();
    $result_vehicule = $stmt_vehicule->get_result();
    while ($row = $result_vehicule->fetch_assoc()) {
        $vehicule_list[] = $row;
    }
    $stmt_vehicule->close();
}

$conn->close();
?>

<title>NTS TOUR | Alimentare Combustibil</title>

<style>
    /* Folosește stilurile din template/header.php sau adaugă aici stiluri specifice pentru tema ta */
    /* Ex: */
    body, html, .main-content { color: #ffffff !important; }
    .card { background-color: #2a3042 !important; color: #e0e0e0 !important; }
    .form-control, .form-select { background-color: #1a2035 !important; color: #e0e0e0 !important; border: 1px solid rgba(255, 255, 255, 0.2) !important; }
    .table { color: #e0e0e0 !important; background-color: #2a3042 !important; }
    .table th, .table td { border-color: rgba(255, 255, 255, 0.1) !important; }
    .table thead th { background-color: #3b435a !important; }
    .btn-primary, .btn-danger, .btn-warning { border-radius: 0.5rem !important; }
    .alert-success { background-color: #2c5234 !important; border-color: #4caf50 !important; }
    .alert-danger { background-color: #5c2c31 !important; border-color: #f44336 !important; }
</style>

<main class="main-wrapper">
    <div class="main-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Alimentare Combustibil</div>
            <div class="ps-3">
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Gestionare Alimentare Combustibil</h4>
                        <hr>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <div class="p-4 border rounded">
                            <form action="alimentare_combustibil.php" method="POST">
                                <input type="hidden" name="action" id="formAction" value="add">
                                <input type="hidden" name="id_alimentare" id="alimentareId">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="id_vehicul" class="form-label">Vehicul</label>
                                        <select class="form-select" id="id_vehicul" name="id_vehicul" required>
                                            <option value="">Selectează Vehiculul</option>
                                            <?php foreach ($vehicule_list as $veh): ?>
                                                <option value="<?php echo htmlspecialchars($veh['id']); ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="data_alimentare" class="form-label">Dată Alimentare</label>
                                        <input type="datetime-local" class="form-control" id="data_alimentare" name="data_alimentare" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cantitate_litri" class="form-label">Cantitate (Litrii)</label>
                                        <input type="number" step="0.01" class="form-control" id="cantitate_litri" name="cantitate_litri" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="pret_litru" class="form-label">Preț pe Litru (RON)</label>
                                        <input type="number" step="0.01" class="form-control" id="pret_litru" name="pret_litru" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="kilometraj_curent" class="form-label">Kilometraj Curent</label>
                                        <input type="number" class="form-control" id="kilometraj_curent" name="kilometraj_curent" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="locatie_alimentare" class="form-label">Locație Alimentare</label>
                                        <input type="text" class="form-control" id="locatie_alimentare" name="locatie_alimentare">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tip_combustibil" class="form-label">Tip Combustibil</label>
                                        <select class="form-select" id="tip_combustibil" name="tip_combustibil">
                                            <option value="Diesel">Diesel</option>
                                            <option value="Benzina">Benzină</option>
                                            <option value="GPL">GPL</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="observatii" class="form-label">Observații</label>
                                        <textarea class="form-control" id="observatii" name="observatii" rows="3"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">Adaugă Alimentare</button>
                                        <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display:none;">Anulează Editarea</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <h4 class="card-title mt-5">Lista Alimentărilor</h4>
                        <hr>
                        <?php if (empty($alimentari)): ?>
                            <div class="alert alert-info">Nu există alimentări înregistrate.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Dată</th>
                                            <th>Cantitate (L)</th>
                                            <th>Preț/Litru</th>
                                            <th>Cost Total</th>
                                            <th>Kilometraj</th>
                                            <th>Locație</th>
                                            <th>Tip Comb.</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alimentari as $al): ?>
                                            <tr>
                                                <td data-label="Vehicul:"><?php echo htmlspecialchars($al['model'] . ' (' . $al['numar_inmatriculare'] . ')'); ?></td>
                                                <td data-label="Dată:"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($al['data_alimentare']))); ?></td>
                                                <td data-label="Cantitate (L):"><?php echo htmlspecialchars(number_format($al['cantitate_litri'], 2, ',', '.')) . ' L'; ?></td>
                                                <td data-label="Preț/Litru:"><?php echo htmlspecialchars(number_format($al['pret_litru'], 2, ',', '.')) . ' RON'; ?></td>
                                                <td data-label="Cost Total:"><?php echo htmlspecialchars(number_format($al['cost_total'], 2, ',', '.')) . ' RON'; ?></td>
                                                <td data-label="Kilometraj:"><?php echo htmlspecialchars(number_format($al['kilometraj_curent'], 0, ',', '.')) . ' km'; ?></td>
                                                <td data-label="Locație:"><?php echo htmlspecialchars($al['locatie_alimentare'] ?? 'N/A'); ?></td>
                                                <td data-label="Tip Comb.:"><?php echo htmlspecialchars($al['tip_combustibil'] ?? 'N/A'); ?></td>
                                                <td data-label="Acțiuni:">
                                                    <button class="btn btn-warning btn-sm edit-btn"
                                                            data-id="<?php echo htmlspecialchars($al['id']); ?>"
                                                            data-id_vehicul="<?php echo htmlspecialchars($al['id_vehicul']); ?>"
                                                            data-data_alimentare="<?php echo htmlspecialchars($al['data_alimentare']); ?>"
                                                            data-cantitate_litri="<?php echo htmlspecialchars($al['cantitate_litri']); ?>"
                                                            data-pret_litru="<?php echo htmlspecialchars($al['pret_litru']); ?>"
                                                            data-kilometraj_curent="<?php echo htmlspecialchars($al['kilometraj_curent']); ?>"
                                                            data-locatie_alimentare="<?php echo htmlspecialchars($al['locatie_alimentare']); ?>"
                                                            data-tip_combustibil="<?php echo htmlspecialchars($al['tip_combustibil']); ?>"
                                                            data-observatii="<?php echo htmlspecialchars($al['observatii']); ?>">
                                                        Editează
                                                    </button>
                                                    <form action="alimentare_combustibil.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Ești sigur că vrei să ștergi această alimentare?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_alimentare" value="<?php echo htmlspecialchars($al['id']); ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Șterge</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'template/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const formAction = document.getElementById('formAction');
    const alimentareId = document.getElementById('alimentareId');
    const idVehiculInput = document.getElementById('id_vehicul');
    const dataAlimentareInput = document.getElementById('data_alimentare');
    const cantitateLitriInput = document.getElementById('cantitate_litri');
    const pretLitruInput = document.getElementById('pret_litru');
    const kilometrajCurentInput = document.getElementById('kilometraj_curent');
    const locatieAlimentareInput = document.getElementById('locatie_alimentare');
    const tipCombustibilInput = document.getElementById('tip_combustibil');
    const observatiiInput = document.getElementById('observatii');
    const submitBtn = document.getElementById('submitBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset; // Accesează toate atributele data-*

            alimentareId.value = data.id;
            idVehiculInput.value = data.id_vehicul;
            // Formatarea datei pentru input[type="datetime-local"]
            const formattedDate = new Date(data.data_alimentare).toISOString().slice(0, 16);
            dataAlimentareInput.value = formattedDate;
            cantitateLitriInput.value = data.cantitate_litri;
            pretLitruInput.value = data.pret_litru;
            kilometrajCurentInput.value = data.kilometraj_curent;
            locatieAlimentareInput.value = data.locatie_alimentare;
            tipCombustibilInput.value = data.tip_combustibil;
            observatiiInput.value = data.observatii;

            formAction.value = 'edit';
            submitBtn.textContent = 'Actualizează Alimentare';
            cancelEditBtn.style.display = 'inline-block';

            // Scrollează înapoi la formular
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    cancelEditBtn.addEventListener('click', function() {
        form.reset(); // Resetează toate câmpurile formularului
        formAction.value = 'add';
        alimentareId.value = '';
        submitBtn.textContent = 'Adaugă Alimentare';
        cancelEditBtn.style.display = 'none';
        // Asigură că select-urile sunt resetate la prima opțiune sau la 'Selectează Vehiculul'
        idVehiculInput.value = "";
        tipCombustibilInput.value = "Diesel"; // sau valoarea implicită
    });
});
</script>