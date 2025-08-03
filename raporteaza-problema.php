<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Preluăm lista de vehicule pentru dropdown
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

// Tipurile de probleme disponibile (extinse)
$tipuri_probleme = [
    'Mecanica - Motor',
    'Mecanica - Transmisie',
    'Mecanica - Franare',
    'Mecanica - Suspensie',
    'Electrica - Baterie',
    'Electrica - Lumini',
    'Electrica - Sistem Pornire',
    'Electrica - Climatizare',
    'Estetica - Caroserie',
    'Estetica - Interior',
    'Documente - Expirate',
    'Documente - Lipsa',
    'Anvelope - Uzura',
    'Anvelope - Presiune',
    'Administrativ - Amenzi',
    'Administrativ - Licente',
    'Siguranta - Centuri',
    'Siguranta - Airbaguri',
    'S.S.M.D. - Echipament', // Securitate și Sănătate în Muncă și Situații de Urgență
    'Altele'
];
?>

<title>NTS TOUR | Raportează Problemă</title>

<style>
    /* Stiluri specifice formularului de raportare problemă */
    .form-section-card {
        background-color: #2a3042;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
    }
    .form-section-card h5 {
        color: #ffffff;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .form-section-card .form-label {
        color: #e0e0e0;
        font-weight: 500;
    }
    .form-section-card .form-control,
    .form-section-card .form-select {
        background-color: #1a2035;
        color: #e0e0e0;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
    }
    .form-section-card .form-control::placeholder {
        color: #b0b0b0;
        opacity: 0.7;
    }
    .form-section-card .form-control:focus,
    .form-section-card .form-select:focus {
        border-color: #6a90f1;
        box-shadow: 0 0 0 0.25rem rgba(106, 144, 241, 0.25);
    }
    .btn-submit-problem {
        background-color: #dc3545; /* Roșu pentru raportare problemă */
        border-color: #dc3545;
        color: #ffffff;
        border-radius: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-weight: bold;
        transition: transform 0.2s ease-in-out;
    }
    .btn-submit-problem:hover {
        transform: translateY(-2px);
    }
    .btn-secondary {
        border-radius: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-weight: bold;
    }
</style>

<main class="main-wrapper">
    <div class="main-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Mentenanță</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Raportează Problemă</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Raportează o Problemă Nouă</h4>
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

                        <form id="reportProblemForm" action="report_problem.php" method="POST">
                            <div class="form-section-card">
                                <h5>Detalii Vehicul și Raportor</h5>
                                <div class="mb-3">
                                    <label for="selectVehicle" class="form-label">Selectează Vehiculul:</label>
                                    <select class="form-select" id="selectVehicle" name="id_vehicul" required>
                                        <option value="">Alege un vehicul</option>
                                        <?php foreach ($vehicule_list as $veh): ?>
                                            <option value="<?php echo $veh['id']; ?>"><?php echo htmlspecialchars($veh['model'] . ' (' . $veh['numar_inmatriculare'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="reporterName" class="form-label">Numele Raportorului:</label>
                                    <input type="text" class="form-control" id="reporterName" name="reporter_name" placeholder="Numele tău" required>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <h5>Detalii Problemă</h5>
                                <div class="mb-3">
                                    <label for="problemType" class="form-label">Tipul Problemei:</label>
                                    <select class="form-select" id="problemType" name="problem_type" required>
                                        <option value="">Selectează tipul problemei</option>
                                        <?php foreach ($tipuri_probleme as $tip): ?>
                                            <option value="<?php echo htmlspecialchars($tip); ?>"><?php echo htmlspecialchars($tip); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="problemDescription" class="form-label">Descriere Detaliată a Problemei:</label>
                                    <textarea class="form-control" id="problemDescription" name="problem_description" rows="5" placeholder="Descrie problema cât mai detaliat (ex: motorul scoate un zgomot ciudat la peste 80km/h, lumina de frână dreapta spate nu funcționează)" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="problemRating" class="form-label">Gravitatea Problemei (1-5, unde 5 este foarte grav):</label>
                                    <input type="number" class="form-control" id="problemRating" name="problem_rating" min="1" max="5" value="3" required>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-submit-problem">Trimite Raportul</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once 'template/footer.php'; ?>
