<?php
session_start(); // Porneste sesiunea pentru a accesa mesaje

// Mesaje de succes sau eroare din sesiune
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Eliminam mesajul dupa afisare
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Eliminam mesajul dupa afisare
}
?>
<!doctype html>
<html lang="ro" data-bs-theme="blue-theme">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>NTS TOUR | Recuperare Parolă</title>
  <!--favicon-->
	<link rel="icon" href="assets/images/favicon-32x32.png" type="image/png">
  <!-- loader-->
	<link href="assets/css/pace.min.css" rel="stylesheet">
	<script src="assets/js/pace.min.js"></script>

  <!--plugins-->
  <link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/metisMenu.min.css">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/mm-vertical.css">
  <!--bootstrap css-->
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
  <!--main css-->
  <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
  <link href="sass/main.css" rel="stylesheet">
  <link href="sass/dark-theme.css" rel="stylesheet">
  <link href="sass/blue-theme.css" rel="stylesheet">
  <link href="sass/responsive.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</head>

<body>


  <!--authentication-->

  <div class="section-authentication-cover">
    <div class="">
      <div class="row g-0">

        <div class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex border-end bg-transparent">

          <div class="card rounded-0 mb-0 border-0 shadow-none bg-transparent bg-none">
            <div class="card-body">
              <!-- Imaginea de fundal pentru partea stângă a paginii -->
              <img src="assets/images/auth/login1.png" class="img-fluid auth-img-cover-login" width="650" alt="Imagine de fundal">
            </div>
          </div>

        </div>

        <div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center border-top border-4 border-primary border-gradient-1">
          <div class="card rounded-0 m-3 mb-0 border-0 shadow-none bg-none">
            <div class="card-body p-sm-5">
              <!-- Logo-ul companiei -->
              <img src="assets/images/logo-dark.webp" class="mb-4" width="200" alt="Logo NTS TOUR">
              <h4 class="fw-bold">Ai uitat parola?</h4>
              <p class="mb-0">Introdu numele de utilizator sau codul de angajat pentru a primi un link de resetare.</p>

              <?php if ($success_message): ?>
                  <div class="alert alert-success mt-3" role="alert">
                      <?php echo $success_message; ?>
                  </div>
              <?php endif; ?>
              <?php if ($error_message): ?>
                  <div class="alert alert-danger mt-3" role="alert">
                      <?php echo $error_message; ?>
                  </div>
              <?php endif; ?>

              <div class="separator section-padding">
                <div class="line"></div>
                <p class="mb-0 fw-bold">FORMULAR</p>
                <div class="line"></div>
              </div>

              <div class="form-body mt-4">
                <form class="row g-3" action="send_reset_link.php" method="POST">
                  <div class="col-12">
                    <label for="inputIdentifier" class="form-label">Nume Utilizator sau Cod Angajat</label>
                    <input type="text" class="form-control" id="inputIdentifier" name="identifier" placeholder="Introdu Numele de Utilizator sau Codul Angajatului" required>
                  </div>
                  
                  <div class="col-12">
                    <div class="d-grid">
                      <button type="submit" class="btn btn-grd-primary">Trimite link de resetare</button>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="text-start">
                      <p class="mb-0">Îți amintești parola? <a href="login.php">Autentifică-te aici</a>
                      </p>
                    </div>
                  </div>
                </form>
              </div>

            </div>
          </div>
        </div>

      </div>
      <!--end row-->
    </div>
  </div>

  <!--authentication-->


  <!--plugins-->
  <script src="assets/js/jquery.min.js"></script>

</body>

</html>
