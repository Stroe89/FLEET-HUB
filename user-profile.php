<?php
$user_role = isset($user_role) ? $user_role : '';
$user_status = isset($user_status) ? $user_status : '';
session_start();
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if (!$user_id) {
    die("Eroare: utilizatorul nu este autentificat.");
}

include 'db_connect.php';

if (!function_exists('get_usermeta')) {
function get_usermeta($user_id, $key, $conn) {
    $stmt = $conn->prepare("SELECT meta_value FROM wppn_usermeta WHERE user_id = ? AND meta_key = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $key);
    $stmt->execute();
    $stmt->bind_result($value);
    if ($stmt->fetch()) {
        return maybe_unserialize($value);
    }
    return '';
}
}
if (!function_exists('maybe_unserialize')) {
function maybe_unserialize($value) {
    if (@unserialize($value) !== false || $value === 'b:0;') {
        return unserialize($value);
    }
    return $value;
}
}
$first_name = get_usermeta($user_id, 'first_name', $conn);
$last_name = get_usermeta($user_id, 'last_name', $conn);
$phone = get_usermeta($user_id, 'phone', $conn);
$dob = get_usermeta($user_id, 'dob', $conn);
$country = get_usermeta($user_id, 'country', $conn);
$city = get_usermeta($user_id, 'city', $conn);
$state = get_usermeta($user_id, 'state', $conn);
$zip = get_usermeta($user_id, 'zip', $conn);
$address = get_usermeta($user_id, 'address', $conn);
$research = get_usermeta($user_id, 'research', $conn);
$strategy = get_usermeta($user_id, 'strategy', $conn);

$result = $conn->query("SELECT user_email FROM wppn_users WHERE ID = $user_id LIMIT 1");
$email = ($result && $row = $result->fetch_assoc()) ? $row['user_email'] : '';


$user_id = $_SESSION['user_id'] ?? 1; // default fallback for test

if (!function_exists('get_usermeta')) {
function get_usermeta($user_id, $key, $conn) {
    $stmt = $conn->prepare("SELECT meta_value FROM wppn_usermeta WHERE user_id = ? AND meta_key = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $key);
    $stmt->execute();
    $stmt->bind_result($value);
    if ($stmt->fetch()) {
        return maybe_unserialize($value);
    }
    return '';
}
}

if (!function_exists('maybe_unserialize')) {
function maybe_unserialize($value) {
    if (@unserialize($value) !== false || $value === 'b:0;') {
        return unserialize($value);
    }
    return $value;
}
}
?>

<!DOCTYPE html>

<html data-bs-theme="blue-theme" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<title>Fleet | Core HUB  </title>
<!--favicon-->
<link href="assets/images/favicon-32x32.png" rel="icon" type="image/png"/>
<!-- loader-->
<link href="assets/css/pace.min.css" rel="stylesheet"/>
<script src="assets/js/pace.min.js"></script>
<!--plugins-->
<link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet"/>
<link href="assets/plugins/metismenu/metisMenu.min.css" rel="stylesheet" type="text/css"/>
<link href="assets/plugins/metismenu/mm-vertical.css" rel="stylesheet" type="text/css"/>
<link href="assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" type="text/css"/>
<!--bootstrap css-->
<link href="assets/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet"/>
<!--main css-->
<link href="assets/css/bootstrap-extended.css" rel="stylesheet"/>
<link href="sass/main.css" rel="stylesheet"/>
<link href="sass/dark-theme.css" rel="stylesheet"/>
<link href="sass/blue-theme.css" rel="stylesheet"/>
<link href="sass/semi-dark.css" rel="stylesheet"/>
<link href="sass/bordered-theme.css" rel="stylesheet"/>
<link href="sass/responsive.css" rel="stylesheet"/>
</head>
<body>
<!--start header-->
<header class="top-header">
<nav class="navbar navbar-expand align-items-center gap-4">
<div class="btn-toggle">
<a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
</div>
<div class="search-bar flex-grow-1">
<div class="position-relative">
<input class="form-control rounded-5 px-5 search-control d-lg-block d-none" placeholder="Caută" type="text"/>
<span class="material-icons-outlined position-absolute d-lg-block d-none ms-3 translate-middle-y start-0 top-50">search</span>
<span class="material-icons-outlined position-absolute me-3 translate-middle-y end-0 top-50 search-close">close</span>
<div class="search-popup p-3">
<div class="card rounded-4 overflow-hidden">
<div class="card-header d-lg-none">
<div class="position-relative">
<input class="form-control rounded-5 px-5 mobile-search-control" placeholder="Search" type="text"/>
<span class="material-icons-outlined position-absolute ms-3 translate-middle-y start-0 top-50">search</span>
<span class="material-icons-outlined position-absolute me-3 translate-middle-y end-0 top-50 mobile-search-close">close</span>
</div>
</div>
<div class="card-body search-content">
<p class="search-title">Căutări recente</p>
<div class="d-flex align-items-start flex-wrap gap-2 kewords-wrapper">
<a class="kewords" href="javascript:;"><span>facturi</span><i class="material-icons-outlined fs-6">search</i></a>
<a class="kewords" href="javascript:;"><span>șoferi</span><i class="material-icons-outlined fs-6">search</i></a>
<a class="kewords" href="javascript:;"><span>mentenanță</span><i class="material-icons-outlined fs-6">search</i></a>
<a class="kewords" href="javascript:;"><span>revizii</span><i class="material-icons-outlined fs-6">search</i></a>
<a class="kewords" href="javascript:;"><span>notificari</span><i class="material-icons-outlined fs-6">search</i></a>
</div>
<hr/>
<a class="btn w-100" href="javascript:;">Vezi toate rezultatele căutării</a>
</div>
</div>
</div>
</div>
</div>
<ul class="navbar-nav gap-1 nav-right-links align-items-center">
<li class="nav-item d-lg-none mobile-search-btn">
<a class="nav-link" href="javascript:;"><i class="material-icons-outlined">search</i></a>
</li>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown" href="avascript:;"><img alt="" src="assets/images/county/02.png" width="22"/>
</a>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item d-flex align-items-center py-2" href="javascript:;"><img alt="" src="assets/images/county/01.png" width="20"/><span class="ms-2">English</span></a>
</li>
<li><a class="dropdown-item d-flex align-items-center py-2" href="javascript:;"><img alt="" src="assets/images/county/02.png" width="20"/><span class="ms-2">Română</span></a>
</li>
<li><a class="dropdown-item d-flex align-items-center py-2" href="javascript:;"><img alt="" src="assets/images/county/03.png" width="20"/><span class="ms-2">Deutsch</span></a>
</li>
<li><a class="dropdown-item d-flex align-items-center py-2" href="javascript:;"><img alt="" src="assets/images/county/04.png" width="20"/><span class="ms-2">Français</span></a>
</li>
<li><a class="dropdown-item d-flex align-items-center py-2" href="javascript:;"><img alt="" src="assets/images/county/05.png" width="20"/><span class="ms-2">Italiano</span></a>
</li>
<li><a class="dropdown-item d-flex align-items-center py-2" href="javascript:;"><img alt="" src="assets/images/county/06.png" width="20"/><span class="ms-2">Español</span></a>
</li>
</ul></li>
</ul>

<li class="nav-item dropdown position-static d-md-flex d-none">
<a class="nav-link dropdown-toggle dropdown-toggle-nocaret" data-bs-auto-close="outside" data-bs-toggle="dropdown" href="javascript:;"><i class="material-icons-outlined">done_all</i></a>
<div class="dropdown-menu dropdown-menu-end mega-menu shadow-lg p-4 p-lg-5">
<div class="mega-menu-widgets">
<div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3 g-4 g-lg-5">
<div class="col">
<a class="text-decoration-none" href="marketing.html">
<div class="card rounded-4 shadow-none border mb-0">
<div class="card-body">
<div class="d-flex align-items-start gap-3">
<div class="mega-menu-icon flex-shrink-0 bg-danger">
<i class="material-icons-outlined text-white">question_answer</i>
</div>
<div class="mega-menu-content">
<h5 class="text-white">Marketing</h5>
<p class="mb-0 f-14 text-white-50">Strategii moderne de promovare, automatizări și soluții digitale pentru creșterea vizibilității brandului tău.</p>
</div>
</div>
</div>
</div>
</a>
</div>
<div class="col">
<a class="text-decoration-none" href="website.html">
<div class="card rounded-4 shadow-none border mb-0">
<div class="card-body">
<div class="d-flex align-items-start gap-3">
<img alt="" src="assets/images/megaIcons/02.png" width="40"/>
<div class="mega-menu-content">
<h5 class="text-white">Website</h5>
<p class="mb-0 f-14 text-white-50">Conectează-ți site-ul direct în platformă și editează-l cu ușurință prin integrarea API-urilor moderne.</p>
</div>
</div>
</div>
</div>
</a>
</div>
<div class="col">
<a class="text-decoration-none" href="templates.html">
<div class="card rounded-4 shadow-none border mb-0">
<div class="card-body">
<div class="d-flex align-items-start gap-3">
<img alt="" src="assets/images/megaIcons/11.png" width="40"/>
<div class="mega-menu-content">
<h5 class="text-white">Șabloane</h5>
<p class="mb-0 f-14 text-white-50">Găsește șabloane profesionale pentru campanii, prezentări sau site-uri, gata de personalizat.</p>
</div>
</div>
</div>
</div>
</a>
</div>
<div class="col">
<a class="text-decoration-none" href="hubspot.html">
<div class="card rounded-4 shadow-none border mb-0">
<div class="card-body">
<div class="d-flex align-items-start gap-3">
<img alt="" src="assets/images/megaIcons/01.png" width="40"/>
<div class="mega-menu-content">
<h5 class="text-white">Hubspot</h5>
<p class="mb-0 f-14 text-white-50">Automatizează comunicarea cu clienții și gestionează lead-urile eficient cu ajutorul Hubspot CRM.</p>
</div>
</div>
</div>
</div>
</a>
</div>
<div class="col">
<a class="text-decoration-none" href="academy.html">
<div class="card rounded-4 shadow-none border mb-0">
<div class="card-body">
<div class="d-flex align-items-start gap-3">
<img alt="" src="assets/images/megaIcons/09.png" width="40"/>
<div class="mega-menu-content">
<h5 class="text-white">Academie</h5>
<p class="mb-0 f-14 text-white-50">Accesează cursuri și resurse educaționale pentru a-ți perfecționa abilitățile digitale și de business.</p>
</div>
</div>
</div>
</div>
</a>
</div>
<div class="col">
<a class="text-decoration-none" href="sales.html">
<div class="card rounded-4 shadow-none border mb-0">
<div class="card-body">
<div class="d-flex align-items-start gap-3">
<img alt="" src="assets/images/megaIcons/12.png" width="40"/>
<div class="mega-menu-content">
<h5 class="text-white">Vânzări</h5>
<p class="mb-0 f-14 text-white-50">Instrumente de vânzări și rapoarte inteligente pentru a-ți crește performanțele comerciale.</p>
</div>
</div>
</div>
</div>
</a>
</div>
</div>
</div>
</div>
</li>
<!--end row-->



<!-- MENIU DROPDOWN APLICAȚII -->
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle dropdown-toggle-nocaret" data-bs-auto-close="outside" data-bs-toggle="dropdown" href="javascript:;"><i class="material-icons-outlined">apps</i></a>
<div class="dropdown-menu dropdown-menu-end dropdown-apps shadow-lg p-3">
<div class="border rounded-4 overflow-hidden">
<div class="row row-cols-3 g-0 border-bottom">
<div class="col border-end">
<a class="text-decoration-none" href="#" id="facebookApp" target="_blank">
<div class="app-wrapper d-flex flex-column gap-2 text-center p-3">
<div class="app-icon">
<img alt="" src="assets/images/apps/09.png" width="36"/>
</div>
<div class="app-name">
<p class="mb-0 text-white">Facebook</p>
</div>
</div>
</a>
</div>
<div class="col border-end">
<a class="text-decoration-none" href="#" id="instagramApp" target="_blank">
<div class="app-wrapper d-flex flex-column gap-2 text-center p-3">
<div class="app-icon">
<img alt="" src="assets/images/apps/06.png" width="36"/>
</div>
<div class="app-name">
<p class="mb-0 text-white">Instagram</p>
</div>
</div>
</a>
</div>
<div class="col">
<a class="text-decoration-none" href="#" id="tiktokApp" target="_blank">
<div class="app-wrapper d-flex flex-column gap-2 text-center p-3">
<div class="app-icon">
<img alt="" src="assets/images/megaIcons/09.png" width="36"/>
</div>
<div class="app-name">
<p class="mb-0 text-white">TikTok</p>
</div>
</div>
</a>
</div>
</div>
</div>
</div>
</li>
<!--end row-->



<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" data-bs-auto-close="outside" data-bs-toggle="dropdown" href="javascript:;" style="display: inline-block;">
    <!-- CLOPOTEL -->
    <i class="material-icons-outlined" style="font-size: 24px; position: relative; z-index: 1;">notifications</i>
    
    <!-- BULINA ROSIE -->
    <span class="badge-notify"
          style="position: absolute; top: -2px; right: -2px; background-color: red; color: white;
                 border-radius: 50%; padding: 2px 6px; font-size: 10px; z-index: 3;">
      5
    </span>
  </a>

  <!-- DROPDOWN NOTIFICARI -->
  <div class="dropdown-menu dropdown-notify dropdown-menu-end shadow"
       style="z-index: 9999; position: absolute; top: 100%; right: 0;">
    <div class="px-3 py-1 d-flex align-items-center justify-content-between border-bottom">
      <h5 class="notiy-title mb-0">Notificări</h5>
      <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle dropdown-toggle-nocaret option"
                type="button" data-bs-toggle="dropdown">
          <span class="material-icons-outlined">more_vert</span>
        </button>
        <div class="dropdown-menu dropdown-option dropdown-menu-end shadow">
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;">
            <i class="material-icons-outlined fs-6">inventory_2</i>Arhivează tot
          </a>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;">
            <i class="material-icons-outlined fs-6">done_all</i>Marchează tot ca citit
          </a>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;">
            <i class="material-icons-outlined fs-6">mic_off</i>Dezactivează notificările
          </a>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;">
            <i class="material-icons-outlined fs-6">grade</i>Ce este nou?
          </a>
          <hr class="dropdown-divider"/>
          <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;">
            <i class="material-icons-outlined fs-6">leaderboard</i>Rapoarte
          </a>
        </div>
      </div>
    </div>
<div class="notify-list">
<div>
<a class="dropdown-item border-bottom py-2" href="javascript:;">
<div class="d-flex align-items-center gap-3">
<div class="">
<img alt="" class="rounded-circle" height="45" src="assets/images/avatars/01.png" width="45"/>
</div>
<div class="">
<h5 class="notify-title">Felicitări, Alina!</h5>
<p class="mb-0 notify-desc">Ai primit o recompensă pentru performanțele din Flota NTS.</p>
<p class="mb-0 notify-time">Astăzi</p>
</div>
<div class="notify-close position-absolute end-0 me-3">
<i class="material-icons-outlined fs-6">close</i>
</div>
</div>
</a>
</div>
<div>
<a class="dropdown-item border-bottom py-2" href="javascript:;">
<div class="d-flex align-items-center gap-3">
<div class="user-wrapper bg-primary text-primary bg-opacity-10">
<span>RS</span>
</div>
<div class="">
<h5 class="notify-title">Cont nou creat</h5>
<p class="mb-0 notify-desc">Un nou șofer a fost înregistrat în platforma NTS.</p>
<p class="mb-0 notify-time">Ieri</p>
</div>
<div class="notify-close position-absolute end-0 me-3">
<i class="material-icons-outlined fs-6">close</i>
</div>
</div>
</a>
</div>
<div>
<a class="dropdown-item border-bottom py-2" href="javascript:;">
<div class="d-flex align-items-center gap-3">
<div class="">
<img alt="" class="rounded-circle" height="45" src="assets/images/apps/13.png" width="45"/>
</div>
<div class="">
<h5 class="notify-title">Plată primită</h5>
<p class="mb-0 notify-desc">O plată a fost înregistrată cu succes pentru cursa recentă.</p>
<p class="mb-0 notify-time">acum 1 zi</p>
</div>
<div class="notify-close position-absolute end-0 me-3">
<i class="material-icons-outlined fs-6">close</i>
</div>
</div>
</a>
</div>
<div>
<a class="dropdown-item border-bottom py-2" href="javascript:;">
<div class="d-flex align-items-center gap-3">
<div class="">
<img alt="" class="rounded-circle" height="45" src="assets/images/apps/14.png" width="45"/>
</div>
<div class="">
<h5 class="notify-title">Comandă nouă primită</h5>
<p class="mb-0 notify-desc">Ai primit o nouă cursă programată pentru mâine dimineață.</p>
<p class="mb-0 notify-time">2:15 AM</p>
</div>
<div class="notify-close position-absolute end-0 me-3">
<i class="material-icons-outlined fs-6">close</i>
</div>
</div>
</a>
</div>
<div>
<a class="dropdown-item border-bottom py-2" href="javascript:;">
<div class="d-flex align-items-center gap-3">
<div class="">
<img alt="" class="rounded-circle" height="45" src="assets/images/avatars/06.png" width="45"/>
</div>
<div class="">
<h5 class="notify-title">Inspecție completă</h5>
<p class="mb-0 notify-desc">Vehiculul NTS-06 a trecut cu succes inspecția tehnică.</p>
<p class="mb-0 notify-time">Astăzi</p>
</div>
<div class="notify-close position-absolute end-0 me-3">
<i class="material-icons-outlined fs-6">close</i>
</div>
</div>
</a>
</div>
<div>
<a class="dropdown-item py-2" href="javascript:;">
<div class="d-flex align-items-center gap-3">
<div class="user-wrapper bg-danger text-danger bg-opacity-10">
<span>PK</span>
</div>
<div class="">
<h5 class="notify-title">Atenție: Mentenanță</h5>
<p class="mb-0 notify-desc">Microbuzul NTS-22 necesită verificare tehnică urgentă.</p>
<p class="mb-0 notify-time">Ieri</p>
</div>
<div class="notify-close position-absolute end-0 me-3">
<i class="material-icons-outlined fs-6">close</i>
</div>
</div>
</a>
</div>
</div>
</div></li>
<li class="nav-item dropdown">
<a class="dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown" href="javascrpt:;">
<img alt="" class="rounded-circle p-1 border" height="45" src="assets/images/avatars/01.png" width="45"/>
</a>
<div class="dropdown-menu dropdown-user dropdown-menu-end shadow">
<a class="dropdown-item gap-2 py-2" href="javascript:;">
<div class="text-center">
<img alt="" class="rounded-circle p-1 shadow mb-3" height="90" src="assets/images/avatars/01.png" width="90"/>
<h5 class="user-name mb-0 fw-bold">Salut, Marius</h5>
</div>
</a>
<hr class="dropdown-divider"/>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">person_outline</i>Profil</a>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">local_bar</i>Setări</a>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">dashboard</i>Panou control</a>
<hr class="dropdown-divider"/>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined">power_settings_new</i>Ieșire</a>
</div>
</li>

</nav>
</header>
<!--end top header-->
<!--start sidebar-->
<aside class="sidebar-wrapper" data-simplebar="true">
<div class="sidebar-header">
<div class="logo-icon">
<img alt="" class="logo-img" src="assets/images/logo-icon.png"/>
</div>
<div class="logo-name flex-grow-1">
<h5 class="mb-0">NTS TOUR</h5>
</div>
<div class="sidebar-close">
<span class="material-icons-outlined">close</span>
</div>
</div>
<div class="sidebar-nav">
<!--navigation-->
<ul class="metismenu" id="sidenav">
<!-- Hub Principal -->
<!-- Hub Principal -->
<li class="menu-label">Hub Principal</li>
<a href="index.php"
   class="btn d-inline-flex align-items-center"
   style="gap: 8px; background-color: transparent; color: white; border: none; box-shadow: none;">
  <i class="material-icons-outlined">home</i>
  Acasă
</a>

<!-- Submeniu Flota -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">directions_bus</i>
    <span style="margin-left: 8px;">Flotă</span>
  </a>
  <ul>
    <li><a href="vehicule.php" target="_blank"><i class="material-icons-outlined">directions_bus</i><span style="margin-left: 8px;">Vehicule</span></a></li>
    <li><a href="adauga-vehicul.php" target="_blank"><i class="material-icons-outlined">add_circle_outline</i><span style="margin-left: 8px;">Adaugă Vehicul</span></a></li>
    <li><a href="curse-active.php" target="_blank"><i class="material-icons-outlined">commute</i><span style="margin-left: 8px;">Curse Active</span></a></li>
    <li><a href="planificare-rute.php" target="_blank"><i class="material-icons-outlined">event_note</i><span style="margin-left: 8px;">Planificare Curse</span></a></li>
    <li><a href="istoric-curse.php" target="_blank"><i class="material-icons-outlined">history</i><span style="margin-left: 8px;">Istoric Curse</span></a></li>
    <li><a href="alocare-vehicul-sofer.php" target="_blank"><i class="material-icons-outlined">person_add</i><span style="margin-left: 8px;">Alocare Vehicul Șofer</span></a></li>
    <li><a href="alimentare_combustibil.php" target="_blank"><i class="material-icons-outlined">local_gas_station</i><span style="margin-left: 8px;">Alimentare Vehicul</span></a></li>
  </ul>
</li>

<!-- Submeniu Documente -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">folder</i>
    <span style="margin-left: 8px;">Documente</span>
  </a>
  <ul>
    <li><a href="documente-vehicule.php" target="_blank"><i class="material-icons-outlined">folder_open</i><span style="margin-left: 8px;">Documente Vehicule</span></a></li>
    <li><a href="contracte-angajati.php" target="_blank"><i class="material-icons-outlined">badge</i><span style="margin-left: 8px;">Contracte Angajați</span></a></li>
    <li><a href="contracte-clienti.php" target="_blank"><i class="material-icons-outlined">people_alt</i><span style="margin-left: 8px;">Contracte Clienți</span></a></li>
    <li><a href="polite-asigurare.php" target="_blank"><i class="material-icons-outlined">schedule</i><span style="margin-left: 8px;">Expirări Curente</span></a></li>
    <li><a href="adauga-document.php" target="_blank"><i class="material-icons-outlined">cloud_upload</i><span style="margin-left: 8px;">Încărcare Documente</span></a></li>
  </ul>
</li>

<!-- Submeniu Angajați -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">groups</i>
    <span style="margin-left: 8px;">Angajați</span>
  </a>
  <ul>
    <li><a href="lista-angajati.php" target="_blank"><i class="material-icons-outlined">group</i><span style="margin-left: 8px;">Lista Angajați</span></a></li>
    <li><a href="fise-individuale.php" target="_blank"><i class="material-icons-outlined">assignment_ind</i><span style="margin-left: 8px;">Fișe Individuale</span></a></li>
    <li><a href="adauga-angajat.php" target="_blank"><i class="material-icons-outlined">person_add_alt</i><span style="margin-left: 8px;">Adaugă Angajat</span></a></li>
    <li><a href="disponibilitate-grafica.php" target="_blank"><i class="material-icons-outlined">event_available</i><span style="margin-left: 8px;">Disponibilitate</span></a></li>
    <li><a href="salarii-bonusuri.php" target="_blank"><i class="material-icons-outlined">payments</i><span style="margin-left: 8px;">Salarii & Bonusuri</span></a></li>
  </ul>
</li>

<!-- Submeniu Clienți -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">supervisor_account</i>
    <span style="margin-left: 8px;">Clienți</span>
  </a>
  <ul>
    <li><a href="lista-clienti.php" target="_blank"><i class="material-icons-outlined">supervisor_account</i><span style="margin-left: 8px;">Lista Clienți</span></a></li>
    <li><a href="adauga-client.php" target="_blank"><i class="material-icons-outlined">person_add_alt_1</i><span style="margin-left: 8px;">Adaugă Clienți</span></a></li>
  </ul>
</li>

<!-- Submeniu Contabilitate -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">account_balance</i>
    <span style="margin-left: 8px;">Contabilitate</span>
  </a>
  <ul>
    <li><a href="facturi-emise.php" target="_blank"><i class="material-icons-outlined">receipt_long</i><span style="margin-left: 8px;">Facturi emise</span></a></li>
    <li><a href="emite-factura-noua.php" target="_blank"><i class="material-icons-outlined">post_add</i><span style="margin-left: 8px;">Emite Factură Nouă</span></a></li>
    <li><a href="incasari-plati.php" target="_blank"><i class="material-icons-outlined">account_balance_wallet</i><span style="margin-left: 8px;">Încasări & Plăți</span></a></li>
    <li><a href="cheltuieli-flota.php" target="_blank"><i class="material-icons-outlined">trending_down</i><span style="margin-left: 8px;">Cheltuieli Flotă</span></a></li>
    <li><a href="raport-financiar-lunar.php" target="_blank"><i class="material-icons-outlined">bar_chart</i><span style="margin-left: 8px;">Raport Financiar Lunar</span></a></li>
    <li><a href="cash-flow-vizual.php" target="_blank"><i class="material-icons-outlined">show_chart</i><span style="margin-left: 8px;">Cash-Flow Vizual</span></a></li>
    <li><a href="export-contabilitate.php" target="_blank"><i class="material-icons-outlined">file_download</i><span style="margin-left: 8px;">Export Contabilitate</span></a></li>
  </ul>
</li>

<!-- Submeniu Mentenanță -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">build_circle</i>
    <span style="margin-left: 8px;">Mentenanță</span>
  </a>
  <ul>
    <li><a href="plan-revizii.php" target="_blank"><i class="material-icons-outlined">build</i><span style="margin-left: 8px;">Programări Service</span></a></li>
    <li><a href="notificari-probleme-raportate.php" target="_blank"><i class="material-icons-outlined">report_problem</i><span style="margin-left: 8px;">Probleme Raportate</span></a></li>
    <li><a href="confirmare-lucrari.php" target="_blank"><i class="material-icons-outlined">check_circle</i><span style="margin-left: 8px;">Confirmare Lucrări Efectuate</span></a></li>
    <li><a href="istoric-reparatii.php" target="_blank"><i class="material-icons-outlined">engineering</i><span style="margin-left: 8px;">Istoric Reparații</span></a></li>
    <li><a href="istoric-curse.php" target="_blank"><i class="material-icons-outlined">history</i><span style="margin-left: 8px;">Istoric Curse</span></a></li>
    <li><a href="plan-revizii.php" target="_blank"><i class="material-icons-outlined">event</i><span style="margin-left: 8px;">Planificare Revizii</span></a></li>
    <li><a href="istoric-mentenanta.php" target="_blank"><i class="material-icons-outlined">timeline</i><span style="margin-left: 8px;">Istoric Mentenanță pe Vehicul</span></a></li>
  </ul>
</li>

<!-- Submeniu Rapoarte -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">bar_chart</i>
    <span style="margin-left: 8px;">Rapoarte</span>
  </a>
  <ul>
    <li><a href="raport-flota-zilnic.php" target="_blank"><i class="material-icons-outlined">today</i><span style="margin-left: 8px;">Raport Flotă Zilnic</span></a></li>
    <li><a href="raport-flota-lunar.php" target="_blank"><i class="material-icons-outlined">date_range</i><span style="margin-left: 8px;">Raport Flotă Lunar</span></a></li>
    <li><a href="raport-financiar.php" target="_blank"><i class="material-icons-outlined">analytics</i><span style="margin-left: 8px;">Raport Financiar</span></a></li>
    <li><a href="raport-consum-combustibil.php" target="_blank"><i class="material-icons-outlined">local_gas_station</i><span style="margin-left: 8px;">Raport Consum Combustibil</span></a></li>
    <li><a href="raport-cost-km.php" target="_blank"><i class="material-icons-outlined">speed</i><span style="margin-left: 8px;">Cost/Km per Vehicul</span></a></li>
    <li><a href="auth-basic-login.html" target="_blank"><i class="material-icons-outlined">file_download</i><span style="margin-left: 8px;">Export Rapoarte</span></a></li>
  </ul>
</li>

<!-- Submeniu Notificări -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">notifications_active</i>
    <span style="margin-left: 8px;">Notificări</span>
  </a>
  <ul>
    <li><a href="notificari-documente-expirate.php" target="_blank"><i class="material-icons-outlined">notification_important</i><span style="margin-left: 8px;">Alerte Documente</span></a></li>
    <li><a href="notificari-mentenanta.php" target="_blank"><i class="material-icons-outlined">build_circle</i><span style="margin-left: 8px;">Alerte Mentenanță</span></a></li>
    <li><a href="notificari-soferi.php" target="_blank"><i class="material-icons-outlined">emoji_transportation</i><span style="margin-left: 8px;">Alerte Șoferi</span></a></li>
    <li><a href="notificari-curse.php" target="_blank"><i class="material-icons-outlined">departure_board</i><span style="margin-left: 8px;">Alerte Curse</span></a></li>
    <li><a href="notificari-probleme-raportate.php" target="_blank"><i class="material-icons-outlined">report_problem</i><span style="margin-left: 8px;">Probleme Raportate</span></a></li>
    <li><a href="setari-notificari.php" target="_blank"><i class="material-icons-outlined">settings</i><span style="margin-left: 8px;">Setări Notificări</span></a></li>
  </ul>
</li>

<!-- Module utile -->
<li class="menu-label">Module utile</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <div class="parent-icon"><i class="material-icons-outlined">email</i></div>
    <div class="menu-title">E-mail & Chat</div>
  </a>
  <ul>
    <li><a href="app-emailbox.html"><i class="material-icons-outlined">inbox</i><span style="margin-left: 8px;">E-mail</span></a></li>
    <li><a href="app-chat-box.html"><i class="material-icons-outlined">chat</i><span style="margin-left: 8px;">Chat</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <div class="parent-icon"><i class="material-icons-outlined">apps</i></div>
    <div class="menu-title">Aplicații</div>
  </a>
  <ul>
    <li><a href="calendar.php"><i class="material-icons-outlined">calendar_month</i><span style="margin-left: 8px;">Calendar</span></a></li>
    <li><a href="app-to-do.html"><i class="material-icons-outlined">check_circle_outline</i><span style="margin-left: 8px;">Activități Planificate</span></a></li>
    <li>
      <a href="timeline.html">
        <i class="material-icons-outlined">timeline</i><span style="margin-left: 8px;">Istoric Activitate</span>
      </a>
    </li>
  </ul>
</li>

<li class="menu-label">Utilizator</li>
<li>

<a href="user-profile.php">
<div class="parent-icon"><i class="material-icons-outlined">person</i></div>
<div class="menu-title">Profil Utilizator</div>
<!-- Submeniu Setări -->
<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">settings</i>
    <span style="margin-left: 8px;">Setări</span>
  </a>
  <ul>
    <li>
      <a href="date-companie.php"><i class="material-icons-outlined">business</i>
      <span style="margin-left: 8px;">Date Companie</span></a>
    </li>
    <li>
      <a href="#"><i class="material-icons-outlined">badge</i>
      <span style="margin-left: 8px;">Utilizatori & Permisiuni</span></a>
    </li>
    <li>
      <a href="configurare-fiscala.php"><i class="material-icons-outlined">payments</i>
      <span style="margin-left: 8px;">Configurare TVA & Monede</span></a>
    </li>
  </ul>
</li>

</a>
</li>
<li class="menu-label">Informații</li>
<li>
<a href="documentatie.html">
<div class="parent-icon"><i class="material-icons-outlined">article</i></div>
<div class="menu-title">Documentație</div>
</a>
</li>
<li>
<a href="suport.html">
<div class="parent-icon"><i class="material-icons-outlined">live_help</i></div>
<div class="menu-title">Suport Tehnic</div>
</a>
</li>
<li>
<a href="faq.html">
<div class="parent-icon"><i class="material-icons-outlined">help_outline</i></div>
<div class="menu-title">Întrebări frecvente</div>
</a>
</li>
<li>
<a href="pricing-table.html">
<div class="parent-icon"><i class="material-icons-outlined">monetization_on</i></div>
<div class="menu-title">Prețuri</div>
</a>
</li>
 <a href="logout.php">
    <div class="parent-icon"><i class="material-icons-outlined">logout</i></div>
    <div class="menu-title">Deconectare</div>
  </a>
</li>
</li></ul>
<li>
 
<!--end navigation-->
    </div>
  </aside>
<!--end sidebar-->


  <!--start main wrapper-->
  <main class="main-wrapper">
    <div class="main-content">
      <!--breadcrumb-->
				<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
					<div class="breadcrumb-title pe-3">Profil Utilizator</div>
					<div class="ps-3">
							</div>
						</div>
					</div>
				</div>
				<!--end breadcrumb-->
      

       <div class="card rounded-4">
  <div class="card-body p-4">
    <div class="position-relative mb-5">
      <img src="assets/images/gallery/profile-cover.png" class="img-fluid rounded-4 shadow" alt="">
      <div class="profile-avatar position-absolute top-100 start-50 translate-middle">
        <img src="assets/images/avatars/01.png" class="img-fluid rounded-circle p-1 bg-grd-danger shadow" width="170" height="170" alt="">
      </div>
    </div>
    <div class="profile-info pt-5 d-flex align-items-center justify-content-between">
      <div class="">
        <h3>Marius Stroe</h3>
        <p class="mb-0">Dezvoltator la Media Expert Solution<br>Latina, Italia</p>
      </div>
      <div class="">
        <a href="chat.php?to_user_id=<?= $user_id ?>" class="btn btn-grd-primary rounded-5 px-4"><i class="bi bi-chat me-2"></i>Trimite mesaj</a>
      </div>
    </div>
    
<div class="col-md-6">
  <label for="input_role" class="form-label">Funcția ocupată în companie</label>
  <select class="form-select" id="input_role" name="user_role">
    <option value="">Alege...</option>
    <optgroup label="Tehnic">
      <option value="IT Support" <?= $user_role === 'IT Support' ? 'selected' : '' ?>>IT Support</option>
      <option value="Dezvoltator Software" <?= $user_role === 'Dezvoltator Software' ? 'selected' : '' ?>>Dezvoltator Software</option>
      <option value="Administrator Sisteme" <?= $user_role === 'Administrator Sisteme' ? 'selected' : '' ?>>Administrator Sisteme</option>
      <option value="Director Tehnic" <?= $user_role === 'Director Tehnic' ? 'selected' : '' ?>>Director Tehnic</option>
    </optgroup>
    <optgroup label="Logistică">
      <option value="Șofer" <?= $user_role === 'Șofer' ? 'selected' : '' ?>>Șofer</option>
      <option value="Mecanic" <?= $user_role === 'Mecanic' ? 'selected' : '' ?>>Mecanic</option>
      <option value="Coordonator Logistică" <?= $user_role === 'Coordonator Logistică' ? 'selected' : '' ?>>Coordonator Logistică</option>
      <option value="Manager Flotă" <?= $user_role === 'Manager Flotă' ? 'selected' : '' ?>>Manager Flotă</option>
    </optgroup>
    <optgroup label="Call-Center & Suport">
      <option value="Operator Call-Center" <?= $user_role === 'Operator Call-Center' ? 'selected' : '' ?>>Operator Call-Center</option>
      <option value="Coordonator Suport" <?= $user_role === 'Coordonator Suport' ? 'selected' : '' ?>>Coordonator Suport</option>
      <option value="Customer Service" <?= $user_role === 'Customer Service' ? 'selected' : '' ?>>Customer Service</option>
    </optgroup>
    <optgroup label="Marketing & Vânzări">
      <option value="Specialist Marketing" <?= $user_role === 'Specialist Marketing' ? 'selected' : '' ?>>Specialist Marketing</option>
      <option value="Copywriter" <?= $user_role === 'Copywriter' ? 'selected' : '' ?>>Copywriter</option>
      <option value="Social Media Manager" <?= $user_role === 'Social Media Manager' ? 'selected' : '' ?>>Social Media Manager</option>
      <option value="Reprezentant Vânzări" <?= $user_role === 'Reprezentant Vânzări' ? 'selected' : '' ?>>Reprezentant Vânzări</option>
      <option value="Manager Regional" <?= $user_role === 'Manager Regional' ? 'selected' : '' ?>>Manager Regional</option>
    </optgroup>
    <optgroup label="Administrație">
      <option value="Administrator" <?= $user_role === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
      <option value="Director General" <?= $user_role === 'Director General' ? 'selected' : '' ?>>Director General</option>
      <option value="Manager Operațional" <?= $user_role === 'Manager Operațional' ? 'selected' : '' ?>>Manager Operațional</option>
      <option value="Assistant Manager" <?= $user_role === 'Assistant Manager' ? 'selected' : '' ?>>Assistant Manager</option>
    </optgroup>
    <optgroup label="HR & Legal">
      <option value="HR Specialist" <?= $user_role === 'HR Specialist' ? 'selected' : '' ?>>HR Specialist</option>
      <option value="Recruiter" <?= $user_role === 'Recruiter' ? 'selected' : '' ?>>Recruiter</option>
      <option value="Jurist" <?= $user_role === 'Jurist' ? 'selected' : '' ?>>Jurist</option>
      <option value="Legal Advisor" <?= $user_role === 'Legal Advisor' ? 'selected' : '' ?>>Legal Advisor</option>
    </optgroup>
  </select>
</div>

<div class="col-md-12">
  <label for="custom_status" class="form-label">Status personalizat (opțional)</label>
  <input type="text" class="form-control" id="custom_status" name="custom_status" placeholder="Ex: În pauză, Concediu..." value="<?= isset($custom_status) ? htmlspecialchars($custom_status) : '' ?>">
</div>



<div class="col-md-6">
  <label for="input_status" class="form-label">Status prezență (vizibil în chat)</label>
  <select class="form-select" id="input_status" name="user_status">
    <option value="">Alege...</option>
    <option value="online" <?= $user_status === 'online' ? 'selected' : '' ?>>🟢 Online</option>
    <option value="offline" <?= $user_status === 'offline' ? 'selected' : '' ?>>⚪ Offline</option>
    <option value="ocupat" <?= $user_status === 'ocupat' ? 'selected' : '' ?>>🔴 Ocupat</option>
  </select>
</div>

  </div>
</div>

<div class="row">
  <div class="col-12 col-xl-8">
    <div class="card rounded-4 border-top border-4 border-primary border-gradient-1">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="">
            <h5 class="mb-0 fw-bold">Editează profilul</h5>
          </div>
          <div class="dropdown">
            <a href="javascript:;" class="dropdown-toggle-nocaret options dropdown-toggle" data-bs-toggle="dropdown">
              <span class="material-icons-outlined fs-5">more_vert</span>
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="javascript:;">Acțiune</a></li>
              <li><a class="dropdown-item" href="javascript:;">Altă acțiune</a></li>
              <li><a class="dropdown-item" href="javascript:;">Altceva aici</a></li>
            </ul>
          </div>
        </div>
        <form class="row g-4" method="POST" action="update-profile.php">
          <div class="col-md-6">
            <label for="input1" class="form-label">Prenume</label>
            <input type="text" class="form-control" id="input1" name="first_name" value="<?= htmlspecialchars($first_name) ?>" placeholder="Prenume">
          </div>
          <div class="col-md-6">
            <label for="input2" class="form-label">Nume</label>
            <input type="text" class="form-control" id="input2" name="last_name" value="<?= htmlspecialchars($last_name) ?>" placeholder="Nume">
          </div>
          <div class="col-md-12">
            <label for="input3" class="form-label">Telefon</label>
            <input type="text" class="form-control" id="input3" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="Telefon">
          </div>
          <div class="col-md-12">
            <label for="input4" class="form-label">Email</label>
            <input type="email" class="form-control" id="input4" name="email" value="<?= htmlspecialchars($email) ?>">
          </div>
          <div class="col-md-12">
            <label for="input5" class="form-label">Parolă</label>
            <input type="password" class="form-control" id="input5" name="password">
          </div>
          <div class="col-md-12">
            <label for="input6" class="form-label">Data nașterii</label>
            <input type="date" class="form-control" id="input6" name="dob" value="<?= htmlspecialchars($dob) ?>">
          </div>
          <div class="col-md-12">
            <label for="input7" class="form-label">Țară</label>
            <select id="input7" name="country" class="form-select">
<option value="Albania"><?= $country === 'Albania' ? "selected" : "" ?>Albania</option>
<option value="Andorra"><?= $country === 'Andorra' ? "selected" : "" ?>Andorra</option>
<option value="Armenia"><?= $country === 'Armenia' ? "selected" : "" ?>Armenia</option>
<option value="Austria"><?= $country === 'Austria' ? "selected" : "" ?>Austria</option>
<option value="Azerbaijan"><?= $country === 'Azerbaijan' ? "selected" : "" ?>Azerbaijan</option>
<option value="Belarus"><?= $country === 'Belarus' ? "selected" : "" ?>Belarus</option>
<option value="Belgia"><?= $country === 'Belgia' ? "selected" : "" ?>Belgia</option>
<option value="Bosnia și Herțegovina"><?= $country === 'Bosnia și Herțegovina' ? "selected" : "" ?>Bosnia și Herțegovina</option>
<option value="Bulgaria"><?= $country === 'Bulgaria' ? "selected" : "" ?>Bulgaria</option>
<option value="Croația"><?= $country === 'Croația' ? "selected" : "" ?>Croația</option>
<option value="Cipru"><?= $country === 'Cipru' ? "selected" : "" ?>Cipru</option>
<option value="Cehia"><?= $country === 'Cehia' ? "selected" : "" ?>Cehia</option>
<option value="Danemarca"><?= $country === 'Danemarca' ? "selected" : "" ?>Danemarca</option>
<option value="Estonia"><?= $country === 'Estonia' ? "selected" : "" ?>Estonia</option>
<option value="Finlanda"><?= $country === 'Finlanda' ? "selected" : "" ?>Finlanda</option>
<option value="Franța"><?= $country === 'Franța' ? "selected" : "" ?>Franța</option>
<option value="Georgia"><?= $country === 'Georgia' ? "selected" : "" ?>Georgia</option>
<option value="Germania"><?= $country === 'Germania' ? "selected" : "" ?>Germania</option>
<option value="Grecia"><?= $country === 'Grecia' ? "selected" : "" ?>Grecia</option>
<option value="Ungaria"><?= $country === 'Ungaria' ? "selected" : "" ?>Ungaria</option>
<option value="Islanda"><?= $country === 'Islanda' ? "selected" : "" ?>Islanda</option>
<option value="Irlanda"><?= $country === 'Irlanda' ? "selected" : "" ?>Irlanda</option>
<option value="Italia"><?= $country === 'Italia' ? "selected" : "" ?>Italia</option>
<option value="Kazahstan"><?= $country === 'Kazahstan' ? "selected" : "" ?>Kazahstan</option>
<option value="Kosovo"><?= $country === 'Kosovo' ? "selected" : "" ?>Kosovo</option>
<option value="Letonia"><?= $country === 'Letonia' ? "selected" : "" ?>Letonia</option>
<option value="Liechtenstein"><?= $country === 'Liechtenstein' ? "selected" : "" ?>Liechtenstein</option>
<option value="Lituania"><?= $country === 'Lituania' ? "selected" : "" ?>Lituania</option>
<option value="Luxemburg"><?= $country === 'Luxemburg' ? "selected" : "" ?>Luxemburg</option>
<option value="Malta"><?= $country === 'Malta' ? "selected" : "" ?>Malta</option>
<option value="Moldova"><?= $country === 'Moldova' ? "selected" : "" ?>Moldova</option>
<option value="Monaco"><?= $country === 'Monaco' ? "selected" : "" ?>Monaco</option>
<option value="Muntenegru"><?= $country === 'Muntenegru' ? "selected" : "" ?>Muntenegru</option>
<option value="Țările de Jos"><?= $country === 'Țările de Jos' ? "selected" : "" ?>Țările de Jos</option>
<option value="Macedonia de Nord"><?= $country === 'Macedonia de Nord' ? "selected" : "" ?>Macedonia de Nord</option>
<option value="Norvegia"><?= $country === 'Norvegia' ? "selected" : "" ?>Norvegia</option>
<option value="Polonia"><?= $country === 'Polonia' ? "selected" : "" ?>Polonia</option>
<option value="Portugalia"><?= $country === 'Portugalia' ? "selected" : "" ?>Portugalia</option>
<option value="România"><?= $country === 'România' ? "selected" : "" ?>România</option>
<option value="Rusia"><?= $country === 'Rusia' ? "selected" : "" ?>Rusia</option>
<option value="San Marino"><?= $country === 'San Marino' ? "selected" : "" ?>San Marino</option>
<option value="Serbia"><?= $country === 'Serbia' ? "selected" : "" ?>Serbia</option>
<option value="Slovacia"><?= $country === 'Slovacia' ? "selected" : "" ?>Slovacia</option>
<option value="Slovenia"><?= $country === 'Slovenia' ? "selected" : "" ?>Slovenia</option>
<option value="Spania"><?= $country === 'Spania' ? "selected" : "" ?>Spania</option>
<option value="Suedia"><?= $country === 'Suedia' ? "selected" : "" ?>Suedia</option>
<option value="Elveția"><?= $country === 'Elveția' ? "selected" : "" ?>Elveția</option>
<option value="Turcia"><?= $country === 'Turcia' ? "selected" : "" ?>Turcia</option>
<option value="Ucraina"><?= $country === 'Ucraina' ? "selected" : "" ?>Ucraina</option>
<option value="Regatul Unit"><?= $country === 'Regatul Unit' ? "selected" : "" ?>Regatul Unit</option>
<option value="Vatican"><?= $country === 'Vatican' ? "selected" : "" ?>Vatican</option>
              <option selected="">Alege...</option>
              <option>Unu</option>
              <option>Doi</option>
              <option>Trei</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="input8" class="form-label">Oraș</label>
            <input type="text" class="form-control" id="input8" name="city" value="<?= htmlspecialchars($city) ?>" placeholder="Oraș">
          </div>
          <div class="col-md-4">
            <label for="input9" class="form-label">Județ/Regiune</label>
            <select id="input9" name="state" class="form-select">
<option value="Alba"><?= $state === 'Alba' ? "selected" : "" ?>Alba</option>
<option value="Arad"><?= $state === 'Arad' ? "selected" : "" ?>Arad</option>
<option value="Argeș"><?= $state === 'Argeș' ? "selected" : "" ?>Argeș</option>
<option value="Bacău"><?= $state === 'Bacău' ? "selected" : "" ?>Bacău</option>
<option value="Bihor"><?= $state === 'Bihor' ? "selected" : "" ?>Bihor</option>
<option value="Bistrița-Năsăud"><?= $state === 'Bistrița-Năsăud' ? "selected" : "" ?>Bistrița-Năsăud</option>
<option value="Botoșani"><?= $state === 'Botoșani' ? "selected" : "" ?>Botoșani</option>
<option value="Brăila"><?= $state === 'Brăila' ? "selected" : "" ?>Brăila</option>
<option value="Brașov"><?= $state === 'Brașov' ? "selected" : "" ?>Brașov</option>
<option value="București"><?= $state === 'București' ? "selected" : "" ?>București</option>
<option value="Buzău"><?= $state === 'Buzău' ? "selected" : "" ?>Buzău</option>
<option value="Călărași"><?= $state === 'Călărași' ? "selected" : "" ?>Călărași</option>
<option value="Caraș-Severin"><?= $state === 'Caraș-Severin' ? "selected" : "" ?>Caraș-Severin</option>
<option value="Cluj"><?= $state === 'Cluj' ? "selected" : "" ?>Cluj</option>
<option value="Constanța"><?= $state === 'Constanța' ? "selected" : "" ?>Constanța</option>
<option value="Covasna"><?= $state === 'Covasna' ? "selected" : "" ?>Covasna</option>
<option value="Dâmbovița"><?= $state === 'Dâmbovița' ? "selected" : "" ?>Dâmbovița</option>
<option value="Dolj"><?= $state === 'Dolj' ? "selected" : "" ?>Dolj</option>
<option value="Galați"><?= $state === 'Galați' ? "selected" : "" ?>Galați</option>
<option value="Giurgiu"><?= $state === 'Giurgiu' ? "selected" : "" ?>Giurgiu</option>
<option value="Gorj"><?= $state === 'Gorj' ? "selected" : "" ?>Gorj</option>
<option value="Harghita"><?= $state === 'Harghita' ? "selected" : "" ?>Harghita</option>
<option value="Hunedoara"><?= $state === 'Hunedoara' ? "selected" : "" ?>Hunedoara</option>
<option value="Ialomița"><?= $state === 'Ialomița' ? "selected" : "" ?>Ialomița</option>
<option value="Iași"><?= $state === 'Iași' ? "selected" : "" ?>Iași</option>
<option value="Ilfov"><?= $state === 'Ilfov' ? "selected" : "" ?>Ilfov</option>
<option value="Maramureș"><?= $state === 'Maramureș' ? "selected" : "" ?>Maramureș</option>
<option value="Mehedinți"><?= $state === 'Mehedinți' ? "selected" : "" ?>Mehedinți</option>
<option value="Mureș"><?= $state === 'Mureș' ? "selected" : "" ?>Mureș</option>
<option value="Neamț"><?= $state === 'Neamț' ? "selected" : "" ?>Neamț</option>
<option value="Olt"><?= $state === 'Olt' ? "selected" : "" ?>Olt</option>
<option value="Prahova"><?= $state === 'Prahova' ? "selected" : "" ?>Prahova</option>
<option value="Sălaj"><?= $state === 'Sălaj' ? "selected" : "" ?>Sălaj</option>
<option value="Satu Mare"><?= $state === 'Satu Mare' ? "selected" : "" ?>Satu Mare</option>
<option value="Sibiu"><?= $state === 'Sibiu' ? "selected" : "" ?>Sibiu</option>
<option value="Suceava"><?= $state === 'Suceava' ? "selected" : "" ?>Suceava</option>
<option value="Teleorman"><?= $state === 'Teleorman' ? "selected" : "" ?>Teleorman</option>
<option value="Timiș"><?= $state === 'Timiș' ? "selected" : "" ?>Timiș</option>
<option value="Tulcea"><?= $state === 'Tulcea' ? "selected" : "" ?>Tulcea</option>
<option value="Vâlcea"><?= $state === 'Vâlcea' ? "selected" : "" ?>Vâlcea</option>
<option value="Vaslui"><?= $state === 'Vaslui' ? "selected" : "" ?>Vaslui</option>
<option value="Vrancea"><?= $state === 'Vrancea' ? "selected" : "" ?>Vrancea</option>
<option value="Extern" <?= $country !== "România" ? "selected" : "" ?>>Extern</option>
              <option selected="">Alege...</option>
              <option>Unu</option>
              <option>Doi</option>
              <option>Trei</option>
            </select>
          </div>
          <div class="col-md-2">
            <label for="input10" class="form-label">Cod poștal</label>
            <input type="text" class="form-control" id="input10" name="zip" value="<?= htmlspecialchars($zip) ?>" placeholder="Cod">
          </div>
          <div class="col-md-12">
            <label for="input11" class="form-label">Adresă</label>
            <textarea class="form-control" id="input11" name="address" placeholder="Adresă ..." rows="4" cols="4"></textarea>
          </div>
          <div class="col-md-12">
            <div class="d-md-flex d-grid align-items-center gap-3">
              <button type="button" class="btn btn-grd-primary px-4">Actualizează profilul</button>
              <button type="button" class="btn btn-light px-4">Resetează</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-4">
    <div class="card rounded-4">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="">
            <h5 class="mb-0 fw-bold">Despre</h5>
          </div>
          <div class="dropdown">
            <a href="javascript:;" class="dropdown-toggle-nocaret options dropdown-toggle" data-bs-toggle="dropdown">
              <span class="material-icons-outlined fs-5">more_vert</span>
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="javascript:;">Acțiune</a></li>
              <li><a class="dropdown-item" href="javascript:;">Altă acțiune</a></li>
              <li><a class="dropdown-item" href="javascript:;">Altceva aici</a></li>
            </ul>
          </div>
        </div>
        <div class="full-info">
          <div class="info-list d-flex flex-column gap-3">
            <div class="info-list-item d-flex align-items-center gap-3"><span class="material-icons-outlined">account_circle</span><p class="mb-0">Nume complet: </p></div>
            <div class="info-list-item d-flex align-items-center gap-3"><span class="material-icons-outlined">done</span><p class="mb-0">Status: activ</p></div>
            <div class="info-list-item d-flex align-items-center gap-3"><span class="material-icons-outlined">code</span><p class="mb-0">Rol: Dezvoltator</p></div>
            <div class="info-list-item d-flex align-items-center gap-3"><span class="material-icons-outlined">flag</span><p class="mb-0">Țară: Italia</p></div>
            <div class="info-list-item d-flex align-items-center gap-3"><span class="material-icons-outlined">language</span><p class="mb-0">Limbă: Engleză</p></div>
            <div class="info-list-item d-flex align-items-center gap-3"><span class="material-icons-outlined">send</span><p class="mb-0">Email: anaaremere.xyz</p></div>
            <div class="info-list-item d-flex align-items-center gap-3"><span class="material-icons-outlined">call</span><p class="mb-0">Telefon: +40 737 189 948</p></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card rounded-4">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="">
            
<h5 class="mb-0 fw-bold">Conturi</h5>
<div class="d-flex flex-column gap-3 mt-4">
  <a href="connect-google.php" class="btn btn-light border rounded-4 shadow-sm py-2 px-4 text-start">
    <img src="assets/images/apps/05.png" width="24" class="me-2"> Conectează cu Google
  </a>
  <a href="connect-instagram.php" class="btn btn-light border rounded-4 shadow-sm py-2 px-4 text-start">
    <img src="assets/images/apps/06.png" width="24" class="me-2"> Conectează cu Instagram
  </a>
  <a href="connect-facebook.php" class="btn btn-light border rounded-4 shadow-sm py-2 px-4 text-start">
    <img src="assets/images/apps/17.png" width="24" class="me-2"> Conectează cu Facebook
  </a>
</div>

            
          </div>
          
            
          </div>
          
            
          </div>
          
          
          
            
          </div>
        </div>
      </div>
    </div>
  </div>
</div>




              </div>
            </div>

           </div>
        </div><!--end row-->
       


    </div>
  </main>
  <!--end main wrapper-->


    <!--start overlay-->
    <div class="overlay btn-toggle"></div>
    <!--end overlay-->


     <!--start footer-->
<footer class="page-footer">
<p class="mb-0">Made with ❤️ by Stroe Marius</p>
</footer>
<!--end footer-->

<!--start switcher-->
<button class="btn btn-grd btn-grd-primary position-fixed bottom-0 end-0 m-3 d-flex align-items-center gap-2" data-bs-target="#staticBackdrop" data-bs-toggle="offcanvas" type="button">
<i class="material-icons-outlined">tune</i>Personalizează
  </button>
<div class="offcanvas offcanvas-end" data-bs-scroll="true" id="staticBackdrop" tabindex="-1">
<div class="offcanvas-header border-bottom h-70">
<div class="">
<h5 class="mb-0">Schimbă tema</h5>
<p class="mb-0">Personalizează tema</p>
</div>
<a class="primaery-menu-close" data-bs-dismiss="offcanvas" href="javascript:;">
<i class="material-icons-outlined">close</i>
</a>
</div>
<div class="offcanvas-body">
<div>
<p>Variante de temă</p>
<div class="row g-3">
<div class="col-12 col-xl-6">
<input checked="" class="btn-check" id="BlueTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="BlueTheme">
<span class="material-icons-outlined">contactless</span>
<span>Albastru</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="LightTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="LightTheme">
<span class="material-icons-outlined">light_mode</span>
<span>Mod luminos</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="DarkTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="DarkTheme">
<span class="material-icons-outlined">dark_mode</span>
<span>Mod întunecat</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="SemiDarkTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="SemiDarkTheme">
<span class="material-icons-outlined">contrast</span>
<span>Semi Închis</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="BoderedTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="BoderedTheme">
<span class="material-icons-outlined">border_style</span>
<span>Stil încadrat</span>
</label>
</div>
</div><!--end row-->
</div>
</div>
</div>
<!--start switcher-->
<!--bootstrap js-->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<!--plugins-->
<script src="assets/js/jquery.min.js"></script>
<!--plugins-->
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/plugins/metismenu/metisMenu.min.js"></script>
<script src="assets/plugins/apexchart/apexcharts.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/plugins/peity/jquery.peity.min.js"></script>
<script>
    $(".data-attributes span").peity("donut")
  </script>
<script src="assets/js/main.js"></script>
<script src="assets/js/dashboard1.js"></script>
<script>
	   new PerfectScrollbar(".user-list")
  </script>
</body>
</html>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const country = document.getElementById("input7");
  const region = document.getElementById("input9");
  const romaniaRegions = ['Alba', 'Arad', 'Argeș', 'Bacău', 'Bihor', 'Bistrița-Năsăud', 'Botoșani', 'Brăila', 'Brașov', 'București', 'Buzău', 'Călărași', 'Caraș-Severin', 'Cluj', 'Constanța', 'Covasna', 'Dâmbovița', 'Dolj', 'Galați', 'Giurgiu', 'Gorj', 'Harghita', 'Hunedoara', 'Ialomița', 'Iași', 'Ilfov', 'Maramureș', 'Mehedinți', 'Mureș', 'Neamț', 'Olt', 'Prahova', 'Sălaj', 'Satu Mare', 'Sibiu', 'Suceava', 'Teleorman', 'Timiș', 'Tulcea', 'Vâlcea', 'Vaslui', 'Vrancea'];

  function updateRegions() {
    const selected = country.value;
    region.innerHTML = "";
    if (selected === "România") {
      romaniaRegions.forEach(function(j) {
        const opt = document.createElement("option");
        opt.value = j;
        opt.text = j;
        region.appendChild(opt);
      });
    } else {
      const opt = document.createElement("option");
      opt.value = "Extern";
      opt.text = "Extern";
      region.appendChild(opt);
    }
  }

  country.addEventListener("change", updateRegions);
  updateRegions(); // load at start
});
</script>
