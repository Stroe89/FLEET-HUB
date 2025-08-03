<?php
session_start();
require_once 'db_connect.php';
require_once 'template/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<title>NTS TOUR | Nume Pagină</title>
<main class="main-wrapper">
    <div class="main-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Nume Categorie</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Nume Pagină</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Aceasta este pagina "Nume Pagină"</h4>
                        <p>Conținutul pentru această secțiune va fi adăugat aici.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once 'template/footer.php'; ?>