<div class="modal-body">
    <form id="addClientForm" class="needs-validation" novalidate>
        <div class="row g-3">
            <div class="col-12">
                <label for="newClientNumeCompanie" class="form-label">Nume Companie:</label>
                <input type="text" class="form-control" id="newClientNumeCompanie" name="nume_companie" required>
                <div class="invalid-feedback">
                    Te rog introdu numele companiei.
                </div>
            </div>
            <div class="col-12">
                <label for="newClientPersoanaContact" class="form-label">Persoană Contact:</label>
                <input type="text" class="form-control" id="newClientPersoanaContact" name="persoana_contact">
            </div>
            <div class="col-md-6">
                <label for="newClientTelefon" class="form-label">Telefon:</label>
                <input type="tel" class="form-control" id="newClientTelefon" name="telefon">
            </div>
            <div class="col-md-6">
                <label for="newClientEmail" class="form-label">Email:</label>
                <input type="email" class="form-control" id="newClientEmail" name="email">
                <div class="invalid-feedback">
                    Te rog introdu o adresă de email validă.
                </div>
            </div>
            <div class="col-12">
                <label for="newClientAdresa" class="form-label">Adresă:</label>
                <textarea class="form-control" id="newClientAdresa" name="adresa" rows="2"></textarea>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
    <button type="submit" form="addClientForm" class="btn btn-primary">Salvează Client</button>
</div>
