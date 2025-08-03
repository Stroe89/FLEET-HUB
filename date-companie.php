<?php
// date-companie.php - Pagina de setări a datelor companiei
require_once 'core/auth_middleware.php';
require_once 'db_connect.php';

init_page_access('company_admin', 'index.php', "Nu aveți permisiunea de a accesa setările companiei. Contactați administratorul."); 

$current_user_id = $_SESSION['user_id']; 

$date_companie = [
    'nume_companie' => '', 'cui' => '', 'nr_reg_com' => '', 'adresa' => '',
    'oras' => '', 'judet' => '', 'cod_postal' => '', 'telefon' => '',
    'email' => '', 'website' => '', 'logo_path' => '',
    'bank_name' => '', 'bank_iban' => '', 'bank_swift' => '',
    'reprezentant_legal' => '', 'functie_reprezentant' => '',
    'cod_fiscal' => '', 'activitate_principala' => '',
    'numar_angajati' => '', 'capital_social' => '',
    'telefon_secundar' => '', 'email_secundar' => '',
    'tara' => '', 'regiune' => '' 
];

$date_companie = getCompanyDataForUser($conn, $current_user_id); 

require_once 'template/header.php'; 
?>

<main class="main-wrapper">
    <div class="main-content-area">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Setări</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Setări Companie Avansate</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title"><i class="bx bx-cog me-2"></i>Configurare Detalii Companie</h4>
                        <p class="text-muted">Introduceți și actualizați detaliile complete ale companiei dumneavoastră. Aceste informații, inclusiv logo-ul, vor fi integrate automat în rapoarte și documente, oferind un aspect profesional și coerent.</p>
                        <hr>

                        <form id="companyDataForm" action="process_date_companie.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_company_data">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($current_user_id); ?>">
                            <input type="hidden" name="existing_logo_path" value="<?php echo htmlspecialchars($date_companie['logo_path'] ?? ''); ?>">
                            
                            <ul class="nav nav-tabs mb-4" id="dateCompanieTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab-info-tab" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">🧾 Informații Generale Companie</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab-contact-tab" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button" role="tab">📍 Detalii de Contact și Adresă</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab-bancar-tab" data-bs-toggle="tab" data-bs-target="#tab-bancar" type="button" role="tab">🏦 Detalii Bancare</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab-logo-tab" data-bs-toggle="tab" data-bs-target="#tab-logo" type="button" role="tab">🧑‍⚖️ Reprezentant Legal și Logo</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="dateCompanieTabsContent">
                                <div class="tab-pane fade show active" id="tab-info" role="tabpanel" aria-labelledby="tab-info-tab">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="numeCompanie" class="form-label">Nume Companie: <span class="text-danger">*</span></label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-building-house input-icon"></i>
                                                <input type="text" class="form-control" id="numeCompanie" name="nume_companie" value="<?php echo htmlspecialchars($date_companie['nume_companie'] ?? ''); ?>" required placeholder="Denumire Legală Companie">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cui" class="form-label">CUI (Cod Unic de Înregistrare):</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-id-card input-icon"></i>
                                                <input type="text" class="form-control" id="cui" name="cui" value="<?php echo htmlspecialchars($date_companie['cui'] ?? ''); ?>" placeholder="RO12345678">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nrRegCom" class="form-label">Nr. Reg. Com. (Număr în Registrul Comerțului):</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-book-bookmark input-icon"></i>
                                                <input type="text" class="form-control" id="nrRegCom" name="nr_reg_com" value="<?php echo htmlspecialchars($date_companie['nr_reg_com'] ?? ''); ?>" placeholder="J40/123/2000">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="codFiscal" class="form-label">Cod Fiscal (dacă e diferit de CUI):</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-dollar-circle input-icon"></i>
                                                <input type="text" class="form-control" id="codFiscal" name="cod_fiscal" value="<?php echo htmlspecialchars($date_companie['cod_fiscal'] ?? ''); ?>" placeholder="12345678">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="activitatePrincipala" class="form-label">Activitate Principală (Cod CAEN / Descriere):</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-briefcase input-icon"></i>
                                                <input type="text" class="form-control" id="activitatePrincipala" name="activitate_principala" value="<?php echo htmlspecialchars($date_companie['activitate_principala'] ?? ''); ?>" placeholder="Ex: Transporturi rutiere de mărfuri (CAEN 4941)">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="numarAngajati" class="form-label">Număr Angajați:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-group input-icon"></i>
                                                <input type="number" class="form-control" id="numarAngajati" name="numar_angajati" value="<?php echo htmlspecialchars($date_companie['numar_angajati'] ?? ''); ?>" placeholder="Ex: 50">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="capitalSocial" class="form-label">Capital Social (RON):</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-wallet input-icon"></i>
                                                <input type="text" class="form-control" id="capitalSocial" name="capital_social" value="<?php echo htmlspecialchars($date_companie['capital_social'] ?? ''); ?>" placeholder="Ex: 200.000,00">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tab-contact" role="tabpanel" aria-labelledby="tab-contact-tab">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="tara" class="form-label">Țara:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-globe input-icon"></i>
                                                <select class="form-select" id="tara" name="tara">
                                                    <option value="">Selectează Țara</option>
                                                    </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="regiune" class="form-label">Regiune/Județ/Stat:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-map-pin input-icon"></i>
                                                <select class="form-select" id="regiune" name="regiune">
                                                    <option value="">Selectează Regiunea</option>
                                                    </select>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="adresa" class="form-label">Adresă Sediu Social:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-map input-icon"></i>
                                                <textarea class="form-control" id="adresa" name="adresa" rows="2" placeholder="Strada Exemplu, Nr. 10, Bloc A"><?php echo htmlspecialchars($date_companie['adresa'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="oras" class="form-label">Oraș:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-city input-icon"></i>
                                                <input type="text" class="form-control" id="oras" name="oras" value="<?php echo htmlspecialchars($date_companie['oras'] ?? ''); ?>" placeholder="București">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="codPostal" class="form-label">Cod Poștal:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-hash input-icon"></i>
                                                <input type="text" class="form-control" id="codPostal" name="cod_postal" value="<?php echo htmlspecialchars($date_companie['cod_postal'] ?? ''); ?>" placeholder="010000">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="telefon" class="form-label">Telefon Principal:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-phone-call input-icon"></i>
                                                <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($date_companie['telefon'] ?? ''); ?>" placeholder="+40 7xx xxx xxx">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="telefonSecundar" class="form-label">Telefon Secundar (Opțional):</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-phone input-icon"></i>
                                                <input type="tel" class="form-control" id="telefonSecundar" name="telefon_secundar" value="<?php echo htmlspecialchars($date_companie['telefon_secundar'] ?? ''); ?>" placeholder="+40 7yy yyy yyy">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Principal:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-at input-icon"></i>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($date_companie['email'] ?? ''); ?>" placeholder="contact@compania.ro">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emailSecundar" class="form-label">Email Secundar (Opțional):</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-envelope input-icon"></i>
                                                <input type="email" class="form-control" id="emailSecundar" name="email_secundar" value="<?php echo htmlspecialchars($date_companie['email_secundar'] ?? ''); ?>" placeholder="office@compania.ro">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="website" class="form-label">Website:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-globe input-icon"></i>
                                                <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($date_companie['website'] ?? ''); ?>" placeholder="https://www.compania.ro">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tab-bancar" role="tabpanel" aria-labelledby="tab-bancar-tab">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label for="bankName" class="form-label">Nume Bancă:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-building input-icon"></i>
                                                <input type="text" class="form-control" id="bankName" name="bank_name" value="<?php echo htmlspecialchars($date_companie['bank_name'] ?? ''); ?>" placeholder="Banca Transilvania">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bankIban" class="form-label">IBAN:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-barcode input-icon"></i>
                                                <input type="text" class="form-control" id="bankIban" name="bank_iban" value="<?php echo htmlspecialchars($date_companie['bank_iban'] ?? ''); ?>" placeholder="ROxx BTRL xxxx xxxx xxxx xxxx">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bankSwift" class="form-label">SWIFT/BIC:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-transfer-alt input-icon"></i>
                                                <input type="text" class="form-control" id="bankSwift" name="bank_swift" value="<?php echo htmlspecialchars($date_companie['bank_swift'] ?? ''); ?>" placeholder="BTRLRO22">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tab-logo" role="tabpanel" aria-labelledby="tab-logo-tab">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="reprezentantLegal" class="form-label">Nume Reprezentant Legal:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-user input-icon"></i>
                                                <input type="text" class="form-control" id="reprezentantLegal" name="reprezentant_legal" value="<?php echo htmlspecialchars($date_companie['reprezentant_legal'] ?? ''); ?>" placeholder="Popescu Ion">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="functieReprezentant" class="form-label">Funcție Reprezentant Legal:</label>
                                            <div class="icon-input-group">
                                                <i class="bx bx-briefcase-alt input-icon"></i>
                                                <input type="text" class="form-control" id="functieReprezentant" name="functie_reprezentant" value="<?php echo htmlspecialchars($date_companie['functie_reprezentant'] ?? ''); ?>" placeholder="Administrator / Director General">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="logoCompanie" class="form-label">Logo Companie (PNG, JPG, GIF - Max 5MB):</label>
                                            <input class="form-control" type="file" id="logoCompanie" name="logo" accept="image/png, image/jpeg, image/gif">
                                            <?php if (!empty($date_companie['logo_path'])): ?>
                                                <div class="mt-3 d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($date_companie['logo_path']); ?>" alt="Logo Companie" class="logo-preview me-3" style="max-height: 80px;">
                                                    <div>
                                                        <small class="text-muted">Logo curent: <a href="<?php echo htmlspecialchars($date_companie['logo_path']); ?>" target="_blank">vizualizează</a></small><br>
                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" type="checkbox" id="deleteLogo" name="delete_logo" value="1">
                                                            <label class="form-check-label" for="deleteLogo">Șterge logo existent</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">Niciun logo încărcat. Vă rugăm să adăugați unul pentru rapoarte profesionale.</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-outline-primary" id="previewPdfBtn">
                                    <i class="bx bxs-file-pdf me-1"></i>Previzualizare PDF
                                </button>
                                <button type="button" class="btn btn-outline-success" id="sendToClientBtn" data-bs-toggle="modal" data-bs-target="#sendModal">
                                    <i class="bx bx-paper-plane me-1"></i>Trimite client
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Salvează setări
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal pentru trimiterea la client -->
<div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="sendModalLabel"><i class="bx bx-paper-plane me-2"></i>Trimite date companie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="clientEmail" class="form-label">Email client:</label>
                    <input type="email" class="form-control" id="clientEmail" placeholder="nume@client.com">
                </div>
                <div class="mb-3">
                    <label for="messageText" class="form-label">Mesaj personalizat:</label>
                    <textarea class="form-control" id="messageText" rows="3" placeholder="Salut, vă trimit datele companiei noastre..."></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="includeLogo" checked>
                    <label class="form-check-label" for="includeLogo">
                        Include logo în document
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-success" id="confirmSendBtn">
                    <i class="bx bx-send me-1"></i>Trimite acum
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Previzualizare PDF -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="pdfPreviewModalLabel"><i class="bx bxs-file-pdf me-2"></i>Previzualizare PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="border rounded p-3 bg-white shadow-sm">
                    <div class="pdf-preview-container text-center p-4">
                        <div class="spinner-border text-primary" role="status" id="pdfLoadingSpinner">
                            <span class="visually-hidden">Se încarcă...</span>
                        </div>
                        <iframe id="pdfPreviewFrame" class="w-100" style="height: 70vh; display: none;" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-primary" id="downloadPdfBtn">
                    <i class="bx bx-download me-1"></i>Descarcă PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logica pentru popularea dropdown-urilor de Țară și Regiune/Județ
        const countrySelect = document.getElementById('tara');
        const regionSelect = document.getElementById('regiune');
        const currentSelectedRegion = "<?php echo htmlspecialchars($date_companie['regiune'] ?? ''); ?>";
        const currentSelectedCountry = "<?php echo htmlspecialchars($date_companie['tara'] ?? ''); ?>";

        let europeRegionsData = {};

        // Încarcă datele JSON
        fetch('assets/data/regiuni-europa.json')
            .then(response => response.json())
            .then(data => {
                europeRegionsData = data;
                countrySelect.innerHTML = '<option value="">Selectează Țara</option>';
                for (const countryName in europeRegionsData) {
                    const option = document.createElement('option');
                    option.value = countryName;
                    option.textContent = countryName;
                    if (countryName === currentSelectedCountry) {
                        option.selected = true;
                    }
                    countrySelect.appendChild(option);
                }

                if (currentSelectedCountry) {
                    populateRegions(currentSelectedCountry);
                    if (currentSelectedRegion) {
                        regionSelect.value = currentSelectedRegion;
                    }
                }
            })
            .catch(error => console.error('Eroare la încărcarea datelor JSON:', error));

        // Funcția pentru popularea dropdown-ului de regiuni
        function populateRegions(country) {
            regionSelect.innerHTML = '<option value="">Selectează Regiunea</option>';
            if (europeRegionsData[country]) {
                europeRegionsData[country].sort().forEach(region => { 
                    const option = document.createElement('option');
                    option.value = region;
                    option.textContent = region;
                    regionSelect.appendChild(option);
                });
            }
        }

        // Adaugă event listener pentru schimbarea țării
        countrySelect.addEventListener('change', function() {
            populateRegions(this.value);
        });

        // LOGICA PREVIZUALIZARE PDF ÎN MODAL
        const previewPdfBtn = document.getElementById('previewPdfBtn');
        const companyDataForm = document.getElementById('companyDataForm');
        const pdfPreviewModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
        const pdfPreviewFrame = document.getElementById('pdfPreviewFrame');
        const pdfLoadingSpinner = document.getElementById('pdfLoadingSpinner');

        previewPdfBtn.addEventListener('click', function() {
            const formData = new FormData(companyDataForm);
            
            // Asigură-te că valoarea selectată din dropdown-uri este adăugată la formData
            formData.set('tara', countrySelect.value);
            formData.set('regiune', regionSelect.value);

            const logoInput = document.getElementById('logoCompanie');
            if (logoInput.files && logoInput.files[0]) {
                formData.set('logo', logoInput.files[0]); 
            }

            // Afișează spinnerul și ascunde iframe-ul
            pdfLoadingSpinner.style.display = 'block';
            pdfPreviewFrame.style.display = 'none';
            
            // Deschide modalul imediat
            pdfPreviewModal.show();

            fetch('preview_company_data_pdf.php', {
                method: 'POST',
                body: formData 
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                } else {
                    return response.text().then(text => { 
                        throw new Error('Eroare răspuns server: ' + text);
                    });
                }
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                
                // Ascunde spinnerul și afișează iframe-ul
                pdfLoadingSpinner.style.display = 'none';
                pdfPreviewFrame.style.display = 'block';
                
                // Setează sursa iframe-ului
                pdfPreviewFrame.src = url;
                
                // Salvează URL-ul pentru descărcare
                pdfPreviewFrame.dataset.downloadUrl = url;
            })
            .catch(error => {
                console.error('Eroare la previzualizare PDF:', error);
                alert('A apărut o eroare la previzualizarea PDF: ' + error.message);
            });
        });

        // Descărcare PDF
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const downloadUrl = pdfPreviewFrame.dataset.downloadUrl;
            if (downloadUrl) {
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = 'date-companie-preview.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        });

        // LOGICA TRIMITERE LA CLIENT
        const sendToClientBtn = document.getElementById('sendToClientBtn');
        const confirmSendBtn = document.getElementById('confirmSendBtn');
        const sendModal = new bootstrap.Modal(document.getElementById('sendModal'));

        confirmSendBtn.addEventListener('click', function() {
            const clientEmail = document.getElementById('clientEmail').value;
            const messageText = document.getElementById('messageText').value;
            const includeLogo = document.getElementById('includeLogo').checked;
            
            if (!clientEmail) {
                alert('Vă rugăm introduceți adresa de email a clientului.');
                return;
            }

            const formData = new FormData(companyDataForm);
            formData.set('tara', countrySelect.value);
            formData.set('regiune', regionSelect.value);
            formData.append('client_email', clientEmail);
            formData.append('message', messageText);
            formData.append('include_logo', includeLogo ? '1' : '0');
            
            const logoInput = document.getElementById('logoCompanie');
            if (logoInput.files && logoInput.files[0]) {
                formData.set('logo', logoInput.files[0]); 
            }

            // Afișează indicator de încărcare
            confirmSendBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Se trimite...';
            confirmSendBtn.disabled = true;

            fetch('send_company_data_pdf.php', {
                method: 'POST',
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Datele companiei au fost trimise cu succes către ' + clientEmail);
                    sendModal.hide();
                } else {
                    throw new Error(data.message || 'Eroare la trimiterea email-ului');
                }
            })
            .catch(error => {
                console.error('Eroare la trimitere:', error);
                alert('A apărut o eroare: ' + error.message);
            })
            .finally(() => {
                // Resetare buton
                confirmSendBtn.innerHTML = '<i class="bx bx-send me-1"></i>Trimite acum';
                confirmSendBtn.disabled = false;
            });
        });
    });
</script>

<?php 
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
require_once 'template/footer.php'; 
?>