<!doctype html>
<html lang="ro" data-bs-theme="blue-theme">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="icon" href="assets/images/favicon-32x32.png" type="image/png">
  <link href="assets/css/pace.min.css" rel="stylesheet">
  <script src="assets/js/pace.min.js"></script>
  <!-- 🚀 WOW ENTERPRISE THEMES SYSTEM CSS -->
  <link href="assets/css/wow-enterprise-themes.css" rel="stylesheet">
  <link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/metisMenu.min.css">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/mm-vertical.css">
  <link rel="stylesheet" type="text/css" href="assets/plugins/simplebar/css/simplebar.css">
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
  <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
  <link href="sass/main.css" rel="stylesheet">
  <link href="sass/dark-theme.css" rel="stylesheet">
  <link href="sass/blue-theme.css" rel="stylesheet">
  <link href="sass/semi-dark.css" rel="stylesheet">
  <link href="sass/bordered-theme.css" rel="stylesheet">
  <link href="sass/responsive.css" rel="stylesheet">

  <style>
    /* Stilurile esentiale pastrate */
    .metismenu .has-arrow::after { display: none !important; }
    .vehicle-card-img { height: 200px; object-fit: contain; background-color: rgba(0,0,0,0.05); padding: 5px; }

    /* Reparatia pentru layout si footer "lipit" */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .main-wrapper {
        flex-grow: 1;
    }
  </style>
</head>

<body>
  <header class="top-header">
    <nav class="navbar navbar-expand align-items-center gap-4">
      <div class="btn-toggle">
        <a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
      </div>
    </nav>
  </header>

   <aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
      <div class="logo-icon" style="padding: 10px;">
        <img src="assets/images/logo-dark.webp" class="logo-img" alt="NTS TOUR Logo" style="width: 150px; height: auto;">
      </div>
      <div class="sidebar-close">
        <span class="material-icons-outlined">close</span>
      </div>
    </div>
    <div class="sidebar-nav">
        <ul class="metismenu" id="sidenav">
          <li><a href="index.php"><div class="parent-icon"><i class="material-icons-outlined">dashboard</i></div><div class="menu-title">Dashboard</div></a></li>
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">directions_car</i></div><div class="menu-title">Flotă</div></a><ul><li><a href="vehicule.php"><i class="material-icons-outlined">arrow_right</i>Vehicule</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Documente vehicule</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Plan revizii</a></li></ul></li>
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">people</i></div><div class="menu-title">Angajați</div></a><ul><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Listă angajați</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Adaugă angajat</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Documente angajați</a></li></ul></li>
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">trending_up</i></div><div class="menu-title">CRM</div></a><ul><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Leaduri</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Clienți</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Campanii</a></li></ul></li>
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">build</i></div><div class="menu-title">Mentenanță</div></a><ul><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Raportează problemă</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Istoric reparații</a></li></ul></li>
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">assessment</i></div><div class="menu-title">Rapoarte</div></a><ul><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Financiar</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Flotă</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Angajați</a></li></ul></li>
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">settings</i></div><div class="menu-title">Setări</div></a><ul><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Profil utilizator</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Schimbă parola</a></li></ul></li>
          <li><a href="javascript:;"><div class="parent-icon"><i class="material-icons-outlined">calendar_today</i></div><div class="menu-title">Calendar</div></a></li>
          <li><a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="material-icons-outlined">notifications_active</i></div><div class="menu-title">Notificări</div></a><ul><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Documente expirate</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Leaduri noi</a></li><li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Probleme raportate</a></li></ul></li>
         </ul>
    </div>
  </aside>