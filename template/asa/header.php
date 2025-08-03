<?php
ob_start();
session_start();
require_once 'db_connect.php';

if (!function_exists('hexToRgb')) {
  function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
      $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
      $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
      $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
  }
}

$user_role = $_SESSION['user_role'] ?? 'Administrator';
$current_theme_mode = $_SESSION['theme_mode'] ?? 'blue-theme';
$current_accent_color = '#0d6efd';
$current_font_family = 'Noto Sans';

$current_language = $_SESSION['lang'] ?? 'ro';
$available_languages = [
  'en' => ['name' => 'English',  'flag' => '01'],
  'ro' => ['name' => 'Română',   'flag' => '02'],
  'de' => ['name' => 'Deutsch',  'flag' => '03'],
  'fr' => ['name' => 'Français', 'flag' => '04'],
  'it' => ['name' => 'Italiano', 'flag' => '05'],
  'es' => ['name' => 'Español',  'flag' => '06'],
];

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
  $_SESSION['lang'] = $_GET['lang'];
  if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $lang = $_GET['lang'];
    $stmt = $conn->prepare("UPDATE users SET language = ? WHERE id = ?");
    $stmt->bind_param("si", $lang, $user_id);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
  exit;
}

$current_lang_info = $available_languages[$current_language] ?? $available_languages['ro'];
$user_profile_image = 'assets/images/avatars/01.png'; // Calea către imaginea de profil
// Poți seta și $_SESSION['user_name'] la login, de exemplu: $_SESSION['user_name'] = "Nume Utilizator";
?>
<!DOCTYPE html>

<html data-bs-theme="<?php echo $current_theme_mode; ?>" lang="<?php echo $current_language; ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<title>NTS TOUR | NTS ADMINISTRARE FLOTA </title>
<link href="assets/images/favicon-32x32.png" rel="icon" type="image/png"/>
<link href="assets/css/pace.min.css" rel="stylesheet"/>
<script src="assets/js/pace.min.js"></script>
<link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet"/>
<link href="assets/plugins/metismenu/metisMenu.min.css" rel="stylesheet" type="text/css"/>
<link href="assets/plugins/metismenu/mm-vertical.css" rel="stylesheet" type="text/css"/>
<link href="assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" type="text/css"/>
<link href="assets/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($current_font_family); ?>:wght@300;400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet"/>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css" rel="stylesheet"/>
<link href="assets/css/bootstrap-extended.css" rel="stylesheet"/>
<link href="sass/main.css" rel="stylesheet"/>
<link href="sass/dark-theme.css" rel="stylesheet"/>
<link href="sass/blue-theme.css" rel="stylesheet"/>
<link href="sass/semi-dark.css" rel="stylesheet"/>
<link href="sass/bordered-theme.css" rel="stylesheet"/>
<link href="sass/responsive.css" rel="stylesheet"/>
</head>
<body style="font-family: '<?php echo $current_font_family; ?>', sans-serif;"> <header class="top-header">
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
<a class="nav-link dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown" href="javascript:;"><img alt="" src="assets/images/county/<?php echo htmlspecialchars($current_lang_info['flag']); ?>.png" width="22"/>
</a>
<ul class="dropdown-menu dropdown-menu-end">
<?php foreach ($available_languages as $code => $lang): ?>
<li><a class="dropdown-item d-flex align-items-center py-2" href="javascript:;" data-lang="<?php echo htmlspecialchars($code); ?>"><img alt="" src="assets/images/county/<?php echo htmlspecialchars($lang['flag']); ?>.png" width="20"/><span class="ms-2"><?php echo htmlspecialchars($lang['name']); ?></span></a>
</li>
<?php endforeach; ?>
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
<i class="material-icons-outlined text-white">Youtube</i>
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
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" data-bs-auto-close="outside" data-bs-toggle="dropdown" href="javascript:;" style="display: inline-block;">
    <i class="material-icons-outlined" style="font-size: 24px; position: relative; z-index: 1;">notifications</i>
    
    <span class="badge-notify"
          style="position: absolute; top: -2px; right: -2px; background-color: red; color: white;
                 border-radius: 50%; padding: 2px 6px; font-size: 10px; z-index: 3;">
      5 </span>
  </a>

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
<a class="dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown" href="javascript:;">
<img alt="" class="rounded-circle p-1 border" height="45" src="<?php echo htmlspecialchars($user_profile_image); ?>" width="45"/>
</a>
<div class="dropdown-menu dropdown-user dropdown-menu-end shadow">
<a class="dropdown-item gap-2 py-2" href="javascript:;">
<div class="text-center">
<img alt="" class="rounded-circle p-1 shadow mb-3" height="90" src="<?php echo htmlspecialchars($user_profile_image); ?>" width="90"/>
<h5 class="user-name mb-0 fw-bold">Salut, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Marius'); ?></h5>
</div>
</a>
<hr class="dropdown-divider"/>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="profil-utilizator.php"><i class="material-icons-outlined">person_outline</i>Profil</a>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="setari.php"><i class="material-icons-outlined">local_bar</i>Setări</a>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="index.php"><i class="material-icons-outlined">dashboard</i>Panou control</a>
<hr class="dropdown-divider"/>
<a class="dropdown-item d-flex align-items-center gap-2 py-2" href="logout.php"><i class="material-icons-outlined">power_settings_new</i>Ieșire</a>
</div>
</li>

</nav>
</header>
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
<ul class="metismenu" id="sidenav">
<li class="menu-label">Hub Principal</li>

 <a href="index.php"
   class="btn d-inline-flex align-items-center"
   style="gap: 8px; background-color: transparent; color: white; border: none; box-shadow: none;">
  <i class="material-icons-outlined">home</i>
  Acasă
</a>



<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">directions_bus</i>
    <span style="margin-left: 8px;">Flotă</span>
  </a>
  <ul>
    <li><a href="vehicule.php"><i class="material-icons-outlined">directions_bus</i><span style="margin-left: 8px;">Vehicule</span></a></li>
    <li><a href="adauga-vehicul.php"><i class="material-icons-outlined">add_circle_outline</i><span style="margin-left: 8px;">Adaugă Vehicul</span></a></li>
    <li><a href="curse-active.php"><i class="material-icons-outlined">commute</i><span style="margin-left: 8px;">Curse Active</span></a></li>
    <li><a href="planificare-rute.php"><i class="material-icons-outlined">event_note</i><span style="margin-left: 8px;">Planificare Curse</span></a></li>
    <li><a href="istoric-curse.php"><i class="material-icons-outlined">history</i><span style="margin-left: 8px;">Istoric Curse</span></a></li>
    <li><a href="alocare-vehicul-sofer.php"><i class="material-icons-outlined">person_add</i><span style="margin-left: 8px;">Alocare Vehicul Șofer</span></a></li>
    <li><a href="alimentare_combustibil.php"><i class="material-icons-outlined">local_gas_station</i><span style="margin-left: 8px;">Alimentare Vehicul</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">folder</i>
    <span style="margin-left: 8px;">Documente</span>
  </a>
  <ul>
    <li><a href="documente-vehicule.php"><i class="material-icons-outlined">folder_open</i><span style="margin-left: 8px;">Documente Vehicule</span></a></li>
    <li><a href="contracte-angajati.php"><i class="material-icons-outlined">badge</i><span style="margin-left: 8px;">Contracte Angajați</span></a></li>
    <li><a href="contracte-clienti.php"><i class="material-icons-outlined">people_alt</i><span style="margin-left: 8px;">Contracte Clienți</span></a></li>
    <li><a href="polite-asigurare.php"><i class="material-icons-outlined">schedule</i><span style="margin-left: 8px;">Expirări Curente</span></a></li>
    <li><a href="adauga-document.php"><i class="material-icons-outlined">cloud_upload</i><span style="margin-left: 8px;">Încărcare Documente</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">groups</i>
    <span style="margin-left: 8px;">Angajați</span>
  </a>
  <ul>
    <li><a href="lista-angajati.php"><i class="material-icons-outlined">group</i><span style="margin-left: 8px;">Lista Angajați</span></a></li>
    <li><a href="fise-individuale.php"><i class="material-icons-outlined">assignment_ind</i><span style="margin-left: 8px;">Fișe Individuale</span></a></li>
    <li><a href="adauga-angajat.php"><i class="material-icons-outlined">person_add_alt</i><span style="margin-left: 8px;">Adaugă Angajat</span></a></li>
    <li><a href="disponibilitate-grafica.php"><i class="material-icons-outlined">event_available</i><span style="margin-left: 8px;">Disponibilitate</span></a></li>
    <li><a href="salarii-bonusuri.php"><i class="material-icons-outlined">payments</i><span style="margin-left: 8px;">Salarii & Bonusuri</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">supervisor_account</i>
    <span style="margin-left: 8px;">Clienți</span>
  </a>
  <ul>
    <li><a href="lista-clienti.php"><i class="material-icons-outlined">supervisor_account</i><span style="margin-left: 8px;">Lista Clienți</span></a></li>
    <li><a href="adauga-client.php"><i class="material-icons-outlined">person_add_alt_1</i><span style="margin-left: 8px;">Adaugă Clienți</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">account_balance</i>
    <span style="margin-left: 8px;">Contabilitate</span>
  </a>
  <ul>
    <li><a href="facturi-emise.php"><i class="material-icons-outlined">receipt_long</i><span style="margin-left: 8px;">Facturi emise</span></a></li>
    <li><a href="emite-factura-noua.php"><i class="material-icons-outlined">post_add</i><span style="margin-left: 8px;">Emite Factură Nouă</span></a></li>
    <li><a href="incasari-plati.php"><i class="material-icons-outlined">account_balance_wallet</i><span style="margin-left: 8px;">Încasări & Plăți</span></a></li>
    <li><a href="cheltuieli-flota.php"><i class="material-icons-outlined">trending_down</i><span style="margin-left: 8px;">Cheltuieli Flotă</span></a></li>
    <li><a href="raport-financiar-lunar.php"><i class="material-icons-outlined">bar_chart</i><span style="margin-left: 8px;">Raport Financiar Lunar</span></a></li>
    <li><a href="cash-flow-vizual.php"><i class="material-icons-outlined">show_chart</i><span style="margin-left: 8px;">Cash-Flow Vizual</span></a></li>
    <li><a href="export-contabilitate.php"><i class="material-icons-outlined">file_download</i><span style="margin-left: 8px;">Export Contabilitate</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">build_circle</i>
    <span style="margin-left: 8px;">Mentenanță</span>
  </a>
  <ul>
    <li><a href="plan-revizii.php"><i class="material-icons-outlined">build</i><span style="margin-left: 8px;">Programări Service</span></a></li>
    <li><a href="notificari-probleme-raportate.php"><i class="material-icons-outlined">report_problem</i><span style="margin-left: 8px;">Probleme Raportate</span></a></li>
    <li><a href="confirmare-lucrari.php"><i class="material-icons-outlined">check_circle</i><span style="margin-left: 8px;">Confirmare Lucrări Efectuate</span></a></li>
    <li><a href="istoric-reparatii.php"><i class="material-icons-outlined">engineering</i><span style="margin-left: 8px;">Istoric Reparații</span></a></li>
    <li><a href="istoric-curse.php"><i class="material-icons-outlined">history</i><span style="margin-left: 8px;">Istoric Curse</span></a></li>
    <li><a href="plan-revizii.php"><i class="material-icons-outlined">event</i><span style="margin-left: 8px;">Planificare Revizii</span></a></li>
    <li><a href="istoric-mentenanta.php"><i class="material-icons-outlined">timeline</i><span style="margin-left: 8px;">Istoric Mentenanță pe Vehicul</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">bar_chart</i>
    <span style="margin-left: 8px;">Rapoarte</span>
  </a>
  <ul>
    <li><a href="raport-flota-zilnic.php"><i class="material-icons-outlined">today</i><span style="margin-left: 8px;">Raport Flotă Zilnic</span></a></li>
    <li><a href="raport-flota-lunar.php"><i class="material-icons-outlined">date_range</i><span style="margin-left: 8px;">Raport Flotă Lunar</span></a></li>
    <li><a href="raport-financiar.php"><i class="material-icons-outlined">analytics</i><span style="margin-left: 8px;">Raport Financiar</span></a></li>
    <li><a href="raport-consum-combustibil.php"><i class="material-icons-outlined">local_gas_station</i><span style="margin-left: 8px;">Raport Consum Combustibil</span></a></li>
    <li><a href="raport-cost-km.php"><i class="material-icons-outlined">speed</i><span style="margin-left: 8px;">Cost/Km per Vehicul</span></a></li>
    <li><a href="auth-basic-login.html"><i class="material-icons-outlined">file_download</i><span style="margin-left: 8px;">Export Rapoarte</span></a></li>
  </ul>
</li>

<li>
  <a class="has-arrow" href="javascript:;">
    <i class="material-icons-outlined">notifications_active</i>
    <span style="margin-left: 8px;">Notificări</span>
  </a>
  <ul>
    <li><a href="notificari-documente-expirate.php"><i class="material-icons-outlined">notification_important</i><span style="margin-left: 8px;">Alerte Documente</span></a></li>
    <li><a href="notificari-mentenanta.php"><i class="material-icons-outlined">build_circle</i><span style="margin-left: 8px;">Alerte Mentenanță</span></a></li>
    <li><a href="notificari-soferi.php"><i class="material-icons-outlined">emoji_transportation</i><span style="margin-left: 8px;">Alerte Șoferi</span></a></li>
    <li><a href="notificari-curse.php"><i class="material-icons-outlined">departure_board</i><span style="margin-left: 8px;">Alerte Curse</span></a></li>
    <li><a href="notificari-probleme-raportate.php"><i class="material-icons-outlined">report_problem</i><span style="margin-left: 8px;">Probleme Raportate</span></a></li>
    <li><a href="setari-notificari.php"><i class="material-icons-outlined">settings</i><span style="margin-left: 8px;">Setări Notificări</span></a></li>
  </ul>
</li>

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
</ul>
<div class="user-role-display text-center p-3 text-muted" style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1);">
    Rol: <?php echo htmlspecialchars($user_role); ?>
</div>
</div>
</aside>
<main class="main-wrapper">
<div class="main-content">

</ol>
</nav>
</div>
</div>
</div>
</div>
</div>
</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>
</main>
<div class="overlay btn-toggle"></div>
<footer class="page-footer">
<p class="mb-0">Made with ❤️ by Stroe Marius</p>
</footer>
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
</div></div>
</div>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
<script src="assets/plugins/metismenu/metisMenu.min.js"></script>
<script src="assets/plugins/apexchart/apexcharts.min.js"></script>
<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
<script src="assets/js/peity/jquery.peity.min.js"></script>
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