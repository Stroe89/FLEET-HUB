<?php
// Acest fișier conține doar secțiunea HTML <footer>.
// Nu conține alte elemente de structură HTML (doctype, head, body, style, etc.).
?>
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
<!-- TEME NOI MODERNE -->
<div class="col-12 col-xl-6">
<input class="btn-check" id="CyberpunkTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="CyberpunkTheme">
<span class="material-icons-outlined">electric_bolt</span>
<span>Cyberpunk</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="OceanTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="OceanTheme">
<span class="material-icons-outlined">waves</span>
<span>Ocean</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="ForestTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="ForestTheme">
<span class="material-icons-outlined">park</span>
<span>Pădure</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="SunsetTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="SunsetTheme">
<span class="material-icons-outlined">wb_sunny</span>
<span>Apus</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="RoseTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="RoseTheme">
<span class="material-icons-outlined">favorite</span>
<span>Rose Gold</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="SpaceTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="SpaceTheme">
<span class="material-icons-outlined">nights_stay</span>
<span>Spațiu</span>
</label>
</div>
<div class="col-12 col-xl-6">
<input class="btn-check" id="MintTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="MintTheme">
<span class="material-icons-outlined">eco</span>
<span>Mentă</span>
</label>
</div>
<!-- TEMA NAVY STELLAR CU ANIMAȚII -->
<div class="col-12 col-xl-6">
<input class="btn-check" id="NavyStellarTheme" name="theme-options" type="radio"/>
<label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4" for="NavyStellarTheme">
<span class="material-icons-outlined">auto_awesome</span>
<span>Navy Stellar ⭐</span>
</label>
</div>
</div></div>
</div>

<!-- NTS TOUR - Sistema Globală de Teme -->
<script src="assets/js/nts-theme-system.js"></script>
</div>