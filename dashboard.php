<?php
session_start();
require_once 'config/database.php';

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$user_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$user_role = $_SESSION['user_role'];
$employee_code = $_SESSION['employee_code'];
?>
<!doctype html>
<html lang="ro" data-bs-theme="blue-theme">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NTS TOUR | Dashboard</title>
    <link rel="icon" href="assets/images/favicon-32x32.png" type="image/png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="sass/main.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <img src="assets/images/logo-dark.webp" alt="NTS TOUR" height="40">
                </a>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Deconectare</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4><i class="bi bi-house-door"></i> Dashboard - Bine ai venit!</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Informații utilizator:</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Nume:</strong></td>
                                        <td><?php echo htmlspecialchars($user_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cod Angajat:</strong></td>
                                        <td><?php echo htmlspecialchars($employee_code); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td><?php echo htmlspecialchars($_SESSION['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Rol:</strong></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($user_role); ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Acțiuni rapide:</h5>
                                <div class="d-grid gap-2">
                                    <?php if ($user_role == 'admin'): ?>
                                        <a href="admin_panel.php" class="btn btn-primary">
                                            <i class="bi bi-gear"></i> Panoul de Administrare
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($user_role, ['admin', 'manager'])): ?>
                                        <a href="reports.php" class="btn btn-info">
                                            <i class="bi bi-graph-up"></i> Rapoarte
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="tours.php" class="btn btn-success">
                                        <i class="bi bi-map"></i> Tururi
                                    </a>
                                    
                                    <a href="profile.php" class="btn btn-secondary">
                                        <i class="bi bi-person"></i> Profil Personal
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4" role="alert">
                            <i class="bi bi-info-circle"></i>
                            <strong>Autentificare reușită!</strong> Sistemul funcționează corect.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
